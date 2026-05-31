@extends('dashboard.layouts.app')

@section('title', 'Espace employe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Portail interne')
@section('page_title', 'Tableau de bord employe')
@section('page_copy', 'Suivez vos rapports, vos conges, vos demandes RH et vos demandes de recrutement assignees depuis un espace unique en francais.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ \App\Models\User::availableStatuses()[$user->status] ?? ucfirst($user->status) }}</span>
@endsection

@php
    $employeeRhChartData = collect([
        ['label' => 'Rapports envoyes', 'value' => (int) $reportCount, 'visible' => $canManageReports],
        ['label' => 'Conges en attente', 'value' => (int) $pendingLeaveCount, 'visible' => $canManageLeaveRequests],
        ['label' => 'Demandes RH ouvertes', 'value' => (int) $openInternalRequestCount, 'visible' => $canManageInternalRequests],
    ])->where('visible', true)->map(fn ($row) => ['label' => $row['label'], 'value' => $row['value']])->values();

    $employeeRecruitmentChartData = collect([
        ['label' => 'Assignees', 'value' => (int) $assignedRequestsCount],
        ['label' => 'En cours', 'value' => (int) $assignedInProgressCount],
        ['label' => 'Terminees', 'value' => (int) $assignedCompletedCount],
    ])->values();
@endphp

@section('content')
    <div class="portal-grid portal-grid--four" style="margin-bottom:18px;">
        @if($canManageReports)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 4h9l3 3v13H6V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M14 4v4h4M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3>Mes rapports</h3>
                </div>
                <p class="portal-kpi">{{ $reportCount }}</p>
                <div class="portal-copy">Total cumule</div>
            </div>
        @endif
        @if($canManageLeaveRequests)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <h3>Conges en attente</h3>
                </div>
                <p class="portal-kpi">{{ $pendingLeaveCount }}</p>
                <div class="portal-copy">Demandes non decidees</div>
            </div>
        @endif
        @if($canManageInternalRequests)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 5h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 10h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3>Demandes RH ouvertes</h3>
                </div>
                <p class="portal-kpi">{{ $openInternalRequestCount }}</p>
                <div class="portal-copy">Suivi administratif</div>
            </div>
        @endif
        @if($canManageRecruitmentRequests)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20v-1a5 5 0 0 1 5-5h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m14 15 2 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <h3>Demandes assignees</h3>
                </div>
                <p class="portal-kpi">{{ $assignedRequestsCount }}</p>
                <div class="portal-copy">Portefeuille recrutement</div>
            </div>
        @endif
        @if($canViewCvBank)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M14 3v5h4M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3>CV Bank</h3>
                </div>
                <p class="portal-kpi">{{ $cvBankCount }}</p>
                <div class="portal-copy">{{ $canManageCvBank ? 'Acces et gestion autorises' : 'Consultation autorisee' }}</div>
            </div>
        @endif
        @if($canViewExternalCvs)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 7c0 2.21 3.58 4 8 4s8-1.79 8-4-3.58-4-8-4-8 1.79-8 4Z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M4 7v5c0 2.21 3.58 4 8 4s8-1.79 8-4V7M4 12v5c0 2.21 3.58 4 8 4s8-1.79 8-4v-5" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                    <h3>Base externe</h3>
                </div>
                <p class="portal-kpi">{{ $externalBatchCount }}</p>
                <div class="portal-copy">{{ $canManageExternalCvs ? 'Lots externes gerables' : 'Lots externes consultables' }}</div>
            </div>
        @endif
        @if($canViewMeetings)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 13h3M8 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3>Reunions</h3>
                </div>
                <p class="portal-kpi">{{ $meetingCount }}</p>
                <div class="portal-copy">Invitations et planning interne</div>
            </div>
        @endif
        @if($canViewRhResources)
            <div class="portal-card">
                <div class="portal-card-top">
                    <span class="portal-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h3>Ressources RH</h3>
                </div>
                <p class="portal-kpi">{{ $rhResourceCount }}</p>
                <div class="portal-copy">Documents visibles selon votre role</div>
            </div>
        @endif
    </div>

    <section class="portal-card rhs-dashboard-quick" style="margin-bottom:18px;">
        <div class="portal-toolbar">
            <div>
                <h3 class="portal-title-tight">Actions rapides</h3>
                <p class="portal-copy portal-copy-tight">Accedez a vos modules internes avant les graphes.</p>
            </div>
        </div>

        <div class="portal-action-grid rhs-dashboard-quick-grid">
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
            @if($canManageRecruitmentRequests)
                <a href="{{ route('employee.recruitment-requests.index') }}" class="portal-action-card">
                    <strong>Demandes assignees</strong>
                    <span>{{ $assignedInProgressCount }} demande(s) actuellement en cours.</span>
                </a>
            @endif
            @if($canSeeClientAlerts)
                <a href="{{ route('employee.client-alerts.index') }}" class="portal-action-card">
                    <strong>Relances clients</strong>
                    <span>{{ $clientAlertsCount }} relance(s) non vue(s).</span>
                </a>
            @endif
            @if($canViewCvBank)
                <a href="{{ route('employee.cvs.index') }}" class="portal-action-card">
                    <strong>CV Bank</strong>
                    <span>{{ $cvBankCount }} CV accessible(s).</span>
                </a>
            @endif
            @if($canViewExternalCvs)
                <a href="{{ route('employee.external-cvs.index') }}" class="portal-action-card">
                    <strong>Base externe</strong>
                    <span>{{ $externalBatchCount }} lot(s) externe(s) disponible(s).</span>
                </a>
            @endif
            @if($canViewMeetings)
                <a href="{{ route('employee.meetings.index') }}" class="portal-action-card">
                    <strong>Reunions</strong>
                    <span>{{ $meetingCount }} reunion(s) accessible(s).</span>
                </a>
            @endif
            @if($canViewRhResources)
                <a href="{{ route('employee.rh-resources.index') }}" class="portal-action-card">
                    <strong>Ressources RH</strong>
                    <span>{{ $rhResourceCount }} ressource(s) visible(s).</span>
                </a>
            @endif
            <a href="{{ route('employee.messages.index') }}" class="portal-action-card">
                <strong>Messagerie interne</strong>
                <span>{{ $unreadMessagesCount }} message(s) non lu(s).</span>
            </a>
            <a href="{{ route('employee.profile.edit') }}" class="portal-action-card">
                <strong>Mon profil</strong>
                <span>Mettre a jour vos coordonnees et votre photo.</span>
            </a>
        </div>
    </section>

    <div class="portal-split" style="margin-bottom:18px;">
        <section class="portal-card rhs-graph-card">
            <div class="portal-toolbar">
                <div>
                    <h3 class="portal-title-tight">Vue RH personnelle</h3>
                    <p class="portal-copy portal-copy-tight">Survolez le graphique pour lire vos volumes RH.</p>
                </div>
            </div>
            <div class="rhs-chart" data-chart-type="donut" data-chart='@json($employeeRhChartData)'></div>
            <div class="dash-chart-list">
                @if($canManageReports)
                    <div class="dash-chart-row" title="{{ $reportCount }} rapport(s), dont {{ $validatedReportCount }} valide(s).">
                        <div class="dash-chart-label">Rapports envoyes</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $reportCount * 12)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $reportCount }}</div>
                    </div>
                @endif
                @if($canManageLeaveRequests)
                    <div class="dash-chart-row" title="{{ $pendingLeaveCount }} demande(s) de conge en attente.">
                        <div class="dash-chart-label">Conges en attente</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $pendingLeaveCount * 20)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $pendingLeaveCount }}</div>
                    </div>
                @endif
                @if($canManageInternalRequests)
                    <div class="dash-chart-row" title="{{ $openInternalRequestCount }} demande(s) RH ouverte(s).">
                        <div class="dash-chart-label">Demandes RH ouvertes</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $openInternalRequestCount * 18)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $openInternalRequestCount }}</div>
                    </div>
                @endif
            </div>
        </section>

        @if($canManageRecruitmentRequests)
            <section class="portal-card rhs-graph-card">
                <div class="portal-toolbar">
                    <div>
                        <h3 class="portal-title-tight">Portefeuille recrutement</h3>
                        <p class="portal-copy portal-copy-tight">Progression interactive de vos demandes assignees.</p>
                    </div>
                </div>
                <div class="rhs-chart" data-chart-type="bar" data-chart='@json($employeeRecruitmentChartData)'></div>
                <div class="dash-chart-list">
                    <div class="dash-chart-row" title="{{ $assignedRequestsCount }} demande(s) assignee(s).">
                        <div class="dash-chart-label">Assignees</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $assignedRequestsCount * 12)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $assignedRequestsCount }}</div>
                    </div>
                    <div class="dash-chart-row" title="{{ $assignedInProgressCount }} demande(s) en cours.">
                        <div class="dash-chart-label">En cours</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $assignedInProgressCount * 16)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $assignedInProgressCount }}</div>
                    </div>
                    <div class="dash-chart-row" title="{{ $assignedCompletedCount }} demande(s) terminee(s).">
                        <div class="dash-chart-label">Terminees</div>
                        <div class="dash-chart-track"><span style="width: {{ min(100, max(8, $assignedCompletedCount * 16)) }}%;"></span></div>
                        <div class="dash-chart-value">{{ $assignedCompletedCount }}</div>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <div class="portal-split">
            <section class="portal-card rhs-dashboard-summary-card">
                <div class="portal-toolbar">
                    <div>
                        <h3 class="portal-title-tight">Resume RH et recrutement</h3>
                        <p class="portal-copy portal-copy-tight">Etat compact de vos dossiers apres les graphes.</p>
                    </div>
                </div>

            <div class="portal-subsection">
                <strong class="portal-subtitle">Mon resume RH et recrutement</strong>
                <div class="portal-mini-list">
                    @if($canManageReports)
                        <div class="portal-mini-item">
                            <span class="portal-status is-success">Valides</span>
                            <div class="portal-mini-copy">{{ $validatedReportCount }} rapport(s) valides par l administration.</div>
                        </div>
                    @endif
                    @if($canManageLeaveRequests)
                        <div class="portal-mini-item">
                            <span class="portal-status {{ $pendingLeaveCount > 0 ? 'is-warning' : 'is-success' }}">Conges</span>
                            <div class="portal-mini-copy">{{ $pendingLeaveCount }} demande(s) de conge encore en attente.</div>
                        </div>
                    @endif
                    @if($canManageInternalRequests)
                        <div class="portal-mini-item">
                            <span class="portal-status {{ $openInternalRequestCount > 0 ? 'is-warning' : 'is-success' }}">RH</span>
                            <div class="portal-mini-copy">{{ $openInternalRequestCount }} demande(s) RH interne(s) ouverte(s).</div>
                        </div>
                    @endif
                    @if($canManageRecruitmentRequests)
                        <div class="portal-mini-item">
                            <span class="portal-status {{ $assignedRequestsCount > 0 ? 'is-info' : 'is-muted' }}">Recrutement</span>
                            <div class="portal-mini-copy">{{ $assignedCompletedCount }} demande(s) assignee(s) deja terminee(s).</div>
                        </div>
                    @endif
                    @if($canViewCvBank)
                        <div class="portal-mini-item">
                            <span class="portal-status is-info">CV Bank</span>
                            <div class="portal-mini-copy">Acces {{ $canManageCvBank ? 'gestion' : 'lecture' }} active par permission.</div>
                        </div>
                    @endif
                    @if($canViewExternalCvs)
                        <div class="portal-mini-item">
                            <span class="portal-status is-info">Base externe</span>
                            <div class="portal-mini-copy">Acces {{ $canManageExternalCvs ? 'gestion' : 'lecture' }} active par permission.</div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

            <section class="portal-card">
                <div class="portal-toolbar">
                    <div>
                        <h3 class="portal-title-tight">Derniere activite</h3>
                        <p class="portal-copy portal-copy-tight">Vos derniers mouvements internes et demandes de recrutement assignees.</p>
                    </div>
                </div>

            <div class="portal-timeline">
                @if($canManageReports)
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
                @endif

                @if($canManageLeaveRequests)
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
                @endif

                @if($canManageInternalRequests)
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
                @endif

                @foreach($assignedRequests as $assignedRequest)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <strong>{{ $assignedRequest->position_title }}</strong>
                            <span class="portal-status {{ $assignedRequest->assignment_status === 'completed' ? 'is-success' : ($assignedRequest->assignment_status === 'in_progress' ? 'is-info' : 'is-warning') }}">
                                {{ \App\Models\RecruitmentRequest::availableAssignmentStatuses()[$assignedRequest->assignment_status] ?? 'Assignee' }}
                            </span>
                        </div>
                        <div class="portal-copy">Etape pipeline: {{ \App\Models\RecruitmentRequest::availablePipelineStages()[$assignedRequest->pipeline_stage] ?? 'Nouvelle demande' }}</div>
                    </article>
                @endforeach

                @if($recentReports->isEmpty() && $recentLeaveRequests->isEmpty() && $recentInternalRequests->isEmpty() && $assignedRequests->isEmpty())
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucune activite recente</div>
                        <div class="portal-empty-copy">Vos futurs rapports, conges, demandes RH et assignments apparaitront ici.</div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
