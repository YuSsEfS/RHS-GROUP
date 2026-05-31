@extends('admin.layouts.app')

@section('title', 'Conversation de relance client')
@section('page_title', 'Conversation de relance client')
@section('page_subtitle', 'Echange detaille avec le client, reponse rapide et suivi du statut de relance.')

@section('top_actions')
    <a href="{{ route('admin.client-request-alerts.index') }}" class="btn btn-ghost">Retour a la liste</a>
@endsection

@section('content')
    <section class="rhs-chat-shell">
        <aside class="rhs-chat-sidebar">
            <div class="rhs-chat-sidebar-head">
                <div>
                    <div class="rhs-chat-sidebar-title">Relance client</div>
                    <div class="rhs-chat-sidebar-copy">Fil de suivi detaille avec contexte demande, date et statut.</div>
                </div>
            </div>

            <div class="rhs-chat-list">
                <div class="portal-record" style="background:#162129; color:#f8fafc; border-color:rgba(255,255,255,.06); box-shadow:none;">
                    <div class="portal-mini-list">
                        <div class="portal-mini-item"><span class="rhs-chat-priority">Client</span><div class="portal-mini-copy" style="color:#e2e8f0;">{{ $alert->clientUser?->name ?: '-' }}</div></div>
                        <div class="portal-mini-item"><span class="rhs-chat-priority">Demande</span><div class="portal-mini-copy" style="color:#e2e8f0;">{{ $alert->recruitmentRequest?->position_title ?: '-' }}</div></div>
                        <div class="portal-mini-item"><span class="rhs-chat-priority">Date</span><div class="portal-mini-copy" style="color:#e2e8f0;">{{ $alert->created_at->format('d/m/Y H:i') }}</div></div>
                        <div class="portal-mini-item"><span class="rhs-chat-priority">Statut</span><div class="portal-mini-copy" style="color:#e2e8f0;">{{ $statuses[$alert->status] ?? $alert->status }}</div></div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="rhs-chat-main">
            <div class="rhs-chat-header">
                <div class="rhs-chat-header-main">
                    <span class="rhs-chat-avatar">{{ strtoupper(substr($alert->clientUser?->name ?: 'C', 0, 1)) }}</span>
                    <div class="rhs-chat-header-copy">
                        <strong>{{ $alert->clientUser?->name ?: 'Client' }}</strong>
                        <span>{{ $alert->recruitmentRequest?->position_title ?: 'Demande de recrutement' }}</span>
                    </div>
                </div>
            </div>

            <div class="rhs-chat-messages">
                @php $previousDate = null; @endphp
                @forelse($threadAlerts as $threadAlert)
                    @php
                        $messageDate = $threadAlert->created_at->format('Y-m-d');
                    @endphp

                    @if($messageDate !== $previousDate)
                        <div class="rhs-chat-day">{{ $threadAlert->created_at->translatedFormat('l d F Y') }}</div>
                        @php $previousDate = $messageDate; @endphp
                    @endif

                    <div class="rhs-chat-row">
                        <div class="rhs-chat-bubble">
                            <div class="rhs-chat-meta">
                                <span>{{ $threadAlert->clientUser?->name ?: 'Client' }}</span>
                                <span>{{ $threadAlert->created_at->format('H:i') }}</span>
                            </div>
                            <div class="rhs-chat-body">{{ $threadAlert->message ?: 'Relance sans message complementaire.' }}</div>
                            <div class="rhs-chat-status">{{ $statuses[$threadAlert->status] ?? $threadAlert->status }}</div>
                        </div>
                    </div>

                    @if($threadAlert->admin_response)
                        <div class="rhs-chat-row is-me">
                            <div class="rhs-chat-bubble">
                                <div class="rhs-chat-meta">
                                    <span>{{ $threadAlert->responder?->name ?: 'RHS' }}</span>
                                    <span>{{ optional($threadAlert->responded_at)->format('H:i') ?: $threadAlert->updated_at->format('H:i') }}</span>
                                </div>
                                <div class="rhs-chat-body">{{ $threadAlert->admin_response }}</div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="rhs-chat-placeholder">
                        <div class="rhs-chat-placeholder-card">
                            <strong>Aucune relance pour le moment</strong>
                            <span>Le client n a pas encore envoye de message sur cette demande.</span>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="rhs-chat-composer">
                <form method="POST" action="{{ route('admin.client-request-alerts.update', $alert) }}" class="form-grid rhs-chat-config-card" style="background:transparent; border:0; padding:0;">
                    @csrf
                    @method('PUT')

                    <div class="form-field">
                        <label class="form-label" for="status">Statut de la relance</label>
                        <select class="form-select select-theme" id="status" name="status">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $alert->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="quick_response">Reponse rapide</label>
                        <select class="form-select select-theme" id="quick_response" name="quick_response">
                            <option value="">Aucune</option>
                            @foreach($quickResponses as $response)
                                <option value="{{ $response }}" @selected(old('quick_response') === $response)>{{ $response }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field full">
                        <label class="form-label" for="admin_response">Reponse personnalisee</label>
                        <textarea class="form-textarea" id="admin_response" name="admin_response" rows="5">{{ old('admin_response', $alert->admin_response) }}</textarea>
                    </div>

                    <div class="full form-actions">
                        <button type="submit" class="btn btn-primary">Envoyer la reponse</button>
                        <a href="{{ route('admin.client-request-alerts.index') }}" class="btn btn-ghost">Retour</a>
                    </div>
                </form>
            </div>
        </section>
    </section>
@endsection
