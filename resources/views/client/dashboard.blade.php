@extends('dashboard.layouts.app')

@php
    $statusLabels = $statuses ?? \App\Models\RecruitmentRequest::availableStatuses();
    $clientOverviewChartData = collect([
        ['label' => 'En cours', 'value' => (int) $requestsInProgress],
        ['label' => 'Traitees', 'value' => (int) $requestsCompleted],
        ['label' => 'Relances envoyees', 'value' => (int) $alertsCount],
    ])->values();
@endphp

@section('title', 'Espace client')
@section('brand', 'RHS Client')
@section('brand_sub', 'Portail recrutement')
@section('page_title', 'Tableau de bord client')
@section('page_copy', 'Vue d ensemble de vos demandes. Le detail, l historique et le suivi se gerent dans des pages dediees.')

@section('sidebar')
    @include('client._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ \App\Models\User::availableStatuses()[$user->status] ?? ucfirst($user->status) }}</span>
@endsection

@section('content')
    <div class="portal-grid portal-grid--four" style="margin-bottom:18px;">
        <div class="portal-card">
            <div class="portal-card-top">
                <span class="portal-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M6 4h9l3 3v13H6V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M14 4v4h4M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <h3>Demandes totales</h3>
            </div>
            <p class="portal-kpi">{{ $requestsCount }}</p>
            <div class="portal-copy">Historique complet</div>
        </div>
        <div class="portal-card">
            <div class="portal-card-top">
                <span class="portal-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 12h4l2-5 4 10 2-5h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <h3>Demandes en cours</h3>
            </div>
            <p class="portal-kpi">{{ $requestsInProgress }}</p>
            <div class="portal-copy">Suivi actif RHS</div>
        </div>
        <div class="portal-card">
            <div class="portal-card-top">
                <span class="portal-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m8.5 12.5 2.2 2.2 4.8-5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h3>Demandes traitees</h3>
            </div>
            <p class="portal-kpi">{{ $requestsCompleted }}</p>
            <div class="portal-copy">Cloturees ou finalisees</div>
        </div>
        <div class="portal-card">
            <div class="portal-card-top">
                <span class="portal-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 12a8 8 0 0 1 13.66-5.66L20 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20 4v4h-4M20 12a8 8 0 0 1-13.66 5.66L4 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 20v-4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h3>Relances envoyees</h3>
            </div>
            <p class="portal-kpi">{{ $alertsCount }}</p>
            <div class="portal-copy">Suivi RHS</div>
        </div>
    </div>

    <div class="portal-split" style="margin-bottom:18px;">
        <section class="portal-card rhs-graph-card">
            <div class="portal-toolbar">
                <div>
                    <h3 class="portal-title-tight">Avancement des demandes</h3>
                    <p class="portal-copy portal-copy-tight">Suivi interactif de votre relation recrutement avec RHS.</p>
                </div>
            </div>
            <div class="rhs-chart" data-chart-type="donut" data-chart='@json($clientOverviewChartData)'></div>
            <div class="dash-chart-list">
                <div class="dash-chart-row" title="{{ $requestsInProgress }} demande(s) actuellement en traitement.">
                    <div class="dash-chart-label">En cours</div>
                    <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $requestsInProgress * 18)) }}%;"></span></div>
                    <div class="dash-chart-value">{{ $requestsInProgress }}</div>
                </div>
                <div class="dash-chart-row" title="{{ $requestsCompleted }} demande(s) cloturee(s) ou finalisee(s).">
                    <div class="dash-chart-label">Traitees</div>
                    <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $requestsCompleted * 18)) }}%;"></span></div>
                    <div class="dash-chart-value">{{ $requestsCompleted }}</div>
                </div>
                <div class="dash-chart-row" title="{{ $alertsCount }} relance(s) envoyee(s).">
                    <div class="dash-chart-label">Relances envoyees</div>
                    <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $alertsCount * 14)) }}%;"></span></div>
                    <div class="dash-chart-value">{{ $alertsCount }}</div>
                </div>
            </div>
        </section>

        <section class="portal-card rhs-graph-card">
            <div class="portal-toolbar">
                <div>
                    <h3 class="portal-title-tight">Prochaine action</h3>
                    <p class="portal-copy portal-copy-tight">Votre espace reste volontairement limite aux demandes et relances.</p>
                </div>
            </div>
            <div class="portal-action-grid">
                <a href="{{ route('client.recruitment-requests.create') }}" class="portal-action-card">
                    <strong>Nouvelle demande</strong>
                    <span>Soumettre un besoin de recrutement sans acces aux CV ou aux scores internes.</span>
                </a>
                <a href="{{ route('client.recruitment-requests.index') }}" class="portal-action-card">
                    <strong>Historique</strong>
                    <span>Consulter vos statuts, dates et reponses RHS.</span>
                </a>
            </div>
        </section>
    </div>

    @if(!$canManageRecruitmentRequests)
        <div class="portal-card">
            <h3 class="portal-title-tight">Module non active</h3>
            <p class="portal-copy portal-copy-tight">Votre compte client n a pas encore acces au module de demandes de recrutement.</p>
        </div>
    @else
        <div class="portal-split">
            <section class="portal-card">
                <div class="portal-toolbar">
                    <div>
                        <h3 class="portal-title-tight">Actions rapides</h3>
                        <p class="portal-copy portal-copy-tight">Creez une nouvelle demande, consultez votre historique ou mettez a jour votre profil.</p>
                    </div>
                </div>

                <div class="portal-action-grid">
                    <a href="{{ route('client.recruitment-requests.create') }}" class="portal-action-card">
                        <strong>Nouvelle demande de recrutement</strong>
                        <span>Ouvrir le formulaire dedie pour un nouveau besoin.</span>
                    </a>
                    <a href="{{ route('client.recruitment-requests.index') }}" class="portal-action-card">
                        <strong>Historique des demandes</strong>
                        <span>Voir la liste complete puis ouvrir chaque demande en detail.</span>
                    </a>
                    <a href="{{ route('client.profile.edit') }}" class="portal-action-card">
                        <strong>Mon profil</strong>
                        <span>Mettre a jour vos coordonnees et votre photo de profil si besoin.</span>
                    </a>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-toolbar">
                    <div>
                        <h3 class="portal-title-tight">Dernieres demandes</h3>
                        <p class="portal-copy portal-copy-tight">Apercu des demandes les plus recentes.</p>
                    </div>
                </div>

                <div class="portal-timeline">
                    @forelse($latestRequests as $requestItem)
                        <article class="portal-record">
                            <div class="portal-record-top">
                                <div>
                                    <strong>{{ $requestItem->position_title }}</strong>
                                    <div class="portal-copy">{{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}</div>
                                </div>
                                <span class="portal-status {{ in_array($requestItem->request_status, ['completed', 'shortlisted']) ? 'is-success' : (in_array($requestItem->request_status, ['under_review', 'matching_in_progress']) ? 'is-info' : 'is-warning') }}">
                                    {{ $statusLabels[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}
                                </span>
                            </div>
                            <div class="portal-form-actions" style="justify-content:flex-start; margin-top:12px;">
                                <a href="{{ route('client.recruitment-requests.show', $requestItem) }}" class="admin-btn admin-btn-ghost portal-btn-auto">Ouvrir</a>
                            </div>
                        </article>
                    @empty
                        <div class="portal-empty">
                            <div class="portal-empty-title">Aucune demande recente</div>
                            <div class="portal-empty-copy">Votre premiere demande apparaitra ici apres creation.</div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
@endsection
