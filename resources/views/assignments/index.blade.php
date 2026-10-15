@extends('layouts.admin')

@section('title', 'কাজ বরাদ্দ ব্যবস্থাপনা — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কাজ বরাদ্দ ব্যবস্থাপনা</h1>
            <p>কৃষক, কাজের টেমপ্লেট ও ফার্ম লোকেশন সমন্বয়ে কাজের পূর্ণাঙ্গ সময়সূচি ও স্থিতি পরিচালনা করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.create') }}" class="btn primary">+ নতুন কাজ বরাদ্দ</a>
        </div>
    </div>

    <div class="grid cols-2">
        <div style="grid-column: 1 / -1;">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">✓</span>
                        <div>
                            <h2>বরাদ্দকৃত কার্যতালিকা</h2>
                            <div class="sub">কৃষক পর্যায়ে কাজের সময়সীমা ও অগ্রগতি ট্র্যাক করুন</div>
                        </div>
                    </div>
                </div>

                {{-- Search & Filter Row --}}
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;flex-wrap:wrap;">
                    <form method="GET" action="{{ route('admin.assignments.index') }}" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <input class="input" type="text" name="search" value="{{ $search }}" placeholder="কৃষক, কাজ বা লোকেশনের নাম দিয়ে খুঁজুন..." style="padding:7px 12px;font-size:12px;flex:1;min-width:200px;">
                        
                        <select name="priority" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                            <option value="">সকল অগ্রাধিকার</option>
                            <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>সাধারণ</option>
                            <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ</option>
                            <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>জরুরি</option>
                        </select>

                        <select name="date_filter" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                            <option value="">সকল তারিখ</option>
                            <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজকের কাজ</option>
                            <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>এই সপ্তাহের কাজ</option>
                            <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>নির্দিষ্ট তারিখ</option>
                        </select>

                        @if($dateFilter === 'custom')
                            <input type="date" name="date" class="input" value="{{ $customDate }}" style="padding:6px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                        @endif

                        <button type="submit" class="btn soft" style="padding:7px 14px">ফিল্টার</button>
                        @if($search || $priority || $dateFilter)
                            <a href="{{ route('admin.assignments.index', array_filter(['status' => $status])) }}" class="btn" style="padding:7px 12px" title="ফিল্টার মুছুন">✕ রিস্টার্ট</a>
                        @endif
                    </form>
                </div>

                {{-- Status Tabs --}}
                <div class="tabs">
                    <a href="{{ route('admin.assignments.index', array_filter(['search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ !$status ? 'active' : '' }}">
                        সব ({{ $totalAssignmentsCount }})
                    </a>
                    <a href="{{ route('admin.assignments.index', array_filter(['status' => 'assigned', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'assigned' ? 'active' : '' }}">
                        বরাদ্দকৃত ({{ $assignedCount }})
                    </a>
                    <a href="{{ route('admin.assignments.index', array_filter(['status' => 'in_progress', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'in_progress' ? 'active' : '' }}">
                        চলমান ({{ $inProgressCount }})
                    </a>
                    <a href="{{ route('admin.assignments.index', array_filter(['status' => 'completed', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'completed' ? 'active' : '' }}">
                        সম্পন্ন ({{ $completedCount }})
                    </a>
                    <a href="{{ route('admin.assignments.index', array_filter(['status' => 'cancelled', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'cancelled' ? 'active' : '' }}">
                        বাতিল ({{ $cancelledCount }})
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>কৃষক</th>
                                <th>কাজের নাম</th>
                                <th>লোকেশন</th>
                                <th>তারিখ ও সময়</th>
                                <th>অগ্রাধিকার</th>
                                <th>অবস্থা</th>
                                <th>নিয়োজক</th>
                                <th style="text-align:right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <b>{{ $assignment->farmer->full_name ?? '—' }}</b>
                                        <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }} • {{ $assignment->farmer->phone ?? '' }}</small>
                                    </td>
                                    <td>
                                        <b>{{ $assignment->taskTemplate->name ?? '—' }}</b>
                                        <br><small style="color:var(--g700)">{{ $assignment->taskTemplate->category ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span>{{ $assignment->location->name ?? '—' }}</span>
                                        <br><small style="color:var(--muted)">{{ $assignment->location->location_code ?? '' }}</small>
                                    </td>
                                    <td>
                                        <b>{{ $assignment->scheduled_date ? $assignment->scheduled_date->format('d M, Y') : '—' }}</b>
                                        <br><small style="color:var(--muted)">{{ substr($assignment->start_time, 0, 5) }} {{ $assignment->end_time ? ' - ' . substr($assignment->end_time, 0, 5) : '' }}</small>
                                    </td>
                                    <td>
                                        <span class="pill {{ $assignment->priority_badge_class }}">
                                            <span class="dot"></span>{{ $assignment->priority_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'assigned' => ['label' => 'বরাদ্দকৃত', 'class' => 'blue'],
                                                'in_progress' => ['label' => 'চলমান', 'class' => 'blue'],
                                                'completed' => ['label' => 'সম্পন্ন', 'class' => ''],
                                                'cancelled' => ['label' => 'বাতিল', 'class' => 'red'],
                                                'late' => ['label' => 'দেরি', 'class' => 'red'],
                                            ];
                                            $stInfo = $statusMap[$assignment->status] ?? ['label' => $assignment->status, 'class' => ''];
                                        @endphp
                                        <span class="pill {{ $stInfo['class'] }}">
                                            <span class="dot"></span>{{ $stInfo['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $assignment->assigner->name ?? 'অ্যাডমিন' }}</small>
                                    </td>
                                    <td style="text-align:right">
                                        <div style="display:flex;gap:4px;justify-content:flex-end">
                                            <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn" style="padding:4px 8px;font-size:11px">দেখুন</a>
                                            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn soft" style="padding:4px 8px;font-size:11px">সম্পাদনা</a>
                                            <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই কাজের বরাদ্দটি মুছে ফেলতে চান? এটি সফট ডিলিট হবে।');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn red" style="padding:4px 8px;font-size:11px">মুছুন</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:28px 12px;color:var(--muted)">
                                        কোনো কাজের বরাদ্দ পাওয়া যায়নি।
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

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">✓</span>
                        <div>
                            <h2>সিস্টেম ও বরাদ্দ নীতিমালা</h2>
                            <div class="sub">মাঠ পর্যায়ে কাজের নিয়ন্ত্রণ ও নীতি</div>
                        </div>
                    </div>
                </div>

                <div class="rule">
                    <span>
                        <b>এক কাজ = এক কৃষক</b>
                        <div class="sub">একটি নির্দিষ্ট কাজের সময়সূচি একজন কৃষককেই কার্যকরভাবে বরাদ্দ করা হবে।</div>
                    </span>
                    <span class="pill"><span class="dot"></span>প্রয়োগকৃত</span>
                </div>

                <div class="rule">
                    <span>
                        <b>ডুপ্লিকেট বরাদ্দ প্রতিরোধ</b>
                        <div class="sub">একই কৃষককে একই তারিখে ও একই লোকেশনে পুনরায় একই কাজ বরাদ্দ করতে বাধা দেওয়া হয়।</div>
                    </span>
                    <span class="pill"><span class="dot"></span>সুরক্ষিত</span>
                </div>

                <div class="rule">
                    <span>
                        <b>রিয়েল-টাইম ক্যামেরা ও জিও-ফেন্সিং</b>
                        <div class="sub">মাঠের কাজের প্রমাণ হিসেবে নির্ধারিত বাফার রেডিয়াসের মধ্যে ছবি তুলতে হবে।</div>
                    </span>
                    <span class="pill"><span class="dot"></span>সক্রিয়</span>
                </div>
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⚡</span>
                        <div>
                            <h2>দ্রুত ফিল্টার অ্যাকশন</h2>
                            <div class="sub">কাজের অগ্রগতি নিরীক্ষণের সরাসরি লিংক</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;padding-top:4px;">
                    <a href="{{ route('admin.assignments.index', ['date_filter' => 'today']) }}" class="btn soft" style="justify-content:space-between;display:flex;">
                        <span>📅 আজকের নির্ধারিত সকল কাজ</span>
                        <b>{{ \App\Models\TaskAssignment::whereDate('scheduled_date', today())->count() }}টি</b>
                    </a>
                    <a href="{{ route('admin.assignments.index', ['status' => 'in_progress']) }}" class="btn soft" style="justify-content:space-between;display:flex;">
                        <span>⏳ বর্তমানে চলমান কাজসমূহ</span>
                        <b>{{ $inProgressCount }}টি</b>
                    </a>
                    <a href="{{ route('admin.assignments.index', ['priority' => 'urgent']) }}" class="btn soft" style="justify-content:space-between;display:flex;">
                        <span>🔥 জরুরি অগ্রাধিকারপ্রাপ্ত কাজসমূহ</span>
                        <b>{{ \App\Models\TaskAssignment::where('priority', 'urgent')->count() }}টি</b>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
