@extends('admin.layouts.app')
@section('title','Admin – Candidature')
@section('page_title','Candidature')

@section('page_subtitle')
Détails de la candidature
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.applications.index') }}">
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
      <div class="panel-title">
        {{ $application->full_name }}
        @if($application->is_read)
          <span class="panel-badge">Consultee</span>
        @else
          <span class="panel-badge">Non consultee</span>
        @endif
      </div>
    </div>

    <div class="panel-body">

      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Email</div>
          <div class="info-value">{{ $application->email }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Téléphone</div>
          <div class="info-value">{{ $application->phone ?? '—' }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Ville</div>
          <div class="info-value">{{ $application->city ?? '—' }}</div>
        </div>

        <div class="info-item">
          <div class="info-label">Offre</div>
          <div class="info-value">{{ $application->offer?->title ?? 'Spontanée' }}</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="file-actions">
        @if($application->cv_path)
          <a class="btn btn-primary" href="{{ route('admin.applications.cv', $application) }}" target="_blank" rel="noopener">
            Voir CV
          </a>
        @endif

        @if($application->letter_path)
          <a class="btn btn-ghost" href="{{ route('admin.applications.letter', $application) }}"
 target="_blank" rel="noopener">
            Voir Lettre
          </a>
        @endif
      </div>

      @if(!empty($application->message))
        <div class="divider"></div>

        <div class="message-box">
          <div class="message-title">Message</div>
          <div class="message-content">{{ $application->message }}</div>
        </div>
      @endif

    </div>
  </div>

@endsection
