@extends('dashboard.layouts.app')

@section('title', 'Relances clients')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Suivi des demandes')
@section('page_title', 'Relances clients')
@section('page_copy', 'Vue lecture seule des relances envoyees par les clients sur leurs demandes de recrutement.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ $alerts->total() }} relance(s)</span>
@endsection

@section('content')
    <section class="portal-card">
        <div class="portal-timeline">
            @forelse($alerts as $alert)
                <article class="portal-record">
                    <div class="portal-record-top">
                        <div>
                            <strong>{{ $alert->clientUser?->name ?: 'Client' }}</strong>
                            <div class="portal-copy">Demande: {{ $alert->recruitmentRequest?->position_title ?: '-' }}</div>
                        </div>
                        <span class="portal-status {{ $alert->status === 'processed' ? 'is-success' : ($alert->status === 'viewed' ? 'is-info' : 'is-warning') }}">
                            {{ $statuses[$alert->status] ?? $alert->status }}
                        </span>
                    </div>
                    <p class="portal-record-copy">{{ $alert->message ?: 'Relance sans message complementaire.' }}</p>
                    @if($alert->admin_response)
                        <div class="portal-note">{{ $alert->admin_response }}</div>
                    @endif
                </article>
            @empty
                <div class="portal-empty">
                    <div class="portal-empty-title">Aucune relance client</div>
                    <div class="portal-empty-copy">Les relances apparaitront ici lorsqu un client sollicitera un suivi sur une demande.</div>
                </div>
            @endforelse
        </div>

        <div style="margin-top:18px;">{{ $alerts->links() }}</div>
    </section>
@endsection
