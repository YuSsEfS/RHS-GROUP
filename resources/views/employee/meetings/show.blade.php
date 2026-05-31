@extends('dashboard.layouts.app')

@section('title',$meeting->title)
@section('brand','RHS Employe')
@section('brand_sub','Planning interne')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title',$meeting->title)
@section('page_copy','Details de votre reunion.')

@section('content')
@if(auth()->user()?->hasPermission('meetings_manage') && (int) $meeting->created_by === (int) auth()->id())
  <div class="action-bar" style="justify-content:flex-end;margin-bottom:16px;">
    <a class="btn btn-ghost" href="{{ route('employee.meetings.edit', $meeting) }}">Modifier</a>
    <form method="POST" action="{{ route('employee.meetings.destroy', $meeting) }}" onsubmit="return confirm('Supprimer cette reunion ?')">
      @csrf
      @method('DELETE')
      <button class="btn btn-danger" type="submit">Supprimer</button>
    </form>
  </div>
@endif

<div class="meeting-page meeting-show-page">
  <div class="meeting-hero-card meeting-hero-card-compact">
    <div class="meeting-date-chip is-large">
      <strong>{{ $meeting->meeting_date?->format('d/m') }}</strong>
      <span>{{ $meeting->meeting_date?->format('Y') }}</span>
      <small>{{ substr($meeting->start_time, 0, 5) }}{{ $meeting->end_time ? ' - '.substr($meeting->end_time, 0, 5) : '' }}</small>
    </div>
    <div class="meeting-title-block">
      <strong>{{ $meeting->title }}</strong>
      <small>{{ $meeting->location ?: ($meeting->online_link ?: 'Lieu non precise') }}</small>
    </div>
    <span class="meeting-status is-{{ $meeting->status }}">{{ \App\Models\Meeting::statuses()[$meeting->status] ?? $meeting->status }}</span>
  </div>

  <div class="grid-2 meeting-detail-grid">
    <div class="panel panel-safe meeting-detail-panel">
      <div class="panel-head">
        <div class="panel-title">Informations</div>
      </div>
      <div class="meeting-info-grid">
        <div class="meeting-info-card"><span>Date</span><strong>{{ $meeting->meeting_date?->format('d/m/Y') }}</strong></div>
        <div class="meeting-info-card"><span>Horaire</span><strong>{{ substr($meeting->start_time, 0, 5) }}{{ $meeting->end_time ? ' - '.substr($meeting->end_time, 0, 5) : '' }}</strong></div>
        <div class="meeting-info-card"><span>Lieu</span><strong>{{ $meeting->location ?: '-' }}</strong></div>
        <div class="meeting-info-card"><span>Statut</span><strong><span class="meeting-status is-{{ $meeting->status }}">{{ \App\Models\Meeting::statuses()[$meeting->status] ?? $meeting->status }}</span></strong></div>
        <div class="meeting-info-card meeting-info-card-wide"><span>Lien</span><strong class="wrap-safe">{{ $meeting->online_link ?: '-' }}</strong></div>
        <div class="meeting-info-card meeting-info-card-wide"><span>Demande liee</span><strong class="wrap-safe">{{ $meeting->recruitmentRequest?->position_title ?: '-' }}</strong></div>
      </div>
      @if($meeting->description)
        <div class="meeting-description-card">
          <span>Description</span>
          <p>{{ $meeting->description }}</p>
        </div>
      @endif
    </div>

    <div class="panel panel-safe meeting-detail-panel">
      <div class="panel-head">
        <div class="panel-title">Participants <span class="panel-badge">{{ $meeting->users->count() }}</span></div>
      </div>
      <div class="meeting-participant-list">
        @forelse($meeting->users as $participant)
          <div class="meeting-participant-card">
            <span class="meeting-avatar">{{ strtoupper(substr($participant->name ?: 'U', 0, 1)) }}</span>
            <div>
              <strong>{{ $participant->name }}</strong>
              <small>{{ $participant->role }}</small>
            </div>
          </div>
        @empty
          <div class="meeting-empty">
            <strong>Aucun participant</strong>
            <span>Les participants apparaitront ici.</span>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
