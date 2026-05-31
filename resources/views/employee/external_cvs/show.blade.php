@extends('dashboard.layouts.app')

@section('title', 'Lot externe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Base externe')
@section('page_title', 'Lot externe')
@section('page_copy', 'Consultez les fichiers du lot externe sans acceder aux fonctions admin globales.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <a href="{{ route('employee.external-cvs.index') }}" class="btn btn-ghost">Retour</a>
@endsection

@section('content')
    <div class="panel panel-safe">
        <div class="panel-head">
            <div>
                <div class="panel-title text-safe">{{ $batch->name }}</div>
                <div class="muted">{{ $batch->folder?->name ?? 'Sans dossier' }}</div>
            </div>
            <span class="pill pill-neutral">{{ $batch->progressPercentage() }}%</span>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('employee.external-cvs.show', $batch) }}" class="ui-filter-grid">
                <div class="form-field">
                    <label class="form-label" for="q">Recherche</label>
                    <input id="q" name="q" class="form-input" value="{{ $q }}" placeholder="Nom, candidat, email...">
                </div>
                <div class="form-field">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select select-theme">
                        <option value="all" @selected($status === 'all')>Tous</option>
                        <option value="pending" @selected($status === 'pending')>En attente</option>
                        <option value="indexed" @selected($status === 'indexed')>Indexe</option>
                        <option value="duplicate" @selected($status === 'duplicate')>Doublon</option>
                        <option value="failed" @selected($status === 'failed')>Echec</option>
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">&nbsp;</label>
                    <div class="action-bar">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="{{ route('employee.external-cvs.show', $batch) }}" class="btn btn-ghost">Reinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-safe table-safe">
        <div class="panel-head">
            <div class="panel-title">Fichiers <span class="panel-badge">{{ $files->total() }}</span></div>
        </div>
        <div class="panel-body" style="padding:0;">
            @if($files->count())
                <div class="table-wrap">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Fichier</th>
                                <th>Candidat</th>
                                <th>Email</th>
                                <th>Ville</th>
                                <th>Poste</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr>
                                    <td class="text-safe">{{ $file->original_filename }}</td>
                                    <td class="text-safe">{{ $file->candidate_name ?: '-' }}</td>
                                    <td class="text-safe">{{ $file->email ?: '-' }}</td>
                                    <td class="text-safe">{{ $file->city ?: '-' }}</td>
                                    <td class="text-safe">{{ $file->current_title ?: '-' }}</td>
                                    <td>
                                        <span class="cv-badge {{ $file->status === 'failed' ? 'status-inactive' : 'source-legacy' }}">
                                            {{ ucfirst($file->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('employee.external-cvs.files.open', $file) }}" class="btn btn-ghost" target="_blank" rel="noopener">Ouvrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
                    {{ $files->links() }}
                </div>
            @else
                <div class="ui-empty-state">
                    <div class="ui-empty-title">Aucun fichier</div>
                    <div class="ui-empty-copy">Aucun fichier ne correspond a ces filtres.</div>
                </div>
            @endif
        </div>
    </div>
@endsection
