@extends('dashboard.layouts.app')

@section('title', 'Rapports employe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Rapports d activite')
@section('page_title', 'Mes rapports d activite')
@section('page_copy', 'Envoyez vos rapports quotidiens ou hebdomadaires, ajoutez une piece jointe si besoin et suivez le traitement admin.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ $reports->where('status', 'pending')->count() }} en attente</span>
@endsection

@section('content')
    <div class="portal-split">
        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Nouveau rapport</h3>
            <p class="portal-copy" style="margin-bottom:18px;">Un format simple, structurant et 100 % en francais pour votre suivi interne.</p>

            <form method="POST" action="{{ route('employee.reports.store') }}" class="portal-form-grid" enctype="multipart/form-data">
                @csrf
                <div>
                    <label for="report_type">Type de rapport</label>
                    <select id="report_type" name="report_type">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('report_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="report_date">Date du rapport</label>
                    <input id="report_date" name="report_date" type="date" value="{{ old('report_date', now()->toDateString()) }}">
                </div>
                <div class="full">
                    <label for="title">Titre</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Ex: Point hebdomadaire recrutement">
                </div>
                <div class="full">
                    <label for="summary">Description generale</label>
                    <textarea id="summary" name="summary" rows="5" placeholder="Resume des actions menees">{{ old('summary') }}</textarea>
                </div>
                <div class="full">
                    <label for="achievements">Realisations</label>
                    <textarea id="achievements" name="achievements" rows="4" placeholder="Taches finalisees, resultats, livrables">{{ old('achievements') }}</textarea>
                </div>
                <div>
                    <label for="blockers">Points de vigilance</label>
                    <textarea id="blockers" name="blockers" rows="4" placeholder="Blocages, retards, besoins">{{ old('blockers') }}</textarea>
                </div>
                <div>
                    <label for="next_steps">Actions suivantes</label>
                    <textarea id="next_steps" name="next_steps" rows="4" placeholder="Prochaines etapes prevues">{{ old('next_steps') }}</textarea>
                </div>
                <div class="full">
                    <label for="attachment">Piece jointe (optionnelle)</label>
                    <input id="attachment" name="attachment" type="file">
                </div>
                <div class="full form-actions-inline">
                    <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Envoyer le rapport</button>
                </div>
            </form>
        </section>

        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Historique</h3>
            <div class="portal-timeline">
                @forelse($reports as $report)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <div>
                                <strong>{{ $report->title ?: ($types[$report->report_type] ?? ucfirst($report->report_type)) }}</strong>
                                <div class="portal-copy">Date: {{ $report->report_date?->format('d/m/Y') }}</div>
                            </div>
                            <span class="portal-status {{ $report->status === 'validated' ? 'is-success' : ($report->status === 'reviewed' ? 'is-info' : 'is-warning') }}">
                                {{ $statuses[$report->status] ?? $report->status }}
                            </span>
                        </div>
                        <p class="portal-record-copy">{{ $report->summary }}</p>
                        @if($report->admin_notes)
                            <div class="portal-note">{{ $report->admin_notes }}</div>
                        @endif
                        @if($report->attachment_path)
                            <div class="portal-copy" style="margin-top:10px;">Piece jointe enregistree.</div>
                        @endif
                    </article>
                @empty
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucun rapport envoye</div>
                        <div class="portal-empty-copy">Votre premier rapport apparaitra ici apres soumission.</div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
