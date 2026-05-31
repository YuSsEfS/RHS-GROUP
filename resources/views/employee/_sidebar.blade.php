@php
    $employeeUser = auth()->user();
    $assignedRequestBadge = data_get($employeeSidebarNotifications ?? [], 'items.assigned_requests', 0);
    $clientAlertBadge = data_get($employeeSidebarNotifications ?? [], 'items.client_alerts', 0);
    $canViewAssignments = $employeeUser->hasAnyPermission(['recruitment_requests', 'recruitment_assignments_view']);
    $canViewClientAlerts = $employeeUser->hasAnyPermission(['recruitment_requests', 'client_alerts_view']);
    $canViewCvBank = $employeeUser->hasAnyPermission(['cv_bank', 'cv_bank_manage']);
    $canViewExternalCvs = $employeeUser->hasAnyPermission(['external_cvs', 'external_cvs_manage']);
    $canViewMeetings = $employeeUser->hasAnyPermission(['meetings_view', 'meetings_manage']);
    $canViewRhResources = $employeeUser->hasAnyPermission(['rh_resources_view', 'rh_resources_manage']);
@endphp

<a href="{{ route('employee.dashboard') }}" class="{{ request()->routeIs('employee.dashboard') ? 'is-active' : '' }}">
    <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
    <span class="portal-label">Accueil</span>
</a>

@if($employeeUser->hasPermission('employee_reports'))
    <a href="{{ route('employee.reports.index') }}" class="{{ request()->routeIs('employee.reports.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l3 3v15H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/><path d="M13 3v4h4M8 12h8M8 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">Rapports</span>
    </a>
@endif

@if($employeeUser->hasPermission('employee_leave_requests'))
    <a href="{{ route('employee.leave-requests.index') }}" class="{{ request()->routeIs('employee.leave-requests.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4v3M17 4v3M4 9h16M6 6h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m9 14 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="portal-label">Conges</span>
    </a>
@endif

@if($employeeUser->hasPermission('employee_internal_requests'))
    <a href="{{ route('employee.internal-requests.index') }}" class="{{ request()->routeIs('employee.internal-requests.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">Demandes RH</span>
    </a>
@endif

@if($canViewAssignments)
    <a href="{{ route('employee.recruitment-requests.index') }}" class="{{ request()->routeIs('employee.recruitment-requests.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11M4 6h1M4 12h1M4 18h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m3.5 5.5 1 1 2-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="portal-label">Demandes assignees</span>
        @if($assignedRequestBadge > 0)
            <span class="portal-nav-badge">{{ $assignedRequestBadge }}</span>
        @endif
    </a>
@endif

@if($canViewClientAlerts)
    <a href="{{ route('employee.client-alerts.index') }}" class="{{ request()->routeIs('employee.client-alerts.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v7l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M20 4v5h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="portal-label">Relances clients</span>
        @if($clientAlertBadge > 0)
            <span class="portal-nav-badge">{{ $clientAlertBadge }}</span>
        @endif
    </a>
@endif

@if($canViewCvBank)
    <a href="{{ route('employee.cvs.index') }}" class="{{ request()->routeIs('employee.cvs.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.8"/><path d="M14 3v4h4M8 12h8M8 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">CV Bank</span>
    </a>
@endif

@if($canViewExternalCvs)
    <a href="{{ route('employee.external-cvs.index') }}" class="{{ request()->routeIs('employee.external-cvs.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6c0-1.66 3.58-3 8-3s8 1.34 8 3-3.58 3-8 3-8-1.34-8-3Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6" stroke="currentColor" stroke-width="1.8"/></svg></span>
        <span class="portal-label">Base externe</span>
    </a>
@endif

@if($canViewMeetings)
    <a href="{{ route('employee.meetings.index') }}" class="{{ request()->routeIs('employee.meetings.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4v3M17 4v3M5 8h14M6 6h12a2 2 0 0 1 2 2v11H4V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 13h3M13 13h3M8 16h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">Reunions</span>
        @if(data_get($employeeSidebarNotifications ?? [], 'items.meetings', 0) > 0)
            <span class="portal-nav-badge">{{ data_get($employeeSidebarNotifications ?? [], 'items.meetings', 0) }}</span>
        @endif
    </a>
@endif

@if($canViewRhResources)
    <a href="{{ route('employee.rh-resources.index') }}" class="{{ request()->routeIs('employee.rh-resources.*') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v16H5V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">Ressources RH</span>
    </a>
@endif

<a href="{{ route('employee.messages.index', ['empty' => 1]) }}" class="{{ request()->routeIs('employee.messages.*') ? 'is-active' : '' }}">
    <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
    <span class="portal-label">Messages</span>
    <span
        class="portal-nav-badge"
        data-conversation-notification-badge
        style="{{ data_get($employeeSidebarNotifications ?? [], 'items.conversations', 0) > 0 ? '' : 'display:none;' }}"
        {{ data_get($employeeSidebarNotifications ?? [], 'items.conversations', 0) > 0 ? '' : 'hidden' }}
    >{{ data_get($employeeSidebarNotifications ?? [], 'items.conversations', 0) }}</span>
</a>

<a href="{{ route('employee.profile.edit') }}" class="{{ request()->routeIs('employee.profile.*') ? 'is-active' : '' }}">
    <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
    <span class="portal-label">Mon profil</span>
</a>
