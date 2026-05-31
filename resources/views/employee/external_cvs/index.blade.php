@extends('dashboard.layouts.app')

@section('title', 'Base externe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Base externe')
@section('page_title', 'Base externe')
@section('page_copy', 'Consultez les lots externes autorises par l administration RHS.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('content')
    <div class="panel panel-safe">
        <div class="panel-head">
            <div>
                <div class="panel-title">Filtres</div>
                <div class="muted">Recherche par lot ou statut.</div>
            </div>
            @if($canManageExternalCvs)
                <span class="pill pill-neutral">Permission gestion active</span>
            @endif
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('employee.external-cvs.index') }}" class="ui-filter-grid">
                <div class="form-field">
                    <label class="form-label" for="q">Recherche</label>
                    <input id="q" name="q" class="form-input" value="{{ $q }}" placeholder="Nom du lot ou notes...">
                </div>
                <div class="form-field">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select select-theme">
                        <option value="all" @selected($status === 'all')>Tous</option>
                        @foreach(\App\Models\ExternalCvBatch::availableStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">&nbsp;</label>
                    <div class="action-bar">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="{{ route('employee.external-cvs.index') }}" class="btn btn-ghost">Reinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-safe table-safe">
        <div class="panel-head">
            <div class="panel-title">Lots externes <span class="panel-badge">{{ $batches->total() }}</span></div>
        </div>
        <div class="panel-body" style="padding:0;">
            @if($batches->count())
                <div class="table-wrap">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Lot</th>
                                <th>Dossier</th>
                                <th>Statut</th>
                                <th>Fichiers</th>
                                <th>Creé le</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                                <tr>
                                    <td class="text-safe"><strong>{{ $batch->name }}</strong></td>
                                    <td class="text-safe">{{ $batch->folder?->name ?? '-' }}</td>
                                    <td><span class="cv-badge source-legacy">{{ \App\Models\ExternalCvBatch::availableStatuses()[$batch->status] ?? $batch->status }}</span></td>
                                    <td>{{ $batch->cvs_count }}</td>
                                    <td>{{ optional($batch->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('employee.external-cvs.show', $batch) }}" class="btn btn-ghost">Ouvrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
                    {{ $batches->links() }}
                </div>
            @else
                <div class="ui-empty-state">
                    <div class="ui-empty-title">Aucun lot externe accessible</div>
                    <div class="ui-empty-copy">Les lots externes autorises apparaitront ici.</div>
                </div>
            @endif
        </div>
    </div>
@endsection
