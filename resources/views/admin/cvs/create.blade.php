@extends('admin.layouts.app')

@section('title', 'Admin – Ajouter des CV')
@section('page_title', 'Ajouter des CV')

@section('page_subtitle')
Ajoutez plusieurs CV, glissez-déposez des fichiers, ou importez un dossier complet.
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.cvs.index') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
    Retour
  </a>
@endsection

@section('content')

  <style>
    .cv-form-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:18px;
    }
    .cv-form-item.full{
      grid-column:1 / -1;
    }
    .cv-input,
    .cv-select,
    .cv-textarea{
      width:100%;
      padding:13px 14px;
      border:1px solid #dbe2ea;
      border-radius:14px;
      background:#fff;
      color:#0f172a;
      font:inherit;
      outline:none;
      transition:border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .cv-input:focus,
    .cv-select:focus,
    .cv-textarea:focus{
      border-color:#94a3b8;
      box-shadow:0 0 0 4px rgba(148,163,184,.14);
    }
    .cv-textarea{
      min-height:120px;
      resize:vertical;
    }
    .upload-zone{
      position:relative;
      border:1.5px dashed #cbd5e1;
      background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%);
      border-radius:18px;
      padding:26px;
      transition:border-color .18s ease, background .18s ease, transform .18s ease;
    }
    .upload-zone.dragover{
      border-color:#2563eb;
      background:#eff6ff;
      transform:translateY(-1px);
    }
    .upload-zone-inner{
      text-align:center;
      max-width:760px;
      margin:0 auto;
    }
    .upload-icon{
      width:56px;
      height:56px;
      margin:0 auto 14px;
      border-radius:16px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#eef2ff;
      color:#2563eb;
    }
    .upload-title{
      margin:0 0 8px;
      font-size:18px;
      font-weight:800;
      color:#0f172a;
    }
    .upload-subtitle{
      margin:0 0 18px;
      color:#64748b;
      line-height:1.55;
    }
    .upload-actions{
      display:flex;
      flex-wrap:wrap;
      justify-content:center;
      gap:12px;
      margin-bottom:14px;
    }
    .upload-hidden{
      display:none;
    }
    .upload-hint{
      color:#64748b;
      font-size:13px;
      margin-top:8px;
    }
    .upload-list{
      margin-top:18px;
      display:grid;
      gap:10px;
    }
    .upload-file{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      padding:12px 14px;
      border:1px solid #e2e8f0;
      border-radius:14px;
      background:#fff;
    }
    .upload-file-name{
      font-weight:700;
      color:#0f172a;
      word-break:break-word;
    }
    .upload-file-meta{
      color:#64748b;
      font-size:13px;
      margin-top:4px;
    }
    .upload-empty{
      padding:14px 16px;
      border-radius:14px;
      background:#f8fafc;
      color:#64748b;
      text-align:center;
      border:1px dashed #e2e8f0;
    }
    .stack-sm{
      display:grid;
      gap:8px;
    }
    .field-help{
      color:#64748b;
      font-size:13px;
      line-height:1.5;
      margin-top:6px;
    }
    .field-error{
      margin-top:8px;
      color:#dc2626;
      font-size:14px;
      font-weight:600;
    }
    .switch-row{
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:center;
    }
    .soft-note{
      padding:12px 14px;
      border-radius:14px;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      color:#475569;
      font-size:14px;
      line-height:1.55;
    }
    @media (max-width: 900px){
      .cv-form-grid{
        grid-template-columns:1fr;
      }
      .cv-form-item.full{
        grid-column:auto;
      }
    }
  </style>

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Importer des CV</div>
    </div>

    <div class="panel-body">
      <form action="{{ route('admin.cvs.store') }}" method="POST" enctype="multipart/form-data" id="cv-upload-form">
        @csrf

        <div class="cv-form-grid">
          <div class="cv-form-item full">
            <div class="info-label">Fichiers CV</div>

            <div class="upload-zone" id="uploadZone">
              <div class="upload-zone-inner">
                <div class="upload-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="28" height="28" fill="none">
                    <path d="M12 16V4m0 0l-4 4m4-4l4 4M5 16v1a2 2 0 002 2h10a2 2 0 002-2v-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>

                <h3 class="upload-title">Glissez-déposez vos CV ici</h3>
                <p class="upload-subtitle">
                  Vous pouvez ajouter plusieurs fichiers à la fois, ou choisir un dossier complet contenant plusieurs CV.
                </p>

                <div class="upload-actions">
                  <button type="button" class="btn btn-primary" id="pickFilesBtn">
                    Choisir des fichiers
                  </button>

                  <button type="button" class="btn btn-ghost" id="pickFolderBtn">
                    Choisir un dossier
                  </button>
                </div>

                <div class="upload-hint">
                  Formats acceptés : PDF, DOC, DOCX, TXT — taille max par fichier : 10 Mo
                </div>
              </div>

              <input
                type="file"
                name="cv_files[]"
                id="cv_files"
                class="upload-hidden"
                accept=".pdf,.doc,.docx,.txt"
                multiple
              >

              <input
                type="file"
                id="cv_folder_picker"
                class="upload-hidden"
                webkitdirectory
                directory
                multiple
              >

              <div id="relative-paths-container"></div>

              <div class="upload-list" id="uploadList">
                <div class="upload-empty">Aucun fichier sélectionné pour le moment.</div>
              </div>

              @error('cv_files')
                <div class="field-error">{{ $message }}</div>
              @enderror
              @error('cv_files.*')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="cv-form-item">
            <div class="info-label">Assigner à un dossier existant</div>
            <select name="cv_folder_id" id="cv_folder_id" class="cv-select">
              <option value="">Aucun dossier</option>
              @foreach(($folders ?? collect()) as $folder)
                <option value="{{ $folder->id }}" {{ old('cv_folder_id') == $folder->id ? 'selected' : '' }}>
                  {{ $folder->name }}
                </option>
              @endforeach
            </select>
            <div class="field-help">
              Sélectionnez un dossier existant si vous voulez y ranger tous les CV importés.
            </div>
            @error('cv_folder_id')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="cv-form-item">
            <div class="info-label">Créer un nouveau dossier</div>
            <input
              type="text"
              name="new_folder_name"
              id="new_folder_name"
              class="cv-input"
              value="{{ old('new_folder_name') }}"
              placeholder="Ex: Casablanca Logistics Avril"
            >
            <div class="field-help">
              Si ce champ est rempli, le dossier sera créé automatiquement et utilisé pour tous les CV envoyés.
            </div>
            @error('new_folder_name')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="cv-form-item full">
            <div class="soft-note">
              Priorité d’assignation du dossier :
              <strong>nouveau dossier</strong> → <strong>dossier existant</strong> → <strong>nom du dossier importé</strong>.
            </div>
          </div>

          <div class="cv-form-item">
            <div class="info-label">Ville</div>
            <input
              type="text"
              name="city"
              id="city"
              class="cv-input"
              value="{{ old('city') }}"
              placeholder="Optionnel"
            >
            <div class="field-help">
              Laissez vide si vous voulez que le système essaie d’extraire la ville depuis le CV.
            </div>
            @error('city')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="cv-form-item">
            <div class="info-label">Poste actuel</div>
            <input
              type="text"
              name="current_title"
              id="current_title"
              class="cv-input"
              value="{{ old('current_title') }}"
              placeholder="Optionnel"
            >
            <div class="field-help">
              Laissez vide si vous voulez que le système essaie d’extraire le poste depuis le CV.
            </div>
            @error('current_title')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="cv-form-item full">
            <div class="info-label">Notes internes</div>
            <textarea
              name="notes"
              id="notes"
              class="cv-textarea"
              placeholder="Notes optionnelles visibles uniquement dans l’admin"
            >{{ old('notes') }}</textarea>
            @error('notes')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="divider"></div>

        <div class="file-actions">
          <button type="submit" class="btn btn-primary">Uploader</button>
          <a href="{{ route('admin.cvs.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function () {
      const fileInput = document.getElementById('cv_files');
      const folderInput = document.getElementById('cv_folder_picker');
      const pickFilesBtn = document.getElementById('pickFilesBtn');
      const pickFolderBtn = document.getElementById('pickFolderBtn');
      const uploadZone = document.getElementById('uploadZone');
      const uploadList = document.getElementById('uploadList');
      const relativePathsContainer = document.getElementById('relative-paths-container');
      const newFolderNameInput = document.getElementById('new_folder_name');

      let currentFiles = [];

      function formatSize(bytes) {
        if (!bytes && bytes !== 0) return '';
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        let size = bytes;
        while (size >= 1024 && i < units.length - 1) {
          size /= 1024;
          i++;
        }
        return `${size.toFixed(size >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
      }

      function isAllowed(file) {
        const name = (file.name || '').toLowerCase();
        return ['.pdf', '.doc', '.docx', '.txt'].some(ext => name.endsWith(ext));
      }

      function dedupeFiles(files) {
        const map = new Map();

        files.forEach(file => {
          const key = [file.name, file.size, file.lastModified].join('__');
          if (!map.has(key)) {
            map.set(key, file);
          }
        });

        return Array.from(map.values());
      }

      function syncInputFiles() {
        const dt = new DataTransfer();
        currentFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
      }

      function syncRelativePaths() {
        relativePathsContainer.innerHTML = '';

        currentFiles.forEach((file, index) => {
          const relativePath = file.webkitRelativePath || '';
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'relative_paths[]';
          input.value = relativePath;
          relativePathsContainer.appendChild(input);
        });
      }

      function maybeSetFolderNameFromUpload() {
        if (newFolderNameInput.value.trim() !== '') {
          return;
        }

        const fileWithPath = currentFiles.find(file => file.webkitRelativePath && file.webkitRelativePath.includes('/'));
        if (!fileWithPath) {
          return;
        }

        const parts = fileWithPath.webkitRelativePath.split('/');
        if (parts.length >= 2 && parts[0].trim() !== '') {
          newFolderNameInput.value = parts[0].trim();
        }
      }

      function renderList() {
        if (!currentFiles.length) {
          uploadList.innerHTML = '<div class="upload-empty">Aucun fichier sélectionné pour le moment.</div>';
          return;
        }

        uploadList.innerHTML = currentFiles.map((file, index) => {
          const relative = file.webkitRelativePath ? `<div class="upload-file-meta">${file.webkitRelativePath}</div>` : '';
          return `
            <div class="upload-file">
              <div>
                <div class="upload-file-name">${escapeHtml(file.name)}</div>
                <div class="upload-file-meta">${formatSize(file.size)}</div>
                ${relative}
              </div>
              <button type="button" class="btn btn-ghost btn-remove-file" data-index="${index}">
                Retirer
              </button>
            </div>
          `;
        }).join('');

        uploadList.querySelectorAll('.btn-remove-file').forEach(btn => {
          btn.addEventListener('click', function () {
            const index = Number(this.getAttribute('data-index'));
            currentFiles.splice(index, 1);
            syncInputFiles();
            syncRelativePaths();
            renderList();
          });
        });
      }

      function addFiles(fileList) {
        const incoming = Array.from(fileList).filter(isAllowed);
        currentFiles = dedupeFiles([...currentFiles, ...incoming]);
        syncInputFiles();
        syncRelativePaths();
        maybeSetFolderNameFromUpload();
        renderList();
      }

      function escapeHtml(value) {
        return String(value)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }

      pickFilesBtn.addEventListener('click', function () {
        fileInput.click();
      });

      pickFolderBtn.addEventListener('click', function () {
        folderInput.click();
      });

      fileInput.addEventListener('change', function (e) {
        addFiles(e.target.files);
      });

      folderInput.addEventListener('change', function (e) {
        addFiles(e.target.files);
      });

      ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          uploadZone.classList.add('dragover');
        });
      });

      ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          uploadZone.classList.remove('dragover');
        });
      });

      uploadZone.addEventListener('drop', function (e) {
        const files = e.dataTransfer.files;
        if (files && files.length) {
          addFiles(files);
        }
      });
    })();
  </script>

@endsection