@php
    $clientUser = auth()->user();
@endphp

<a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'is-active' : '' }}">
    <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
    <span class="portal-label">Accueil</span>
</a>

@if($clientUser->hasPermission('recruitment_requests'))
    <a href="{{ route('client.recruitment-requests.index') }}" class="{{ request()->routeIs('client.recruitment-requests.index') || request()->routeIs('client.recruitment-requests.show') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12v18H6V3Z" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="portal-label">Historique demandes</span>
    </a>
    <a href="{{ route('client.recruitment-requests.create') }}" class="{{ request()->routeIs('client.recruitment-requests.create') ? 'is-active' : '' }}">
        <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/><path d="M5 4h14v16H5V4Z" stroke="currentColor" stroke-width="1.6" opacity=".75"/></svg></span>
        <span class="portal-label">Nouvelle demande</span>
    </a>
@endif

<a href="{{ route('client.profile.edit') }}" class="{{ request()->routeIs('client.profile.*') ? 'is-active' : '' }}">
    <span class="portal-ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
    <span class="portal-label">Mon profil</span>
</a>
