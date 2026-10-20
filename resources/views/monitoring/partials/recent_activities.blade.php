<section class="card" id="monitoring-recent-activities-card">
    <div class="card-title">
        <div class="title-left">
            <span class="iconbox">◉</span>
            <div>
                <h2>সাম্প্রতিক কার্যক্রম</h2>
                <div class="sub">শেষ আপডেটগুলো (অ্যাক্টিভিটি লগ)</div>
            </div>
        </div>
        <a href="{{ Route::has('admin.audit') ? route('admin.audit') : route('admin.dashboard') }}" class="btn soft" style="font-size:12px;padding:6px 12px;">
            সব দেখুন →
        </a>
    </div>

    <div class="timeline">
        @forelse($recentActivities as $activity)
            <div class="event">
                <p>
                    <b>{{ $activity->description }}</b><br>
                    <small style="color:var(--muted)">{{ $activity->user->name ?? 'সিস্টেম' }} • {{ $activity->created_at->diffForHumans() }}</small>
                </p>
            </div>
        @empty
            <div class="empty">
                কোনো সাম্প্রতিক কার্যক্রম পাওয়া যায়নি।
            </div>
        @endforelse
    </div>
</section>
