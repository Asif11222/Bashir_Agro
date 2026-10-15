@php
    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $active = $active ?? 'dashboard';
@endphp

<aside class="sidebar" id="side">
    <div class="brand">
        <div class="leaf">🌱</div>
        <div>
            <b>আমার ফার্ম</b>
            <small>কৃষি ব্যবস্থাপনা প্ল্যাটফর্ম</small>
        </div>
    </div>
    <nav class="nav" id="nav">
        <a href="{{ route('admin.dashboard') }}" class="{{ $active === 'dashboard' ? 'active' : '' }}">
            <span class="ico">⌂</span>ড্যাশবোর্ড
        </a>
        <a href="{{ Route::has('admin.farmers.index') ? route('admin.farmers.index') : route('admin.dashboard') }}" class="{{ $active === 'farmers' ? 'active' : '' }}">
            <span class="ico">♟</span>কৃষক ব্যবস্থাপনা
        </a>
        <a href="{{ Route::has('admin.tasks.index') ? route('admin.tasks.index') : route('admin.dashboard') }}" class="{{ $active === 'tasks' ? 'active' : '' }}">
            <span class="ico">▣</span>কাজ ব্যবস্থাপনা
        </a>
        <a href="{{ Route::has('admin.assignments.index') ? route('admin.assignments.index') : route('admin.dashboard') }}" class="{{ $active === 'assignments' ? 'active' : '' }}">
            <span class="ico">✓</span>কাজ বরাদ্দ
        </a>
        <a href="{{ Route::has('admin.locations.index') ? route('admin.locations.index') : route('admin.dashboard') }}" class="{{ $active === 'locations' ? 'active' : '' }}">
            <span class="ico">⌖</span>ফার্ম লোকেশন
        </a>
        <a href="{{ Route::has('admin.monitoring') ? route('admin.monitoring') : route('admin.dashboard') }}" class="{{ $active === 'monitor' ? 'active' : '' }}">
            <span class="ico">◉</span>কার্যক্রম পর্যবেক্ষণ
        </a>
        <a href="{{ Route::has('admin.reports') ? route('admin.reports') : route('admin.dashboard') }}" class="{{ $active === 'reports' ? 'active' : '' }}">
            <span class="ico">▥</span>রিপোর্ট
        </a>
        <a href="{{ Route::has('admin.notifications') ? route('admin.notifications') : route('admin.dashboard') }}" class="{{ $active === 'notifications' ? 'active' : '' }}">
            <span class="ico">♧</span>নোটিফিকেশন 
            @if($unreadNotifications > 0)
                <span class="badge">{{ $unreadNotifications }}</span>
            @endif
        </a>
        <a href="{{ Route::has('admin.audit') ? route('admin.audit') : route('admin.dashboard') }}" class="{{ $active === 'audit' ? 'active' : '' }}">
            <span class="ico">📜</span>অডিট লগ
        </a>
        <a href="{{ Route::has('admin.settings') ? route('admin.settings') : route('admin.dashboard') }}" class="{{ $active === 'settings' ? 'active' : '' }}">
            <span class="ico">⚙</span>সেটিংস
        </a>
    </nav>
    <div class="sidebar-foot">
        <b>টেকসই কৃষি<br>সমৃদ্ধ বাংলাদেশ</b><br>
        <span style="opacity:.75">Bashir Agro Admin</span>
    </div>
</aside>
