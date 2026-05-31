@extends('dashboard.layouts.app')

@section('title','Mes reunions')
@section('brand','RHS Employe')
@section('brand_sub','Planning interne')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title','Mes reunions')
@section('page_copy','Consultez les reunions auxquelles vous etes invite.')

@section('content')
@php
  $pageMeetings = $meetings->getCollection();
  $scheduledOnPage = $pageMeetings->where('status', \App\Models\Meeting::STATUS_SCHEDULED)->count();
  $todayOnPage = $pageMeetings->filter(fn ($meeting) => $meeting->meeting_date?->isToday())->count();
@endphp

<div class="meeting-page">
  <div class="meeting-summary-grid">
    <div class="meeting-summary-card">
      <span>Mes reunions</span>
      <strong>{{ $meetings->total() }}</strong>
      <small>Invitations recues</small>
    </div>
    <div class="meeting-summary-card">
      <span>Planifiees</span>
      <strong>{{ $scheduledOnPage }}</strong>
      <small>Sur cette page</small>
    </div>
    <div class="meeting-summary-card">
      <span>Aujourd'hui</span>
      <strong>{{ $todayOnPage }}</strong>
      <small>Agenda du jour</small>
    </div>
  </div>

  <div class="panel panel-safe meeting-filter-panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Filtres</div>
        <p class="panel-subtitle">Affinez votre planning par statut.</p>
      </div>
      @if(auth()->user()?->hasPermission('meetings_manage'))
        <a class="btn btn-primary" href="{{ route('employee.meetings.create') }}">Nouvelle reunion</a>
      @endif
    </div>
    <form method="GET" class="meeting-filter-grid meeting-filter-grid-employee">
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
      <a class="btn btn-ghost" href="{{ route('employee.meetings.index') }}">Reinitialiser</a>
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
            <th>Demande liee</th>
            <th>Statut</th>
            <th class="th-actions">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($meetings as $meeting)
            <tr>
              <td>
                <div class="meeting-date-chip">
                  <strong>{{ $meeting->meeting_date?->format('d/m') }}</strong>
                  <span>{{ $meeting->meeting_date?->format('Y') }}</span>
                  <small>{{ substr($meeting->start_time, 0, 5) }}</small>
                </div>
              </td>
              <td>
                <div class="meeting-title-block">
                  <strong>{{ $meeting->title }}</strong>
                  <small>{{ $meeting->location ?: ($meeting->online_link ?: 'Lieu non precise') }}</small>
                </div>
              </td>
              <td class="wrap-safe">{{ $meeting->recruitmentRequest?->position_title ?: '-' }}</td>
              <td><span class="meeting-status is-{{ $meeting->status }}">{{ $statuses[$meeting->status] ?? $meeting->status }}</span></td>
              <td class="td-actions">
                <a class="btn btn-ghost btn-sm" href="{{ route('employee.meetings.show', $meeting) }}">Ouvrir</a>
                @if(auth()->user()?->hasPermission('meetings_manage') && (int) $meeting->created_by === (int) auth()->id())
                  <a class="btn btn-ghost btn-sm" href="{{ route('employee.meetings.edit', $meeting) }}">Modifier</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="meeting-empty">
                  <strong>Aucune reunion pour le moment</strong>
                  <span>Vos prochaines invitations apparaitront ici.</span>
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
