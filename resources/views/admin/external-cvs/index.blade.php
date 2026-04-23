@extends('admin.layouts.app')

@section('title', 'Admin – Base externe')
@section('page_title', 'Base externe')

@section('page_subtitle')
Gérez les lots importés depuis votre source externe et préparez leur indexation vers la CV Bank.
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
      'completed' => 'Terminé',
      'failed' => 'Échoué',
  ];

  $batchStatusColors = [
      'draft' => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
      'pending' => 'background:#fff7ed;color:#c2410c;border-color:#fdba74;',
      'processing' => 'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;',
      'completed' => 'background:#f0fdf4;color:#15803d;border-color:#86efac;',
      'failed' => 'background:#fef2f2;color:#dc2626;border-color:#fca5a5;',
  ];
@endphp

  @if(session('success'))
    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-body">
        <div class="panel-badge" style="background:#e9f9ef;color:#15803d;border-color:#bbf7d0;">
          {{ session('success') }}
        </div>
      </div>
    </div>
  @endif

  @if(session('error'))
    <div class="panel" style="margin-bottom:16px;">
      <div class="panel-body">
        <div class="panel-badge" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">
          {{ session('error') }}
        </div>
      </div>
    </div>
  @endif

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Filtres</div>
    </div>

    <div class="panel-body">
      <form method="GET" action="{{ route('admin.external-cvs.index') }}">
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Recherche</div>
            <div class="info-value">
              <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Nom du lot ou notes..."
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
                <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                <option value="processing" {{ ($status ?? '') === 'processing' ? 'selected' : '' }}>En cours</option>
                <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>Terminé</option>
                <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>Échoué</option>
              </select>
            </div>
          </div>
        </div>

        <div class="divider"></div>

        <div class="file-actions">
          <button type="submit" class="btn btn-primary">Filtrer</button>
          <a href="{{ route('admin.external-cvs.index') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-top:18px;">
    <div class="panel-head">
      <div class="panel-title">
        Lots importés
        <span class="panel-badge">{{ $batches->count() }}</span>
      </div>
    </div>

    <div class="panel-body" style="padding:0;">
      @if($batches->count())
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;">
            <thead style="background:#f8fafc;">
              <tr>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Nom</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Dossier CV</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Statut</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Fichiers</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Indexés</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Erreurs</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Créé par</th>
                <th style="text-align:left;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Créé le</th>
                <th style="text-align:right;padding:14px 16px;border-bottom:1px solid #e5e7eb;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($batches as $batch)
                <tr>
                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <div style="font-weight:700;color:#0f172a;">{{ $batch->name }}</div>
                    @if($batch->notes)
                      <div style="margin-top:4px;font-size:13px;color:#64748b;">
                        {{ $batch->notes }}
                      </div>
                    @endif
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $batch->folder?->name ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <span class="panel-badge" style="{{ $batchStatusColors[$batch->status] ?? '' }}">
                      {{ $batchStatusLabels[$batch->status] ?? ucfirst($batch->status) }}
                    </span>
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $batch->total_files }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $batch->indexed_files }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $batch->failed_files }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ $batch->creator?->name ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    {{ optional($batch->created_at)->format('Y-m-d H:i') ?? '—' }}
                  </td>

                  <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right;">
                    <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                      <a class="btn btn-ghost" href="{{ route('admin.external-cvs.show', $batch) }}">
                        Ouvrir
                      </a>

                      <form method="POST"
                            action="{{ route('admin.external-cvs.destroy', $batch) }}"
                            onsubmit="return confirm('Supprimer ce dossier d’indexation ?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_mode" value="batch_only">
                        <button type="submit" class="btn btn-danger">
                          Supprimer
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div style="padding:34px 24px;text-align:center;">
          <div style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:8px;">
            Aucun lot importé
          </div>
          <div style="color:#64748b;margin-bottom:18px;">
            Commencez par créer un nouveau lot et importer vos CV externes.
          </div>
          <a href="{{ route('admin.external-cvs.create') }}" class="btn btn-primary">
            Nouveau lot
          </a>
        </div>
      @endif
    </div>
  </div>

@endsection