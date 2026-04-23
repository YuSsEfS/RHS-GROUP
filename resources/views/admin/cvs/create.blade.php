@extends('admin.layouts.app')

@section('title','Admin – Upload CVs')
@section('page_title','Upload CVs')

@section('page_subtitle')
Ajouter manuellement des CVs au CV Bank
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.cvs.index') }}">
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
      Upload CVs
      <span class="panel-badge">CV Bank</span>
    </div>
  </div>

  <div class="panel-body">
    <form action="{{ route('admin.cvs.store') }}" method="POST" enctype="multipart/form-data" class="form">
      @csrf

      <div class="form-field full">
        <label for="cv_files">Choisir les CVs</label>
        <input id="cv_files" type="file" name="cv_files[]" multiple required>
        @error('cv_files') <div class="form-error">{{ $message }}</div> @enderror
        @error('cv_files.*') <div class="form-error">{{ $message }}</div> @enderror
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Upload</button>
      </div>
    </form>
  </div>
</div>

@endsection