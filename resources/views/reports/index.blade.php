@extends('layouts.admin')

@section('title', 'রিপোর্ট ও অ্যানালিটিক্স — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>রিপোর্ট ও অ্যানালিটিক্স</h1>
            <p>কাজের সম্পাদন হার, কৃষক পারফরম্যান্স, লোকেশন ভিত্তিক কার্যক্রম ও সিস্টেম অ্যাক্টিভিটি সারসংক্ষেপ</p>
        </div>
        <div class="actions">
            <button type="button" class="btn soft" onclick="window.print()">🖨 রিপোর্ট প্রিন্ট</button>
            <a href="{{ route('admin.monitoring') }}" class="btn primary">◉ লাইভ মনিটরিং</a>
        </div>
    </div>

    {{-- Top Analytics KPIs --}}
    <div class="grid cols-4">
        <div class="card kpi">
            <span class="iconbox">✓</span>
            <div>
                <b>{{ $kpis['completion_rate'] }}%</b>
                <small>সময়মতো সম্পন্ন হার</small>
            </div>
            <span class="trend">{{ $kpis['completed_assignments'] }}টি সম্পন্ন</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">▣</span>
            <div>
                <b>{{ $kpis['total_assignments'] }}</b>
                <small>মোট নির্ধারিত কাজ</small>
            </div>
            <span class="trend">অপারেশন</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">⏳</span>
            <div>
                <b>{{ $kpis['in_progress_assignments'] }}</b>
                <small>চলমান কাজ</small>
            </div>
            <span class="trend" style="color:var(--blue);background:#e7f1ff">মাঠ পর্যায়ে</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">⚠</span>
            <div>
                <b>{{ $kpis['late_assignments'] }}</b>
                <small>দেরিতে জমা / ফ্ল্যাগ</small>
            </div>
            @if($kpis['late_assignments'] > 0)
                <span class="trend" style="color:var(--red);background:#ffe8e6">{{ $kpis['late_assignments'] }}টি দেরি</span>
            @else
                <span class="trend">সব ঠিক আছে</span>
            @endif
        </div>
    </div>

    {{-- Filter Card --}}
    <section class="card section-gap">
        <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div style="display:flex;align-items:center;gap:6px;">
                <span class="label" style="margin:0;">সময়কাল:</span>
                <select name="date_filter" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>চলতি মাস</option>
                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>এই সপ্তাহ</option>
                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজ</option>
                    <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>গতকাল</option>
                    <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>সকল সময় (All Time)</option>
                    <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>কাস্টম রেঞ্জ</option>
                </select>
            </div>

            @if($dateFilter === 'custom')
                <div style="display:flex;align-items:center;gap:6px;">
                    <input type="date" name="start_date" class="input" value="{{ $startDate }}" style="padding:6px 10px;font-size:12px;width:auto;">
                    <span>থেকে</span>
                    <input type="date" name="end_date" class="input" value="{{ $endDate }}" style="padding:6px 10px;font-size:12px;width:auto;">
                </div>
            @endif

            @if($tab === 'status')
                <select name="priority" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                    <option value="">সকল অগ্রাধিকার</option>
                    <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>সাধারণ</option>
                    <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ</option>
                    <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>জরুরি</option>
                </select>

                <select name="location_id" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                    <option value="">সকল লোকেশন</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $locationId === $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($tab !== 'status')
                <input class="input" type="text" name="search" value="{{ $search }}" placeholder="{{ match($tab) {
                    'farmers' => 'কৃষকের নাম, আইডি বা কার্যক্রম দিয়ে খুঁজুন...',
                    'locations' => 'লোকেশনের নাম বা ধরন দিয়ে খুঁজুন...',
                    'activity' => 'অ্যাকশন বা বিবরণ দিয়ে খুঁজুন...',
                    default => 'কাজের নাম, কোড বা শ্রেণি দিয়ে খুঁজুন...',
                } }}" style="padding:7px 12px;font-size:12px;flex:1;min-width:180px;">
            @endif

            <button type="submit" class="btn soft" style="padding:7px 14px">ফিল্টার প্রয়োগ</button>

            @if($search || $priority || $locationId || $dateFilter !== 'this_month')
                <a href="{{ route('admin.reports', ['tab' => $tab]) }}" class="btn" style="padding:7px 12px" title="রিসেট">✕ রিসেট</a>
            @endif

            <div style="margin-left:auto;font-size:12px;color:var(--muted);font-weight:600;">
                {{ $dateRangeLabel }}
            </div>
        </form>
    </section>

    {{-- Report Navigation Tabs --}}
    <div class="tabs section-gap">
        <a href="{{ route('admin.reports', array_filter(['tab' => 'tasks', 'date_filter' => $dateFilter, 'start_date' => $startDate, 'end_date' => $endDate])) }}" class="tab {{ $tab === 'tasks' ? 'active' : '' }}">
            ▣ কাজের পারফরম্যান্স রিপোর্ট
        </a>
        <a href="{{ route('admin.reports', array_filter(['tab' => 'farmers', 'date_filter' => $dateFilter, 'start_date' => $startDate, 'end_date' => $endDate])) }}" class="tab {{ $tab === 'farmers' ? 'active' : '' }}">
            ♟ কৃষকের কার্যক্রম রিপোর্ট
        </a>
        <a href="{{ route('admin.reports', array_filter(['tab' => 'locations', 'date_filter' => $dateFilter, 'start_date' => $startDate, 'end_date' => $endDate])) }}" class="tab {{ $tab === 'locations' ? 'active' : '' }}">
            ⌖ লোকেশন পারফরম্যান্স রিপোর্ট
        </a>
        <a href="{{ route('admin.reports', array_filter(['tab' => 'status', 'date_filter' => $dateFilter, 'start_date' => $startDate, 'end_date' => $endDate])) }}" class="tab {{ $tab === 'status' ? 'active' : '' }}">
            📊 বরাদ্দ স্থিতি ও বিভাজন
        </a>
        <a href="{{ route('admin.reports', array_filter(['tab' => 'activity', 'date_filter' => $dateFilter, 'start_date' => $startDate, 'end_date' => $endDate])) }}" class="tab {{ $tab === 'activity' ? 'active' : '' }}">
            ◉ সিস্টেম অ্যাক্টিভিটি রিপোর্ট
        </a>
    </div>

    {{-- TAB 1: Task Performance Report --}}
    @if($tab === 'tasks')
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">▣</span>
                    <div>
                        <h2>কাজের পারফরম্যান্স রিপোর্ট</h2>
                        <div class="sub">প্রতিটি কাজের টেমপ্লেটের অধীনে মোট বরাদ্দ, সম্পাদন সংখ্যা ও সফলতার হার</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>কাজের নাম ও কোড</th>
                            <th>শ্রেণি</th>
                            <th>মোট বরাদ্দ</th>
                            <th>সম্পন্ন</th>
                            <th>চলমান</th>
                            <th>বাতিল</th>
                            <th style="min-width:160px;">সম্পাদন হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taskReports as $task)
                            <tr>
                                <td>
                                    <b>{{ $task->name }}</b>
                                    <br><small style="color:var(--muted)">{{ $task->task_code }}</small>
                                </td>
                                <td>{{ $task->category }}</td>
                                <td><b>{{ $task->total_assignments }}টি</b></td>
                                <td><span style="color:var(--g700);font-weight:700;">{{ $task->completed_assignments }}টি</span></td>
                                <td><span style="color:var(--blue);">{{ $task->in_progress_assignments }}টি</span></td>
                                <td><span style="color:var(--red);">{{ $task->cancelled_assignments }}টি</span></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress" style="flex:1;">
                                            <span style="width: {{ $task->completion_percentage }}%"></span>
                                        </div>
                                        <b style="font-size:11px;min-width:36px;">{{ $task->completion_percentage }}%</b>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">
                                    নির্বাচিত সময়কালে কোনো কাজের পারফরম্যান্স ডাটা পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($taskReports && $taskReports->hasPages())
                <div style="margin-top:16px;">
                    {{ $taskReports->links() }}
                </div>
            @endif
        </section>
    @endif

    {{-- TAB 2: Farmer Activity Report --}}
    @if($tab === 'farmers')
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">♟</span>
                    <div>
                        <h2>কৃষকের কার্যক্রম রিপোর্ট</h2>
                        <div class="sub">কৃষকভিত্তিক মোট অ্যাসাইনমেন্ট, সম্পন্ন কাজ ও নির্ভরযোগ্যতা হার</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>কৃষক</th>
                            <th>আইডি</th>
                            <th>প্রধান কার্যক্রম</th>
                            <th>মোট দায়িত্ব</th>
                            <th>সম্পন্ন কাজ</th>
                            <th>অপেক্ষমাণ / চলমান</th>
                            <th style="min-width:160px;">সফলতা হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($farmerReports as $farmer)
                            <tr>
                                <td>
                                    <b>{{ $farmer->full_name }}</b>
                                    <br><small style="color:var(--muted)">{{ $farmer->phone }}</small>
                                </td>
                                <td>{{ $farmer->farmer_code }}</td>
                                <td>{{ $farmer->primary_activity }}</td>
                                <td><b>{{ $farmer->total_assigned_tasks }}টি</b></td>
                                <td><span style="color:var(--g700);font-weight:700;">{{ $farmer->completed_tasks }}টি</span></td>
                                <td><span style="color:var(--blue);">{{ $farmer->pending_tasks }}টি</span></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress" style="flex:1;">
                                            <span style="width: {{ $farmer->completion_rate }}%"></span>
                                        </div>
                                        <b style="font-size:11px;min-width:36px;">{{ $farmer->completion_rate }}%</b>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">
                                    নির্বাচিত সময়কালে কোনো কৃষকের কার্যক্রম রেকর্ড পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($farmerReports && $farmerReports->hasPages())
                <div style="margin-top:16px;">
                    {{ $farmerReports->links() }}
                </div>
            @endif
        </section>
    @endif

    {{-- TAB 3: Location Performance Report --}}
    @if($tab === 'locations')
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">⌖</span>
                    <div>
                        <h2>লোকেশন পারফরম্যান্স রিপোর্ট</h2>
                        <div class="sub">ফার্ম প্লট/পুকুর ভিত্তিক সম্পন্ন কার্যসূচি ও সক্রিয় কৃষক সংযুক্তি</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>লোকেশন</th>
                            <th>ধরন</th>
                            <th>নির্ধারিত কাজ</th>
                            <th>সম্পন্ন কাজ</th>
                            <th>সংযুক্ত কৃষক</th>
                            <th style="min-width:160px;">কার্যকারিতা হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locationReports as $location)
                            <tr>
                                <td>
                                    <b>{{ $location->name }}</b>
                                    <br><small style="color:var(--muted)">{{ $location->location_code }}</small>
                                </td>
                                <td>{{ $location->type }}</td>
                                <td><b>{{ $location->assigned_tasks }}টি</b></td>
                                <td><span style="color:var(--g700);font-weight:700;">{{ $location->completed_tasks }}টি</span></td>
                                <td><span>{{ $location->active_farmers }} জন</span></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress" style="flex:1;">
                                            <span style="width: {{ $location->completion_rate }}%"></span>
                                        </div>
                                        <b style="font-size:11px;min-width:36px;">{{ $location->completion_rate }}%</b>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">
                                    নির্বাচিত সময়কালে কোনো লোকেশনের পারফরম্যান্স ডাটা পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($locationReports && $locationReports->hasPages())
                <div style="margin-top:16px;">
                    {{ $locationReports->links() }}
                </div>
            @endif
        </section>
    @endif

    {{-- TAB 4: Assignment Status Distribution Report --}}
    @if($tab === 'status')
        <div class="grid cols-2">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">📊</span>
                        <div>
                            <h2>কাজের অবস্থা বিভাজন</h2>
                            <div class="sub">মোট {{ $statusReport['total'] }}টি অ্যাসাইনমেন্টের স্ট্যাটাস সারসংক্ষেপ</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;padding:8px 0;">
                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;">
                            <span><b>সম্পন্ন (Completed)</b></span>
                            <b>{{ $statusReport['completed'] }}টি ({{ $statusReport['completed_pct'] }}%)</b>
                        </div>
                        <div class="progress"><span style="width: {{ $statusReport['completed_pct'] }}%;background:#16a15a;"></span></div>
                    </div>

                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;">
                            <span><b>চলমান (In Progress)</b></span>
                            <b>{{ $statusReport['in_progress'] }}টি ({{ $statusReport['in_progress_pct'] }}%)</b>
                        </div>
                        <div class="progress"><span style="width: {{ $statusReport['in_progress_pct'] }}%;background:#236cb1;"></span></div>
                    </div>

                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;">
                            <span><b>বরাদ্দকৃত (Assigned)</b></span>
                            <b>{{ $statusReport['assigned'] }}টি ({{ $statusReport['assigned_pct'] }}%)</b>
                        </div>
                        <div class="progress"><span style="width: {{ $statusReport['assigned_pct'] }}%;background:#8f9f97;"></span></div>
                    </div>

                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;">
                            <span><b>বাতিল (Cancelled)</b></span>
                            <b>{{ $statusReport['cancelled'] }}টি ({{ $statusReport['cancelled_pct'] }}%)</b>
                        </div>
                        <div class="progress"><span style="width: {{ $statusReport['cancelled_pct'] }}%;background:#c63c37;"></span></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⚡</span>
                        <div>
                            <h2>অপারেশনাল মেট্রিক্স হাইলাইটস</h2>
                            <div class="sub">সিস্টেম রিলায়াবিলিটি ও ঝুঁকি নির্দেশক</div>
                        </div>
                    </div>
                </div>

                <div class="rule">
                    <span>
                        <b>মোট কার্যকর সম্পন্ন</b>
                        <div class="sub">সফলভাবে যাচাই ও গৃহীত কাজ</div>
                    </span>
                    <span class="pill"><span class="dot"></span>{{ $statusReport['completed'] }}টি</span>
                </div>

                <div class="rule">
                    <span>
                        <b>দেরিতে জমা (Late Submissions)</b>
                        <div class="sub">নির্ধারিত সময়সীমা অতিক্রম করে জমা হওয়া কাজ</div>
                    </span>
                    @if($statusReport['late'] > 0)
                        <span class="pill red"><span class="dot"></span>{{ $statusReport['late'] }}টি লাল পতাকা</span>
                    @else
                        <span class="pill"><span class="dot"></span>০টি দেরি</span>
                    @endif
                </div>

                <div class="rule">
                    <span>
                        <b>সফল কার্যসম্পাদন অনুপাত</b>
                        <div class="sub">মোট কাজের তুলনায় সম্পন্নের শতাংশ</div>
                    </span>
                    <span class="pill blue"><span class="dot"></span>{{ $statusReport['completed_pct'] }}%</span>
                </div>
            </section>
        </div>
    @endif

    {{-- TAB 5: System Activity Report --}}
    @if($tab === 'activity')
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">◉</span>
                    <div>
                        <h2>সিস্টেম অ্যাক্টিভিটি রিপোর্ট</h2>
                        <div class="sub">নিরাপত্তা, তৈরি, পরিবর্তন ও সফট ডিলিট সংক্রান্ত পূর্ণাঙ্গ লগ</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ব্যবহারকারী</th>
                            <th>অ্যাকশন</th>
                            <th>মডিউল / ধরন</th>
                            <th>বিবরণ</th>
                            <th>আইপি ঠিকানা</th>
                            <th>তারিখ ও সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityReports as $log)
                            <tr>
                                <td><b>{{ $log->user->name ?? 'সিস্টেম' }}</b></td>
                                <td>
                                    <span class="pill blue"><span class="dot"></span>{{ $log->action }}</span>
                                </td>
                                <td>
                                    <small>{{ $log->subject_type ? class_basename($log->subject_type) : 'সাধারণ' }}</small>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td><small style="color:var(--muted)">{{ $log->ip_address ?? '—' }}</small></td>
                                <td>
                                    <b>{{ $log->created_at ? $log->created_at->format('d M, Y') : '—' }}</b>
                                    <br><small style="color:var(--muted)">{{ $log->created_at ? $log->created_at->format('h:i A') : '' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">
                                    নির্বাচিত সময়কালে কোনো সিস্টেম অ্যাক্টিভিটি লগ পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activityReports && $activityReports->hasPages())
                <div style="margin-top:16px;">
                    {{ $activityReports->links() }}
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
