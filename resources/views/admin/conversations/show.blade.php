@extends('admin.layouts.app')

@section('title', 'Messagerie interne')
@section('page_title', 'Messagerie interne')
@section('page_subtitle', 'Une interface de discussion claire entre administration et employes, avec priorites, pieces jointes et suivi de lecture.')

@section('top_actions')
    <a href="{{ route('admin.conversations.index', ['create' => 1]) }}" class="btn btn-primary">Nouvelle conversation</a>
@endsection

@section('content')
    @php
        $conversationName = $conversation->displayNameFor($user);
        $conversationSubtitle = $conversation->displaySubtitleFor($user);
        $otherParticipant = $conversation->otherParticipantFor($user);
        $participants = $conversation->isGroup()
            ? $conversation->participants
            : collect([$conversation->participantOneUser, $conversation->participantTwoUser])->filter();
        $attachments = $conversation->messages
            ->filter(fn ($message) => filled($message->attachment_path))
            ->reverse()
            ->values();
        $recentActivity = $conversation->messages
            ->reverse()
            ->take(4)
            ->values();
        $unreadTotal = $conversations->sum(fn ($item) => $item->unreadMessagesCountFor($user));
    @endphp

    <section class="rhs-chat-shell rhs-chat-shell-modern">
        <aside class="rhs-chat-sidebar rhs-chat-card">
            <div class="rhs-chat-sidebar-head">
                <div>
                    <div class="rhs-chat-sidebar-title">Conversations</div>
                    <div class="rhs-chat-sidebar-copy">Suivi des echanges directs avec vos equipes RHS.</div>
                </div>
                <span class="rhs-chat-count" data-chat-unread-total @if($unreadTotal <= 0) hidden style="display:none;" @endif>{{ $unreadTotal }}</span>
            </div>

            <div class="rhs-chat-search">
                <div class="rhs-chat-search-box">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m21 21-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input type="search" placeholder="Rechercher une conversation" data-chat-list-search>
                </div>
            </div>

            <div class="rhs-chat-tabs" aria-label="Filtres conversations">
                <button type="button" class="is-active" data-chat-list-filter="all">Tous</button>
                <button type="button" data-chat-list-filter="unread">Non lus</button>
                <button type="button" data-chat-list-filter="group">Groupes</button>
            </div>

            <div class="rhs-chat-list" data-chat-list>
                @foreach($conversations as $listConversation)
                    @php
                        $listConversationName = $listConversation->displayNameFor($user);
                        $listUnread = $listConversation->unreadMessagesCountFor($user);
                        $listOtherParticipant = $listConversation->otherParticipantFor($user);
                        $listSearch = strtolower($listConversationName . ' ' . $listConversation->subject . ' ' . optional($listConversation->latestMessage)->body . ' ' . $listConversation->displaySubtitleFor($user));
                    @endphp
                    <a
                        href="{{ route('admin.conversations.show', $listConversation) }}"
                        class="rhs-chat-item {{ $listConversation->is($conversation) ? 'is-active' : '' }}"
                        data-chat-item
                        data-chat-search="{{ $listSearch }}"
                        data-chat-unread="{{ $listUnread > 0 ? '1' : '0' }}"
                        data-chat-group="{{ $listConversation->isGroup() ? '1' : '0' }}"
                    >
                        <span class="rhs-chat-avatar">
                            @if(!$listConversation->isGroup() && $listOtherParticipant?->profile_photo_url)
                                <img src="{{ $listOtherParticipant->profile_photo_url }}" alt="{{ $listConversationName }}">
                            @else
                                {{ $listConversation->avatarInitialFor($user) }}
                            @endif
                            <i class="rhs-chat-online-dot"></i>
                        </span>
                        <span class="rhs-chat-details">
                            <span class="rhs-chat-row-top">
                                <span class="rhs-chat-name truncate-safe">{{ $listConversationName }}</span>
                                <span class="rhs-chat-time">{{ optional($listConversation->last_message_at ?: $listConversation->updated_at)->format('H:i') }}</span>
                            </span>
                            <span class="rhs-chat-subject truncate-safe">{{ $listConversation->displaySubtitleFor($user) }}</span>
                            <span class="rhs-chat-preview truncate-safe">
                                {{ \Illuminate\Support\Str::limit(optional($listConversation->latestMessage)->body ?: 'Piece jointe ou conversation sans texte.', 68) }}
                            </span>
                            <span class="rhs-chat-priority {{ $listConversation->priority === 'urgent' ? 'is-urgent' : '' }}">
                                {{ $priorities[$listConversation->priority] ?? ucfirst($listConversation->priority) }}
                            </span>
                        </span>
                        @if($listUnread > 0)
                            <span class="rhs-chat-badge">{{ $listUnread }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </aside>

        <section class="rhs-chat-main rhs-chat-card">
            <div class="rhs-chat-header">
                <div class="rhs-chat-header-main">
                    <span class="rhs-chat-avatar rhs-chat-avatar-lg">{{ $conversation->avatarInitialFor($user) }}</span>
                    <div class="rhs-chat-header-copy">
                        <strong>{{ $conversationName }}</strong>
                        <span><i class="rhs-chat-online-dot is-inline"></i> {{ $conversationSubtitle }} - {{ $statuses[$conversation->status] ?? $conversation->status }}</span>
                    </div>
                </div>

                <div class="rhs-chat-header-actions">
                    <a href="{{ route('admin.conversations.index', ['create' => 1]) }}" class="rhs-chat-action-link">Nouvelle</a>
                    <a href="{{ route('admin.conversations.index', ['empty' => 1]) }}" class="rhs-chat-icon-btn" title="Fermer la conversation" aria-label="Fermer la conversation">x</a>
                    <button type="button" class="rhs-chat-icon-btn" data-message-search-toggle title="Rechercher dans la conversation" aria-label="Rechercher dans la conversation">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m21 21-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <span class="rhs-chat-priority {{ $conversation->priority === 'urgent' ? 'is-urgent' : '' }}">
                        {{ $priorities[$conversation->priority] ?? ucfirst($conversation->priority) }}
                    </span>
                </div>
            </div>

            <div class="rhs-chat-message-search" data-message-search-panel hidden>
                <input type="search" placeholder="Rechercher un message, un fichier..." data-message-search-input>
                <span data-message-search-count>0 resultat</span>
            </div>

            <div
                class="rhs-chat-messages is-preparing-scroll"
                data-message-list
                data-scroll-bottom-on-load
                data-chat-refresh-url="{{ route('admin.conversations.messages.index', $conversation) }}"
                data-chat-conversation-id="{{ $conversation->id }}"
                data-chat-last-message-id="{{ optional($conversation->messages->last())->id }}"
                data-chat-message-count="{{ $conversation->messages->count() }}"
                data-chat-messages-version="{{ $conversation->messages->map(fn ($message) => optional($message->updated_at)->toIso8601String())->filter()->max() }}"
            >
                @include('partials.rhs-chat-message-list', [
                    'conversation' => $conversation,
                    'attachmentRouteName' => 'admin.conversation-messages.attachment',
                    'previewRouteName' => 'admin.conversation-messages.preview',
                    'deleteRouteName' => 'admin.conversation-messages.destroy',
                ])
            </div>

            <div class="rhs-chat-composer">
                <form method="POST" action="{{ route('admin.conversations.messages.store', $conversation) }}" class="rhs-chat-composer-form" enctype="multipart/form-data" data-rhs-chat-composer>
                    @csrf
                    <div class="rhs-chat-composer-box">
                        <div class="rhs-chat-file-preview" data-rhs-chat-file-preview hidden>
                            <span class="rhs-chat-file-preview-logo">RHS</span>
                            <span class="rhs-chat-file-preview-copy">
                                <strong data-rhs-chat-file-name></strong>
                                <small data-rhs-chat-file-meta></small>
                            </span>
                            <button type="button" class="rhs-chat-file-preview-clear" data-rhs-chat-file-clear aria-label="Retirer la piece jointe">x</button>
                        </div>

                        <input id="admin-chat-attachment" name="attachments[]" type="file" multiple class="rhs-chat-attach-input" data-rhs-chat-file-input>
                        <label for="admin-chat-attachment" class="rhs-chat-tool-btn" title="Ajouter une piece jointe">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                                <path d="M8 12.5 13.5 7a3.18 3.18 0 0 1 4.5 4.5l-7.25 7.25a5 5 0 0 1-7.07-7.07L11 4.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </label>
                        <button type="button" class="rhs-chat-tool-btn" title="Image" data-rhs-chat-file-trigger>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                                <path d="M5 5h14v14H5V5Z" stroke="currentColor" stroke-width="1.8"/><path d="m8 16 3-3 2 2 2-3 3 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button type="button" class="rhs-chat-tool-btn" title="Mention" data-rhs-chat-mention>
                            <span>@</span>
                        </button>
                        <textarea id="body" name="body" rows="1" placeholder="Ecrire a {{ $conversationName }}..."></textarea>
                        <input type="hidden" name="priority" value="{{ $conversation->priority }}" data-chat-priority-value>
                        <div class="rhs-chat-priority-picker" aria-label="Priorite du message">
                            @foreach($priorities as $value => $label)
                                <button type="button" class="{{ $conversation->priority === $value ? 'is-active' : '' }}" data-chat-priority-option="{{ $value }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary rhs-chat-send-btn" title="Envoyer">
                            Envoyer
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                                <path d="m3 20 18-8L3 4l2.5 8L21 12 5.5 12 3 20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="rhs-chat-helper">Entree pour envoyer - Maj+Entree pour saut de ligne <span>Lecture confirmee</span></div>
                </form>
            </div>
        </section>

        <aside class="rhs-chat-info rhs-chat-card">
            <div class="rhs-chat-profile-card">
                <span class="rhs-chat-avatar rhs-chat-profile-avatar">{{ $conversation->avatarInitialFor($user) }}</span>
                <strong>{{ $conversationName }}</strong>
                <span>{{ $conversationSubtitle }}</span>
            </div>

            <div class="rhs-chat-side-section">
                <div class="rhs-chat-side-title">
                    <span>Participants</span>
                    <strong>{{ $participants->count() }}</strong>
                </div>
                <div class="rhs-chat-avatar-stack">
                    @foreach($participants->take(6) as $participant)
                        <span title="{{ $participant->name }}">{{ strtoupper(mb_substr($participant->name, 0, 2, 'UTF-8')) }}</span>
                    @endforeach
                    @if($participants->count() > 6)
                        <span>+{{ $participants->count() - 6 }}</span>
                    @endif
                </div>
            </div>

            <div class="rhs-chat-side-section">
                <div class="rhs-chat-side-title">
                    <span>Pieces jointes</span>
                    <button type="button" data-chat-open-attachments {{ $attachments->isEmpty() ? 'disabled' : '' }}>Voir tout</button>
                </div>
                <div class="rhs-chat-side-attachment-data" hidden>
                    @foreach($attachments as $attachment)
                        @php($attachmentUrl = route('admin.conversation-messages.attachment', $attachment))
                        @php($previewUrl = ($attachment->isWordAttachment() || $attachment->isSpreadsheetAttachment()) ? route('admin.conversation-messages.preview', $attachment) : null)
                        <span
                            data-chat-side-attachment
                            data-attachment-id="{{ $attachment->id }}"
                            data-attachment-url="{{ $attachmentUrl }}"
                            data-preview-url="{{ $previewUrl }}"
                            data-download-url="{{ $attachmentUrl }}?download=1"
                            data-attachment-name="{{ e($attachment->attachment_original_name ?: 'Fichier joint') }}"
                            data-attachment-type="{{ e($attachment->attachmentTypeLabel()) }}"
                            data-attachment-size="{{ e($attachment->attachmentSizeForHumans() ?: 'Fichier') }}"
                            data-attachment-kind="{{ $attachment->isImageAttachment() ? 'image' : ($attachment->isVideoAttachment() ? 'video' : ($attachment->isPdfAttachment() ? 'pdf' : (($attachment->isWordAttachment() || $attachment->isSpreadsheetAttachment()) ? 'office' : 'file'))) }}"
                        ></span>
                    @endforeach
                </div>
                <div class="rhs-chat-attachment-list">
                    @forelse($attachments->take(4) as $attachment)
                        @php($attachmentUrl = route('admin.conversation-messages.attachment', $attachment))
                        <button type="button" data-chat-open-attachments data-attachment-id="{{ $attachment->id }}">
                            <span class="rhs-chat-file-icon">{{ $attachment->attachmentTypeLabel() }}</span>
                            <span>
                                <strong>{{ $attachment->attachment_original_name ?: 'Fichier joint' }}</strong>
                                <small>{{ $attachment->attachmentSizeForHumans() ?: 'Fichier' }}</small>
                            </span>
                        </button>
                    @empty
                        <div class="rhs-chat-empty-note">Aucune piece jointe pour le moment.</div>
                    @endforelse
                </div>
            </div>

            <div class="rhs-chat-side-section">
                <div class="rhs-chat-side-title">
                    <span>Activite recente</span>
                </div>
                <div class="rhs-chat-activity-list">
                    @forelse($recentActivity as $activity)
                        <div>
                            <i></i>
                            <span>
                                <strong>{{ $activity->attachment_path ? 'Piece jointe ajoutee' : 'Nouveau message' }}</strong>
                                <small>{{ $activity->created_at?->diffForHumans() }}</small>
                            </span>
                        </div>
                    @empty
                        <div class="rhs-chat-empty-note">Aucune activite recente.</div>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('admin.conversations.update', $conversation) }}" class="rhs-chat-settings-form">
                @csrf
                @method('PATCH')
                <label>
                    <span>Priorite</span>
                    <select name="priority">
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" @selected($conversation->priority === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Statut</span>
                    <select name="status">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($conversation->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn btn-ghost btn-sm" type="submit">Mettre a jour</button>
            </form>
        </aside>
    </section>

    @include('partials.rhs-chat-composer-script')
    @include('partials.rhs-chat-search-script')
    @include('partials.rhs-chat-modals', [
        'bulkDownloadUrl' => route('admin.conversations.attachments.download', $conversation),
    ])
@endsection
