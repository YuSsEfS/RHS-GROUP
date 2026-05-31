@extends('layouts.app')
@section('title','Nos Services - RHS GROUP')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/services.css') }}">
@endpush

@section('content')

<div class="services-page">

    <section class="services-hero" data-animate>
        <div class="container">
            <div class="services-hero-content">
                <p class="services-eyebrow" data-cms-key="services.hero.eyebrow">NOS EXPERTISES</p>

                <h1 class="services-title" data-cms-key="services.hero.title">
                    Des solutions RH <br><span data-cms-key="services.hero.title_span">complètes & sur mesure</span>
                </h1>

                <p class="services-subtitle" data-cms-key="services.hero.subtitle">
                    RHS GROUP accompagne les entreprises dans la gestion de leurs talents, la conformité sociale,
                    le recrutement, le travail temporaire et l'organisation de la fonction RH.
                </p>
            </div>
        </div>
    </section>

    <section class="services-subnav-section">
        <div class="container services-subnav">
            <a href="#travail-temporaire">Travail temporaire</a>
            <a href="#recrutement">Recrutement</a>
            <a href="#conseil-rh">Conseil RH</a>
            <a href="#code-travail">Code du Travail</a>
        </div>
    </section>

    <div class="services-detail-slider" data-service-slider>
        <div class="services-detail-track" data-service-track>
    <section id="travail-temporaire" class="service-detail-section service-detail-section--alt">
        <div class="container service-detail-grid">
            <div>
                <p class="services-eyebrow" data-cms-key="services.temp.eyebrow">TRAVAIL TEMPORAIRE</p>
                <h2 data-cms-key="services.temp.title">Vous avez un accroissement temporaire d'activité ou une demande ponctuelle ?</h2>
                <p data-cms-key="services.temp.p1">
                    RHS GROUP étudie votre demande, analyse votre contexte et vous apporte une solution de travail temporaire
                    dans les meilleurs délais. Nous mobilisons un vivier de profils qualifiés et des contrats flexibles adaptés
                    a vos contraintes.
                </p>
                <p data-cms-key="services.temp.p2">
                    Notre accompagnement couvre la sélection, la mise a disposition, la gestion administrative et le suivi de
                    la prestation afin de garantir continuité, qualité et réactivité.
                </p>
            </div>
            <div class="service-points-card">
                <h3 data-cms-key="services.temp.points.title">Nos engagements</h3>
                <ul>
                    <li data-cms-key="services.temp.points.1">Une base de données riche en profils opérationnels.</li>
                    <li data-cms-key="services.temp.points.2">Une administration du personnel dans le cadre d'une gestion déléguée.</li>
                    <li data-cms-key="services.temp.points.3">Une proximité et des déplacements de nos équipes sur site.</li>
                    <li data-cms-key="services.temp.points.4">Des rapports de visite et indicateurs de qualité partagés avec chaque client.</li>
                    <li data-cms-key="services.temp.points.5">Des réunions périodiques pour le suivi de la prestation.</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="recrutement" class="service-detail-section">
        <div class="container service-detail-grid">
            <div>
                <p class="services-eyebrow" data-cms-key="services.recruitment.eyebrow">RECRUTEMENT</p>
                <h2 data-cms-key="services.recruitment.title">Vous recherchez un profil spécifique et les meilleurs candidats ?</h2>
                <p data-cms-key="services.recruitment.p1">
                    Considérant le recrutement comme un acte stratégique, RHS GROUP engage son expérience et ses outils
                    éprouvés pour accompagner vos projets de recrutement. Notre priorité est de garantir la meilleure
                    adéquation poste/profil dans les meilleurs délais.
                </p>
                <p data-cms-key="services.recruitment.p2">
                    Nous accompagnons les entreprises dans leur stratégie d'acquisition de talents, depuis la définition
                    du besoin jusqu'a la présentation de candidats qualifiés.
                </p>
            </div>
            <div class="service-points-card">
                <h3 data-cms-key="services.recruitment.methods.title">Nos méthodes</h3>
                <ul>
                    <li data-cms-key="services.recruitment.methods.1">CVthèque tout profil.</li>
                    <li data-cms-key="services.recruitment.methods.2">Approche directe.</li>
                    <li data-cms-key="services.recruitment.methods.3">Sourcing cible.</li>
                    <li data-cms-key="services.recruitment.methods.4">Annonces et présélection.</li>
                    <li data-cms-key="services.recruitment.methods.5">Démarche en 5P : poste, profil, potentiel, parcours et performance.</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="conseil-rh" class="service-detail-section service-detail-section--alt">
        <div class="container service-detail-grid">
            <div>
                <p class="services-eyebrow" data-cms-key="services.consulting.eyebrow">CONSEIL RH</p>
                <h2 data-cms-key="services.consulting.title">Vous souhaitez réaliser un audit RH et mettre en place un plan d'action ?</h2>
                <p data-cms-key="services.consulting.p1">
                    RHS GROUP dispose d'une grande expérience dans la gestion des Ressources Humaines. Grâce au savoir-faire
                    de nos consultants, nos missions de conseil procurent une valeur ajoutée concrète et mesurable.
                </p>
                <p data-cms-key="services.consulting.p2">
                    Nous vous accompagnons dans la structuration de vos pratiques RH, l'amélioration de vos processus et la
                    mise en oeuvre de solutions adaptées a votre organisation.
                </p>
            </div>
            <div class="service-points-card">
                <h3 data-cms-key="services.consulting.solutions.title">Solutions adaptées</h3>
                <ul>
                    <li data-cms-key="services.consulting.solutions.1">Audit organisationnel en ressources humaines.</li>
                    <li data-cms-key="services.consulting.solutions.2">Diagnostic et plans d'amélioration.</li>
                    <li data-cms-key="services.consulting.solutions.3">Manuel des procédures RH.</li>
                    <li data-cms-key="services.consulting.solutions.4">Conformité sociale.</li>
                    <li data-cms-key="services.consulting.solutions.5">Descriptifs de fonctions et référentiel des compétences.</li>
                    <li data-cms-key="services.consulting.solutions.6">Systèmes de rémunération variable et tableaux de bord RH.</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="code-travail" class="code-download-section">
        <div class="container code-download-card">
            <div>
                <p class="services-eyebrow" data-cms-key="services.code.eyebrow">RESSOURCE LEGALE</p>
                <h2 data-cms-key="services.code.title">Télécharger le Code du Travail marocain</h2>
                <p data-cms-key="services.code.desc">
                    Un document de référence pour consulter les principales dispositions du droit du travail au Maroc.
                    Cette ressource accompagne nos clients dans une démarche de conformité et de bonne gestion sociale.
                </p>
            </div>

            <a href="{{ asset('documents/code-du-travail-maroc.pdf') }}" class="code-download-btn" download>
                <span class="pdf-icon">PDF</span>
                Télécharger le document
            </a>
        </div>
    </section>
        </div>
    </div>

    <section class="services-cta" data-animate>
        <div class="container services-cta-inner">
            <h2 data-cms-key="services.cta.title">
                Un besoin RH précis ?
                <span data-cms-key="services.cta.title_span">Parlons-en.</span>
            </h2>

            <p data-cms-key="services.cta.desc">
                Nos équipes vous orientent vers la solution la plus adaptée a vos enjeux, a votre secteur et a vos délais.
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
