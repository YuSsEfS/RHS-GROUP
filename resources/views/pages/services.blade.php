@extends('layouts.app')
@section('title','Nos Services – RHS GROUP')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/services.css') }}">
@endpush

@section('content')

<div class="services-page">

    {{-- ================= HERO ================= --}}
    <section class="services-hero" data-animate>
        <div class="container">
            <p class="services-eyebrow" data-cms-key="services.hero.eyebrow">NOS EXPERTISES</p>

            <h1 class="services-title" data-cms-key="services.hero.title">
                Des solutions RH <br><span data-cms-key="services.hero.title_span">complètes & sur- <br>mesure</span>
            </h1>

            <p class="services-subtitle" data-cms-key="services.hero.subtitle">
                RHS GROUP accompagne entreprises et talents avec une approche globale,
                humaine et orientée performance.
            </p>
        </div>
    </section>

    {{-- ================= SERVICES GRID ================= --}}
    <section class="services-grid-section">
        <div class="container services-grid">

            {{-- SERVICE 1 --}}
            <div class="service-card" data-animate>
                <div class="service-icon">
                    <img src="{{ asset('images/icons/employment.svg') }}" alt="" data-cms-img="services.card1.icon">
                </div>

                <h3 data-cms-key="services.card1.title">Travail temporaire & Intérim</h3>

                <p data-cms-key="services.card1.desc">
                    Mise à disposition rapide de profils qualifiés pour répondre à vos besoins
                    opérationnels immédiats, tous secteurs confondus.
                </p>

                <ul>
                    <li data-cms-key="services.card1.li1">Missions temporaires</li>
                    <li data-cms-key="services.card1.li2">CDI intérimaire</li>
                    <li data-cms-key="services.card1.li3">Flexibilité & réactivité</li>
                </ul>

                <a href="https://rhsemploi.ma" target="_blank" class="service-btn" data-cms-key="services.card1.btn">
                    Découvrir RHS Emploi
                </a>
            </div>

            {{-- SERVICE 2 --}}
            <div class="service-card featured" data-animate>
                <div class="service-icon">
                    <img src="{{ asset('images/icons/formation.svg') }}" alt="" data-cms-img="services.card2.icon">
                </div>

                <h3 data-cms-key="services.card2.title">Recrutement, Formation & Coaching</h3>

                <p data-cms-key="services.card2.desc">
                    Identification, développement et accompagnement des talents
                    à travers des dispositifs modernes et personnalisés.
                </p>

                <ul>
                    <li data-cms-key="services.card2.li1">Recrutement CDI / CDD</li>
                    <li data-cms-key="services.card2.li2">Formation professionnelle</li>
                    <li data-cms-key="services.card2.li3">Coaching individuel & collectif</li>
                </ul>

                <a href="https://openact.ma" target="_blank" class="service-btn" data-cms-key="services.card2.btn">
                    Découvrir Open Act
                </a>
            </div>

            {{-- SERVICE 3 --}}
            <div class="service-card" data-animate>
                <div class="service-icon">
                    <img src="{{ asset('images/icons/agri.svg') }}" alt="" data-cms-img="services.card3.icon">
                </div>

                <h3 data-cms-key="services.card3.title">Solutions RH – Secteur Agricole</h3>

                <p data-cms-key="services.card3.desc">
                    Expertise RH dédiée au monde agricole, avec une parfaite maîtrise
                    des contraintes terrain et saisonnières.
                </p>

                <ul>
                    <li data-cms-key="services.card3.li1">Main-d’œuvre saisonnière</li>
                    <li data-cms-key="services.card3.li2">Postes permanents</li>
                    <li data-cms-key="services.card3.li3">Expertise agricole</li>
                </ul>

                <a href="https://rhsprofil.com" target="_blank" class="service-btn" data-cms-key="services.card3.btn">
                    Découvrir RHS Profil
                </a>
            </div>

        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="services-cta" data-animate>
        <div class="container services-cta-inner">
            <h2 data-cms-key="services.cta.title">
                Un besoin RH précis ?
                <span data-cms-key="services.cta.title_span">Parlons-en.</span>
            </h2>

            <p data-cms-key="services.cta.desc">
                Nos équipes vous orientent vers la solution la plus adaptée
                à vos enjeux et à votre secteur.
            </p>

            <a href="{{ route('contact') }}" class="btn-primary" data-cms-key="services.cta.btn">
                Contactez-nous
            </a>
        </div>
    </section>

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/services.js') }}" defer></script>
@endpush
