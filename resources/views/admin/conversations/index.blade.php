@extends('admin.layouts.app')

@section('title', 'Messagerie interne')
@section('page_title', 'Messagerie interne')
@section('page_subtitle', 'Une interface de discussion claire entre administration et employes, avec priorites, pieces jointes et suivi de lecture.')

@section('content')
    @php($isEmptyConversationSurface = !request()->boolean('create'))
    @php($unreadTotal = $conversations->getCollection()->sum(fn ($item) => $item->unreadMessagesCountFor($user)))
    <section class="rhs-chat-shell rhs-chat-shell-modern rhs-chat-create-shell">
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
                    <input type="search" value="" placeholder="Rechercher une conversation" data-chat-list-search>
                </div>
            </div>

            <div class="rhs-chat-tabs" aria-label="Filtres conversations">
                <button type="button" class="is-active" data-chat-list-filter="all">Tous</button>
                <button type="button" data-chat-list-filter="unread">Non lus</button>
                <button type="button" data-chat-list-filter="group">Groupes</button>
            </div>

            <div class="rhs-chat-list" data-chat-list>
                @forelse($conversations as $conversation)
                    @php($displayName = $conversation->displayNameFor($user))
                    @php($otherParticipant = $conversation->otherParticipantFor($user))
                    @php($unreadCount = $conversation->unreadMessagesCountFor($user))
                    <a
                        href="{{ route('admin.conversations.show', $conversation) }}"
                        class="rhs-chat-item"
                        data-chat-item
                        data-chat-search="{{ strtolower($displayName . ' ' . $conversation->subject . ' ' . optional($conversation->latestMessage)->body . ' ' . $conversation->displaySubtitleFor($user)) }}"
                        data-chat-unread="{{ $unreadCount > 0 ? '1' : '0' }}"
                        data-chat-group="{{ $conversation->isGroup() ? '1' : '0' }}"
                    >
                        <span class="rhs-chat-avatar">
                            @if(!$conversation->isGroup() && $otherParticipant?->profile_photo_url)
                                <img src="{{ $otherParticipant->profile_photo_url }}" alt="{{ $displayName }}">
                            @else
                                {{ $conversation->avatarInitialFor($user) }}
                            @endif
                        </span>
                        <span class="rhs-chat-details">
                            <span class="rhs-chat-row-top">
                                <span class="rhs-chat-name truncate-safe">{{ $displayName }}</span>
                                <span class="rhs-chat-time">{{ optional($conversation->last_message_at ?: $conversation->updated_at)->format('H:i') }}</span>
                            </span>
                            <span class="rhs-chat-subject truncate-safe">{{ $conversation->subject }}</span>
                            <span class="rhs-chat-preview truncate-safe">
                                {{ \Illuminate\Support\Str::limit(optional($conversation->latestMessage)->body ?: 'Piece jointe / conversation sans texte.', 72) }}
                            </span>
                            <span class="rhs-chat-subline truncate-safe">
                                {{ $conversation->displaySubtitleFor($user) }}
                                @if(!$conversation->isGroup() && $otherParticipant)
                                    - {{ $otherParticipant->email }}
                                @endif
                            </span>
                        </span>
                        <span class="rhs-chat-side-meta">
                            @if($unreadCount > 0)
                                <span class="rhs-chat-badge">{{ $unreadCount }}</span>
                            @endif
                            <span class="rhs-chat-priority {{ $conversation->priority === 'urgent' ? 'is-urgent' : '' }}">
                                {{ $priorities[$conversation->priority] ?? ucfirst($conversation->priority) }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucune conversation</div>
                        <div class="portal-empty-copy">Demarrez votre premier echange avec un employe.</div>
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="rhs-chat-main rhs-chat-card">
            <div class="rhs-chat-header rhs-chat-create-hero">
                <div class="rhs-chat-header-main">
                    <span class="rhs-chat-avatar rhs-chat-avatar-lg">+</span>
                    <div class="rhs-chat-header-copy">
                        <span class="rhs-chat-create-kicker">Nouvel echange</span>
                        <strong>Demarrer une conversation</strong>
                        <span>Selectionnez un employe, definissez la priorite puis lancez un premier message.</span>
                    </div>
                </div>
                @if($isEmptyConversationSurface)
                    <div class="rhs-chat-header-actions">
                        <a href="{{ route('admin.conversations.index', ['create' => 1]) }}" class="rhs-chat-action-link">Creer</a>
                    </div>
                @endif
            </div>

            <div class="rhs-chat-placeholder rhs-chat-placeholder-form">
                @if($isEmptyConversationSurface)
                    <div class="rhs-chat-empty-conversation">
                        <span class="rhs-chat-avatar rhs-chat-avatar-lg">+</span>
                        <strong>Ouvrez une conversation</strong>
                        <p>Selectionnez une discussion dans la liste ou creez un nouvel echange.</p>
                        <a href="{{ route('admin.conversations.index', ['create' => 1]) }}" class="btn btn-primary">Creer une conversation</a>
                    </div>
                @else
                <div class="rhs-chat-create-card">
                    <form method="POST" action="{{ route('admin.conversations.store') }}" class="conversation-create-form" enctype="multipart/form-data" data-conversation-create-form>
                        @csrf
                        <div class="rhs-create-section">
                            <label class="form-label" for="conversation_mode">Type</label>
                            <select id="conversation_mode" name="conversation_mode" class="rhs-create-native-select" data-native-select data-conversation-mode>
                                <option value="direct">Conversation directe</option>
                                <option value="group">Groupe</option>
                            </select>
                            <div class="rhs-create-type-grid" data-create-type-grid>
                                <button type="button" class="is-active" data-create-type="direct">
                                    <span>+</span>
                                    <strong>Directe</strong>
                                    <small>1-a-1</small>
                                </button>
                                <button type="button" data-create-type="group">
                                    <span>#</span>
                                    <strong>Groupe</strong>
                                    <small>Equipe</small>
                                </button>
                            </div>
                        </div>

                        <div class="rhs-create-grid">
                        <div class="form-field" data-direct-conversation-field>
                            <label class="form-label" for="employee_user_id">Employe</label>
                            <select id="employee_user_id" name="employee_user_id" class="form-select select-theme">
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->hasRole(\App\Models\User::ROLE_SUPERVISOR) ? 'Superviseur' : 'Employe' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field" data-group-conversation-field hidden>
                            <label class="form-label" for="group_name">Nom du groupe</label>
                            <input id="group_name" name="group_name" class="form-input" type="text" placeholder="Ex: Equipe recrutement Casa">
                        </div>

                        <div class="form-field full" data-group-conversation-field hidden>
                            <label class="form-label" for="participant_user_ids">Participants</label>
                            <select id="participant_user_ids" name="participant_user_ids[]" class="form-select select-theme conversation-participant-select" multiple size="6">
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->hasRole(\App\Models\User::ROLE_SUPERVISOR) ? 'Superviseur' : 'Employe' }}</option>
                                @endforeach
                            </select>
                            <div class="form-help">Selectionnez les participants du groupe. Le champ est masque pour une conversation directe.</div>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="priority">Priorite</label>
                            <select id="priority" name="priority" class="rhs-create-native-select" data-native-select data-create-priority-select>
                                @foreach($priorities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="rhs-create-priority-grid" data-create-priority-grid>
                                @foreach($priorities as $value => $label)
                                    <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-create-priority="{{ $value }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        </div>

                        <div class="form-field full">
                            <label class="form-label" for="subject">Sujet</label>
                            <input id="subject" name="subject" class="form-input" type="text" required placeholder="Ex. Validation du contrat de Marc">
                        </div>

                        <div class="form-field full">
                            <label class="form-label" for="body">Message</label>
                            <div class="rhs-create-message-box">
                                <textarea id="body" name="body" rows="6" placeholder="Bonjour,&#10;&#10;Je te contacte au sujet de..."></textarea>
                                <div class="rhs-create-message-tools">
                                    <label for="attachment" title="Ajouter une piece jointe">
                                        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" aria-hidden="true">
                                            <path d="M8 12.5 13.5 7a3.18 3.18 0 0 1 4.5 4.5l-7.25 7.25a5 5 0 0 1-7.07-7.07L11 4.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </label>
                                    <label for="attachment" title="Ajouter des images">
                                        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" aria-hidden="true">
                                            <path d="M5 5h14v14H5V5Z" stroke="currentColor" stroke-width="1.8"/><path d="m8 16 3-3 2 2 2-3 3 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </label>
                                    <span data-create-file-summary>Glissez vos fichiers ici</span>
                                </div>
                            </div>
                        </div>

                        <input id="attachment" name="attachments[]" type="file" class="rhs-file-card-input" multiple data-create-file-input>

                        <div class="rhs-create-actions">
                            <a href="{{ route('admin.conversations.index', ['empty' => 1]) }}" class="btn btn-ghost">Annuler</a>
                            <button type="submit" class="btn btn-primary rhs-chat-send-btn">Envoyer le message</button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </section>

        <aside class="rhs-chat-info rhs-chat-card">
            <div class="rhs-chat-profile-card">
                <span class="rhs-chat-avatar rhs-chat-profile-avatar">AD</span>
                <strong>Nouvelle discussion</strong>
                <span>Choisissez un destinataire pour afficher le contexte.</span>
            </div>
            <div class="rhs-chat-side-section">
                <div class="rhs-chat-side-title"><span>Pieces jointes</span><button type="button" disabled>Voir tout</button></div>
                <div class="rhs-chat-empty-note">Les derniers fichiers envoyes apparaitront ici apres creation.</div>
            </div>
            <div class="rhs-chat-side-section">
                <div class="rhs-chat-side-title"><span>Activite recente</span></div>
                <div class="rhs-chat-activity-list">
                    <div><i></i><span><strong>Conversation prete</strong><small>En attente du premier message</small></span></div>
                </div>
            </div>
        </aside>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mode = document.querySelector('[data-conversation-mode]');
            const directFields = document.querySelectorAll('[data-direct-conversation-field]');
            const groupFields = document.querySelectorAll('[data-group-conversation-field]');

            if (!mode) {
                return;
            }

            function refreshMode() {
                const isGroup = mode.value === 'group';
                document.querySelector('[data-conversation-create-form]')?.classList.toggle('is-group-mode', isGroup);

                const toggleField = (field, hidden) => {
                    field.hidden = hidden;
                    field.querySelectorAll('input, select, textarea').forEach((control) => {
                        control.disabled = hidden;
                    });
                };

                directFields.forEach((field) => toggleField(field, isGroup));
                groupFields.forEach((field) => toggleField(field, !isGroup));
            }

            mode.addEventListener('change', refreshMode);
            refreshMode();

            document.querySelectorAll('[data-create-type]').forEach((button) => {
                button.addEventListener('click', () => {
                    mode.value = button.dataset.createType || 'direct';
                    mode.dispatchEvent(new Event('change'));
                    document.querySelectorAll('[data-create-type]').forEach((item) => item.classList.toggle('is-active', item === button));
                });
            });

            const prioritySelect = document.querySelector('[data-create-priority-select]');
            document.querySelectorAll('[data-create-priority]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (prioritySelect) {
                        prioritySelect.value = button.dataset.createPriority || prioritySelect.value;
                    }
                    document.querySelectorAll('[data-create-priority]').forEach((item) => item.classList.toggle('is-active', item === button));
                });
            });

            const fileInput = document.querySelector('[data-create-file-input]');
            const fileSummary = document.querySelector('[data-create-file-summary]');
            fileInput?.addEventListener('change', () => {
                const files = Array.from(fileInput.files || []);
                fileSummary.textContent = files.length
                    ? files.length + (files.length > 1 ? ' fichiers selectionnes' : ' fichier selectionne')
                    : 'Glissez vos fichiers ici';
            });
        });
    </script>
    @include('partials.rhs-chat-composer-script')
    @include('partials.rhs-chat-search-script')
@endsection
