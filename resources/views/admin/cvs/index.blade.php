@extends('admin.layouts.app')

@section('title', $archived ? 'Admin - Archives CV' : 'Admin - CV Bank')
@section('page_title', $archived ? 'Archives CV' : 'CV Bank')

@section('page_subtitle')
{{ $archived ? 'Consultez les CV archives sans les remettre dans la banque active par erreur.' : 'Gerez tous les CV provenant des candidatures, des ajouts manuels et de la base externe.' }}
@endsection

@section('top_actions')
  <div class="action-bar">
    @if(!$archived)
      <form method="POST" action="{{ route('admin.cvs.optimize-uncompressed-storage') }}" class="action-bar-form">
        @csrf
        <button type="submit" class="btn btn-ghost">Compresser les CV non optimises</button>
      </form>
      <form method="POST" action="{{ route('admin.cvs.retry-failed-compression') }}" class="action-bar-form">
        @csrf
        <button type="submit" class="btn btn-ghost">Recompresser les echecs</button>
      </form>
      <a class="btn btn-primary" href="{{ route('admin.cvs.create') }}">
        <span class="btn-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </span>
        Ajouter des CV
      </a>
      <a class="btn btn-ghost" href="{{ route('admin.cvs.archived') }}">Voir les archives</a>
    @else
      <a class="btn btn-ghost" href="{{ route('admin.cvs.index') }}">Retour a la CV Bank</a>
    @endif
  </div>
@endsection

@section('content')
@php
  $compressionStatusLabels = \App\Models\Cv::availableCompressionStatuses();
  $compressionProgress = $storageStats['compression_progress'] ?? [];
  $formatBytes = static function (?int $bytes): string {
      $bytes = max(0, (int) $bytes);
      $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
      $index = 0;
      $size = (float) $bytes;

      while ($size >= 1024 && $index < count($units) - 1) {
          $size /= 1024;
          $index++;
      }

      return number_format($size, $size >= 10 || $index === 0 ? 0 : 1, ',', ' ') . ' ' . $units[$index];
  };
@endphp

<div class="cv-page">
  <div class="dash-kpis dash-kpis--four" style="margin-bottom:18px;">
    <div class="dash-kpi">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">CV en base</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M14 3v5h4M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value" id="cv-storage-total-files">{{ $storageStats['total_files'] ?? 0 }}</div>
      <div class="dash-kpi-foot"><span>Fichiers admin prives</span></div>
      <span class="dash-kpi-accent"></span>
    </div>

    <div class="dash-kpi">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Taille originale</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M5 7h14M7 7V5h10v2M7 7v12h10V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value" id="cv-storage-original-size">{{ $formatBytes($storageStats['total_original_size'] ?? 0) }}</div>
      <div class="dash-kpi-foot"><span>Volume avant optimisation</span></div>
      <span class="dash-kpi-accent"></span>
    </div>

    <div class="dash-kpi">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Stockage optimise</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 7c0 2.21 3.58 4 8 4s8-1.79 8-4-3.58-4-8-4-8 1.79-8 4Z" stroke="currentColor" stroke-width="1.8"/>
            <path d="M4 7v5c0 2.21 3.58 4 8 4s8-1.79 8-4V7" stroke="currentColor" stroke-width="1.8"/>
            <path d="M4 12v5c0 2.21 3.58 4 8 4s8-1.79 8-4v-5" stroke="currentColor" stroke-width="1.8"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value" id="cv-storage-current-size">{{ $formatBytes($storageStats['total_current_size'] ?? 0) }}</div>
      <div class="dash-kpi-foot">
        <span id="cv-storage-optimized-foot">
          {{ (int) ($storageStats['compression_completed_files'] ?? 0) }} CV optimise(s)
          @if(!empty($storageStats['keep_originals']))
            · originaux conserves en securite
          @endif
        </span>
      </div>
      <span class="dash-kpi-accent"></span>
    </div>

    <div class="dash-kpi">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Gain estime</div>
        <div class="dash-kpi-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 17 10 11l4 4 6-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M15 7h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value" id="cv-storage-saved-size">{{ $formatBytes($storageStats['estimated_saved_space'] ?? 0) }}</div>
      <div class="dash-kpi-foot">
        <span id="cv-storage-gain-foot">
          {{ (int) ($storageStats['compression_processing_files'] ?? 0) }} en cours ·
          {{ (int) ($storageStats['compression_pending_files'] ?? 0) }} en attente ·
          {{ (int) ($storageStats['compression_failed_files'] ?? 0) }} echec(s) ·
          {{ (int) ($storageStats['compression_missing_files'] ?? 0) }} fichier(s) manquant(s)
        </span>
      </div>
      <span class="dash-kpi-accent"></span>
    </div>
  </div>

  @if(!$archived)
    <div class="ui-progress-card cv-optimization-progress" style="margin-bottom:18px;">
      <div class="ui-progress-head">
        <div>
          <strong>Progression de l optimisation stockage</strong>
          <div class="ui-progress-copy" style="margin-top:4px;">
            Compression en arriere-plan. Vous pouvez quitter cette page, le worker continue le traitement.
          </div>
        </div>
        <span id="cv-storage-progress-text" class="match-score">
          {{ (int) ($compressionProgress['progress_percentage'] ?? 0) }}%
        </span>
      </div>
      <div class="ui-progress-track">
        <div
          id="cv-storage-progress-bar"
          class="ui-progress-bar"
          style="width:{{ (int) ($compressionProgress['progress_percentage'] ?? 0) }}%;"
        ></div>
      </div>
      <div class="ui-progress-kpis">
        <span><strong id="cv-storage-completed">{{ (int) ($compressionProgress['completed_files'] ?? 0) }}</strong> optimises</span>
        <span><strong id="cv-storage-processing">{{ (int) ($compressionProgress['processing_files'] ?? 0) }}</strong> en cours</span>
        <span><strong id="cv-storage-pending">{{ (int) ($compressionProgress['pending_files'] ?? 0) }}</strong> en attente</span>
        <span><strong id="cv-storage-queued-jobs">{{ (int) ($compressionProgress['queued_jobs'] ?? 0) }}</strong> jobs queue</span>
        <span><strong id="cv-storage-unoptimized">{{ (int) ($compressionProgress['unoptimized_files'] ?? 0) }}</strong> non optimises</span>
        <span><strong id="cv-storage-failed">{{ (int) ($compressionProgress['failed_files'] ?? 0) }}</strong> echecs</span>
        <span><strong id="cv-storage-missing">{{ (int) ($compressionProgress['missing_files'] ?? 0) }}</strong> fichiers manquants</span>
      </div>
      <div id="cv-storage-progress-subtext" class="ui-progress-copy">
        {{ $compressionProgress['status_message'] ?? 'Compression en arriere-plan.' }}
        Temps estime restant :
        <strong id="cv-storage-eta">{{ $compressionProgress['estimated_time_remaining'] ?? 'Calcul en cours' }}</strong>
      </div>
      <div style="margin-top:12px;">
        <button type="button" class="btn btn-ghost btn-sm" id="cv-storage-load-status">
          Charger le statut stockage
        </button>
      </div>
    </div>
  @endif

  <div class="cv-filters-sticky">
    <div class="panel cv-filters-panel panel-safe">
      <div class="panel-head">
        <div class="panel-title">Filtres et tri</div>
      </div>

      <div class="panel-body">
        <div class="cv-filter-scroll">
          <div class="cv-filter-surface">
            <div class="cv-inline-row">
              <div class="cv-note">
                Vous pouvez filtrer la banque de CV par recherche, source, offre, dossier, statut et ordre de date.
              </div>

              <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <form action="{{ route('admin.cv-folders.store') }}" method="POST" class="cv-inline-form">
                  @csrf
                  <input
                    type="text"
                    name="name"
                    class="cv-input"
                    placeholder="Creer un nouveau dossier"
                    value="{{ old('name') }}"
                  >
                  @if(!$archived)
                    <button type="submit" class="btn btn-ghost">Creer dossier</button>

                    @if(($folders ?? collect())->count())
                      <button type="button" class="btn btn-ghost" id="openArchiveFolderModal">
                        Archiver dossier
                      </button>
                      <button type="button" class="btn btn-ghost" id="openDeleteFolderModal">
                        Supprimer dossier
                      </button>
                    @endif
                  @elseif(($folders ?? collect())->count())
                    <button type="button" class="btn btn-ghost" id="openRestoreFolderModal">
                      Restaurer dossier
                    </button>
                  @endif
                </form>
              </div>
            </div>

            <form method="GET" action="{{ $archived ? route('admin.cvs.archived') : route('admin.cvs.index') }}">
              <div class="cv-filters-grid">
            <div>
              <div class="info-label">Recherche</div>
              <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Nom, email, telephone, ville, poste..."
                class="cv-input"
              >
            </div>

            <div>
              <div class="info-label">Source</div>
              <select name="source" class="cv-select">
                <option value="all" {{ ($source ?? 'all') === 'all' ? 'selected' : '' }}>Toutes</option>
                <option value="application" {{ ($source ?? '') === 'application' ? 'selected' : '' }}>Candidatures</option>
                <option value="external_db" {{ ($source ?? '') === 'external_db' ? 'selected' : '' }}>Base externe</option>
                <option value="manual" {{ ($source ?? '') === 'manual' ? 'selected' : '' }}>Ajout manuel</option>
              </select>
            </div>

            <div>
              <div class="info-label">Offre</div>
              <select name="offer" class="cv-select">
                <option value="all" {{ ($offer ?? 'all') === 'all' ? 'selected' : '' }}>Toutes</option>
                <option value="spontaneous" {{ ($offer ?? '') === 'spontaneous' ? 'selected' : '' }}>Spontanee</option>
                @foreach(($offers ?? collect()) as $item)
                  <option value="{{ $item->id }}" {{ (string) ($offer ?? '') === (string) $item->id ? 'selected' : '' }}>
                    {{ $item->title }}
                  </option>
                @endforeach
              </select>
            </div>

            <div>
              <div class="info-label">Dossier</div>
              <select name="folder" class="cv-select">
                <option value="all" {{ ($folder ?? 'all') === 'all' ? 'selected' : '' }}>Tous</option>
                @foreach(($folders ?? collect()) as $item)
                  <option value="{{ $item->id }}" {{ (string) ($folder ?? '') === (string) $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div>
              <div class="info-label">Statut</div>
              <select name="status" class="cv-select">
                <option value="active" {{ ($status ?? 'active') === 'active' ? 'selected' : '' }}>Actifs</option>
                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>Tous</option>
              </select>
            </div>

            <div>
              <div class="info-label">Ordre de date</div>
              <select name="direction" class="cv-select">
                <option value="desc" {{ ($direction ?? 'desc') === 'desc' ? 'selected' : '' }}>Decroissant</option>
                <option value="asc" {{ ($direction ?? '') === 'asc' ? 'selected' : '' }}>Croissant</option>
              </select>
            </div>

            <div>
              <div class="info-label">&nbsp;</div>
              <div class="file-actions" style="justify-content:flex-start;">
                <button type="submit" class="btn btn-primary">Appliquer</button>
                <a href="{{ $archived ? route('admin.cvs.archived') : route('admin.cvs.index') }}" class="btn btn-ghost">Reinitialiser</a>
              </div>
            </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <form
    id="bulkDeleteForm"
    action="{{ route('admin.cvs.bulk-destroy') }}"
    method="POST"
    data-rhs-confirm="Supprimer les CV selectionnes ?"
    onsubmit="return confirm('Supprimer les CV selectionnes ?');"
  >
    @csrf
    @method('DELETE')
  </form>

  <form
    id="bulkAssignFolderForm"
    action="{{ route('admin.cvs.bulk-assign-folder') }}"
    method="POST"
  >
    @csrf
    @method('PATCH')
  </form>

  <form
    id="bulkArchiveForm"
    action="{{ route('admin.cvs.bulk-archive') }}"
    method="POST"
  >
    @csrf
    @method('PATCH')
  </form>

  @php
    $cvPanelTotal = isset($cvListTotal)
      ? $cvListTotal
      : (isset($cvs) && method_exists($cvs, 'total')
        ? (int) $cvs->total()
        : (isset($cvs) ? $cvs->count() : 0));
  @endphp

  <div class="panel cv-table-panel panel-safe table-safe">
    <div class="panel-head">
      <div class="panel-title">
        Liste des CV
        <span class="panel-badge">{{ is_numeric($cvPanelTotal) ? number_format((int) $cvPanelTotal, 0, ',', ' ') : $cvPanelTotal }}</span>
      </div>

      <div class="cv-bulk-actions bulk-actions" id="bulkActions">
        <span class="cv-bulk-count">
          <span id="selectedCount">0</span> selectionne(s)
        </span>

        <select
          id="bulkFolderSelect"
          name="cv_folder_id"
          form="bulkAssignFolderForm"
          class="cv-select cv-bulk-folder-select"
          title="Assigner les CV selectionnes a un dossier"
        >
          <option value="">Retirer du dossier</option>
          @foreach(($folders ?? collect()) as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
          @endforeach
        </select>

        <button type="submit" form="bulkAssignFolderForm" class="btn btn-ghost">Assigner au dossier</button>

        @if(!$archived)
          <button type="submit" form="bulkArchiveForm" class="btn btn-ghost">Archiver la selection</button>
        @endif

        <button type="submit" form="bulkDeleteForm" class="btn cv-bulk-delete-btn">
          Supprimer selection
        </button>
      </div>
    </div>

    <div class="panel-body" style="padding:0;">
      @if(isset($cvs) && $cvs->count())
        <div class="cv-table-wrap">
          <table class="cv-table">
            <thead>
              <tr>
                <th>Candidat</th>
                <th>Contact</th>
                <th>Poste</th>
                <th>Ville</th>
                <th>Source</th>
                <th>Dossier</th>
                <th>Fichier / stockage</th>
                <th>Ajoute le</th>
                <th style="text-align:right;">
                  <label style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                    <span>Actions</span>
                    <input type="checkbox" id="selectAllCvs" class="cv-select-check" title="Tout selectionner">
                  </label>
                </th>
              </tr>
            </thead>

            <tbody>
              @foreach($cvs as $cv)
                @php
                  $resolvedSource = $cv->source_type;

                  if (!$resolvedSource && !empty($cv->source_id)) {
                    $resolvedSource = 'application';
                  }

                  if (!$resolvedSource && empty($cv->encrypted_path) && (!empty($cv->email) || !empty($cv->candidate_name))) {
                    $resolvedSource = 'legacy_application';
                  }

                  $sourceLabel = match ($resolvedSource) {
                    'application' => 'Candidature',
                    'external_db' => 'Base externe',
                    'manual' => 'Manuel',
                    'legacy_application' => 'Ancienne candidature',
                    default => 'Inconnu',
                  };

                  $sourceClass = match ($resolvedSource) {
                    'application' => 'source-application',
                    'external_db' => 'source-external',
                    'manual' => 'source-manual',
                    'legacy_application' => 'source-legacy',
                    default => '',
                  };

                  $displayTitle = $cv->current_title
                    ?? data_get($cv->structured_profile, 'title')
                    ?? data_get($cv->structured_profile, 'current_title')
                    ?? data_get($cv->structured_profile, 'headline')
                    ?? data_get($cv->structured_profile, 'desired_position')
                    ?? '-';

                  $displayCity = $cv->city
                    ?? data_get($cv->structured_profile, 'city')
                    ?? data_get($cv->structured_profile, 'location.city')
                    ?? data_get($cv->structured_profile, 'address.city')
                    ?? '-';
                @endphp

                <tr>
                  <td>
                    <div class="cv-main text-safe">{{ $cv->candidate_name ?: '-' }}</div>
                  </td>

                  <td>
                    <div class="text-safe">{{ $cv->email ?: '-' }}</div>
                    <div class="cv-sub text-safe">{{ $cv->phone ?: '-' }}</div>
                  </td>

                  <td class="text-safe">{{ $displayTitle }}</td>

                  <td class="text-safe">{{ $displayCity }}</td>

                  <td>
                    <span class="cv-badge {{ $sourceClass }}">{{ $sourceLabel }}</span>

                    @if(isset($cv->is_active) && !$cv->is_active)
                      <div style="margin-top:8px;">
                        <span class="cv-badge status-inactive">Inactif</span>
                      </div>
                    @endif
                  </td>

                  <td class="text-safe">{{ $cv->folder?->name ?? '-' }}</td>

                  <td>
                    <div class="cv-file-name text-safe" title="{{ $cv->original_filename }}">
                      {{ $cv->original_filename ?: '-' }}
                    </div>
                    <div class="cv-sub">
                      Original : {{ $formatBytes($cv->original_file_size ?: $cv->file_size) }}
                      @if(!empty($cv->compressed_file_size) && !empty($cv->compression_verified_at))
                        <br>Compresse : {{ $formatBytes($cv->compressed_file_size) }}
                      @endif
                    </div>

                    @if(!empty($cv->compression_status))
                      <div style="margin-top:8px;">
                        <span class="cv-badge {{ $cv->compression_status === 'completed' ? 'source-manual' : ($cv->compression_status === 'failed' ? 'status-inactive' : 'source-legacy') }}">
                          {{ $compressionStatusLabels[$cv->compression_status] ?? ucfirst($cv->compression_status) }}
                        </span>
                      </div>
                    @endif

                    @if(!empty($cv->duplicate_of_cv_id))
                      <div class="cv-sub wrap-safe" style="margin-top:6px;">
                        Doublon rapproche du CV #{{ $cv->duplicate_of_cv_id }}
                      </div>
                    @endif
                  </td>

                  <td>
                    {{ optional($cv->uploaded_at)->format('Y-m-d H:i') ?? optional($cv->created_at)->format('Y-m-d H:i') ?? '-' }}
                  </td>

                  <td>
                    <div class="cv-actions">
                      <input
                        type="checkbox"
                        value="{{ $cv->id }}"
                        data-cv-id="{{ $cv->id }}"
                        class="cv-select-check cv-row-check"
                        title="Selectionner ce CV"
                      >

                      <a
                        class="cv-icon-btn primary"
                        href="{{ route('admin.cvs.open', $cv) }}"
                        target="_blank"
                        rel="noopener"
                        title="Ouvrir"
                      >
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                          <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2"/>
                          <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>
                      </a>

                      <form action="{{ route('admin.cvs.assign-folder', $cv) }}" method="POST" class="cv-folder-assign">
                        @csrf
                        @method('PATCH')

                        <select name="cv_folder_id" title="Assigner a un dossier">
                          <option value="">Aucun</option>
                          @foreach(($folders ?? collect()) as $item)
                            <option value="{{ $item->id }}" {{ (string) $cv->cv_folder_id === (string) $item->id ? 'selected' : '' }}>
                              {{ $item->name }}
                            </option>
                          @endforeach
                        </select>

                        <button type="submit" class="cv-icon-btn warn" title="Assigner dossier">
                          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                            <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 10h18l-1.2 8a2 2 0 0 1-2 1.7H6.2a2 2 0 0 1-2-1.7L3 10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                          </svg>
                        </button>
                      </form>

                      @if(!$archived)
                        <form action="{{ route('admin.cvs.archive', $cv) }}" method="POST" style="display:inline;">
                          @csrf
                          @method('PATCH')
                          <button type="submit" class="cv-icon-btn warn" title="Archiver">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                              <path d="M4 7h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                              <path d="M3 7l2-3h14l2 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                              <path d="M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                          </button>
                        </form>
                      @else
                        <form action="{{ route('admin.cvs.restore', $cv) }}" method="POST" style="display:inline;">
                          @csrf
                          @method('PATCH')
                          <button type="submit" class="cv-icon-btn primary" title="Restaurer">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                              <path d="M20 12a8 8 0 1 1-2.34-5.66L20 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                              <path d="M20 4v5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                          </button>
                        </form>
                      @endif

                      <form
                        action="{{ route('admin.cvs.destroy', $cv) }}"
                        method="POST"
                        data-rhs-confirm="Supprimer ce CV ?"
                        onsubmit="return confirm('Supprimer ce CV ?');"
                        style="display:inline;"
                      >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="cv-icon-btn danger" title="Supprimer">
                          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                            <path d="M4 7h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
          {{ $cvs->links() }}
        </div>
      @else
        <div class="cv-empty">
          <div class="cv-empty-title">{{ $archived ? 'Aucun CV archive' : 'Aucun CV trouve' }}</div>
          <div class="cv-empty-subtitle">
            {{ $archived ? 'Aucun CV archive ne correspond aux filtres selectionnes.' : 'Votre CV Bank est vide pour le moment, ou aucun resultat ne correspond aux filtres selectionnes.' }}
          </div>
          @if(!$archived)
            <a href="{{ route('admin.cvs.create') }}" class="btn btn-primary">Ajouter des CV</a>
          @else
            <a href="{{ route('admin.cvs.index') }}" class="btn btn-ghost">Retour a la CV Bank</a>
          @endif
        </div>
      @endif
    </div>
  </div>
</div>

@if(($folders ?? collect())->count())
  @if($archived)
    <div class="cv-modal-backdrop" id="restoreFolderModal">
      <div class="cv-modal" role="dialog" aria-modal="true" aria-labelledby="restoreFolderModalTitle">
        <div class="cv-modal-head">
          <div class="cv-modal-title" id="restoreFolderModalTitle">Restaurer un dossier</div>

          <button type="button" class="cv-modal-close" id="closeRestoreFolderModal" aria-label="Fermer">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form method="POST" id="restoreFolderForm">
          @csrf
          @method('PATCH')

          <div class="cv-modal-body">
            <div>
              <label class="info-label" for="folderToRestore">Dossier</label>
              <select id="folderToRestore" class="cv-select">
                @foreach(($folders ?? collect()) as $folderItem)
                  <option value="{{ $folderItem->id }}">{{ $folderItem->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="cv-modal-text">
              Tous les CV archives de ce dossier seront remis dans la CV Bank active.
            </div>
          </div>

          <div class="cv-modal-actions">
            <button type="button" class="btn btn-ghost" id="cancelRestoreFolderModal">Annuler</button>
            <button type="submit" class="btn btn-primary">Restaurer</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <div class="cv-modal-backdrop" id="archiveFolderModal">
    <div class="cv-modal" role="dialog" aria-modal="true" aria-labelledby="archiveFolderModalTitle">
      <div class="cv-modal-head">
        <div class="cv-modal-title" id="archiveFolderModalTitle">Archiver un dossier</div>

        <button type="button" class="cv-modal-close" id="closeArchiveFolderModal" aria-label="Fermer">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <form method="POST" id="archiveFolderForm">
        @csrf
        @method('PATCH')

        <div class="cv-modal-body">
          <div>
            <label class="info-label" for="folderToArchive">Dossier</label>
            <select id="folderToArchive" class="cv-select">
              @foreach(($folders ?? collect()) as $folderItem)
                <option value="{{ $folderItem->id }}">{{ $folderItem->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="cv-modal-text">
            Tous les CV actifs assignes a ce dossier seront retires de la CV Bank active et envoyes dans la page d archives.
          </div>
        </div>

        <div class="cv-modal-actions">
          <button type="button" class="btn btn-ghost" id="cancelArchiveFolderModal">Annuler</button>
          <button type="submit" class="btn btn-primary">Archiver</button>
        </div>
      </form>
    </div>
  </div>

  <div class="cv-modal-backdrop" id="deleteFolderModal">
    <div class="cv-modal" role="dialog" aria-modal="true" aria-labelledby="deleteFolderModalTitle">
      <div class="cv-modal-head">
        <div class="cv-modal-title" id="deleteFolderModalTitle">Supprimer un dossier</div>

        <button type="button" class="cv-modal-close" id="closeDeleteFolderModal" aria-label="Fermer">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <form method="POST" id="deleteFolderForm">
        @csrf
        @method('DELETE')

        <div class="cv-modal-body">
          <div>
            <label class="info-label" for="folderToDelete">Dossier</label>
            <select name="folder_id_ui" id="folderToDelete" class="cv-select">
              @foreach(($folders ?? collect()) as $folderItem)
                <option value="{{ $folderItem->id }}">
                  {{ $folderItem->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="cv-modal-text">
            Choisissez ce que vous voulez supprimer. Cette action est sensible.
          </div>

          <div class="cv-radio-list">
            <div class="cv-radio-card">
              <label>
                <input type="radio" name="delete_mode" value="folder_only" checked>
                <div>
                  <div class="cv-radio-title">Supprimer uniquement le dossier</div>
                  <div class="cv-radio-desc">
                    Le dossier sera supprime, mais les CV resteront dans la CV Bank et seront simplement desassignes.
                  </div>
                </div>
              </label>
            </div>

            <div class="cv-radio-card">
              <label>
                <input type="radio" name="delete_mode" value="folder_and_files">
                <div>
                  <div class="cv-radio-title">Supprimer le dossier et les fichiers associes</div>
                  <div class="cv-radio-desc">
                    Le dossier sera supprime ainsi que tous les CV qui lui sont assignes.
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <div class="cv-modal-actions">
          <button type="button" class="btn btn-ghost" id="cancelDeleteFolderModal">Annuler</button>
          <button type="submit" class="btn cv-danger-btn">Supprimer</button>
        </div>
      </form>
    </div>
  </div>
@endif

<script>
(function () {
  const selectAll = document.getElementById('selectAllCvs');
  const rowChecks = document.querySelectorAll('.cv-row-check');
  const bulkActions = document.getElementById('bulkActions');
  const selectedCount = document.getElementById('selectedCount');
  const bulkForms = [
    document.getElementById('bulkDeleteForm'),
    document.getElementById('bulkAssignFolderForm'),
    document.getElementById('bulkArchiveForm'),
    document.getElementById('bulkOptimizeStorageForm'),
  ].filter(Boolean);

  function getSelectedIds() {
    return Array.from(document.querySelectorAll('.cv-row-check:checked'))
      .map(check => check.dataset.cvId || check.value)
      .filter(Boolean);
  }

  function syncBulkForm(form) {
    form.querySelectorAll('.js-bulk-generated').forEach(input => input.remove());

    getSelectedIds().forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'cv_ids[]';
      input.value = id;
      input.className = 'js-bulk-generated';
      form.appendChild(input);
    });
  }

  function updateBulkActions() {
    const checked = getSelectedIds().length;

    if (selectedCount) {
      selectedCount.textContent = checked;
    }

    if (bulkActions) {
      bulkActions.classList.toggle('is-visible', checked > 0);
    }

    if (selectAll) {
      selectAll.checked = checked === rowChecks.length && rowChecks.length > 0;
      selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
    }
  }

  rowChecks.forEach(check => {
    check.addEventListener('change', updateBulkActions);
  });

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      rowChecks.forEach(check => {
        check.checked = selectAll.checked;
      });

      updateBulkActions();
    });
  }

  bulkForms.forEach(form => {
    form.addEventListener('submit', function (event) {
      if (!getSelectedIds().length) {
        event.preventDefault();
        updateBulkActions();
        return;
      }

      syncBulkForm(form);
    });
  });

  updateBulkActions();
})();
</script>

@if(!$archived)
<script>
(function () {
  const statusUrl = @json(route('admin.cvs.storage-optimization-status'));
  const progressBar = document.getElementById('cv-storage-progress-bar');
  const progressText = document.getElementById('cv-storage-progress-text');
  const etaNode = document.getElementById('cv-storage-eta');
  const subtextNode = document.getElementById('cv-storage-progress-subtext');
  const completedNode = document.getElementById('cv-storage-completed');
  const processingNode = document.getElementById('cv-storage-processing');
  const pendingNode = document.getElementById('cv-storage-pending');
  const queuedJobsNode = document.getElementById('cv-storage-queued-jobs');
  const unoptimizedNode = document.getElementById('cv-storage-unoptimized');
  const failedNode = document.getElementById('cv-storage-failed');
  const missingNode = document.getElementById('cv-storage-missing');
  const loadButton = document.getElementById('cv-storage-load-status');
  const totalFilesNode = document.getElementById('cv-storage-total-files');
  const originalSizeNode = document.getElementById('cv-storage-original-size');
  const currentSizeNode = document.getElementById('cv-storage-current-size');
  const savedSizeNode = document.getElementById('cv-storage-saved-size');
  const optimizedFootNode = document.getElementById('cv-storage-optimized-foot');
  const gainFootNode = document.getElementById('cv-storage-gain-foot');

  if (!progressBar || !progressText) {
    return;
  }

  const formatNumber = function (value) {
    return Number(value || 0).toLocaleString('fr-FR');
  };

  const formatBytes = function (bytes) {
    let size = Math.max(0, Number(bytes || 0));
    const units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    let index = 0;

    while (size >= 1024 && index < units.length - 1) {
      size = size / 1024;
      index++;
    }

    const digits = size >= 10 || index === 0 ? 0 : 1;
    return size.toLocaleString('fr-FR', {
      minimumFractionDigits: digits,
      maximumFractionDigits: digits
    }) + ' ' + units[index];
  };

  const statusUrlWithFilters = function () {
    const url = new URL(statusUrl, window.location.origin);
    const currentParams = new URLSearchParams(window.location.search);

    currentParams.forEach(function (value, key) {
      if (key !== 'page') {
        url.searchParams.set(key, value);
      }
    });

    url.searchParams.set('archived', @json((bool) $archived));

    return url.toString();
  };

  const updateProgress = function (payload) {
    const percent = Number(payload.progress_percentage || 0);
    progressBar.style.width = percent + '%';
    progressText.textContent = percent + '%';

    if (totalFilesNode) totalFilesNode.textContent = formatNumber(payload.total_files || 0);
    if (originalSizeNode) originalSizeNode.textContent = formatBytes(payload.total_original_size || 0);
    if (currentSizeNode) currentSizeNode.textContent = formatBytes(payload.total_current_size || 0);
    if (savedSizeNode) savedSizeNode.textContent = formatBytes(payload.estimated_saved_space || 0);
    if (optimizedFootNode) optimizedFootNode.textContent = formatNumber(payload.completed_files || 0) + ' CV optimise(s)';
    if (gainFootNode) {
      gainFootNode.textContent =
        formatNumber(payload.processing_files || 0) + ' en cours - ' +
        formatNumber(payload.pending_files || 0) + ' en attente - ' +
        formatNumber(payload.failed_files || 0) + ' echec(s) - ' +
        formatNumber(payload.missing_files || 0) + ' fichier(s) manquant(s)';
    }

    if (etaNode) etaNode.textContent = payload.estimated_time_remaining || 'Calcul en cours';
    if (completedNode) completedNode.textContent = payload.completed_files || 0;
    if (processingNode) processingNode.textContent = payload.processing_files || 0;
    if (pendingNode) pendingNode.textContent = payload.pending_files || 0;
    if (queuedJobsNode) queuedJobsNode.textContent = payload.queued_jobs || 0;
    if (unoptimizedNode) unoptimizedNode.textContent = payload.unoptimized_files || 0;
    if (failedNode) failedNode.textContent = payload.failed_files || 0;
    if (missingNode) missingNode.textContent = payload.missing_files || 0;

    if (subtextNode) {
      const active = Number(payload.processing_files || 0) + Number(payload.pending_files || 0);
      const message = payload.status_message || (active > 0 ? 'Compression en arriere-plan.' : 'Aucun traitement actif.');
      subtextNode.innerHTML = message + ' Temps estime restant : <strong id="cv-storage-eta">' + (payload.estimated_time_remaining || 'Calcul en cours') + '</strong>';
    }
  };

  const poll = async function () {
    try {
      if (loadButton) {
        loadButton.disabled = true;
        loadButton.textContent = 'Chargement...';
      }

      const response = await fetch(statusUrlWithFilters(), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        return;
      }

      const payload = await response.json();
      updateProgress(payload);

      if (loadButton) {
        loadButton.disabled = false;
        loadButton.textContent = 'Actualiser le statut stockage';
      }

      // Manual refresh only: avoids a long status request blocking other local pages.
    } catch (error) {
      // Keep the page usable if the worker/status endpoint is temporarily unavailable.
      if (loadButton) {
        loadButton.disabled = false;
        loadButton.textContent = 'Reessayer le statut stockage';
      }
    }
  };

  if (loadButton) {
    loadButton.addEventListener('click', poll);
  }
})();
</script>
@endif

@if(($folders ?? collect())->count())
<script>
(function () {
  const restoreModal = document.getElementById('restoreFolderModal');
  const openRestoreBtn = document.getElementById('openRestoreFolderModal');
  const closeRestoreBtn = document.getElementById('closeRestoreFolderModal');
  const cancelRestoreBtn = document.getElementById('cancelRestoreFolderModal');
  const restoreSelect = document.getElementById('folderToRestore');
  const restoreForm = document.getElementById('restoreFolderForm');
  const archiveModal = document.getElementById('archiveFolderModal');
  const openArchiveBtn = document.getElementById('openArchiveFolderModal');
  const closeArchiveBtn = document.getElementById('closeArchiveFolderModal');
  const cancelArchiveBtn = document.getElementById('cancelArchiveFolderModal');
  const archiveSelect = document.getElementById('folderToArchive');
  const archiveForm = document.getElementById('archiveFolderForm');
  const modal = document.getElementById('deleteFolderModal');
  const openBtn = document.getElementById('openDeleteFolderModal');
  const closeBtn = document.getElementById('closeDeleteFolderModal');
  const cancelBtn = document.getElementById('cancelDeleteFolderModal');
  const select = document.getElementById('folderToDelete');
  const form = document.getElementById('deleteFolderForm');

  if (restoreModal && openRestoreBtn && restoreSelect && restoreForm) {
    const openRestoreModal = function () {
      restoreModal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    };

    const closeRestoreModal = function () {
      restoreModal.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    const updateRestoreAction = function () {
      restoreForm.action = "{{ url('/admin/cv-folders') }}/" + restoreSelect.value + "/restore";
    };

    openRestoreBtn.addEventListener('click', function () {
      updateRestoreAction();
      openRestoreModal();
    });

    restoreSelect.addEventListener('change', updateRestoreAction);

    if (closeRestoreBtn) closeRestoreBtn.addEventListener('click', closeRestoreModal);
    if (cancelRestoreBtn) cancelRestoreBtn.addEventListener('click', closeRestoreModal);

    restoreModal.addEventListener('click', function (e) {
      if (e.target === restoreModal) closeRestoreModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && restoreModal.classList.contains('is-open')) closeRestoreModal();
    });
  }

  if (archiveModal && openArchiveBtn && archiveSelect && archiveForm) {
    const openArchiveModal = function () {
      archiveModal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    };

    const closeArchiveModal = function () {
      archiveModal.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    const updateArchiveAction = function () {
      archiveForm.action = "{{ url('/admin/cv-folders') }}/" + archiveSelect.value + "/archive";
    };

    openArchiveBtn.addEventListener('click', function () {
      updateArchiveAction();
      openArchiveModal();
    });

    archiveSelect.addEventListener('change', updateArchiveAction);

    if (closeArchiveBtn) closeArchiveBtn.addEventListener('click', closeArchiveModal);
    if (cancelArchiveBtn) cancelArchiveBtn.addEventListener('click', closeArchiveModal);

    archiveModal.addEventListener('click', function (e) {
      if (e.target === archiveModal) closeArchiveModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && archiveModal.classList.contains('is-open')) closeArchiveModal();
    });
  }

  if (!modal || !openBtn || !select || !form) return;

  function openModal() {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function updateFormAction() {
    const folderId = select.value;
    form.action = "{{ url('/admin/cv-folders') }}/" + folderId;
  }

  openBtn.addEventListener('click', function () {
    updateFormAction();
    openModal();
  });

  select.addEventListener('change', updateFormAction);

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });
})();
</script>
@endif

@endsection
