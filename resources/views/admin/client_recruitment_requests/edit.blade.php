@extends('admin.layouts.app')

@php
    $requestDate = optional($recruitmentRequest->request_date)->format('d/m/Y') ?: $recruitmentRequest->created_at->format('d/m/Y');
    $matchesCount = $recruitmentRequest->matches_count ?? 0;
    $alertsCount = $recruitmentRequest->client_alerts_count ?? 0;
@endphp

@section('title', 'Gerer la demande client')
@section('page_title', 'Gerer la demande client')
@section('page_subtitle', 'Pilotez le statut client, l assignation employe, le pipeline et les notes visibles ou internes.')

@section('top_actions')
    <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $recruitmentRequest->id]) }}" class="btn btn-primary">
        Lancer le matching
    </a>
@endsection

@section('content')
    <div class="admin-card" style="padding:24px;">
        <div class="action-row" style="margin-bottom:24px;">
            <span class="admin-chip">{{ $statuses[$recruitmentRequest->request_status] ?? $recruitmentRequest->request_status }}</span>
            <span class="admin-chip" style="background:rgba(15,23,42,.06); color:#0f172a; border-color:rgba(15,23,42,.10);">
                {{ $matchesCount }} match(es)
            </span>
            <span class="admin-chip" style="background:rgba(59,130,246,.10); color:#1d4ed8; border-color:rgba(59,130,246,.18);">
                {{ $pipelineStages[$recruitmentRequest->pipeline_stage] ?? 'Nouvelle demande' }}
            </span>
            @if($recruitmentRequest->matching_job_status)
                <span class="admin-chip" style="background:rgba(251,191,36,.12); color:#92400e; border-color:rgba(251,191,36,.18);">
                    Traitement: {{ $jobStatuses[$recruitmentRequest->matching_job_status] ?? $recruitmentRequest->matching_job_status }}
                </span>
            @endif
            @if($alertsCount > 0)
                <span class="admin-chip" style="background:rgba(239,68,68,.12); color:#991b1b; border-color:rgba(239,68,68,.18);">
                    {{ $alertsCount }} relance(s)
                </span>
            @endif
            @if($matchesCount > 0)
                <a href="{{ route('admin.recruitment_requests.results', ['recruitmentRequest' => $recruitmentRequest->id, 'offer' => $recruitmentRequest->job_offer_id ?: 'all', 'folder' => $recruitmentRequest->cv_folder_id ?: 'all']) }}" class="btn btn-ghost">
                    Voir les resultats
                </a>
            @endif
        </div>

        <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-bottom:24px;">
            <div class="admin-card" style="padding:18px;">
                <h3 style="margin:0 0 10px;">Informations client</h3>
                <div><strong>Nom :</strong> {{ $recruitmentRequest->client_name ?: $recruitmentRequest->clientUser?->name ?: '-' }}</div>
                <div><strong>Email :</strong> {{ $recruitmentRequest->clientUser?->email ?: '-' }}</div>
                <div><strong>Date :</strong> {{ $requestDate }}</div>
                <div><strong>Reference :</strong> {{ $recruitmentRequest->reference ?: '-' }}</div>
            </div>

            <div class="admin-card" style="padding:18px;">
                <h3 style="margin:0 0 10px;">Besoin exprime</h3>
                <div><strong>Poste :</strong> {{ $recruitmentRequest->position_title }}</div>
                <div><strong>Lieu :</strong> {{ $recruitmentRequest->work_location ?: '-' }}</div>
                <div><strong>Experience :</strong> {{ $recruitmentRequest->experience_years ?: '-' }}</div>
                <div><strong>Contrat :</strong> {{ $recruitmentRequest->contract_type ?: '-' }}</div>
            </div>
        </div>

        <div class="admin-card" style="padding:18px; margin-bottom:24px;">
            <h3 style="margin:0 0 10px;">Missions et connaissances</h3>
            <p style="margin:0 0 12px; color:#334155; line-height:1.7;">{{ $recruitmentRequest->missions ?: 'Aucune mission precisee.' }}</p>
            <p style="margin:0; color:#334155; line-height:1.7;"><strong>Connaissances specifiques :</strong> {{ $recruitmentRequest->specific_knowledge ?: '-' }}</p>
        </div>

        @if($recruitmentRequest->matching_job_status === \App\Models\RecruitmentRequest::JOB_STATUS_PENDING)
            <div class="admin-alert" style="margin-bottom:18px;">Le matching est planifie et en attente d execution.</div>
        @elseif($recruitmentRequest->matching_job_status === \App\Models\RecruitmentRequest::JOB_STATUS_RUNNING)
            <div class="admin-alert" style="margin-bottom:18px;">Le matching est en cours en arriere-plan. La navigation reste disponible.</div>
        @elseif($recruitmentRequest->matching_job_status === \App\Models\RecruitmentRequest::JOB_STATUS_FAILED)
            <div class="admin-alert admin-alert-danger" style="margin-bottom:18px;">Le matching a echoue. {{ $recruitmentRequest->matching_error_message ?: 'Consultez les logs et relancez le traitement.' }}</div>
        @endif

        <form method="POST" action="{{ route('admin.client-recruitment-requests.update', $recruitmentRequest) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display:grid; gap:18px;">
                <div class="admin-card request-logo-edit-card" style="padding:16px;">
                    <div class="request-logo-cell">
                        <span class="request-logo-thumb request-logo-thumb-lg">
                            @if($recruitmentRequest->logo_url)
                                <img src="{{ $recruitmentRequest->logo_url }}" alt="{{ $recruitmentRequest->client_name }}">
                            @else
                                {{ strtoupper(substr($recruitmentRequest->client_name ?: 'R', 0, 1)) }}
                            @endif
                        </span>
                        <div class="form-field" style="flex:1; min-width:0;">
                            <label for="logo">Logo / image de la demande</label>
                            <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                            <div class="form-help">Optionnel. JPG, PNG ou WEBP, 2 Mo max.</div>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px;">
                    <div>
                        <label class="admin-label" for="request_status">Statut client</label>
                        <select class="admin-input" id="request_status" name="request_status" style="width:100%; height:44px;">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('request_status', $recruitmentRequest->request_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="admin-label" for="pipeline_stage">Etape pipeline</label>
                        <select class="admin-input" id="pipeline_stage" name="pipeline_stage" style="width:100%; height:44px;">
                            @foreach($pipelineStages as $value => $label)
                                <option value="{{ $value }}" @selected(old('pipeline_stage', $recruitmentRequest->pipeline_stage ?: \App\Models\RecruitmentRequest::PIPELINE_STAGE_NEW) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px;">
                    <div>
                        <label class="admin-label" for="assigned_employee_id">Employe assigne</label>
                        <select class="admin-input" id="assigned_employee_id" name="assigned_employee_id" style="width:100%; height:44px;">
                            <option value="">Aucune assignation</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) old('assigned_employee_id', $recruitmentRequest->assigned_employee_id) === (int) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="admin-label" for="assignment_status">Statut de tache</label>
                        <select class="admin-input" id="assignment_status" name="assignment_status" style="width:100%; height:44px;">
                            <option value="">Selectionner</option>
                            @foreach($assignmentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('assignment_status', $recruitmentRequest->assignment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="admin-label" for="admin_notes">Notes admin visibles au client</label>
                    <textarea class="admin-input" id="admin_notes" name="admin_notes" rows="5" style="width:100%; padding:14px;">{{ old('admin_notes', $recruitmentRequest->admin_notes) }}</textarea>
                </div>

                <div>
                    <label class="admin-label" for="employee_notes">Notes internes / retour employe</label>
                    <textarea class="admin-input" id="employee_notes" name="employee_notes" rows="5" style="width:100%; padding:14px;">{{ old('employee_notes', $recruitmentRequest->employee_notes) }}</textarea>
                </div>
            </div>

            <div class="action-row" style="margin-top:24px;">
                <button type="submit" class="admin-btn admin-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $recruitmentRequest->id]) }}" class="admin-btn admin-btn-ghost">Ouvrir dans AI Matching</a>
                <a href="{{ route('admin.client-request-alerts.index', ['request' => $recruitmentRequest->id]) }}" class="admin-btn admin-btn-ghost">Voir les relances</a>
                <a href="{{ route('admin.client-recruitment-requests.index') }}" class="admin-btn admin-btn-ghost">Retour</a>
            </div>
        </form>
    </div>
@endsection
