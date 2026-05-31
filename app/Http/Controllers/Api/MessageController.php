<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\AdminEmployeeConversation;
use App\Models\AdminEmployeeMessage;
use App\Models\MobilePushToken;
use App\Models\User;
use App\Services\ExpoPushNotificationService;
use App\Services\SidebarNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $timezone = $this->userTimezone($request);

        $conversations = AdminEmployeeConversation::query()
            ->with(['participantOneUser:id,name,email,role,profile_photo_path', 'participantTwoUser:id,name,email,role,profile_photo_path', 'latestMessage.sender:id,name,email,role,profile_photo_path'])
            ->withCount([
                'messages as attachments_count' => fn ($query) => $query->whereNotNull('attachment_path'),
            ])
            ->forParticipant($request->user())
            ->latest('last_message_at')
            ->paginate(20)
            ->through(fn (AdminEmployeeConversation $conversation) => $this->conversationPayload($conversation, $request->user(), $timezone));

        return response()->json($conversations);
    }

    public function targets(Request $request)
    {
        $user = $request->user();
        $role = (string) $user->role;

        $query = User::query()
            ->whereKeyNot($user->id)
            ->when(Schema::hasColumn('users', 'status'), fn ($builder) => $builder->where('status', User::STATUS_APPROVED));

        if ($user->isAdmin()) {
            $query->whereIn('role', [User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]);
        } elseif (in_array($role, [User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR], true)) {
            $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR]);
        } else {
            $query->where('role', User::ROLE_ADMIN);
        }

        return response()->json(
            $query->orderBy('name')
                ->limit(80)
                ->get()
                ->map(fn (User $target) => $this->userPayload($target))
                ->values()
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $timezone = $this->userTimezone($request);

        $data = $request->validate([
            'participant_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id)),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(array_keys(AdminEmployeeConversation::availablePriorities()))],
            'body' => ['required_without_all:attachment,attachments', 'nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*' => ['file', 'max:51200'],
        ]);

        $target = User::query()->findOrFail($data['participant_user_id']);
        [$adminId, $employeeId] = $this->conversationSides($user, $target);

        $conversation = AdminEmployeeConversation::create([
            'admin_user_id' => $adminId,
            'employee_user_id' => $employeeId,
            'conversation_type' => AdminEmployeeConversation::TYPE_DIRECT,
            'group_name' => null,
            'subject' => $data['subject'],
            'priority' => $data['priority'] ?? AdminEmployeeConversation::PRIORITY_NORMAL,
            'status' => AdminEmployeeConversation::STATUS_OPEN,
            'last_message_at' => now(),
            'admin_seen_at' => (int) $adminId === (int) $user->id ? now() : null,
            'employee_seen_at' => (int) $employeeId === (int) $user->id ? now() : null,
        ]);

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $conversation->participants()->syncWithoutDetaching([
                $adminId => ['seen_at' => (int) $adminId === (int) $user->id ? now() : null],
                $employeeId => ['seen_at' => (int) $employeeId === (int) $user->id ? now() : null],
            ]);
        }

        $messages = $this->storeMessages($conversation, $request, $data['body'] ?? null, $user);
        $conversation->load(['participantOneUser:id,name,email,role,profile_photo_path', 'participantTwoUser:id,name,email,role,profile_photo_path']);
        $conversation->loadCount([
            'messages as attachments_count' => fn ($query) => $query->whereNotNull('attachment_path'),
        ]);

        return response()->json([
            ...$this->conversationPayload($conversation, $user, $timezone),
            'messages' => $messages->map(fn (AdminEmployeeMessage $message) => $this->messagePayload($message->load('sender:id,name,email,role,profile_photo_path'), $user, $timezone))->values(),
        ], 201);
    }

    public function show(Request $request, AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant($request->user()), 403);
        $timezone = $this->userTimezone($request);

        $this->markMessagesSeen($request->user(), $conversation);
        $conversation->markSeenFor($request->user());
        app(SidebarNotificationService::class)->clearFor($request->user());

        $conversation->load([
            'participantOneUser:id,name,email,role,profile_photo_path',
            'participantTwoUser:id,name,email,role,profile_photo_path',
            'participants:id,name,email,role,profile_photo_path',
            'messages.sender:id,name,email,role,profile_photo_path',
        ]);
        $conversation->loadCount([
            'messages as attachments_count' => fn ($query) => $query->whereNotNull('attachment_path'),
        ]);

        return response()->json([
            ...$this->conversationPayload($conversation, $request->user(), $timezone),
            'participants' => $conversation->isGroup()
                ? $conversation->participants->map(fn ($user) => $this->userPayload($user))->values()
                : collect([$conversation->participantOneUser, $conversation->participantTwoUser])
                    ->filter()
                    ->map(fn ($user) => $this->userPayload($user))
                    ->values(),
            'messages' => $conversation->messages->map(fn (AdminEmployeeMessage $message) => $this->messagePayload($message, $request->user(), $timezone))->values(),
        ]);
    }

    public function send(Request $request, AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant($request->user()), 403);
        $timezone = $this->userTimezone($request);

        $data = $request->validate([
            'body' => ['required_without_all:attachment,attachments', 'nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*' => ['file', 'max:51200'],
        ]);

        $messages = $this->storeMessages($conversation, $request, $data['body'] ?? null, $request->user());

        return response()->json([
            'messages' => $messages->map(fn (AdminEmployeeMessage $message) => $this->messagePayload($message->load('sender:id,name,email,role,profile_photo_path'), $request->user(), $timezone))->values(),
        ], 201);
    }

    public function attachment(Request $request, AdminEmployeeMessage $message)
    {
        abort_unless($message->conversation?->isParticipant($request->user()), 403);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        $filename = str_replace(['"', '\\'], '', $message->attachment_original_name ?: basename($message->attachment_path));

        return Storage::disk('local')->download($message->attachment_path, $filename);
    }

    private function conversationPayload(AdminEmployeeConversation $conversation, $viewer, string $timezone): array
    {
        $other = $conversation->otherParticipantFor($viewer);
        $latest = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'name' => $conversation->displayNameFor($viewer),
            'title' => $conversation->subject ?: $conversation->displayNameFor($viewer),
            'subtitle' => $conversation->displaySubtitleFor($viewer),
            'type' => $conversation->conversation_type,
            'is_group' => $conversation->isGroup(),
            'priority' => $conversation->priority,
            'priority_label' => AdminEmployeeConversation::availablePriorities()[$conversation->priority] ?? ucfirst((string) $conversation->priority),
            'status' => AdminEmployeeConversation::availableStatuses()[$conversation->status] ?? $conversation->status,
            'unread' => $conversation->unreadFor($viewer),
            'unread_count' => $conversation->unreadMessagesCountFor($viewer),
            'attachments_count' => (int) ($conversation->attachments_count ?? 0),
            'has_attachments' => (int) ($conversation->attachments_count ?? 0) > 0,
            'avatar_initial' => $conversation->avatarInitialFor($viewer),
            'avatar_url' => $other?->profile_photo_url,
            'preview' => $latest?->body ?: ($latest?->attachment_original_name ?: 'Aucun message recent'),
            'last_message' => $latest?->body ?: ($latest?->attachment_original_name ?: null),
            'time' => $this->localDateTime($conversation->last_message_at ?: $conversation->updated_at, $timezone, 'd/m H:i'),
            'updated_at' => $this->isoDateTime($conversation->updated_at, $timezone),
            'timezone' => $timezone,
        ];
    }

    private function messagePayload(AdminEmployeeMessage $message, $viewer, string $timezone): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => (int) $message->sender_id === (int) $viewer->id,
            'sender' => $message->sender ? $this->userPayload($message->sender) : null,
            'attachment' => $message->attachment_path ? [
                'id' => $message->id,
                'name' => $message->attachment_original_name,
                'type' => $message->attachmentTypeLabel(),
                'size' => $message->attachmentSizeForHumans(),
                'mime' => $message->attachment_mime_type,
                'is_image' => $message->isImageAttachment(),
                'is_video' => $message->isVideoAttachment(),
                'is_pdf' => $message->isPdfAttachment(),
                'download_url' => route('api.messages.attachments.download', $message, false),
            ] : null,
            'created_at' => $this->localDateTime($message->created_at, $timezone),
            'created_at_iso' => $this->isoDateTime($message->created_at, $timezone),
        ];
    }

    private function storeMessages(AdminEmployeeConversation $conversation, Request $request, ?string $body, User $sender)
    {
        $body = trim((string) $body) !== '' ? trim((string) $body) : null;
        $files = $this->messageFiles($request);
        $messages = collect();

        if (empty($files)) {
            $messages->push($conversation->messages()->create($this->messagePayloadForStorage($body, null, $sender)));
            $conversation->syncAfterOutgoingMessage($sender);
            $this->clearConversationNotificationCaches($conversation);
            $this->pushConversationNotification($conversation, $sender, $body, $files);

            return $messages;
        }

        foreach ($files as $index => $file) {
            $messages->push($conversation->messages()->create($this->messagePayloadForStorage($index === 0 ? $body : null, $file, $sender)));
        }

        $conversation->syncAfterOutgoingMessage($sender);
        $this->clearConversationNotificationCaches($conversation);
        $this->pushConversationNotification($conversation, $sender, $body, $files);

        return $messages;
    }

    private function messageFiles(Request $request): array
    {
        $files = [];

        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file) {
                    $files[] = $file;
                }
            }
        }

        if (empty($files) && $request->hasFile('attachment')) {
            $files[] = $request->file('attachment');
        }

        return array_slice($files, 0, 30);
    }

    private function messagePayloadForStorage(?string $body, $file, User $sender): array
    {
        $payload = [
            'sender_id' => $sender->id,
            'body' => $body,
            'delivered_at' => now(),
            'seen_at' => null,
        ];

        if ($file) {
            $payload['attachment_path'] = $file->store('private/admin-employee-messages', 'local');
            $payload['attachment_original_name'] = $file->getClientOriginalName();
            $payload['attachment_mime_type'] = $file->getMimeType();
            $payload['attachment_size'] = (int) $file->getSize();
        }

        return $payload;
    }

    private function conversationSides(User $sender, User $target): array
    {
        if ($sender->isAdmin()) {
            return [$sender->id, $target->id];
        }

        if ($target->isAdmin()) {
            return [$target->id, $sender->id];
        }

        $admin = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();

        return [$admin?->id ?: $target->id, $sender->id];
    }

    private function markMessagesSeen(User $user, AdminEmployeeConversation $conversation): void
    {
        AdminEmployeeMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('seen_at')
            ->update([
                'delivered_at' => now(),
                'seen_at' => now(),
            ]);
    }

    private function clearConversationNotificationCaches(AdminEmployeeConversation $conversation): void
    {
        $conversation->loadMissing(['participantOneUser', 'participantTwoUser']);
        $users = collect([$conversation->participantOneUser, $conversation->participantTwoUser])->filter();

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $users = $users->merge($conversation->participants()->get());
        }

        $users
            ->unique('id')
            ->each(fn (User $user) => app(SidebarNotificationService::class)->clearFor($user));
    }

    private function pushConversationNotification(AdminEmployeeConversation $conversation, User $sender, ?string $body, array $files): void
    {
        try {
            $conversation->loadMissing(['participantOneUser', 'participantTwoUser']);
            $recipients = collect([$conversation->participantOneUser, $conversation->participantTwoUser])->filter();

            if (Schema::hasTable('admin_employee_conversation_participants')) {
                $recipients = $recipients->merge($conversation->participants()->get());
            }

            $recipientIds = $recipients
                ->where('id', '!=', $sender->id)
                ->unique('id')
                ->pluck('id')
                ->values();

            if ($recipientIds->isEmpty()) {
                return;
            }

            $tokens = MobilePushToken::query()
                ->with('user')
                ->whereIn('user_id', $recipientIds)
                ->whereNotNull('expo_push_token')
                ->get();

            if ($tokens->isEmpty()) {
                return;
            }

            $preview = trim((string) $body) !== ''
                ? Str::limit(trim((string) $body), 110)
                : count($files) . ' piece' . (count($files) > 1 ? 's' : '') . ' jointe' . (count($files) > 1 ? 's' : '');

            $notifications = app(SidebarNotificationService::class);

            app(ExpoPushNotificationService::class)->send(
                $tokens->map(function (MobilePushToken $token) use ($conversation, $sender, $preview, $notifications) {
                    $recipient = $token->user;
                    $unreadInThread = $recipient ? $conversation->unreadMessagesCountFor($recipient) : 1;
                    $counts = $recipient ? $this->notificationCountsFor($recipient, $notifications) : [];
                    $totalUnread = (int) ($counts['conversations'] ?? $unreadInThread);
                    $summary = $unreadInThread > 1
                        ? $sender->name . ' vous a envoye ' . $unreadInThread . ' messages. Dernier: ' . $preview
                        : $sender->name . ': ' . $preview;

                    return [
                    'to' => $token->expo_push_token,
                    'sound' => 'default',
                    'title' => 'Message de ' . $sender->name,
                    'subtitle' => $totalUnread > 1 ? $totalUnread . ' messages non lus' : null,
                    'body' => $summary,
                    'badge' => max(1, $totalUnread),
                    'channelId' => 'rhs-live',
                    'priority' => 'high',
                    'ttl' => 86400,
                    'collapseId' => 'rhs-conversation-' . $conversation->id,
                    'data' => [
                        'target' => 'messages',
                        'key' => 'conversations',
                        'conversation_id' => $conversation->id,
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'unread_thread_count' => $unreadInThread,
                        'unread_total_count' => $totalUnread,
                    ],
                    ];
                })->all()
            );

            $this->syncPushBaselines($tokens->pluck('user')->filter()->unique('id'));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function syncPushBaselines($users): void
    {
        $notifications = app(SidebarNotificationService::class);

        foreach ($users as $user) {
            $counts = $this->notificationCountsFor($user, $notifications);

            MobilePushToken::query()
                ->where('user_id', $user->id)
                ->get()
                ->each(fn (MobilePushToken $token) => $token->forceFill([
                    'last_counts' => $counts,
                    'last_notified_at' => now(),
                ])->save());
        }
    }

    private function notificationCountsFor(User $user, SidebarNotificationService $notifications): array
    {
        if ($user->isAdmin()) {
            return array_map('intval', $notifications->forAdmin($user)['items'] ?? []);
        }

        if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
            return array_map('intval', $notifications->forEmployee($user)['items'] ?? []);
        }

        return [];
    }

    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}
