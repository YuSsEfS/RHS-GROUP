@extends('layouts.app')
@section('title', 'À propos - RHS GROUP')

@section('content')

<div class="about-page">

    <section id="about-hero" class="about-hero">
        <div class="container about-hero-inner">
            <p class="about-chip"><span></span> <span data-cms-key="about.hero.eyebrow">À propos</span></p>
            <h1 class="about-hero-title" data-cms-key="about.hero.title">
                À propos de <span data-cms-key="about.hero.title_span">RHS GROUP</span>
            </h1>

            <p class="about-hero-subtitle" data-cms-key="about.hero.subtitle">
                Votre partenaire RH depuis plus de 20 ans, au service des talents, des entreprises et de la performance sociale.
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

    <section id="mot-direction" class="director-section" data-animate>
        <div class="container">
            <div class="director-card">
                <div class="director-img-wrapper">
                    <img
                        src="{{ asset('images/director.png') }}"
                        class="director-img"
                        alt="Directeur RHS GROUP"
                        data-cms-img="about.director.image"
                    >
                    <!-- <div class="director-badge">
                        <strong data-cms-key="about.director.badge">+20</strong>
                        <span data-cms-key="about.director.badge_label">années d'expertise RH</span>
                    </div> -->
                </div>

                <div class="director-content">
                    <p class="section-eyebrow" data-cms-key="about.director.eyebrow">Parole de Dirigeant</p>
                    <!-- <h2 class="director-title" data-cms-key="about.director.title">
                        Parole de Dirigeant
                    </h2> -->

                    <p class="director-text" data-cms-key="about.director.p1">Cher Client, Cher Prospect,</p>

                    <p class="director-text" data-cms-key="about.director.p2">
                        Avec une expérience confirmée de plus de 20 ans dans l'organisation des Ressources Humaines,
                        le recrutement, le travail temporaire et la formation, RHS GROUP a su développer un savoir-faire
                        et une expertise confirmés qui font de nous un partenaire professionnel et fiable dans tous nos
                        domaines d'intervention.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p3">
                        Notre approche est basée sur un partenariat durable avec nos clients et sur des relations de
                        confiance avec nos collaborateurs et candidats.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p4">
                        Expertise, conformité sociale, proximité, qualité et confidentialité sont des valeurs qui
                        définissent notre responsabilité envers nos clients et auxquelles adhèrent nos collaborateurs.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p5">
                        Pour l'ensemble de nos clients, nous apportons notre expertise, nos conseils ainsi que
                        l'expérience de notre réseau international, pour atteindre ensemble les meilleurs résultats
                        dans les meilleurs délais.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p6">
                        Toutes nos prestations en organisation des ressources humaines, recrutement et travail temporaire
                        sont menées avec agilité, efficacité et discrétion.
                    </p>

                    <p class="director-text" data-cms-key="about.director.p7">
                        C'est donc avec un enthousiasme profond que nous souhaitons vous compter parmi nos clients.
                    </p>

                    <div class="director-sign">
                        <p class="director-name" data-cms-key="about.director.name">Mr. Miloud AKZAZ</p>
                        <p class="director-role" data-cms-key="about.director.role">Directeur Général</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section" data-animate>
        <div class="container testimonials-grid">
            <div class="testimonials-left">
                <div class="rating-badge">
                    <span class="js-rating-number" data-target="97">0%</span>
                    <span class="rating-label" data-cms-key="about.testimonials.rating_label">Satisfaction Client</span>
                </div>

                <img
                    src="{{ asset('images/reviews/review-group.jpg') }}"
                    class="testimonials-img"
                    alt="Clients et partenaires RHS GROUP"
                    data-cms-img="about.testimonials.image"
                >
            </div>

            <div class="testimonials-right">
                <p class="section-eyebrow" data-cms-key="about.testimonials.eyebrow">Témoignages</p>
                <h2 class="test-title" data-cms-key="about.testimonials.title">
                    Nos clients parlent de <span data-cms-key="about.testimonials.title_span">nous</span>
                </h2>

                <div class="test-stars" aria-hidden="true">★★★★★</div>

                <p class="test-quote js-test-quote" data-cms-key="about.testimonials.quote">
                    "RHS GROUP nous accompagne avec proximité, discrétion et une vraie compréhension de nos enjeux RH."
                </p>

                <div class="test-person">
                    <div class="test-avatar js-test-avatar">RH</div>
                    <div>
                        <p class="test-name js-test-name" data-cms-key="about.testimonials.name">Client partenaire</p>
                        <p class="test-role js-test-role" data-cms-key="about.testimonials.role">Direction Ressources Humaines</p>
                    </div>
                </div>

                <div class="test-controls">
                    <button type="button" class="test-arrow js-test-prev" aria-label="Témoignage précédent">‹</button>
                    <div class="test-dots js-test-dots"></div>
                    <button type="button" class="test-arrow js-test-next" aria-label="Témoignage suivant">›</button>
                </div>
            </div>
        </div>
    </section>

    <section id="qui-sommes-nous" class="about-presentation" data-animate>
        <div class="container">
            <div class="about-section-head">
                <p class="section-eyebrow" data-cms-key="about.presentation.eyebrow">Présentation du groupe</p>
                <h2 class="section-title" data-cms-key="about.presentation.title">Qui sommes-nous ?</h2>
                <div class="about-line"></div>
            </div>

            <div class="presentation-grid">
                <div>
                    <p class="section-text" data-cms-key="about.presentation.p1">
                        RHS GROUP est un partenaire RH global spécialisé dans le conseil, l'organisation des Ressources Humaines,
                        le recrutement et le travail temporaire. Nous accompagnons les entreprises publiques et privées dans leurs
                        enjeux de performance sociale, de conformité et de mobilisation des talents.
                    </p>

                    <p class="section-text" data-cms-key="about.presentation.p2">
                        Notre équipe met à disposition une expérience terrain, des outils éprouvés et une approche de proximité
                        pour garantir qualité, réactivité et adéquation aux besoins de chaque client.
                    </p>
                </div>

                <div class="about-filiales">
                    <div class="filiale-card">
                        <div class="filiale-number">1</div>
                        <div>
                            <h3 class="filiale-title" data-cms-key="about.filiales.1.title">Travail temporaire</h3>
                            <p class="filiale-desc" data-cms-key="about.filiales.1.desc">Mise à disposition de personnel, contrats flexibles et gestion administrative.</p>
                        </div>
                    </div>

                    <div class="filiale-card">
                        <div class="filiale-number">2</div>
                        <div>
                            <h3 class="filiale-title" data-cms-key="about.filiales.2.title">Recrutement</h3>
                            <p class="filiale-desc" data-cms-key="about.filiales.2.desc">Sourcing, vivier de candidats, évaluation et adéquation poste/profil.</p>
                        </div>
                    </div>

                    <div class="filiale-card">
                        <div class="filiale-number">3</div>
                        <div>
                            <h3 class="filiale-title" data-cms-key="about.filiales.3.title">Conseil RH</h3>
                            <p class="filiale-desc" data-cms-key="about.filiales.3.desc">Audit RH, conformité sociale, procédures, référentiels et tableaux de bord.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="nos-filiales" class="about-filiales-section" data-animate>
        <div class="container">
            <div class="about-section-head">
                <p class="section-eyebrow" data-cms-key="about.subsidiaries.eyebrow">Nos filiales</p>
                <h2 class="section-title" data-cms-key="about.subsidiaries.title">Un écosystème RH complet</h2>
                <div class="about-line"></div>
                <p class="section-text" data-cms-key="about.subsidiaries.intro">
                    RHS GROUP fédère des expertises complémentaires pour répondre aux besoins des entreprises, des candidats
                    et des secteurs spécialisés avec la même exigence de proximité et de performance.
                </p>
            </div>

            <div class="subsidiaries-grid">
                <article class="subsidiary-card">
                    <div class="subsidiary-mark">RHS</div>
                    <p class="subsidiary-kicker">Plateforme RH & intérim</p>
                    <h3>RHS Emploi</h3>
                    <p>
                        Spécialiste du travail temporaire, de la gestion des contrats et de la mobilisation rapide de profils
                        opérationnels pour les entreprises.
                    </p>
                    <a href="https://rhsemploi.ma" target="_blank" rel="noopener">Découvrir RHS Emploi <span>→</span></a>
                </article>

                <article class="subsidiary-card subsidiary-card-featured">
                    <div class="subsidiary-mark">OA</div>
                    <p class="subsidiary-kicker">Recrutement & coaching</p>
                    <h3>Open Act</h3>
                    <p>
                        Cabinet dédié au recrutement stratégique, au coaching professionnel, à la formation et au développement
                        durable des compétences.
                    </p>
                    <a href="https://openact.ma" target="_blank" rel="noopener">Découvrir Open Act <span>→</span></a>
                </article>

                <article class="subsidiary-card">
                    <div class="subsidiary-mark">RP</div>
                    <p class="subsidiary-kicker">Recrutement agricole</p>
                    <h3>RHS Profil</h3>
                    <p>
                        Pôle spécialisé dans le recrutement agricole et l'accompagnement des exploitations, campagnes
                        saisonnières et projets durables.
                    </p>
                    <a href="https://rhsprofil.com" target="_blank" rel="noopener">Découvrir RHS Profil <span>→</span></a>
                </article>
            </div>
        </div>
    </section>

    <section id="pourquoi-nous-choisir" class="why-section" data-animate>
        <div class="container">
            <div class="about-section-head">
                <p class="section-eyebrow" data-cms-key="about.why.eyebrow">Un partenaire stratégique</p>
                <h2 class="section-title" data-cms-key="about.why.title">Pourquoi choisir RHS GROUP ?</h2>
                <div class="about-line"></div>
            </div>

            <p class="section-text mb-40" data-cms-key="about.why.p1">
                Notre expertise en gestion des ressources humaines devient votre avantage compétitif. En choisissant RHS GROUP,
                vous optez pour un partenariat solide, une approche personnalisée et une équipe dédiée à votre succès.
            </p>

            <div class="why-grid">
                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/expertise.png') }}" alt="" data-cms-img="about.why.card1.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card1.title">Expertise métier</h3>
                    <p class="why-desc" data-cms-key="about.why.card1.desc">Une expérience confirmée dans le recrutement, le travail temporaire et le conseil RH.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/shield.svg') }}" alt="" data-cms-img="about.why.card2.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card2.title">Conformité sociale</h3>
                    <p class="why-desc" data-cms-key="about.why.card2.desc">Un accompagnement attentif aux exigences légales, administratives et sociales.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/talents.png') }}" alt="" data-cms-img="about.why.card3.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card3.title">Équipe professionnelle</h3>
                    <p class="why-desc" data-cms-key="about.why.card3.desc">Des consultants proches du terrain, capables de comprendre les contraintes de chaque secteur.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon">
                        <img src="{{ asset('images/icons/support.png') }}" alt="" data-cms-img="about.why.card4.icon">
                    </div>
                    <h3 class="why-title" data-cms-key="about.why.card4.title">Conseil sur mesure</h3>
                    <p class="why-desc" data-cms-key="about.why.card4.desc">Des solutions adaptées à vos besoins, avec un suivi clair et des indicateurs utiles.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="nos-valeurs" class="about-values-section" data-animate>
        <div class="container">
            <div class="about-section-head">
                <p class="section-eyebrow" data-cms-key="about.values.eyebrow">Nos valeurs</p>
                <h2 class="section-title" data-cms-key="about.values.title">Faire de vos enjeux RH nos priorités</h2>
                <div class="about-line"></div>
            </div>

            <div class="about-values-grid">
                <article class="about-value-card">
                    <span>01</span>
                    <h3 data-cms-key="about.values.excellence.title">Excellence</h3>
                    <p data-cms-key="about.values.excellence.text">Nous visons l'excellence à chaque mandat grâce à notre expérience, notre connaissance du marché et notre exigence de résultat.</p>
                </article>
                <article class="about-value-card">
                    <span>02</span>
                    <h3 data-cms-key="about.values.proximite.title">Proximité</h3>
                    <p data-cms-key="about.values.proximite.text">Nous restons proches de nos clients pour écouter, comprendre chaque demande et répondre avec agilité.</p>
                </article>
                <article class="about-value-card">
                    <span>03</span>
                    <h3 data-cms-key="about.values.fiabilite.title">Fiabilité</h3>
                    <p data-cms-key="about.values.fiabilite.text">Mandats complexes, profils spécifiques ou projets stratégiques : nous respectons vos délais, vos contraintes et nos engagements.</p>
                </article>
                <article class="about-value-card">
                    <span>04</span>
                    <h3 data-cms-key="about.values.transparence.title">Transparence</h3>
                    <p data-cms-key="about.values.transparence.text">Nous fondons nos relations d'affaires sur l'honnêteté, l'authenticité et une communication claire.</p>
                </article>
                <article class="about-value-card">
                    <span>05</span>
                    <h3 data-cms-key="about.values.passion.title">Passion</h3>
                    <p data-cms-key="about.values.passion.text">Notre énergie quotidienne vient de notre passion pour le métier RH et de l'envie de réussir avec nos partenaires.</p>
                </article>
            </div>
        </div>
    </section>

</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/about-animations.js') }}" defer></script>
@endpush
