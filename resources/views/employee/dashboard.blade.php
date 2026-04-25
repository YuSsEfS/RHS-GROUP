@extends('dashboard.layouts.app')

@section('title', 'Espace employe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Portail interne')
@section('page_title', 'Tableau de bord employe')
@section('page_copy', 'Suivez vos rapports, vos conges, vos demandes RH et vos actions prioritaires depuis un espace unique en francais.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ \App\Models\User::availableStatuses()[$user->status] ?? ucfirst($user->status) }}</span>
@endsection

@section('content')
    <div class="portal-grid portal-grid--four" style="margin-bottom:18px;">
        <div class="portal-card">
            <h3>Mes rapports</h3>
            <p class="portal-kpi">{{ $reportCount }}</p>
            <div class="portal-copy">Total cumule</div>
        </div>
        <div class="portal-card">
            <h3>Ce mois-ci</h3>
            <p class="portal-kpi">{{ $reportMonthCount }}</p>
            <div class="portal-copy">Rapports envoyes</div>
        </div>
        <div class="portal-card">
            <h3>Conges en attente</h3>
            <p class="portal-kpi">{{ $pendingLeaveCount }}</p>
            <div class="portal-copy">Demandes non decidees</div>
        </div>
        <div class="portal-card">
            <h3>Demandes RH ouvertes</h3>
            <p class="portal-kpi">{{ $openInternalRequestCount }}</p>
            <div class="portal-copy">Suivi en cours</div>
        </div>
    </div>

    <div class="portal-split">
        <section class="portal-card">
            <div class="portal-section-head">
                <div>
                    <h3 style="margin:0 0 8px;">Actions rapides</h3>
                    <p class="portal-copy" style="margin:0;">Accedez a vos modules internes autorises.</p>
                </div>
            </div>

            <div class="portal-action-grid">
                @if($canManageReports)
                    <a href="{{ route('employee.reports.index') }}" class="portal-action-card">
                        <strong>Rapports d activite</strong>
                        <span>Creer, joindre et suivre vos rapports.</span>
                    </a>
                @endif
                @if($canManageLeaveRequests)
                    <a href="{{ route('employee.leave-requests.index') }}" class="portal-action-card">
                        <strong>Demandes de conge</strong>
                        <span>Soumettre et consulter vos conges.</span>
                    </a>
                @endif
                @if($canManageInternalRequests)
                    <a href="{{ route('employee.internal-requests.index') }}" class="portal-action-card">
                        <strong>Demandes RH internes</strong>
                        <span>Envoyer une demande et suivre la reponse.</span>
                    </a>
                @endif
                @if($canSeeClientAlerts)
                    <a href="{{ route('employee.client-alerts.index') }}" class="portal-action-card">
                        <strong>Relances clients</strong>
                        <span>{{ $clientAlertsCount }} relance(s) non vue(s) sur les demandes.</span>
                    </a>
                @endif
            </div>

            <div class="portal-subsection">
                <strong class="portal-subtitle">Mon resume RH</strong>
                <div class="portal-mini-list">
                    <div class="portal-mini-item">
                        <span class="portal-status is-success">Valides</span>
                        <div class="portal-mini-copy">{{ $validatedReportCount }} rapport(s) valides par l administration.</div>
                    </div>
                    <div class="portal-mini-item">
                        <span class="portal-status {{ $pendingLeaveCount > 0 ? 'is-warning' : 'is-success' }}">Conges</span>
                        <div class="portal-mini-copy">{{ $pendingLeaveCount }} demande(s) de conge encore en attente.</div>
                    </div>
                    <div class="portal-mini-item">
                        <span class="portal-status {{ $openInternalRequestCount > 0 ? 'is-warning' : 'is-success' }}">RH</span>
                        <div class="portal-mini-copy">{{ $openInternalRequestCount }} demande(s) RH interne(s) ouverte(s).</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-section-head">
                <div>
                    <h3 style="margin:0 0 8px;">Derniere activite</h3>
                    <p class="portal-copy" style="margin:0;">Vos derniers mouvements internes.</p>
                </div>
            </div>

            <div class="portal-timeline">
                @foreach($recentReports as $report)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <strong>{{ $report->title ?: 'Rapport d activite' }}</strong>
                            <span class="portal-status {{ $report->status === 'validated' ? 'is-success' : ($report->status === 'reviewed' ? 'is-info' : 'is-warning') }}">
                                {{ \App\Models\EmployeeReport::availableStatuses()[$report->status] ?? $report->status }}
                            </span>
                        </div>
                        <div class="portal-copy">Rapport du {{ $report->report_date?->format('d/m/Y') }}</div>
                    </article>
                @endforeach

                @foreach($recentLeaveRequests as $leaveRequest)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <strong>{{ \App\Models\EmployeeLeaveRequest::availableTypes()[$leaveRequest->leave_type] ?? $leaveRequest->leave_type }}</strong>
                            <span class="portal-status {{ $leaveRequest->status === 'approved' ? 'is-success' : ($leaveRequest->status === 'rejected' || $leaveRequest->status === 'cancelled' ? 'is-danger' : 'is-warning') }}">
                                {{ \App\Models\EmployeeLeaveRequest::availableStatuses()[$leaveRequest->status] ?? $leaveRequest->status }}
                            </span>
                        </div>
                        <div class="portal-copy">Du {{ $leaveRequest->start_date?->format('d/m/Y') }} au {{ $leaveRequest->end_date?->format('d/m/Y') }}</div>
                    </article>
                @endforeach

                @foreach($recentInternalRequests as $requestItem)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <strong>{{ $requestItem->subject }}</strong>
                            <span class="portal-status {{ $requestItem->status === 'resolved' ? 'is-success' : ($requestItem->status === 'rejected' ? 'is-danger' : 'is-warning') }}">
                                {{ \App\Models\EmployeeInternalRequest::availableStatuses()[$requestItem->status] ?? $requestItem->status }}
                            </span>
                        </div>
                        <div class="portal-copy">{{ \App\Models\EmployeeInternalRequest::availableCategories()[$requestItem->category] ?? $requestItem->category }}</div>
                    </article>
                @endforeach

                @if($recentReports->isEmpty() && $recentLeaveRequests->isEmpty() && $recentInternalRequests->isEmpty())
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucune activite recente</div>
                        <div class="portal-empty-copy">Vos futurs rapports, conges et demandes RH apparaitront ici.</div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
