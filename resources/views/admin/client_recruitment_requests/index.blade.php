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

                <div class="table-filter">
                    <select name="assignment">
                        <option value="all" @selected(($assignment ?? 'all') === 'all')>Toutes les affectations</option>
                        <option value="assigned_unseen" @selected(($assignment ?? '') === 'assigned_unseen')>Affectations non vues</option>
                        <option value="assigned" @selected(($assignment ?? '') === 'assigned')>Demandes assignees</option>
                        <option value="assigned_in_progress" @selected(($assignment ?? '') === 'assigned_in_progress')>Assignations en cours</option>
                        <option value="assigned_completed" @selected(($assignment ?? '') === 'assigned_completed')>Assignations terminees</option>
                        <option value="unassigned" @selected(($assignment ?? '') === 'unassigned')>Non assignees</option>
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
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Pipeline</th>
                        <th>Assignation</th>
                        <th>Traitement</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td>
                                <div class="cell-main request-logo-cell">
                                    <span class="request-logo-thumb">
                                        @if($requestItem->logo_url)
                                            <img src="{{ $requestItem->logo_url }}" alt="{{ $requestItem->client_name }}">
                                        @else
                                            {{ strtoupper(substr($requestItem->client_name ?: 'R', 0, 1)) }}
                                        @endif
                                    </span>
                                    <span>
                                    <div class="cell-title">{{ $requestItem->client_name ?: $requestItem->clientUser?->name ?: '-' }}</div>
                                    <div class="cell-sub">{{ $requestItem->clientUser?->email ?: '-' }}</div>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-main">
                                    <div class="cell-title">{{ $requestItem->position_title }}</div>
                                    <div class="cell-sub">Ref: {{ $requestItem->reference ?: '-' }}</div>
                                </div>
                            </td>
                            <td>{{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}</td>
                            <td>
                                <span class="pill {{ $statusTone($requestItem->request_status) }}">
                                    {{ $statuses[$requestItem->request_status] ?? $requestItem->request_status }}
                                </span>
                            </td>
                            <td>
                                <span class="pill pill-neutral">
                                    {{ \App\Models\RecruitmentRequest::availablePipelineStages()[$requestItem->pipeline_stage] ?? 'Nouvelle demande' }}
                                </span>
                            </td>
                            <td>
                                @if($requestItem->assignedEmployee)
                                    <div class="cell-main">
                                        <div class="cell-title">{{ $requestItem->assignedEmployee->name }}</div>
                                        <div class="cell-sub">
                                            {{ \App\Models\RecruitmentRequest::availableAssignmentStatuses()[$requestItem->assignment_status] ?? 'Assignee' }}
                                        </div>
                                    </div>
                                @else
                                    <span class="pill pill-danger">Non assignee</span>
                                @endif
                            </td>
                            <td>
                                @if($requestItem->matching_job_status)
                                    <span class="pill pill-neutral">
                                        {{ \App\Models\RecruitmentRequest::availableJobStatuses()[$requestItem->matching_job_status] ?? $requestItem->matching_job_status }}
                                    </span>
                                @elseif($requestItem->matches_count > 0)
                                    <span class="pill pill-success">{{ $requestItem->matches_count }} match(es)</span>
                                @else
                                    <span class="pill pill-neutral">A lancer</span>
                                @endif
                            </td>
                            <td class="td-actions">
                                <a href="{{ route('admin.client-recruitment-requests.edit', $requestItem) }}" class="btn btn-primary btn-sm">
                                    Ouvrir
                                </a>
                                <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $requestItem->id]) }}" class="btn btn-ghost btn-sm">
                                    Matching
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
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
