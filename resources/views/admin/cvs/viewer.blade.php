@extends('admin.layouts.app')

@section('title', 'Visualisation CV')
@section('page_title', 'Visualisation du CV')
@section('page_subtitle', 'Apercu interne du fichier CV sans sortie vers un lecteur externe.')

@section('top_actions')
  <a href="{{ url()->previous() }}" class="btn btn-ghost">Retour</a>
@endsection

@section('content')
  <div class="panel panel-safe">
    <div class="panel-head">
      <div class="panel-title text-safe">{{ $cv->candidate_name ?: ($cv->original_filename ?: 'CV #' . $cv->id) }}</div>
      <div class="action-bar">
        <span class="pill pill-neutral">{{ strtoupper(pathinfo((string) $cv->original_filename, PATHINFO_EXTENSION) ?: 'Fichier') }}</span>
        <a href="{{ $streamUrl }}" class="btn btn-ghost" target="_blank" rel="noopener">Ouvrir le fichier brut</a>
      </div>
    </div>
    <div class="panel-body">
      @if(str_starts_with($mime, 'application/pdf'))
        <iframe
          src="{{ $streamUrl }}"
          title="Apercu du CV"
          style="width:100%; min-height:78vh; border:1px solid rgba(15,23,42,.10); border-radius:18px; background:#fff;"
        ></iframe>
      @elseif(str_starts_with($mime, 'image/'))
        <div class="panel-safe" style="padding:12px; border-radius:18px; background:#f8fafc;">
          <img src="{{ $streamUrl }}" alt="{{ $cv->original_filename }}" style="max-width:100%; border-radius:16px; display:block; margin:0 auto;">
        </div>
      @else
        <div class="ui-empty-state">
          <div class="ui-empty-title">Apercu integre non disponible pour ce format</div>
          <div class="ui-empty-copy">
            Ce type de fichier ne peut pas etre rendu directement dans le navigateur. Utilisez l ouverture brute ou le telechargement.
          </div>
          <div class="action-bar" style="margin-top:16px;">
            <a href="{{ $streamUrl }}" class="btn btn-primary" target="_blank" rel="noopener">Ouvrir le fichier</a>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
