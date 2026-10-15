@extends('layouts.admin')

@section('title', 'কার্যক্রম পর্যবেক্ষণ — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কার্যক্রম পর্যবেক্ষণ</h1>
            <p>মাঠ পর্যায়ের অপারেশন, সময়সূচি, জিপিএস জিও-ফেন্স ও প্রমাণ ছবি যাচাইকরণ এক নজরে</p>
        </div>
        <div class="actions" style="display:flex;align-items:center;gap:10px;">
            <span class="pill" style="font-size:11px;color:var(--g700);background:#e8f5e9;" title="স্বয়ংক্রিয় লাইভ সিঙ্ক সক্রিয়">
                <span class="dot" style="background:#2e7d32"></span>লাইভ সিঙ্ক
            </span>
            <a href="{{ route('admin.assignments.create') }}" class="btn soft">+ নতুন কাজ বরাদ্দ</a>
            <a href="{{ route('admin.monitoring', array_filter(['date_filter' => $dateFilter, 'status' => $status, 'priority' => $priority, 'search' => $search])) }}" class="btn primary">🔄 রিফ্রেশ মনিটরিং</a>
        </div>
    </div>

    {{-- Live Operation KPI Row --}}
    <div class="grid cols-4">
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
    </div>

    {{-- Main Monitoring Content Grid --}}
    <div class="grid monitoring-grid section-gap">
        <div class="monitoring-main-col">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">◉</span>
                        <div>
                            <h2>সরাসরি কার্যক্রম নিরীক্ষণ</h2>
                            <div class="sub">কৃষক, কাজ ও মাঠের অবস্থান অনুযায়ী ফিল্টার ও ট্র্যাক করুন</div>
                        </div>
                    </div>
                </div>

                {{-- Search & Filter Row --}}
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;flex-wrap:wrap;">
                    <form method="GET" action="{{ route('admin.monitoring') }}" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <input class="input" type="text" name="search" value="{{ $search }}" placeholder="কৃষকের নাম, কাজ বা লোকেশন খুঁজুন..." style="padding:7px 12px;font-size:12px;flex:1;min-width:180px;">

                        <select name="priority" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                            <option value="">সকল অগ্রাধিকার</option>
                            <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>সাধারণ</option>
                            <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ</option>
                            <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>জরুরি</option>
                        </select>

                        <select name="date_filter" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                            <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজকের কাজ</option>
                            <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>এই সপ্তাহের কাজ</option>
                            <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>সকল তারিখ</option>
                            <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>নির্দিষ্ট তারিখ</option>
                        </select>

                        @if($dateFilter === 'custom')
                            <input type="date" name="date" class="input" value="{{ $customDate }}" style="padding:6px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                        @endif

                        <button type="submit" class="btn soft" style="padding:7px 12px">খুঁজুন</button>
                        @if($search || $priority || ($dateFilter && $dateFilter !== 'today'))
                            <a href="{{ route('admin.monitoring', array_filter(['status' => $status])) }}" class="btn" style="padding:7px 10px" title="রিসেট">✕</a>
                        @endif
                    </form>
                </div>

                {{-- Status Tabs --}}
                <div class="tabs">
                    <a href="{{ route('admin.monitoring', array_filter(['search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ !$status ? 'active' : '' }}">
                        সব ({{ $scopedTotalCount }})
                    </a>
                    <a href="{{ route('admin.monitoring', array_filter(['status' => 'in_progress', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'in_progress' ? 'active' : '' }}">
                        চলমান ({{ $inProgressCount }})
                    </a>
                    <a href="{{ route('admin.monitoring', array_filter(['status' => 'assigned', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'assigned' ? 'active' : '' }}">
                        বরাদ্দকৃত ({{ $assignedCount }})
                    </a>
                    <a href="{{ route('admin.monitoring', array_filter(['status' => 'completed', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'completed' ? 'active' : '' }}">
                        সম্পন্ন ({{ $completedCount }})
                    </a>
                    @if($lateCount > 0)
                    <a href="{{ route('admin.monitoring', array_filter(['status' => 'late', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'late' ? 'active' : '' }}">
                        দেরি ({{ $lateCount }})
                    </a>
                    @endif
                    <a href="{{ route('admin.monitoring', array_filter(['status' => 'cancelled', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'cancelled' ? 'active' : '' }}">
                        বাতিল ({{ $cancelledCount }})
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>কৃষক</th>
                                <th>কাজ</th>
                                <th>লোকেশন</th>
                                <th>সময়</th>
                                <th>অগ্রাধিকার</th>
                                <th>অবস্থা</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <b>{{ $assignment->farmer->full_name ?? '—' }}</b>
                                        <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }}</small>
                                    </td>
                                    <td>
                                        <b>{{ $assignment->taskTemplate->name ?? '—' }}</b>
                                        <br><small style="color:var(--g700)">{{ $assignment->taskTemplate->category ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span>{{ $assignment->location->name ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <b>{{ substr($assignment->start_time, 0, 5) }}</b>
                                        @if($assignment->end_time)
                                            - {{ substr($assignment->end_time, 0, 5) }}
                                        @endif
                                        <br><small style="color:var(--muted)">{{ $assignment->scheduled_date ? $assignment->scheduled_date->format('d/m/Y') : '' }}</small>
                                    </td>
                                    <td>
                                        <span class="pill {{ $assignment->priority_badge_class }}">
                                            <span class="dot"></span>{{ $assignment->priority_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $stClass = match($assignment->status) {
                                                'completed' => '',
                                                'in_progress' => 'blue',
                                                'late', 'cancelled' => 'red',
                                                default => 'blue',
                                            };
                                        @endphp
                                        <span class="pill {{ $stClass }}">
                                            <span class="dot"></span>{{ $assignment->status_label }}
                                        </span>
                                    </td>
                                    <td style="text-align:right">
                                        <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn" style="padding:4px 8px;font-size:11px">
                                            দেখুন
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty">
                                        কোনো অপারেশনের তথ্য খুঁজে পাওয়া যায়নি।
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($assignments->hasPages())
                    <div style="margin-top:16px;">
                        {{ $assignments->links() }}
                    </div>
                @endif
            </section>
        </div>

        {{-- Sidebar: Focused Active Operation & Verification Rules --}}
        <div class="monitoring-side-col">
            @if($activeAssignment)
                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">{{ $isOngoing ? '◉' : '⏱' }}</span>
                            <div>
                                <h2>{{ $isOngoing ? 'চলমান অপারেশন ফোকাস' : 'নির্ধারিত অপারেশন ফোকাস' }}</h2>
                                <div class="sub">
                                    {{ $activeAssignment->taskTemplate->name ?? 'কাজের বিবরণ' }} • {{ $activeAssignment->taskTemplate->task_code ?? '' }}
                                    @if(!empty($isGlobalActive))
                                        <span style="color:var(--blue);font-weight:600">(সার্বিক চলমান মাঠ কাজ)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div>
                            <span class="label">নিয়োজিত কৃষক</span>
                            <b>{{ $activeAssignment->farmer->full_name ?? '—' }}</b>
                            <br><small style="color:var(--muted)">{{ $activeAssignment->farmer->farmer_code ?? '' }}</small>
                        </div>
                        <div>
                            <span class="label">ফার্ম লোকেশন</span>
                            <b>{{ $activeAssignment->location->name ?? '—' }}</b>
                            <br><small style="color:var(--muted)">{{ $activeAssignment->location->location_code ?? '' }}</small>
                        </div>
                        <div>
                            <span class="label">নির্ধারিত সময়সূচি</span>
                            <b>{{ substr($activeAssignment->start_time, 0, 5) }} - {{ $activeAssignment->end_time ? substr($activeAssignment->end_time, 0, 5) : '—' }}</b>
                        </div>
                        <div>
                            <span class="label">বর্তমান অবস্থা</span>
                            <span class="pill {{ $activeAssignment->status_badge_class }}">
                                <span class="dot"></span>{{ $activeAssignment->status_label }}
                            </span>
                        </div>
                    </div>

                    <div class="section-gap">
                        <span class="label">কাজের অগ্রগতি নিরীক্ষা</span>
                        <div class="progress">
                            <span style="width: {{ $activeAssignment->status === 'completed' ? '100%' : ($activeAssignment->status === 'in_progress' ? '65%' : ($activeAssignment->status === 'assigned' ? '5%' : '20%')) }}"></span>
                        </div>
                        <small style="color:var(--muted);display:block;margin-top:6px;font-size:11px;">
                            @if($activeAssignment->status === 'in_progress')
                                🟢 মাঠ পর্যায়ে কাজ বর্তমানে চলমান রয়েছে
                            @elseif($activeAssignment->status === 'completed')
                                ✓ কাজ সফলভাবে যাচাই ও সম্পন্ন হয়েছে
                            @elseif($activeAssignment->status === 'assigned')
                                ⏱ কৃষক মোবাইল অ্যাপ থেকে কাজ শুরু করলে স্বয়ংক্রিয়ভাবে 'চলমান' হবে
                            @else
                                স্ট্যাটাস: {{ $activeAssignment->status_label }}
                            @endif
                        </small>
                    </div>

                    <div class="notice warn section-gap">
                        নির্ধারিত সময় শেষ হওয়ার পরও কৃষক কাজ জমা দিতে পারবেন; তখন সাবমিশনটি <b>Late</b> হিসেবে চিহ্নিত থাকবে।
                    </div>
                </section>

                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">✓</span>
                            <div>
                                <h2>যাচাই ও সুরক্ষা মানদণ্ড</h2>
                                <div class="sub">বৈধ সাবমিশন নিশ্চিতকরণের সিস্টেম শর্ত</div>
                            </div>
                        </div>
                    </div>

                    <div class="rule">
                        <span>
                            <b>GPS জিও-ফেন্স বাফার</b>
                            <div class="sub">নির্ধারিত ব্যাসার্ধ: {{ $activeAssignment->location->geofence_radius_meters ?? 20 }} মিটারের মধ্যে উপস্থিতি</div>
                        </span>
                        @if($activeAssignment->taskTemplate->require_geofence ?? true)
                            <span class="pill"><span class="dot"></span>চালু</span>
                        @else
                            <span class="pill red"><span class="dot"></span>বন্ধ</span>
                        @endif
                    </div>

                    <div class="rule">
                        <span>
                            <b>রিয়েল-টাইম ক্যামেরা ছবি</b>
                            <div class="sub">প্রয়োজনীয় ছবি: {{ $activeAssignment->taskTemplate->required_photo_count ?? 1 }}টি (সরাসরি তোলা বাধ্যতামূলক)</div>
                        </span>
                        @if($activeAssignment->taskTemplate->require_realtime_photo ?? true)
                            <span class="pill"><span class="dot"></span>বাধ্যতামূলক</span>
                        @else
                            <span class="pill blue"><span class="dot"></span>ঐচ্ছিক</span>
                        @endif
                    </div>

                    <div class="rule">
                        <span>
                            <b>গ্যালারি থেকে ছবি আপলোড</b>
                            <div class="sub">পূর্বের সংরক্ষিত ফাইল আপলোড সুরক্ষা</div>
                        </span>
                        @if($activeAssignment->taskTemplate->allow_gallery_upload ?? false)
                            <span class="pill blue"><span class="dot"></span>অনুমোদিত</span>
                        @else
                            <span class="pill red"><span class="dot"></span>ব্লক করা</span>
                        @endif
                    </div>

                    <div class="rule">
                        <span>
                            <b>সময়সীমা পর্যবেক্ষণ</b>
                            <div class="sub">আজকের নির্ধারিত সময়সূচি ট্র্যাকিং</div>
                        </span>
                        <span class="pill blue"><span class="dot"></span>সক্রিয়</span>
                    </div>

                    <div class="notice red section-gap">
                        ক্যামেরা খোলা যাবে, কিন্তু GPS/জিও-ফেন্স শর্ত পূরণ না হলে বৈধ কাজ সাবমিট করা যাবে না।
                    </div>
                </section>
            @else
                <section class="card">
                    <div class="empty">
                        বর্তমানে কোনো চলমান অপারেশন নেই।
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">◉</span>
                        <div>
                            <h2>সাম্প্রতিক অপারেশনাল কার্যক্রম</h2>
                            <div class="sub">রিয়েল-টাইম সিস্টেম অ্যাক্টিভিটি স্ট্রিম</div>
                        </div>
                    </div>
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
        </div>
    </div>
</div>

<script>
    // Live synchronization with mobile app field operations (polls every 30 seconds)
    document.addEventListener('DOMContentLoaded', function () {
        let timer = null;
        function scheduleSync() {
            timer = setTimeout(function () {
                const activeEl = document.activeElement;
                const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'SELECT' || activeEl.tagName === 'TEXTAREA');
                if (!isTyping && !document.hidden) {
                    window.location.reload();
                } else {
                    scheduleSync();
                }
            }, 30000);
        }
        scheduleSync();
    });
</script>
@endsection
