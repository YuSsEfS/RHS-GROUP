@extends('admin.layouts.app')

@section('title', 'Admin - Tableau de bord')
@section('page_title', 'Tableau de bord')

@section('page_subtitle')
Pilotage global des demandes clients, candidatures, RH interne et banque CV.
@endsection

@php
    $statusLabels = \App\Models\RecruitmentRequest::availableStatuses();
    $leaveLabels = \App\Models\EmployeeLeaveRequest::availableStatuses();
    $internalLabels = \App\Models\EmployeeInternalRequest::availableCategories();
@endphp

@section('content')
  <div class="dash-kpis dash-kpis--five">
    <a class="dash-kpi" href="{{ route('admin.users.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Total utilisateurs</div>
        <div class="dash-kpi-icon"><span>U</span></div>
      </div>
      <div class="dash-kpi-value">{{ $totalUsers }}</div>
      <div class="dash-kpi-foot"><span>Comptes actifs et en attente</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.users.index', ['status' => 'pending']) }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Approvals en attente</div>
        <div class="dash-kpi-icon"><span>A</span></div>
      </div>
      <div class="dash-kpi-value">{{ $pendingUserApprovals }}</div>
      <div class="dash-kpi-foot"><span>Validation manuelle</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-recruitment-requests.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes clients en attente</div>
        <div class="dash-kpi-icon"><span>D</span></div>
      </div>
      <div class="dash-kpi-value">{{ $pendingClientRequests }}</div>
      <div class="dash-kpi-foot"><span>Suivi des besoins ouverts</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.client-request-alerts.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Relances clients non traitees</div>
        <div class="dash-kpi-icon"><span>R</span></div>
      </div>
      <div class="dash-kpi-value">{{ $pendingClientAlerts }}</div>
      <div class="dash-kpi-foot"><span>Relances a traiter</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.applications.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Candidatures non vues</div>
        <div class="dash-kpi-icon"><span>C</span></div>
      </div>
      <div class="dash-kpi-value">{{ $appsUnread }}</div>
      <div class="dash-kpi-foot"><span>Badge notification uniquement</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>
  </div>

  <div class="dash-kpis dash-kpis--five" style="margin-top:16px;">
    <a class="dash-kpi" href="{{ route('admin.cvs.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">CVs en banque</div>
        <div class="dash-kpi-icon"><span>CV</span></div>
      </div>
      <div class="dash-kpi-value">{{ $cvBankCount }}</div>
      <div class="dash-kpi-foot"><span>Base privee RHS</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.recruitment_requests.create') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes de recrutement actives</div>
        <div class="dash-kpi-icon"><span>M</span></div>
      </div>
      <div class="dash-kpi-value">{{ $activeRecruitmentRequests }}</div>
      <div class="dash-kpi-foot"><span>Flux matching</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.employee-reports.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Rapports employes en attente</div>
        <div class="dash-kpi-icon"><span>RE</span></div>
      </div>
      <div class="dash-kpi-value">{{ $pendingEmployeeReports }}</div>
      <div class="dash-kpi-foot"><span>Revues a faire</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.employee-leave-requests.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes de conge</div>
        <div class="dash-kpi-icon"><span>CG</span></div>
      </div>
      <div class="dash-kpi-value">{{ $pendingLeaveRequests }}</div>
      <div class="dash-kpi-foot"><span>Demandes a decider</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.employee-internal-requests.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Demandes RH internes ouvertes</div>
        <div class="dash-kpi-icon"><span>RH</span></div>
      </div>
      <div class="dash-kpi-value">{{ $openInternalRequests }}</div>
      <div class="dash-kpi-foot"><span>Suivi RH interne</span><span class="dash-kpi-arrow">-></span></div>
      <span class="dash-kpi-accent"></span>
    </a>
  </div>

  <div class="dash-grid" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Actions rapides</h2>
          <p class="dash-card-sub">Acces directs aux flux critiques.</p>
        </div>
      </div>

      <div class="dash-actions">
        <a class="dash-action" href="{{ route('admin.client-recruitment-requests.index') }}">
          <div class="dash-action-text">
            <div class="dash-action-title">Demandes clients</div>
            <div class="dash-action-sub">Suivre le statut, les notes et lancer le matching.</div>
          </div>
          <span class="dash-action-go">-></span>
        </a>
        <a class="dash-action" href="{{ route('admin.client-request-alerts.index') }}">
          <div class="dash-action-text">
            <div class="dash-action-title">Relances clients</div>
            <div class="dash-action-sub">Repondre rapidement aux relances et demandes d acceleration.</div>
          </div>
          <span class="dash-action-go">-></span>
        </a>
        <a class="dash-action" href="{{ route('admin.applications.index') }}">
          <div class="dash-action-text">
            <div class="dash-action-title">Candidatures</div>
            <div class="dash-action-sub">Traitement des nouvelles candidatures recues.</div>
          </div>
          <span class="dash-action-go">-></span>
        </a>
        <a class="dash-action" href="{{ route('admin.employee-reports.index') }}">
          <div class="dash-action-text">
            <div class="dash-action-title">Rapports employes</div>
            <div class="dash-action-sub">Consulter et valider les rapports d activite.</div>
          </div>
          <span class="dash-action-go">-></span>
        </a>
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes clients par statut</h2>
          <p class="dash-card-sub">Vue simple sans bibliotheque supplementaire.</p>
        </div>
      </div>

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

  <div class="dash-grid" style="margin-top:18px;">
    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes de conge par statut</h2>
          <p class="dash-card-sub">Lecture rapide des decisions RH.</p>
        </div>
      </div>

      <div class="dash-chart-list">
        @forelse($leaveStatusChart as $status => $total)
          <div class="dash-chart-row">
            <div class="dash-chart-label">{{ $leaveLabels[$status] ?? ucfirst($status) }}</div>
            <div class="dash-chart-track"><span style="width: {{ min(100, max(10, $total * 12)) }}%;"></span></div>
            <div class="dash-chart-value">{{ $total }}</div>
          </div>
        @empty
          <div class="dash-empty">
            <div class="dash-empty-title">Aucune demande de conge</div>
            <div class="dash-empty-sub">Les compteurs RH apparaitront ici des qu un flux sera actif.</div>
          </div>
        @endforelse
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Demandes RH internes par categorie</h2>
          <p class="dash-card-sub">Repartition des besoins des employes.</p>
        </div>
      </div>

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
  </div>
@endsection
