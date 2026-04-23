@extends('admin.layouts.app')

@section('title', 'Admin – Nouveau lot externe')
@section('page_title', 'Nouveau lot externe')

@section('page_subtitle')
Importez plusieurs CV dans un lot externe avant de lancer l’indexation.
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.external-cvs.index') }}">
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
  .external-form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
  }

  .external-form-item.full{
    grid-column:1 / -1;
  }

  .external-input,
  .external-select,
  .external-textarea{
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

  .external-input,
  .external-select{
    height:48px;
  }

  .external-input:focus,
  .external-select:focus,
  .external-textarea:focus{
    border-color:#94a3b8;
    box-shadow:0 0 0 4px rgba(148,163,184,.14);
  }

  .external-textarea{
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
    border-color:#ef4444;
    background:#fff5f5;
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
    background:#fef2f2;
    color:#ef4444;
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

    /* max 4 rows visible */
    max-height:360px;
    overflow-y:auto;
    overflow-x:hidden;
    padding-right:8px;
    scroll-behavior:smooth;
  }

  .upload-list::-webkit-scrollbar{
    width:8px;
  }

  .upload-list::-webkit-scrollbar-track{
    background:#f1f5f9;
    border-radius:999px;
  }

  .upload-list::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
  }

  .upload-list::-webkit-scrollbar-thumb:hover{
    background:#94a3b8;
  }

  .upload-file{
    min-height:78px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:12px 14px;
    border:1px solid #e2e8f0;
    border-radius:14px;
    background:#fff;
  }

  .upload-file-info{
    min-width:0;
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
    word-break:break-word;
  }

  .upload-empty{
    padding:14px 16px;
    border-radius:14px;
    background:#f8fafc;
    color:#64748b;
    text-align:center;
    border:1px dashed #e2e8f0;
  }

  .upload-summary{
    margin-top:14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:10px 14px;
    border-radius:14px;
    background:#fff;
    border:1px solid #e2e8f0;
    color:#475569;
    font-size:14px;
  }

  .upload-summary strong{
    color:#0f172a;
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
    .external-form-grid{
      grid-template-columns:1fr;
    }

    .external-form-item.full{
      grid-column:auto;
    }

    .upload-list{
      max-height:340px;
    }
  }
</style>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">Créer un lot externe</div>
  </div>

  <div class="panel-body">
    <form action="{{ route('admin.external-cvs.store') }}" method="POST" enctype="multipart/form-data" id="external-upload-form">
      @csrf

      <div class="external-form-grid">
        <div class="external-form-item">
          <div class="info-label">Nom du lot</div>
          <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            placeholder="Ex: Export Avril 2026"
            class="external-input"
            id="batch_name"
          >
          <div class="field-help">
            Si vous importez un dossier complet, son nom pourra être repris automatiquement.
          </div>
          @error('name')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="external-form-item">
          <div class="info-label">Dossier CV Bank</div>
          <select name="cv_folder_id" id="cv_folder_id" class="external-select">
            <option value="">Aucun — créer automatiquement</option>
            @foreach(($folders ?? collect()) as $folder)
              <option value="{{ $folder->id }}" {{ old('cv_folder_id') == $folder->id ? 'selected' : '' }}>
                {{ $folder->name }}
              </option>
            @endforeach
          </select>
          <div class="field-help">
            Tous les CV indexés de ce lot seront affectés à ce dossier dans la CV Bank.
          </div>
          @error('cv_folder_id')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="external-form-item full">
          <div class="info-label">Notes</div>
          <textarea
            name="notes"
            rows="4"
            placeholder="Optionnel"
            class="external-textarea"
          >{{ old('notes') }}</textarea>
          @error('notes')
            <div class="field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="external-form-item full">
          <div class="soft-note">
            Vous pouvez importer plusieurs CV à la fois, glisser-déposer vos fichiers ou sélectionner un dossier complet contenant plusieurs CV.
          </div>
        </div>

        <div class="external-form-item full">
          <div class="info-label">Fichiers CV</div>

          <div class="upload-zone" id="uploadZone">
            <div class="upload-zone-inner">
              <div class="upload-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none">
                  <path d="M12 16V4m0 0l-4 4m4-4l4 4M5 16v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>

              <h3 class="upload-title">Glissez-déposez vos CV ici</h3>
              <p class="upload-subtitle">
                Importez plusieurs fichiers à la fois ou choisissez directement un dossier contenant tous les CV.
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
              required
            >

            <input
              type="file"
              id="cv_folder_picker"
              class="upload-hidden"
              webkitdirectory
              directory
              multiple
            >

            <div class="upload-summary" id="uploadSummary" style="display:none;">
              <div><strong id="uploadCount">0</strong> fichier(s) sélectionné(s)</div>
              <div>Total : <strong id="uploadTotalSize">0 KB</strong></div>
            </div>

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
      </div>

      <div class="divider"></div>

      <div class="file-actions">
        <button type="submit" class="btn btn-primary">Importer le lot</button>
        <a href="{{ route('admin.external-cvs.index') }}" class="btn btn-ghost">Annuler</a>
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
    const uploadSummary = document.getElementById('uploadSummary');
    const uploadCount = document.getElementById('uploadCount');
    const uploadTotalSize = document.getElementById('uploadTotalSize');
    const batchNameInput = document.getElementById('batch_name');

    let currentFiles = [];

    function formatSize(bytes) {
      const units = ['B', 'KB', 'MB', 'GB'];
      let i = 0;
      let size = bytes || 0;

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
        const relativePath = file.webkitRelativePath || '';
        const key = [relativePath, file.name, file.size, file.lastModified].join('__');

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

    function maybeSetBatchNameFromFolder() {
      if (!batchNameInput || batchNameInput.value.trim() !== '') {
        return;
      }

      const fileWithPath = currentFiles.find(file => file.webkitRelativePath && file.webkitRelativePath.includes('/'));

      if (!fileWithPath) {
        return;
      }

      const parts = fileWithPath.webkitRelativePath.split('/');

      if (parts.length >= 2 && parts[0].trim() !== '') {
        batchNameInput.value = parts[0].trim();
      }
    }

    function updateSummary() {
      if (!currentFiles.length) {
        uploadSummary.style.display = 'none';
        uploadCount.textContent = '0';
        uploadTotalSize.textContent = '0 KB';
        return;
      }

      const total = currentFiles.reduce((sum, file) => sum + (file.size || 0), 0);

      uploadSummary.style.display = 'flex';
      uploadCount.textContent = currentFiles.length;
      uploadTotalSize.textContent = formatSize(total);
    }

    function renderList() {
      updateSummary();

      if (!currentFiles.length) {
        uploadList.innerHTML = '<div class="upload-empty">Aucun fichier sélectionné pour le moment.</div>';
        return;
      }

      uploadList.innerHTML = currentFiles.map((file, index) => {
        const relative = file.webkitRelativePath
          ? `<div class="upload-file-meta">${escapeHtml(file.webkitRelativePath)}</div>`
          : '';

        return `
          <div class="upload-file">
            <div class="upload-file-info">
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
          renderList();
        });
      });
    }

    function addFiles(fileList) {
      const incoming = Array.from(fileList).filter(isAllowed);

      currentFiles = dedupeFiles([...currentFiles, ...incoming]);

      syncInputFiles();
      maybeSetBatchNameFromFolder();
      renderList();

      uploadList.scrollTop = uploadList.scrollHeight;
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
      fileInput.value = '';
    });

    folderInput.addEventListener('change', function (e) {
      addFiles(e.target.files);
      folderInput.value = '';
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('external-upload-form');
    const fileInput = document.getElementById('cv_files');
    const folderInput = document.getElementById('cv_folder_picker');

    if (!form || !fileInput) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    const chunkSize = 20;

    const progressBox = document.createElement('div');
    progressBox.style.marginTop = '18px';
    progressBox.style.padding = '14px';
    progressBox.style.border = '1px solid #e2e8f0';
    progressBox.style.borderRadius = '14px';
    progressBox.style.background = '#f8fafc';
    progressBox.style.display = 'none';

    progressBox.innerHTML = `
        <strong id="chunkProgressText">Préparation...</strong>
        <div style="height:10px;background:#e5e7eb;border-radius:999px;margin-top:10px;overflow:hidden;">
            <div id="chunkProgressBar" style="height:100%;width:0%;background:#ef4444;border-radius:999px;"></div>
        </div>
    `;

    form.appendChild(progressBox);

    const progressText = document.getElementById('chunkProgressText');
    const progressBar = document.getElementById('chunkProgressBar');

    function chunkArray(array, size) {
        const chunks = [];

        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }

        return chunks;
    }

    form.addEventListener('submit', async function (e) {
        const files = Array.from(fileInput.files || []);

        if (files.length <= chunkSize) {
            return;
        }

        e.preventDefault();

        const csrf = form.querySelector('input[name="_token"]').value;
        const chunks = chunkArray(files, chunkSize);

        let batchId = null;
        let uploaded = 0;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Importation en cours...';
        progressBox.style.display = 'block';

        try {
            for (let i = 0; i < chunks.length; i++) {
                const formData = new FormData();

                formData.append('_token', csrf);
                formData.append('name', form.querySelector('[name="name"]')?.value || '');
                formData.append('notes', form.querySelector('[name="notes"]')?.value || '');
                formData.append('cv_folder_id', form.querySelector('[name="cv_folder_id"]')?.value || '');
                formData.append('chunk_index', i);
                formData.append('total_chunks', chunks.length);
                formData.append('total_files', files.length);

                if (batchId) {
                    formData.append('batch_id', batchId);
                }

                chunks[i].forEach(file => {
                    formData.append('cv_files[]', file);
                });

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Erreur pendant le chunk ' + (i + 1));
                }

                const result = await response.json();

                if (!batchId) {
                    batchId = result.batch_id;
                }

                uploaded += chunks[i].length;

                const percent = Math.round((uploaded / files.length) * 100);

                progressText.textContent = `Importation ${uploaded}/${files.length} CV...`;
                progressBar.style.width = percent + '%';

                if (i === chunks.length - 1) {
                    progressText.textContent = 'Importation terminée. Redirection...';
                    progressBar.style.width = '100%';

                    window.location.href = result.redirect_url;
                }
            }
        } catch (error) {
            alert(error.message);

            submitBtn.disabled = false;
            submitBtn.textContent = 'Importer le lot';

            progressText.textContent = 'Erreur pendant l’importation.';
        }
    });
});
</script>

@endsection