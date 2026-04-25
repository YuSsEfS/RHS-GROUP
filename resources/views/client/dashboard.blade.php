@extends('dashboard.layouts.app')

@php
    $statusLabels = $statuses ?? \App\Models\RecruitmentRequest::availableStatuses();
    $alertLabels = $alertStatuses ?? \App\Models\ClientRequestAlert::availableStatuses();

    $statusClass = static function (string $status): string {
        return match ($status) {
            'completed', 'shortlisted' => 'is-success',
            'under_review', 'matching_in_progress' => 'is-info',
            'rejected', 'cancelled' => 'is-danger',
            default => 'is-warning',
        };
    };

    $alertClass = static function (string $status): string {
        return match ($status) {
            'processed' => 'is-success',
            'viewed' => 'is-info',
            default => 'is-warning',
        };
    };
@endphp

@section('title', 'Espace client')
@section('brand', 'RHS Client')
@section('brand_sub', 'Portail recrutement')
@section('page_title', 'Mes demandes de recrutement')
@section('page_copy', 'Creez vos demandes, suivez leur avancement et relancez RHS si un dossier prend trop de temps. Aucun CV, profil candidat ou resultat de matching n est expose dans cet espace.')

@section('sidebar')
    <a href="{{ route('client.dashboard') }}" class="is-active">Accueil</a>
@endsection

@section('top_badge')
    <span class="portal-badge">{{ \App\Models\User::availableStatuses()[$user->status] ?? ucfirst($user->status) }}</span>
@endsection

@section('content')
    <div class="portal-grid portal-grid--four" style="margin-bottom:18px;">
        <div class="portal-card">
            <h3>Demandes totales</h3>
            <p class="portal-kpi">{{ $requests->count() }}</p>
        </div>
        <div class="portal-card">
            <h3>En cours</h3>
            <p class="portal-kpi">{{ $requests->whereIn('request_status', ['pending', 'under_review', 'matching_in_progress'])->count() }}</p>
        </div>
        <div class="portal-card">
            <h3>Traitees</h3>
            <p class="portal-kpi">{{ $requests->whereIn('request_status', ['shortlisted', 'completed'])->count() }}</p>
        </div>
        <div class="portal-card">
            <h3>Relances envoyees</h3>
            <p class="portal-kpi">{{ $alertsEnabled ? $requests->sum('client_alerts_count') : 0 }}</p>
        </div>
    </div>

    @if(!$canManageRecruitmentRequests)
        <div class="portal-card">
            <h3 style="margin:0 0 8px;">Module non active</h3>
            <p class="portal-copy" style="margin:0;">
                Votre compte client n a pas encore acces au module de demandes de recrutement. Contactez un administrateur RHS pour l activer.
            </p>
        </div>
    @else
        <div class="portal-split">
            <section class="portal-card">
                <h3 style="margin:0 0 8px;">Nouvelle demande</h3>
                <p class="portal-copy" style="margin-bottom:18px;">
                    Renseignez votre besoin. Le suivi se fait ici a travers un statut global et des notes de l equipe RHS.
                </p>

                <form method="POST" action="{{ route('client.recruitment-requests.store') }}" class="portal-form-grid">
                    @csrf
                    <div>
                        <label for="reference">Reference interne</label>
                        <input id="reference" name="reference" type="text" value="{{ old('reference') }}" placeholder="Ex: RHS-CLI-001">
                    </div>
                    <div>
                        <label for="position_title">Poste recherche</label>
                        <input id="position_title" name="position_title" type="text" value="{{ old('position_title') }}" required placeholder="Ex: Responsable achats">
                    </div>
                    <div>
                        <label for="work_location">Lieu de travail</label>
                        <input id="work_location" name="work_location" type="text" value="{{ old('work_location') }}" placeholder="Ex: Casablanca">
                    </div>
                    <div>
                        <label for="contract_type">Type de contrat</label>
                        <input id="contract_type" name="contract_type" type="text" value="{{ old('contract_type') }}" placeholder="Ex: CDI">
                    </div>
                    <div>
                        <label for="experience_years">Experience souhaitee</label>
                        <input id="experience_years" name="experience_years" type="text" value="{{ old('experience_years') }}" placeholder="Ex: 5 ans minimum">
                    </div>
                    <div>
                        <label for="planned_start_date">Date souhaitee</label>
                        <input id="planned_start_date" name="planned_start_date" type="date" value="{{ old('planned_start_date') }}">
                    </div>
                    <div class="full">
                        <label for="missions">Missions principales</label>
                        <textarea id="missions" name="missions" rows="5" placeholder="Decrivez les principales missions du poste">{{ old('missions') }}</textarea>
                    </div>
                    <div class="full">
                        <label for="specific_knowledge">Competences et connaissances requises</label>
                        <textarea id="specific_knowledge" name="specific_knowledge" rows="4" placeholder="Logiciels, langues, certifications, environnement metier">{{ old('specific_knowledge') }}</textarea>
                    </div>
                    <div class="full">
                        <label for="personal_qualities">Qualites attendues</label>
                        <textarea id="personal_qualities" name="personal_qualities" rows="3" placeholder="Ex: rigueur, leadership, sens de l organisation">{{ old('personal_qualities') }}</textarea>
                    </div>
                    <div class="full form-actions-inline">
                        <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Envoyer la demande</button>
                    </div>
                </form>
            </section>

            <section class="portal-card">
                <div class="portal-section-head">
                    <div>
                        <h3 style="margin:0 0 8px;">Historique et suivi</h3>
                        <p class="portal-copy" style="margin:0;">Les notes admin visibles ici restent generales. Aucun CV, candidat ou score n est affiche au client.</p>
                    </div>
                </div>

                <div class="portal-timeline">
                    @forelse($requests as $requestItem)
                        <article class="portal-record">
                            <div class="portal-record-top">
                                <div>
                                    <strong>{{ $requestItem->position_title }}</strong>
                                    <div class="portal-copy">
                                        Reference: {{ $requestItem->reference ?: '-' }} ·
                                        Date: {{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }} ·
                                        Lieu: {{ $requestItem->work_location ?: '-' }}
                                    </div>
                                </div>
                                <span class="portal-status {{ $statusClass($requestItem->request_status) }}">
                                    {{ $statusLabels[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}
                                </span>
                            </div>

                            @if($requestItem->missions)
                                <p class="portal-record-copy">{{ \Illuminate\Support\Str::limit($requestItem->missions, 220) }}</p>
                            @endif

                            @if($requestItem->admin_notes)
                                <div class="portal-note">
                                    <strong style="display:block; margin-bottom:6px;">Message RHS</strong>
                                    {{ $requestItem->admin_notes }}
                                </div>
                            @endif

                            @if($alertsEnabled && $requestItem->clientAlerts->isNotEmpty())
                                <div class="portal-subsection">
                                    <strong class="portal-subtitle">Dernieres relances</strong>
                                    <div class="portal-mini-list">
                                        @foreach($requestItem->clientAlerts as $alert)
                                            <div class="portal-mini-item">
                                                <span class="portal-status {{ $alertClass($alert->status) }}">
                                                    {{ $alertLabels[$alert->status] ?? $alert->status }}
                                                </span>
                                                <div class="portal-mini-copy">
                                                    {{ $alert->message ?: 'Relance sans message complementaire.' }}
                                                    @if($alert->admin_response)
                                                        <div class="portal-note" style="margin-top:8px;">{{ $alert->admin_response }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($alertsEnabled)
                                <form method="POST" action="{{ route('client.recruitment-requests.alerts.store', $requestItem) }}" class="portal-inline-form">
                                    @csrf
                                    <div class="full">
                                        <label for="alert-message-{{ $requestItem->id }}">Envoyer une relance</label>
                                        <textarea id="alert-message-{{ $requestItem->id }}" name="message" rows="3" placeholder="Message optionnel pour preciser votre relance"></textarea>
                                    </div>
                                    <button type="submit" class="admin-btn admin-btn-ghost portal-btn-auto">Relancer RHS</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <div class="portal-empty">
                            <div class="portal-empty-title">Aucune demande pour le moment</div>
                            <div class="portal-empty-copy">Votre historique apparaitra ici des qu une demande sera enregistree.</div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
@endsection
