@extends('admin.layouts.app')

@section('title', 'Admin – CV Bank')
@section('page_title', 'CV Bank')

@section('page_subtitle')
Gérez tous les CV provenant des candidatures, des ajouts manuels et de la base externe.
@endsection

@section('top_actions')
  <a class="btn btn-primary" href="{{ route('admin.cvs.create') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </span>
    Ajouter des CV
  </a>
@endsection

@section('content')

  <style>
    .cv-page{
      display:grid;
      gap:18px;
    }

    .cv-filters-sticky{
      position:sticky;
      top:16px;
      z-index:15;
    }

    .cv-filters-panel{
      overflow:hidden;
    }

    .cv-filters-grid{
      display:grid;
      grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr 1fr;
      gap:16px;
      align-items:end;
    }

    .cv-input,
    .cv-select{
      width:100%;
      height:48px;
      padding:0 14px;
      border:1px solid #dbe2ea;
      border-radius:14px;
      background:#fff;
      color:#0f172a;
      font:inherit;
      outline:none;
      transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .cv-input:focus,
    .cv-select:focus{
      border-color:#94a3b8;
      box-shadow:0 0 0 4px rgba(148,163,184,.14);
    }

    .cv-inline-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      flex-wrap:wrap;
      margin-bottom:16px;
    }

    .cv-inline-form{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
    }

    .cv-inline-form .cv-input{
      min-width:280px;
    }

    .cv-note{
      padding:12px 14px;
      border-radius:14px;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      color:#475569;
      line-height:1.55;
    }

    .cv-table-panel{
      overflow:hidden;
    }

    .cv-table-wrap{
      width:100%;
      overflow-x:auto;
      overflow-y:visible;
      border-top:1px solid #eef2f7;
      scrollbar-width:thin;
    }

    .cv-table-wrap::-webkit-scrollbar{
      height:10px;
    }

    .cv-table-wrap::-webkit-scrollbar-track{
      background:#edf2f7;
      border-radius:999px;
    }

    .cv-table-wrap::-webkit-scrollbar-thumb{
      background:#cbd5e1;
      border-radius:999px;
    }

    .cv-table{
      width:100%;
      min-width:1360px;
      border-collapse:separate;
      border-spacing:0;
    }

    .cv-table th{
      text-align:left;
      padding:14px 16px;
      background:#f8fafc;
      color:#334155;
      font-size:13px;
      font-weight:800;
      letter-spacing:.02em;
      white-space:nowrap;
      border-bottom:1px solid #e5e7eb;
      position:sticky;
      top:0;
      z-index:5;
    }

    .cv-table td{
      padding:16px;
      border-bottom:1px solid #f1f5f9;
      vertical-align:middle;
      color:#0f172a;
      background:#fff;
    }

    .cv-table tr:hover td{
      background:#fcfdff;
    }

    .cv-main{
      font-weight:800;
      color:#0f172a;
      line-height:1.35;
    }

    .cv-sub{
      margin-top:4px;
      color:#64748b;
      font-size:13px;
      line-height:1.45;
    }

    .cv-file-name{
      max-width:260px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    .cv-badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid #dbe2ea;
      background:#f8fafc;
      color:#334155;
      font-size:12px;
      font-weight:700;
      white-space:nowrap;
    }

    .cv-badge.source-application{
      background:#eff6ff;
      color:#1d4ed8;
      border-color:#bfdbfe;
    }

    .cv-badge.source-external{
      background:#f8fafc;
      color:#0f172a;
      border-color:#cbd5e1;
    }

    .cv-badge.source-manual{
      background:#ecfdf5;
      color:#15803d;
      border-color:#bbf7d0;
    }

    .cv-badge.source-legacy{
      background:#fff7ed;
      color:#c2410c;
      border-color:#fed7aa;
    }

    .cv-badge.status-inactive{
      background:#fef2f2;
      color:#b91c1c;
      border-color:#fecaca;
    }

    .cv-actions{
      display:flex;
      justify-content:flex-end;
      align-items:center;
      gap:10px;
      min-width:220px;
    }

    .cv-icon-btn{
      width:42px;
      height:42px;
      border-radius:12px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border:1px solid #dbe2ea;
      background:#fff;
      color:#0f172a;
      transition:.18s ease;
      cursor:pointer;
      text-decoration:none;
      flex:0 0 auto;
    }

    .cv-icon-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 10px 24px rgba(15,23,42,.08);
    }

    .cv-icon-btn.primary{
      background:#ef4444;
      border-color:#ef4444;
      color:#fff;
    }

    .cv-icon-btn.primary:hover{
      background:#dc2626;
      border-color:#dc2626;
    }

    .cv-icon-btn.warn{
      background:#fff7ed;
      border-color:#fed7aa;
      color:#c2410c;
    }

    .cv-icon-btn.danger{
      background:#fff;
      border-color:#fecaca;
      color:#dc2626;
    }

    .cv-folder-assign{
      display:flex;
      align-items:center;
      gap:8px;
      flex:0 0 auto;
    }

    .cv-folder-assign select{
      min-width:160px;
      max-width:180px;
      height:42px;
      padding:0 12px;
      border:1px solid #dbe2ea;
      border-radius:10px;
      background:#fff;
      font:inherit;
      color:#0f172a;
    }

    .cv-empty{
      padding:36px 24px;
      text-align:center;
    }

    .cv-empty-title{
      font-size:18px;
      font-weight:800;
      color:#0f172a;
      margin-bottom:8px;
    }

    .cv-empty-subtitle{
      color:#64748b;
      margin-bottom:18px;
      line-height:1.6;
    }

    .panel-alert{
      padding:14px 16px;
      border-radius:14px;
      font-weight:600;
    }

    .panel-alert.success{
      background:#ecfdf5;
      color:#166534;
      border:1px solid #bbf7d0;
    }

    .panel-alert.error{
      background:#fef2f2;
      color:#991b1b;
      border:1px solid #fecaca;
    }

    @media (max-width: 1500px){
      .cv-filters-grid{
        grid-template-columns:repeat(4, minmax(0, 1fr));
      }
    }

    @media (max-width: 1100px){
      .cv-filters-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 760px){
      .cv-filters-grid{
        grid-template-columns:1fr;
      }

      .cv-inline-row{
        flex-direction:column;
        align-items:stretch;
      }

      .cv-inline-form{
        width:100%;
      }

      .cv-inline-form .cv-input{
        min-width:0;
        width:100%;
      }

      .cv-actions{
        min-width:unset;
      }
    }
  </style>

  <div class="cv-page">

    @if(session('success'))
      <div class="panel">
        <div class="panel-body">
          <div class="panel-alert success">{{ session('success') }}</div>
        </div>
      </div>
    @endif

    @if(session('error'))
      <div class="panel">
        <div class="panel-body">
          <div class="panel-alert error">{{ session('error') }}</div>
        </div>
      </div>
    @endif

    <div class="cv-filters-sticky">
      <div class="panel cv-filters-panel">
        <div class="panel-head">
          <div class="panel-title">Filtres et tri</div>
        </div>

        <div class="panel-body">
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
      placeholder="Créer un nouveau dossier"
      value="{{ old('name') }}"
    >
    <button type="submit" class="btn btn-ghost">Créer dossier</button>
    @if(($folders ?? collect())->count())
    <button type="button" class="btn btn-ghost" id="openDeleteFolderModal">
      Supprimer dossier
    </button>
  @endif
  </form>

</div>
          </div>

          <form method="GET" action="{{ route('admin.cvs.index') }}">
            <div class="cv-filters-grid">
              <div>
                <div class="info-label">Recherche</div>
                <input
                  type="text"
                  name="q"
                  value="{{ $q ?? '' }}"
                  placeholder="Nom, email, téléphone, ville, poste..."
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
                  <option value="spontaneous" {{ ($offer ?? '') === 'spontaneous' ? 'selected' : '' }}>Spontanée</option>
                  @foreach(($offers ?? collect()) as $item)
                    <option value="{{ $item->id }}" {{ (string)($offer ?? '') === (string)$item->id ? 'selected' : '' }}>
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
                    <option value="{{ $item->id }}" {{ (string)($folder ?? '') === (string)$item->id ? 'selected' : '' }}>
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
                  <option value="desc" {{ ($direction ?? 'desc') === 'desc' ? 'selected' : '' }}>Décroissant</option>
                  <option value="asc" {{ ($direction ?? '') === 'asc' ? 'selected' : '' }}>Croissant</option>
                </select>
              </div>

              <div>
                <div class="info-label">&nbsp;</div>
                <div class="file-actions" style="justify-content:flex-start;">
                  <button type="submit" class="btn btn-primary">Appliquer</button>
                  <a href="{{ route('admin.cvs.index') }}" class="btn btn-ghost">Réinitialiser</a>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="panel cv-table-panel">
      <div class="panel-head">
        <div class="panel-title">
          Liste des CV
          <span class="panel-badge">{{ isset($cvs) ? $cvs->count() : 0 }}</span>
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
                  <th>Fichier</th>
                  <th>Ajouté le</th>
                  <th style="text-align:right;">Actions</th>
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

                    $sourceLabel = match($resolvedSource) {
                      'application' => 'Candidature',
                      'external_db' => 'Base externe',
                      'manual' => 'Manuel',
                      'legacy_application' => 'Ancienne candidature',
                      default => 'Inconnu',
                    };

                    $sourceClass = match($resolvedSource) {
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
                      ?? '—';

                    $displayCity = $cv->city
                      ?? data_get($cv->structured_profile, 'city')
                      ?? data_get($cv->structured_profile, 'location.city')
                      ?? data_get($cv->structured_profile, 'address.city')
                      ?? '—';
                  @endphp

                  <tr>
                    <td>
                      <div class="cv-main">{{ $cv->candidate_name ?: '—' }}</div>
                    </td>

                    <td>
                      <div>{{ $cv->email ?: '—' }}</div>
                      <div class="cv-sub">{{ $cv->phone ?: '—' }}</div>
                    </td>

                    <td>{{ $displayTitle }}</td>

                    <td>{{ $displayCity }}</td>

                    <td>
                      <span class="cv-badge {{ $sourceClass }}">
                        {{ $sourceLabel }}
                      </span>

                      @if(isset($cv->is_active) && !$cv->is_active)
                        <div style="margin-top:8px;">
                          <span class="cv-badge status-inactive">Inactif</span>
                        </div>
                      @endif
                    </td>

                    <td>
                      {{ $cv->folder?->name ?? '—' }}
                    </td>

                    <td>
                      <div class="cv-file-name" title="{{ $cv->original_filename }}">
                        {{ $cv->original_filename ?: '—' }}
                      </div>
                    </td>

                    <td>
                      {{ optional($cv->uploaded_at)->format('Y-m-d H:i') ?? optional($cv->created_at)->format('Y-m-d H:i') ?? '—' }}
                    </td>

                    <td>
                      <div class="cv-actions">
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
                          <select name="cv_folder_id" title="Assigner à un dossier">
                            <option value="">Aucun</option>
                            @foreach(($folders ?? collect()) as $item)
                              <option value="{{ $item->id }}" {{ (string)$cv->cv_folder_id === (string)$item->id ? 'selected' : '' }}>
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

                        <form
                          action="{{ route('admin.cvs.destroy', $cv) }}"
                          method="POST"
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
        @else
          <div class="cv-empty">
            <div class="cv-empty-title">Aucun CV trouvé</div>
            <div class="cv-empty-subtitle">
              Votre CV Bank est vide pour le moment, ou aucun résultat ne correspond aux filtres sélectionnés.
            </div>
            <a href="{{ route('admin.cvs.create') }}" class="btn btn-primary">
              Ajouter des CV
            </a>
          </div>
        @endif
      </div>
    </div>

  </div>
<style>
  .cv-modal-backdrop{
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
  }

  .cv-modal-backdrop.is-open{
    display: flex;
  }

  .cv-modal{
    width: 100%;
    max-width: 560px;
    background: #fff;
    border-radius: 22px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22);
    overflow: hidden;
  }

  .cv-modal-head{
    padding: 20px 22px;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .cv-modal-title{
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
  }

  .cv-modal-close{
    width: 40px;
    height: 40px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .cv-modal-body{
    padding: 22px;
    display: grid;
    gap: 18px;
  }

  .cv-modal-text{
    color: #475569;
    line-height: 1.65;
  }

  .cv-radio-list{
    display: grid;
    gap: 12px;
  }

  .cv-radio-card{
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 16px;
    background: #fff;
    transition: .18s ease;
  }

  .cv-radio-card:hover{
    border-color: #fca5a5;
    background: #fffafa;
  }

  .cv-radio-card label{
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
  }

  .cv-radio-card input[type="radio"]{
    margin-top: 4px;
    accent-color: #ef4444;
    transform: scale(1.1);
  }

  .cv-radio-title{
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
  }

  .cv-radio-desc{
    color: #64748b;
    font-size: 14px;
    line-height: 1.55;
  }

  .cv-modal-actions{
    padding: 20px 22px;
    border-top: 1px solid #eef2f7;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
  }

  .cv-danger-btn{
    background: #ef4444 !important;
    border-color: #ef4444 !important;
    color: #fff !important;
  }

  .cv-danger-btn:hover{
    background: #dc2626 !important;
    border-color: #dc2626 !important;
  }
</style>

@if(($folders ?? collect())->count())
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
                    Le dossier sera supprimé, mais les CV resteront dans la CV Bank et seront simplement désassignés.
                  </div>
                </div>
              </label>
            </div>

            <div class="cv-radio-card">
              <label>
                <input type="radio" name="delete_mode" value="folder_and_files">
                <div>
                  <div class="cv-radio-title">Supprimer le dossier et les fichiers associés</div>
                  <div class="cv-radio-desc">
                    Le dossier sera supprimé ainsi que tous les CV qui lui sont assignés.
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

  <script>
    (function () {
      const modal = document.getElementById('deleteFolderModal');
      const openBtn = document.getElementById('openDeleteFolderModal');
      const closeBtn = document.getElementById('closeDeleteFolderModal');
      const cancelBtn = document.getElementById('cancelDeleteFolderModal');
      const select = document.getElementById('folderToDelete');
      const form = document.getElementById('deleteFolderForm');

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

      if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
      }

      if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
      }

      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          closeModal();
        }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
          closeModal();
        }
      });
    })();
  </script>
@endif
  @endsection