<div class="card kpi">
    <span class="iconbox">▣</span>
    <div>
        <b>{{ $dateFilter === 'today' ? $todaysAssignmentsCount : $scopedTotalCount }}</b>
        <small>{{ $dateFilter === 'today' ? 'আজকের মোট কাজ' : 'ফিল্টারকৃত মোট কাজ' }}</small>
    </div>
    <span class="trend">রিয়েল-টাইম</span>
</div>

<div class="card kpi">
    <span class="iconbox">⏳</span>
    <div>
        <b>{{ $inProgressCount }}</b>
        <small>{{ $dateFilter === 'today' ? 'আজকের চলমান কাজ' : 'চলমান অপারেশন' }}</small>
    </div>
    @if($globalInProgressCount > $inProgressCount)
        <span class="trend" style="color:var(--blue);background:#e7f1ff" title="সার্বিক খামারে মোট {{ $globalInProgressCount }}টি কাজ চলমান">সার্বিক {{ $globalInProgressCount }}টি</span>
    @else
        <span class="trend" style="color:var(--blue);background:#e7f1ff">সক্রিয় মাঠ কাজ</span>
    @endif
</div>

<div class="card kpi">
    <span class="iconbox">✓</span>
    <div>
        <b>{{ $completedCount }}</b>
        <small>{{ $dateFilter === 'today' ? 'আজকের সম্পন্ন কাজ' : 'সম্পন্ন কাজ' }}</small>
    </div>
    <span class="trend">যাচাইকৃত</span>
</div>

<div class="card kpi">
    <span class="iconbox">⚠</span>
    <div>
        <b>{{ $lateCount }}</b>
        <small>দেরিতে জমা / মনোযোগ</small>
    </div>
    @if($lateCount > 0)
        <span class="trend" style="color:var(--red);background:#ffe8e6">{{ $lateCount }}টি লাল পতাকা</span>
    @else
        <span class="trend">সব ঠিক আছে</span>
    @endif
</div>
