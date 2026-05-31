@php
    /** @var \App\Models\AdminEmployeeConversation $conversation */
    $currentUser = auth()->user();
    $messageGroups = [];
    $suppressFirstDate = $suppressFirstDate ?? false;

    foreach ($conversation->messages as $message) {
        $isAttachment = !empty($message->attachment_path);
        $lastIndex = count($messageGroups) - 1;
        $canJoinPrevious = false;

        if ($isAttachment && $lastIndex >= 0) {
            $lastGroup = $messageGroups[$lastIndex];
            $lastMessage = $lastGroup['messages'][count($lastGroup['messages']) - 1];
            $secondsBetween = $message->created_at && $lastMessage->created_at
                ? abs($message->created_at->diffInSeconds($lastMessage->created_at))
                : 9999;

            $canJoinPrevious = $lastGroup['type'] === 'attachments'
                && (int) $lastGroup['sender_id'] === (int) $message->sender_id
                && $secondsBetween <= 20
                && blank($message->body);
        }

        if ($canJoinPrevious) {
            $messageGroups[$lastIndex]['messages'][] = $message;
            continue;
        }

        $messageGroups[] = [
            'type' => $isAttachment ? 'attachments' : 'message',
            'sender_id' => $message->sender_id,
            'date' => $message->created_at?->format('Y-m-d'),
            'messages' => [$message],
        ];
    }

    $previousDate = null;
@endphp

@foreach($messageGroups as $group)
    @php
        $firstMessage = $group['messages'][0];
        $galleryId = 'message-gallery-' . $firstMessage->id;
        $groupDate = $firstMessage->created_at?->format('Y-m-d');
        $isCurrentUserMessage = (int) $firstMessage->sender_id === (int) $currentUser?->id;
        $bodyMessage = collect($group['messages'])->first(fn ($item) => filled($item->body));
        $attachments = collect($group['messages'])->filter(fn ($item) => !empty($item->attachment_path));
    @endphp

    @if($groupDate !== $previousDate && !($suppressFirstDate && $loop->first))
        <div class="rhs-chat-day">{{ $firstMessage->created_at?->isoFormat('dddd D MMMM YYYY') }}</div>
        @php($previousDate = $groupDate)
    @endif

    <div class="rhs-chat-row {{ $isCurrentUserMessage ? 'is-me' : '' }}" data-message-text="{{ strtolower(($bodyMessage?->body ?: '') . ' ' . $attachments->pluck('attachment_original_name')->join(' ')) }}">
        @if(!$isCurrentUserMessage)
            <span class="rhs-chat-message-avatar">{{ strtoupper(mb_substr($firstMessage->sender?->name ?: 'U', 0, 2, 'UTF-8')) }}</span>
        @endif
        <div class="rhs-chat-bubble {{ $attachments->count() > 1 ? 'has-media-grid' : '' }}">
            <div class="rhs-chat-meta">
                <span>{{ $firstMessage->sender?->name ?: 'Utilisateur' }}</span>
                <span>{{ $firstMessage->created_at?->format('H:i') }}</span>
                @if($attachments->count() > 1)
                    <span>{{ $attachments->count() }} fichier(s)</span>
                @endif
                @if(!$attachments->count() && $firstMessage->canBeDeletedBy($currentUser))
                    <form method="POST" action="{{ route($deleteRouteName, $firstMessage) }}" class="rhs-chat-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Supprimer ce message">Supprimer</button>
                    </form>
                @endif
            </div>

            @if($bodyMessage?->body)
                <div class="rhs-chat-body">{{ $bodyMessage->body }}</div>
            @endif

            @if($attachments->count() === 1)
                @php($attachmentMessage = $attachments->first())
                @include('partials.rhs-chat-attachment', [
                    'message' => $attachmentMessage,
                    'attachmentUrl' => route($attachmentRouteName, $attachmentMessage),
                    'previewUrl' => isset($previewRouteName) && ($attachmentMessage->isWordAttachment() || $attachmentMessage->isSpreadsheetAttachment()) ? route($previewRouteName, $attachmentMessage) : null,
                    'deleteRouteName' => $deleteRouteName,
                    'galleryId' => $galleryId,
                    'compact' => false,
                ])
            @elseif($attachments->count() > 1)
                <div class="rhs-chat-media-grid">
                    @foreach($attachments as $attachmentIndex => $attachmentMessage)
                        @include('partials.rhs-chat-attachment', [
                            'message' => $attachmentMessage,
                            'attachmentUrl' => route($attachmentRouteName, $attachmentMessage),
                            'previewUrl' => isset($previewRouteName) && ($attachmentMessage->isWordAttachment() || $attachmentMessage->isSpreadsheetAttachment()) ? route($previewRouteName, $attachmentMessage) : null,
                            'deleteRouteName' => $deleteRouteName,
                            'galleryId' => $galleryId,
                            'compact' => true,
                            'hiddenInGrid' => $attachmentIndex > 3,
                            'overflowCount' => $attachmentIndex === 3 ? $attachments->count() - 4 : 0,
                        ])
                    @endforeach
                </div>
            @endif

            @if($isCurrentUserMessage)
                @php($lastGroupMessage = collect($group['messages'])->last())
                <div class="rhs-chat-status">{{ $lastGroupMessage->seen_at ? 'Vu' : ($lastGroupMessage->delivered_at ? 'Delivre' : 'Envoye') }}</div>
            @endif
        </div>
    </div>
@endforeach
