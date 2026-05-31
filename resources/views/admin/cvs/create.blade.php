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

  @if($importBatch)
    @php
      $importStatusLabels = \App\Models\CvImportBatch::availableStatuses();
    @endphp
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          Suivi du dernier import
          <span class="panel-badge">{{ $importBatch->name ?: 'Import CV' }}</span>
        </div>
      </div>

      <div class="panel-body">
        <div class="ui-progress-head">
          <div>
            <strong id="cv-import-status-label">{{ $importStatusLabels[$importBatch->status] ?? $importBatch->status }}</strong>
            <div id="cv-import-status-subtext" class="ui-progress-copy">
              {{ $importBatch->pendingFilesCount() > 0 ? 'L indexation continue en arriere-plan.' : 'Aucun traitement en attente.' }}
            </div>
          </div>
          <div id="cv-import-progress-text" class="match-score">{{ $importBatch->progressPercentage() }}%</div>
        </div>

        <div class="ui-progress-track">
          <div id="cv-import-progress-bar" class="ui-progress-bar" style="width:{{ $importBatch->progressPercentage() }}%;"></div>
        </div>

        <div class="ui-progress-kpis">
          <span><strong id="cv-import-pending">{{ $importBatch->pendingFilesCount() }}</strong> en attente</span>
          <span>Temps estime : <strong id="cv-import-eta">Calcul en cours</strong></span>
        </div>

        <div class="info-grid" style="margin-top:16px;">
          <div class="info-item">
            <div class="info-label">Total</div>
            <div class="info-value" id="cv-import-total">{{ $importBatch->total_files }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">En file</div>
            <div class="info-value" id="cv-import-queued">{{ $importBatch->queued_files }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Traites</div>
            <div class="info-value" id="cv-import-processed">{{ $importBatch->processed_files }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Doublons</div>
            <div class="info-value" id="cv-import-duplicates">{{ $importBatch->duplicate_files }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Echecs</div>
            <div class="info-value" id="cv-import-failed">{{ $importBatch->failed_files }}</div>
          </div>
        </div>

        <div id="cv-import-error" class="ui-progress-error">
          {{ $importBatch->error_message }}
        </div>
      </div>
    </div>
  @endif

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
          const key = [
            file.webkitRelativePath || '',
            file.name,
            file.size,
            file.lastModified
          ].join('__');

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

        currentFiles.forEach(file => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'relative_paths[]';
          input.value = file.webkitRelativePath || '';
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
          const relative = file.webkitRelativePath
            ? `<div class="upload-file-meta">${escapeHtml(file.webkitRelativePath)}</div>`
            : '';

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
      const form = document.getElementById('cv-upload-form');
      const fileInput = document.getElementById('cv_files');

      if (!form || !fileInput) {
        return;
      }

      const chunkSize = 10;
      const submitBtn = form.querySelector('button[type="submit"]');
      const progressBox = document.createElement('div');
      progressBox.className = 'ui-progress-card';
      progressBox.style.marginTop = '18px';
      progressBox.style.display = 'none';
      progressBox.innerHTML = `
        <div class="ui-progress-head">
          <strong id="cvChunkProgressText">Preparation...</strong>
        </div>
        <div class="ui-progress-track">
          <div id="cvChunkProgressBar" class="ui-progress-bar" style="width:0%;"></div>
        </div>
      `;
      form.appendChild(progressBox);

      const progressText = document.getElementById('cvChunkProgressText');
      const progressBar = document.getElementById('cvChunkProgressBar');

      const chunkArray = function (array, size) {
        const chunks = [];

        for (let i = 0; i < array.length; i += size) {
          chunks.push(array.slice(i, i + size));
        }

        return chunks;
      };

      const pollImportStatus = async function (statusUrl) {
        const response = await fetch(statusUrl, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error('Impossible de recuperer le statut de l import.');
        }

        const payload = await response.json();
        const statusLabelNode = document.getElementById('cv-import-status-label');
        const statusSubtextNode = document.getElementById('cv-import-status-subtext');
        const statusProgressTextNode = document.getElementById('cv-import-progress-text');
        const statusProgressBarNode = document.getElementById('cv-import-progress-bar');
        const totalNode = document.getElementById('cv-import-total');
        const queuedNode = document.getElementById('cv-import-queued');
        const processedNode = document.getElementById('cv-import-processed');
        const duplicateNode = document.getElementById('cv-import-duplicates');
        const failedNode = document.getElementById('cv-import-failed');
        const pendingNode = document.getElementById('cv-import-pending');
        const etaNode = document.getElementById('cv-import-eta');
        const errorNode = document.getElementById('cv-import-error');

        const labels = {
          en_attente: 'En attente',
          en_cours: 'En cours',
          termine: 'Termine',
          echoue: 'Echoue'
        };

        if (statusLabelNode) statusLabelNode.textContent = labels[payload.status] || payload.status;
        if (statusSubtextNode) {
          statusSubtextNode.textContent = payload.pending_files > 0
            ? 'L indexation continue en arriere-plan. Temps estime restant : ' + (payload.estimated_time_remaining || 'Calcul en cours') + '.'
            : 'L import a termine son traitement.';
        }
        if (statusProgressTextNode) statusProgressTextNode.textContent = payload.progress_percentage + '%';
        if (statusProgressBarNode) statusProgressBarNode.style.width = payload.progress_percentage + '%';
        if (totalNode) totalNode.textContent = payload.total_files;
        if (queuedNode) queuedNode.textContent = payload.queued_files;
        if (processedNode) processedNode.textContent = payload.processed_files;
        if (duplicateNode) duplicateNode.textContent = payload.duplicate_files || 0;
        if (failedNode) failedNode.textContent = payload.failed_files;
        if (pendingNode) pendingNode.textContent = payload.pending_files || 0;
        if (etaNode) etaNode.textContent = payload.estimated_time_remaining || 'Calcul en cours';
        if (errorNode) errorNode.textContent = payload.error_message || '';

        if (payload.status === 'en_attente' || payload.status === 'en_cours') {
          window.setTimeout(function () {
            pollImportStatus(statusUrl).catch(function (error) {
              if (errorNode) {
                errorNode.textContent = error.message;
              }
            });
          }, 5000);
        }
      };

      form.addEventListener('submit', async function (event) {
        const files = Array.from(fileInput.files || []);

        if (!files.length) {
          return;
        }

        event.preventDefault();

        const csrf = form.querySelector('input[name="_token"]').value;
        const chunks = chunkArray(files, chunkSize);
        let batchId = null;
        let statusUrl = null;
        let uploaded = 0;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Upload en cours...';
        progressBox.style.display = 'block';

        try {
          for (let i = 0; i < chunks.length; i++) {
            const formData = new FormData();

            formData.append('_token', csrf);
            formData.append('cv_folder_id', form.querySelector('[name="cv_folder_id"]')?.value || '');
            formData.append('new_folder_name', form.querySelector('[name="new_folder_name"]')?.value || '');
            formData.append('city', form.querySelector('[name="city"]')?.value || '');
            formData.append('current_title', form.querySelector('[name="current_title"]')?.value || '');
            formData.append('notes', form.querySelector('[name="notes"]')?.value || '');
            formData.append('chunk_index', i);
            formData.append('total_chunks', chunks.length);
            formData.append('total_files', files.length);

            if (batchId) {
              formData.append('batch_id', batchId);
            }

            chunks[i].forEach(function (file) {
              formData.append('cv_files[]', file);
              formData.append('relative_paths[]', file.webkitRelativePath || '');
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
              throw new Error('Erreur pendant l envoi du lot ' + (i + 1));
            }

            const result = await response.json();

            if (!batchId) {
              batchId = result.batch_id;
            }

            if (!statusUrl && result.status_url) {
              statusUrl = result.status_url;
            }

            uploaded += chunks[i].length;

            const percent = Math.round((uploaded / files.length) * 100);
            progressText.textContent = `Upload ${uploaded}/${files.length} CV...`;
            progressBar.style.width = percent + '%';
          }

          progressText.textContent = 'Upload termine. Indexation en arriere-plan...';
          progressBar.style.width = '100%';

          if (batchId) {
            window.location.href = @json(route('admin.cvs.create')) + '?import_batch=' + batchId;
            return;
          }

          if (statusUrl) {
            pollImportStatus(statusUrl).catch(function () {});
          }
        } catch (error) {
          alert(error.message);
          submitBtn.disabled = false;
          submitBtn.textContent = 'Uploader';
          progressText.textContent = 'Erreur pendant l import.';
        }
      });

      @if($importBatch)
        pollImportStatus(@json(route('admin.cvs.import-status', $importBatch))).catch(function () {});
      @endif
    });
  </script>

@endsection
