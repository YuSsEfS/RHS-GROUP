@extends('admin.layouts.app')

@section('title', 'Gerer la demande de conge')
@section('page_title', 'Gerer la demande de conge')
@section('page_subtitle', 'Mettre a jour la decision visible par l employe.')

@section('content')
    <div class="panel">
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Employe</div><div class="info-value">{{ $leaveRequest->user?->name }}</div></div>
                <div class="info-item"><div class="info-label">Type</div><div class="info-value">{{ \App\Models\EmployeeLeaveRequest::availableTypes()[$leaveRequest->leave_type] ?? $leaveRequest->leave_type }}</div></div>
                <div class="info-item"><div class="info-label">Debut</div><div class="info-value">{{ $leaveRequest->start_date?->format('d/m/Y') }}</div></div>
                <div class="info-item"><div class="info-label">Fin</div><div class="info-value">{{ $leaveRequest->end_date?->format('d/m/Y') }}</div></div>
            </div>

            <div class="message-box" style="margin-top:18px;">
                <div class="message-title">Motif</div>
                <div class="message-content">{{ $leaveRequest->reason }}</div>
            </div>

            <form method="POST" action="{{ route('admin.employee-leave-requests.update', $leaveRequest) }}" style="margin-top:24px;">
                @csrf
                @method('PUT')
                <div style="display:grid; gap:18px;">
                    <div>
                        <label class="admin-label" for="status">Statut</label>
                        <select class="admin-input" id="status" name="status" style="width:100%; height:44px;">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $leaveRequest->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="admin_notes">Notes admin</label>
                        <textarea class="admin-input" id="admin_notes" name="admin_notes" rows="6" style="width:100%; padding:14px;">{{ old('admin_notes', $leaveRequest->admin_notes) }}</textarea>
                    </div>
                </div>

                <div class="action-row" style="margin-top:24px;">
                    <button type="submit" class="admin-btn admin-btn-primary" style="width:auto;">Enregistrer</button>
                    <a href="{{ route('admin.employee-leave-requests.index') }}" class="admin-btn admin-btn-ghost" style="width:auto;">Retour</a>
                </div>
            </form>
        </div>
    </div>
@endsection
