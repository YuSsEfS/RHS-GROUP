@extends('admin.layouts.app')

@php
    $requestDate = optional($recruitmentRequest->request_date)->format('d/m/Y') ?: $recruitmentRequest->created_at->format('d/m/Y');
    $matchesCount = $recruitmentRequest->matches_count ?? 0;
@endphp

@section('title', 'Gerer la demande client')
@section('page_title', 'Gerer la demande client')
@section('page_subtitle', 'Mettre a jour le statut et les notes visibles cote client.')

@section('top_actions')
    <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $recruitmentRequest->id]) }}" class="btn btn-primary">
        Lancer le matching
    </a>
@endsection

@section('content')
    <div class="admin-card" style="padding:24px;">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-bottom:24px;">
            <span class="admin-chip">{{ $statuses[$recruitmentRequest->request_status] ?? $recruitmentRequest->request_status }}</span>
            <span class="admin-chip" style="background:rgba(15,23,42,.06); color:#0f172a; border-color:rgba(15,23,42,.10);">
                {{ $matchesCount }} match(es) generes
            </span>
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
            <h3 style="margin:0 0 10px;">Missions / connaissances</h3>
            <p style="margin:0 0 12px; color:#334155; line-height:1.7;">{{ $recruitmentRequest->missions ?: 'Aucune mission precisee.' }}</p>
            <p style="margin:0; color:#334155; line-height:1.7;"><strong>Connaissances specifiques :</strong> {{ $recruitmentRequest->specific_knowledge ?: '-' }}</p>
        </div>

        <form method="POST" action="{{ route('admin.client-recruitment-requests.update', $recruitmentRequest) }}">
            @csrf
            @method('PUT')

            <div style="display:grid; gap:18px;">
                <div>
                    <label class="admin-label" for="request_status">Statut</label>
                    <select class="admin-input" id="request_status" name="request_status" style="width:100%; height:44px;">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('request_status', $recruitmentRequest->request_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="admin-label" for="admin_notes">Notes admin visibles au client</label>
                    <textarea class="admin-input" id="admin_notes" name="admin_notes" rows="6" style="width:100%; padding:14px;">{{ old('admin_notes', $recruitmentRequest->admin_notes) }}</textarea>
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:24px; flex-wrap:wrap;">
                <button type="submit" class="admin-btn admin-btn-primary">Enregistrer</button>
                <a href="{{ route('admin.recruitment_requests.create', ['client_request' => $recruitmentRequest->id]) }}" class="admin-btn admin-btn-ghost">Ouvrir dans AI Matching</a>
                <a href="{{ route('admin.client-recruitment-requests.index') }}" class="admin-btn admin-btn-ghost">Retour</a>
            </div>
        </form>
    </div>
@endsection
