@extends('dashboard.layouts.app')

@section('title', 'Conges')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Demandes de conge')
@section('page_title', 'Mes demandes de conge')
@section('page_copy', 'Envoyez vos demandes de conge, consultez les decisions et suivez les notes de l administration.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ $leaveRequests->where('status', 'pending')->count() }} en attente</span>
@endsection

@section('content')
    <div class="portal-split">
        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Nouvelle demande de conge</h3>

            <form method="POST" action="{{ route('employee.leave-requests.store') }}" class="portal-form-grid">
                @csrf
                <div>
                    <label for="leave_type">Type de conge</label>
                    <select id="leave_type" name="leave_type">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('leave_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div></div>
                <div>
                    <label for="start_date">Date de debut</label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}">
                </div>
                <div>
                    <label for="end_date">Date de fin</label>
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}">
                </div>
                <div class="full">
                    <label for="reason">Motif</label>
                    <textarea id="reason" name="reason" rows="5" placeholder="Expliquez le contexte de votre absence">{{ old('reason') }}</textarea>
                </div>
                <div class="full form-actions-inline">
                    <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Envoyer la demande</button>
                </div>
            </form>
        </section>

        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Historique</h3>
            <div class="portal-timeline">
                @forelse($leaveRequests as $leaveRequest)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <div>
                                <strong>{{ $types[$leaveRequest->leave_type] ?? $leaveRequest->leave_type }}</strong>
                                <div class="portal-copy">{{ $leaveRequest->start_date?->format('d/m/Y') }} -> {{ $leaveRequest->end_date?->format('d/m/Y') }}</div>
                            </div>
                            <span class="portal-status {{ $leaveRequest->status === 'approved' ? 'is-success' : ($leaveRequest->status === 'rejected' || $leaveRequest->status === 'cancelled' ? 'is-danger' : 'is-warning') }}">
                                {{ $statuses[$leaveRequest->status] ?? $leaveRequest->status }}
                            </span>
                        </div>
                        <p class="portal-record-copy">{{ $leaveRequest->reason }}</p>
                        @if($leaveRequest->admin_notes)
                            <div class="portal-note">{{ $leaveRequest->admin_notes }}</div>
                        @endif
                        @if($leaveRequest->status === 'pending')
                            <form method="POST" action="{{ route('employee.leave-requests.cancel', $leaveRequest) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="admin-btn admin-btn-ghost portal-btn-auto">Annuler la demande</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucune demande de conge</div>
                        <div class="portal-empty-copy">Vos demandes apparaitront ici des la premiere soumission.</div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
