@extends('admin.layouts.app')

@section('title','Admin – Nouvelle demande')
@section('page_title','Nouvelle demande recrutement')

@section('page_subtitle')
Créer une demande et lancer l’analyse intelligente des CV
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.dashboard') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M15 18l-6-6 6-6"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"/>
      </svg>
    </span>
    Retour
  </a>
@endsection

@section('content')

<style>
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }

  .form-field.full {
    grid-column: 1 / -1;
  }

  .form-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
  }

  .form-field input[type="text"],
  .form-field input[type="date"],
  .form-field input[type="email"],
  .form-field input[type="number"],
  .form-field textarea,
  .form-field .form-select {
    width: 100%;
    min-height: 46px;
    padding: 12px 14px;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    background: #fff;
    color: #111827;
    font-size: 14px;
    line-height: 1.4;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
  }

  .form-field textarea {
    min-height: 120px;
    resize: vertical;
  }

  .form-field input::placeholder,
  .form-field textarea::placeholder,
  .form-field .form-select {
    color: #6b7280;
  }

  .form-field input:focus,
  .form-field textarea:focus,
  .form-field .form-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
  }

  .form-error {
    margin-top: 8px;
    color: #dc2626;
    font-size: 13px;
  }

  .form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    align-items: center;
    margin-top: 24px;
    flex-wrap: wrap;
  }

  /* File upload */
  .file-upload {
    position: relative;
  }

  .file-upload-input {
    position: absolute;
    opacity: 0;
    inset: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
  }

  .file-upload-label {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 16px 18px;
    border: 1.5px dashed #cbd5e1;
    border-radius: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    transition: all 0.25s ease;
    cursor: pointer;
  }

  .file-upload:hover .file-upload-label,
  .file-upload-input:focus + .file-upload-label {
    border-color: #6366f1;
    background: #eef2ff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
  }

  .file-upload-icon {
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(99, 102, 241, 0.10);
    color: #4f46e5;
  }

  .file-upload-icon svg {
    width: 22px;
    height: 22px;
  }

  .file-upload-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
  }

  .file-upload-title {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
  }

  .file-upload-text {
    font-size: 13px;
    color: #6b7280;
    word-break: break-word;
  }

  /* Custom select */
  .select-wrapper {
    position: relative;
  }

  .form-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 44px !important;
    cursor: pointer;
  }

  .select-icon {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #6b7280;
    pointer-events: none;
  }

  .select-icon svg {
    width: 100%;
    height: 100%;
  }

  /* Checkbox group */
  .checkbox-group {
    display: flex;
    gap: 14px 20px;
    flex-wrap: wrap;
    padding: 12px 14px;
    min-height: 46px;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
  }

  .checkbox-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #111827;
  }

  .checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #4f46e5;
  }

  @media (max-width: 900px) {
    .form-grid {
      grid-template-columns: 1fr;
    }

    .form-field.full {
      grid-column: auto;
    }
  }
</style>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Nouvelle demande recrutement
      <span class="panel-badge">AI Matching</span>
    </div>
  </div>

  <div class="panel-body">

    <div class="panel" style="margin-bottom: 24px;">
      <div class="panel-head">
        <div class="panel-title">Importer un document Word</div>
      </div>

      <div class="panel-body">
        <form action="{{ route('admin.recruitment-requests.import-docx') }}" method="POST" enctype="multipart/form-data" class="form">
          @csrf

          <div class="form-field full">
            <label for="docx_file">Fichier Word (.docx)</label>

            <div class="file-upload">
              <input
                type="file"
                name="docx_file"
                id="docx_file"
                accept=".docx"
                required
                class="file-upload-input"
              >

              <label for="docx_file" class="file-upload-label">
                <span class="file-upload-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 16V4M12 4l-4 4M12 4l4 4M4 20h16"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                  </svg>
                </span>

                <span class="file-upload-content">
                  <span class="file-upload-title">Choisir un fichier Word</span>
                  <span class="file-upload-text" id="file-upload-text">Aucun fichier sélectionné</span>
                </span>
              </label>
            </div>

            @error('docx_file')
              <div class="form-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              Importer et pré-remplir
            </button>
          </div>
        </form>
      </div>
    </div>

    @if (!empty($importedText))
      <div class="panel" style="margin-bottom: 24px;">
        <div class="panel-head">
          <div class="panel-title">Texte extrait du document</div>
        </div>

        <div class="panel-body">
          <pre style="white-space: pre-wrap; margin:0;">{{ $importedText }}</pre>
        </div>
      </div>
    @endif

    <form method="POST"
          action="{{ route('admin.recruitment_requests.store') }}"
          class="form">
      @csrf

      @include('admin.recruitment_requests._form', [
        'request' => $request ?? null
      ])

      <div class="form-actions">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-light">
          Annuler
        </a>

        <button type="submit" class="btn btn-primary">
          <span class="btn-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M5 12l5 5L20 7"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"/>
            </svg>
          </span>
          Analyse and Match CVs
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('docx_file');
    const fileText = document.getElementById('file-upload-text');

    if (fileInput && fileText) {
      fileInput.addEventListener('change', function () {
        fileText.textContent = this.files && this.files.length
          ? this.files[0].name
          : 'Aucun fichier sélectionné';
      });
    }
  });
</script>

@endsection