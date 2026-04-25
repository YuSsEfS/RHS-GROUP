@extends('admin.layouts.app')

@section('title','Admin – Résultats du matching')
@section('page_title','Résultats du matching')

@section('page_subtitle')
Classement des CV compatibles avec l’offre choisie
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.recruitment_requests.create') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M15 18l-6-6 6-6"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"/>
      </svg>
    </span>
    Retour
  </a>
@endsection

@push('styles')
<style>
  .match-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:18px}
  .match-chip{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid rgba(15,23,42,.08);border-radius:999px;background:#fff;font-weight:800;color:#0f172a}
  .match-chip span{color:#64748b;font-weight:700}
  .match-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap}
  .match-score{display:inline-flex;align-items:center;justify-content:center;min-width:72px;padding:8px 12px;border-radius:999px;font-weight:900;background:rgba(239,68,68,.08);color:#dc2626}
  .match-summary{max-width:560px;color:#334155;line-height:1.55}
  .match-breakdown{margin-top:10px;display:flex;flex-wrap:wrap;gap:8px}
  .match-tag{display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:800}
  .match-empty{padding:30px 10px;text-align:center;color:#64748b;font-weight:700}
  .match-candidate{display:flex;flex-direction:column;gap:4px}
  .match-candidate strong{color:#0f172a;font-size:16px}
  .match-candidate small{color:#64748b;font-weight:700}
  .match-checkbox{width:20px;height:20px;accent-color:#ef4444;cursor:pointer}
  .match-select-cell{width:120px}
  .match-actions-cell{width:250px}
  .match-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
  .match-ai-form{display:inline-block;margin:0}
  .match-status-row{margin-top:12px;display:flex;flex-wrap:wrap;gap:8px}
  .match-status{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800}
  .match-status-local{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
  .match-status-ai{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
  .match-status-neutral{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
  .ai-btn.loading{pointer-events:none;opacity:.8}

  .match-filter-grid{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:end;
  }

  .match-filter-item{
    display:grid;
    gap:6px;
  }

  .match-filter-item label{
    font-size:12px;
    font-weight:800;
    color:#64748b;
  }

  .match-filter-item select{
    min-width:210px;
    height:42px;
    padding:0 12px;
    border:1px solid #dbe2ea;
    border-radius:12px;
    background:#fff;
    color:#0f172a;
    font-weight:700;
    outline:none;
  }

  .match-filter-item select:focus{
    border-color:#94a3b8;
    box-shadow:0 0 0 4px rgba(148,163,184,.14);
  }

  @media(max-width:800px){
    .match-filter-item,
    .match-filter-item select{
      width:100%;
    }
  }
</style>
@endpush

@section('content')

@php
  $breakdownLabels = [
    'title_fit' => 'Adéquation du poste',
    'education_fit' => 'Formation',
    'experience_fit' => 'Expérience',
    'age_fit' => 'Âge',
    'skills_fit' => 'Compétences',
    'language_fit' => 'Langues',
    'location_fit' => 'Localisation',
    'availability_fit' => 'Disponibilité',
    'overall_consistency' => 'Cohérence globale',
  ];

  $currentOffer = request('offer', $offerId ?? ($recruitmentRequest->job_offer_id ?: 'all'));
  $currentFolder = request('folder', $folderId ?? 'all');
@endphp

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Résultats du matching
      <span class="panel-badge">{{ $recruitmentRequest->jobOffer?->title ?? $recruitmentRequest->position_title ?? 'Poste' }}</span>
    </div>

    <div class="panel-tools">
      <form method="GET"
            class="match-filter-grid"
            action="{{ route('admin.recruitment_requests.results', $recruitmentRequest) }}"
            autocomplete="off">

        <div class="match-filter-item">
          <label for="offer">Offre liée</label>
          <select name="offer" id="offer" onchange="this.form.submit()">
            <option value="all" {{ (string) $currentOffer === 'all' ? 'selected' : '' }}>
              Toutes les offres
            </option>

            @foreach(($offers ?? collect()) as $o)
              <option value="{{ $o->id }}" {{ (string) $currentOffer === (string) $o->id ? 'selected' : '' }}>
                {{ $o->title }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="match-filter-item">
          <label for="folder">Dossier CV Bank</label>
          <select name="folder" id="folder" onchange="this.form.submit()">
            <option value="all" {{ (string) $currentFolder === 'all' ? 'selected' : '' }}>
              Tous les dossiers
            </option>

            @foreach(($folders ?? collect()) as $folder)
              <option value="{{ $folder->id }}" {{ (string) $currentFolder === (string) $folder->id ? 'selected' : '' }}>
                {{ $folder->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="table-ctrl-actions">
          <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
          <a class="btn btn-ghost btn-sm" href="{{ route('admin.recruitment_requests.results', $recruitmentRequest) }}">
            Réinitialiser
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="panel-body">
    <div class="match-meta">
      <div class="match-chip"><span>CV trouvés :</span>{{ count($matches) }}</div>
      <div class="match-chip"><span>Offre :</span>{{ $recruitmentRequest->jobOffer?->title ?? '—' }}</div>
      <div class="match-chip"><span>Dossier :</span>
        @if((string) $currentFolder === 'all')
          Tous
        @else
          {{ optional(($folders ?? collect())->firstWhere('id', (int) $currentFolder))->name ?? '—' }}
        @endif
      </div>
      <div class="match-chip"><span>Référence :</span>{{ $recruitmentRequest->reference ?: '—' }}</div>
      <div class="match-chip"><span>Sélectionnés :</span>{{ $matches->where('selected', true)->count() }}</div>
    </div>

    <div class="match-toolbar">
      <div></div>
      <a class="btn btn-primary" href="{{ route('admin.recruitment_requests.downloadSelected', $recruitmentRequest) }}">
        Télécharger les CV sélectionnés
      </a>
    </div>

    <div class="table-wrap" style="margin-top:18px;">
      <table class="table">
        <thead>
          <tr>
            <th>Candidat</th>
            <th>Email</th>
            <th>Dossier</th>
            <th>Score final</th>
            <th>Résumé</th>
            <th class="match-actions-cell">Actions</th>
            <th class="match-select-cell">Sélection</th>
          </tr>
        </thead>

        <tbody>
          @forelse($matches as $match)
            @php
              $fullBreakdown = is_array($match->score_breakdown ?? null)
                  ? $match->score_breakdown
                  : (json_decode($match->score_breakdown ?? '[]', true) ?: []);

              $meta = is_array($fullBreakdown['_meta'] ?? null) ? $fullBreakdown['_meta'] : [];
              unset($fullBreakdown['_meta']);

              $localScore = isset($meta['local_score']) ? (float) $meta['local_score'] : null;
              $aiScore = array_key_exists('ai_score', $meta) && $meta['ai_score'] !== null ? (float) $meta['ai_score'] : null;
              $finalScore = isset($meta['final_score']) ? (float) $meta['final_score'] : (float) $match->score;
              $aiAvailable = (bool) ($meta['ai_available'] ?? false);
              $lastAnalysis = $meta['last_analysis'] ?? null;
            @endphp

            <tr>
              <td>
                <div class="match-candidate">
                  <strong>{{ $match->cv->candidate_name ?? 'Candidat inconnu' }}</strong>
                  <small>{{ $match->cv->phone ?? 'Téléphone non disponible' }}</small>
                </div>
              </td>

              <td>
                <span class="pill pill-neutral">{{ $match->cv->email ?? '—' }}</span>
              </td>

              <td>
                <span class="pill pill-neutral">{{ $match->cv->folder?->name ?? '—' }}</span>
              </td>

              <td>
                <span class="match-score">{{ number_format($finalScore, 0) }}%</span>
              </td>

              <td>
                <div class="match-summary">
                  {{ $match->summary ?: 'Résumé non disponible.' }}
                </div>

                <div class="match-status-row">
                  @if($aiAvailable)
                    <span class="match-status match-status-ai">Matching IA validé : {{ number_format($aiScore ?? 0, 0) }}%</span>
                  @elseif(!is_null($aiScore))
                    <span class="match-status match-status-local">Matching avancé estimé : {{ number_format($aiScore, 0) }}%</span>
                  @else
                    <span class="match-status match-status-local">Score local</span>
                  @endif

                  @if(!is_null($localScore))
                    <span class="match-status match-status-neutral">Local : {{ number_format($localScore, 0) }}%</span>
                  @endif

                  <span class="match-status match-status-neutral">Final : {{ number_format($finalScore, 0) }}%</span>

                  @if($lastAnalysis)
                    <span class="match-status match-status-neutral">Analyse : {{ $lastAnalysis }}</span>
                  @endif
                </div>

                @if(!empty($fullBreakdown))
                  <div class="match-breakdown">
                    @foreach($fullBreakdown as $key => $value)
                      <span class="match-tag">
                        {{ $breakdownLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }} :
                        {{ rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') }}
                      </span>
                    @endforeach
                  </div>
                @endif
              </td>

              <td class="match-actions-cell">
                <div class="match-actions">
                  <a class="btn btn-light btn-sm"
                     href="{{ route('admin.cvs.open', $match->cv) }}"
                     target="_blank"
                     rel="noopener">
                    Ouvrir
                  </a>

                  <form method="POST"
                        action="{{ route('admin.matches.analyzeAi', $match) }}"
                        class="match-ai-form js-ai-form">
                    @csrf

                    <input type="hidden" name="folder" value="{{ $currentFolder }}">
                    <input type="hidden" name="offer" value="{{ $currentOffer }}">

                    <button type="submit" class="btn btn-primary btn-sm ai-btn">
                      Analyser avec IA
                    </button>
                  </form>
                </div>
              </td>

              <td class="match-select-cell">
                <form method="POST" action="{{ route('admin.matches.toggleSelection', $match) }}">
                  @csrf
                  <input type="hidden" name="selected" value="0">
                  <input type="checkbox"
                         name="selected"
                         value="1"
                         class="match-checkbox"
                         onchange="this.form.submit()"
                         {{ $match->selected ? 'checked' : '' }}>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7">
                <div class="match-empty">Aucun résultat disponible.</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-ai-form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const btn = form.querySelector('.ai-btn');

      if (btn) {
        btn.classList.add('loading');
        btn.textContent = 'Analyse IA...';
      }
    });
  });
});
</script>
@endpush