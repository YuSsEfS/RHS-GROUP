@extends('admin.layouts.app')

@section('title', 'Admin – Détail du lot externe')
@section('page_title', 'Lot externe')

@section('page_subtitle')
Consultez les fichiers du lot et lancez leur indexation vers la CV Bank.
@endsection

@section('top_actions')
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a class="btn btn-ghost" href="{{ route('admin.external-cvs.index') }}">
      <span class="btn-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      Retour
    </a>

    <form method="POST"
          action="{{ route('admin.external-cvs.destroy', $batch) }}"
          onsubmit="return confirm('Supprimer ce dossier d’indexation ?')">
      @csrf
      @method('DELETE')
      <input type="hidden" name="delete_mode" value="batch_only">
      <button type="submit" class="btn btn-danger">
        Supprimer le dossier
      </button>
    </form>
  </div>
@endsection

@section('content')

@php
    $batchStatusLabels = [
        'draft' => 'Brouillon',
        'pending' => 'En attente',
        'processing' => 'En cours',
        'completed' => 'Terminé',
        'failed' => 'Échoué',
    ];

    $fileStatusLabels = [
        'pending' => 'En attente',
        'indexed' => 'Indexé',
        'failed' => 'Échec',
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
        'indexed' => 'background:#fef2f2;color:#ef4444;border-color:#f5c2c7;',
        'failed' => 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;',
    ];
@endphp

  @if(session('success'))
    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-body">
        <div style="padding:14px 16px;border-radius:14px;border:1px solid #bbf7d0;background:#e9f9ef;color:#15803d;font-weight:700;">
          {{ session('success') }}
        </div>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-body">
        <div style="padding:14px 16px;border-radius:14px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;font-weight:700;">
          {{ session('error') }}
        </div>
      </div>
    </div>
  @endif

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">
        {{ $batch->name }}
        <span class="panel-badge" style="{{ $batchStatusColors[$batch->status] ?? '' }}">
          {{ $batchStatusLabels[$batch->status] ?? ucfirst($batch->status) }}
        </span>
      </div>
    </div>

    <div class="panel-body">
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Total fichiers</div>
          <div class="info-value">{{ $batch->total_files }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Indexés</div>
          <div class="info-value">{{ $batch->indexed_files }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Échecs</div>
          <div class="info-value">{{ $batch->failed_files }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Créé par</div>
          <div class="info-value">{{ $batch->creator?->name ?? '—' }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Dossier CV Bank</div>
          <div class="info-value">{{ $batch->folder?->name ?? '—' }}</div>
        </div>
      </div>

      @if($batch->notes)
        <div class="divider"></div>
        <div class="message-box">
          <div class="message-title">Notes</div>
          <div class="message-content">{{ $batch->notes }}</div>
        </div>
      @endif

      <div class="divider"></div>

      <div class="file-actions">
        <div style="display:flex;gap:10px;flex-wrap:wrap;">

          <form method="POST" action="{{ route('admin.external-cvs.index-batch', $batch) }}">
            @csrf
            <button class="btn btn-danger" type="submit">
              Indexer ce lot
            </button>
          </form>

          <form method="POST" action="{{ route('admin.external-cvs.index-batch', $batch) }}">
            @csrf
            <input type="hidden" name="force_reindex" value="1">

            <button
              class="btn btn-ghost"
              type="submit"
              onclick="return confirm('Réindexer tout le lot ? Cela écrasera les données extraites existantes.')"
            >
              Réindexer ce lot
            </button>
          </form>

        </div>
      </div>
    </div>
  </div>

  <div class="panel" style="margin-top:18px;">
    <div class="panel-head">
      <div class="panel-title">Fichiers du lot</div>
    </div>

    <div class="panel-body">
      <form method="GET" action="{{ route('admin.external-cvs.show', $batch) }}">
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Recherche</div>
            <div class="info-value">
              <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Nom fichier, candidat, email..."
                style="width:100%;padding:12px 14px;border:1px solid #dbe2ea;border-radius:12px;background:#fff;"
              >
            </div>
          </div>

          <div class="info-item">
            <div class="info-label">Statut</div>
            <div class="info-value">
              <select
                name="status"
                style="width:100%;padding:12px 14px;border:1px solid #dbe2ea;border-radius:12px;background:#fff;"
              >
                <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Tous</option>
                <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="indexed" {{ ($status ?? '') === 'indexed' ? 'selected' : '' }}>Indexé</option>
                <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>Échec</option>
              </select>
            </div>
          </div>
        </div>

        <div class="divider"></div>

        <div class="file-actions">
          <button type="submit" class="btn btn-primary">Filtrer</button>
          <a href="{{ route('admin.external-cvs.show', $batch) }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-top:18px;">
    <div class="panel-head">
      <div class="panel-title">
        Fichiers
        <span class="panel-badge">{{ $files->count() }}</span>
      </div>
    </div>

    <div class="panel-body" style="padding:0;">
      @if($files->count())
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;">
            <thead style="background:#f8fafc;">
              <tr>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Fichier</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Candidat</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Email</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Ville</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Poste</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Statut</th>
                <th style="text-align:right;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($files as $file)
                <tr>
                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $file->original_filename }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $file->candidate_name ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $file->email ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $file->city ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $file->current_title ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <span class="panel-badge" style="{{ $fileStatusColors[$file->status] ?? '' }}">
                      {{ $fileStatusLabels[$file->status] ?? ucfirst($file->status) }}
                    </span>
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right;">
                    <a class="btn btn-ghost" href="{{ route('admin.external-cvs.files.open', $file) }}" target="_blank">
                      Ouvrir
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div style="padding:34px 24px;text-align:center;">
          <div style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:8px;">
            Aucun fichier trouvé
          </div>
        </div>
      @endif
    </div>
  </div>

@endsection