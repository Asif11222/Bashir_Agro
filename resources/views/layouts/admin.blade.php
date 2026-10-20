<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'আমার ফার্ম — অ্যাডমিন প্যানেল')</title>
    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌱</text></svg>">
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>
<div class="app">
    @include('components.sidebar', ['active' => $activeTab ?? 'dashboard'])

    <main class="main">
        @include('components.topbar', ['title' => $title ?? 'ড্যাশবোর্ড'])

        <div id="app">
            @include('components.flash-message')
            @yield('content')
        </div>
    </main>
</div>

<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<div class="modal-wrap" id="modal">
    @yield('modal')
</div>

<div class="toast" id="toast"></div>

<script src="{{ asset('js/admin.js') }}"></script>
@stack('scripts')
@auth
    @include('components.idle-session-modal')
@endauth
</body>
</html>
