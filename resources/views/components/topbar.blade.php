@php
    $user = auth()->user();
    $avatarLetter = $user ? mb_substr($user->name, 0, 1) : 'আ';
    $roleName = ($user && $user->role === 'owner') ? 'মালিক' : 'অ্যাডমিন';
    $unreadNotifications = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="topbar">
    <button class="mobile" id="menu" type="button" aria-label="মেনু খুলুন">☰</button>
    <div class="crumb" id="crumb" title="{{ $title ?? 'ড্যাশবোর্ড' }}">{{ $title ?? 'ড্যাশবোর্ড' }}</div>
    <div class="spacer"></div>
    <div class="search-wrap" id="global-search-wrap">
        <label class="search" for="global-search-input">
            <span class="search-icon" aria-hidden="true">⌕</span>
            <input id="global-search-input" type="text" placeholder="কৃষক, লোকেশন বা কাজ খুঁজুন..." autocomplete="off" spellcheck="false" data-search-url="{{ route('admin.global-search') }}">
            <kbd class="search-kbd" title="কীবোর্ড শর্টকাট: Ctrl + K">Ctrl K</kbd>
            <button type="button" class="search-clear-btn" id="global-search-clear" aria-label="সার্চ মুছুন" style="display:none;">✕</button>
        </label>
        <div class="search-dropdown" id="global-search-dropdown" style="display:none;" role="listbox"></div>
    </div>
    <a href="{{ Route::has('admin.notifications') ? route('admin.notifications') : '#' }}" class="top-icon" aria-label="নোটিফিকেশন">
        ♧
        @if($unreadNotifications > 0)
            <i>{{ $unreadNotifications }}</i>
        @endif
    </a>
    <div class="user">
        <div class="avatar" title="{{ $user->name ?? 'ব্যবহারকারী' }}">{{ $avatarLetter }}</div>
        <div class="user-meta">
            <b>{{ $user->name ?? 'আব্দুল রহমান' }}</b>
            <small>{{ $roleName }}</small>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" class="topbar-logout-form">
            @csrf
            <button type="submit" class="btn soft topbar-logout-btn" title="লগআউট">
                <span class="logout-text">লগআউট</span>
                <span class="logout-icon" aria-hidden="true">↪</span>
            </button>
        </form>
    </div>
</header>
