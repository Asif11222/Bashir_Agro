<section class="card" id="monitoring-recent-activities-card">
    <div class="card-title">
        <div class="title-left">
            <span class="iconbox">◉</span>
            <div>
                <h2>সাম্প্রতিক অপারেশনাল কার্যক্রম</h2>
                <div class="sub">রিয়েল-টাইম সিস্টেম অ্যাক্টিভিটি স্ট্রিম</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="pill" style="font-size:11px;color:var(--g700);background:#e8f5e9;">
                <span class="dot" style="background:#2e7d32"></span>সর্বশেষ ১০টি রেকর্ড
            </span>
        </div>
    </div>

    <div class="timeline recent-activities-timeline">
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
