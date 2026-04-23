<footer class="footer">
  <div class="container footer-grid">

    <!-- COL 1 — LOGO + CONTACT -->
    <div class="footer-col footer-col-info">
      <img src="{{ asset('images/rhs-logo.png') }}" class="footer-logo" alt="RHS GROUP">

      <div class="footer-contact">
        <p>
          <img src="{{ asset('images/icons/location.svg') }}" class="footer-icon-sm">
          <a href="https://maps.google.com/?q=13ème étage, 137 Bd Moulay Ismaïl, Casablanca 20290" target="_blank">
            13ème étage, 137 Bd Moulay Ismaïl, Casablanca 20290
          </a>
        </p>

        <p>
          <img src="{{ asset('images/icons/phone.svg') }}" class="footer-icon-sm">
          <a href="tel:0522400808">05 22 40 08 08</a>
        </p>

        <p>
          <img src="{{ asset('images/icons/mail.png') }}" class="footer-icon-sm">
          <a href="mailto:contact@rhsgroup.ma">contact@rhsgroup.ma</a>
        </p>

        <div class="footer-socials">
          <a href="https://www.linkedin.com/company/rhsemploi" target="_blank">
            <img src="{{ asset('images/icons/linkedin.svg') }}" alt="LinkedIn">
          </a>
          <a href="https://www.instagram.com/rhs.group1" target="_blank">
            <img src="{{ asset('images/icons/instagram.svg') }}" alt="Instagram">
          </a>
          <a href="https://www.tiktok.com/@rhs.group1" target="_blank">
            <img src="{{ asset('images/icons/tiktok.svg') }}" alt="TikTok">
          </a>
        </div>
      </div>
    </div>

    <!-- COL 2 — NAVIGATION -->
    <div class="footer-col">
      <h4>Navigation</h4>
      <a href="{{ route('home') }}">Accueil</a>
      <a href="{{ route('about') }}">À propos</a>
      <a href="{{ route('services') }}">Services</a>
      <a href="{{ route('jobs') }}">Offres d’emploi</a>
      <a href="{{ route('contact') }}">Contact</a>
    </div>

    <!-- COL 3 — RHS EMPLOI -->
    <div class="footer-col">
      <h4>RHS Emploi</h4>
      <a href="https://rhsemploi.ma" target="_blank" class="footer-filiale-title">
        Plateforme RH & Intérim →
      </a>
      <p>
        Spécialiste du travail temporaire et de la gestion des talents,
        RHS Emploi accompagne entreprises et candidats vers des opportunités fiables.
      </p>
    </div>

    <!-- COL 4 — OPEN ACT -->
    <div class="footer-col">
      <h4>Open Act</h4>
      <a href="https://openact.ma" target="_blank" class="footer-filiale-title">
        Recrutement & Coaching →
      </a>
      <p>
        Cabinet spécialisé en recrutement stratégique, coaching professionnel
        et développement des compétences.
      </p>
    </div>

    <!-- COL 5 — RHS PROFIL -->
    <div class="footer-col">
      <h4>RHS Profil</h4>
      <a href="https://rhsprofil.com" target="_blank" class="footer-filiale-title">
        Recrutement Agricole →
      </a>
      <p>
        Pôle dédié au recrutement agricole spécialisé,
        au service des exploitations et des projets durables.
      </p>
    </div>

  </div>

  <div class="footer-bottom">
    <p>© {{ date('Y') }} RHS GROUP - Tous droits réservés.</p>
  </div>
</footer>
