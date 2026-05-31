@extends('admin.layouts.app')

@section('title', 'Admin - Detail du lot externe')
@section('page_title', 'Lot externe')

@section('page_subtitle')
Consultez les fichiers du lot et suivez leur indexation vers la CV Bank.
@endsection

@section('top_actions')
  <div class="ui-inline-actions">
    <a class="btn btn-ghost" href="{{ route('admin.external-cvs.index') }}">
      <span class="btn-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      Retour
    </a>

    <button type="button" class="btn btn-danger" id="openExternalDeleteBatchModal">
      Supprimer le lot
    </button>
  </div>
@endsection

@section('content')
@php
    $batchStatusLabels = \App\Models\ExternalCvBatch::availableStatuses();
    $fileStatusLabels = [
        'pending' => 'En attente',
        'indexed' => 'Indexe',
        'duplicate' => 'Doublon',
        'failed' => 'Echec',
    ];

    $batchStatusColors = [
        'draft' => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
        'pending' => 'background:#fff7ed;color:#c2410c;border-color:#fdba74;',
        'processing' => 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
        'completed' => 'background:#f0fdf4;color:#15803d;border-color:#86efac;',
        'failed' => 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;',
    ];

    $fileStatusColors = [
        'pending' => 'background:#fff7ed;color:#c2410c;border-color:#fdba74;',
        'indexed' => 'background:#f0fdf4;color:#15803d;border-color:#86efac;',
        'duplicate' => 'background:#fefce8;color:#a16207;border-color:#fde68a;',
        'failed' => 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;',
    ];

    $shouldPoll = in_array($batch->status, [
        \App\Models\ExternalCvBatch::STATUS_PENDING,
        \App\Models\ExternalCvBatch::STATUS_PROCESSING,
    ], true);
@endphp

<div class="panel panel-safe">
  <div class="panel-head">
    <div class="panel-title">
      {{ $batch->name }}
      <span class="panel-badge" id="batch-status-badge" style="{{ $batchStatusColors[$batch->status] ?? '' }}">
        {{ $batchStatusLabels[$batch->status] ?? ucfirst($batch->status) }}
      </span>
    </div>
  </div>

  <div class="panel-body">
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Total fichiers</div>
        <div class="info-value" id="batch-total-files">{{ $batch->total_files }}</div>
      </div>

      <div class="info-item">
        <div class="info-label">Indexes</div>
        <div class="info-value" id="batch-indexed-files">{{ $batch->indexed_files }}</div>
      </div>

      <div class="info-item">
        <div class="info-label">Doublons</div>
        <div class="info-value" id="batch-duplicate-files">{{ $batch->duplicate_files ?? 0 }}</div>
      </div>

      <div class="info-item">
        <div class="info-label">Echecs</div>
        <div class="info-value" id="batch-failed-files">{{ $batch->failed_files }}</div>
      </div>

      <div class="info-item">
        <div class="info-label">Cree par</div>
        <div class="info-value">{{ $batch->creator?->name ?? '-' }}</div>
      </div>

      <div class="info-item">
        <div class="info-label">Dossier CV Bank</div>
        <div class="info-value">{{ $batch->folder?->name ?? '-' }}</div>
      </div>
    </div>

    <div class="ui-progress-card" style="margin-top:18px;">
      <div class="ui-progress-head">
        <strong id="batch-progress-label">Progression de l indexation</strong>
        <span id="batch-progress-text" class="match-score">{{ $batch->progressPercentage() }}%</span>
      </div>
      <div class="ui-progress-track">
        <div id="batch-progress-bar" class="ui-progress-bar" style="width:{{ $batch->progressPercentage() }}%;"></div>
      </div>
      <div class="ui-progress-kpis">
        <span><strong id="batch-progress-indexed">{{ $batch->indexed_files }}</strong> indexes</span>
        <span><strong id="batch-progress-duplicates">{{ $batch->duplicate_files ?? 0 }}</strong> doublons</span>
        <span><strong id="batch-progress-pending">{{ $batch->pendingFilesCount() }}</strong> en attente</span>
        <span><strong id="batch-progress-failed">{{ $batch->failed_files }}</strong> echecs</span>
        <span><strong id="batch-progress-queued">0</strong> jobs queue</span>
        <span>Temps estime : <strong id="batch-progress-eta">Calcul en cours</strong></span>
      </div>
      <div id="batch-progress-subtext" class="ui-progress-copy">
        {{ $batch->pendingFilesCount() > 0 ? 'Le lot est en cours de traitement en arriere-plan.' : 'Aucun traitement en attente.' }}
      </div>
      <div id="batch-progress-error" class="ui-progress-error">
        {{ $batch->processing_error_message }}
      </div>
    </div>

    @if($batch->notes)
      <div class="divider"></div>
      <div class="message-box">
        <div class="message-title">Notes</div>
        <div class="message-content wrap-safe">{{ $batch->notes }}</div>
      </div>
    @endif

    <div class="divider"></div>

    <div class="file-actions">
      <form method="POST" action="{{ route('admin.external-cvs.index-batch', $batch) }}">
        @csrf
        <button class="btn btn-danger" type="submit">
          Reprendre l indexation
        </button>
      </form>

      <form method="POST" action="{{ route('admin.external-cvs.index-batch', $batch) }}">
        @csrf
        <input type="hidden" name="force_reindex" value="1">

        <button
          class="btn btn-ghost"
          type="submit"
          data-rhs-confirm="Reindexer tout le lot ? Cela ecrasera les donnees extraites existantes."
          onclick="return confirm('Reindexer tout le lot ? Cela ecrasera les donnees extraites existantes.')"
        >
          Reindexer ce lot
        </button>
      </form>
    </div>
  </div>
</div>

<div class="panel panel-safe">
  <div class="panel-head">
    <div class="panel-title">Fichiers du lot</div>
  </div>

  <div class="panel-body ui-filter-panel">
    <form method="GET" action="{{ route('admin.external-cvs.show', $batch) }}" class="ui-filter-grid ui-filter-grid--compact">
      <div>
        <label class="ui-label" for="batch-file-search">Recherche</label>
        <input
          id="batch-file-search"
          type="text"
          name="q"
          value="{{ $q ?? '' }}"
          placeholder="Nom fichier, candidat, email..."
        >
      </div>

      <div>
        <label class="ui-label" for="batch-file-status">Statut</label>
        <select name="status" id="batch-file-status">
          <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Tous</option>
          <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>En attente</option>
          <option value="indexed" {{ ($status ?? '') === 'indexed' ? 'selected' : '' }}>Indexe</option>
          <option value="duplicate" {{ ($status ?? '') === 'duplicate' ? 'selected' : '' }}>Doublon</option>
          <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>Echec</option>
        </select>
      </div>

      <div class="table-ctrl-actions">
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.external-cvs.show', $batch) }}" class="btn btn-ghost">Reinitialiser</a>
      </div>
    </form>
  </div>
</div>

<div class="panel panel-safe table-safe">
  <div class="panel-head">
    <div class="panel-title">
      Fichiers
      <span class="panel-badge">{{ $files->total() }}</span>
    </div>
  </div>

  <div class="panel-body" style="padding:0;">
    @if($files->count())
      <div class="table-wrap ui-table-scroll ui-table-sticky">
        <table class="table">
          <thead>
            <tr>
              <th>Fichier</th>
              <th>Candidat</th>
              <th>Email</th>
              <th>Ville</th>
              <th>Poste</th>
              <th>Statut</th>
              <th class="th-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($files as $file)
              <tr>
                <td class="text-safe">{{ $file->original_filename }}</td>
                <td class="text-safe">{{ $file->candidate_name ?? '-' }}</td>
                <td class="text-safe">{{ $file->email ?? '-' }}</td>
                <td class="text-safe">{{ $file->city ?? '-' }}</td>
                <td class="text-safe">{{ $file->current_title ?? '-' }}</td>
                <td>
                  <span class="panel-badge" style="{{ $fileStatusColors[$file->status] ?? '' }}">
                    {{ $fileStatusLabels[$file->status] ?? ucfirst($file->status) }}
                  </span>
                  @if($file->status === 'duplicate' && $file->duplicate_of_cv_id)
                    <div class="ui-table-meta" style="margin-top:8px;">
                      <span>CV rapproche: #{{ $file->duplicate_of_cv_id }}</span>
                    </div>
                  @endif
                </td>
                <td>
                  <div class="td-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ $file->cv ? route('admin.cvs.open', $file->cv) : route('admin.external-cvs.files.open', $file) }}" target="_blank">
                      Ouvrir
                    </a>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
        {{ $files->links() }}
      </div>
    @else
      <div class="ui-empty-state">
        <div class="ui-empty-title">Aucun fichier trouve</div>
        <div class="ui-empty-copy">Les fichiers du lot apparaitront ici apres import ou si les filtres correspondent a des resultats.</div>
      </div>
    @endif
  </div>
</div>

<div class="cv-modal-backdrop" id="externalDeleteBatchModal">
  <div class="cv-modal" role="dialog" aria-modal="true" aria-labelledby="externalDeleteBatchTitle">
    <div class="cv-modal-head">
      <div class="cv-modal-title" id="externalDeleteBatchTitle">Supprimer ce lot externe</div>

      <button type="button" class="cv-modal-close" id="closeExternalDeleteBatchModal" aria-label="Fermer">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <form method="POST" id="externalDeleteBatchForm" action="{{ route('admin.external-cvs.destroy', $batch) }}">
      @csrf
      @method('DELETE')

      <div class="cv-modal-body">
        <div class="cv-modal-text">
          Choisissez si vous voulez supprimer uniquement ce lot, ou supprimer aussi les CV indexes qui lui appartiennent reellement.
        </div>

        <div class="cv-radio-list">
          <div class="cv-radio-card">
            <label>
              <input type="radio" name="delete_mode" value="batch_only" checked>
              <div>
                <div class="cv-radio-title">Supprimer uniquement le lot</div>
                <div class="cv-radio-desc">
                  Le lot externe disparait, mais les CV conserves dans la CV Bank restent consultables.
                </div>
              </div>
            </label>
          </div>

          <div class="cv-radio-card">
            <label>
              <input type="radio" name="delete_mode" value="batch_and_cvs">
              <div>
                <div class="cv-radio-title">Supprimer le lot et ses CV proprietaires</div>
                <div class="cv-radio-desc">
                  Seuls les CV crees par ce lot et non partages ailleurs seront supprimes avec leurs fichiers.
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div class="cv-modal-actions">
        <button type="button" class="btn btn-ghost" id="cancelExternalDeleteBatchModal">Annuler</button>
        <button type="submit" class="btn cv-danger-btn">Confirmer la suppression</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('externalDeleteBatchModal');
  const openBtn = document.getElementById('openExternalDeleteBatchModal');
  const closeBtn = document.getElementById('closeExternalDeleteBatchModal');
  const cancelBtn = document.getElementById('cancelExternalDeleteBatchModal');

  if (modal && openBtn) {
    const closeModal = function () {
      modal.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    openBtn.addEventListener('click', function () {
      modal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  }

  if (!@json($shouldPoll)) {
    return;
  }

  const statusUrl = @json(route('admin.external-cvs.status', $batch));
  const statusBadge = document.getElementById('batch-status-badge');
  const totalNode = document.getElementById('batch-total-files');
  const indexedNode = document.getElementById('batch-indexed-files');
  const duplicateNode = document.getElementById('batch-duplicate-files');
  const failedNode = document.getElementById('batch-failed-files');
  const progressBar = document.getElementById('batch-progress-bar');
  const progressText = document.getElementById('batch-progress-text');
  const progressSubtext = document.getElementById('batch-progress-subtext');
  const progressError = document.getElementById('batch-progress-error');
  const progressIndexed = document.getElementById('batch-progress-indexed');
  const progressDuplicates = document.getElementById('batch-progress-duplicates');
  const progressPending = document.getElementById('batch-progress-pending');
  const progressFailed = document.getElementById('batch-progress-failed');
  const progressQueued = document.getElementById('batch-progress-queued');
  const progressEta = document.getElementById('batch-progress-eta');

  const labels = {
    en_attente: 'En attente',
    en_cours: 'En cours',
    termine: 'Termine',
    echoue: 'Echoue'
  };

  const statusStyles = {
    en_attente: 'background:#fff7ed;color:#c2410c;border-color:#fdba74;',
    en_cours: 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
    termine: 'background:#f0fdf4;color:#15803d;border-color:#86efac;',
    echoue: 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;'
  };

  const poll = async function () {
    try {
      const response = await fetch(statusUrl, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error('Impossible de recuperer le statut du lot.');
      }

      const payload = await response.json();

      if (statusBadge) {
        statusBadge.textContent = labels[payload.status] || payload.status;
        statusBadge.setAttribute('style', statusStyles[payload.status] || '');
      }

      if (totalNode) totalNode.textContent = payload.total_files;
      if (indexedNode) indexedNode.textContent = payload.indexed_files;
      if (duplicateNode) duplicateNode.textContent = payload.duplicate_files || 0;
      if (failedNode) failedNode.textContent = payload.failed_files;
      if (progressBar) progressBar.style.width = payload.progress_percentage + '%';
      if (progressText) progressText.textContent = payload.progress_percentage + '%';
      if (progressIndexed) progressIndexed.textContent = payload.indexed_files || 0;
      if (progressDuplicates) progressDuplicates.textContent = payload.duplicate_files || 0;
      if (progressPending) progressPending.textContent = payload.pending_files || 0;
      if (progressFailed) progressFailed.textContent = payload.failed_files || 0;
      if (progressQueued) progressQueued.textContent = payload.queued_jobs || 0;
      if (progressEta) progressEta.textContent = payload.estimated_time_remaining || 'Calcul en cours';

      if (progressSubtext) {
        progressSubtext.textContent = (payload.status_message || (
          payload.pending_files > 0
            ? 'Le lot est toujours en cours de traitement en arriere-plan.'
            : 'Le traitement du lot est termine.'
        )) + ' Temps estime restant : ' + (payload.estimated_time_remaining || 'Calcul en cours') + '.';
      }

      if (progressError) {
        progressError.textContent = payload.error_message || '';
      }

      if (payload.status === 'en_attente' || payload.status === 'en_cours') {
        window.setTimeout(poll, 5000);
      } else {
        window.setTimeout(function () {
          window.location.reload();
        }, 1200);
      }
    } catch (error) {
      if (progressError) {
        progressError.textContent = error.message;
      }
    }
  };

  window.setTimeout(poll, 3000);
});
</script>
@endpush
