<a href="{{ route('employee.dashboard') }}" class="{{ request()->routeIs('employee.dashboard') ? 'is-active' : '' }}">Accueil</a>

@if(auth()->user()->hasPermission('employee_reports'))
    <a href="{{ route('employee.reports.index') }}" class="{{ request()->routeIs('employee.reports.*') ? 'is-active' : '' }}">
        Rapports
    </a>
@endif

@if(auth()->user()->hasPermission('employee_leave_requests'))
    <a href="{{ route('employee.leave-requests.index') }}" class="{{ request()->routeIs('employee.leave-requests.*') ? 'is-active' : '' }}">
        Conges
    </a>
@endif

@if(auth()->user()->hasPermission('employee_internal_requests'))
    <a href="{{ route('employee.internal-requests.index') }}" class="{{ request()->routeIs('employee.internal-requests.*') ? 'is-active' : '' }}">
        Demandes RH
    </a>
@endif

@if(auth()->user()->hasPermission('recruitment_requests'))
    <a href="{{ route('employee.client-alerts.index') }}" class="{{ request()->routeIs('employee.client-alerts.*') ? 'is-active' : '' }}">
        Relances clients
    </a>
@endif
