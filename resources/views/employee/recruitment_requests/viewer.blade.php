@extends('dashboard.layouts.app')

@section('title', 'Visualisation CV')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Suivi recrutement')
@section('page_title', 'Visualisation du CV')
@section('page_copy', 'Apercu interne du fichier CV sans sortie vers un lecteur externe.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <a href="{{ url()->previous() }}" class="admin-btn admin-btn-ghost portal-btn-auto">Retour</a>
@endsection

@section('content')
    <section class="portal-card portal-card--spaced">
        <div class="portal-toolbar">
            <div>
                <h3 class="portal-title-tight text-safe">{{ $cv->candidate_name ?: ($cv->original_filename ?: 'CV #' . $cv->id) }}</h3>
            </div>
            <div class="portal-form-actions">
                <span class="portal-status is-muted">{{ strtoupper(pathinfo((string) $cv->original_filename, PATHINFO_EXTENSION) ?: 'Fichier') }}</span>
                <a href="{{ $streamUrl }}" class="admin-btn admin-btn-ghost portal-btn-auto" target="_blank" rel="noopener">Ouvrir le fichier brut</a>
            </div>
        </div>

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
            <div class="portal-empty">
                <div class="portal-empty-title">Apercu integre non disponible pour ce format</div>
                <div class="portal-empty-copy">
                    Ce type de fichier ne peut pas etre rendu directement dans le navigateur. Utilisez l ouverture brute ou le telechargement.
                </div>
                <div class="portal-form-actions" style="margin-top:16px; justify-content:center;">
                    <a href="{{ $streamUrl }}" class="admin-btn admin-btn-primary portal-btn-auto" target="_blank" rel="noopener">Ouvrir le fichier</a>
                </div>
            </div>
        @endif
    </section>
@endsection
