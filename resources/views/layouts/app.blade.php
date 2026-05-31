<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800|dm-serif-display:400&display=swap" rel="stylesheet" />

    <!-- ✅ Vite (Tailwind / Breeze) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('images/rhs-group-logo.png') }}" type="image/png">

    <!-- ✅ Website CSS -->
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home-modern.css') }}">

    <!-- ✅ Page-specific CSS should be LAST (so it overrides Tailwind + other files) -->
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/apply.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/visitor-modern.css') }}">

</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">

        {{-- ✅ YOUR NAVBAR --}}
        {{-- resources/views/components/navbar.blade.php --}}
        @include('components.navbar')

        {{-- ✅ Optional Breeze header --}}
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- ✅ Page content --}}
        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        {{-- ✅ Footer --}}
        @includeIf('components.footer')

    </div>

    <!-- ✅ Page-specific JS -->
    @stack('scripts')

    @if(request('builder') == '1' && auth()->check() && auth()->user()->role === 'admin')
        <script src="{{ asset('js/cms-inline.js') }}" defer></script>
    @endif

    <script src="{{ asset('js/navbar.js') }}"></script>
    <script src="{{ asset('js/rhs-ui.js') }}" defer></script>
</body>
</html>
