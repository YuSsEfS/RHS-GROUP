@php($name = $name ?? 'briefcase')
@switch($name)
    @case('phone')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.1 5.18 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.72c.12.9.32 1.78.6 2.63a2 2 0 0 1-.45 2.11L9 10.7a16 16 0 0 0 4.3 4.3l1.24-1.24a2 2 0 0 1 2.11-.45c.85.28 1.73.48 2.63.6A2 2 0 0 1 22 16.9Z"/></svg>
        @break
    @case('clock')
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @break
    @case('pin')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></svg>
        @break
    @case('car')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 17H4a2 2 0 0 1-2-2v-3l2.2-5A3 3 0 0 1 7 5h10a3 3 0 0 1 2.8 2l2.2 5v3a2 2 0 0 1-2 2h-1"/><path d="M7 17h10"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
        @break
    @case('plane')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2 16 20-11-7 17-4-7-9 1Z"/><path d="m22 5-11 10"/></svg>
        @break
    @case('wheat')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v20"/><path d="M8 6c0 3 4 4 4 4s0-4-4-4Z"/><path d="M16 6c0 3-4 4-4 4s0-4 4-4Z"/><path d="M7 12c0 3 5 4 5 4s0-4-5-4Z"/><path d="M17 12c0 3-5 4-5 4s0-4 5-4Z"/></svg>
        @break
    @case('stethoscope')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v3a4 4 0 0 0 8 0v-1"/><circle cx="18" cy="14" r="2"/></svg>
        @break
    @case('calculator')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01"/></svg>
        @break
    @case('folder')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><circle cx="17" cy="14" r="2"/><path d="m19 16 2 2"/></svg>
        @break
    @case('cap')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
        @break
    @case('clipboard')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M9 5a3 3 0 0 1 6 0M8 11h8M8 15h5"/></svg>
        @break
    @case('check')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
        @break
    @default
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 12h18"/></svg>
@endswitch
