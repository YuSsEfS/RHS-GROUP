@extends('admin.layouts.app')

@section('title', 'Demandes de conge')
@section('page_title', 'Demandes de conge')
@section('page_subtitle', 'Validez ou refusez les absences employees.')

@section('content')
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-body">
            <form method="GET" class="table-controls">
                <div class="table-filter">
                    <select name="employee">
                        <option value="all">Tous les employes</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($employeeId === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-filter">
                    <select name="status">
                        <option value="all">Tous les statuts</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-filter"><input class="admin-input" type="date" name="from" value="{{ $from }}" style="height:44px;"></div>
                <div class="table-filter"><input class="admin-input" type="date" name="to" value="{{ $to }}" style="height:44px;"></div>
                <div class="table-ctrl-actions"><button class="btn btn-primary btn-sm" type="submit">Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><div class="panel-title">Demandes de conge <span class="panel-badge">{{ $leaveRequests->total() }}</span></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employe</th>
                        <th>Type</th>
                        <th>Periode</th>
                        <th>Statut</th>
                        <th>Motif</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $leaveRequest)
                        <tr>
                            <td>{{ $leaveRequest->user?->name }}</td>
                            <td>{{ \App\Models\EmployeeLeaveRequest::availableTypes()[$leaveRequest->leave_type] ?? $leaveRequest->leave_type }}</td>
                            <td>{{ $leaveRequest->start_date?->format('d/m/Y') }} - {{ $leaveRequest->end_date?->format('d/m/Y') }}</td>
                            <td><span class="pill {{ $leaveRequest->status === 'approved' ? 'pill-success' : ($leaveRequest->status === 'pending' ? 'pill-neutral' : 'pill-danger') }}">{{ $statuses[$leaveRequest->status] ?? $leaveRequest->status }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($leaveRequest->reason, 90) }}</td>
                            <td class="td-actions"><a href="{{ route('admin.employee-leave-requests.edit', $leaveRequest) }}" class="btn btn-primary btn-sm">Gerer</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><div class="table-empty-title">Aucune demande de conge trouvee.</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">{{ $leaveRequests->links() }}</div>
@endsection
