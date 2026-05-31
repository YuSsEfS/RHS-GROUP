<header id="main-header">

  <div class="topbar">
    <div class="container topbar-inner">
      <a href="{{ route('home') }}" class="topbar-logo">
        <img src="{{ asset('images/rhs-group-logo.png') }}" alt="RHS GROUP">
      </a>

      <a href="tel:+212522400808" class="topbar-item topbar-phone">
        <img src="{{ asset('images/icons/phone.svg') }}" class="topbar-icon" alt="Téléphone">
        <div>
          <span>Appelez-nous</span>
          <strong>05 22 40 08 08</strong>
        </div>
      </a>

      <div class="topbar-item topbar-hours">
        <img src="{{ asset('images/icons/clock.svg') }}" class="topbar-icon" alt="Horaires">
        <div>
          <span>8h30 – 17h30</span>
          <strong>Lundi – Vendredi</strong>
        </div>
      </div>

      <a href="https://maps.app.goo.gl/Agf9jXdeAp9ULgDv7" target="_blank" class="topbar-item topbar-address" rel="noopener">
        <img src="{{ asset('images/icons/location.svg') }}" class="topbar-icon" alt="Adresse">
        <div>
          <span>Adresse</span>
          <strong>137 Bd Moulay Ismaïl, Casablanca</strong>
        </div>
      </a>
    </div>
  </div>

  <nav class="main-nav" id="main-nav">
    <div class="container nav-inner">
      <ul class="nav-menu" id="nav-menu">
        <li class="nav-has-submenu">
          <a href="{{ route('home') }}">Accueil</a>
          <ul class="nav-submenu">
            <li><a href="{{ route('home') }}#hero-slider">Accueil</a></li>
            <li><a href="{{ route('home') }}#chiffres-cles">Chiffres clés</a></li>
            <li><a href="{{ route('home') }}#secteurs-activites">Secteurs d'activités</a></li>
            <li><a href="{{ route('home') }}#champs-action">Champs d'action</a></li>
          </ul>
        </li>
        <li class="nav-has-submenu">
          <a href="{{ route('about') }}">A propos</a>
          <ul class="nav-submenu">
            <li><a href="{{ route('about') }}#mot-direction">Mot du directeur</a></li>
            <li><a href="{{ route('about') }}#qui-sommes-nous">Qui sommes-nous ?</a></li>
            <li><a href="{{ route('about') }}#nos-filiales">Nos filiales</a></li>
            <li><a href="{{ route('about') }}#pourquoi-nous-choisir">Pourquoi nous choisir ?</a></li>
            <li><a href="{{ route('about') }}#nos-valeurs">Nos valeurs</a></li>
          </ul>
        </li>
        <li class="nav-has-submenu">
          <a href="{{ route('services') }}">Services</a>
          <ul class="nav-submenu">
            <li><a href="{{ route('services') }}#travail-temporaire">Travail temporaire</a></li>
            <li><a href="{{ route('services') }}#recrutement">Recrutement</a></li>
            <li><a href="{{ route('services') }}#conseil-rh">Conseil RH</a></li>
            <li><a href="{{ route('services') }}#code-travail">Code du Travail</a></li>
          </ul>
        </li>
        <li class="nav-has-submenu">
          <a href="{{ route('contact') }}">Contact</a>
          <ul class="nav-submenu">
            <li><a href="{{ route('contact') }}#contact-coordonnees">Coordonnées</a></li>
            <li><a href="{{ route('contact') }}#contact-map">Nous trouver</a></li>
            <li><a href="{{ route('contact') }}#contact-form">Envoyer un message</a></li>
            <li><a href="{{ route('contact') }}#contact-urgent">Besoin urgent</a></li>
          </ul>
        </li>
      </ul>

      <div class="nav-cta" id="nav-cta">
        <a href="{{ route('jobs') }}" class="btn-outline-red">Offres d'emploi</a>
        <a href="{{ route('catalogue') }}" class="btn-red">Catalogue de formation</a>
      </div>

      <button id="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav-menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

</header>
