<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RHS Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .portal-shell { display:grid; grid-template-columns:290px 1fr; min-height:100vh; background:radial-gradient(900px 380px at 0% 0%, rgba(239,68,68,.12), transparent 55%), #f8fafc; }
        .portal-sidebar { background:linear-gradient(180deg, #0b1324, #101b33); color:#fff; padding:28px 22px; display:flex; flex-direction:column; gap:24px; }
        .portal-brand { font-size:1.2rem; font-weight:800; letter-spacing:.02em; }
        .portal-sub { color:rgba(255,255,255,.68); font-size:.92rem; }
        .portal-nav { display:flex; flex-direction:column; gap:10px; }
        .portal-nav a { color:rgba(255,255,255,.92); text-decoration:none; padding:12px 14px; border-radius:14px; background:rgba(255,255,255,.04); font-weight:700; display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid rgba(255,255,255,.08); transition:.18s ease; }
        .portal-nav a:hover { transform:translateY(-1px); background:rgba(255,255,255,.08); }
        .portal-nav a.is-active { background:linear-gradient(135deg, #ef4444, #dc2626); border-color:rgba(239,68,68,.34); }
        .portal-main { background:transparent; padding:28px; }
        .portal-top { display:flex; justify-content:space-between; gap:20px; align-items:center; margin-bottom:24px; }
        .portal-title { margin:0; font-size:2rem; font-weight:900; }
        .portal-copy { color:#64748b; margin-top:6px; }
        .portal-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; }
        .portal-grid--four { grid-template-columns:repeat(4, minmax(0, 1fr)); }
        .portal-split { display:grid; grid-template-columns:1.05fr .95fr; gap:18px; align-items:start; }
        .portal-card { background:#fff; border-radius:18px; padding:22px; box-shadow:0 10px 30px rgba(15,23,42,.08); border:1px solid rgba(15,23,42,.06); }
        .portal-card h3 { margin:0 0 8px; font-size:1rem; }
        .portal-kpi { font-size:1.9rem; font-weight:900; margin:0; color:#0f172a; }
        .portal-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:.78rem; font-weight:800; background:#fee2e2; color:#b91c1c; }
        .portal-nav-badge { min-width:22px; height:22px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; padding:0 8px; background:#ef4444; color:#fff; font-size:.72rem; font-weight:900; }
        .portal-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
        .portal-form-grid .full { grid-column:1 / -1; }
        .portal-form-grid label { display:block; margin-bottom:8px; font-weight:800; color:#0f172a; }
        .portal-form-grid input, .portal-form-grid textarea, .portal-form-grid select { width:100%; border:1px solid #dbe2ea; border-radius:12px; padding:12px 14px; background:#fff; min-height:46px; box-sizing:border-box; }
        .portal-form-grid textarea { min-height:110px; resize:vertical; }
        .portal-section-head { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; margin-bottom:18px; }
        .portal-timeline { display:flex; flex-direction:column; gap:14px; }
        .portal-record { border:1px solid rgba(15,23,42,.08); border-radius:16px; padding:18px; background:#fff; }
        .portal-record-top { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; }
        .portal-record-copy { margin:10px 0 0; color:#475569; line-height:1.7; }
        .portal-status { display:inline-flex; align-items:center; padding:7px 11px; border-radius:999px; font-size:.78rem; font-weight:800; }
        .portal-status.is-success { background:#dcfce7; color:#166534; }
        .portal-status.is-warning { background:#fef3c7; color:#92400e; }
        .portal-status.is-danger { background:#fee2e2; color:#b91c1c; }
        .portal-status.is-info { background:#dbeafe; color:#1d4ed8; }
        .portal-status.is-muted { background:#e2e8f0; color:#475569; }
        .portal-note { margin-top:12px; padding:12px 14px; border-radius:12px; background:#f8fafc; color:#334155; line-height:1.7; border:1px solid rgba(15,23,42,.06); }
        .portal-subsection { margin-top:16px; }
        .portal-subtitle { display:block; margin-bottom:10px; color:#0f172a; font-weight:900; }
        .portal-mini-list { display:grid; gap:10px; }
        .portal-mini-item { display:grid; grid-template-columns:auto 1fr; gap:10px; align-items:start; }
        .portal-mini-copy { color:#475569; line-height:1.65; }
        .portal-inline-form { margin-top:16px; display:grid; gap:12px; }
        .portal-btn-auto { width:auto !important; }
        .form-actions-inline { display:flex; justify-content:flex-end; }
        .portal-empty { border:1px dashed rgba(15,23,42,.14); border-radius:16px; padding:20px; text-align:center; background:#fbfcff; }
        .portal-empty-title { font-weight:900; color:#0f172a; }
        .portal-empty-copy { color:#64748b; margin-top:6px; line-height:1.6; }
        .portal-action-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        .portal-action-card { display:flex; flex-direction:column; gap:6px; padding:16px; border-radius:16px; text-decoration:none; color:#0f172a; border:1px solid rgba(15,23,42,.08); background:#fff; transition:.18s ease; box-shadow:0 8px 22px rgba(15,23,42,.05); }
        .portal-action-card:hover { transform:translateY(-1px); border-color:rgba(239,68,68,.24); box-shadow:0 14px 28px rgba(15,23,42,.08); }
        .portal-action-card span { color:#64748b; line-height:1.6; }
        @media (max-width: 1100px) { .portal-grid--four { grid-template-columns:repeat(2, minmax(0, 1fr)); } .portal-split { grid-template-columns:1fr; } }
        @media (max-width: 900px) { .portal-shell { grid-template-columns:1fr; } .portal-sidebar { padding:20px; } .portal-main { padding:20px; } }
        @media (max-width: 720px) { .portal-form-grid, .portal-grid--four, .portal-action-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="portal-shell">
        <aside class="portal-sidebar">
            <div>
                <div class="portal-brand">@yield('brand', 'RHS Portal')</div>
                <div class="portal-sub">@yield('brand_sub', 'Espace prive')</div>
            </div>

            <nav class="portal-nav">
                @yield('sidebar')
            </nav>

            <div class="portal-sub" style="margin-top:auto;">
                Connecte: {{ auth()->user()->name }}<br>
                {{ auth()->user()->email }}
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-danger" style="width:100%;">Deconnexion</button>
            </form>
        </aside>

        <main class="portal-main">
            @if(session('success'))
                <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="admin-alert admin-alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="admin-alert admin-alert-danger">
                    <div class="admin-alert-title">Erreur</div>
                    <ul class="admin-error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="portal-top">
                <div>
                    <h1 class="portal-title">@yield('page_title')</h1>
                    <div class="portal-copy">@yield('page_copy')</div>
                </div>

                @yield('top_badge')
            </div>

            @yield('content')
        </main>
    </div>
</body>
</html>
