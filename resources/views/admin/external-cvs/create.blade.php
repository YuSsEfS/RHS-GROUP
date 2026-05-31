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
                Formats acceptes : PDF, DOC, DOCX, TXT - taille max par fichier : 50 Mo
              </div>
              <div class="upload-large-note">
                Mode grand volume : envoi par lots de 20 fichiers, affichage limite pour garder le navigateur fluide.
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

            <div class="upload-summary" id="uploadSummary" style="display:none;">
              <div><strong id="uploadCount">0</strong> fichier(s) sélectionné(s)</div>
              <div>Total : <strong id="uploadTotalSize">0 KB</strong></div>
              <button type="button" class="btn btn-ghost btn-sm" id="clearUploadSelection">Vider</button>
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
    const clearUploadSelection = document.getElementById('clearUploadSelection');
    const batchNameInput = document.getElementById('batch_name');

    const MAX_FILES = 50000;
    const MAX_FILE_SIZE = 50 * 1024 * 1024;
    const MAX_VISIBLE_FILES = 200;

    let currentFiles = [];
    let selectionMessage = '';

    window.rhsExternalUploadFiles = function () {
      return currentFiles;
    };

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

    function isAllowedSize(file) {
      return (file.size || 0) <= MAX_FILE_SIZE;
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
      // Do not build a native DataTransfer with thousands of files.
      // The chunk uploader reads currentFiles directly and keeps the browser responsive.
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

      const visibleFiles = currentFiles.slice(0, MAX_VISIBLE_FILES);
      const hiddenCount = Math.max(0, currentFiles.length - visibleFiles.length);

      uploadList.innerHTML = visibleFiles.map((file, index) => {
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
      }).join('')
        + (hiddenCount > 0
          ? `<div class="upload-more">+ ${hiddenCount} fichier(s) non affiches pour garder la page fluide.</div>`
          : '')
        + (selectionMessage
          ? `<div class="upload-large-note is-warning">${escapeHtml(selectionMessage)}</div>`
          : '');

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
      const rawFiles = Array.from(fileList);
      const rejectedByType = rawFiles.filter(file => !isAllowed(file));
      const rejectedBySize = rawFiles.filter(file => isAllowed(file) && !isAllowedSize(file));
      const incoming = rawFiles.filter(file => isAllowed(file) && isAllowedSize(file));

      const merged = dedupeFiles([...currentFiles, ...incoming]);
      selectionMessage = '';

      if (rejectedByType.length || rejectedBySize.length) {
        const typeMessage = rejectedByType.length ? `${rejectedByType.length} fichier(s) ignore(s): format non autorise.` : '';
        const sizeMessage = rejectedBySize.length ? `${rejectedBySize.length} fichier(s) ignore(s): plus de 50 Mo.` : '';
        selectionMessage = [typeMessage, sizeMessage].filter(Boolean).join(' ');
      }

      if (merged.length > MAX_FILES) {
        currentFiles = merged.slice(0, MAX_FILES);
        selectionMessage = `${selectionMessage ? selectionMessage + ' ' : ''}Selection limitee a ${MAX_FILES} CV pour proteger le navigateur. Importez le reste dans un deuxieme lot.`;
      } else {
        currentFiles = merged;
      }

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

    clearUploadSelection?.addEventListener('click', function () {
      currentFiles = [];
      selectionMessage = '';
      fileInput.value = '';
      folderInput.value = '';
      renderList();
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

    if (!form || !fileInput) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    const chunkSize = 20;
    const maxRetries = 2;
    const uploadState = {
        signature: '',
        batchId: null,
        nextChunkIndex: 0,
    };

    const progressBox = document.createElement('div');
    progressBox.className = 'ui-progress-card';
    progressBox.style.marginTop = '18px';
    progressBox.style.display = 'none';

    progressBox.innerHTML = `
        <strong id="chunkProgressText">Préparation...</strong>
        <div class="ui-progress-track">
            <div id="chunkProgressBar" class="ui-progress-bar" style="width:0%;"></div>
        </div>
        <div id="chunkProgressMeta" class="ui-progress-copy"></div>
        <div id="chunkProgressError" class="ui-progress-error" style="display:none;"></div>
    `;

    form.appendChild(progressBox);

    const progressText = document.getElementById('chunkProgressText');
    const progressBar = document.getElementById('chunkProgressBar');
    const progressMeta = document.getElementById('chunkProgressMeta');
    const progressError = document.getElementById('chunkProgressError');

    function chunkArray(array, size) {
        const chunks = [];

        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }

        return chunks;
    }

    function filesSignature(files) {
        return files.length + ':' + files.slice(0, 80).map(file => [
            file.webkitRelativePath || file.name,
            file.size,
            file.lastModified,
        ].join('|')).join('::');
    }

    function sleep(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    function chunkFileNames(chunk) {
        const names = chunk.slice(0, 6).map(file => file.webkitRelativePath || file.name).join(', ');
        const hidden = chunk.length > 6 ? ` + ${chunk.length - 6} autre(s)` : '';

        return names + hidden;
    }

    async function readResponseMessage(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            const payload = await response.json().catch(() => null);
            const errors = payload?.errors
                ? Object.values(payload.errors).flat().filter(Boolean).join(' ')
                : '';

            return errors || payload?.message || `HTTP ${response.status}`;
        }

        const text = await response.text().catch(() => '');
        const cleanText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

        return cleanText ? cleanText.slice(0, 500) : `HTTP ${response.status}`;
    }

    async function sendChunk(formData, chunk, chunkNumber, totalChunks) {
        for (let attempt = 1; attempt <= maxRetries + 1; attempt++) {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: formData
            });

            if (response.ok) {
                return response.json();
            }

            const message = await readResponseMessage(response);
            const canRetry = response.status >= 500 || response.status === 408 || response.status === 429;

            if (canRetry && attempt <= maxRetries) {
                progressMeta.textContent = `Chunk ${chunkNumber}/${totalChunks} en erreur temporaire. Nouvelle tentative ${attempt}/${maxRetries}...`;
                await sleep(1200 * attempt);
                continue;
            }

            throw new Error(`Chunk ${chunkNumber}/${totalChunks}: ${message}. Fichiers: ${chunkFileNames(chunk)}`);
        }
    }

    form.addEventListener('submit', async function (e) {
        const trackedFiles = typeof window.rhsExternalUploadFiles === 'function'
            ? window.rhsExternalUploadFiles()
            : [];
        const files = trackedFiles.length ? trackedFiles : Array.from(fileInput.files || []);

        if (!files.length) {
            e.preventDefault();
            progressBox.style.display = 'block';
            progressText.textContent = 'Aucun fichier selectionne.';
            progressBar.style.width = '0%';
            progressMeta.textContent = 'Choisissez des fichiers ou un dossier avant de lancer l import.';
            return;
        }

        e.preventDefault();

        const csrf = form.querySelector('input[name="_token"]').value;
        const chunks = chunkArray(files, chunkSize);
        const signature = filesSignature(files);

        if (uploadState.signature !== signature) {
            uploadState.signature = signature;
            uploadState.batchId = null;
            uploadState.nextChunkIndex = 0;
        }

        let batchId = uploadState.batchId;
        let uploaded = uploadState.nextChunkIndex * chunkSize;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Importation en cours...';
        progressBox.style.display = 'block';
        progressError.style.display = 'none';
        progressError.textContent = '';
        progressMeta.textContent = `${chunks.length} lot(s) de ${chunkSize} fichier(s) seront envoyes. Reprise au lot ${uploadState.nextChunkIndex + 1}. L indexation continuera ensuite dans la file d attente.`;

        try {
            for (let i = uploadState.nextChunkIndex; i < chunks.length; i++) {
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

                progressText.textContent = `Envoi du chunk ${i + 1}/${chunks.length}...`;
                progressMeta.textContent = `Fichiers du chunk: ${chunkFileNames(chunks[i])}`;

                const result = await sendChunk(formData, chunks[i], i + 1, chunks.length);

                if (!batchId) {
                    batchId = result.batch_id;
                    uploadState.batchId = batchId;
                }

                uploaded += chunks[i].length;
                uploadState.nextChunkIndex = i + 1;

                const percent = Math.round((uploaded / files.length) * 100);

                progressText.textContent = `Importation ${uploaded}/${files.length} CV...`;
                progressMeta.textContent = `Lot ${i + 1}/${chunks.length} envoye. Le traitement lourd reste cote queue.`;
                progressBar.style.width = percent + '%';

                if (i === chunks.length - 1) {
                    progressText.textContent = 'Importation terminee. Redirection...';
                    progressMeta.textContent = 'Le lot est cree. L indexation continuera avec le worker de queue.';
                    progressBar.style.width = '100%';
                    uploadState.batchId = null;
                    uploadState.nextChunkIndex = 0;

                    window.location.href = result.redirect_url;
                }
            }
        } catch (error) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Importer le lot';
            progressText.textContent = 'Erreur pendant l importation.';
            progressMeta.textContent = batchId
                ? `Les chunks deja envoyes restent dans le lot #${batchId}. Corrigez le fichier indique si besoin, puis relancez: l upload reprendra au chunk ${uploadState.nextChunkIndex + 1}.`
                : 'Aucun fichier supplementaire ne sera envoye tant que vous ne relancez pas.';
            progressError.textContent = error.message || 'Erreur inconnue.';
            progressError.style.display = 'block';

        }
    });
});
</script>

@endsection
