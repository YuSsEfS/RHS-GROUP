@extends('admin.layouts.app')

@section('title', 'Rapport employe')
@section('page_title', 'Rapport employe')
@section('page_subtitle', 'Consultez le detail d un rapport, ajoutez des notes et validez le traitement.')

@section('content')
    <div class="panel">
        <div class="panel-head">
            <div class="panel-title">{{ $report->title ?: ucfirst($report->report_type) }}</div>
        </div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Employe</div><div class="info-value">{{ $report->user?->name }}</div></div>
                <div class="info-item"><div class="info-label">Date</div><div class="info-value">{{ $report->report_date?->format('d/m/Y') }}</div></div>
                <div class="info-item"><div class="info-label">Type</div><div class="info-value">{{ \App\Models\EmployeeReport::availableTypes()[$report->report_type] ?? ucfirst($report->report_type) }}</div></div>
                <div class="info-item"><div class="info-label">Statut</div><div class="info-value">{{ $statuses[$report->status] ?? $report->status }}</div></div>
            </div>

            <div class="divider"></div>
            <div class="message-box">
                <div class="message-title">Resume</div>
                <div class="message-content">{{ $report->summary }}</div>
            </div>

            @if($report->achievements)
                <div class="message-box" style="margin-top:16px;">
                    <div class="message-title">Realisations</div>
                    <div class="message-content">{{ $report->achievements }}</div>
                </div>
            @endif

            @if($report->blockers)
                <div class="message-box" style="margin-top:16px;">
                    <div class="message-title">Blocages</div>
                    <div class="message-content">{{ $report->blockers }}</div>
                </div>
            @endif

            @if($report->next_steps)
                <div class="message-box" style="margin-top:16px;">
                    <div class="message-title">Prochaines etapes</div>
                    <div class="message-content">{{ $report->next_steps }}</div>
                </div>
            @endif

            @if($report->attachment_path)
                <div class="message-box" style="margin-top:16px;">
                    <div class="message-title">Piece jointe</div>
                    <div class="message-content">Une piece jointe a ete enregistree avec ce rapport.</div>
                </div>
            @endif

            @if($report->admin_notes)
                <div class="message-box" style="margin-top:16px;">
                    <div class="message-title">Notes admin actuelles</div>
                    <div class="message-content">{{ $report->admin_notes }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.employee-reports.review', $report) }}" style="margin-top:24px;">
                @csrf
                @method('PATCH')
                <div style="display:grid; gap:18px;">
                    <div>
                        <label class="admin-label" for="status">Statut</label>
                        <select class="admin-input" id="status" name="status" style="width:100%; height:44px;">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="admin_notes">Notes admin</label>
                        <textarea class="admin-input" id="admin_notes" name="admin_notes" rows="5" style="width:100%; padding:14px;">{{ old('admin_notes', $report->admin_notes) }}</textarea>
                    </div>
                </div>

                <div class="action-row" style="margin-top:24px;">
                    <button type="submit" class="admin-btn admin-btn-primary" style="width:auto;">Enregistrer</button>
                    <a href="{{ route('admin.employee-reports.index') }}" class="admin-btn admin-btn-ghost" style="width:auto;">Retour</a>
                </div>
            </form>
        </div>
    </div>
@endsection
