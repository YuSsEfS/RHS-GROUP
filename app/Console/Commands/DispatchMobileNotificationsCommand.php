<?php

namespace App\Console\Commands;

use App\Models\MobilePushToken;
use App\Models\AdminEmployeeConversation;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\ExpoPushNotificationService;
use App\Services\SidebarNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DispatchMobileNotificationsCommand extends Command
{
    protected $signature = 'mobile:notifications:dispatch {--watch : Keep running like a worker} {--interval=12 : Poll interval in seconds}';

    protected $description = 'Dispatch mobile push notifications for unread messages, reunions, matching results, relances, and CV processing.';

    private array $watched = [
        'conversations' => ['Nouveau message', 'Une conversation RHS a recu une nouvelle reponse.', 'messages'],
        'meetings' => ['Nouvelle reunion', 'Une reunion est disponible dans votre planning.', 'resources'],
        'matching_history' => ['Matching termine', 'Des resultats de matching sont prets a consulter.', 'matching'],
        'client_alerts' => ['Relance client', 'Une relance client attend votre suivi.', 'requests'],
        'assigned_requests' => ['Nouvelle demande assignee', 'Une demande client a ete assignee a votre espace.', 'requests'],
        'client_requests' => ['Nouvelle demande client', 'Une demande client attend traitement.', 'requests'],
        'external_batches' => ['Traitement CV externe', 'Un lot de CV externe demande votre attention.', 'cvs'],
        'cv_imports' => ['Import CV', 'Un import CV est en attente ou en cours.', 'cvs'],
    ];

    public function handle(SidebarNotificationService $notifications, ExpoPushNotificationService $push): int
    {
        do {
            $this->dispatchOnce($notifications, $push);

            if (!$this->option('watch')) {
                break;
            }

            sleep(max(5, (int) $this->option('interval')));
        } while (true);

        return self::SUCCESS;
    }

    private function dispatchOnce(SidebarNotificationService $notifications, ExpoPushNotificationService $push): void
    {
        MobilePushToken::query()
            ->with('user')
            ->whereNotNull('expo_push_token')
            ->chunkById(100, function ($tokens) use ($notifications, $push) {
                $messages = [];

                foreach ($tokens as $token) {
                    if (!$token->user) {
                        continue;
                    }

                    $counts = $this->countsFor($token->user, $notifications);
                    $previous = $token->last_counts;

                    if (is_array($previous)) {
                        foreach ($this->notificationMessages($token, $previous, $counts) as $message) {
                            $messages[] = $message;
                        }
                    }

                    $token->forceFill([
                        'last_counts' => $counts,
                        'last_notified_at' => !empty($messages) ? now() : $token->last_notified_at,
                    ])->save();
                }

                if ($messages) {
                    $push->send($messages);
                    $this->info('Sent ' . count($messages) . ' mobile push notification(s).');
                }
            });
    }

    private function countsFor(User $user, SidebarNotificationService $notifications): array
    {
        if ($user->isAdmin()) {
            return array_map('intval', $notifications->forAdmin($user)['items'] ?? []);
        }

        if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
            return array_map('intval', $notifications->forEmployee($user)['items'] ?? []);
        }

        return [];
    }

    private function notificationMessages(MobilePushToken $token, array $previous, array $current): array
    {
        $messages = [];
        $user = $token->user;

        foreach ($this->watched as $key => [$title, $body, $target]) {
            $before = (int) ($previous[$key] ?? 0);
            $now = (int) ($current[$key] ?? 0);

            if ($now <= $before) {
                continue;
            }

            [$summaryTitle, $summaryBody, $collapseId] = $this->summaryFor($user, $key, $title, $body, $now - $before);

            $messages[] = [
                'to' => $token->expo_push_token,
                'sound' => 'default',
                'title' => $summaryTitle,
                'body' => $summaryBody,
                'subtitle' => $now > 1 ? $now . ' notification(s) en attente' : null,
                'badge' => max(1, array_sum($current)),
                'channelId' => 'rhs-live',
                'priority' => 'high',
                'ttl' => 86400,
                'collapseId' => $collapseId,
                'data' => [
                    'target' => $target,
                    'key' => $key,
                    'count' => $now,
                ],
            ];
        }

        return $messages;
    }

    private function summaryFor(?User $user, string $key, string $title, string $body, int $delta): array
    {
        if (!$user) {
            return [$title, $delta > 1 ? $body . ' (+' . $delta . ')' : $body, 'rhs-' . $key];
        }

        if ($key === 'conversations' && Schema::hasTable('admin_employee_conversations')) {
            $conversation = AdminEmployeeConversation::query()
                ->with(['latestMessage.sender:id,name', 'participantOneUser:id,name', 'participantTwoUser:id,name'])
                ->forParticipant($user)
                ->whereHas('messages', fn ($query) => $query->where('sender_id', '!=', $user->id)->whereNull('seen_at'))
                ->latest('last_message_at')
                ->first();

            if ($conversation?->latestMessage) {
                $sender = $conversation->latestMessage->sender?->name ?: 'RHS';
                $preview = trim((string) $conversation->latestMessage->body) !== ''
                    ? Str::limit(trim((string) $conversation->latestMessage->body), 110)
                    : ($conversation->latestMessage->attachment_original_name ?: 'Piece jointe');
                $count = $conversation->unreadMessagesCountFor($user);

                return [
                    'Message de ' . $sender,
                    $count > 1 ? $sender . ' vous a envoye ' . $count . ' messages. Dernier: ' . $preview : $sender . ': ' . $preview,
                    'rhs-conversation-' . $conversation->id,
                ];
            }
        }

        if ($key === 'meetings' && Schema::hasTable('meeting_participants')) {
            $participant = MeetingParticipant::query()
                ->with('meeting')
                ->where('user_id', $user->id)
                ->whereNull('notification_read_at')
                ->latest('id')
                ->first();

            if ($participant?->meeting) {
                $meeting = $participant->meeting;
                $date = optional($meeting->meeting_date)->format('d/m/Y');
                $time = $meeting->start_time ? substr((string) $meeting->start_time, 0, 5) : null;

                return [
                    'Reunion planifiee',
                    trim(($meeting->title ?: 'Reunion RHS') . ' - ' . $date . ($time ? ' a ' . $time : '')),
                    'rhs-meeting-' . $meeting->id,
                ];
            }
        }

        return [$title, $delta > 1 ? $body . ' (+' . $delta . ')' : $body, 'rhs-' . $key];
    }
}
