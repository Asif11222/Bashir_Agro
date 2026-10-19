<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'আমার ফার্ম — অ্যাডমিন প্যানেল')</title>
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
