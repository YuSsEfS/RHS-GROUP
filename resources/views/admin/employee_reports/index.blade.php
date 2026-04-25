@extends('admin.layouts.app')

@section('title', 'Rapports employes')
@section('page_title', 'Rapports employes')
@section('page_subtitle', 'Filtrez, consultez et mettez a jour les rapports employes.')

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
                <div class="table-filter">
                    <input class="admin-input" type="date" name="date" value="{{ $date }}" style="height:44px;">
                </div>
                <div class="table-ctrl-actions">
                    <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div class="panel-title">Rapports recus <span class="panel-badge">{{ $reports->total() }}</span></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employe</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Resume</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->user?->name }}</td>
                            <td>{{ \App\Models\EmployeeReport::availableTypes()[$report->report_type] ?? ucfirst($report->report_type) }}</td>
                            <td>{{ $report->report_date?->format('d/m/Y') }}</td>
                            <td><span class="pill {{ $report->status === 'validated' ? 'pill-success' : ($report->status === 'reviewed' ? 'pill-neutral' : 'pill-danger') }}">{{ $statuses[$report->status] ?? $report->status }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($report->summary, 90) }}</td>
                            <td class="td-actions">
                                <a href="{{ route('admin.employee-reports.show', $report) }}" class="btn btn-primary btn-sm">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><div class="table-empty-title">Aucun rapport trouve.</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">{{ $reports->links() }}</div>
@endsection
