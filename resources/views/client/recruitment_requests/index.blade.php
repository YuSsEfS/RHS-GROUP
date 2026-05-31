@extends('dashboard.layouts.app')

@section('title', 'Historique des demandes')
@section('brand', 'RHS Client')
@section('brand_sub', 'Portail recrutement')
@section('page_title', 'Historique de mes demandes')
@section('page_copy', 'Consultez toutes vos demandes sous forme de liste, avec acces au detail et au suivi des relances.')

@section('sidebar')
    @include('client._sidebar')
@endsection

@section('top_badge')
    <a href="{{ route('client.recruitment-requests.create') }}" class="admin-btn admin-btn-primary portal-btn-auto">Nouvelle demande</a>
@endsection

@section('content')
    <section class="portal-card">
        <div class="portal-timeline">
            @forelse($requests as $requestItem)
                <article class="portal-record">
                    <div class="portal-record-top">
                        <div>
                            <strong>{{ $requestItem->position_title }}</strong>
                            <div class="portal-copy">
                                Reference: {{ $requestItem->reference ?: '-' }} |
                                Date: {{ optional($requestItem->request_date)->format('d/m/Y') ?: $requestItem->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <span class="portal-status {{ in_array($requestItem->request_status, ['completed', 'shortlisted']) ? 'is-success' : (in_array($requestItem->request_status, ['under_review', 'matching_in_progress']) ? 'is-info' : 'is-warning') }}">
                            {{ $statuses[$requestItem->request_status] ?? ucfirst(str_replace('_', ' ', $requestItem->request_status)) }}
                        </span>
                    </div>

                    <div class="portal-mini-list" style="margin-top:14px;">
                        <div class="portal-mini-item">
                            <span class="portal-status is-muted">Etape</span>
                            <div class="portal-mini-copy">{{ $pipelineStages[$requestItem->pipeline_stage] ?? 'Nouvelle demande' }}</div>
                        </div>
                        <div class="portal-mini-item">
                            <span class="portal-status is-info">Relances</span>
                            <div class="portal-mini-copy">{{ $requestItem->client_alerts_count }} relance(s)</div>
                        </div>
                    </div>

                    <div class="portal-form-actions" style="margin-top:16px; justify-content:flex-start;">
                        <a href="{{ route('client.recruitment-requests.show', $requestItem) }}" class="admin-btn admin-btn-ghost portal-btn-auto">Ouvrir</a>
                    </div>
                </article>
            @empty
                <div class="portal-empty">
                    <div class="portal-empty-title">Aucune demande pour le moment</div>
                    <div class="portal-empty-copy">Votre historique apparaitra ici apres votre premiere demande.</div>
                </div>
            @endforelse
        </div>

        <div style="margin-top:18px;">
            {{ $requests->links() }}
        </div>
    </section>
@endsection
