<header id="main-header">

  {{-- ===== TOP INFO BAR ===== --}}
  <div class="topbar">
    <div class="container topbar-inner">

      {{-- LOGO --}}
      <a href="{{ route('home') }}" class="topbar-logo">
        <img src="{{ asset('images/rhs-logo.png') }}" alt="RHS GROUP">
      </a>

      {{-- PHONE (CLICKABLE) --}}
      <a href="tel:+212522400808" class="topbar-item topbar-phone">
        <img src="{{ asset('images/icons/phone.svg') }}" class="topbar-icon" alt="Téléphone">
        <div>
          <span>Appelez-nous</span>
          <strong>05 22 40 08 08</strong>
        </div>
      </a>

      {{-- WORKING HOURS --}}
      <div class="topbar-item topbar-hours">
        <img src="{{ asset('images/icons/clock.svg') }}" class="topbar-icon" alt="Horaires">
        <div>
          <span>8h30 – 17h30</span>
          <strong>Lundi – Vendredi</strong>
        </div>
      </div>

      {{-- ADDRESS (CLICKABLE MAP LINK) --}}
      <a href="https://maps.app.goo.gl/Agf9jXdeAp9ULgDv7" target="_blank" class="topbar-item topbar-address">
        <img src="{{ asset('images/icons/location.svg') }}" class="topbar-icon" alt="Adresse">
        <div>
          <span>Adresse</span>
          <strong>137 Bd Moulay Ismaïl, Casablanca</strong>
        </div>
      </a>

    </div>
  </div>


  {{-- ===== MAIN NAVIGATION ===== --}}
  <nav class="main-nav" id="main-nav">
    <div class="container nav-inner">

      <ul class="nav-menu" id="nav-menu">
        <li><a href="{{ route('home') }}">Accueil</a></li>
        <li><a href="{{ route('about') }}">À propos</a></li>
        <li><a href="{{ route('services') }}">Services</a></li>
        <li><a href="{{ route('contact') }}">Contact</a></li>
      </ul>

      <div class="nav-cta" id="nav-cta">
        <a href="{{ route('jobs') }}" class="btn-outline-red">Offres d’emploi</a>
        <a href="{{ route('catalogue') }}" class="btn-red">Catalogue de formation</a>
      </div>

      <button id="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav-menu">
        <span></span><span></span><span></span>
      </button>

    </div>
  </nav>

</header>
