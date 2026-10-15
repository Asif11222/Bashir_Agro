@php
    $user = auth()->user();
    $avatarLetter = $user ? mb_substr($user->name, 0, 1) : 'আ';
    $roleName = ($user && $user->role === 'owner') ? 'মালিক' : 'অ্যাডমিন';
    $unreadNotifications = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="topbar">
    <button class="mobile" id="menu" type="button" aria-label="মেনু খুলুন">☰</button>
    <div class="crumb" id="crumb">{{ $title ?? 'ড্যাশবোর্ড' }}</div>
    <div class="spacer"></div>
    <select class="date" aria-label="সময় নির্বাচন">
        <option>এই মাস</option>
        <option>গত ৭ দিন</option>
        <option>গত ৩০ দিন</option>
    </select>
    <label class="search">
        ⌕ <input id="search" placeholder="কৃষক, লোকেশন বা কাজ খুঁজুন...">
    </label>
    <a href="{{ Route::has('admin.notifications') ? route('admin.notifications') : '#' }}" class="top-icon" aria-label="নোটিফিকেশন">
        ♧
        @if($unreadNotifications > 0)
            <i>{{ $unreadNotifications }}</i>
        @endif
    </a>
    <div class="user">
        <div class="avatar">{{ $avatarLetter }}</div>
        <div>
            <b>{{ $user->name ?? 'আব্দুল রহমান' }}</b>
            <small>{{ $roleName }}</small>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin-left:8px">
            @csrf
            <button type="submit" class="btn soft" style="padding:4px 8px;font-size:11px" title="লগআউট">লগআউট</button>
        </form>
    </div>
</header>
