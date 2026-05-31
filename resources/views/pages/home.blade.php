@extends('layouts.app')
@section('title','RHS GROUP - Accueil')

@section('content')
@php
    $slides = [
        [
            'key' => 'home.hero.slide1',
            'eyebrow' => cms('home.hero.slide1.eyebrow', 'Expertise RH'),
            'title' => cms('home.hero.slide1.signature', "Votre partenaire RH\nde confiance"),
            'text' => cms('home.hero.slide1.lead', "RHS GROUP accompagne les entreprises avec une expertise confirmée en organisation RH, recrutement, travail temporaire et conseil social."),
            'image' => cms_img('home.hero.slide1.bg', asset('images/IMAGE DE slogan.png')),
            'image_key' => 'home.hero.slide1.bg',
            'cta' => cms('home.hero.slide1.btn1', 'Découvrir nos services'),
            'url' => route('services'),
            'ghost' => cms('home.hero.slide1.btn2', 'Qui sommes-nous ?'),
            'ghost_url' => route('about') . '#qui-sommes-nous',
        ],
        [
            'key' => 'home.hero.slide2',
            'eyebrow' => cms('home.hero.slide2.eyebrow', 'Travail temporaire'),
            'title' => cms('home.hero.slide2.title', "Contrats flexibles,\nprofils opérationnels"),
            'text' => cms('home.hero.slide2.text', "Des contrats flexibles et des profils opérationnels pour répondre à vos accroissements d'activité, remplacements et besoins ponctuels."),
            'image' => cms_img('home.hero.slide2.bg', asset('images/ChatGPT Image Jan 19, 2026, 05_23_33 PM.png')),
            'image_key' => 'home.hero.slide2.bg',
            'cta' => cms('home.hero.slide2.cta', 'Découvrir la prestation'),
            'url' => route('services') . '#travail-temporaire',
            'ghost' => cms('home.hero.slide2.ghost', 'Code du Travail'),
            'ghost_url' => route('services') . '#code-travail',
        ],
        [
            'key' => 'home.hero.slide4',
            'eyebrow' => cms('home.hero.slide4.eyebrow', 'Recrutement'),
            'title' => cms('home.hero.slide4.title', "Recrutement"),
            'text' => cms('home.hero.slide4.text', "Le bon candidat, au bon moment, avec CVthèque, sourcing, approche directe et adéquation poste/profil."),
            'image' => cms_img('home.hero.slide4.bg', asset('images/freepik__assistant__14091.png')),
            'image_key' => 'home.hero.slide4.bg',
            'cta' => cms('home.hero.slide4.cta', 'Trouver vos talents'),
            'url' => route('services') . '#recrutement',
            'ghost' => cms('home.hero.slide4.ghost', "Voir les offres"),
            'ghost_url' => route('jobs'),
        ],
        [
            'key' => 'home.hero.slide5',
            'eyebrow' => cms('home.hero.slide5.eyebrow', 'Conseil RH'),
            'title' => cms('home.hero.slide5.title', "Votre partenaire\nRessources Humaines"),
            'text' => cms('home.hero.slide5.text', "Audit RH, conformité sociale, procédures, référentiels et tableaux de bord pour structurer votre organisation."),
            'image' => cms_img('home.hero.slide5.bg', asset('images/2147771767.jpg')),
            'image_key' => 'home.hero.slide5.bg',
            'cta' => cms('home.hero.slide5.cta', 'Structurer votre fonction RH'),
            'url' => route('services') . '#conseil-rh',
            'ghost' => cms('home.hero.slide5.ghost', 'Nous contacter'),
            'ghost_url' => route('contact'),
        ],
    ];

    $marquee = [
        'Ressources Humaines',
        'Consulting RH',
        'Formation',
        'Recrutement',
        'Coaching',
        'Mise à disposition',
        'Travail temporaire',
        'Management & Développement',
    ];

    $stats = [
        ['prefix' => '+', 'value' => cms('home.stats.kpi1.value','10000'), 'suffix' => '', 'label' => cms('home.stats.kpi1.label', 'Travailleurs placés')],
        ['prefix' => '', 'value' => cms('home.stats.kpi2.value','20'), 'suffix' => '', 'label' => cms('home.stats.kpi2.label', "Années d'expérience")],
        ['prefix' => '+', 'value' => cms('home.stats.kpi3.value','3000'), 'suffix' => '', 'label' => cms('home.stats.kpi3.label', 'Candidats recrutés')],
        ['prefix' => '+', 'value' => cms('home.stats.kpi4.value','500'), 'suffix' => '', 'label' => cms('home.stats.kpi4.label', 'Formations animées')],
        ['prefix' => '+', 'value' => cms('home.stats.kpi5.value','200'), 'suffix' => '', 'label' => cms('home.stats.kpi5.label', 'Coachés accompagnés')],
        ['prefix' => '', 'value' => cms('home.stats.kpi6.value','98'), 'suffix' => '%', 'label' => cms('home.stats.kpi6.label', 'Clients satisfaits')],
    ];

    $sectors = [
        [
            'key' => 'automobile',
            'icon' => 'car',
            'title' => cms('home.sectors.automobile.title', 'Automobile'),
            'intro' => "RHS GROUP accompagne les ateliers, équipementiers, concessions et unités industrielles automobiles dans la mobilisation rapide de profils fiables et opérationnels.",
            'needs' => ['Techniciens qualifiés et opérateurs de production', 'Renforts pour pics d’activité et lignes de montage', 'Profils maintenance, contrôle qualité et logistique'],
            'solution' => "Nous sécurisons vos besoins terrain avec un vivier qualifié, une sélection rigoureuse et un suivi de proximité pour maintenir la continuité de production.",
        ],
        [
            'key' => 'aero',
            'icon' => 'plane',
            'title' => cms('home.sectors.aero.title', 'Aéronautique'),
            'intro' => "Dans un secteur exigeant où la précision et la conformité sont essentielles, RHS GROUP aide les entreprises aéronautiques à structurer leurs recrutements et leurs équipes support.",
            'needs' => ['Profils techniques spécialisés', 'Contrôle qualité et respect des procédures', 'Recrutement ciblé pour postes sensibles'],
            'solution' => "Notre approche privilégie l’adéquation poste/profil, la vérification des compétences et un accompagnement RH adapté aux environnements normés.",
        ],
        [
            'key' => 'agro',
            'icon' => 'wheat',
            'title' => cms('home.sectors.agro.title', 'Agroalimentaire'),
            'intro' => "RHS GROUP répond aux contraintes de saisonnalité, de cadence et d’hygiène propres aux industries agroalimentaires et aux chaînes de transformation.",
            'needs' => ['Renforts saisonniers et équipes de production', 'Personnel sensibilisé aux règles d’hygiène', 'Continuité des lignes et remplacement rapide'],
            'solution' => "Nous construisons des solutions flexibles avec des profils disponibles, encadrés et suivis pour garantir réactivité et stabilité opérationnelle.",
        ],
        [
            'key' => 'btp',
            'icon' => 'briefcase',
            'title' => cms('home.sectors.btp.title', 'Génie Civil & BTP'),
            'intro' => "Pour les chantiers, ouvrages et projets d’infrastructure, RHS GROUP met à disposition des profils adaptés aux délais, aux exigences terrain et aux contraintes de sécurité.",
            'needs' => ['Main-d’œuvre qualifiée pour chantiers', 'Encadrement, chefs d’équipe et profils techniques', 'Renforts selon avancement des projets'],
            'solution' => "Nous aidons à dimensionner les équipes, organiser les besoins et assurer une présence fiable sur site avec un suivi administratif clair.",
        ],
        [
            'key' => 'hotel',
            'icon' => 'stethoscope',
            'title' => cms('home.sectors.hotel.title', 'Hôtellerie & Tourisme'),
            'intro' => "Dans l’hôtellerie et le tourisme, l’expérience client repose sur des équipes disponibles, présentables et réactives. RHS GROUP accompagne ces métiers avec souplesse.",
            'needs' => ['Réception, service, housekeeping et support', 'Renforts événementiels et saisonniers', 'Profils orientés client et qualité de service'],
            'solution' => "Nous sélectionnons des talents adaptés à votre standing, à vos horaires et à vos pics d’activité, avec une attention forte à la fiabilité et au savoir-être.",
        ],
        [
            'key' => 'logistique',
            'icon' => 'calculator',
            'title' => cms('home.sectors.logistique.title', 'Logistique'),
            'intro' => "RHS GROUP soutient les plateformes, entrepôts et activités de transport avec des solutions RH agiles pour absorber les volumes et fluidifier les opérations.",
            'needs' => ['Préparateurs, magasiniers et caristes', 'Renforts inventaires, réception et expédition', 'Organisation des équipes en horaires variables'],
            'solution' => "Nous apportons une réponse rapide, structurée et suivie pour sécuriser vos flux, vos délais et la qualité de service auprès de vos clients.",
        ],
    ];

    $actions = [
        [
            'icon' => 'briefcase',
            'title' => cms('home.filiales.f1.title', 'Travail temporaire'),
            'text' => cms('home.filiales.f1.text', "Mise à disposition rapide de profils qualifiés pour vos besoins ponctuels, remplacements et accroissements d'activité."),
            'items' => [
                cms('home.filiales.f1.li1', 'Missions temporaires'),
                cms('home.filiales.f1.li2', 'Contrats flexibles'),
                cms('home.filiales.f1.li3', 'Suivi sur site'),
            ],
            'url' => route('services') . '#travail-temporaire',
        ],
        [
            'icon' => 'folder',
            'title' => cms('home.filiales.f2.title', 'Recrutement'),
            'text' => cms('home.filiales.f2.text', 'Identification, évaluation et présentation des meilleurs candidats pour vos postes stratégiques.'),
            'items' => [
                cms('home.filiales.f2.li1', 'CVthèque et sourcing'),
                cms('home.filiales.f2.li2', 'Approche directe'),
                cms('home.filiales.f2.li3', 'Adéquation poste/profil'),
            ],
            'url' => route('services') . '#recrutement',
        ],
        [
            'icon' => 'cap',
            'title' => cms('home.filiales.f3.title', 'Conseil RH'),
            'text' => cms('home.filiales.f3.text', 'Audit, diagnostic et accompagnement pour structurer vos pratiques RH et renforcer votre conformité sociale.'),
            'items' => [
                cms('home.filiales.f3.li1', 'Audit RH'),
                cms('home.filiales.f3.li2', 'Procédures RH'),
                cms('home.filiales.f3.li3', 'Tableaux de bord'),
            ],
            'url' => route('services') . '#conseil-rh',
        ],
    ];
@endphp

<section id="hero-slider" class="rhs-hero" aria-label="Présentation RHS GROUP">
    <div class="rhs-hero-glow" aria-hidden="true"></div>
    <div class="rhs-hero-shell rhs-container">
        <div class="rhs-hero-copy">
            @foreach($slides as $index => $slide)
                <article class="rhs-hero-panel {{ $index === 0 ? 'is-active' : '' }}" data-hero-panel="{{ $index }}">
                    <div class="rhs-eyebrow" data-cms-key="{{ $slide['key'] }}.eyebrow">
                        <span></span>
                        {{ $slide['eyebrow'] }}
                    </div>
                    <h1 data-cms-key="{{ $slide['key'] }}.{{ $index === 0 ? 'signature' : 'title' }}">{!! nl2br(e($slide['title'])) !!}</h1>
                    <div class="rhs-gradient-line"></div>
                    <p data-cms-key="{{ $slide['key'] }}.{{ $index === 0 ? 'lead' : 'text' }}">{{ $slide['text'] }}</p>
                    <div class="rhs-hero-actions">
                        <a href="{{ $slide['url'] }}" class="rhs-btn rhs-btn-primary">
                            <span data-cms-key="{{ $slide['key'] }}.{{ $index === 0 ? 'btn1' : 'cta' }}">{{ $slide['cta'] }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="{{ $slide['ghost_url'] }}" class="rhs-btn rhs-btn-ghost" data-cms-key="{{ $slide['key'] }}.{{ $index === 0 ? 'btn2' : 'ghost' }}">
                            {{ $slide['ghost'] }}
                        </a>
                    </div>
                </article>
            @endforeach

            <div class="rhs-slider-control" aria-label="Navigation du slider">
                <div class="rhs-hero-dots"></div>
                <div class="rhs-slide-count"><span data-current-slide>01</span> / {{ str_pad(count($slides), 2, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        <div class="rhs-hero-visual">
            <button type="button" class="rhs-hero-arrow rhs-hero-prev" aria-label="Slide précédente">‹</button>
            <button type="button" class="rhs-hero-arrow rhs-hero-next" aria-label="Slide suivante">›</button>
            <div class="rhs-hero-halo"></div>
            @foreach($slides as $index => $slide)
                <article class="rhs-hero-image {{ $index === 0 ? 'is-active' : '' }}" data-hero-image="{{ $index }}">
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['eyebrow'] }}" data-cms-img="{{ $slide['image_key'] }}">
                </article>
            @endforeach
            <div class="rhs-float rhs-float-expertise">
                <strong>+20</strong>
                <span>Années<br>d'expertise RH</span>
            </div>
            <div class="rhs-float rhs-float-client">
                <span>Clients </span>
                <strong>98%</strong>
                <span>Satisfaits</span>
            </div>
        </div>
    </div>
</section>

<section class="rhs-marquee" aria-label="Domaines RHS GROUP">
    <div class="rhs-marquee-track">
        @for($i = 0; $i < 2; $i++)
            @foreach($marquee as $item)
                <span>{{ $item }}</span>
                <i></i>
            @endforeach
        @endfor
    </div>
</section>

<section id="chiffres-cles" class="rhs-stats">
    <div class="rhs-stats-bg"></div>
    <div class="rhs-container rhs-stats-inner">
        <div class="rhs-stats-copy rhs-reveal">
            <div class="rhs-eyebrow rhs-eyebrow-dark" data-cms-key="home.stats.eyebrow">{{ cms('home.stats.eyebrow', 'Chiffres clés') }}</div>
            <h2 data-cms-key="home.stats.title">{!! nl2br(e(cms('home.stats.title', "Laissez les chiffres de RHS GROUP\nparler d'eux-mêmes"))) !!}</h2>
            <p data-cms-key="home.stats.p1">{{ cms('home.stats.p1', "Depuis plus de 20 ans, nous accompagnons les entreprises et les talents à travers le travail temporaire, le recrutement, le conseil RH, la formation et le coaching, avec une approche rigoureuse et adaptée au terrain.") }}</p>
            <p data-cms-key="home.stats.p2">{{ cms('home.stats.p2', "Notre objectif : sécuriser vos projets RH, mobiliser les bons profils, structurer vos pratiques sociales et construire avec vous une performance durable.") }}</p>
            <a href="{{ route('contact') }}" class="rhs-btn rhs-btn-primary" data-cms-key="home.stats.cta">{{ cms('home.stats.cta', 'Contactez-nous') }}</a>
        </div>

        <div class="rhs-stats-grid">
            @foreach($stats as $index => $stat)
                <article class="rhs-stat-card rhs-reveal" style="--delay: {{ $index * 80 }}ms">
                    <i></i>
                    <div>
                        <span class="rhs-stat-prefix">{{ $stat['prefix'] }}</span>
                        <span class="rhs-stat-number" data-counter data-target="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">0</span>
                    </div>
                    <p data-cms-key="home.stats.kpi{{ $index + 1 }}.label">{{ $stat['label'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
    <div class="rhs-container">
        <div class="rhs-stats-banner rhs-reveal">
            <span class="rhs-banner-icon">@include('partials.home-icon', ['name' => 'clipboard'])</span>
            <strong data-cms-key="home.stats.bottom">{{ cms('home.stats.bottom', 'Nous vous garantissons des solutions RH efficaces, adaptées à vos besoins et suivies par une équipe professionnelle et expérimentée.') }}</strong>
        </div>
    </div>
</section>

<section id="secteurs-activites" class="rhs-sectors">
    <div class="rhs-section-head rhs-container rhs-reveal">
        <div class="rhs-eyebrow" data-cms-key="home.sectors.eyebrow">Expertise multi-secteurs</div>
        <h2 data-cms-key="home.sectors.title">{{ cms('home.sectors.title', "Nos secteurs d'activités") }}</h2>
        <div class="rhs-gradient-line"></div>
        <p data-cms-key="home.sectors.subtitle">{{ cms('home.sectors.subtitle', "Nous intervenons auprès d'entreprises de secteurs variés avec des solutions RH adaptées à leurs contraintes terrain.") }}</p>
    </div>

    <div class="rhs-container rhs-sector-carousel rhs-reveal" aria-label="Secteurs d'activités">
        <button type="button" class="rhs-sector-arrow rhs-sector-prev" aria-label="Secteur précédent">‹</button>
        <div class="rhs-sector-window">
            <div class="rhs-sector-track">
                @foreach($sectors as $index => $sector)
                    <article class="rhs-sector-card" data-sector-card="{{ $index }}" tabindex="0" role="button" aria-label="Voir le secteur {{ $sector['title'] }}"
                        data-sector-title="{{ $sector['title'] }}"
                        data-sector-intro="{{ $sector['intro'] }}"
                        data-sector-solution="{{ $sector['solution'] }}"
                        data-sector-needs='@json($sector["needs"])'>
                        <div class="rhs-sector-icon">
                            <span>@include('partials.home-icon', ['name' => $sector['icon']])</span>
                        </div>
                        <div class="rhs-sector-copy">
                            <small>secteur</small>
                            <h3 data-cms-key="home.sectors.{{ $sector['key'] }}.title">{{ $sector['title'] }}</h3>
                            <em>↗</em>
                            <i></i>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
        <button type="button" class="rhs-sector-arrow rhs-sector-next" aria-label="Secteur suivant">›</button>
        <div class="rhs-sector-dots" aria-label="Pagination des secteurs"></div>
    </div>
</section>

<div class="rhs-sector-modal" id="sector-modal" aria-hidden="true">
    <div class="rhs-sector-modal-backdrop" data-sector-close></div>
    <section class="rhs-sector-dialog" role="dialog" aria-modal="true" aria-labelledby="sector-modal-title">
        <button type="button" class="rhs-sector-close" data-sector-close aria-label="Fermer">×</button>
        <div class="rhs-sector-dialog-mark">
            <span id="sector-modal-icon"></span>
        </div>
        <div class="rhs-eyebrow">Secteur d'activité</div>
        <h3 id="sector-modal-title"></h3>
        <p id="sector-modal-intro"></p>
        <div class="rhs-sector-modal-grid">
            <div>
                <h4>Enjeux fréquents</h4>
                <ul id="sector-modal-needs"></ul>
            </div>
            <div>
                <h4>Réponse RHS GROUP</h4>
                <p id="sector-modal-solution"></p>
            </div>
        </div>
        <a href="{{ route('contact') }}" class="rhs-btn rhs-btn-primary">
            Parler de votre besoin
            <span>→</span>
        </a>
    </section>
</div>

<section id="champs-action" class="rhs-actions">
    <div class="rhs-section-head rhs-container rhs-reveal">
        <div class="rhs-eyebrow" data-cms-key="home.filiales.eyebrow">Nos services</div>
        <h2 data-cms-key="home.filiales.title">{{ cms('home.filiales.title', "Nos champs d'action") }}</h2>
        <div class="rhs-gradient-line"></div>
        <p data-cms-key="home.filiales.subtitle">{{ cms('home.filiales.subtitle', 'Trois expertises complémentaires pour accompagner vos enjeux RH de bout en bout.') }}</p>
    </div>

    <div class="rhs-container rhs-action-grid">
        @foreach($actions as $index => $action)
            <article class="rhs-action-card rhs-reveal" style="--delay: {{ $index * 120 }}ms">
                <div class="rhs-action-blob"></div>
                <div class="rhs-action-stripe"></div>
                <div class="rhs-action-icon">@include('partials.home-icon', ['name' => $action['icon']])</div>
                <h3 data-cms-key="home.filiales.f{{ $index + 1 }}.title">{{ $action['title'] }}</h3>
                <p data-cms-key="home.filiales.f{{ $index + 1 }}.text">{{ $action['text'] }}</p>
                <ul>
                    @foreach($action['items'] as $itemIndex => $item)
                        <li data-cms-key="home.filiales.f{{ $index + 1 }}.li{{ $itemIndex + 1 }}"><span>@include('partials.home-icon', ['name' => 'check'])</span>{{ $item }}</li>
                    @endforeach
                </ul>
                <a href="{{ $action['url'] }}" class="rhs-btn rhs-btn-primary">
                    Découvrir
                    <span>→</span>
                </a>
            </article>
        @endforeach
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/home.js') }}" defer></script>
@endpush
