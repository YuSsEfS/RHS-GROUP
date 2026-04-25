<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte en attente</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(900px 420px at 10% 0%, rgba(239,68,68,.18), transparent 60%),
                radial-gradient(720px 320px at 100% 10%, rgba(15,23,42,.14), transparent 58%),
                #f6f7fb;
        }

        .pending-shell {
            width: min(100%, 980px);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid rgba(15,23,42,.08);
            background: #fff;
            box-shadow: 0 30px 80px rgba(15,23,42,.14);
        }

        .pending-brand {
            padding: 34px;
            color: #fff;
            background:
                linear-gradient(160deg, rgba(11,19,36,.98), rgba(20,33,61,.94)),
                #0b1324;
        }

        .pending-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.12);
            font-weight: 800;
            font-size: .84rem;
        }

        .pending-brand h1 {
            margin: 20px 0 12px;
            font-size: clamp(2rem, 4vw, 2.9rem);
            line-height: 1.05;
        }

        .pending-brand p {
            margin: 0;
            color: rgba(255,255,255,.76);
            line-height: 1.8;
        }

        .pending-panel {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 18px;
        }

        .pending-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 9px 14px;
            border-radius: 999px;
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
            font-weight: 900;
            font-size: .86rem;
        }

        .pending-panel h2 {
            margin: 0;
            font-size: 1.8rem;
            color: #0f172a;
        }

        .pending-panel p {
            margin: 0;
            color: #475569;
            line-height: 1.75;
        }

        .pending-steps {
            display: grid;
            gap: 12px;
            margin-top: 8px;
        }

        .pending-step {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(15,23,42,.08);
        }

        .pending-step-index {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: rgba(239,68,68,.12);
            color: #b91c1c;
            display: grid;
            place-items: center;
            font-weight: 900;
            flex: 0 0 30px;
        }

        .pending-actions {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .pending-actions .admin-btn {
            width: auto;
            justify-content: center;
            min-width: 180px;
        }

        @media (max-width: 860px) {
            .pending-shell {
                grid-template-columns: 1fr;
            }

            .pending-brand,
            .pending-panel {
                padding: 26px;
            }

            .pending-actions .admin-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="pending-shell">
        <section class="pending-brand">
            <span class="pending-brand-badge">RHS Access Control</span>
            <h1>Validation en cours</h1>
            <p>
                Votre espace prive a bien ete cree. L equipe RHS doit encore verifier votre profil
                avant d activer l acces complet a votre tableau de bord.
            </p>
        </section>

        <section class="pending-panel">
            <span class="pending-status">Compte {{ ucfirst($user->status) }}</span>
            <h2>Bonjour {{ $user->name }}</h2>
            <p>
                Votre compte {{ $user->role }} existe bien, mais il n est pas encore actif. Tant que
                l approbation admin n est pas terminee, l acces au dashboard reste bloque.
            </p>

            <div class="pending-steps">
                <div class="pending-step">
                    <div class="pending-step-index">1</div>
                    <div>Un administrateur RHS verifie votre compte et votre role d acces.</div>
                </div>
                <div class="pending-step">
                    <div class="pending-step-index">2</div>
                    <div>Une fois approuve, vous pourrez utiliser le point d acces prive habituel.</div>
                </div>
                <div class="pending-step">
                    <div class="pending-step-index">3</div>
                    <div>Si votre compte est refuse, vous devrez contacter l equipe RHS pour un nouvel examen.</div>
                </div>
            </div>

            <div class="pending-actions">
                <a href="{{ route('home') }}" class="admin-btn admin-btn-ghost">Retour au site</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-primary">Se deconnecter</button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
