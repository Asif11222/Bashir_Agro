@extends('layouts.admin')

@section('title', 'ড্যাশবোর্ড — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>ড্যাশবোর্ড</h1>
            <p>আজকের ফার্ম অপারেশন, কৃষক ও কাজের অবস্থা এক নজরে</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.farmers.create') }}" class="btn soft">+ নতুন কৃষক</a>
            <a href="{{ route('admin.tasks.create') }}" class="btn primary">+ নতুন কাজ</a>
        </div>
    </div>

    <div class="grid cols-4">
        <div class="card kpi">
            <span class="iconbox">♟</span>
            <div>
                <b>{{ $activeFarmers }}</b>
                <small>সক্রিয় কৃষক</small>
            </div>
            <span class="trend">+{{ $newFarmersThisMonth }} এই মাসে</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">▣</span>
            <div>
                <b>{{ $todaysTasksCount }}</b>
                <small>আজকের কাজ</small>
            </div>
            <span class="trend">{{ $todaysCompletedCount }} সম্পন্ন</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">⌖</span>
            <div>
                <b>{{ $activeLocations }}</b>
                <small>সক্রিয় লোকেশন</small>
            </div>
            <span class="trend">{{ $locationActivePercent }}% সক্রিয়</span>
        </div>

        <div class="card kpi">
            <span class="iconbox">⚠</span>
            <div>
                <b>{{ $attentionNeeded }}</b>
                <small>মনোযোগ প্রয়োজন</small>
            </div>
            @if($todaysLateCount > 0)
                <span class="trend" style="color:var(--red);background:#ffe8e6">{{ $todaysLateCount }}টি দেরি</span>
            @else
                <span class="trend">সব ঠিক আছে</span>
            @endif
        </div>
    </div>

    <div class="grid dashboard-grid section-gap">
        <div class="grid">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">▣</span>
                        <div>
                            <h2>আজকের কাজ</h2>
                            <div class="sub">কাজের অবস্থা ও সময়সীমা (PostgreSQL থেকে রিয়েল-টাইম)</div>
                        </div>
                    </div>
                </div>

                <div class="tabs">
                    <a href="{{ route('admin.dashboard') }}" class="tab {{ !$statusFilter ? 'active' : '' }}">
                        সব ({{ $todaysTasksCount }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['status' => 'in_progress']) }}" class="tab {{ $statusFilter === 'in_progress' ? 'active' : '' }}">
                        চলমান ({{ $todaysInProgressCount }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['status' => 'late']) }}" class="tab {{ $statusFilter === 'late' ? 'active' : '' }}">
                        দেরি ({{ $todaysLateCount }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['status' => 'completed']) }}" class="tab {{ $statusFilter === 'completed' ? 'active' : '' }}">
                        সম্পন্ন ({{ $todaysCompletedCount }})
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
                                <th>অবস্থা</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($todaysAssignments as $assignment)
                                <tr>
                                    <td>
                                        <b>{{ $assignment->farmer->full_name ?? 'অজ্ঞাত কৃষক' }}</b>
                                        @if($assignment->farmer?->phone)
                                            <br><small style="color:var(--muted)">{{ $assignment->farmer->phone }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <b>{{ $assignment->taskTemplate->name ?? 'অজ্ঞাত কাজ' }}</b>
                                        <br><small style="color:var(--muted)">{{ $assignment->taskTemplate->task_code ?? '' }}</small>
                                    </td>
                                    <td>{{ $assignment->location->name ?? 'অজ্ঞাত লোকেশন' }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($assignment->start_time)->format('H:i') }}
                                        @if($assignment->end_time)
                                            - {{ \Carbon\Carbon::parse($assignment->end_time)->format('H:i') }}
                                        @endif
                                    </td>
                                    <td>
                                        @include('components.status-pill', [
                                            'status' => $assignment->status
                                        ])
                                        @include('components.submission-health', ['assignment' => $assignment])
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn">
                                            দেখুন
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="empty">
                                        আজকের কোনো কাজ খুঁজে পাওয়া যায়নি।
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
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
        </div>

        <div class="grid">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⚡</span>
                        <div>
                            <h2>দ্রুত কাজ</h2>
                            <div class="sub">সবচেয়ে বেশি ব্যবহৃত অ্যাকশন</div>
                        </div>
                    </div>
                </div>
                <div class="quick">
                    <a href="{{ route('admin.farmers.create') }}">
                        ♟<b>কৃষক যোগ করুন</b>
                        <small>নতুন কৃষক অ্যাকাউন্ট</small>
                    </a>
                    <a href="{{ route('admin.tasks.create') }}">
                        ▣<b>কাজ তৈরি করুন</b>
                        <small>কাজের টেমপ্লেট ও নিয়ম</small>
                    </a>
                    <a href="{{ route('admin.locations.create') }}">
                        ⌖<b>লোকেশন যোগ করুন</b>
                        <small>জিও-ফেন্সসহ</small>
                    </a>
                    <a href="{{ route('admin.assignments.create') }}">
                        ✓<b>কাজ বরাদ্দ করুন</b>
                        <small>এক কৃষককে এক কাজ</small>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
