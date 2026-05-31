@extends('admin.layouts.app')

@section('title', 'Admin - Tableau de bord')
@section('page_title', 'Tableau de bord')
@section('page_subtitle', 'Pilotage global des demandes clients, candidatures, RH interne, assignations et banque CV.')

@php
    $statusLabels = \App\Models\RecruitmentRequest::availableStatuses();
    $pipelineLabels = \App\Models\RecruitmentRequest::availablePipelineStages();
    $internalLabels = \App\Models\EmployeeInternalRequest::availableCategories();
    $userRoleLabels = \App\Models\User::availableRoles();
    $offerStatusLabels = [
      'active' => 'Actives',
      'inactive' => 'Inactives',
    ];
    $clientStatusChartData = collect($clientRequestStatusChart)
      ->map(fn ($total, $status) => [
        'label' => $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)),
        'value' => (int) $total,
      ])
      ->values();
    $pipelineChartData = collect($pipelineStageChart)
      ->map(fn ($total, $stage) => [
        'label' => $pipelineLabels[$stage] ?? ucfirst(str_replace('_', ' ', $stage)),
        'value' => (int) $total,
      ])
      ->values();
    $assignedEmployeeChartData = collect($assignedRequestsByEmployee)
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $offerStatusChartData = collect($offerStatusChart)
      ->map(fn ($total, $status) => [
        'label' => $offerStatusLabels[$status] ?? ucfirst($status),
        'value' => (int) $total,
      ])
      ->values();
    $employeePerformanceChartData = collect($employeePerformanceChart)
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $employeeRequestTypeChartData = collect($employeeRequestTypeChart)
      ->map(fn ($total, $category) => [
        'label' => $internalLabels[$category] ?? ucfirst($category),
        'value' => (int) $total,
      ])
      ->values();
    $userRoleChartData = collect($userRoleChart)
      ->map(fn ($total, $role) => [
        'label' => $userRoleLabels[$role] ?? ucfirst($role),
        'value' => (int) $total,
      ])
      ->values();
    $cvsByOfferChartData = collect($cvsByOfferChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $requestsByClientChartData = collect($requestsByClientChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $cvSourceChartData = collect($cvSourceChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $cvHealthChartData = collect($cvHealthChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $cvsByFolderChartData = collect($cvsByFolderChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $clientActivityChartData = collect($clientActivityChart ?? [])
      ->map(fn ($row) => [
        'label' => $row['label'],
        'value' => (int) $row['value'],
        'secondary' => (int) ($row['secondary'] ?? 0),
        'total' => (int) ($row['total'] ?? $row['value']),
      ])
      ->values();
    $clientAlertChartData = collect($clientAlertChart ?? [])
      ->map(fn ($row) => ['label' => $row['label'], 'value' => (int) $row['total']])
      ->values();
    $employeeTaskChartData = collect($employeeTaskChart ?? [])
      ->map(fn ($row) => [
        'label' => $row['label'],
        'value' => (int) $row['value'],
        'secondary' => (int) ($row['secondary'] ?? 0),
        'third' => (int) ($row['third'] ?? 0),
        'total' => (int) ($row['total'] ?? $row['value']),
      ])
      ->values();
@endphp

@section('content')
  <div class="dash-kpis dash-kpis--five">
    <a class="dash-kpi" href="{{ route('admin.users.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Total utilisateurs</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 10C9.66 10 11 8.66 11 7S9.66 4 8 4 5 5.34 5 7s1.34 3 3 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 20v-1a4 4 0 0 1 4-4h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $totalUsers }}</div>
      <div class="dash-kpi-foot"><span>Comptes actifs et en attente</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.users.index', ['status' => 'pending']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Approvals en attente</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 7.5v5M12 16.5h.01" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $pendingUserApprovals }}</div>
      <div class="dash-kpi-foot"><span>Validation manuelle</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-recruitment-requests.index', ['status' => 'pending']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes clients en attente</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 5h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 10h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $pendingClientRequests }}</div>
      <div class="dash-kpi-foot"><span>Suivi des besoins ouverts</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-request-alerts.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Relances clients non traitees</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 12a8 8 0 0 1 13.66-5.66L20 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M20 4v4h-4M20 12a8 8 0 0 1-13.66 5.66L4 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 20v-4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $pendingClientAlerts }}</div>
      <div class="dash-kpi-foot"><span>Relances a traiter</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.applications.index', ['status' => 'unread']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Candidatures non vues</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $appsUnread }}</div>
      <div class="dash-kpi-foot"><span>Statut de consultation</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>
  </div>

  <div class="dash-kpis dash-kpis--five" style="margin-top:16px;">
    <a class="dash-kpi" href="{{ route('admin.cvs.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">CVs en banque</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M14 3v5h4M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $cvBankCount }}</div>
      <div class="dash-kpi-foot"><span>Base privee RHS</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-recruitment-requests.index', ['status' => 'matching_in_progress']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes actives</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 12h4l2-5 4 10 2-5h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $activeRecruitmentRequests }}</div>
      <div class="dash-kpi-foot"><span>Flux recrutement vivant</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-recruitment-requests.index', ['assignment' => 'unassigned']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes non assignees</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20v-1a5 5 0 0 1 5-5h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15 14h6M18 11v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $unassignedRecruitmentRequests }}</div>
      <div class="dash-kpi-foot"><span>Assignation employee a faire</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.employee-reports.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Rapports employes</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 4h9l3 3v13H6V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M14 4v4h4M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $pendingEmployeeReports }}</div>
      <div class="dash-kpi-foot"><span>Revues en attente</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.offers.index', ['status' => 'active']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Offres actives</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 7h12M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M5 7h14v12H5V7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="m9 14 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $activeOffersCount }}</div>
      <div class="dash-kpi-foot"><span>Offres visibles sur le site</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>
  </div>

  <section class="dash-card rhs-dashboard-quick" style="margin-top:18px;">
    <div class="dash-card-head">
      <div>
        <h2 class="dash-card-title">Acces rapides</h2>
        <p class="dash-card-sub">Les raccourcis les plus utiles avant les graphes et le suivi detaille.</p>
      </div>
    </div>

    <div class="dash-actions rhs-dashboard-quick-grid">
      <a class="dash-action" href="{{ route('admin.client-recruitment-requests.index', ['status' => 'all']) }}">
        <div class="dash-action-text">
          <div class="dash-action-title">Demandes clients</div>
          <div class="dash-action-sub">Suivre le statut, les notes et les assignations employes.</div>
        </div>
        <span class="dash-action-go">-></span>
      </a>
      <a class="dash-action" href="{{ route('admin.client-request-alerts.index', ['status' => 'new']) }}">
        <div class="dash-action-text">
          <div class="dash-action-title">Relances clients</div>
          <div class="dash-action-sub">Repondre rapidement aux relances et demandes d acceleration.</div>
        </div>
        <span class="dash-action-go">-></span>
      </a>
      <a class="dash-action" href="{{ route('admin.applications.index', ['status' => 'unread']) }}">
        <div class="dash-action-text">
          <div class="dash-action-title">Candidatures</div>
          <div class="dash-action-sub">Traitement des nouvelles candidatures recues.</div>
        </div>
        <span class="dash-action-go">-></span>
      </a>
      <a class="dash-action" href="{{ route('admin.employee-internal-requests.index', ['status' => 'pending']) }}">
        <div class="dash-action-text">
          <div class="dash-action-title">Demandes RH internes</div>
          <div class="dash-action-sub">Suivre les besoins administratifs des equipes.</div>
        </div>
        <span class="dash-action-go">-></span>
      </a>
      @if(\Illuminate\Support\Facades\Route::has('client.register'))
        <div class="dash-action">
          <div class="dash-action-text">
            <div class="dash-action-title">Lien inscription client</div>
            <div class="dash-action-sub">Partage rapide du formulaire d inscription client.</div>
          </div>
          <button
            type="button"
            class="btn btn-ghost btn-sm"
            data-copy-target="dashboard-client-register-link"
            data-copy-feedback="dashboard-client-register-feedback"
          >
            Copier
          </button>
        </div>
        <input type="hidden" id="dashboard-client-register-link" value="{{ route('client.register') }}">
        <div class="admin-copy-feedback" id="dashboard-client-register-feedback">Lien copie</div>
      @endif
    </div>
  </section>

  <div class="dash-grid rhs-dashboard-advanced-one" style="margin-top:18px;">
    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">CV par offre</h2>
          <p class="dash-card-sub">Selectionnez une offre, y compris les candidatures spontanees et la banque CV.</p>
        </div>
      </div>

      <div class="rhs-chart rhs-chart--showcase" data-chart-type="showcase" data-lazy-chart="cvsByOfferChart" data-dashboard-chart-order="30" data-chart='@json($cvsByOfferChartData)' data-unit="CV" data-picker-label="Choisir une offre" data-search-placeholder="Rechercher une offre"></div>
    </section>

    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes par client</h2>
          <p class="dash-card-sub">Demandes et relances combinees pour repérer les clients les plus actifs.</p>
        </div>
      </div>

      <div class="rhs-chart rhs-chart--stacked" data-chart-type="stacked" data-lazy-chart="clientActivityChart" data-dashboard-chart-order="20" data-chart='@json($clientActivityChartData)' data-unit="activite" data-primary-label="Demandes" data-secondary-label="Relances"></div>
    </section>
  </div>

  <div class="dash-grid rhs-dashboard-advanced-two" style="margin-top:18px;">
    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Origine des CV</h2>
          <p class="dash-card-sub">Candidatures, ajouts manuels et bases externes dans la banque CV.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="donut" data-lazy-chart="cvSourceChart" data-dashboard-chart-order="40" data-chart='@json($cvSourceChartData)'></div>
    </section>

    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Sante CV Bank</h2>
          <p class="dash-card-sub">Qualite des donnees utiles au matching, a la recherche et a l envoi Odoo.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="bar" data-lazy-chart="cvHealthChart" data-dashboard-chart-order="50" data-chart='@json($cvHealthChartData)'></div>
    </section>
  </div>

  <div class="dash-grid rhs-dashboard-advanced-three" style="margin-top:18px;">
    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">CV par dossier</h2>
          <p class="dash-card-sub">Recherche rapide par dossier CV Bank, incluant les CV sans dossier.</p>
        </div>
      </div>

      <div class="rhs-chart rhs-chart--showcase" data-chart-type="showcase" data-lazy-chart="cvsByFolderChart" data-dashboard-chart-order="60" data-chart='@json($cvsByFolderChartData)' data-unit="CV" data-picker-label="Choisir un dossier" data-search-placeholder="Rechercher un dossier"></div>
    </section>

    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Top employés actifs</h2>
          <p class="dash-card-sub">Demandes terminees, rapports envoyes et reponses traitees.</p>
        </div>
      </div>

      <div class="rhs-chart rhs-chart--stacked" data-chart-type="stacked" data-lazy-chart="employeeTaskChart" data-dashboard-chart-order="80" data-chart='@json($employeeTaskChartData)' data-unit="action" data-primary-label="Terminees" data-secondary-label="Rapports" data-third-label="Reponses"></div>
    </section>

    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Relances par client</h2>
          <p class="dash-card-sub">Clients qui relancent le plus, utile pour prioriser les réponses.</p>
        </div>
      </div>

      <div class="rhs-chart rhs-chart--showcase" data-chart-type="showcase" data-lazy-chart="clientAlertChart" data-dashboard-chart-order="70" data-chart='@json($clientAlertChartData)' data-unit="relance" data-picker-label="Choisir un client" data-search-placeholder="Rechercher un client"></div>
    </section>

    <section class="dash-card dash-card--visual-chart">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Pipeline + statut</h2>
          <p class="dash-card-sub">Vue mixte des étapes pipeline et des statuts client.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="donut" data-chart='@json($pipelineChartData->merge($clientStatusChartData)->values())'></div>
    </section>
  </div>

  <div class="dash-grid dash-grid--single rhs-dashboard-core-status" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes clients par statut</h2>
          <p class="dash-card-sub">Survolez ou cliquez un segment pour isoler une valeur.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="donut" data-chart='@json($clientStatusChartData)'></div>

      <div class="dash-chart-list">
        @forelse($clientRequestStatusChart as $status => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 10)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune demande client</div>
            <div class="dash-empty-sub">Les premiers indicateurs apparaitront ici.</div>
          </div>
        @endforelse
      </div>
    </section>
  </div>

  <div class="dash-grid rhs-dashboard-core-pipeline" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Pipeline recrutement</h2>
          <p class="dash-card-sub">Progression globale du pipeline client.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="bar" data-chart='@json($pipelineChartData)'></div>

      <div class="dash-chart-list">
        @forelse($pipelineStageChart as $stage => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $pipelineLabels[$stage] ?? ucfirst(str_replace('_', ' ', $stage)) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 12)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune etape pipeline</div>
            <div class="dash-empty-sub">Le pipeline apparaitra ici quand des demandes actives seront assignees.</div>
          </div>
        @endforelse
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes assignees par employe</h2>
          <p class="dash-card-sub">Charge de travail actuelle.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="bar" data-chart='@json($assignedEmployeeChartData)'></div>

      <div class="dash-chart-list">
        @forelse($assignedRequestsByEmployee as $row)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $row['label'] }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $row['total'] * 14)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $row['total'] }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune assignation employee</div>
            <div class="dash-empty-sub">Assignez vos premieres demandes clientes pour suivre la charge ici.</div>
          </div>
        @endforelse
      </div>
    </section>
  </div>

  <div class="dash-grid rhs-dashboard-people" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Offres par statut</h2>
          <p class="dash-card-sub">Activation des offres publiees sur le site.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="donut" data-chart='@json($offerStatusChartData)'></div>

      <div class="dash-chart-list">
        @forelse($offerStatusChart as $status => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $offerStatusLabels[$status] ?? ucfirst($status) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 12)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune offre</div>
            <div class="dash-empty-sub">Les offres actives et inactives apparaitront ici.</div>
          </div>
        @endforelse
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Performance employee</h2>
          <p class="dash-card-sub">Demandes assignees marquees terminees.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="bar" data-chart='@json($employeePerformanceChartData)'></div>

      <div class="dash-chart-list">
        @forelse($employeePerformanceChart as $row)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $row['label'] }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $row['total'] * 16)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $row['total'] }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune performance mesuree</div>
            <div class="dash-empty-sub">Les performances apparaitront apres les premiers traitements termines.</div>
          </div>
        @endforelse
      </div>
    </section>
  </div>

  <div class="dash-grid rhs-dashboard-admin-meta" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes RH internes par categorie</h2>
          <p class="dash-card-sub">Repartition des besoins des employes.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="bar" data-chart='@json($employeeRequestTypeChartData)'></div>

      <div class="dash-chart-list">
        @forelse($employeeRequestTypeChart as $category => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $internalLabels[$category] ?? ucfirst($category) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 12)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune demande RH interne</div>
            <div class="dash-empty-sub">Les categories RH apparaitront ici apres les premieres soumissions.</div>
          </div>
        @endforelse
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Utilisateurs par role</h2>
          <p class="dash-card-sub">Repartition des comptes par espace d acces.</p>
        </div>
      </div>

      <div class="rhs-chart" data-chart-type="donut" data-chart='@json($userRoleChartData)'></div>

      <div class="dash-mini-list">
        @forelse($userRoleChart as $role => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $userRoleLabels[$role] ?? ucfirst($role) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 12)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucun utilisateur</div>
            <div class="dash-empty-sub">Les roles apparaitront ici apres la creation des comptes.</div>
          </div>
        @endforelse
      </div>
    </section>
  </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const lazyCharts = document.querySelectorAll('[data-lazy-chart]');

  if (lazyCharts.length) {
    lazyCharts.forEach(function (chart) {
      chart.innerHTML = '<div class="dash-empty"><div class="dash-empty-title">Chargement...</div><div class="dash-empty-sub">Les graphes se preparent en arriere-plan.</div></div>';
    });

    const chartBaseUrl = '{{ route('admin.dashboard.charts') }}';
    const loadChart = function (chart) {
      const key = chart.getAttribute('data-lazy-chart');

      return fetch(chartBaseUrl + '?chart=' + encodeURIComponent(key), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          const rows = (payload && payload[key]) ? payload[key] : [];
          const mapped = rows.map(function (row) {
            return {
              label: row.label,
              value: Number(row.total || row.value || 0),
              secondary: Number(row.secondary || 0),
              third: Number(row.third || 0),
              total: Number(row.total || row.value || 0)
            };
          });

          chart.setAttribute('data-chart', JSON.stringify(mapped));
          chart.removeAttribute('data-lazy-chart');

          if (window.rhsRenderDashboardCharts) {
            window.rhsRenderDashboardCharts(chart);
          }
        })
        .catch(function () {
          chart.innerHTML = '<div class="dash-empty"><div class="dash-empty-title">Graphes non charges</div><div class="dash-empty-sub">Rechargez la page pour reessayer.</div></div>';
        });
    };

    Array.from(lazyCharts).sort(function (a, b) {
      return Number(a.getAttribute('data-dashboard-chart-order') || 999)
        - Number(b.getAttribute('data-dashboard-chart-order') || 999);
    }).reduce(function (chain, chart, index) {
      return chain.then(function () {
        chart.closest('.dash-card')?.style.setProperty('--dashboard-chart-order', index + 1);
        return loadChart(chart);
      });
    }, Promise.resolve());
  }

  document.querySelectorAll('.dash-chart-row').forEach(function (row) {
    row.setAttribute('tabindex', '0');
    row.addEventListener('click', function () {
      row.classList.toggle('is-expanded');
    });
    row.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        row.classList.toggle('is-expanded');
      }
    });
  });
});
</script>
@endpush
