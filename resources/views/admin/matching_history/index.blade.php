@extends('admin.layouts.app')

@section('title', 'Admin - Historique matching')
@section('page_title', 'Historique Matching')
@section('page_subtitle', 'Retrouvez tous les traitements de matching lances, termines ou en attente.')

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.matching-history.index', request()->query()) }}">
    Rafraichir
  </a>
  <a class="btn btn-primary" href="{{ route('admin.recruitment_requests.create') }}">
    Nouveau matching
  </a>
@endsection

@section('content')
@php
  $matchingHistoryTotal = method_exists($requests, 'total') ? $requests->total() : $requests->count();
@endphp

<div class="panel panel-safe">
  <div class="panel-head">
    <div class="panel-title">Historique des resultats</div>
  </div>

  <div class="panel-body ui-filter-panel">
    <form method="GET" action="{{ route('admin.matching-history.index') }}" class="matching-history-filters">
      <div class="form-group">
        <label for="q">Recherche</label>
        <input id="q" type="text" name="q" value="{{ $q }}" placeholder="Reference, poste, client, offre..." />
      </div>

      <div class="form-group">
        <label for="status">Statut</label>
        <select id="status" name="status">
          <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tous</option>
          @foreach($statusLabels as $key => $label)
            <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="table-ctrl-actions action-bar">
        <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.matching-history.index') }}">Reinitialiser</a>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.matching-history.index', request()->query()) }}">Actualiser le statut</a>
      </div>
    </form>
  </div>
</div>

  <div class="panel panel-safe table-safe">
  <div class="panel-head">
    <div class="panel-title">
      Traitements de matching
      <span class="panel-badge">{{ $matchingHistoryTotal }}</span>
    </div>
  </div>

  <div class="panel-body" style="padding:0;">
    @if($requests->count())
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Demande</th>
              <th>Offre</th>
              <th>Dossier CV</th>
              <th>Traitement</th>
              <th>Matches</th>
              <th>Mis a jour</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($requests as $requestItem)
              @php
                $matchingStatus = $requestItem->resolveMatchingStatus() ?? \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING;
              @endphp
              <tr>
                <td>
                  <div class="matching-history-meta text-safe wrap-safe">
                    <strong>{{ $requestItem->reference ?: 'Sans reference' }}</strong>
                    <span>{{ $requestItem->position_title ?: 'Poste non renseigne' }}</span>
                    <span>{{ $requestItem->client_name ?: 'Client non renseigne' }}</span>
                  </div>
                </td>
                <td class="text-safe wrap-safe">{{ $requestItem->jobOffer?->title ?: '-' }}</td>
                <td class="text-safe wrap-safe">{{ $requestItem->folder?->name ?: '-' }}</td>
                <td>
                  <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <span class="matching-history-status {{ $matchingStatus }}">
                      {{ $statusLabels[$matchingStatus] ?? ucfirst($matchingStatus) }}
                    </span>
                    @if($requestItem->hasUnreadMatchingResults())
                      <span class="matching-history-unread">Nouveaux resultats</span>
                    @endif
                  </div>
                </td>
                <td>
                  <div class="matching-history-meta">
                    <strong>{{ $requestItem->matches_count }}</strong>
                    <span>{{ $requestItem->selected_matches_count }} selectionne(s)</span>
                  </div>
                </td>
                <td>
                  <div class="matching-history-meta">
                    <strong>{{ optional($requestItem->created_at)->format('d/m/Y H:i') }}</strong>
                    <span>{{ optional($requestItem->updated_at)->diffForHumans() }}</span>
                  </div>
                </td>
                <td>
                  <a class="btn btn-primary btn-sm" href="{{ route('admin.recruitment_requests.results', ['recruitmentRequest' => $requestItem->id, 'offer' => $requestItem->job_offer_id ?: 'all', 'folder' => $requestItem->cv_folder_id ?: 'all']) }}">
                    Voir resultats
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
        {{ $requests->links() }}
      </div>
    @else
      <div class="ui-empty-state">
        <div class="ui-empty-title">Aucun historique de matching</div>
        <div class="ui-empty-copy">Les traitements en attente, termines ou en erreur apparaitront ici automatiquement.</div>
      </div>
    @endif
  </div>
</div>
@endsection
