<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="icon" href="{{ asset('images/ChatGPT%20Image%20Jan%2015%2C%202026%2C%2009_50_56%20PM.png') }}" type="image/png">
  <title>Administration RHS — Connexion</title>

  {{-- Fonts --}}
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  {{-- Tailwind (optional, but kept because Breeze expects Vite) --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    :root{
      --rhs-red:#e23b31;
      --rhs-red-dark:#c92927;
      --text:#0f172a;
      --muted:#64748b;
      --border:#e5e7eb;
      --bg:#f6f7fb;
      --card:#ffffff;
      --shadow: 0 18px 60px rgba(2,6,23,.12);
      --radius: 18px;
    }

    *{ box-sizing:border-box; }
    body{
      margin:0;
      font-family: Figtree, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: #111;
      color: var(--text);
    }

    /* ====== Layout (like screenshot #2) ====== */
    .auth-wrap{
      min-height: 100vh;
      display: grid;
      grid-template-columns: 520px 1fr;
      background: #fff;
    }

    /* Left panel */
    .auth-left{
      background: #fff;
      padding: 46px 48px;
      display:flex;
      flex-direction:column;
      justify-content:flex-start;
      gap: 26px;
      border-right: 1px solid rgba(15,23,42,.08);
    }

    .rhs-logo{
      width: 108px;
      height: auto;
      display:block;
    }

    .badge-row{
      display:flex;
      align-items:center;
      gap: 10px;
      margin-top: 4px;
      color: var(--rhs-red);
      font-weight: 800;
      font-size: 13px;
    }
    .badge-dot{
      width: 26px;
      height: 26px;
      border-radius: 999px;
      border: 1px solid rgba(226,59,49,.35);
      background: rgba(226,59,49,.08);
      display:grid;
      place-items:center;
    }
    .badge-dot svg{ width: 14px; height: 14px; color: var(--rhs-red); }

    h1{
      margin: 0;
      font-size: 34px;
      font-weight: 900;
      letter-spacing: -.02em;
      line-height: 1.05;
    }
    .sub{
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      max-width: 46ch;
    }

    /* Form */
    .form{
      margin-top: 16px;
      display:flex;
      flex-direction:column;
      gap: 16px;
      width: 100%;
      max-width: 420px;
    }

    .field label{
      display:block;
      font-size: 13px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 8px;
    }

   .control {
  position: relative;
  display: flex;
  align-items: center;
}
    .icon-left{
      position:absolute;
      left: 12px;
      width: 20px;
      height: 20px;
      opacity: .75;
      color: #64748b;
    }

  .icon-right {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  border: 1px solid rgba(15,23,42,.08);
  background: rgba(248,250,252,.9);
  cursor: pointer;
  transition: transform .15s ease, background .15s ease;
  z-index: 2; /* always on top */
}

.icon-right:hover {
  transform: translateY(-50%) translateY(-1px);
  background: #fff;
}

    input[type="email"],
input[type="password"],
input[type="text"] {
  width: 100%;
  height: 46px;
  border-radius: 10px;
  border: 1px solid rgba(15,23,42,.12);
  background: #fff;
  padding: 0 56px 0 42px; /* reserve space for right button */
  font-size: 14px;
  outline: none;
  transition: box-shadow .15s ease, border-color .15s ease;
  box-sizing: border-box; /* ensure padding doesn't break layout */
}

    input[type="password"]{ padding-right: 56px; }

    input:focus{
      border-color: rgba(226,59,49,.45);
      box-shadow: 0 0 0 5px rgba(226,59,49,.12);
    }

    .row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      margin-top: 4px;
    }

    .remember{
      display:flex;
      align-items:center;
      gap: 10px;
      color: #64748b;
      font-size: 13px;
      font-weight: 700;
    }

    .remember input{
      width: 16px;
      height: 16px;
      border-radius: 4px;
      accent-color: var(--rhs-red);
    }

    .forgot{
      color: var(--rhs-red);
      text-decoration:none;
      font-weight: 800;
      font-size: 13px;
    }
    .forgot:hover{ text-decoration: underline; }

    .btn{
      margin-top: 6px;
      height: 50px;
      border-radius: 10px;
      border: 0;
      cursor:pointer;
      background: #d32f2f; /* screenshot-like */
      color: #fff;
      font-weight: 900;
      letter-spacing: .02em;
      box-shadow: 0 14px 22px rgba(211,47,47,.18);
      transition: transform .15s ease, filter .15s ease;
    }
    .btn:hover{ transform: translateY(-1px); filter: brightness(.98); }

    .divider{
      margin-top: 18px;
      border-top: 1px solid rgba(15,23,42,.08);
      padding-top: 14px;
      color: #64748b;
      font-size: 12px;
      text-align:center;
    }

    /* Alerts */
    .alert{
      max-width: 420px;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 13px;
      font-weight: 700;
      border: 1px solid rgba(15,23,42,.10);
      background: #fff;
    }
    .alert-success{
      border-color: rgba(16,185,129,.25);
      background: rgba(16,185,129,.10);
      color: #065f46;
    }
    .alert-error{
      border-color: rgba(239,68,68,.25);
      background: rgba(239,68,68,.08);
      color: #7f1d1d;
    }
    .alert-error ul{ margin: 10px 0 0; padding-left: 18px; }

    /* Right panel with photo */
    .auth-right{
      position: relative;
      overflow:hidden;
      background:
        linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(255,255,255,.0) 22%),
        url('/images/login-bg.jpg'); /* you can replace with your own */
      background-size: cover;
      background-position: center;
      border-bottom-left-radius: 26px;
    }

    /* If you don't have /images/login-bg.jpg, you can use the same slogan image */
    .auth-right.fallback{
      background:
        linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(255,255,255,.0) 22%),
url('/images/premium-round-golden-frame-red-background-design_1017-54880.avif');
      background-size: cover;
      background-position: center;
    }

    .right-overlay{
      position:absolute;
      inset:0;
      pointer-events:none;
      background:
        radial-gradient(900px 500px at 65% 75%, rgba(0,0,0,.08), transparent 55%),
        linear-gradient(to bottom, rgba(0,0,0,.0), rgba(0,0,0,.18));
    }

    .right-text{
      position:absolute;
      left: 56px;
      bottom: 52px;
      max-width: 520px;
      color: #fff;
      text-shadow: 0 12px 35px rgba(0,0,0,.35);
    }

    .right-text h2{
      margin: 0;
      font-size: 38px;
      font-weight: 950;
      line-height: 1.08;
    }
    .right-text p{
      margin: 12px 0 0;
      font-size: 15px;
      line-height: 1.7;
      opacity: .92;
      max-width: 58ch;
    }

    /* Responsive */
    @media (max-width: 980px){
      .auth-wrap{ grid-template-columns: 1fr; }
      .auth-left{ border-right: none; padding: 34px 22px; }
      .auth-right{ display:none; }
      h1{ font-size: 30px; }
    }
  </style>
</head>

<body>
  <div class="auth-wrap">

    {{-- LEFT --}}
    <section class="auth-left">

      {{-- ✅ RHS Logo (your exact file with spaces) --}}
      <img
        class="rhs-logo"
        src="{{ asset('images/ChatGPT Image Jan 15, 2026, 09_50_56 PM.png') }}"
        alt="RHS GROUP"
      >

      <div>
        <div class="badge-row">
          <span class="badge-dot" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
          </span>
          RHS Admin
        </div>

        <h1>Administration RHS</h1>
        <p class="sub">Accès sécurisé à l’espace administrateur</p>
      </div>

      {{-- Session status --}}
      @if (session('status'))
        <div class="alert alert-success">
          {{ session('status') }}
        </div>
      @endif

      {{-- Errors --}}
      @if($errors->any())
        <div class="alert alert-error">
          <strong>Erreur :</strong>
          <ul>
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- FORM (same Breeze logic) --}}
      <form class="form" method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="field">
          <label for="email">Email</label>
          <div class="control">
            <svg class="icon-left" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2" />
              <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <input
              id="email"
              type="email"
              name="email"
              value="{{ old('email') }}"
              required
              autofocus
              autocomplete="username"
              placeholder="admin@rhs-group.ma"
            >
          </div>
        </div>

        {{-- Password --}}
        <div class="field">
          <label for="password">Mot de passe</label>
          <div class="control">
            <svg class="icon-left" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M6 11h12v10H6V11Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>

            <input
              id="password"
              type="password"
              name="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
            >

            <button type="button" class="icon-right" id="togglePwd" aria-label="Afficher / masquer le mot de passe">
              <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;color:#64748b;">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="2"/>
                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="row">
          <label class="remember" for="remember_me">
            <input id="remember_me" type="checkbox" name="remember">
            Se souvenir de moi
          </label>

          @if (Route::has('password.request'))
            <a class="forgot" href="{{ route('password.request') }}">
              Mot de passe oublié ?
            </a>
          @endif
        </div>

        <button class="btn" type="submit">
          Se connecter
        </button>

        <div class="divider">
          RHS GROUP • Administration interne • Accès restreint
        </div>
      </form>

    </section>

    {{-- RIGHT (photo) --}}
    <section class="auth-right fallback">
      <div class="right-overlay"></div>

      <div class="right-text">
        <h2>Bienvenue sur votre espace RH</h2>
        <p>
          Gérez vos ressources humaines avec efficacité et sécurité grâce à notre plateforme dédiée.
        </p>
      </div>
    </section>

  </div>

  <script>
    (function(){
      const btn = document.getElementById('togglePwd');
      const input = document.getElementById('password');
      const eye = document.getElementById('eyeIcon');

      if(!btn || !input) return;

      btn.addEventListener('click', () => {
        const isPwd = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPwd ? 'text' : 'password');

        // tiny visual feedback (optional)
        btn.style.transform = 'translateY(-1px)';
        setTimeout(() => btn.style.transform = '', 120);

        // switch icon (open eye / crossed eye)
        eye.innerHTML = isPwd
          ? '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="2"/><path d="M4 4l16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
          : '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="2"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>';
      });
    })();
  </script>
</body>
</html>
