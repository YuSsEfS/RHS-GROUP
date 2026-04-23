@extends('layouts.app')
@section('title','Offres d’emploi – RHS GROUP')

@section('content')
<div class="jobs-page">

  {{-- ================= HERO ================= --}}
  <section class="jobs-hero">
    <div class="container jobs-hero-inner">
      <div class="jobs-hero-content">
        <p class="jobs-eyebrow" data-cms-key="jobs.hero.eyebrow">OPPORTUNITÉS DE CARRIÈRE</p>

        <h1 class="jobs-title" data-cms-key="jobs.hero.title">
          Trouvez l’emploi<br>
          qui vous <span data-cms-key="jobs.hero.title_span">ressemble</span>
        </h1>

        <p class="jobs-subtitle" data-cms-key="jobs.hero.subtitle">
          RHS GROUP accompagne les talents vers des opportunités durables, adaptées à leurs compétences.
        </p>

        {{-- Optional: small scroll hint --}}
        <div class="jobs-hero-actions">
  <a href="#jobs-list" class="jobs-hero-cta">
    Voir les offres
    <span class="jobs-hero-cta-icon">↓</span>
  </a>

  <a href="{{ route('apply') }}?type=spontaneous"
     class="jobs-hero-cta jobs-hero-cta--secondary">
    Candidature spontanée
    <span class="jobs-hero-cta-icon">✉</span>
  </a>
</div>

      </div>
    </div>
  </section>

  {{-- ================= LIST ================= --}}
  <section class="jobs-list" id="jobs-list">
    <div class="container">
    {{-- ================= FILTERS ================= --}}
<form class="jobs-filters" method="GET" action="{{ url()->current() }}" autocomplete="off">
  <div class="jobs-filters-row">

    {{-- Search --}}
  <div class="jobs-filter jobs-filter-search">
  <label class="sr-only" for="q">Rechercher</label>

  <div class="jobs-search-wrap">
    <input
      id="q"
      type="text"
      name="q"
      value="{{ request('q') }}"
      placeholder="Rechercher (titre, entreprise)..."
      autocomplete="off"
    >
    <div id="jobsSuggest" class="jobs-suggest" hidden></div>
  </div>
</div>


    {{-- Location --}}
    <div class="jobs-filter">
      <label class="sr-only" for="location">Lieu</label>
      <select id="location" name="location">
        <option value="">Tous les lieux</option>
        @foreach(($locations ?? collect()) as $loc)
          <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>
            {{ $loc }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Contract --}}
    <div class="jobs-filter">
      <label class="sr-only" for="contract">Contrat</label>
      <select id="contract" name="contract">
        <option value="">Tous contrats</option>
        @foreach(($contracts ?? collect()) as $c)
          <option value="{{ $c }}" {{ request('contract') === $c ? 'selected' : '' }}>
            {{ $c }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Sector --}}
    <div class="jobs-filter">
      <label class="sr-only" for="sector">Secteur</label>
      <select id="sector" name="sector">
        <option value="">Tous secteurs</option>
        @foreach(($sectors ?? collect()) as $s)
          <option value="{{ $s }}" {{ request('sector') === $s ? 'selected' : '' }}>
            {{ $s }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Sort --}}
    <div class="jobs-filter">
      <label class="sr-only" for="sort">Tri</label>
      <select id="sort" name="sort">
        <option value="new" {{ request('sort','new') === 'new' ? 'selected' : '' }}>Plus récentes</option>
        <option value="old" {{ request('sort') === 'old' ? 'selected' : '' }}>Plus anciennes</option>
      </select>
    </div>

    <div class="jobs-filter-actions">
      <button type="submit" class="jobs-filter-btn">Filtrer</button>
      <a class="jobs-filter-reset" href="{{ url()->current() }}">Réinitialiser</a>
    </div>

  </div>
</form>

      <div class="jobs-grid">
        @forelse($offers as $offer)
          <article class="job-card">

            {{-- Top --}}
            <div class="job-card-top">
              <div class="job-main">
                <div class="job-title-row">
                  <h3 class="job-title">{{ $offer->title }}</h3>

                  {{-- optional: tiny badge (if sector exists) --}}
                  @if(!empty($offer->sector))
                    <span class="job-badge">{{ $offer->sector }}</span>
                  @endif
                </div>

                <p class="job-company">{{ $offer->company ?? 'RHS GROUP' }}</p>

                @if(!empty($offer->excerpt))
                  <p class="job-desc">{{ $offer->excerpt }}</p>
                @endif
              </div>

              {{-- Meta pills --}}
              <div class="job-meta">
                <span class="job-pill">
                  <span class="job-pill-dot"></span>
                  <span class="job-pill-text">{{ $offer->location ?? '—' }}</span>
                </span>

                <span class="job-pill">
                  <span class="job-pill-dot"></span>
                  <span class="job-pill-text">{{ $offer->contract_type ?? '—' }}</span>
                </span>

                @if(!empty($offer->sector))
                  <span class="job-pill is-soft">
                    <span class="job-pill-dot"></span>
                    <span class="job-pill-text">{{ $offer->sector }}</span>
                  </span>
                @endif
              </div>
            </div>

            {{-- Bottom --}}
            <div class="job-card-bottom">
              <div class="job-date">
                <span class="job-date-label" data-cms-key="jobs.card.published_label">Publié :</span>
                <span class="job-date-value">
{{ optional($offer->published_at)->format('d/m/Y') ?? '—' }}
                </span>
              </div>

              <a href="{{ route('offres.show', $offer->slug) }}"
                 class="job-btn"
                 data-cms-key="jobs.card.btn">
                Voir l’offre
                <span class="job-btn-icon">→</span>
              </a>
            </div>

          </article>
        @empty
          <div class="jobs-empty">
            <div class="jobs-empty-icon">📭</div>
            <h3 data-cms-key="jobs.empty.title">Aucune offre disponible</h3>
            <p data-cms-key="jobs.empty.desc">Revenez bientôt, de nouvelles opportunités seront publiées.</p>
          </div>
        @endforelse
      </div>

      @if(method_exists($offers, 'links'))
        <div class="jobs-pagination">
          {{ $offers->links() }}
        </div>
      @endif

    </div>
  </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jobs.css') }}">
@endpush
@push('scripts')
<script>
(() => {
  const input = document.getElementById('q');
  const box   = document.getElementById('jobsSuggest');
  if (!input || !box) return;

  let t = null;
  let aborter = null;

  const hide = () => { box.hidden = true; box.innerHTML = ''; };
  const show = () => { box.hidden = false; };

  const esc = (s) => String(s)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');

  const render = (items) => {
    if (!items || !items.length) return hide();

    box.innerHTML = items.map(it => `
      <a class="jobs-suggest-item" href="{{ url('/offres') }}/${encodeURIComponent(it.slug)}">
        <div class="jobs-suggest-title">${esc(it.title || '')}</div>
        ${it.meta ? `<div class="jobs-suggest-meta">${esc(it.meta)}</div>` : ``}
      </a>
    `).join('');

    show();
  };

  const fetchSuggest = async (q) => {
    if (aborter) aborter.abort();
    aborter = new AbortController();

const url = "{{ route('jobs.suggest') }}" + "?q=" + encodeURIComponent(q);
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' },
      signal: aborter.signal
    });

    if (!res.ok) return hide();
    render(await res.json());
  };

  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (q.length < 2) return hide();

    clearTimeout(t);
    t = setTimeout(() => fetchSuggest(q).catch(hide), 160);
  });

  input.addEventListener('focus', () => {
    const q = input.value.trim();
    if (q.length >= 2) fetchSuggest(q).catch(hide);
  });

  document.addEventListener('click', (e) => {
    if (!box.contains(e.target) && e.target !== input) hide();
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') hide();
  });
})();
</script>
@endpush
