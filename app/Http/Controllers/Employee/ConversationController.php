<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AdminEmployeeConversation;
use App\Models\AdminEmployeeMessage;
use App\Models\User;
use App\Services\OfficeAttachmentPreviewService;
use App\Services\SidebarNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class ConversationController extends Controller
{
    public function index()
    {
        $employee = auth()->user();

        $conversations = AdminEmployeeConversation::query()
            ->with($this->conversationListRelations())
            ->forParticipant($employee)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        $this->markMessagesDelivered($employee, $conversations->getCollection()->pluck('id')->all());

        return view('employee.conversations.index', [
            'user' => $employee,
            'conversations' => $conversations,
            'priorities' => AdminEmployeeConversation::availablePriorities(),
            'statuses' => AdminEmployeeConversation::availableStatuses(),
            'recipients' => User::query()
                ->where('status', User::STATUS_APPROVED)
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])
                ->whereKeyNot($employee->id)
                ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [User::ROLE_ADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = auth()->user();

        $validated = $request->validate([
            'recipient_user_id' => [
                'required',
                'integer',
                Rule::notIn([$employee->id]),
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status', User::STATUS_APPROVED)
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]);
                }),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(array_keys(AdminEmployeeConversation::availablePriorities()))],
            'body' => ['required_without_all:attachment,attachments', 'nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*' => ['file', 'max:51200'],
        ]);

        $conversation = AdminEmployeeConversation::create([
            'admin_user_id' => $employee->id,
            'employee_user_id' => (int) $validated['recipient_user_id'],
            'conversation_type' => AdminEmployeeConversation::TYPE_DIRECT,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => AdminEmployeeConversation::STATUS_OPEN,
            'last_message_at' => now(),
            'admin_seen_at' => now(),
            'employee_seen_at' => null,
        ]);

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $conversation->participants()->syncWithoutDetaching([
                $employee->id => ['seen_at' => now()],
                (int) $validated['recipient_user_id'] => ['seen_at' => null],
            ]);
        }

        $this->storeMessages($conversation, $request, $validated['body'] ?? null);

        return redirect()
            ->route('employee.messages.show', $conversation)
            ->with('success', 'Conversation demarree avec succes.');
    }

    public function show(AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant(auth()->user()), 403);

        $conversation->load($this->conversationShowRelations());

        $this->markMessagesDelivered(auth()->user(), [$conversation->id]);
        $seenMessages = $this->markMessagesSeen(auth()->user(), $conversation);
        $conversation->markSeenFor(auth()->user());
        if ($seenMessages > 0) {
            app(SidebarNotificationService::class)->clearFor(auth()->user());
        }

        return view('employee.conversations.show', [
            'user' => auth()->user(),
            'conversation' => $conversation,
            'conversations' => AdminEmployeeConversation::query()
                ->with($this->conversationListRelations())
                ->forParticipant(auth()->user())
                ->orderByDesc('last_message_at')
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(),
            'priorities' => AdminEmployeeConversation::availablePriorities(),
            'statuses' => AdminEmployeeConversation::availableStatuses(),
        ]);
    }

    public function send(Request $request, AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant(auth()->user()), 403);

        $validated = $request->validate([
            'body' => ['required_without_all:attachment,attachments', 'nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:20480'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*' => ['file', 'max:51200'],
        ]);

        $messages = $this->storeMessages($conversation, $request, $validated['body'] ?? null);

        if ($request->expectsJson()) {
            return response()->json($this->sentMessagesPayload($conversation, $messages));
        }

        return back()->with('success', 'Message envoye.');
    }

    public function messages(Request $request, AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant(auth()->user()), 403);

        $this->markMessagesDelivered(auth()->user(), [$conversation->id]);
        $seenMessages = $this->markMessagesSeen(auth()->user(), $conversation);
        $conversation->markSeenFor(auth()->user());
        if ($seenMessages > 0) {
            app(SidebarNotificationService::class)->clearFor(auth()->user());
        }

        $meta = $this->messageListMeta($conversation);

        if ($this->requestHasCurrentMessages($request, $meta)) {
            return response()->json($meta);
        }

        return response()->json($this->messageListPayload($conversation));
    }

    public function attachment(Request $request, AdminEmployeeMessage $message)
    {
        abort_unless($message->conversation?->isParticipant(auth()->user()), 403);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        $filename = str_replace(['"', '\\'], '', $message->attachment_original_name ?: basename($message->attachment_path));

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($message->attachment_path, $filename);
        }

        return Storage::disk('local')->response(
            $message->attachment_path,
            $filename,
            [
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    public function preview(AdminEmployeeMessage $message, OfficeAttachmentPreviewService $previewer)
    {
        abort_unless($message->conversation?->isParticipant(auth()->user()), 403);
        abort_unless($message->isWordAttachment() || $message->isSpreadsheetAttachment(), 404);

        $html = $previewer->render($message);

        return response($html ?: $previewer->fallback($message))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function downloadAttachments(Request $request, AdminEmployeeConversation $conversation)
    {
        abort_unless($conversation->isParticipant(auth()->user()), 403);

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:50'],
            'attachments.*' => ['integer'],
        ]);

        $messages = AdminEmployeeMessage::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $validated['attachments'])
            ->whereNotNull('attachment_path')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        abort_if($messages->isEmpty(), 404);

        return $this->downloadMessagesZip($messages, 'conversation-' . $conversation->id . '-pieces-jointes');
    }

    public function destroyMessage(AdminEmployeeMessage $message)
    {
        $user = auth()->user();
        $conversation = $message->conversation;

        abort_unless($conversation?->isParticipant($user), 403);
        abort_unless($message->canBeDeletedBy($user), 403);

        if ($message->attachment_path && Storage::disk('local')->exists($message->attachment_path)) {
            Storage::disk('local')->delete($message->attachment_path);
        }

        $message->delete();

        $latestMessage = AdminEmployeeMessage::query()
            ->where('conversation_id', $conversation->id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $conversation->forceFill([
            'last_message_at' => $latestMessage?->created_at,
        ])->save();

        if (request()->expectsJson()) {
            return response()->json($this->messageListPayload($conversation));
        }

        return back()->with('success', 'Message supprime.');
    }

    private function messageListPayload(AdminEmployeeConversation $conversation): array
    {
        $conversation->load($this->conversationShowRelations());
        $lastMessage = $conversation->messages->last();

        return [
            'html' => view('partials.rhs-chat-message-list', [
                'conversation' => $conversation,
                'attachmentRouteName' => 'employee.messages.attachment',
                'previewRouteName' => 'employee.messages.preview',
                'deleteRouteName' => 'employee.messages.items.destroy',
            ])->render(),
            'last_message_id' => $lastMessage?->id,
            'messages_count' => $conversation->messages->count(),
            'messages_version' => $conversation->messages
                ->map(fn ($message) => optional($message->updated_at)->toIso8601String())
                ->filter()
                ->max(),
            'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
            'unread_conversations' => (int) data_get(app(SidebarNotificationService::class)->forEmployee(auth()->user()), 'items.conversations', 0),
        ];
    }

    private function requestHasCurrentMessages(Request $request, array $meta): bool
    {
        return (string) $request->query('last_message_id', '') === (string) ($meta['last_message_id'] ?? '')
            && (string) $request->query('messages_count', '0') === (string) ($meta['messages_count'] ?? 0)
            && (string) $request->query('messages_version', '') === (string) ($meta['messages_version'] ?? '');
    }

    private function sentMessagesPayload(AdminEmployeeConversation $conversation, $messages): array
    {
        $messages = collect($messages);
        $messages->each->load('sender');
        $conversation->loadMissing(['participantOneUser', 'participantTwoUser']);
        $conversation->setRelation('messages', $messages);

        $meta = $this->messageListMeta($conversation);

        return [
            'append_html' => view('partials.rhs-chat-message-list', [
                'conversation' => $conversation,
                'attachmentRouteName' => 'employee.messages.attachment',
                'previewRouteName' => 'employee.messages.preview',
                'deleteRouteName' => 'employee.messages.items.destroy',
                'suppressFirstDate' => true,
            ])->render(),
            ...$meta,
        ];
    }

    private function messageListMeta(AdminEmployeeConversation $conversation): array
    {
        $messagesVersion = AdminEmployeeMessage::query()
            ->where('conversation_id', $conversation->id)
            ->max('updated_at');

        return [
            'last_message_id' => AdminEmployeeMessage::query()
                ->where('conversation_id', $conversation->id)
                ->max('id'),
            'messages_count' => AdminEmployeeMessage::query()
                ->where('conversation_id', $conversation->id)
                ->count(),
            'messages_version' => $messagesVersion
                ? \Illuminate\Support\Carbon::parse($messagesVersion)->toIso8601String()
                : '',
            'last_message_at' => optional($conversation->fresh()?->last_message_at)->toIso8601String(),
            'unread_conversations' => (int) data_get(app(SidebarNotificationService::class)->forEmployee(auth()->user()), 'items.conversations', 0),
        ];
    }

    private function storeMessages(AdminEmployeeConversation $conversation, Request $request, ?string $body)
    {
        $body = trim((string) $body) !== '' ? trim((string) $body) : null;
        $files = $this->messageFiles($request);
        $messages = collect();

        if (empty($files)) {
            $messages->push($conversation->messages()->create($this->messagePayload($body)));
            $conversation->syncAfterOutgoingMessage(auth()->user());
            $this->clearConversationNotificationCaches($conversation);

            return $messages;
        }

        foreach ($files as $index => $file) {
            $messages->push($conversation->messages()->create($this->messagePayload($index === 0 ? $body : null, $file)));
        }

        $conversation->syncAfterOutgoingMessage(auth()->user());
        $this->clearConversationNotificationCaches($conversation);

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

    private function messagePayload(?string $body, $file = null): array
    {
        $payload = [
            'sender_id' => auth()->id(),
            'body' => $body,
            'delivered_at' => null,
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

    private function markMessagesDelivered(User $user, array $conversationIds): void
    {
        if (empty($conversationIds)) {
            return;
        }

        AdminEmployeeMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);
    }

    private function downloadMessagesZip($messages, string $baseName)
    {
        $tempFolder = storage_path('app/temp');

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        $zipFilename = $baseName . '-' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempFolder . DIRECTORY_SEPARATOR . $zipFilename;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Impossible de creer le fichier ZIP.');
        }

        $addedFiles = 0;

        foreach ($messages as $message) {
            if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
                continue;
            }

            $filename = $message->attachment_original_name ?: basename($message->attachment_path);
            $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: ('piece-jointe-' . $message->id);
            $zip->addFile(Storage::disk('local')->path($message->attachment_path), $message->id . '-' . $safeFilename);
            $addedFiles++;
        }

        $zip->close();

        if ($addedFiles === 0 || !file_exists($zipPath)) {
            return back()->with('error', 'Aucune piece jointe valide trouvee.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function markMessagesSeen(User $user, AdminEmployeeConversation $conversation): int
    {
        return AdminEmployeeMessage::query()
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

    private function conversationListRelations(): array
    {
        $relations = ['participantOneUser', 'participantTwoUser', 'latestMessage.sender'];

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $relations[] = 'participants';
        }

        return $relations;
    }

    private function conversationShowRelations(): array
    {
        $relations = ['participantOneUser', 'participantTwoUser', 'messages.sender'];

        if (Schema::hasTable('admin_employee_conversation_participants')) {
            $relations[] = 'participants';
        }

        return $relations;
    }
}
