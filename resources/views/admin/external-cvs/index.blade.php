@extends('admin.layouts.app')

@section('title', 'Admin - Base externe')
@section('page_title', 'Base externe')

@section('page_subtitle')
Gerez les lots importes depuis votre source externe et preparez leur indexation vers la CV Bank.
@endsection

@section('top_actions')
  <a class="btn btn-primary" href="{{ route('admin.external-cvs.create') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </span>
    Nouveau lot
  </a>
@endsection

@section('content')
@php
  $batchStatusLabels = [
      'draft' => 'Brouillon',
      'pending' => 'En attente',
      'processing' => 'En cours',
      'completed' => 'Termine',
      'failed' => 'Echoue',
  ];

  $batchStatusColors = [
      'draft' => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
      'pending' => 'background:#fff7ed;color:#c2410c;border-color:#fdba74;',
      'processing' => 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
      'completed' => 'background:#f0fdf4;color:#15803d;border-color:#86efac;',
      'failed' => 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;',
  ];
@endphp

<div class="panel panel-safe">
  <div class="panel-head">
    <div class="panel-title">Filtres</div>
  </div>

  <div class="panel-body ui-filter-panel">
    <form method="GET" action="{{ route('admin.external-cvs.index') }}" class="ui-filter-grid ui-filter-grid--compact">
      <div>
        <label class="ui-label" for="external-search">Recherche</label>
        <input id="external-search" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nom du lot ou notes...">
      </div>

      <div>
        <label class="ui-label" for="external-status">Statut</label>
        <select id="external-status" name="status">
          <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Tous</option>
          <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Brouillon</option>
          <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>En attente</option>
          <option value="processing" {{ ($status ?? '') === 'processing' ? 'selected' : '' }}>En cours</option>
          <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>Termine</option>
          <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>Echoue</option>
        </select>
      </div>

      <div class="table-ctrl-actions">
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.external-cvs.index') }}" class="btn btn-ghost">Reinitialiser</a>
      </div>
    </form>
  </div>
</div>

<div class="panel panel-safe table-safe">
  <div class="panel-head">
    <div class="panel-title">
      Lots importes
      <span class="panel-badge">{{ $batches->total() }}</span>
    </div>
  </div>

  <div class="panel-body" style="padding:0;">
    @if($batches->count())
      <div class="table-wrap ui-table-scroll ui-table-sticky">
        <table class="table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Dossier CV</th>
              <th>Statut</th>
              <th>Fichiers</th>
              <th>Indexes</th>
              <th>Doublons</th>
              <th>Echecs</th>
              <th>Cree par</th>
              <th>Cree le</th>
              <th class="th-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($batches as $batch)
              <tr>
                <td>
                  <div class="ui-table-meta">
                    <strong>{{ $batch->name }}</strong>
                    @if($batch->notes)
                      <span class="wrap-safe">{{ $batch->notes }}</span>
                    @endif
                  </div>
                </td>

                <td class="text-safe">{{ $batch->folder?->name ?? '-' }}</td>

                <td>
                  <span class="panel-badge" style="{{ $batchStatusColors[$batch->status] ?? '' }}">
                    {{ $batchStatusLabels[$batch->status] ?? ucfirst($batch->status) }}
                  </span>
                </td>

                <td>{{ $batch->total_files }}</td>
                <td>{{ $batch->indexed_files }}</td>
                <td>{{ $batch->duplicate_files ?? 0 }}</td>
                <td>{{ $batch->failed_files }}</td>
                <td class="text-safe">{{ $batch->creator?->name ?? '-' }}</td>
                <td>{{ optional($batch->created_at)->format('Y-m-d H:i') ?? '-' }}</td>

                <td>
                  <div class="td-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ route('admin.external-cvs.show', $batch) }}">
                      Ouvrir
                    </a>

                    <button
                      type="button"
                      class="btn btn-danger btn-sm js-open-external-delete-modal"
                      data-action="{{ route('admin.external-cvs.destroy', $batch) }}"
                      data-batch-name="{{ $batch->name }}"
                    >
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
        {{ $batches->links() }}
      </div>
    @else
      <div class="ui-empty-state">
        <div class="ui-empty-title">Aucun lot importe</div>
        <div class="ui-empty-copy">
          Commencez par creer un nouveau lot et importer vos CV externes.
        </div>
        <div class="ui-inline-actions" style="justify-content:center; margin-top:18px;">
          <a href="{{ route('admin.external-cvs.create') }}" class="btn btn-primary">
            Nouveau lot
          </a>
        </div>
      </div>
    @endif
  </div>
</div>

<div class="cv-modal-backdrop" id="externalDeleteBatchModal">
  <div class="cv-modal" role="dialog" aria-modal="true" aria-labelledby="externalDeleteBatchTitle">
    <div class="cv-modal-head">
      <div class="cv-modal-title" id="externalDeleteBatchTitle">Supprimer un lot externe</div>

      <button type="button" class="cv-modal-close" id="closeExternalDeleteBatchModal" aria-label="Fermer">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <form method="POST" id="externalDeleteBatchForm">
      @csrf
      @method('DELETE')

      <div class="cv-modal-body">
        <div class="cv-modal-text">
          <strong id="externalDeleteBatchName">Lot externe</strong><br>
          Choisissez si vous voulez supprimer uniquement le lot, ou supprimer aussi les CV indexes qui lui appartiennent reellement.
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
  const form = document.getElementById('externalDeleteBatchForm');
  const title = document.getElementById('externalDeleteBatchName');
  const openButtons = document.querySelectorAll('.js-open-external-delete-modal');
  const closeBtn = document.getElementById('closeExternalDeleteBatchModal');
  const cancelBtn = document.getElementById('cancelExternalDeleteBatchModal');

  if (!modal || !form) {
    return;
  }

  function openModal(action, batchName) {
    form.action = action;
    if (title) {
      title.textContent = batchName || 'Lot externe';
    }
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  openButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      openModal(button.dataset.action, button.dataset.batchName);
    });
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

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
});
</script>
@endpush
