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
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">
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

                        <select name="date_filter" id="dateFilterSelect" class="input" style="padding:7px 10px;font-size:12px;width:auto;" onchange="handleDateFilterChange(this)">
                            <option value="">সকল তারিখ</option>
                            <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজকের কাজ</option>
                            <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>এই সপ্তাহের কাজ</option>
                            <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>নির্দিষ্ট তারিখ</option>
                        </select>

                        <input type="date" id="customDateInput" name="date" class="input" value="{{ $customDate }}" style="padding:6px 10px;font-size:12px;width:auto;display:{{ $dateFilter === 'custom' ? 'inline-block' : 'none' }};" onchange="this.form.submit()">

                        <button type="submit" class="btn soft" style="padding:7px 14px">ফিল্টার</button>
                        @if($search || $priority || $dateFilter)
                            <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => $status])) }}" class="btn" style="padding:7px 12px" title="ফিল্টার মুছুন">✕ রিস্টার্ট</a>
                        @endif
                    </form>
                    <script>
                        function handleDateFilterChange(select) {
                            var dateInput = document.getElementById('customDateInput');
                            if (select.value === 'custom') {
                                if (dateInput) {
                                    dateInput.style.display = 'inline-block';
                                    dateInput.focus();
                                }
                            } else {
                                if (dateInput) {
                                    dateInput.style.display = 'none';
                                    dateInput.value = '';
                                }
                                select.form.submit();
                            }
                        }
                    </script>
                </div>

                {{-- Status Tabs & View Mode Switcher Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                    <div class="tabs" style="margin:0;">
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ !$status ? 'active' : '' }}">
                            সব ({{ $totalAssignmentsCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => 'assigned', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'assigned' ? 'active' : '' }}">
                            বরাদ্দকৃত ({{ $assignedCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => 'in_progress', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'in_progress' ? 'active' : '' }}">
                            চলমান ({{ $inProgressCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => 'completed', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'completed' ? 'active' : '' }}">
                            সম্পন্ন ({{ $completedCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => 'cancelled', 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}" class="tab {{ $status === 'cancelled' ? 'active' : '' }}">
                            বাতিল ({{ $cancelledCount }})
                        </a>
                    </div>

                    {{-- View Mode Switcher (Grouped vs Flat) --}}
                    <div style="display:inline-flex;background:#edf2ef;padding:3px;border-radius:8px;border:1px solid #d4ded8;">
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => 'grouped', 'status' => $status, 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}"
                           style="padding:5px 12px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s;{{ $viewMode === 'grouped' ? 'background:#08783f;color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#4b6354;' }}"
                           title="প্রতিটি পুনরাবৃত্তিমূলক কাজের সিরিজ ১টি রো/কার্ডে দেখাবে">
                            📁 সিরিজ গ্রুপ ভিউ
                        </a>
                        <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => 'flat', 'status' => $status, 'search' => $search, 'priority' => $priority, 'date_filter' => $dateFilter, 'date' => $customDate])) }}"
                           style="padding:5px 12px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s;{{ $viewMode === 'flat' ? 'background:#08783f;color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#4b6354;' }}"
                           title="প্রতিটি তারিখের কাজ আলাদা রো-তে দেখাবে">
                            📋 সব তারিখের তালিকা
                        </a>
                    </div>
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
                                <th>অবস্থা ও অগ্রগতি</th>
                                <th>নিয়োজক</th>
                                <th style="text-align:right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $statusMap = [
                                    'assigned' => ['label' => 'বরাদ্দকৃত', 'class' => 'blue'],
                                    'pending' => ['label' => 'বরাদ্দকৃত', 'class' => 'blue'],
                                    'in_progress' => ['label' => 'চলমান', 'class' => 'blue'],
                                    'completed' => ['label' => 'সম্পন্ন', 'class' => ''],
                                    'cancelled' => ['label' => 'বাতিল', 'class' => 'red'],
                                    'late' => ['label' => 'দেরি', 'class' => 'red'],
                                ];
                            @endphp

                            @forelse ($assignments as $assignment)
                                @php
                                    $isGroup = ($viewMode === 'grouped' && $assignment->is_recurring && $assignment->recurringChildren && $assignment->recurringChildren->count() > 0);
                                @endphp

                                @if($isGroup)
                                    @php
                                        $children = $assignment->recurringChildren;
                                        $totalChildren = $children->count();
                                        $completedKids = $children->where('status', 'completed')->count();
                                        $inProgressKids = $children->where('status', 'in_progress')->count();
                                        $assignedKids = $children->where('status', 'assigned')->count();
                                        $cancelledKids = $children->where('status', 'cancelled')->count();
                                        $progressPct = $totalChildren > 0 ? round(($completedKids / $totalChildren) * 100) : 0;
                                        $firstDate = $children->first()?->scheduled_date;
                                        $lastDate = $children->last()?->scheduled_date;
                                        $nextChild = $children->first(fn($c) => $c->scheduled_date && $c->scheduled_date->gte(today()) && !in_array($c->status, ['completed', 'cancelled'])) ?? $children->first();
                                    @endphp

                                    {{-- Recurring Routine Master Row --}}
                                    <tr class="recurring-master-row" data-group-id="{{ $assignment->recurring_group_id }}" style="background:#f8fcf9;border-left:4px solid #08783f;">
                                        <td>
                                            <b>{{ $assignment->farmer->full_name ?? '—' }}</b>
                                            <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }} • {{ $assignment->farmer->phone ?? '' }}</small>
                                        </td>
                                        <td>
                                            <b>{{ $assignment->taskTemplate->name ?? '—' }}</b>
                                            <br><small style="color:var(--g700)">{{ $assignment->taskTemplate->category ?? '' }}</small>
                                            <br>
                                            <span class="pill" style="font-size:11px;padding:2px 8px;background:#e8f7ee;color:#08783f;border:1px solid #bbf7d0;display:inline-flex;align-items:center;gap:4px;margin-top:3px;font-weight:600;">
                                                🔄 {{ $assignment->recurrence_type_label }} রুটিন (মোট {{ $totalChildren }}টি সেশন)
                                            </span>
                                        </td>
                                        <td>
                                            <span>{{ $assignment->location->name ?? '—' }}</span>
                                            <br><small style="color:var(--muted)">{{ $assignment->location->location_code ?? '' }}</small>
                                        </td>
                                        <td>
                                            @if($firstDate && $lastDate && $firstDate->ne($lastDate))
                                                <b>{{ $firstDate->format('d M') }} — {{ $lastDate->format('d M, Y') }}</b>
                                            @elseif($assignment->scheduled_date)
                                                <b>{{ $assignment->scheduled_date->format('d M, Y') }}</b>
                                            @else
                                                —
                                            @endif
                                            <br><small style="color:var(--muted)">{{ $assignment->formatted_time_range }}</small>
                                            @if($nextChild && $nextChild->scheduled_date)
                                                <br><span style="font-size:11px;color:#08783f;font-weight:600;">
                                                    ⏱ পরবর্তী: {{ $nextChild->scheduled_date->format('d M') }} ({{ $nextChild->status_label }})
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pill {{ $assignment->priority_badge_class }}">
                                                <span class="dot"></span>{{ $assignment->priority_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="min-width:135px;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                                                    <span style="font-size:11px;font-weight:700;color:#08783f;">{{ $progressPct }}%</span>
                                                    <span style="font-size:10.5px;color:var(--muted);">{{ $completedKids }}/{{ $totalChildren }} সম্পন্ন</span>
                                                </div>
                                                <div style="width:100%;height:6px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-bottom:4px;">
                                                    <div style="width:{{ $progressPct }}%;height:100%;background:#08783f;border-radius:4px;transition:width 0.3s ease;"></div>
                                                </div>
                                                <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px;">
                                                    @if($completedKids > 0)
                                                        <span style="color:#08783f;font-weight:600;">{{ $completedKids }} সম্পন্ন</span>
                                                    @endif
                                                    @if($inProgressKids > 0)
                                                        <span style="color:#2563eb;font-weight:600;">• {{ $inProgressKids }} চলমান</span>
                                                    @endif
                                                    @if($assignedKids > 0)
                                                        <span style="color:#64748b;">• {{ $assignedKids }} বাকি</span>
                                                    @endif
                                                    @if($cancelledKids > 0)
                                                        <span style="color:#dc2626;">• {{ $cancelledKids }} বাতিল</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small>{{ $assignment->assigner->name ?? 'অ্যাডমিন' }}</small>
                                        </td>
                                        <td style="text-align:right">
                                            <div style="display:flex;gap:4px;justify-content:flex-end;align-items:center;">
                                                <button type="button" class="btn soft" onclick="toggleSeriesDrawer('{{ $assignment->recurring_group_id }}', this)" style="padding:4px 8px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px;" title="সবগুলো তারিখের তালিকা দেখুন">
                                                    <span class="chevron-icon">▾</span> <span class="btn-label">{{ $totalChildren }}টি তারিখ</span>
                                                </button>
                                                <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn" style="padding:4px 8px;font-size:11px" title="সিরিজ বিস্তারিত">দেখুন</a>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Collapsible Drawer for this Recurring Series --}}
                                    <tr class="series-drawer-row" id="drawer-{{ $assignment->recurring_group_id }}" style="display:none;background:#f5faf7;">
                                        <td colspan="8" style="padding:0;border-bottom:2px solid #bbf7d0;">
                                            <div style="padding:14px 18px;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;border-bottom:1px solid #d2ded7;padding-bottom:8px;flex-wrap:wrap;gap:8px;">
                                                    <div>
                                                        <b style="font-size:13px;color:#14241d;">
                                                            🔄 {{ $assignment->taskTemplate->name }} — {{ $assignment->recurrence_type_label }} রুটিনের বিস্তারিত তালিকা
                                                        </b>
                                                        <span style="font-size:12px;color:var(--muted);margin-left:8px;">
                                                            (মোট {{ $totalChildren }}টি সেশন • কৃষক: {{ $assignment->farmer->full_name }})
                                                        </span>
                                                    </div>
                                                    <div style="display:flex;align-items:center;gap:8px;">
                                                        <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn soft" style="font-size:11px;padding:3px 10px;">
                                                            🔄 সিরিজ পরিচালনা ও বিকল্প কৃষক
                                                        </a>
                                                        <button type="button" class="btn" style="font-size:11px;padding:3px 8px;" onclick="toggleSeriesDrawer('{{ $assignment->recurring_group_id }}')">
                                                            ✕ বন্ধ করুন
                                                        </button>
                                                    </div>
                                                </div>

                                                <table class="table" style="background:#ffffff;border:1px solid #dcebe1;border-radius:8px;overflow:hidden;margin:0;">
                                                    <thead>
                                                        <tr style="background:#eaf4ee;font-size:11.5px;">
                                                            <th style="padding:6px 10px;width:60px;">পর্ব</th>
                                                            <th style="padding:6px 10px;">নির্ধারিত তারিখ</th>
                                                            <th style="padding:6px 10px;">সময়সীমা</th>
                                                            <th style="padding:6px 10px;">অ্যাসাইনকৃত কৃষক</th>
                                                            <th style="padding:6px 10px;">অবস্থা</th>
                                                            <th style="padding:6px 10px;text-align:right;">অ্যাকশন</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($children as $child)
                                                            <tr style="font-size:12px;border-bottom:1px solid #eef3f0;">
                                                                <td style="padding:6px 10px;">
                                                                    <span class="badge" style="background:#e0ede5;color:#08783f;font-weight:700;padding:2px 6px;border-radius:4px;font-size:11px;">
                                                                        #{{ $child->recurrence_seq ?? ($loop->iteration) }}
                                                                    </span>
                                                                </td>
                                                                <td style="padding:6px 10px;">
                                                                    <b>{{ $child->scheduled_date ? $child->scheduled_date->format('d M, Y') : '—' }}</b>
                                                                </td>
                                                                <td style="padding:6px 10px;">
                                                                    <small>{{ $child->formatted_time_range }}</small>
                                                                </td>
                                                                <td style="padding:6px 10px;">
                                                                    <b>{{ $child->farmer->full_name ?? '—' }}</b>
                                                                    @if($child->farmer_id != $assignment->farmer_id)
                                                                        <span class="pill" style="font-size:10px;padding:1px 5px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;margin-left:4px;">
                                                                            🔄 বিকল্প কৃষক
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td style="padding:6px 10px;">
                                                                    @php
                                                                        $stInfoChild = $statusMap[$child->status] ?? ['label' => $child->status, 'class' => ''];
                                                                    @endphp
                                                                    <span class="pill {{ $stInfoChild['class'] }}" style="font-size:11px;padding:2px 7px;">
                                                                        <span class="dot"></span>{{ $stInfoChild['label'] }}
                                                                    </span>
                                                                    @if($child->is_past_deadline && !in_array($child->status, ['completed', 'cancelled']))
                                                                        <span style="font-size:10px;color:var(--red);margin-left:3px;">● সময় অতিক্রান্ত</span>
                                                                    @endif
                                                                    @include('components.submission-health', ['assignment' => $child])
                                                                </td>
                                                                <td style="padding:6px 10px;text-align:right;">
                                                                    <div style="display:flex;gap:4px;justify-content:flex-end;">
                                                                        <a href="{{ route('admin.assignments.show', $child) }}" class="btn" style="padding:2px 7px;font-size:11px;">দেখুন</a>
                                                                        <a href="{{ route('admin.assignments.edit', $child) }}" class="btn soft" style="padding:2px 7px;font-size:11px;">সম্পাদনা</a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    {{-- Standard Single Task Row (Non-recurring or Flat Mode) --}}
                                    <tr>
                                        <td>
                                            <b>{{ $assignment->farmer->full_name ?? '—' }}</b>
                                            <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }} • {{ $assignment->farmer->phone ?? '' }}</small>
                                        </td>
                                        <td>
                                            <b>{{ $assignment->taskTemplate->name ?? '—' }}</b>
                                            <br><small style="color:var(--g700)">{{ $assignment->taskTemplate->category ?? '' }}</small>
                                            @if($assignment->is_recurring)
                                                <br>
                                                <span class="pill" style="font-size:10.5px;padding:1px 6px;background:#edfcf2;color:#08783f;border:1px solid #bbf7d0;display:inline-flex;align-items:center;gap:3px;margin-top:3px;">
                                                    🔄 {{ match($assignment->recurrence_type) { 'daily' => 'দৈনিক', 'weekly' => 'সাপ্তাহিক', 'monthly' => 'মাসিক', default => 'সিরিজ' } }} ({{ $assignment->recurrence_seq }}/{{ $assignment->recurrence_total_count }})
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span>{{ $assignment->location->name ?? '—' }}</span>
                                            <br><small style="color:var(--muted)">{{ $assignment->location->location_code ?? '' }}</small>
                                        </td>
                                        <td>
                                            <b>{{ $assignment->scheduled_date ? $assignment->scheduled_date->format('d M, Y') : '—' }}</b>
                                            <br><small style="color:var(--muted)">{{ $assignment->formatted_time_range }}</small>
                                            @if($assignment->is_past_deadline && !in_array($assignment->status, ['completed', 'cancelled']))
                                                <br><span style="font-size:10px;color:var(--red)">● সময় অতিক্রান্ত</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pill {{ $assignment->priority_badge_class }}">
                                                <span class="dot"></span>{{ $assignment->priority_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $stInfo = $statusMap[$assignment->status] ?? ['label' => $assignment->status, 'class' => ''];
                                            @endphp
                                            <span class="pill {{ $stInfo['class'] }}">
                                                <span class="dot"></span>{{ $stInfo['label'] }}
                                            </span>
                                            @include('components.submission-health', ['assignment' => $assignment])
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
                                @endif
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

                <script>
                    function toggleSeriesDrawer(groupId, btn) {
                        const drawer = document.getElementById('drawer-' + groupId);
                        if (!drawer) return;
                        const isHidden = (drawer.style.display === 'none' || drawer.style.display === '');
                        drawer.style.display = isHidden ? 'table-row' : 'none';

                        if (btn) {
                            const chevron = btn.querySelector('.chevron-icon');
                            if (chevron) {
                                chevron.innerText = isHidden ? '▴' : '▾';
                            }
                        }
                    }
                </script>

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
