<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title','Admin – RHS')</title>
  <link rel="icon" href="{{ asset('images/ChatGPT%20Image%20Jan%2015%2C%202026%2C%2009_50_56%20PM.png') }}" type="image/png">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  @stack('styles')
</head>
<body class="admin-body">
  @php
    $hasClientAlertsTable = \Illuminate\Support\Facades\Schema::hasTable('client_request_alerts');
    $sidebarPendingClientRequests = \App\Models\RecruitmentRequest::query()
      ->whereNotNull('client_user_id')
      ->whereNull('admin_seen_at')
      ->count();

    $sidebarClientAlerts = $hasClientAlertsTable
      ? \App\Models\ClientRequestAlert::query()->whereNull('admin_seen_at')->count()
      : 0;

    $sidebarUnreadApplications = \App\Models\JobApplication::query()
      ->whereNull('admin_seen_at')
      ->count();

    $sidebarPendingUsers = \App\Models\User::query()
      ->where('status', \App\Models\User::STATUS_PENDING)
      ->count();

    $sidebarNewEmployeeReports = \App\Models\EmployeeReport::query()
      ->whereNull('admin_seen_at')
      ->count();

    $sidebarPendingLeaveRequests = \App\Models\EmployeeLeaveRequest::query()
      ->whereNull('admin_seen_at')
      ->count();

    $sidebarUnreadEmployeeInternalRequests = \App\Models\EmployeeInternalRequest::query()
      ->whereNull('admin_seen_at')
      ->count();
  @endphp

  <aside class="admin-sidebar">

    <div class="admin-brand">
      <a href="{{ route('admin.dashboard') }}" class="admin-brand-link">
        <div class="admin-brand-logo">
          <img
            src="{{ asset('images/ChatGPT Image Jan 15, 2026, 09_50_56 PM.png') }}"
            alt="RHS"
            loading="lazy"
          >
        </div>

        <div class="admin-brand-text">
          <div class="admin-brand-title">RHS Admin</div>
          <div class="admin-brand-sub">Dashboard</div>
        </div>
      </a>
    </div>

    <nav class="admin-nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Dashboard
      </a>

      <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 10C9.66 10 11 8.66 11 7S9.66 4 8 4 5 5.34 5 7s1.34 3 3 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 20v-1a4 4 0 0 1 4-4h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Utilisateurs
        @if($sidebarPendingUsers > 0)
          <span class="admin-nav-badge">{{ $sidebarPendingUsers }}</span>
        @endif
      </a>

      <a href="{{ route('admin.client-recruitment-requests.index') }}" class="{{ request()->routeIs('admin.client-recruitment-requests.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M6 7h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        Demandes clients
        @if($sidebarPendingClientRequests > 0)
          <span class="admin-nav-badge">{{ $sidebarPendingClientRequests }}</span>
        @endif
      </a>

      <a href="{{ route('admin.client-request-alerts.index') }}" class="{{ request()->routeIs('admin.client-request-alerts.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 5h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 10h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        Relances clients
        @if($sidebarClientAlerts > 0)
          <span class="admin-nav-badge">{{ $sidebarClientAlerts }}</span>
        @endif
      </a>

      <a href="{{ route('admin.employee-reports.index') }}" class="{{ request()->routeIs('admin.employee-reports.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 9h6M9 13h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        Rapports employes
        @if($sidebarNewEmployeeReports > 0)
          <span class="admin-nav-badge">{{ $sidebarNewEmployeeReports }}</span>
        @endif
      </a>

      <a href="{{ route('admin.employee-leave-requests.index') }}" class="{{ request()->routeIs('admin.employee-leave-requests.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <path d="M9 14l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Conges employes
        @if($sidebarPendingLeaveRequests > 0)
          <span class="admin-nav-badge">{{ $sidebarPendingLeaveRequests }}</span>
        @endif
      </a>

      <a href="{{ route('admin.employee-internal-requests.index') }}" class="{{ request()->routeIs('admin.employee-internal-requests.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 14c0 1.1-.9 2-2 2H8l-5 4V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v9Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Requetes internes
        @if($sidebarUnreadEmployeeInternalRequests > 0)
          <span class="admin-nav-badge">{{ $sidebarUnreadEmployeeInternalRequests }}</span>
        @endif
      </a>

      <a href="{{ route('admin.offers.index') }}" class="{{ request()->routeIs('admin.offers.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 7h10v14H7V7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 3h6v4H9V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        Offres
      </a>

      <a href="{{ route('admin.applications.index') }}" class="{{ request()->routeIs('admin.applications.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 10C9.66 10 11 8.66 11 7S9.66 4 8 4 5 5.34 5 7s1.34 3 3 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 20v-1a4 4 0 0 1 4-4h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Candidatures
        @if($sidebarUnreadApplications > 0)
          <span class="admin-nav-badge">{{ $sidebarUnreadApplications }}</span>
        @endif
      </a>

      <a href="{{ route('admin.cvs.index') }}" class="{{ request()->routeIs('admin.cvs.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 3v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 13h6M9 17h6M9 9h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        CV Bank
      </a>
      <a href="{{ route('admin.external-cvs.index') }}"
   class="{{ request()->routeIs('admin.external-cvs.*') ? 'is-active' : '' }}">
  <span class="admin-ico">
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M4 6h16M4 12h16M4 18h16"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"/>
    </svg>
  </span>
  Base externe
</a>

      <a href="{{ route('admin.recruitment_requests.create') }}" class="{{ request()->routeIs('admin.recruitment_requests.*') || request()->routeIs('admin.matches.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M9 3v3m6-3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <rect x="5" y="6" width="14" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <path d="M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        AI Matching
      </a>

      <a href="{{ route('admin.formations.index') }}" class="{{ request()->routeIs('admin.formations.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M3 7l9-4 9 4-9 4-9-4Z"
                  stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21 10v6"
                  stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round"/>
            <path d="M5 12v5c0 1.1 3.13 2 7 2s7-.9 7-2v-5"
                  stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round"/>
          </svg>
        </span>
        Catalogue des formations
      </a>

      <a href="{{ route('admin.content.index') }}" class="{{ request()->routeIs('admin.content.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 3h10v18H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 7h4M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        Contenu site
      </a>
    </nav>

    <div class="admin-sidebar-footer">
      <a href="{{ route('home') }}" class="admin-btn admin-btn-ghost">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14 3h7v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 14 21 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Voir le site
      </a>

      <a href="{{ route('admin.profile.edit') }}"
         class="admin-profile {{ request()->routeIs('admin.profile.*') ? 'is-active' : '' }}">
        <div class="admin-profile-avatar">
          {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>

        <div class="admin-profile-info">
          <div class="admin-profile-name">
            {{ auth()->user()->name }}
          </div>
          <div class="admin-profile-email">
            {{ auth()->user()->email }}
          </div>
        </div>

        <span class="admin-profile-ico">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </a>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="admin-btn admin-btn-danger">
          <span class="admin-ico">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M15 12H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M21 3v18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </span>
          Déconnexion
        </button>
      </form>
    </div>

  </aside>

  <main class="admin-main">

    <header class="admin-top">
      <div class="admin-top-left">
        <h1 class="admin-title">@yield('page_title','')</h1>

        @hasSection('page_subtitle')
          <p class="admin-subtitle">@yield('page_subtitle')</p>
        @endif
      </div>

      <div class="admin-top-actions">
        @yield('top_actions')
      </div>
    </header>

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

    <div class="admin-content">
      @yield('content')
    </div>

  </main>

  @stack('scripts')
</body>
</html>
