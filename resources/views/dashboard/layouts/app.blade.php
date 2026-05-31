<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RHS Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <style>
        .portal-shell { min-height:100vh; background:radial-gradient(900px 380px at 0% 0%, rgba(239,68,68,.12), transparent 55%), linear-gradient(180deg, #fbfbfd 0%, #f8fafc 100%); }
        .portal-sidebar { background:linear-gradient(180deg, #0b1324, #101b33); color:#fff; padding:28px 22px; display:flex; flex-direction:column; gap:24px; box-shadow:26px 0 40px rgba(15,23,42,.12); position:fixed; top:0; left:0; width:var(--portal-sidebar-open, 290px); height:100vh; overflow:auto; overflow-x:hidden; transition:width .28s cubic-bezier(.22,1,.36,1), padding .28s ease, box-shadow .28s ease; z-index:55; scrollbar-width:none; }
        .portal-sidebar::-webkit-scrollbar { width:0; height:0; }
        .portal-brand { font-size:1.2rem; font-weight:800; letter-spacing:.02em; }
        .portal-sub { color:rgba(255,255,255,.68); font-size:.92rem; line-height:1.6; }
        .portal-nav { display:flex; flex-direction:column; gap:10px; }
        .portal-nav a { color:rgba(255,255,255,.92); text-decoration:none; padding:12px 14px; border-radius:14px; background:rgba(255,255,255,.04); font-weight:700; display:flex; align-items:center; justify-content:flex-start; gap:12px; border:1px solid rgba(255,255,255,.08); transition:.18s ease; min-width:0; overflow-wrap:anywhere; position:relative; }
        .portal-ico { width:22px; height:22px; flex:0 0 22px; display:inline-flex; align-items:center; justify-content:center; color:rgba(255,255,255,.88); }
        .portal-ico svg { width:20px; height:20px; display:block; }
        .portal-label { min-width:0; overflow:hidden; text-overflow:ellipsis; }
        .portal-nav-badge { margin-left:auto; }
        .portal-nav a:hover { transform:translateY(-1px); background:rgba(255,255,255,.08); box-shadow:0 14px 26px rgba(15,23,42,.18); }
        .portal-nav a.is-active { background:linear-gradient(135deg, #ef4444, #dc2626); border-color:rgba(239,68,68,.34); box-shadow:0 18px 36px rgba(239,68,68,.22); }
        .portal-main { width:calc(100% - var(--portal-sidebar-open, 290px)); min-height:100vh; margin-left:var(--portal-sidebar-open, 290px); padding:30px clamp(20px, 3vw, 34px); display:flex; flex-direction:column; gap:22px; min-width:0; transition:margin-left .28s cubic-bezier(.22,1,.36,1), width .28s cubic-bezier(.22,1,.36,1); }
        .portal-content { display:grid; gap:20px; width:100%; min-width:0; align-content:start; }
        .portal-navbar { position:sticky; top:0; z-index:25; display:flex; justify-content:flex-end; gap:12px; align-items:center; flex-wrap:wrap; width:100%; padding:10px 0 14px; backdrop-filter:blur(14px); }
        .portal-navbar-actions { display:flex; justify-content:flex-end; align-items:stretch; gap:12px; flex-wrap:wrap; width:100%; }
        .portal-header-bar { display:flex; justify-content:flex-end; gap:12px; align-items:center; flex-wrap:wrap; width:100%; }
        .portal-header-actions { display:flex; justify-content:flex-end; align-items:stretch; gap:12px; flex-wrap:wrap; width:100%; }
        .portal-header-link { display:inline-flex; align-items:center; justify-content:center; gap:10px; min-height:52px; padding:0 16px; border-radius:16px; border:1px solid rgba(15,23,42,.10); background:#fff; color:#0f172a; text-decoration:none; box-shadow:0 10px 26px rgba(15,23,42,.06); white-space:nowrap; }
        .portal-header-avatar { width:38px; height:38px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; background:#fff1f2; color:#b91c1c; font-weight:900; overflow:hidden; }
        .portal-header-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .portal-top { display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:18px; align-items:flex-start; width:100%; }
        .portal-top-copy { min-width:0; max-width:860px; }
        .portal-title { margin:0; font-size:clamp(2rem, 3vw, 3rem); font-weight:900; line-height:1.08; letter-spacing:-.03em; }
        .portal-copy { color:#64748b; margin-top:8px; line-height:1.72; max-width:72ch; }
        .portal-btn-auto { width:auto !important; }

        @media (min-width: 901px) {
            .portal-sidebar { width:var(--portal-sidebar-collapsed, 82px); padding-inline:18px; box-shadow:18px 0 38px rgba(15,23,42,.16); }
            .portal-sidebar:hover,
            .portal-sidebar:focus-within { width:var(--portal-sidebar-open, 290px); padding-inline:22px; box-shadow:30px 0 60px rgba(15,23,42,.24); }
            .portal-main { width:calc(100% - var(--portal-sidebar-collapsed, 82px)); margin-left:var(--portal-sidebar-collapsed, 82px); }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav { align-items:center; }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav a {
                width:58px;
                height:58px;
                min-height:58px;
                padding:0;
                margin-inline:auto;
                justify-content:center;
                align-items:center;
                position:relative;
                border-radius:19px;
                font-size:0;
                color:transparent;
                overflow:visible;
                background:rgba(255,255,255,.045);
                border-color:rgba(255,255,255,.09);
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand,
            .portal-sidebar:not(:hover):not(:focus-within) .portal-sub,
            .portal-sidebar:not(:hover):not(:focus-within) .portal-label,
            .portal-sidebar:not(:hover):not(:focus-within) form button:not(.keep-text) {
                color:transparent;
                text-shadow:none;
                opacity:0;
                width:0;
                max-width:0;
                overflow:hidden;
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand::first-letter { color:#fff; }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav a:not(:has(.portal-ico))::before {
                content:attr(data-initial);
                width:30px;
                height:30px;
                border-radius:12px;
                background:rgba(255,255,255,.08);
                border:1px solid rgba(255,255,255,.12);
                color:#fff;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                font-size:13px;
                font-weight:900;
                box-shadow:0 10px 22px rgba(15,23,42,.18);
                flex:0 0 30px;
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav a.is-active::before {
                background:#ef4444;
                box-shadow:0 0 0 7px rgba(239,68,68,.16);
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-ico {
                width:30px;
                height:30px;
                flex-basis:30px;
                color:#fff;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                margin:0;
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-ico svg {
                width:24px;
                height:24px;
                display:block;
                margin:0 auto;
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav a.is-active {
                background:linear-gradient(135deg, rgba(239,68,68,.34), rgba(239,68,68,.16));
                border-color:rgba(239,68,68,.45);
                box-shadow:0 14px 30px rgba(239,68,68,.18), inset 4px 0 0 rgba(255,255,255,.55);
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav-badge {
                color:#fff;
            }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav-badge { position:absolute; top:9px; right:5px; transform:none; min-width:22px; height:22px; padding:0 6px; font-size:11px; line-height:22px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 10px 22px rgba(239,68,68,.38), 0 0 0 3px #0b1324; }
            .portal-sidebar:not(:hover):not(:focus-within) form button { justify-content:center; font-size:0; padding-inline:0; min-height:48px; }
            .portal-sidebar:not(:hover):not(:focus-within) .portal-sub { opacity:0; max-height:0; overflow:hidden; }
            .portal-brand,
            .portal-sub,
            .portal-nav a,
            .portal-nav a span,
            .portal-sidebar form button { transition:color .18s ease, opacity .18s ease, max-height .22s ease, padding .18s ease; }
        }

        @media (max-width: 900px) {
            .portal-sidebar { position:relative; width:100%; height:auto; padding:20px; }
            .portal-main { width:100%; margin-left:0; padding:22px 18px 28px; }
            .portal-top { grid-template-columns:1fr; }
            .portal-navbar-actions, .portal-header-actions { justify-content:flex-start; }
        }

        .portal-shell {
            --portal-sidebar-collapsed:84px;
            --portal-sidebar-open:304px;
            background:radial-gradient(1200px 600px at 15% 0%, rgba(239,35,60,.14), transparent 60%), radial-gradient(900px 520px at 100% 25%, rgba(239,35,60,.08), transparent 55%), #f6f7fb;
        }

        .portal-sidebar {
            gap:12px;
            padding:18px 10px;
            background:linear-gradient(180deg, #ffffff 0%, #fff7f8 48%, #ffffff 100%);
            color:#06142d;
            border-right:1px solid rgba(219, 229, 239, .95);
            box-shadow:10px 0 32px rgba(15, 23, 42, .06);
        }

        .portal-brand,
        .portal-sub {
            color:#06142d;
        }

        .portal-sub {
            color:#64748b;
        }

        .portal-nav {
            align-items:center;
            gap:8px;
        }

        .portal-nav a {
            width:58px;
            min-width:58px;
            max-width:58px;
            min-height:58px;
            margin:0 auto;
            padding:0;
            justify-content:center;
            gap:0;
            color:#06142d;
            background:rgba(255, 255, 255, .72);
            border-color:rgba(219, 229, 239, .72);
            border-radius:24px;
            box-shadow:0 12px 26px rgba(15, 23, 42, .035);
        }

        .portal-ico {
            width:22px;
            height:22px;
            flex:0 0 22px;
            color:currentColor;
        }

        .portal-ico svg {
            width:22px;
            height:22px;
        }

        .portal-nav a:hover,
        .portal-nav a.is-active {
            color:#ef233c;
            background:#fff1f2;
            border-color:rgba(239, 35, 60, .22);
            box-shadow:0 18px 36px rgba(239, 35, 60, .08);
        }

        .portal-nav a.is-active::before {
            content:"";
            position:absolute;
            left:7px;
            top:50%;
            width:4px;
            height:28px;
            border-radius:999px;
            background:#ef233c;
            transform:translateY(-50%);
        }

        .portal-nav-badge {
            position:absolute;
            top:8px;
            right:3px;
            min-width:22px;
            height:22px;
            padding:0 6px;
            display:inline-grid;
            place-items:center;
            border-radius:999px;
            background:#ef233c;
            color:#fff;
            border:2px solid #fff;
            box-shadow:0 12px 24px rgba(239, 35, 60, .28);
            font-size:10px;
            line-height:1;
            font-weight:950;
        }

        @media (min-width: 901px) {
            .portal-sidebar {
                width:var(--portal-sidebar-collapsed);
                padding-inline:10px;
                transition:width 260ms cubic-bezier(.22, 1, .36, 1), padding 260ms cubic-bezier(.22, 1, .36, 1), box-shadow 180ms ease;
            }

            .portal-sidebar:hover,
            .portal-sidebar:focus-within {
                width:var(--portal-sidebar-open);
                padding-inline:14px;
                box-shadow:18px 0 48px rgba(15, 23, 42, .10);
            }

            .portal-sidebar:hover .portal-nav,
            .portal-sidebar:focus-within .portal-nav {
                align-items:stretch;
            }

            .portal-sidebar:hover .portal-nav a,
            .portal-sidebar:focus-within .portal-nav a {
                width:100%;
                max-width:100%;
                min-width:0;
                min-height:56px;
                margin:0;
                padding:0 14px;
                justify-content:flex-start;
                gap:12px;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav a {
                width:58px;
                height:58px;
                min-height:58px;
                color:#06142d;
                background:rgba(255, 255, 255, .72);
                border-color:rgba(219, 229, 239, .72);
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-label {
                display:none;
            }

            .portal-sidebar:hover .portal-label,
            .portal-sidebar:focus-within .portal-label {
                display:inline-block;
                color:inherit;
                opacity:1;
                width:auto;
                max-width:100%;
                font-size:14px;
                font-weight:900;
            }

            .portal-sidebar:hover .portal-nav-badge,
            .portal-sidebar:focus-within .portal-nav-badge {
                position:static;
                min-width:26px;
                height:24px;
                margin-left:auto;
                font-size:11px;
            }
        }

        .portal-brand-block {
            flex:0 0 auto;
            width:100%;
            margin:0 auto 8px;
        }

        .portal-brand-link {
            width:100%;
            height:58px;
            min-width:0;
            max-width:100%;
            margin:0 auto;
            padding:6px 12px;
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:12px;
            border:1px solid rgba(219, 229, 239, .95);
            border-radius:24px;
            background:rgba(255, 255, 255, .90);
            box-shadow:0 14px 28px rgba(15, 23, 42, .05);
            overflow:hidden;
            text-decoration:none;
        }

        .portal-brand-logo {
            width:46px;
            height:46px;
            min-width:46px;
            flex:0 0 46px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            background:#ef233c;
            color:#fff;
            border:1px solid rgba(219, 229, 239, .90);
            box-shadow:0 14px 28px rgba(239, 35, 60, .10);
            overflow:hidden;
            font-size:13px;
            font-weight:950;
        }

        .portal-brand-logo img {
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
            border-radius:inherit;
        }

        .portal-brand-logo .portal-brand-initial {
            width:100%;
            height:100%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:inherit;
            background:#ef233c;
            color:#fff;
        }

        .portal-brand-text {
            min-width:0;
            max-width:180px;
            opacity:1;
            visibility:visible;
            overflow:hidden;
            white-space:nowrap;
            pointer-events:auto;
            transition:opacity 160ms ease, max-width 220ms ease;
        }

        .portal-brand-text .portal-brand {
            display:block;
            color:#06142d;
            font-size:15px;
            line-height:1.2;
            font-weight:950;
            letter-spacing:0;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .portal-brand-text .portal-sub {
            display:block;
            margin-top:2px;
            color:#64748b;
            font-size:12px;
            line-height:1.2;
            font-weight:750;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .portal-sidebar-footer {
            margin-top:auto;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:8px;
            width:100%;
        }

        .portal-profile-wrapper {
            position:relative;
            width:58px;
            min-width:58px;
            max-width:58px;
            margin:0 auto;
        }

        .portal-profile {
            width:58px;
            min-width:58px;
            max-width:58px;
            min-height:58px;
            padding:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:12px;
            border:1px solid rgba(219, 229, 239, .72);
            border-radius:999px;
            background:#eef3f8;
            color:#06142d;
            overflow:hidden;
            text-decoration:none;
        }

        .portal-profile-avatar {
            width:42px;
            height:42px;
            min-width:42px;
            flex:0 0 42px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            background:#ef233c;
            color:#fff;
            font-size:13px;
            font-weight:950;
            overflow:hidden;
        }

        .portal-profile-avatar img {
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
        }

        .portal-profile-info {
            min-width:0;
            max-width:0;
            opacity:0;
            visibility:hidden;
            overflow:hidden;
            white-space:nowrap;
        }

        .portal-profile-name,
        .portal-profile-email {
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .portal-profile-name {
            color:#06142d;
            font-size:14px;
            line-height:1.2;
            font-weight:950;
        }

        .portal-profile-email {
            margin-top:2px;
            color:#64748b;
            font-size:12px;
            line-height:1.2;
            font-weight:700;
        }

        .portal-logout-form {
            position:absolute;
            top:50%;
            right:10px;
            width:34px;
            height:34px;
            margin:0;
            padding:0;
            transform:translateY(-50%);
            display:none;
            z-index:2;
        }

        .portal-logout-btn,
        .portal-logout-btn.admin-btn {
            width:34px !important;
            min-width:34px !important;
            height:34px !important;
            min-height:34px !important;
            padding:0 !important;
            margin:0 !important;
            display:grid !important;
            place-items:center !important;
            border:0 !important;
            border-radius:999px !important;
            background:transparent !important;
            color:#64748b !important;
            box-shadow:none !important;
        }

        .portal-logout-btn:hover {
            background:rgba(255, 255, 255, .74) !important;
            color:#ef233c !important;
            transform:translateX(1px) !important;
        }

        .portal-logout-btn svg {
            width:18px;
            height:18px;
            display:block;
            stroke:currentColor;
        }

        .portal-logout-btn span:not(.portal-logout-ico) {
            display:none;
        }

        @media (min-width: 901px) {
            .portal-sidebar:hover .portal-brand-block,
            .portal-sidebar:focus-within .portal-brand-block,
            .portal-sidebar:hover .portal-profile-wrapper,
            .portal-sidebar:focus-within .portal-profile-wrapper {
                width:100%;
                min-width:0;
                max-width:100%;
                margin-left:0;
                margin-right:0;
            }

            .portal-sidebar:hover .portal-brand-link,
            .portal-sidebar:focus-within .portal-brand-link {
                width:100%;
                max-width:100%;
                min-width:0;
                height:68px;
                justify-content:flex-start;
                padding:8px 12px;
                margin:0;
            }

            .portal-sidebar:hover .portal-brand-text,
            .portal-sidebar:focus-within .portal-brand-text,
            .portal-sidebar:hover .portal-profile-info,
            .portal-sidebar:focus-within .portal-profile-info {
                max-width:180px;
                opacity:1;
                visibility:visible;
                pointer-events:auto;
            }

            .portal-sidebar:hover .portal-profile,
            .portal-sidebar:focus-within .portal-profile {
                width:100%;
                min-width:0;
                max-width:100%;
                min-height:58px;
                justify-content:flex-start;
                padding:8px 52px 8px 8px;
            }

            .portal-sidebar:hover .portal-logout-form,
            .portal-sidebar:focus-within .portal-logout-form {
                display:block;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand,
            .portal-sidebar:not(:hover):not(:focus-within) .portal-sub {
                opacity:1;
                width:auto;
                max-width:none;
                color:inherit;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand::first-letter {
                color:inherit;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand-text,
            .portal-sidebar:not(:hover):not(:focus-within) .portal-profile-info {
                display:block;
                opacity:0;
                visibility:hidden;
                max-width:0;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-sidebar-footer,
            .portal-sidebar:not(:hover):not(:focus-within) .portal-profile-wrapper {
                width:58px !important;
                min-width:58px !important;
                max-width:58px !important;
                margin-left:auto !important;
                margin-right:auto !important;
                align-items:center !important;
                justify-content:center !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-profile {
                width:58px !important;
                min-width:58px !important;
                max-width:58px !important;
                height:58px !important;
                min-height:58px !important;
                padding:8px !important;
                margin:0 auto !important;
                display:grid !important;
                place-items:center !important;
                justify-content:center !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-profile-avatar {
                width:42px !important;
                min-width:42px !important;
                height:42px !important;
                margin:0 !important;
                transform:none !important;
                display:grid !important;
                place-items:center !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand-block {
                width:58px;
                min-width:58px;
                max-width:58px;
                margin-left:auto;
                margin-right:auto;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-brand-link {
                width:58px;
                min-width:58px;
                max-width:58px;
                height:58px;
                padding:6px;
                justify-content:center;
                gap:0;
            }
        }

        @media (min-width: 901px) {
            .portal-sidebar:hover + .portal-main,
            .portal-sidebar:focus-within + .portal-main {
                margin-left:var(--portal-sidebar-open) !important;
                width:calc(100% - var(--portal-sidebar-open)) !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) {
                width:var(--portal-sidebar-collapsed) !important;
                padding-inline:10px !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav {
                align-items:center !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav > a {
                width:58px !important;
                min-width:58px !important;
                max-width:58px !important;
                height:58px !important;
                min-height:58px !important;
                max-height:58px !important;
                margin:0 auto !important;
                padding:0 !important;
                display:flex !important;
                align-items:center !important;
                justify-content:center !important;
                gap:0 !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-ico {
                width:22px !important;
                height:22px !important;
                min-width:22px !important;
                flex:0 0 22px !important;
                margin:0 !important;
                display:inline-flex !important;
                align-items:center !important;
                justify-content:center !important;
                color:currentColor !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-ico svg {
                width:22px !important;
                height:22px !important;
                margin:0 !important;
                display:block !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-label {
                display:none !important;
                opacity:0 !important;
                visibility:hidden !important;
                max-width:0 !important;
            }

            .portal-sidebar:hover .portal-nav > a,
            .portal-sidebar:focus-within .portal-nav > a {
                width:100% !important;
                min-width:0 !important;
                max-width:100% !important;
                min-height:56px !important;
                max-height:none !important;
                margin:0 !important;
                padding:0 14px !important;
                display:flex !important;
                align-items:center !important;
                justify-content:flex-start !important;
                gap:12px !important;
            }

            .portal-sidebar:hover .portal-nav > a > .portal-label,
            .portal-sidebar:focus-within .portal-nav > a > .portal-label {
                display:inline-block !important;
                flex:1 1 auto !important;
                width:auto !important;
                max-width:100% !important;
                min-width:0 !important;
                opacity:1 !important;
                visibility:visible !important;
                color:inherit !important;
                font-size:14px !important;
                line-height:1.15 !important;
                font-weight:900 !important;
                white-space:nowrap !important;
                overflow:hidden !important;
                text-overflow:ellipsis !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav > a > .portal-ico {
                position:absolute !important;
                left:50% !important;
                top:50% !important;
                transform:translate(-50%, -50%) !important;
                width:22px !important;
                height:22px !important;
                min-width:22px !important;
                max-width:22px !important;
                flex:0 0 22px !important;
                margin:0 !important;
                padding:0 !important;
                display:grid !important;
                place-items:center !important;
            }

            .portal-sidebar:not(:hover):not(:focus-within) .portal-nav > a > .portal-ico svg {
                width:22px !important;
                height:22px !important;
                margin:0 !important;
                display:block !important;
            }

            .portal-sidebar:hover .portal-nav > a > .portal-ico,
            .portal-sidebar:focus-within .portal-nav > a > .portal-ico {
                position:static !important;
                left:auto !important;
                top:auto !important;
                transform:none !important;
                width:22px !important;
                height:22px !important;
                min-width:22px !important;
                flex:0 0 22px !important;
                display:inline-grid !important;
                place-items:center !important;
            }
        }

        /* Final portal brand override: the portal rail is visually expanded, so keep the name visible. */
        .portal-sidebar .portal-brand-block {
            width:100% !important;
            min-width:0 !important;
            max-width:100% !important;
            margin:0 0 8px !important;
        }

        .portal-sidebar .portal-brand-link {
            width:100% !important;
            min-width:0 !important;
            max-width:100% !important;
            height:68px !important;
            padding:8px 12px !important;
            display:grid !important;
            grid-template-columns:46px minmax(0, 1fr) !important;
            align-items:center !important;
            justify-content:stretch !important;
            gap:12px !important;
        }

        .portal-sidebar .portal-brand-logo {
            position:static !important;
            grid-column:1 !important;
            width:46px !important;
            height:46px !important;
            min-width:46px !important;
            max-width:46px !important;
            margin:0 !important;
            transform:none !important;
            inset:auto !important;
            flex:0 0 46px !important;
            background:#fff !important;
        }

        .portal-sidebar .portal-brand-logo img {
            width:100% !important;
            height:100% !important;
            object-fit:contain !important;
        }

        .portal-sidebar .portal-brand-text {
            position:static !important;
            grid-column:2 !important;
            display:block !important;
            flex:1 1 auto !important;
            width:auto !important;
            min-width:0 !important;
            max-width:100% !important;
            opacity:1 !important;
            visibility:visible !important;
            color:#06142d !important;
            pointer-events:auto !important;
            transform:none !important;
            text-align:left !important;
            line-height:1.15 !important;
        }

        .portal-sidebar .portal-brand-text .portal-brand,
        .portal-sidebar .portal-brand-text .portal-sub {
            display:block !important;
            opacity:1 !important;
            visibility:visible !important;
            color:inherit !important;
            white-space:nowrap !important;
            overflow:hidden !important;
            text-overflow:ellipsis !important;
            text-align:left !important;
            transform:none !important;
        }

        .portal-sidebar .portal-brand-text .portal-brand {
            font-size:14px !important;
            font-weight:950 !important;
        }

        .portal-sidebar .portal-brand-text .portal-sub {
            margin-top:3px !important;
            color:#64748b !important;
            font-size:12px !important;
            font-weight:750 !important;
        }

        .rhs-portal-identity,
        .rhs-portal-identity *,
        .rhs-portal-identity *::before,
        .rhs-portal-identity *::after {
            box-sizing:border-box !important;
        }

        .rhs-portal-identity {
            width:100% !important;
            min-width:0 !important;
            max-width:100% !important;
            height:68px !important;
            margin:0 0 18px !important;
            padding:0 !important;
            display:block !important;
            position:relative !important;
            z-index:5 !important;
        }

        .rhs-portal-identity-link {
            width:100% !important;
            height:68px !important;
            min-height:68px !important;
            padding:8px 12px !important;
            display:grid !important;
            grid-template-columns:46px minmax(0, 1fr) !important;
            align-items:center !important;
            gap:12px !important;
            border:1px solid rgba(219,229,239,.95) !important;
            border-radius:24px !important;
            background:rgba(255,255,255,.92) !important;
            box-shadow:0 14px 28px rgba(15,23,42,.05) !important;
            color:#06142d !important;
            text-decoration:none !important;
            overflow:hidden !important;
        }

        .rhs-portal-identity-logo {
            position:static !important;
            width:46px !important;
            height:46px !important;
            min-width:46px !important;
            max-width:46px !important;
            display:grid !important;
            place-items:center !important;
            border-radius:999px !important;
            border:1px solid rgba(219,229,239,.9) !important;
            background:#fff !important;
            box-shadow:0 10px 20px rgba(15,23,42,.04) !important;
            overflow:hidden !important;
            transform:none !important;
        }

        .rhs-portal-identity-logo img {
            width:100% !important;
            height:100% !important;
            display:block !important;
            object-fit:contain !important;
            border-radius:inherit !important;
        }

        .rhs-portal-identity-copy {
            min-width:0 !important;
            display:block !important;
            opacity:1 !important;
            visibility:visible !important;
            transform:none !important;
            color:#06142d !important;
            line-height:1.15 !important;
        }

        .rhs-portal-identity-name,
        .rhs-portal-identity-sub {
            display:block !important;
            min-width:0 !important;
            max-width:100% !important;
            white-space:nowrap !important;
            overflow:hidden !important;
            text-overflow:ellipsis !important;
            opacity:1 !important;
            visibility:visible !important;
            transform:none !important;
            text-align:left !important;
        }

        .rhs-portal-identity-name {
            color:#06142d !important;
            font-size:14px !important;
            font-weight:950 !important;
        }

        .rhs-portal-identity-sub {
            margin-top:4px !important;
            color:#64748b !important;
            font-size:12px !important;
            font-weight:750 !important;
        }

        .portal-nav a.is-active::before {
            content:none !important;
            display:none !important;
        }

        .portal-profile-avatar,
        .portal-header-avatar,
        .rhs-portal-identity-logo {
            display:grid !important;
            place-items:center !important;
            text-align:center !important;
            line-height:1 !important;
        }

        .portal-profile-avatar img,
        .portal-header-avatar img,
        .rhs-portal-identity-logo img {
            width:100% !important;
            height:100% !important;
            display:block !important;
            object-fit:cover !important;
            object-position:center center !important;
        }

        .rhs-portal-identity-logo img {
            object-fit:contain !important;
        }
    </style>
</head>
<body>
    <div class="portal-shell">
        @php
            $portalIsEmployee = auth()->user()->hasAnyRole([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_SUPERVISOR]);
            $portalDashboardRoute = $portalIsEmployee ? route('employee.dashboard') : route('client.dashboard');
            $portalProfileRoute = $portalIsEmployee ? route('employee.profile.edit') : route('client.profile.edit');
        @endphp

        <aside class="portal-sidebar">
            <div class="rhs-portal-identity">
                <a href="{{ $portalDashboardRoute }}" class="rhs-portal-identity-link" aria-label="RHS dashboard">
                    <span class="rhs-portal-identity-logo">
                        <img src="{{ asset('images/rhs-logo.png') }}" alt="RHS">
                    </span>
                    <span class="rhs-portal-identity-copy">
                        <span class="rhs-portal-identity-name">{{ auth()->user()->name }}</span>
                        <span class="rhs-portal-identity-sub">@yield('brand_sub', 'Espace prive')</span>
                    </span>
                </a>
            </div>

            <nav class="portal-nav">
                @yield('sidebar')
            </nav>

            <div class="portal-sidebar-footer">
                <div class="portal-profile-wrapper">
                    <a href="{{ $portalProfileRoute }}" class="portal-profile">
                        <span class="portal-profile-avatar">
                            @if(auth()->user()->profile_photo_url)
                                <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="portal-profile-info">
                            <span class="portal-profile-email">{{ auth()->user()->email }}</span>
                        </span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="portal-logout-form">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-danger portal-logout-btn" aria-label="Deconnexion">
                            <span class="portal-logout-ico">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M15 8l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M19 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M12 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>Deconnexion</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-navbar">
                <div class="portal-navbar-actions">
                @if(auth()->user()->hasAnyRole([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_SUPERVISOR]))
                    <a href="{{ route('employee.messages.index', ['empty' => 1]) }}" class="portal-header-link">
                        <span>Messages</span>
                        <span
                            class="portal-nav-badge"
                            data-conversation-notification-badge
                            style="{{ ($portalHeaderData['messages_count'] ?? 0) > 0 ? '' : 'display:none;' }}"
                            {{ ($portalHeaderData['messages_count'] ?? 0) > 0 ? '' : 'hidden' }}
                        >{{ $portalHeaderData['messages_count'] ?? 0 }}</span>
                    </a>
                @endif

                <a href="{{ $portalProfileRoute }}" class="portal-header-link">
                    <span class="portal-header-avatar">
                        @if(auth()->user()->profile_photo_url)
                            <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </span>
                    <span>Profil</span>
                </a>
                </div>
            </div>
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
                <div class="portal-top-copy">
                    <h1 class="portal-title">@yield('page_title')</h1>
                    <div class="portal-copy">@yield('page_copy')</div>
                </div>

                @yield('top_badge')
            </div>

            <div class="portal-content">
                @yield('content')
            </div>
        </main>
    </div>
    @include('partials.rhs-select-enhancer')
    @include('partials.rhs-feedback')
    @if(auth()->user()->hasAnyRole([\App\Models\User::ROLE_EMPLOYEE, \App\Models\User::ROLE_SUPERVISOR]))
        @include('partials.rhs-notification-worker', ['notificationEndpoint' => route('employee.notifications.sidebar')])
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const portalSidebar = document.querySelector('.portal-sidebar');

            if (!portalSidebar) {
                return;
            }

            portalSidebar.querySelectorAll('.portal-nav a').forEach(function (link) {
                const text = (link.textContent || '').replace(/\d+/g, '').trim();

                if (!link.dataset.initial && text) {
                    const words = text.split(/\s+/).filter(Boolean);
                    link.dataset.initial = words.length > 1
                        ? (words[0].charAt(0) + words[1].charAt(0)).toUpperCase()
                        : words[0].slice(0, 2).toUpperCase();
                }
            });

            portalSidebar.addEventListener('mouseleave', function () {
                if (portalSidebar.contains(document.activeElement)) {
                    document.activeElement.blur();
                }

                window.setTimeout(function () {
                    if (!portalSidebar.matches(':hover') && !portalSidebar.matches(':focus-within')) {
                        portalSidebar.scrollTop = 0;
                    }
                }, 260);
            });
        });
    </script>
    <script src="{{ asset('js/rhs-ui.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
