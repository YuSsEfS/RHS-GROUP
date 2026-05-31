@php
    /** @var \App\Models\AdminEmployeeMessage $message */
    $attachmentUrl = $attachmentUrl ?? '#';
    $attachmentName = $message->attachment_original_name ?: 'Piece jointe';
    $attachmentType = $message->attachmentTypeLabel();
    $attachmentSize = $message->attachmentSizeForHumans();
    $compact = (bool) ($compact ?? false);
    $galleryId = $galleryId ?? null;
    $messageId = $message->id;
    $hiddenInGrid = (bool) ($hiddenInGrid ?? false);
    $overflowCount = (int) ($overflowCount ?? 0);
    $downloadUrl = $attachmentUrl . (str_contains($attachmentUrl, '?') ? '&' : '?') . 'download=1';
    $previewUrl = $previewUrl ?? null;
    $deleteRouteName = $deleteRouteName ?? null;
    $canDeleteAttachment = $deleteRouteName && $message->canBeDeletedBy(auth()->user());
@endphp

<div
    class="rhs-chat-media-card {{ $compact ? 'is-compact' : '' }} {{ $hiddenInGrid ? 'is-hidden-in-grid' : '' }}"
    data-chat-attachment-item
    data-gallery-id="{{ $galleryId }}"
    data-attachment-id="{{ $messageId }}"
    data-attachment-url="{{ $attachmentUrl }}"
    data-preview-url="{{ $previewUrl }}"
    data-download-url="{{ $downloadUrl }}"
    data-attachment-name="{{ e($attachmentName) }}"
    data-attachment-type="{{ e($attachmentType) }}"
    data-attachment-size="{{ e($attachmentSize ?: 'Fichier') }}"
    data-attachment-kind="{{ $message->isImageAttachment() ? 'image' : ($message->isVideoAttachment() ? 'video' : ($message->isPdfAttachment() ? 'pdf' : (($message->isWordAttachment() || $message->isSpreadsheetAttachment()) ? 'office' : 'file'))) }}"
>
    @if($message->isImageAttachment())
        <button type="button" class="rhs-chat-media-preview rhs-chat-media-image" title="Ouvrir l'image" data-chat-media-open>
            <img src="{{ $attachmentUrl }}" alt="{{ $attachmentName }}">
            @if($compact)
                <span class="rhs-chat-media-overlay-name">{{ $attachmentName }}</span>
            @endif
            @if($overflowCount > 0)
                <span class="rhs-chat-media-more">+{{ $overflowCount }}</span>
            @endif
        </button>
    @elseif($message->isVideoAttachment())
        <video class="rhs-chat-media-preview rhs-chat-media-video" controls preload="metadata">
            <source src="{{ $attachmentUrl }}" type="{{ $message->attachment_mime_type }}">
        </video>
    @elseif($message->isPdfAttachment())
        <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="rhs-chat-media-preview rhs-chat-media-document" title="Ouvrir le PDF">
            <span class="rhs-chat-media-document-icon">PDF</span>
            <span>Consulter le PDF dans l'application</span>
        </a>
    @endif

    <button type="button" class="rhs-chat-attachment" data-chat-media-open>
        <span class="rhs-chat-attachment-icon">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <path d="M8 12.5 13.5 7a3.18 3.18 0 0 1 4.5 4.5l-7.25 7.25a5 5 0 0 1-7.07-7.07L11 4.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="rhs-chat-attachment-copy">
            <span class="rhs-chat-attachment-name">{{ $attachmentName }}</span>
            <span class="rhs-chat-attachment-meta">{{ $attachmentType }}{{ $attachmentSize ? ' - ' . $attachmentSize : '' }} - Ouvrir</span>
        </span>
    </button>

    @if($canDeleteAttachment)
        <form method="POST" action="{{ route($deleteRouteName, $message) }}" class="rhs-chat-media-delete">
            @csrf
            @method('DELETE')
            <button type="submit" title="Supprimer ce fichier">×</button>
        </form>
    @endif
</div>
