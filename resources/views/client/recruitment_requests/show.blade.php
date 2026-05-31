@extends('dashboard.layouts.app')

@section('title', 'Detail de la demande')
@section('brand', 'RHS Client')
@section('brand_sub', 'Portail recrutement')
@section('page_title', 'Detail de ma demande')
@section('page_copy', 'Suivi detaille de votre demande et conversation avec RHS.')

@section('sidebar')
    @include('client._sidebar')
@endsection

@section('top_badge')
    <a href="{{ route('client.recruitment-requests.index') }}" class="admin-btn admin-btn-ghost portal-btn-auto">Retour a l historique</a>
@endsection

@section('content')
    <div class="portal-split">
        <section class="portal-card">
            <h3 class="portal-title-tight">{{ $requestItem->position_title }}</h3>
            <div class="portal-mini-list">
                <div class="portal-mini-item"><span class="portal-status is-muted">Reference</span><div class="portal-mini-copy">{{ $requestItem->reference ?: '-' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-muted">Date</span><div class="portal-mini-copy">{{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-info">Statut</span><div class="portal-mini-copy">{{ $statuses[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-warning">Etape</span><div class="portal-mini-copy">{{ $pipelineStages[$requestItem->pipeline_stage] ?? 'Nouvelle demande' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-muted">Lieu</span><div class="portal-mini-copy">{{ $requestItem->work_location ?: '-' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-muted">Experience</span><div class="portal-mini-copy">{{ $requestItem->experience_years ?: '-' }}</div></div>
            </div>

            @if($requestItem->missions)
                <div class="portal-note" style="margin-top:16px;">
                    <strong style="display:block; margin-bottom:6px;">Missions</strong>
                    {{ $requestItem->missions }}
                </div>
            @endif

            @if($requestItem->admin_notes)
                <div class="portal-note" style="margin-top:16px;">
                    <strong style="display:block; margin-bottom:6px;">Reponse generale RHS</strong>
                    {{ $requestItem->admin_notes }}
                </div>
            @endif
        </section>

        <section class="rhs-chat-main" style="border-radius:24px; overflow:hidden; min-height:620px;">
            <div class="rhs-chat-header">
                <div class="rhs-chat-header-main">
                    <span class="rhs-chat-avatar">R</span>
                    <div class="rhs-chat-header-copy">
                        <strong>Conversation de suivi</strong>
                        <span>Envoyez une relance ou un point de suivi. RHS repondra dans ce fil.</span>
                    </div>
                </div>
            </div>

            <div class="rhs-chat-messages">
                @php $previousDate = null; @endphp
                @forelse($requestItem->clientAlerts as $alert)
                    @php
                        $messageDate = $alert->created_at->format('Y-m-d');
                    @endphp

                    @if($messageDate !== $previousDate)
                        <div class="rhs-chat-day">{{ $alert->created_at->translatedFormat('l d F Y') }}</div>
                        @php $previousDate = $messageDate; @endphp
                    @endif

                    <div class="rhs-chat-row is-me">
                        <div class="rhs-chat-bubble">
                            <div class="rhs-chat-meta">
                                <span>Vous</span>
                                <span>{{ $alert->created_at->format('H:i') }}</span>
                            </div>
                            <div class="rhs-chat-body">{{ $alert->message ?: 'Relance sans message complementaire.' }}</div>
                            <div class="rhs-chat-status">{{ $alertStatuses[$alert->status] ?? $alert->status }}</div>
                        </div>
                    </div>

                    @if($alert->admin_response)
                        <div class="rhs-chat-row">
                            <div class="rhs-chat-bubble">
                                <div class="rhs-chat-meta">
                                    <span>RHS</span>
                                    <span>{{ optional($alert->responded_at)->format('H:i') ?: $alert->updated_at->format('H:i') }}</span>
                                </div>
                                <div class="rhs-chat-body">{{ $alert->admin_response }}</div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="rhs-chat-placeholder">
                        <div class="rhs-chat-placeholder-card">
                            <strong>Aucune relance pour cette demande</strong>
                            <span>Vous pouvez envoyer un premier message de suivi si vous le souhaitez.</span>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="rhs-chat-composer">
                <form method="POST" action="{{ route('client.recruitment-requests.alerts.store', $requestItem) }}" class="rhs-chat-composer-form">
                    @csrf
                    <div></div>
                    <div class="rhs-chat-composer-input">
                        <textarea id="message" name="message" rows="3" placeholder="Precisez votre relance, votre retour ou toute information utile"></textarea>
                        <div class="rhs-chat-helper">Aucune donnee candidat ne sera exposee dans cette conversation.</div>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-primary rhs-chat-send-btn" title="Envoyer">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none">
                            <path d="m3 20 18-8L3 4l2.5 8L21 12 5.5 12 3 20Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
