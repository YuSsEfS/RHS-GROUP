@extends('layouts.app')
@section('title', 'À propos - RHS GROUP')

@section('content')

<div class="about-page">

    {{-- ================= HERO ================= --}}
    <section id="about-hero" class="about-hero">
        <div class="container about-hero-inner">
            <h1 class="about-hero-title" data-cms-key="about.hero.title">
                À propos de <span data-cms-key="about.hero.title_span">RHS GROUP</span>
            </h1>

            <p class="about-hero-subtitle" data-cms-key="about.hero.subtitle">
                Votre partenaire RH depuis plus de 20 ans, au service des talents et des entreprises.
            </p>

            <div class="about-hero-buttons">
                <a href="{{ route('services') }}" class="btn-red" data-cms-key="about.hero.btn_services">
                    Découvrir nos services
                </a>
                <a href="{{ route('contact') }}" class="btn-outline-red" data-cms-key="about.hero.btn_contact">
                    Nous contacter
                </a>
            </div>
        </div>
    </section>


    {{-- ============= MESSAGE DE LA DIRECTION ============= --}}
    <section class="director-section" data-animate>
        <div class="container">
            <div class="director-card">
                <div class="director-img-wrapper">
                    <img
                        src="{{ asset('images/director.png') }}"
                        class="director-img"
                        alt="Directeur RHS GROUP"
                        data-cms-img="about.director.image"
                    >
                </div>

                <div class="director-content">
                    <p class="director-eyebrow" data-cms-key="about.director.eyebrow">VISION & ENGAGEMENT</p>
                    <h2 class="director-title" data-cms-key="about.director.title">Message de la Direction</h2>
                    <h3 class="director-subtitle" data-cms-key="about.director.subtitle">L’avenir des ressources humaines</h3>

                    <p class="director-text" data-cms-key="about.director.p1">
                        Parce que la réussite des entreprises et l’épanouissement des talents sont au cœur de notre mission,
                        nous avons bâti RHS GROUP sur des valeurs fortes : <strong>proximité, excellence et engagement</strong>.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p2">
                        Forts de plus de 20 ans d’expertise, nous réunissons travail temporaire, recrutement, formation,
                        coaching et conseil RH pour accompagner durablement nos partenaires.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p3">
                        Notre ambition : transformer vos défis RH en leviers de performance grâce à des dispositifs sur-mesure,
                        des équipes engagées et des outils digitaux innovants.
                    </p>

                    <div class="director-sign">
                        <p class="director-name" data-cms-key="about.director.name">Miloud AKZAZ</p>
                        <p class="director-role" data-cms-key="about.director.role">Directeur Général – RHS GROUP</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ================== TÉMOIGNAGES ================== --}}
    <section class="testimonials-section" data-animate>
        <div class="container testimonials-grid">

            <div class="testimonials-left">
                <div class="rating-badge">
                    {{-- Note: number is animated by JS; we keep label editable --}}
                    <span class="js-rating-number" data-target="97">0%</span>
                    <span class="rating-label" data-cms-key="about.testimonials.rating_label">Satisfaction Client</span>
                </div>

                <img
                    src="{{ asset('images/reviews/review-group.jpg') }}"
                    class="testimonials-img"
                    alt="Photo témoignages"
                    data-cms-img="about.testimonials.image"
                >
            </div>

            <div class="testimonials-right">
                <p class="test-eyebrow" data-cms-key="about.testimonials.eyebrow">TÉMOIGNAGES</p>
                <h2 class="test-title" data-cms-key="about.testimonials.title">
                    Nos clients parlent de <span data-cms-key="about.testimonials.title_span">nous</span> !
                </h2>

                <div class="test-stars">★★★★★</div>

                <p class="test-quote js-test-quote" data-cms-key="about.testimonials.quote">
                    “RHS GROUP a transformé notre processus de recrutement.”
                </p>

                <div class="test-person">
                    {{-- Avatar is generated; keep name/role editable --}}
                    <div class="test-avatar js-test-avatar">AF</div>
                    <div>
                        <p class="test-name js-test-name" data-cms-key="about.testimonials.name">Ahmed F.</p>
                        <p class="test-role js-test-role" data-cms-key="about.testimonials.role">Responsable Production</p>
                    </div>
                </div>

                <div class="test-dots js-test-dots"></div>
            </div>

        </div>
    </section>


    {{-- ================== QUI SOMMES-NOUS ? ================== --}}
    <section class="about-presentation" data-animate>
        <div class="container">
            <p class="section-eyebrow" data-cms-key="about.presentation.eyebrow">PRÉSENTATION DU GROUPE</p>
            <h2 class="section-title" data-cms-key="about.presentation.title">Qui sommes-nous ?</h2>

            <p class="section-text" data-cms-key="about.presentation.p1">
                RHS GROUP est un acteur global des Ressources Humaines qui accompagne les entreprises publiques et privées
                dans leurs enjeux de recrutement, de développement des compétences et de performance sociale.
            </p>

            <p class="section-text" data-cms-key="about.presentation.p2">
                Grâce à un écosystème de filiales spécialisées, nous proposons des solutions RH complètes, flexibles et alignées
                sur la réalité terrain.
            </p>

            <div class="about-filiales">
                <div class="filiale-card">
                    <div class="filiale-number">1</div>
                    <div>
                        <h3 class="filiale-title" data-cms-key="about.filiales.1.title">RHS EMPLOI</h3>
                        <p class="filiale-desc" data-cms-key="about.filiales.1.desc">Travail temporaire et mise à disposition de personnel.</p>
                    </div>
                </div>

                <div class="filiale-card">
                    <div class="filiale-number">2</div>
                    <div>
                        <h3 class="filiale-title" data-cms-key="about.filiales.2.title">RHS PROFIL</h3>
                        <p class="filiale-desc" data-cms-key="about.filiales.2.desc">Expertise agricole et ressources saisonnières.</p>
                    </div>
                </div>

                <div class="filiale-card">
                    <div class="filiale-number">3</div>
                    <div>
                        <h3 class="filiale-title" data-cms-key="about.filiales.3.title">Open ACT</h3>
                        <p class="filiale-desc" data-cms-key="about.filiales.3.desc">Recrutement, formation et coaching professionnels.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ========= POURQUOI CHOISIR RHS GROUP ? ========= --}}
    <section class="why-section" data-animate>
        <div class="container">
            <p class="section-eyebrow" data-cms-key="about.why.eyebrow">UN PARTENAIRE STRATÉGIQUE</p>
            <h2 class="section-title" data-cms-key="about.why.title">Pourquoi choisir RHS GROUP ?</h2>

            <p class="section-text mb-40" data-cms-key="about.why.p1">
                RHS GROUP, c’est une équipe soudée par un profond sens du service, de l’éthique et de la performance.
                Nous co-construisons des solutions RH concrètes et durables.
            </p>

            <div class="why-grid">

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/expertise.png') }}" alt="" data-cms-img="about.why.card1.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card1.title">Expertise RH à 360°</h3>
                    <p class="why-desc" data-cms-key="about.why.card1.desc">Des solutions complètes et intégrées.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/talents.png') }}" alt="" data-cms-img="about.why.card2.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card2.title">Talents fiables & opérationnels</h3>
                    <p class="why-desc" data-cms-key="about.why.card2.desc">Une sélection rigoureuse et réactive.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/digital.png') }}" alt="" data-cms-img="about.why.card3.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card3.title">Approche sur-mesure & digitale</h3>
                    <p class="why-desc" data-cms-key="about.why.card3.desc">Des outils modernes de pilotage RH.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/support.png') }}" alt="" data-cms-img="about.why.card4.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card4.title">Accompagnement engagé</h3>
                    <p class="why-desc" data-cms-key="about.why.card4.desc">Un suivi transparent et personnalisé.</p>
                </div>

            </div>
        </div>
    </section>

</div> {{-- /.about-page --}}

@endsection

@push('scripts')
    <script src="{{ asset('js/about-animations.js') }}" defer></script>
@endpush
