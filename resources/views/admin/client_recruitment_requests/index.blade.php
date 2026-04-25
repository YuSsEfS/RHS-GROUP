@extends('admin.layouts.app')

@section('title', 'Demandes clients')
@section('page_title', 'Demandes clients')
@section('page_subtitle', 'Suivi des demandes de recrutement envoyees depuis l espace client.')

@php
    $statusTone = static function (string $value): string {
        return match ($value) {
            'completed', 'shortlisted' => 'pill-success',
            'rejected', 'cancelled' => 'pill-danger',
            default => 'pill-neutral',
        };
    };
@endphp

@section('content')
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-body">
            <form method="GET" class="table-controls">
                <div class="table-search">
                    <span class="table-search-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Client, reference, poste">
                </div>

                <div class="table-filter">
                    <select name="status">
                        <option value="all">Tous les statuts</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-ctrl-actions">
                    <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div class="panel-title">
                Demandes clients
                <span class="panel-badge">{{ $requests->total() }}</span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Poste</th>
                        <th>Reference</th>
                        <th>Statut</th>
                        <th>Traitement</th>
                        <th>Date</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td>
                                <div class="cell-main">
                                    <div class="cell-title">{{ $requestItem->client_name ?: $requestItem->clientUser?->name ?: '-' }}</div>
                                    <div class="cell-sub">{{ $requestItem->clientUser?->email ?: '-' }}</div>
                                </div>
                            </td>
                            <td>{{ $requestItem->position_title }}</td>
                            <td>{{ $requestItem->reference ?: '-' }}</td>
                            <td>
                                <span class="pill {{ $statusTone($requestItem->request_status) }}">
                                    {{ $statuses[$requestItem->request_status] ?? $requestItem->request_status }}
                                </span>
                            </td>
                            <td>
                                @if($requestItem->matches_count > 0)
                                    <span class="pill pill-success">{{ $requestItem->matches_count }} match(es)</span>
                                @else
                                    <span class="pill pill-neutral">En attente de matching</span>
                                @endif
                            </td>
                            <td>{{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}</td>
                            <td class="td-actions">
                                <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $requestItem->id]) }}" class="btn btn-ghost btn-sm">
                                    Matching
                                </a>
                                <a href="{{ route('admin.client-recruitment-requests.edit', $requestItem) }}" class="btn btn-primary btn-sm">
                                    Gerer
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <div class="table-empty-title">Aucune demande client trouvee.</div>
                                    <div class="table-empty-sub">Les nouvelles demandes apparaitront ici et seront marquees comme vues a l ouverture de cette page.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">
        {{ $requests->links() }}
    </div>
@endsection
