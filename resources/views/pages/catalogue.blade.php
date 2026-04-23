@extends('layouts.app')
@section('title','Catalogue de formations – RHS GROUP')

@section('content')
<div class="jobs-page">

  {{-- ================= HERO ================= --}}
  <section class="catalogue-hero">
    <div class="container jobs-hero-inner">
      <div class="jobs-hero-content">
        <p class="jobs-eyebrow">CATALOGUE DE FORMATION</p>
        <h1 class="jobs-title">
          Découvrez nos <span>formations</span>
        </h1>
        <p class="jobs-subtitle">
          RHS GROUP accompagne entreprises et professionnels avec des formations pratiques et certifiantes.
        </p>
      </div>
    </div>
  </section>

  {{-- ================= LIST & FILTERS ================= --}}
  <section class="jobs-list" id="formations-list">
    <div class="container">

      <form class="jobs-filters" method="GET" action="{{ url()->current() }}" autocomplete="off">
        <div class="jobs-filters-row">

          {{-- Search --}}
          <div class="jobs-filter jobs-filter-search">
            <label class="sr-only" for="q">Rechercher</label>
            <input type="text" id="q" name="q" placeholder="Rechercher une formation..." value="{{ request('q') }}">
          </div>

          {{-- Domain --}}
          <div class="jobs-filter">
            <label class="sr-only" for="domain">Domaine</label>
            <select id="domain" name="domain">
              <option value="">Tous les domaines</option>
              @foreach($domains as $d)
                <option value="{{ $d }}" {{ request('domain') == $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Public --}}
          <div class="jobs-filter">
            <label class="sr-only" for="public">Public</label>
            <select id="public" name="public">
              <option value="">Tous les publics</option>
              @foreach($publics as $p)
                <option value="{{ $p }}" {{ request('public') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Format --}}
          <div class="jobs-filter">
            <label class="sr-only" for="format">Format</label>
            <select id="format" name="format">
              <option value="">Tous les formats</option>
              @foreach($formats as $f)
                <option value="{{ $f }}" {{ request('format') == $f ? 'selected' : '' }}>{{ ucfirst($f) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Actions --}}
          <div class="jobs-filter-actions">
            <button type="submit" class="jobs-filter-btn">Filtrer</button>
            <a href="{{ url()->current() }}" class="jobs-filter-reset">Réinitialiser</a>
          </div>
        </div>
      </form>

      {{-- ================= GRID ================= --}}
     <div class="formations-grid">

@forelse($formations as $f)
<article class="formation-card">

    {{-- TAGS --}}
    <div class="formation-tags">
        @if($f->domain)
            <span class="formation-tag">{{ $f->domain }}</span>
        @endif

        @if($f->certification)
            <span class="formation-badge">
                {{ $f->certification }}
            </span>
        @endif
    </div>

    {{-- TITLE --}}
    <h3 class="formation-title">
        {{ $f->title }}
    </h3>

    {{-- DESCRIPTION --}}
    <p class="formation-desc">
        {{ Str::limit($f->excerpt ?? $f->description, 150) }}
    </p>

    {{-- INFOS --}}
    <div class="formation-infos">
        @if($f->duration)
            <span>⏱ {{ $f->duration }}</span>
        @endif

        @if($f->level)
            <span>👥 {{ $f->level }}</span>
        @endif

        @if($f->format)
            <span>💻 {{ $f->format }}</span>
        @endif
    </div>

    {{-- CTA --}}
    <a href="{{ route('formations.show', $f->id) }}" class="formation-btn">
    Voir la formation →
</a>



</article>
@empty

<div class="formations-empty">
    <div class="jobs-empty-icon">📭</div>
    <h3>Aucune formation disponible</h3>
    <p>Revenez bientôt, de nouvelles formations seront publiées.</p>
</div>

@endforelse
</div>


      @if($formations->hasPages())
        <div class="jobs-pagination">
          {{ $formations->links() }}
        </div>
      @endif

    </div>
  </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jobs.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogue.css') }}">
@endpush
