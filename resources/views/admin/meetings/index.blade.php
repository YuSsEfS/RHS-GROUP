@extends('admin.layouts.app')

@section('title','Reunions')
@section('page_title','Reunions')
@section('page_subtitle','Planifiez les reunions internes, entretiens et suivis de recrutement.')

@section('top_actions')
<a href="{{ route('admin.meetings.create') }}" class="btn btn-primary">Nouvelle reunion</a>
@endsection

@section('content')
@php
  $pageMeetings = $meetings->getCollection();
  $scheduledOnPage = $pageMeetings->where('status', \App\Models\Meeting::STATUS_SCHEDULED)->count();
  $todayOnPage = $pageMeetings->filter(fn ($meeting) => $meeting->meeting_date?->isToday())->count();
@endphp

<div class="meeting-page meeting-index-page">
  <div class="meeting-summary-grid">
    <div class="meeting-summary-card">
      <span>Total reunions</span>
      <strong>{{ $meetings->total() }}</strong>
      <small>Planning complet</small>
    </div>
    <div class="meeting-summary-card">
      <span>Planifiees</span>
      <strong>{{ $scheduledOnPage }}</strong>
      <small>Sur cette page</small>
    </div>
    <div class="meeting-summary-card">
      <span>Aujourd'hui</span>
      <strong>{{ $todayOnPage }}</strong>
      <small>Rendez-vous du jour</small>
    </div>
  </div>

  <div class="panel panel-safe meeting-filter-panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Filtres</div>
        <p class="panel-subtitle">Recherchez une reunion par titre, lieu, description ou statut.</p>
      </div>
    </div>
    <form method="GET" class="meeting-filter-grid">
      <label class="meeting-field">
        <span>Recherche</span>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Titre, lieu, description...">
      </label>
      <label class="meeting-field">
        <span>Statut</span>
        <select name="status">
          <option value="">Tous les statuts</option>
          @foreach($statuses as $key => $label)
            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn btn-primary" type="submit">Filtrer</button>
      <a class="btn btn-ghost" href="{{ route('admin.meetings.index') }}">Reinitialiser</a>
    </form>
  </div>

  <div class="panel panel-safe table-safe meeting-list-panel">
    <div class="panel-head">
      <div class="panel-title">Planning <span class="panel-badge">{{ $meetings->total() }}</span></div>
    </div>
    <div class="table-wrap">
    <table class="table meeting-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Reunion</th>
          <th>Participants</th>
          <th>Demande liee</th>
          <th>Statut</th>
          <th class="th-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($meetings as $meeting)
          <tr>
            <td>
              <div class="meeting-date-chip">
                <strong>{{ $meeting->meeting_date?->format('d/m') }}</strong>
                <span>{{ $meeting->meeting_date?->format('Y') }}</span>
                <small>{{ substr($meeting->start_time, 0, 5) }}{{ $meeting->end_time ? ' - '.substr($meeting->end_time, 0, 5) : '' }}</small>
              </div>
            </td>
            <td>
              <div class="meeting-title-block">
                <strong>{{ $meeting->title }}</strong>
                <small>{{ $meeting->location ?: ($meeting->online_link ?: 'Lieu non precise') }}</small>
              </div>
            </td>
            <td><span class="meeting-pill">{{ $meeting->users->count() }} participant(s)</span></td>
            <td class="wrap-safe">{{ $meeting->recruitmentRequest?->position_title ?: '-' }}</td>
            <td><span class="meeting-status is-{{ $meeting->status }}">{{ $statuses[$meeting->status] ?? $meeting->status }}</span></td>
            <td class="td-actions">
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.meetings.show', $meeting) }}">Ouvrir</a>
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.meetings.edit', $meeting) }}">Modifier</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="meeting-empty">
                <strong>Aucune reunion planifiee</strong>
                <span>Creez une reunion pour informer les participants.</span>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="panel-foot">{{ $meetings->links() }}</div>
  </div>
</div>
@endsection
