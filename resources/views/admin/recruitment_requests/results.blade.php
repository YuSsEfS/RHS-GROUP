@extends('admin.layouts.app')

@section('title', 'Admin - Resultats du matching')
@section('page_title', 'Resultats du matching')
@section('page_subtitle', 'Les resultats du matching local et des selections restent disponibles dans l historique.')

@section('top_actions')
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a class="btn btn-ghost" href="{{ route('admin.matching-history.index') }}">
      <span class="btn-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      Historique
    </a>
    <a class="btn btn-primary" href="{{ route('admin.recruitment_requests.create') }}">Nouveau matching</a>
  </div>
@endsection

@section('content')
@php
  $breakdownLabels = [
    'title_fit' => 'Adequation du poste',
    'education_fit' => 'Formation',
    'experience_fit' => 'Experience',
    'age_fit' => 'Age',
    'skills_fit' => 'Competences',
    'language_fit' => 'Langues',
    'location_fit' => 'Localisation',
    'availability_fit' => 'Disponibilite',
    'overall_consistency' => 'Cohesion globale',
  ];
  $breakdownExplanationKeys = [
    'title_fit' => 'title',
    'education_fit' => 'education',
    'experience_fit' => 'experience',
    'age_fit' => 'age',
    'skills_fit' => 'skills',
    'language_fit' => 'languages',
    'location_fit' => 'location',
    'availability_fit' => 'availability',
    'overall_consistency' => 'consistency',
  ];
  $criteria = is_array($recruitmentRequest->ai_normalized_requirements ?? null)
    ? $recruitmentRequest->ai_normalized_requirements
    : (json_decode($recruitmentRequest->ai_normalized_requirements ?? '[]', true) ?: []);

  $matchingStatus = $recruitmentRequest->resolveMatchingStatus() ?? \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING;
  $matchingStatusLabels = \App\Models\RecruitmentRequest::availableMatchingStatuses();
  $jobStatusLabels = \App\Models\RecruitmentRequest::availableJobStatuses();
  $currentOffer = request('offer', $offerId ?? ($recruitmentRequest->job_offer_id ?: 'all'));
  $currentFolder = request('folder', $folderId ?? 'all');
  $currentSearch = $search ?? request('q', '');
  $refreshUrl = route('admin.recruitment_requests.results', ['recruitmentRequest' => $recruitmentRequest->id, 'offer' => $currentOffer, 'folder' => $currentFolder, 'q' => $currentSearch]);
  $matchingStatusUrl = route('admin.recruitment_requests.matching-status', ['recruitmentRequest' => $recruitmentRequest->id, 'folder' => $currentFolder]);
  $matchSuggestUrl = route('admin.recruitment_requests.results.suggest', ['recruitmentRequest' => $recruitmentRequest->id, 'folder' => $currentFolder]);
  $shouldAutoRefresh = in_array($matchingStatus, [
    \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING,
    \App\Models\RecruitmentRequest::MATCHING_STATUS_PROCESSING,
  ], true);
  $matchingStatusMessage = $matchingProgress['status_message'] ?? null;
  $matchingQueuedJobs = (int) ($matchingProgress['queued_jobs'] ?? 0);
  $displayedCandidatesTotal = (int) ($matchingProgress['total_items'] ?? 0);
  $displayedCandidatesTotal = $displayedCandidatesTotal > 0
    ? $displayedCandidatesTotal
    : ($matchesTotal ?? (is_object($matches ?? null) && method_exists($matches, 'total') ? $matches->total() : 0));
  $odooExportStatusUrl = route('admin.recruitment_requests.odooExportStatus', $recruitmentRequest);
  $odooInitialStatus = $odooExportStatus ?? ['status' => 'idle', 'message' => null];
  $odooStatusName = (string) ($odooInitialStatus['status'] ?? 'idle');
  $odooHasStatus = $odooStatusName !== 'idle' && !empty($odooInitialStatus['message']);
  $odooAlertClass = match ($odooStatusName) {
    'success' => 'admin-alert-success',
    'failed' => 'admin-alert-danger',
    default => '',
  };
  $odooStatusTitle = match ($odooStatusName) {
    'queued' => 'Export Odoo en attente',
    'running' => 'Export Odoo en cours',
    'success' => 'Export Odoo termine',
    'warning' => 'Export Odoo a verifier',
    'failed' => 'Export Odoo echoue',
    default => 'Export Odoo',
  };
@endphp

<div class="admin-alert matching-results-status-card">
  <div class="matching-results-status-grid">
    <div>
      <strong>Statut du matching : {{ $matchingStatusLabels[$matchingStatus] ?? ucfirst($matchingStatus) }}</strong>
      <div class="ui-progress-copy">
        {{ $matchingStatusMessage ?: 'Les resultats restent disponibles depuis l historique matching.' }}
      </div>
    </div>
    <div class="matching-results-status-kpis">
      <span><strong id="matching-results-total">{{ $matchesTotal ?? 0 }}</strong> resultat(s)</span>
      <span><strong>{{ $selectedMatchesCount ?? 0 }}</strong> selectionne(s)</span>
      @if($matchingQueuedJobs > 0)
        <span><strong>{{ $matchingQueuedJobs }}</strong> job queue</span>
      @endif
    </div>
  </div>
</div>

<div
  id="odoo-export-status"
  class="admin-alert {{ $odooAlertClass }}"
  data-status-url="{{ $odooExportStatusUrl }}"
  style="{{ $odooHasStatus ? '' : 'display:none;' }}"
>
  <strong id="odoo-export-title">{{ $odooStatusTitle }}</strong>
  <div id="odoo-export-message" class="ui-progress-copy">
    {{ $odooInitialStatus['message'] ?? '' }}
  </div>
  @if(!empty($odooInitialStatus['odoo_url']))
    <a id="odoo-export-link" class="btn btn-ghost btn-sm" href="{{ $odooInitialStatus['odoo_url'] }}" target="_blank" rel="noopener" style="margin-top:10px;">
      Ouvrir Odoo
    </a>
  @else
    <a id="odoo-export-link" class="btn btn-ghost btn-sm" href="#" target="_blank" rel="noopener" style="display:none;margin-top:10px;">
      Ouvrir Odoo
    </a>
  @endif
</div>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Resultats du matching
      <span class="panel-badge">{{ $recruitmentRequest->jobOffer?->title ?? $recruitmentRequest->position_title ?? 'Poste' }}</span>
    </div>

    <div class="panel-tools">
      <form method="GET" class="match-filter-grid" action="{{ route('admin.recruitment_requests.results', $recruitmentRequest) }}" autocomplete="off">
        <div class="match-filter-item match-search-item">
          <label for="match-results-search">Recherche CV</label>
          <div class="table-search match-results-search">
            <span class="table-search-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <input
              id="match-results-search"
              type="search"
              name="q"
              value="{{ $currentSearch }}"
              placeholder="Nom, email, telephone, poste..."
              autocomplete="off"
              spellcheck="false"
              data-results-url="{{ route('admin.recruitment_requests.results', $recruitmentRequest) }}"
              data-suggest-url="{{ $matchSuggestUrl }}"
            >
            <div id="match-results-suggest" class="search-suggest match-results-suggest" hidden></div>
          </div>
        </div>

        <div class="match-filter-item">
          <label for="offer">Offre liee</label>
          <select name="offer" id="offer" onchange="this.form.submit()">
            <option value="all" {{ (string) $currentOffer === 'all' ? 'selected' : '' }}>Toutes les offres</option>
            @foreach(($offers ?? collect()) as $offer)
              <option value="{{ $offer->id }}" {{ (string) $currentOffer === (string) $offer->id ? 'selected' : '' }}>
                {{ $offer->title }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="match-filter-item">
          <label for="folder">Dossier CV Bank</label>
          <select name="folder" id="folder" onchange="this.form.submit()">
            <option value="all" {{ (string) $currentFolder === 'all' ? 'selected' : '' }}>Tous les dossiers</option>
            @foreach(($folders ?? collect()) as $folder)
              <option value="{{ $folder->id }}" {{ (string) $currentFolder === (string) $folder->id ? 'selected' : '' }}>
                {{ $folder->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="table-ctrl-actions">
          <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
          <a class="btn btn-ghost btn-sm" href="{{ route('admin.recruitment_requests.results', $recruitmentRequest) }}">Reinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <div class="panel-body">
    <div class="request-context-card">
      <span class="request-logo-thumb request-logo-thumb-lg">
        @if($recruitmentRequest->logo_url)
          <img src="{{ $recruitmentRequest->logo_url }}" alt="{{ $recruitmentRequest->client_name ?: $recruitmentRequest->position_title }}">
        @else
          {{ strtoupper(substr($recruitmentRequest->client_name ?: $recruitmentRequest->position_title ?: 'R', 0, 1)) }}
        @endif
      </span>
      <div class="request-context-copy">
        <strong>{{ $recruitmentRequest->position_title ?: 'Demande recrutement' }}</strong>
        <span>{{ $recruitmentRequest->client_name ?: 'Client non renseigne' }} · {{ $recruitmentRequest->reference ?: 'Sans reference' }}</span>
      </div>
    </div>

    <div class="request-context-card" style="align-items:flex-start; gap:14px;">
      <div class="request-context-copy" style="width:100%;">
        <strong>Criteres saisis</strong>
        <div class="match-meta" style="margin-top:10px;">
          <div class="match-chip"><span>Poste :</span>{{ $criteria['role'] ?? $recruitmentRequest->position_title ?? '-' }}</div>
          <div class="match-chip"><span>Nombre recherche :</span>{{ $recruitmentRequest->candidate_count ?: '-' }}</div>
          <div class="match-chip"><span>Lieu :</span>{{ $criteria['location'] ?? $recruitmentRequest->work_location ?? '-' }}</div>
          <div class="match-chip"><span>Experience :</span>{{ $criteria['experience_text'] ?? $recruitmentRequest->experience_years ?? '-' }}</div>
          <div class="match-chip"><span>Formation :</span>{{ $criteria['education'] ?? $recruitmentRequest->education ?? '-' }}</div>
          <div class="match-chip"><span>Langues :</span>{{ !empty($criteria['languages']) ? implode(', ', (array) $criteria['languages']) : '-' }}</div>
          <div class="match-chip"><span>Contrat :</span>{{ $criteria['contract_type'] ?? $recruitmentRequest->contract_type ?? '-' }}</div>
          <div class="match-chip"><span>Disponibilite :</span>{{ $criteria['availability'] ?? $recruitmentRequest->availability ?? '-' }}</div>
        </div>
      </div>
    </div>

    <div class="match-meta">
      <div class="match-chip"><span>CV trouves :</span><span id="matching-results-found">{{ $displayedCandidatesTotal }}</span></div>
      <div class="match-chip"><span>Offre :</span>{{ $recruitmentRequest->jobOffer?->title ?? '-' }}</div>
      <div class="match-chip"><span>Dossier :</span>{{ (string) $currentFolder === 'all' ? 'Tous' : (optional(($folders ?? collect())->firstWhere('id', (int) $currentFolder))->name ?? '-') }}</div>
      <div class="match-chip"><span>Reference :</span>{{ $recruitmentRequest->reference ?: '-' }}</div>
      <div class="match-chip"><span>Selectionnes :</span><span id="selected-count" data-selected-total="{{ $selectedMatchesCount ?? 0 }}">{{ $selectedMatchesCount ?? 0 }}</span></div>
      <div class="match-chip"><span>Traitement :</span>{{ $matchingStatusLabels[$matchingStatus] ?? ucfirst($matchingStatus) }}</div>
    </div>

    @if($shouldAutoRefresh)
      <form method="POST" action="{{ route('admin.recruitment_requests.cancel-matching', $recruitmentRequest) }}" style="margin-bottom:14px;" onsubmit="return confirm('Annuler ce matching ?')">
        @csrf
        @method('PATCH')
        <button class="btn btn-ghost btn-sm" type="submit">Annuler le matching</button>
      </form>
    @endif

    <form method="POST" action="{{ route('admin.recruitment_requests.downloadSelected', $recruitmentRequest) }}" id="download-selected-form">
      @csrf
      <input type="hidden" name="auto_select_count" id="match-auto-select-count" value="">

      <div class="match-toolbar">
        @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_COMPLETED)
          <div class="rhs-autoselect-card">
            <div>
              <strong>Selection rapide</strong>
              <span>Choisissez automatiquement les meilleurs profils, meme sur les pages suivantes.</span>
            </div>
            <div class="rhs-autoselect-controls">
              <select id="match-auto-select-preset" class="form-select select-theme">
                <option value="">Nombre de CV</option>
                <option value="10">10 premiers</option>
                <option value="20">20 premiers</option>
                <option value="40">40 premiers</option>
                <option value="50">50 premiers</option>
                <option value="100">100 premiers</option>
                <option value="250">250 premiers</option>
                <option value="500">500 premiers</option>
              </select>
              <input id="match-auto-select-custom" class="form-input" type="number" min="1" max="5000" placeholder="Nombre libre">
              <button class="btn btn-ghost btn-sm" type="button" id="match-auto-select-apply">Selectionner</button>
              <button class="btn btn-light btn-sm" type="button" id="match-auto-select-clear">Vider</button>
            </div>
          </div>
        @endif

        <div class="action-row">
          @if($recruitmentRequest->client_user_id)
            <span class="meta">
              <span class="meta-dot"></span>
              Statut client: {{ \App\Models\RecruitmentRequest::availableStatuses()[$recruitmentRequest->request_status] ?? ucfirst(str_replace('_', ' ', $recruitmentRequest->request_status)) }}
            </span>
          @endif

          @if($recruitmentRequest->matching_job_status)
            <span class="meta">
              <span class="meta-dot"></span>
              Job queue: {{ $jobStatusLabels[$recruitmentRequest->matching_job_status] ?? $recruitmentRequest->matching_job_status }}
            </span>
          @endif
        </div>

        <div class="table-ctrl-actions">
          <a class="btn btn-ghost" href="{{ $refreshUrl }}">Rafraichir</a>
          @if($recruitmentRequest->client_user_id)
            <a class="btn btn-ghost" href="{{ route('admin.client-recruitment-requests.edit', $recruitmentRequest) }}">
              Mettre a jour le statut client
            </a>
          @endif
        @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_COMPLETED)

    <div style="display:flex; gap:10px; align-items:center;">

        <button class="btn btn-primary" type="submit">
            Telecharger les CV selectionnes
        </button>

        <button
            class="btn btn-success"
            type="submit"
            formaction="{{ route('admin.recruitment_requests.exportSelectedOdoo', $recruitmentRequest) }}"
            formmethod="POST"
            onclick="return confirm('Envoyer les CV selectionnes vers Odoo ?')"
        >
            Envoyer vers Odoo
        </button>

    </div>

@endif
        </div>
      </div>

      @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING || $matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_PROCESSING)
        <div class="admin-alert" style="margin-bottom:14px;">
          Le matching est en cours. Les resultats apparaitront ici automatiquement.
        </div>
        <div class="ui-progress-card" style="margin-bottom:14px;">
          <div class="ui-progress-head">
            <div>
              <strong>Avancement du matching</strong>
              <div id="matching-progress-subtext" class="ui-progress-copy">
                Analyse des CV en arriere-plan. Temps estime restant :
                <strong id="matching-progress-eta">{{ $matchingProgress['estimated_time_remaining'] ?? 'Calcul en cours' }}</strong>
              </div>
            </div>
            <span id="matching-progress-text" class="match-score">{{ (int) ($matchingProgress['progress_percentage'] ?? 0) }}%</span>
          </div>
          <div class="ui-progress-track">
            <div
              id="matching-progress-bar"
              class="ui-progress-bar"
              style="width:{{ (int) ($matchingProgress['progress_percentage'] ?? 0) }}%;"
            ></div>
          </div>
          <div class="ui-progress-kpis">
            <span><strong id="matching-progress-processed">{{ (int) ($matchingProgress['processed_items'] ?? 0) }}</strong> CV traites</span>
            <span><strong id="matching-progress-total">{{ (int) ($matchingProgress['total_items'] ?? 0) }}</strong> CV estimes</span>
            <span><strong id="matching-progress-remaining">{{ (int) ($matchingProgress['remaining_items'] ?? 0) }}</strong> restants</span>
          </div>
        </div>
      @elseif($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_FAILED)
        <div class="admin-alert admin-alert-danger" style="margin-bottom:14px;">
          Le matching a echoue. {{ $recruitmentRequest->resolveMatchingError() ?: 'Veuillez consulter les logs puis relancer le traitement.' }}
        </div>
      @elseif($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_CANCELLED)
        <div class="admin-alert" style="margin-bottom:14px;">
          Le matching a ete annule. Vous pouvez modifier la demande ou relancer un traitement.
        </div>
      @endif

      @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_COMPLETED)
        <div class="table-wrap" style="margin-top:18px;">
          <table class="table">
            <thead>
              <tr>
                <th>Candidat</th>
                <th>Email</th>
                <th>Dossier</th>
                <th>Score final</th>
                <th>Resume</th>
                <th class="match-actions-cell">Actions</th>
                <th class="match-select-cell">Selection</th>
              </tr>
            </thead>
            <tbody id="match-results-tbody">
              @forelse($matches as $match)
                @php
                  $fullBreakdown = is_array($match->score_breakdown ?? null)
                    ? $match->score_breakdown
                    : (json_decode($match->score_breakdown ?? '[]', true) ?: []);

                  $meta = is_array($fullBreakdown['_meta'] ?? null) ? $fullBreakdown['_meta'] : [];
                  unset($fullBreakdown['_meta']);

                  $localScore = isset($meta['local_score']) ? (float) $meta['local_score'] : null;
                  $aiScore = array_key_exists('ai_score', $meta) && $meta['ai_score'] !== null ? (float) $meta['ai_score'] : null;
                  $finalScore = isset($meta['final_score']) ? (float) $meta['final_score'] : (float) $match->score;
                  $aiAvailable = (bool) ($meta['ai_available'] ?? false);
                  $lastAnalysis = $meta['last_analysis'] ?? null;
                  $explanations = is_array($meta['explanations'] ?? null) ? $meta['explanations'] : [];
                @endphp
                <tr>
                  <td>
                    <div class="match-candidate">
                      <strong>{{ $match->cv->candidate_name ?? 'Candidat inconnu' }}</strong>
                      <small>{{ $match->cv->phone ?? 'Telephone non disponible' }}</small>
                    </div>
                  </td>
                  <td><span class="pill pill-neutral">{{ $match->cv->email ?? '-' }}</span></td>
                  <td><span class="pill pill-neutral">{{ $match->cv->folder?->name ?? '-' }}</span></td>
                  <td><span class="match-score">{{ number_format($finalScore, 0) }}%</span></td>
                  <td>
                    <div class="match-summary">{{ $match->summary ?: 'Resume non disponible.' }}</div>

                    <div class="match-status-row">
                      @if($aiAvailable)
                        <span class="match-status match-status-ai">Analyse IA validee : {{ number_format($aiScore ?? 0, 0) }}%</span>
                      @elseif(!is_null($aiScore))
                        <span class="match-status match-status-local">Analyse complementaire : {{ number_format($aiScore, 0) }}%</span>
                      @else
                        <span class="match-status match-status-local">Score local</span>
                      @endif

                      @if(!is_null($localScore))
                        <span class="match-status match-status-neutral">Local : {{ number_format($localScore, 0) }}%</span>
                      @endif

                      <span class="match-status match-status-neutral">Final : {{ number_format($finalScore, 0) }}%</span>

                      @if($match->ai_analysis_status)
                        <span class="match-status match-status-neutral">
                          IA : {{ $jobStatusLabels[$match->ai_analysis_status] ?? $match->ai_analysis_status }}
                        </span>
                      @endif

                      @if($lastAnalysis)
                        <span class="match-status match-status-neutral">Analyse : {{ $lastAnalysis }}</span>
                      @endif
                    </div>

                    @if(!empty($fullBreakdown))
                      <div class="match-breakdown">
                        @foreach($fullBreakdown as $key => $value)
                          @php
                            $explanationKey = $breakdownExplanationKeys[$key] ?? $key;
                            $explanation = (string) ($explanations[$explanationKey] ?? 'Aucune explication detaillee disponible pour ce critere.');
                          @endphp
                          <button
                            class="match-tag match-tag-button js-criterion-detail"
                            type="button"
                            data-title="{{ e($breakdownLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))) }}"
                            data-score="{{ e(rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.')) }}"
                            data-explanation="{{ e($explanation) }}"
                          >
                            {{ $breakdownLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }} :
                            {{ rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') }}
                          </button>
                        @endforeach
                      </div>
                    @endif
                  </td>
                  <td class="match-actions-cell">
                    <div class="match-actions">
                      <a class="btn btn-light btn-sm" href="{{ route('admin.cvs.open', $match->cv) }}" target="_blank" rel="noopener">
                        Ouvrir
                      </a>
                    </div>
                  </td>
                  <td class="match-select-cell">
                    <input type="hidden" name="visible_matches[]" value="{{ $match->id }}">
                    <input
                      type="checkbox"
                      name="selected_matches[]"
                      value="{{ $match->id }}"
                      class="match-checkbox js-match-checkbox"
                      {{ $match->selected ? 'checked' : '' }}
                    >
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7">
                    <div class="match-empty">Aucun resultat disponible pour cette demande.</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if(method_exists($matches, 'hasPages') && $matches->hasPages())
          <div class="pagination-wrap" id="match-results-pagination">
            {{ $matches->links() }}
          </div>
        @else
          <div class="pagination-wrap" id="match-results-pagination" hidden></div>
        @endif
      @else
        <div class="match-processing-box" style="margin-top:18px;">
          <strong>Traitement asynchrone en cours</strong>
          <p>
            Vous pouvez rester sur cette page et utiliser le bouton <strong>Rafraichir</strong>, ou laisser l actualisation automatique verifier l arrivee des resultats toutes les 10 secondes.
          </p>
        </div>
      @endif
    </form>
  </div>
</div>

<div class="criterion-modal" id="criterion-detail-modal" hidden>
  <div class="criterion-modal-backdrop" data-criterion-close></div>
  <div class="criterion-modal-card" role="dialog" aria-modal="true" aria-labelledby="criterion-detail-title">
    <button class="criterion-modal-close" type="button" data-criterion-close aria-label="Fermer">x</button>
    <span class="pill pill-danger">Detail du critere</span>
    <h3 id="criterion-detail-title">Critere</h3>
    <div class="match-score" id="criterion-detail-score">0</div>
    <p id="criterion-detail-copy">Aucune explication disponible.</p>
  </div>
</div>
@endsection

@push('styles')
<style>
  .match-tag-button {
    border: 0;
    cursor: pointer;
    font: inherit;
  }

  .match-tag-button:hover {
    transform: translateY(-1px);
  }

  .match-search-item {
    min-width: min(430px, 100%);
  }

  .match-results-search {
    position: relative;
    width: min(430px, 100%);
    min-width: min(430px, 100%);
    z-index: 80;
  }

  .match-results-search input {
    width: 100% !important;
    min-height: 52px !important;
    height: 52px !important;
    padding: 0 18px 0 46px !important;
    border-radius: 18px !important;
    border: 1px solid #dbe3ee !important;
    background: rgba(255, 255, 255, .96) !important;
    color: #0f172a !important;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .04) !important;
    font-weight: 800 !important;
  }

  .match-results-search input::placeholder {
    color: #8b9ab1 !important;
    font-weight: 800 !important;
  }

  .match-results-search input:focus {
    outline: none !important;
    border-color: rgba(239, 35, 60, .32) !important;
    box-shadow: 0 0 0 4px rgba(239, 35, 60, .08), 0 18px 42px rgba(15, 23, 42, .08) !important;
  }

  .match-results-search .table-search-ico {
    left: 16px;
    color: #94a3b8;
  }

  .match-results-search.is-loading input {
    padding-right: 88px;
  }

  .match-results-search.is-loading::after {
    content: "Recherche";
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
  }

  .match-results-suggest {
    position: absolute !important;
    top: calc(100% + 10px) !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 120 !important;
    display: grid;
    gap: 4px;
    max-height: 340px;
    overflow: auto;
    padding: 10px !important;
    border: 1px solid #e5eaf1 !important;
    border-radius: 18px !important;
    background: rgba(255, 255, 255, .98) !important;
    box-shadow: 0 22px 60px rgba(15, 23, 42, .16) !important;
  }

  .match-results-suggest[hidden] {
    display: none !important;
  }

  .match-results-suggest .suggest-item {
    width: 100% !important;
    display: block !important;
    padding: 12px 14px !important;
    border: 0 !important;
    border-radius: 14px !important;
    background: transparent !important;
    color: #0f172a !important;
    text-align: left !important;
    text-decoration: none !important;
    cursor: pointer !important;
    box-shadow: none !important;
  }

  .match-results-suggest .suggest-item:hover,
  .match-results-suggest .suggest-item:focus {
    outline: none !important;
    background: #fff1f2 !important;
    color: #ef233c !important;
  }

  .match-results-suggest .suggest-title {
    color: inherit !important;
    font-size: 13px !important;
    line-height: 1.25 !important;
    font-weight: 900 !important;
  }

  .match-results-suggest .suggest-meta {
    margin-top: 5px !important;
    color: #64748b !important;
    font-size: 12px !important;
    line-height: 1.35 !important;
    font-weight: 750 !important;
  }

  @media (max-width: 900px) {
    .match-search-item,
    .match-results-search {
      width: 100%;
      min-width: 0;
    }
  }

  .criterion-modal[hidden] {
    display: none;
  }

  .criterion-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 24px;
  }

  .criterion-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, .55);
    backdrop-filter: blur(5px);
  }

  .criterion-modal-card {
    position: relative;
    width: min(560px, 100%);
    border-radius: 24px;
    border: 1px solid rgba(239, 68, 68, .22);
    background: #fff;
    box-shadow: 0 24px 80px rgba(15, 23, 42, .22);
    padding: 28px;
  }

  .criterion-modal-card h3 {
    margin: 14px 0 8px;
    font-size: 28px;
  }

  .criterion-modal-card p {
    color: #475569;
    line-height: 1.7;
    margin: 14px 0 0;
  }

  .criterion-modal-close {
    position: absolute;
    right: 18px;
    top: 18px;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 999px;
    background: #fee2e2;
    color: #ef3340;
    cursor: pointer;
    font-weight: 800;
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('download-selected-form');
  const counter = document.getElementById('selected-count');
  let checkboxes = Array.from(document.querySelectorAll('.js-match-checkbox'));
  const criterionModal = document.getElementById('criterion-detail-modal');
  const criterionTitle = document.getElementById('criterion-detail-title');
  const criterionScore = document.getElementById('criterion-detail-score');
  const criterionCopy = document.getElementById('criterion-detail-copy');
  let initialSelectedTotal = counter ? Number(counter.dataset.selectedTotal || 0) : 0;
  let initialVisibleSelected = checkboxes.filter(function (checkbox) {
    return checkbox.checked;
  }).length;

  const bindCriterionButtons = function () {
    document.querySelectorAll('.js-criterion-detail').forEach(function (button) {
      if (button.dataset.boundCriterion === '1') return;
      button.dataset.boundCriterion = '1';
      button.addEventListener('click', function () {
        if (!criterionModal) return;
        if (criterionTitle) criterionTitle.textContent = button.dataset.title || 'Critere';
        if (criterionScore) criterionScore.textContent = (button.dataset.score || '0') + ' pts';
        if (criterionCopy) criterionCopy.textContent = button.dataset.explanation || 'Aucune explication detaillee disponible.';
        criterionModal.hidden = false;
      });
    });
  };

  bindCriterionButtons();

  document.querySelectorAll('[data-criterion-close]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (criterionModal) criterionModal.hidden = true;
    });
  });

  const refreshCount = function () {
    if (!counter) {
      return;
    }

    const visibleSelected = checkboxes.filter(function (checkbox) {
      return checkbox.checked;
    }).length;

    counter.textContent = Math.max(0, initialSelectedTotal - initialVisibleSelected + visibleSelected);
  };

  const bindMatchCheckboxes = function () {
    checkboxes = Array.from(document.querySelectorAll('.js-match-checkbox'));
    initialSelectedTotal = counter ? Number(counter.dataset.selectedTotal || counter.textContent || 0) : 0;
    initialVisibleSelected = checkboxes.filter(function (checkbox) {
      return checkbox.checked;
    }).length;
    checkboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', refreshCount);
    });
  };

  bindMatchCheckboxes();

  const searchInput = document.getElementById('match-results-search');
  const suggestBox = document.getElementById('match-results-suggest');
  const searchShell = searchInput ? searchInput.closest('.match-results-search') : null;
  const filterForm = searchInput ? searchInput.closest('form') : null;
  const tbody = document.getElementById('match-results-tbody');
  const pagination = document.getElementById('match-results-pagination');
  const resultsFound = document.getElementById('matching-results-found');
  const resultsTotal = document.getElementById('matching-results-total');
  let searchTimer = null;
  let suggestTimer = null;
  let resultsAborter = null;
  let suggestAborter = null;

  const escapeHtml = function (value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      })[char];
    });
  };

  const currentResultsUrl = function (pageUrl) {
    const url = new URL(pageUrl || (searchInput?.dataset.resultsUrl || window.location.href), window.location.origin);
    const params = new FormData(filterForm);
    params.forEach(function (value, key) {
      if (value !== '') {
        url.searchParams.set(key, value);
      } else {
        url.searchParams.delete(key);
      }
    });
    return url;
  };

  const hideSuggest = function () {
    if (!suggestBox) return;
    suggestBox.hidden = true;
    suggestBox.innerHTML = '';
  };

  const renderSuggest = function (items) {
    if (!suggestBox || !searchInput || !Array.isArray(items) || items.length === 0) {
      hideSuggest();
      return;
    }

    suggestBox.innerHTML = items.map(function (item) {
      return '<button type="button" class="suggest-item" data-value="' + escapeHtml(item.value || item.title || '') + '">' +
        '<div class="suggest-title">' + escapeHtml(item.title || 'Candidat') + '</div>' +
        '<div class="suggest-meta">' + escapeHtml(item.meta || '') + '</div>' +
      '</button>';
    }).join('');
    suggestBox.hidden = false;

    suggestBox.querySelectorAll('.suggest-item').forEach(function (button) {
      button.addEventListener('click', function () {
        searchInput.value = button.dataset.value || '';
        hideSuggest();
        fetchResults();
      });
    });
  };

  const fetchSuggest = async function () {
    if (!searchInput || !searchInput.dataset.suggestUrl) return;
    const q = searchInput.value.trim();
    if (q.length < 2) {
      hideSuggest();
      return;
    }

    if (suggestAborter) suggestAborter.abort();
    suggestAborter = new AbortController();

    const url = new URL(searchInput.dataset.suggestUrl, window.location.origin);
    url.searchParams.set('q', q);

    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: suggestAborter.signal
    });

    if (!response.ok) throw new Error('Suggestions indisponibles.');
    renderSuggest(await response.json());
  };

  const applyResultsDocument = function (html, url) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const nextBody = doc.getElementById('match-results-tbody');
    const nextPagination = doc.getElementById('match-results-pagination');
    const nextFound = doc.getElementById('matching-results-found');
    const nextTotal = doc.getElementById('matching-results-total');
    const nextSelected = doc.getElementById('selected-count');

    if (tbody && nextBody) {
      tbody.innerHTML = nextBody.innerHTML;
    }
    if (pagination && nextPagination) {
      pagination.innerHTML = nextPagination.innerHTML;
      pagination.hidden = nextPagination.hidden;
    }
    if (resultsFound && nextFound) {
      resultsFound.textContent = nextFound.textContent;
    }
    if (resultsTotal && nextTotal) {
      resultsTotal.textContent = nextTotal.textContent;
    }
    if (counter && nextSelected) {
      counter.textContent = nextSelected.textContent;
      counter.dataset.selectedTotal = nextSelected.dataset.selectedTotal || nextSelected.textContent || '0';
    }

    bindCriterionButtons();
    bindMatchCheckboxes();
    bindPaginationLinks();
    window.history.replaceState({}, '', url.toString());
  };

  const fetchResults = async function (pageUrl) {
    if (!searchInput || !tbody) return;
    if (resultsAborter) resultsAborter.abort();
    resultsAborter = new AbortController();
    const url = currentResultsUrl(pageUrl);

    searchShell?.classList.add('is-loading');

    try {
      const response = await fetch(url, {
        headers: {
          'Accept': 'text/html',
          'X-Requested-With': 'XMLHttpRequest'
        },
        signal: resultsAborter.signal
      });

      if (!response.ok) throw new Error('Recherche indisponible.');
      applyResultsDocument(await response.text(), url);
    } finally {
      searchShell?.classList.remove('is-loading');
    }
  };

  const bindPaginationLinks = function () {
    if (!pagination) return;
    pagination.querySelectorAll('a[href]').forEach(function (link) {
      if (link.dataset.boundAjax === '1') return;
      link.dataset.boundAjax = '1';
      link.addEventListener('click', function (event) {
        event.preventDefault();
        fetchResults(link.href).catch(function () {
          window.location.href = link.href;
        });
      });
    });
  };

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      clearTimeout(suggestTimer);
      searchTimer = window.setTimeout(function () {
        fetchResults().catch(function () {});
      }, 260);
      suggestTimer = window.setTimeout(function () {
        fetchSuggest().catch(hideSuggest);
      }, 160);
    });

    searchInput.addEventListener('focus', function () {
      fetchSuggest().catch(hideSuggest);
    });

    document.addEventListener('click', function (event) {
      if (!suggestBox || !searchInput) return;
      if (!suggestBox.contains(event.target) && event.target !== searchInput) {
        hideSuggest();
      }
    });
  }

  bindPaginationLinks();

  const autoPreset = document.getElementById('match-auto-select-preset');
  const autoCustom = document.getElementById('match-auto-select-custom');
  const autoApply = document.getElementById('match-auto-select-apply');
  const autoClear = document.getElementById('match-auto-select-clear');
  const autoCount = document.getElementById('match-auto-select-count');
  const totalMatches = Number(@json($matchesTotal ?? 0));

  if (autoApply) {
    autoApply.addEventListener('click', function () {
      const requested = Number(autoCustom?.value || autoPreset?.value || 0);
      const safeCount = Math.max(0, Math.min(requested, Math.max(totalMatches, checkboxes.length)));
      const visibleCount = Math.min(safeCount, checkboxes.length);

      checkboxes.forEach(function (checkbox, index) {
        checkbox.checked = index < visibleCount;
      });

      if (autoCount) autoCount.value = safeCount > checkboxes.length ? safeCount : '';
      if (counter && safeCount > checkboxes.length) counter.textContent = safeCount;
      else refreshCount();
    });
  }

  if (autoPreset && autoCustom) {
    autoPreset.addEventListener('change', function () {
      autoCustom.value = autoPreset.value || '';
    });
  }

  if (autoClear) {
    autoClear.addEventListener('click', function () {
      checkboxes.forEach(function (checkbox) {
        checkbox.checked = false;
      });
      if (autoPreset) autoPreset.value = '';
      if (autoCustom) autoCustom.value = '';
      if (autoCount) autoCount.value = '';
      refreshCount();
    });
  }

  if (form) {
    form.addEventListener('submit', function () {
      const button = document.activeElement;

if (button && button.type === 'submit') {
  button.disabled = true;
  button.textContent = button.textContent.includes('Odoo')
    ? 'Envoi vers Odoo...'
    : 'Preparation du telechargement...';
}
    });
  }

  const odooStatusBox = document.getElementById('odoo-export-status');
  const odooStatusTitle = document.getElementById('odoo-export-title');
  const odooStatusMessage = document.getElementById('odoo-export-message');
  const odooStatusLink = document.getElementById('odoo-export-link');
  const initialOdooStatus = @json($odooInitialStatus);

  const odooTitles = {
    queued: 'Export Odoo en attente',
    running: 'Export Odoo en cours',
    success: 'Export Odoo termine',
    warning: 'Export Odoo a verifier',
    failed: 'Export Odoo echoue'
  };

  const renderOdooStatus = function (payload) {
    if (!odooStatusBox || !payload || payload.status === 'idle' || !payload.message) {
      return;
    }

    odooStatusBox.style.display = '';
    odooStatusBox.classList.remove('admin-alert-success', 'admin-alert-danger');
    if (payload.status === 'success') {
      odooStatusBox.classList.add('admin-alert-success');
    } else if (payload.status === 'failed') {
      odooStatusBox.classList.add('admin-alert-danger');
    }

    if (odooStatusTitle) {
      odooStatusTitle.textContent = odooTitles[payload.status] || 'Export Odoo';
    }
    if (odooStatusMessage) {
      odooStatusMessage.textContent = payload.message;
    }
    if (odooStatusLink) {
      if (payload.odoo_url) {
        odooStatusLink.href = payload.odoo_url;
        odooStatusLink.style.display = 'inline-flex';
      } else {
        odooStatusLink.style.display = 'none';
      }
    }
  };

  const pollOdooStatus = async function () {
    if (!odooStatusBox || !odooStatusBox.dataset.statusUrl) {
      return;
    }

    try {
      const response = await fetch(odooStatusBox.dataset.statusUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error('Statut Odoo indisponible.');
      }

      const payload = await response.json();
      renderOdooStatus(payload);

      if (payload.status === 'queued' || payload.status === 'running') {
        window.setTimeout(pollOdooStatus, 3000);
      }
    } catch (error) {
      window.setTimeout(pollOdooStatus, 7000);
    }
  };

  renderOdooStatus(initialOdooStatus);
  if (initialOdooStatus.status === 'queued' || initialOdooStatus.status === 'running') {
    window.setTimeout(pollOdooStatus, 1200);
  }

  if (@json($shouldAutoRefresh)) {
    const statusUrl = @json($matchingStatusUrl);
    const progressBar = document.getElementById('matching-progress-bar');
    const progressText = document.getElementById('matching-progress-text');
    const progressEta = document.getElementById('matching-progress-eta');
    const progressSubtext = document.getElementById('matching-progress-subtext');
    const processedNode = document.getElementById('matching-progress-processed');
    const totalNode = document.getElementById('matching-progress-total');
    const remainingNode = document.getElementById('matching-progress-remaining');

    const pollMatching = async function () {
      try {
        const response = await fetch(statusUrl, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error('Statut matching indisponible.');
        }

        const payload = await response.json();
        const percent = Number(payload.progress_percentage || 0);

        if (progressBar) progressBar.style.width = percent + '%';
        if (progressText) progressText.textContent = percent + '%';
        if (progressEta) progressEta.textContent = payload.estimated_time_remaining || 'Calcul en cours';
        if (processedNode) processedNode.textContent = payload.processed_items || payload.matches_count || 0;
        if (totalNode) totalNode.textContent = payload.total_items || 0;
        if (remainingNode) remainingNode.textContent = payload.remaining_items || 0;
        if (progressSubtext) {
          progressSubtext.innerHTML = (payload.status_message || 'Analyse des CV en arriere-plan.') + ' Temps estime restant : <strong id="matching-progress-eta">' + (payload.estimated_time_remaining || 'Calcul en cours') + '</strong>';
        }

        if (payload.status === 'completed' || payload.status === 'failed' || payload.status === 'cancelled') {
          window.setTimeout(function () {
            window.location.reload();
          }, 900);
          return;
        }

        window.setTimeout(pollMatching, 5000);
      } catch (error) {
        window.setTimeout(function () {
          window.location.reload();
        }, 10000);
      }
    };

    window.setTimeout(pollMatching, 2500);
  }
});
</script>
@endpush
