@extends('layouts.app')
@section('title','RHS GROUP – Accueil')

@section('content')

{{-- HERO: full-viewport slider --}}
<section id="hero-slider" class="hero hero--home" data-animate>
    <div class="hero-track">

        {{-- Slide 1: Slogan principal --}}
        <article class="slide"
                 data-cms-img="home.hero.slide1.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide1.bg', asset('images/IMAGE DE slogan.png')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    {{-- Signature: keep your JS behavior + store the text in cms --}}
                    <h1 class="signature"
                        data-signature="{{ cms('home.hero.slide1.signature', 'L’Humain d’abord, l’Excellence toujours') }}"
                        data-cms-key="home.hero.slide1.signature"></h1>

                    <p class="lead" data-cms-key="home.hero.slide1.lead">
                        {{ cms('home.hero.slide1.lead', "Parce que RHS Group croit que chaque talent mérite d’être valorisé et que chaque entreprise mérite l’excellence sur mesure.") }}
                    </p>

                    <div class="hero-buttons">
                        <a href="{{ route('services') }}"
                           class="btn-primary"
                           data-cms-key="home.hero.slide1.btn1">
                            {{ cms('home.hero.slide1.btn1', 'Découvrir nos services') }}
                        </a>

                        <a href="{{ route('contact') }}"
                           class="btn-outline"
                           data-cms-key="home.hero.slide1.btn2">
                            {{ cms('home.hero.slide1.btn2', 'Contactez-nous') }}
                        </a>
                    </div>
                </div>
            </div>
        </article>

        {{-- Slide 2: Travail temporaire --}}
        <article class="slide"
                 data-cms-img="home.hero.slide2.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide2.bg', asset('images/ChatGPT Image Jan 19, 2026, 05_23_33 PM.png')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    <h2 data-cms-key="home.hero.slide2.title">
                        {{ cms('home.hero.slide2.title', 'Travail temporaire') }}
                    </h2>

                    <p data-cms-key="home.hero.slide2.text">
                        {{ cms('home.hero.slide2.text', 'Des talents flexibles et qualifiés pour vos besoins immédiats.') }}
                    </p>

                    <a href="{{ route('jobs') }}"
                       class="btn-primary"
                       data-cms-key="home.hero.slide2.cta">
                        {{ cms('home.hero.slide2.cta', 'Bénéficiez d’une main-d’œuvre prête à l’action') }}
                    </a>
                </div>
            </div>
        </article>

        <!-- {{-- Slide 3: Recrutement --}}
        <article class="slide"
                 data-cms-img="home.hero.slide3.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide3.bg', asset('images/IMAGE DE slogan.png')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    <h2 data-cms-key="home.hero.slide3.title">
                        {{ cms('home.hero.slide3.title', 'Recrutement') }}
                    </h2>

                    <p data-cms-key="home.hero.slide3.text">
                        {{ cms('home.hero.slide3.text', 'Recrutez vite les meilleurs talents pour vos postes clés.') }}
                    </p>

                    <a href="{{ route('enterprises') }}"
                       class="btn-primary"
                       data-cms-key="home.hero.slide3.cta">
                        {{ cms('home.hero.slide3.cta', 'Trouvez vos futurs talents dès aujourd’hui') }}
                    </a>
                </div>
            </div>
        </article> -->

        {{-- Slide 4: Formation --}}
        <article class="slide"
                 data-cms-img="home.hero.slide4.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide4.bg', asset('images/image_1_1768843792787.jpg')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    <h2 data-cms-key="home.hero.slide4.title">
                        {{ cms('home.hero.slide4.title', 'Formation') }}
                    </h2>

                    <p data-cms-key="home.hero.slide4.text">
                        {{ cms('home.hero.slide4.text', 'Un catalogue riche de plus de 100 formations sur-mesure pour développer vos équipes.') }}
                    </p>

                    <a href="{{ route('catalogue') }}"
                       class="btn-primary"
                       data-cms-key="home.hero.slide4.cta">
                        {{ cms('home.hero.slide4.cta', 'Découvrez notre catalogue et boostez vos talents') }}
                    </a>
                </div>
            </div>
        </article>

        {{-- Slide 5: Conseil RH --}}
        <article class="slide"
                 data-cms-img="home.hero.slide5.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide5.bg', asset('images/2147771767.jpg')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    <h2 data-cms-key="home.hero.slide5.title">
                        {{ cms('home.hero.slide5.title', 'Conseil RH') }}
                    </h2>

                    <p data-cms-key="home.hero.slide5.text">
                        {{ cms('home.hero.slide5.text', 'Des solutions RH concrètes pour relever vos défis et optimiser vos résultats.') }}
                    </p>

                    <a href="{{ route('services') }}"
                       class="btn-primary"
                       data-cms-key="home.hero.slide5.cta">
                        {{ cms('home.hero.slide5.cta', 'Transformez vos enjeux RH en performance') }}
                    </a>
                </div>
            </div>
        </article>

        <!-- {{-- Slide 6: Coaching --}}
        <article class="slide"
                 data-cms-img="home.hero.slide6.bg"
                 style="background-image:url('{{ cms_img('home.hero.slide6.bg', asset('images/IMAGE DE slogan.png')) }}')">
            <div class="hero-inner container">
                <div class="hero-pos hero-content">
                    <h2 data-cms-key="home.hero.slide6.title">
                        {{ cms('home.hero.slide6.title', 'Coaching') }}
                    </h2>

                    <p data-cms-key="home.hero.slide6.text">
                        {{ cms('home.hero.slide6.text', 'Osez évoluer, osez le coaching.') }}
                    </p>

                    <a href="{{ route('services') }}"
                       class="btn-primary"
                       data-cms-key="home.hero.slide6.cta">
                        {{ cms('home.hero.slide6.cta', 'Passez à l’action') }}
                    </a>
                </div>
            </div>
        </article> -->

    </div>

    <div class="hero-dots" aria-label="Pagination"></div>
</section>

{{-- ===== RIBBON – 2 lignes qui défilent ===== --}}
<section class="ribbon-dual">
    <div class="ribbon-line line1" data-cms-key="home.ribbon.line1">
        {{ cms('home.ribbon.line1', 'Ressources Humaines • Consulting RH • Formation • Recrutement • Coaching • Mise à disposition • Travail temporaire • Management & Développement •') }}
    </div>
    <div class="ribbon-line line2" data-cms-key="home.ribbon.line2">
        {{ cms('home.ribbon.line2', 'Travail temporaire • Management & Développement • Ressources Humaines • Consulting RH • Formation • Recrutement • Coaching • Mise à disposition •') }}
    </div>
</section>

{{-- ===== CHIFFRES CLÉS ===== --}}
<section id="chiffres-cles" class="stats-hero" data-animate>
    <div class="container stats-hero-inner">
        <div class="stats-copy">
            <small data-cms-key="home.stats.eyebrow">{{ cms('home.stats.eyebrow', 'Chiffres clés') }}</small>

            <h2 data-cms-key="home.stats.title">
                {!! nl2br(e(cms('home.stats.title', "Laissez les chiffres de RHS GROUP\nparler d’eux-mêmes"))) !!}
            </h2>

            <p data-cms-key="home.stats.p1">
                {{ cms('home.stats.p1', "Depuis plus de 20 ans, nous accompagnons les entreprises et les talents à travers le travail temporaire, le recrutement, la formation, le coaching et le conseil RH, avec une approche sur-mesure, digitale et innovante.") }}
            </p>

            <p data-cms-key="home.stats.p2">
                {{ cms('home.stats.p2', "Notre objectif : transformer vos défis RH en opportunités durables, en mobilisant rapidement des profils adaptés et des solutions concrètes.") }}
            </p>

            <a href="{{ route('contact') }}"
               class="btn-outline stats-cta"
               data-cms-key="home.stats.cta">
                {{ cms('home.stats.cta', 'Contactez-nous') }}
            </a>
        </div>

        <div class="stats-hero-grid">
            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-prefix">+</span>
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi1.value','10000') }}">0</span>
                </div>
                <p data-cms-key="home.stats.kpi1.label">{{ cms('home.stats.kpi1.label', 'Travailleurs placés') }}</p>
            </div>

            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi2.value','20') }}">0</span>
                </div>
                <p data-cms-key="home.stats.kpi2.label">{{ cms('home.stats.kpi2.label', 'Années d’expérience') }}</p>
            </div>

            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-prefix">+</span>
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi3.value','3000') }}">0</span>
                </div>
                <p data-cms-key="home.stats.kpi3.label">{{ cms('home.stats.kpi3.label', 'Candidats recrutés') }}</p>
            </div>

            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-prefix">+</span>
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi4.value','500') }}">0</span>
                </div>
                <p data-cms-key="home.stats.kpi4.label">{{ cms('home.stats.kpi4.label', 'Formations animées') }}</p>
            </div>

            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-prefix">+</span>
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi5.value','200') }}">0</span>
                </div>
                <p data-cms-key="home.stats.kpi5.label">{{ cms('home.stats.kpi5.label', 'Coachés accompagnés') }}</p>
            </div>

            <div class="stats-hero-item" data-animate>
                <div class="stat-unit">
                    <span class="stat-number" data-counter data-target="{{ cms('home.stats.kpi6.value','98') }}" data-suffix="%">0</span>
                </div>
                <p data-cms-key="home.stats.kpi6.label">{{ cms('home.stats.kpi6.label', 'Clients satisfaits') }}</p>
            </div>
        </div>
    </div>

    {{-- Bloc bas --}}
    <div class="stats-hero-bottom">
        <div class="stats-hero-bottom-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="4" width="18" height="14" rx="2" stroke="#ffffff" stroke-width="1.6"/>
                <path d="M7 9H11V13H7V9Z" stroke="#ffffff" stroke-width="1.6"/>
                <path d="M13 9H17" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M13 12H16" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="stats-hero-bottom-text" data-cms-key="home.stats.bottom">
            {{ cms('home.stats.bottom', 'Nous offrons des services de consulting et un accompagnement sur-mesure, parfaitement adaptés à vos besoins, pour faire évoluer vos équipes et sécuriser vos projets RH.') }}
        </div>
    </div>
</section>

{{-- Nos Valeurs --}}
<section class="valeurs2-section">
    <div class="container valeurs2-head">
        <h2 data-cms-key="home.values.title">
            {!! nl2br(e(cms('home.values.title', 'Ce qui nous définit'))) !!}
        </h2>
        <p data-cms-key="home.values.subtitle">
            {{ cms('home.values.subtitle', 'Des valeurs fortes qui guident chacune de nos actions et notre engagement envers nos clients.') }}
        </p>
    </div>

    <div class="container valeurs2-grid">

        <div class="valeur2-card">
            <div class="valeur2-icon">
                <img data-cms-img="home.values.v1.icon" src="{{ cms_img('home.values.v1.icon', asset('images/icons/heart.svg')) }}" alt="L'Humain au cœur">
            </div>
            <h3 data-cms-key="home.values.v1.title">{{ cms('home.values.v1.title', 'L’Humain au cœur de notre vision') }}</h3>
            <p data-cms-key="home.values.v1.text">
                {{ cms('home.values.v1.text', 'Nous plaçons l’Humain au centre de notre démarche, en favorisant un environnement d’écoute, de respect et de collaboration.') }}
            </p>
        </div>

        <div class="valeur2-card">
            <div class="valeur2-icon">
                <img data-cms-img="home.values.v2.icon" src="{{ cms_img('home.values.v2.icon', asset('images/icons/idea.svg')) }}" alt="Qualité & Innovation">
            </div>
            <h3 data-cms-key="home.values.v2.title">{{ cms('home.values.v2.title', 'Qualité & Innovation') }}</h3>
            <p data-cms-key="home.values.v2.text">
                {{ cms('home.values.v2.text', 'Nous combinons une qualité irréprochable et des approches novatrices pour offrir des services d’excellence, modernes, fiables et parfaitement adaptés aux besoins de nos clients.') }}
            </p>
        </div>

        <div class="valeur2-card">
            <div class="valeur2-icon">
                <img data-cms-img="home.values.v3.icon" src="{{ cms_img('home.values.v3.icon', asset('images/icons/shield.svg')) }}" alt="Engagement & Responsabilité">
            </div>
            <h3 data-cms-key="home.values.v3.title">{{ cms('home.values.v3.title', 'Engagement & Responsabilité') }}</h3>
            <p data-cms-key="home.values.v3.text">
                {{ cms('home.values.v3.text', 'Nous faisons preuve de rigueur et d’intégrité, en nous engageant activement à fournir des solutions concrètes, responsables et éthiques.') }}
            </p>
        </div>

        <div class="valeur2-card">
            <div class="valeur2-icon">
                <img data-cms-img="home.values.v4.icon" src="{{ cms_img('home.values.v4.icon', asset('images/icons/handshake.svg')) }}" alt="Confiance">
            </div>
            <h3 data-cms-key="home.values.v4.title">{{ cms('home.values.v4.title', 'Confiance') }}</h3>
            <p data-cms-key="home.values.v4.text">
                {{ cms('home.values.v4.text', 'Nous garantissons une gestion sécurisée et rigoureuse de vos données, en alliant transparence, écoute attentive et respect mutuel.') }}
            </p>
        </div>
    </div>
</section>

{{-- Nos Filiales --}}
<section class="filiales-section">
    <div class="filiales-head">
        <h2 data-cms-key="home.filiales.title">
            {{ cms('home.filiales.title', 'Nos Filiales') }}
        </h2>
        <p data-cms-key="home.filiales.subtitle">
            {{ cms('home.filiales.subtitle', 'Trois expertises complémentaires au service de vos besoins RH') }}
        </p>
    </div>

    <div class="container filiales-grid">

        <div class="filiale-card2">
            <div class="filiale-top-line"></div>
            <div class="filiale-icon2">
                <img data-cms-img="home.filiales.f1.icon" src="{{ cms_img('home.filiales.f1.icon', asset('images/icons/employment.svg')) }}" alt="">
            </div>

            <h3 data-cms-key="home.filiales.f1.title">{{ cms('home.filiales.f1.title', 'RHS EMPLOI') }}</h3>
            <p data-cms-key="home.filiales.f1.text">{{ cms('home.filiales.f1.text', "Solutions de travail temporaire et intérim pour tous les secteurs d’activité.") }}</p>

            <ul>
                <li data-cms-key="home.filiales.f1.li1">{{ cms('home.filiales.f1.li1', 'Missions temporaires') }}</li>
                <li data-cms-key="home.filiales.f1.li2">{{ cms('home.filiales.f1.li2', 'CDI intérimaire') }}</li>
                <li data-cms-key="home.filiales.f1.li3">{{ cms('home.filiales.f1.li3', 'Tous secteurs') }}</li>
            </ul>

            <a href="{{ cms('home.filiales.f1.url','https://rhsemploi.ma') }}"
               target="_blank"
               class="filiale-btn"
               data-cms-key="home.filiales.f1.btn">
                {{ cms('home.filiales.f1.btn', 'Découvrir') }}
            </a>
        </div>

        <div class="filiale-card2">
            <div class="filiale-top-line"></div>
            <div class="filiale-icon2">
                <img data-cms-img="home.filiales.f2.icon" src="{{ cms_img('home.filiales.f2.icon', asset('images/icons/formation.svg')) }}" alt="">
            </div>

            <h3 data-cms-key="home.filiales.f2.title">{{ cms('home.filiales.f2.title', 'Open Act') }}</h3>
            <p data-cms-key="home.filiales.f2.text">{{ cms('home.filiales.f2.text', 'Recrutement spécialisé, formation sur-mesure et coaching professionnel.') }}</p>

            <ul>
                <li data-cms-key="home.filiales.f2.li1">{{ cms('home.filiales.f2.li1', 'Recrutement CDI/CDD') }}</li>
                <li data-cms-key="home.filiales.f2.li2">{{ cms('home.filiales.f2.li2', 'Formation professionnelle') }}</li>
                <li data-cms-key="home.filiales.f2.li3">{{ cms('home.filiales.f2.li3', 'Coaching de carrière') }}</li>
            </ul>

            <a href="{{ cms('home.filiales.f2.url','https://openact.ma') }}"
               target="_blank"
               class="filiale-btn"
               data-cms-key="home.filiales.f2.btn">
                {{ cms('home.filiales.f2.btn', 'Découvrir') }}
            </a>
        </div>

        <div class="filiale-card2">
            <div class="filiale-top-line"></div>
            <div class="filiale-icon2">
                <img data-cms-img="home.filiales.f3.icon" src="{{ cms_img('home.filiales.f3.icon', asset('images/icons/agri.svg')) }}" alt="">
            </div>

            <h3 data-cms-key="home.filiales.f3.title">{{ cms('home.filiales.f3.title', 'RHS PROFIL') }}</h3>
            <p data-cms-key="home.filiales.f3.text">{{ cms('home.filiales.f3.text', 'Solutions RH dédiées au secteur agricole avec expertise approfondie.') }}</p>

            <ul>
                <li data-cms-key="home.filiales.f3.li1">{{ cms('home.filiales.f3.li1', 'Emploi saisonnier') }}</li>
                <li data-cms-key="home.filiales.f3.li2">{{ cms('home.filiales.f3.li2', 'Postes permanents') }}</li>
                <li data-cms-key="home.filiales.f3.li3">{{ cms('home.filiales.f3.li3', 'Expertise agricole') }}</li>
            </ul>

            <a href="{{ cms('home.filiales.f3.url','https://rhsprofil.com') }}"
               target="_blank"
               class="filiale-btn"
               data-cms-key="home.filiales.f3.btn">
                {{ cms('home.filiales.f3.btn', 'Découvrir') }}
            </a>
        </div>

    </div>
</section>

{{-- (Témoignages déplacés sur page À propos si besoin) --}}

@endsection

@push('scripts')
    <script src="{{ url('/js/home.js') }}" defer></script>
@endpush
