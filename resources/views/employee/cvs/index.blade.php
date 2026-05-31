@extends('dashboard.layouts.app')

@section('title', 'CV Bank')
@section('brand', 'RHS Employe')
@section('brand_sub', 'CV Bank')
@section('page_title', 'CV Bank')
@section('page_copy', 'Consultez les CV autorises par l administration RHS. Les donnees restent strictement internes.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('content')
    <div class="panel panel-safe">
        <div class="panel-head">
            <div>
                <div class="panel-title">Filtres</div>
                <div class="muted">Recherche, source et dossier.</div>
            </div>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('employee.cvs.index') }}" class="ui-filter-grid">
                <div class="form-field">
                    <label class="form-label" for="q">Recherche</label>
                    <input id="q" name="q" class="form-input" value="{{ $q }}" placeholder="Nom, email, telephone, poste...">
                </div>
                <div class="form-field">
                    <label class="form-label" for="source">Source</label>
                    <select id="source" name="source" class="form-select select-theme">
                        <option value="all" @selected($source === 'all')>Toutes</option>
                        <option value="application" @selected($source === 'application')>Candidatures</option>
                        <option value="external_db" @selected($source === 'external_db')>Base externe</option>
                        <option value="manual" @selected($source === 'manual')>Ajout manuel</option>
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="folder">Dossier</label>
                    <select id="folder" name="folder" class="form-select select-theme">
                        <option value="all" @selected($folder === 'all')>Tous</option>
                        @foreach($folders as $folderItem)
                            <option value="{{ $folderItem->id }}" @selected((string) $folder === (string) $folderItem->id)>{{ $folderItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">&nbsp;</label>
                    <div class="action-bar">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="{{ route('employee.cvs.index') }}" class="btn btn-ghost">Reinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-safe table-safe">
        <div class="panel-head">
            <div class="panel-title">Liste des CV <span class="panel-badge">{{ $cvListTotal }}</span></div>
            @if($canManageCvBank)
                <span class="pill pill-neutral">Permission gestion active</span>
            @endif
        </div>
        <div class="panel-body" style="padding:0;">
            @if($cvs->count())
                <div class="table-wrap">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Candidat</th>
                                <th>Contact</th>
                                <th>Poste</th>
                                <th>Ville</th>
                                <th>Source</th>
                                <th>Dossier</th>
                                <th>Fichier</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cvs as $cv)
                                <tr>
                                    <td class="text-safe"><strong>{{ $cv->candidate_name ?: '-' }}</strong></td>
                                    <td class="text-safe">
                                        {{ $cv->email ?: '-' }}
                                        <div class="muted">{{ $cv->phone ?: '-' }}</div>
                                    </td>
                                    <td class="text-safe">{{ $cv->current_title ?: data_get($cv->structured_profile, 'title', '-') }}</td>
                                    <td class="text-safe">{{ $cv->city ?: data_get($cv->structured_profile, 'city', '-') }}</td>
                                    <td><span class="cv-badge source-external">{{ $cv->display_source }}</span></td>
                                    <td class="text-safe">{{ $cv->folder?->name ?? '-' }}</td>
                                    <td class="text-safe">{{ $cv->original_filename ?: '-' }}</td>
                                    <td>
                                        <a href="{{ route('employee.cvs.open', $cv) }}" class="btn btn-ghost" target="_blank" rel="noopener">Ouvrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:18px 20px;border-top:1px solid #eef2f7;">
                    {{ $cvs->links() }}
                </div>
            @else
                <div class="ui-empty-state">
                    <div class="ui-empty-title">Aucun CV accessible</div>
                    <div class="ui-empty-copy">Aucun CV ne correspond a vos filtres ou a vos droits actuels.</div>
                </div>
            @endif
        </div>
    </div>
@endsection
