<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title','Admin - RHS')</title>
  <link rel="icon" href="{{ asset('images/ChatGPT%20Image%20Jan%2015%2C%202026%2C%2009_50_56%20PM.png') }}" type="image/png">
 <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">

@stack('styles')

<link rel="stylesheet" href="{{ asset('css/admin-sidebar-fix.css') }}?v={{ filemtime(public_path('css/admin-sidebar-fix.css')) }}">
</head>
<body class="admin-body">
  @php
    $sidebarItems = data_get($sidebarNotifications ?? [], 'items', []);
    $sidebarGroups = data_get($sidebarNotifications ?? [], 'groups', []);
    $clientRegister = data_get($sidebarNotifications ?? [], 'client_register', [
      'enabled' => false,
      'url' => null,
    ]);

    $sidebarPendingUsers = (int) ($sidebarItems['users'] ?? 0);
    $sidebarNewEmployeeReports = (int) ($sidebarItems['employee_reports'] ?? 0);
    $sidebarEmployeeAssignments = (int) ($sidebarItems['employee_assignments'] ?? 0);
    $sidebarPendingLeaveRequests = (int) ($sidebarItems['employee_leave_requests'] ?? 0);
    $sidebarUnreadEmployeeInternalRequests = (int) ($sidebarItems['employee_internal_requests'] ?? 0);
    $sidebarPendingClientRequests = (int) ($sidebarItems['client_requests'] ?? 0);
    $sidebarClientAlerts = (int) ($sidebarItems['client_alerts'] ?? 0);
    $sidebarUnreadApplications = (int) ($sidebarItems['applications'] ?? 0);
    $sidebarUnreadMatchingResults = (int) ($sidebarItems['matching_history'] ?? 0);
    $sidebarCvImportBatches = (int) ($sidebarItems['cv_imports'] ?? 0);
    $sidebarExternalBatches = (int) ($sidebarItems['external_batches'] ?? 0);
    $sidebarUnreadConversations = (int) ($sidebarItems['conversations'] ?? 0);
    $sidebarUnreadMeetings = (int) ($sidebarItems['meetings'] ?? 0);

    $isEmployeeAssignmentsRoute = request()->routeIs('admin.client-recruitment-requests.index')
      && request()->filled('assignment')
      && request('assignment') !== 'all';

    $employeesNavOpen = request()->routeIs('admin.employee-reports.*')
      || $isEmployeeAssignmentsRoute
      || request()->routeIs('admin.employee-leave-requests.*')
      || request()->routeIs('admin.employee-internal-requests.*');

    $clientsNavOpen = (request()->routeIs('admin.client-recruitment-requests.*') && !$isEmployeeAssignmentsRoute)
      || request()->routeIs('admin.client-request-alerts.*');

    $recruitmentNavOpen = request()->routeIs('admin.recruitment_requests.*')
      || request()->routeIs('admin.matching-history.*')
      || request()->routeIs('admin.cvs.*')
      || request()->routeIs('admin.external-cvs.*')
      || request()->routeIs('admin.applications.*')
      || request()->routeIs('admin.offers.*');

    $platformNavOpen = request()->routeIs('admin.formations.*')
      || request()->routeIs('admin.content.*');
  @endphp

  <aside class="admin-sidebar">

  <a href="{{ route('admin.dashboard') }}" class="admin-brand admin-brand-link" aria-label="RHS Admin Dashboard">
    <span class="admin-brand-logo">
      <img src="{{ asset('images/ChatGPT%20Image%20Jan%2015%2C%202026%2C%2009_50_56%20PM.png') }}" alt="RHS Admin">
    </span>
    <span class="admin-brand-text">
      <span class="admin-brand-title">RHS Admin</span>
      <span class="admin-brand-sub">Dashboard</span>
    </span>
  </a>

    <nav class="admin-nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span>Dashboard</span>
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
        <span>Utilisateurs</span>
        @if($sidebarPendingUsers > 0)
          <span class="admin-nav-badge">{{ $sidebarPendingUsers }}</span>
        @endif
      </a>

      <details class="sidebar-group {{ $employeesNavOpen ? 'is-active' : '' }}" {{ $employeesNavOpen ? 'open' : '' }}>
        <summary class="sidebar-group-toggle">
          <span class="sidebar-group-label">
            <span class="admin-ico">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 10C9.66 10 11 8.66 11 7S9.66 4 8 4 5 5.34 5 7s1.34 3 3 3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 20v-1a4 4 0 0 1 4-4h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="admin-nav-label">Employes</span>
          </span>
          @if(($sidebarGroups['employees'] ?? 0) > 0)
            <span class="admin-nav-badge">{{ $sidebarGroups['employees'] }}</span>
          @endif
          <span class="sidebar-group-caret" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </summary>

        <div class="sidebar-submenu">
          <a href="{{ route('admin.client-recruitment-requests.index', ['assignment' => 'assigned_unseen']) }}" class="{{ $isEmployeeAssignmentsRoute ? 'is-active' : '' }}">
            <span>Affectations recrutement</span>
            @if($sidebarEmployeeAssignments > 0)
              <span class="admin-nav-badge">{{ $sidebarEmployeeAssignments }}</span>
            @endif
          </a>

          <a href="{{ route('admin.employee-reports.index') }}" class="{{ request()->routeIs('admin.employee-reports.*') ? 'is-active' : '' }}">
            <span>Rapports employes</span>
            @if($sidebarNewEmployeeReports > 0)
              <span class="admin-nav-badge">{{ $sidebarNewEmployeeReports }}</span>
            @endif
          </a>

          <a href="{{ route('admin.employee-leave-requests.index') }}" class="{{ request()->routeIs('admin.employee-leave-requests.*') ? 'is-active' : '' }}">
            <span>Conges employes</span>
            @if($sidebarPendingLeaveRequests > 0)
              <span class="admin-nav-badge">{{ $sidebarPendingLeaveRequests }}</span>
            @endif
          </a>

          <a href="{{ route('admin.employee-internal-requests.index') }}" class="{{ request()->routeIs('admin.employee-internal-requests.*') ? 'is-active' : '' }}">
            <span>Requetes internes</span>
            @if($sidebarUnreadEmployeeInternalRequests > 0)
              <span class="admin-nav-badge">{{ $sidebarUnreadEmployeeInternalRequests }}</span>
            @endif
          </a>
        </div>
      </details>

      <details class="sidebar-group {{ $clientsNavOpen ? 'is-active' : '' }}" {{ $clientsNavOpen ? 'open' : '' }}>
        <summary class="sidebar-group-toggle">
          <span class="sidebar-group-label">
            <span class="admin-ico">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 5h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 10h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="admin-nav-label">Clients</span>
          </span>
          @if(($sidebarGroups['clients'] ?? 0) > 0)
            <span class="admin-nav-badge">{{ $sidebarGroups['clients'] }}</span>
          @endif
          <span class="sidebar-group-caret" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </summary>

        <div class="sidebar-submenu">
          <a href="{{ route('admin.client-recruitment-requests.index') }}" class="{{ request()->routeIs('admin.client-recruitment-requests.*') ? 'is-active' : '' }}">
            <span>Demandes clients</span>
            @if($sidebarPendingClientRequests > 0)
              <span class="admin-nav-badge">{{ $sidebarPendingClientRequests }}</span>
            @endif
          </a>

          <a href="{{ route('admin.client-request-alerts.index') }}" class="{{ request()->routeIs('admin.client-request-alerts.*') ? 'is-active' : '' }}">
            <span>Relances clients</span>
            @if($sidebarClientAlerts > 0)
              <span class="admin-nav-badge">{{ $sidebarClientAlerts }}</span>
            @endif
          </a>
        </div>
      </details>

      <details class="sidebar-group {{ $recruitmentNavOpen ? 'is-active' : '' }}" {{ $recruitmentNavOpen ? 'open' : '' }}>
        <summary class="sidebar-group-toggle">
          <span class="sidebar-group-label">
            <span class="admin-ico">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M9 3v3m6-3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <rect x="5" y="6" width="14" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/>
                <path d="M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="admin-nav-label">Recrutement &amp; AI Matching</span>
          </span>
          @if(($sidebarGroups['recruitment'] ?? 0) > 0)
            <span class="admin-nav-badge">{{ $sidebarGroups['recruitment'] }}</span>
          @endif
          <span class="sidebar-group-caret" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </summary>

        <div class="sidebar-submenu">
          <a href="{{ route('admin.recruitment_requests.create') }}" class="{{ request()->routeIs('admin.recruitment_requests.create') ? 'is-active' : '' }}">
            <span>AI Matching</span>
          </a>

          <a href="{{ route('admin.matching-history.index') }}" class="{{ request()->routeIs('admin.matching-history.*') || request()->routeIs('admin.recruitment_requests.results') ? 'is-active' : '' }}">
            <span>Historique Matching</span>
            @if($sidebarUnreadMatchingResults > 0)
              <span class="admin-nav-badge">{{ $sidebarUnreadMatchingResults }}</span>
            @endif
          </a>

          <a href="{{ route('admin.applications.index') }}" class="{{ request()->routeIs('admin.applications.*') ? 'is-active' : '' }}">
            <span>Candidatures</span>
            @if($sidebarUnreadApplications > 0)
              <span class="admin-nav-badge">{{ $sidebarUnreadApplications }}</span>
            @endif
          </a>

          <a href="{{ route('admin.offers.index') }}" class="{{ request()->routeIs('admin.offers.*') ? 'is-active' : '' }}">
            <span>Offres</span>
          </a>

          <a href="{{ route('admin.cvs.index') }}" class="{{ request()->routeIs('admin.cvs.*') ? 'is-active' : '' }}">
            <span>CV Bank</span>
            @if($sidebarCvImportBatches > 0)
              <span class="admin-nav-badge">{{ $sidebarCvImportBatches }}</span>
            @endif
          </a>

          <a href="{{ route('admin.cvs.archived') }}" class="{{ request()->routeIs('admin.cvs.archived') ? 'is-active' : '' }}">
            <span>Archives CV</span>
          </a>

          <a href="{{ route('admin.external-cvs.index') }}" class="{{ request()->routeIs('admin.external-cvs.*') ? 'is-active' : '' }}">
            <span>Base externe</span>
            @if($sidebarExternalBatches > 0)
              <span class="admin-nav-badge">{{ $sidebarExternalBatches }}</span>
            @endif
          </a>
        </div>
      </details>

      <details class="sidebar-group {{ $platformNavOpen ? 'is-active' : '' }}" {{ $platformNavOpen ? 'open' : '' }}>
        <summary class="sidebar-group-toggle">
          <span class="sidebar-group-label">
            <span class="admin-ico">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 3h10v18H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 7h4M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="admin-nav-label">Plateforme</span>
          </span>
          @if(($sidebarGroups['platform'] ?? 0) > 0)
            <span class="admin-nav-badge">{{ $sidebarGroups['platform'] }}</span>
          @endif
          <span class="sidebar-group-caret" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </summary>

        <div class="sidebar-submenu">
          <a href="{{ route('admin.formations.index') }}" class="{{ request()->routeIs('admin.formations.*') ? 'is-active' : '' }}">
            <span>Catalogue des formations</span>
          </a>

          <a href="{{ route('admin.content.index') }}" class="{{ request()->routeIs('admin.content.*') ? 'is-active' : '' }}">
            <span>Contenu site</span>
          </a>
        </div>
      </details>

      <a href="{{ route('admin.meetings.index') }}" class="{{ request()->routeIs('admin.meetings.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 3v3m10-3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <span>Reunions</span>
        @if($sidebarUnreadMeetings > 0)
          <span class="admin-nav-badge">{{ $sidebarUnreadMeetings }}</span>
        @endif
      </a>

      <a href="{{ route('admin.rh-resources.index') }}" class="{{ request()->routeIs('admin.rh-resources.*') ? 'is-active' : '' }}">
        <span class="admin-ico">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 4h9l3 3v13H6V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M14 4v4h4M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </span>
        <span>Ressources RH</span>
      </a>
    </nav>

    <div class="admin-sidebar-footer">
      <div class="admin-sidebar-card panel-safe">
        <div class="admin-sidebar-card-head">
          <strong>Lien inscription client</strong>
        </div>

        @if($clientRegister['enabled'] && $clientRegister['url'])
          <div class="admin-copy-box">
            <input
              type="text"
              id="client-register-link"
              class="admin-copy-input"
              readonly
              value="{{ $clientRegister['url'] }}"
            >
            <button
              type="button"
              class="admin-btn admin-btn-ghost admin-btn-sm"
              data-copy-target="client-register-link"
              data-copy-feedback="client-register-feedback"
            >
              Copier
            </button>
          </div>
          <div class="admin-copy-feedback" id="client-register-feedback">Lien copie</div>
        @else
          <div class="admin-copy-disabled">L inscription client n est pas active sur cette installation.</div>
        @endif
      </div>

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
          @if(auth()->user()->profile_photo_url)
            <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}">
          @else
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
          @endif
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
      <a href="{{ config('odoo.recruitment_url') ?: rtrim(config('odoo.url'), '/') . '/odoo/recruitment' }}"
   class="admin-btn admin-btn-ghost"
   target="_blank">
    <span class="admin-ico">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    </span>
    Odoo Recruitment
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

    <div class="admin-navbar">
      <div class="admin-global-search" data-search-endpoint="{{ route('admin.search.suggest') }}">
        <form method="GET" action="{{ route('admin.search.index') }}">
          <input type="search" name="q" id="admin-global-search-input" placeholder="Rechercher partout dans le dashboard admin">
        </form>
        <div class="admin-search-suggestions" id="admin-search-suggestions"></div>
      </div>

      <div class="admin-navbar-actions">
        <a href="{{ route('admin.conversations.index') }}" class="btn btn-ghost">
          Messages
          @if($sidebarUnreadConversations > 0)
            <span class="admin-nav-badge">{{ $sidebarUnreadConversations }}</span>
          @endif
        </a>
        <a href="{{ route('admin.profile.edit') }}" class="btn btn-ghost">Profil</a>
      </div>
    </div>

    <header class="admin-top">
      <div class="admin-top-left">
        <h1 class="admin-title">@yield('page_title','')</h1>

        @hasSection('page_subtitle')
          <p class="admin-subtitle">@yield('page_subtitle')</p>
        @endif
      </div>

      @hasSection('top_actions')
        <div class="admin-page-actions">
          @yield('top_actions')
        </div>
      @endif
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

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', async function () {
          const targetId = button.getAttribute('data-copy-target');
          const feedbackId = button.getAttribute('data-copy-feedback');
          const input = document.getElementById(targetId);
          const feedback = feedbackId ? document.getElementById(feedbackId) : null;

          if (!input) {
            return;
          }

          try {
            await navigator.clipboard.writeText(input.value);

            if (feedback) {
              feedback.classList.add('is-visible');
              window.setTimeout(function () {
                feedback.classList.remove('is-visible');
              }, 2200);
            }
          } catch (error) {
            input.focus();
            input.select();
            document.execCommand('copy');

            if (feedback) {
              feedback.classList.add('is-visible');
              window.setTimeout(function () {
                feedback.classList.remove('is-visible');
              }, 2200);
            }
          }
        });
      });

      const searchWrapper = document.querySelector('.admin-global-search');
      const searchInput = document.getElementById('admin-global-search-input');
      const suggestionBox = document.getElementById('admin-search-suggestions');

      if (searchWrapper && searchInput && suggestionBox) {
        let timer = null;

        const clearSuggestions = function () {
          suggestionBox.innerHTML = '';
          suggestionBox.classList.remove('is-visible');
        };

        const escapeHtml = function (value) {
          return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({
              '&': '&amp;',
              '<': '&lt;',
              '>': '&gt;',
              '"': '&quot;',
              "'": '&#039;'
            })[char];
          });
        };

        searchInput.addEventListener('input', function () {
          window.clearTimeout(timer);
          const value = searchInput.value.trim();

          if (value === '') {
            clearSuggestions();
            return;
          }

          timer = window.setTimeout(async function () {
            try {
              const response = await fetch(searchWrapper.dataset.searchEndpoint + '?q=' + encodeURIComponent(value), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              });
              const items = await response.json();
              const limitedItems = Array.isArray(items) ? items.slice(0, 5) : [];

              suggestionBox.innerHTML = limitedItems.length
                ? '<div class="admin-search-suggestions-head"><span>Suggestions</span><button type="button" class="admin-search-close" aria-label="Fermer">x</button></div>' +
                  limitedItems.map(function (item) {
                    return '<a class="admin-search-suggestion" href="' + escapeHtml(item.url) + '">' +
                      '<span class="admin-search-group">' + escapeHtml(item.group) + '</span>' +
                      '<strong>' + escapeHtml(item.label) + '</strong>' +
                    '</a>';
                  }).join('')
                : '';

              suggestionBox.classList.toggle('is-visible', limitedItems.length > 0);
            } catch (error) {
              clearSuggestions();
            }
          }, 180);
        });

        suggestionBox.addEventListener('click', function (event) {
          if (event.target.closest('.admin-search-close')) {
            event.preventDefault();
            clearSuggestions();
            searchInput.blur();
          }
        });

        document.addEventListener('click', function (event) {
          if (!searchWrapper.contains(event.target)) {
            clearSuggestions();
          }
        });
      }
    });
  </script>

  @include('partials.rhs-select-enhancer')
  @include('partials.rhs-feedback')
  <script src="{{ asset('js/rhs-ui.js') }}" defer></script>
  <script src="{{ asset('js/admin-sidebar.js') }}?v={{ filemtime(public_path('js/admin-sidebar.js')) }}" defer></script>

  @stack('scripts')
</body>
</html>
