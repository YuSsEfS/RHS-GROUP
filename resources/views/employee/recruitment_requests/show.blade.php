@extends('dashboard.layouts.app')

@section('title', 'Demande assignee')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Suivi recrutement')
@section('page_title', 'Detail de la demande assignee')
@section('page_copy', 'Suivez la demande, lancez le matching si autorise, et mettez a jour votre progression.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <a href="{{ route('employee.recruitment-requests.index') }}" class="admin-btn admin-btn-ghost portal-btn-auto">Retour a la liste</a>
@endsection

@section('content')
    <div class="portal-split">
        <section class="portal-card">
            <div class="portal-record-top">
                <div>
                    <strong>{{ $requestItem->position_title }}</strong>
                    <div class="portal-copy">Client: {{ $requestItem->client_name ?: $requestItem->clientUser?->name ?: '-' }}</div>
                </div>
                <span class="portal-status {{ $requestItem->assignment_status === 'completed' ? 'is-success' : ($requestItem->assignment_status === 'in_progress' ? 'is-info' : 'is-warning') }}">
                    {{ $assignmentStatuses[$requestItem->assignment_status] ?? 'Assignee' }}
                </span>
            </div>

            <div class="portal-mini-list" style="margin-top:16px;">
                <div class="portal-mini-item"><span class="portal-status is-muted">Reference</span><div class="portal-mini-copy">{{ $requestItem->reference ?: '-' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-info">Statut client</span><div class="portal-mini-copy">{{ $requestStatuses[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-warning">Pipeline</span><div class="portal-mini-copy">{{ $pipelineStages[$requestItem->pipeline_stage] ?? 'Nouvelle demande' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-muted">Lieu</span><div class="portal-mini-copy">{{ $requestItem->work_location ?: '-' }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-muted">Date</span><div class="portal-mini-copy">{{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}</div></div>
                <div class="portal-mini-item"><span class="portal-status is-info">Matches</span><div class="portal-mini-copy">{{ $requestItem->matches_count }} resultat(s)</div></div>
            </div>

            @if($requestItem->missions)
                <div class="portal-note" style="margin-top:16px;">
                    <strong style="display:block; margin-bottom:6px;">Besoin exprime</strong>
                    {{ $requestItem->missions }}
                </div>
            @endif

            <div class="portal-form-actions" style="margin-top:16px; justify-content:flex-start;">
                @if($canUpdateRecruitmentAssignments)
                    <form method="POST" action="{{ route('employee.recruitment-requests.launch-matching', $requestItem) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Lancer le matching</button>
                    </form>
                @endif
                @if($requestItem->matches_count > 0)
                    <a href="{{ route('employee.recruitment-requests.results', ['recruitmentRequest' => $requestItem->id, 'offer' => $requestItem->job_offer_id ?: 'all', 'folder' => $requestItem->cv_folder_id ?: 'all']) }}" class="admin-btn admin-btn-ghost portal-btn-auto">Voir les resultats</a>
                @endif
            </div>
        </section>

        <section class="portal-card">
            @if($canUpdateRecruitmentAssignments)
                <form method="POST" action="{{ route('employee.recruitment-requests.update', $requestItem) }}" class="portal-form-grid">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="assignment_status">Statut de tache</label>
                        <select id="assignment_status" name="assignment_status">
                            @foreach($assignmentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(($requestItem->assignment_status ?: \App\Models\RecruitmentRequest::ASSIGNMENT_STATUS_ASSIGNED) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="pipeline_stage">Etape pipeline</label>
                        <select id="pipeline_stage" name="pipeline_stage">
                            @foreach($pipelineStages as $value => $label)
                                <option value="{{ $value }}" @selected(($requestItem->pipeline_stage ?: \App\Models\RecruitmentRequest::PIPELINE_STAGE_NEW) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="full">
                        <label for="employee_notes">Notes employe</label>
                        <textarea id="employee_notes" name="employee_notes" rows="6">{{ old('employee_notes', $requestItem->employee_notes) }}</textarea>
                    </div>
                    <div class="full portal-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Mettre a jour</button>
                    </div>
                </form>
            @else
                <div class="portal-note">Votre role actuel vous permet seulement la consultation de cette demande.</div>
            @endif
        </section>
    </div>

    @if($requestItem->clientAlerts->isNotEmpty())
        <section class="portal-card portal-card--spaced">
            <h3 class="portal-title-tight">Relances clients</h3>
            <div class="chat-thread chat-thread-scroll">
                @foreach($requestItem->clientAlerts as $alert)
                    <div class="chat-bubble chat-bubble-client">
                        <div class="chat-meta">Client - {{ $alert->created_at->format('d/m/Y H:i') }}</div>
                        <div class="chat-body">{{ $alert->message ?: 'Relance sans message complementaire.' }}</div>
                    </div>
                    @if($alert->admin_response)
                        <div class="chat-bubble chat-bubble-admin">
                            <div class="chat-meta">RHS - {{ optional($alert->responded_at)->format('d/m/Y H:i') ?: $alert->updated_at->format('d/m/Y H:i') }}</div>
                            <div class="chat-body">{{ $alert->admin_response }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
@endsection
