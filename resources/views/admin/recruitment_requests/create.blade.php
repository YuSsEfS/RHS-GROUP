@extends('admin.layouts.app')

@section('title','Admin – Nouvelle demande')
@section('page_title','Nouvelle demande de recrutement')

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
<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Nouvelle demande recrutement
      <span class="panel-badge">AI Matching</span>
    </div>
  </div>

  <div class="panel-body">
    @if(!empty($sourceClientRequest))
      <div class="admin-alert" style="margin-bottom:24px; border-color:rgba(239,68,68,.18); background:rgba(239,68,68,.06); color:#7f1d1d;">
        <div class="admin-alert-title">Demande client connectee</div>
        <div>
          Vous lancez le matching depuis la demande client
          <strong>{{ $sourceClientRequest->reference ?: '#' . $sourceClientRequest->id }}</strong>
          pour le poste <strong>{{ $sourceClientRequest->position_title }}</strong>.
        </div>
      </div>
    @endif

    <div class="panel panel-spaced">
      <div class="panel-head">
        <div class="panel-title">Importer un document Word</div>
      </div>

      <div class="panel-body">
        <form action="{{ route('admin.recruitment-requests.import-docx') }}" method="POST" enctype="multipart/form-data" class="form">
          @csrf
          @if(!empty($sourceClientRequest))
            <input type="hidden" name="source_client_request_id" value="{{ $sourceClientRequest->id }}">
          @endif

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
      <div class="panel panel-spaced">
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
          enctype="multipart/form-data"
          class="form">
      @csrf
      @if(!empty($sourceClientRequest))
        <input type="hidden" name="source_client_request_id" value="{{ $sourceClientRequest->id }}">
      @endif

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
