@extends('layouts.app')
@section('title', $offer->title . ' – RHS GROUP')

@section('content')
<div class="job-detail-page">

  {{-- ================= HERO ================= --}}
  @php
    use Illuminate\Support\Facades\Storage;

    $defaultHero = asset('images/IMAGE DE slogan.png');

    // ✅ Use Storage URL (works better on subfolders/cPanel than asset('storage/...'))
   $hero = !empty($offer->hero_image)
  ? route('public.file', ['path' => $offer->hero_image])
  : $defaultHero;

  @endphp

  <section class="job-detail-hero" style="--hero-url: url('{{ $hero }}');">
    <div class="container job-hero-inner">

      <p class="job-detail-eyebrow" data-cms-key="job.detail.hero.eyebrow">
        OFFRE D’EMPLOI
      </p>

      <h1 class="job-detail-title">
        {{ $offer->title }}
      </h1>

      <div class="job-detail-meta">
        <span class="meta-item">
          <span class="meta-ico" aria-hidden="true">📍</span>
          <span class="meta-text">{{ $offer->location ?? '—' }}</span>
        </span>

        <span class="meta-item">
          <span class="meta-ico" aria-hidden="true">🧾</span>
          <span class="meta-text">{{ $offer->contract_type ?? '—' }}</span>
        </span>

        <span class="meta-item">
          <span class="meta-ico" aria-hidden="true">🏢</span>
          <span class="meta-text">{{ $offer->company ?? 'RHS GROUP' }}</span>
        </span>

        @if(!empty($offer->sector))
          <span class="meta-item meta-item-soft">
            <span class="meta-ico" aria-hidden="true">🏷️</span>
            <span class="meta-text">{{ $offer->sector }}</span>
          </span>
        @endif

        @if(!empty($offer->published_at))
          <span class="meta-item meta-item-soft">
            <span class="meta-ico" aria-hidden="true">🗓️</span>
            <span class="meta-text">
              Publié le {{ $offer->published_at?->format('d/m/Y') }}
            </span>
          </span>
        @endif
      </div>

      <div class="job-detail-actions">
        <a href="{{ route('apply') }}?offer={{ $offer->id }}"
           class="btn-primary"
           data-cms-key="job.detail.hero.btn_apply">
          Postuler
          <span class="btn-ico" aria-hidden="true">→</span>
        </a>

        <a href="{{ route('offres') }}"
           class="btn-outline"
           data-cms-key="job.detail.hero.btn_back">
          Retour aux offres
        </a>
      </div>

    </div>
  </section>

  {{-- ================= BODY ================= --}}
  <section class="job-detail-body">
    <div class="container job-detail-grid">

      {{-- MAIN --}}
      <div class="job-detail-main">

        <div class="job-section">
          <h2 data-cms-key="job.detail.section.description">Description</h2>
          <div class="job-detail-rich">
            {!! nl2br(e($offer->description)) !!}
          </div>
        </div>

        @if($offer->missions)
          <div class="job-section">
            <h2 data-cms-key="job.detail.section.missions">Missions</h2>
            <div class="job-detail-rich">
              {!! nl2br(e($offer->missions)) !!}
            </div>
          </div>
        @endif

        @if($offer->requirements)
          <div class="job-section">
            <h2 data-cms-key="job.detail.section.profile">Profil recherché</h2>
            <div class="job-detail-rich">
              {!! nl2br(e($offer->requirements)) !!}
            </div>
          </div>
        @endif

      </div>

      {{-- SIDEBAR --}}
      <aside class="job-detail-side">

        <div class="job-detail-card is-sticky">
          <div class="card-head">
            <h3 data-cms-key="job.detail.sidebar.info_title">Informations</h3>
            <span class="card-chip">Détails</span>
          </div>

          <ul class="info-list">
            <li>
              <span class="info-label" data-cms-key="job.detail.sidebar.location_label">Localisation</span>
              <span class="info-value">{{ $offer->location ?? '—' }}</span>
            </li>

            <li>
              <span class="info-label" data-cms-key="job.detail.sidebar.contract_label">Contrat</span>
              <span class="info-value">{{ $offer->contract_type ?? '—' }}</span>
            </li>

            <li>
              <span class="info-label" data-cms-key="job.detail.sidebar.sector_label">Secteur</span>
              <span class="info-value">{{ $offer->sector ?? '—' }}</span>
            </li>

            <li>
              <span class="info-label" data-cms-key="job.detail.sidebar.published_label">Publié le</span>
              <span class="info-value">
                {{ $offer->published_at?->format('d/m/Y') ?? '—' }}
              </span>
            </li>
          </ul>

          <div class="side-cta">
            <a href="{{ route('apply') }}?offer={{ $offer->id }}"
               class="btn-primary full"
               data-cms-key="job.detail.sidebar.btn_apply">
              Déposer ma candidature
              <span class="btn-ico" aria-hidden="true">→</span>
            </a>
          </div>
        </div>

        <div class="job-detail-card">
          <div class="card-head">
            <h3 data-cms-key="job.detail.sidebar.apply_title">Postuler</h3>
            <span class="card-chip card-chip-soft">CV & Infos</span>
          </div>

          <p class="side-text" data-cms-key="job.detail.sidebar.apply_text">
            Envoyez votre CV via le formulaire de candidature. Nous reviendrons vers vous si votre profil correspond.
          </p>

          <a href="{{ route('apply') }}?offer={{ $offer->id }}"
             class="btn-outline full"
             data-cms-key="job.detail.sidebar.btn_form">
            Accéder au formulaire
          </a>
        </div>

      </aside>

    </div>
  </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/job-detail.css') }}">
@endpush
