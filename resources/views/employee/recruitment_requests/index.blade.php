@extends('dashboard.layouts.app')

@section('title', 'Demandes assignees')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Suivi recrutement')
@section('page_title', 'Demandes de recrutement assignees')
@section('page_copy', 'Consultez uniquement les demandes clientes qui vous sont attribuees, mettez a jour la progression et ajoutez vos notes sans acceder aux CV ni aux resultats candidats.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ $requests->total() }} demande(s)</span>
@endsection

@section('content')
    <section class="portal-card" style="margin-bottom:18px;">
        <form method="GET" class="portal-form-grid" style="align-items:end;">
            <div>
                <label for="status">Statut de tache</label>
                <select id="status" name="status">
                    <option value="all">Tous</option>
                    @foreach($assignmentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="stage">Etape pipeline</label>
                <select id="stage" name="stage">
                    <option value="all">Toutes</option>
                    @foreach($pipelineStages as $value => $label)
                        <option value="{{ $value }}" @selected($stage === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="portal-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Filtrer</button>
            </div>
        </form>
    </section>

    <section class="portal-card">
        <div class="portal-timeline">
            @forelse($requests as $requestItem)
                <article class="portal-record">
                    <div class="portal-record-top">
                        <div>
                            <strong>{{ $requestItem->position_title }}</strong>
                            <div class="portal-copy">
                                Client: {{ $requestItem->client_name ?: $requestItem->clientUser?->name ?: '-' }} |
                                Reference: {{ $requestItem->reference ?: '-' }}
                            </div>
                        </div>
                        <span class="portal-status {{ $requestItem->assignment_status === 'completed' ? 'is-success' : ($requestItem->assignment_status === 'in_progress' ? 'is-info' : 'is-warning') }}">
                            {{ $assignmentStatuses[$requestItem->assignment_status] ?? 'Assignee' }}
                        </span>
                    </div>

                    <div class="portal-mini-list" style="margin-top:14px;">
                        <div class="portal-mini-item">
                            <span class="portal-status is-muted">Statut client</span>
                            <div class="portal-mini-copy">{{ $requestStatuses[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}</div>
                        </div>
                        <div class="portal-mini-item">
                            <span class="portal-status is-info">Pipeline</span>
                            <div class="portal-mini-copy">{{ $pipelineStages[$requestItem->pipeline_stage] ?? 'Nouvelle demande' }}</div>
                        </div>
                        <div class="portal-mini-item">
                            <span class="portal-status is-warning">Relances</span>
                            <div class="portal-mini-copy">{{ $requestItem->client_alerts_count }} relance(s) liee(s)</div>
                        </div>
                    </div>

                    @if($requestItem->missions)
                        <div class="portal-note" style="margin-top:14px;">
                            <strong style="display:block; margin-bottom:6px;">Besoin exprime</strong>
                            {{ \Illuminate\Support\Str::limit($requestItem->missions, 260) }}
                        </div>
                    @endif

                    @if($requestItem->clientAlerts->isNotEmpty())
                        <div class="portal-subsection">
                            <strong class="portal-subtitle">Dernieres relances clients</strong>
                            <div class="portal-mini-list">
                                @foreach($requestItem->clientAlerts as $alert)
                                    <div class="portal-mini-item">
                                        <span class="portal-status {{ $alert->status === 'processed' ? 'is-success' : ($alert->status === 'viewed' ? 'is-info' : 'is-warning') }}">
                                            {{ \App\Models\ClientRequestAlert::availableStatuses()[$alert->status] ?? $alert->status }}
                                        </span>
                                        <div class="portal-mini-copy">
                                            {{ $alert->message ?: 'Relance sans message complementaire.' }}
                                            @if($alert->admin_response)
                                                <div class="portal-note portal-inline-note">{{ $alert->admin_response }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="portal-form-actions" style="margin-top:16px; justify-content:flex-start;">
                        <a href="{{ route('employee.recruitment-requests.show', $requestItem) }}" class="admin-btn admin-btn-ghost portal-btn-auto">Ouvrir</a>
                    </div>
                </article>
            @empty
                <div class="portal-empty">
                    <div class="portal-empty-title">Aucune demande assignee</div>
                    <div class="portal-empty-copy">Les demandes de recrutement qui vous seront attribuees apparaitront ici avec leurs relances associees.</div>
                </div>
            @endforelse
        </div>
        <div style="margin-top:18px;">
            {{ $requests->links() }}
        </div>
    </section>
@endsection
