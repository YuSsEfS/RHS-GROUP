<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription client</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        body { margin: 0; background: #f8fafc; font-family: Figtree, Arial, sans-serif; }
        .register-shell { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .register-card { width: min(100%, 760px); background: #fff; border-radius: 24px; padding: 34px; box-shadow: 0 20px 50px rgba(15,23,42,.08); border: 1px solid rgba(15,23,42,.08); }
        .register-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .register-grid .full { grid-column: 1 / -1; }
        .field label { display:block; font-weight:800; margin-bottom:8px; }
        .field input { width:100%; height:46px; border:1px solid #dbe2ea; border-radius:12px; padding:0 14px; box-sizing:border-box; }
        @media (max-width: 720px) { .register-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="register-shell">
        <div class="register-card">
            <span class="admin-chip">Portail client RHS</span>
            <h1 style="margin:16px 0 10px;">Créer une demande d'accès client</h1>
            <p style="color:#64748b; line-height:1.7;">
                Cette inscription crée un compte client en attente de validation. Un administrateur devra approuver votre accès
                avant l'ouverture du tableau de bord client.
            </p>

            @if($errors->any())
                <div class="admin-alert admin-alert-danger">
                    <ul class="admin-error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('client.register.store') }}" style="margin-top:24px;">
                @csrf
                <div class="register-grid">
                    <div class="field full">
                        <label for="name">Nom / entreprise</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="field">
                        <label for="password">Mot de passe</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="field full">
                        <label for="password_confirmation">Confirmation du mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:24px; flex-wrap:wrap;">
                    <button type="submit" class="admin-btn admin-btn-primary">Envoyer la demande</button>
                    <a href="{{ route('home') }}" class="admin-btn admin-btn-ghost">Retour au site</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
