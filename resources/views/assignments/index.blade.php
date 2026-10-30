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

                @php
                    $queryParams = array_filter([
                        'view_mode' => $viewMode,
                        'search' => $search,
                        'priority' => $priority,
                        'farmer_id' => $farmerId,
                        'location_id' => $locationId,
                        'date_filter' => $dateFilter,
                        'date' => $customDate,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'time_shift' => $timeShift,
                        'time_from' => $timeFrom,
                        'time_to' => $timeTo,
                        'sort_by' => $sortBy !== 'latest' ? $sortBy : null,
                    ], fn($v) => !is_null($v) && $v !== '');

                    $hasAdvancedFilters = !empty($dateFilter) || !empty($customDate) || !empty($startDate) || !empty($endDate) || !empty($timeShift) || !empty($timeFrom) || !empty($timeTo) || (!empty($sortBy) && $sortBy !== 'latest') || !empty($priority);
                    $anyFilterActive = ($search !== '') || $farmerId || $locationId || $priority || $dateFilter || $customDate || $startDate || $endDate || $timeShift || $timeFrom || $timeTo || ($sortBy && $sortBy !== 'latest');
                @endphp

                {{-- Comprehensive Search & Filter Toolbar --}}
                <div style="background:#f8faf9;border:1px solid #d4ded8;border-radius:10px;padding:12px 14px;margin-bottom:14px;">
                    <form method="GET" action="{{ route('admin.assignments.index') }}" id="assignmentFilterForm">
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif

                        {{-- Row 1: Primary Controls --}}
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            {{-- Search Input --}}
                            <div style="position:relative;flex:1;min-width:200px;">
                                <input class="input" type="text" name="search" value="{{ $search }}" placeholder="কৃষক, কাজ বা লোকেশনের নাম দিয়ে খুঁজুন..." style="padding:7px 12px;font-size:12px;width:100%;">
                            </div>

                            {{-- Farmer Dropdown --}}
                            <select name="farmer_id" class="input" style="padding:7px 10px;font-size:12px;width:auto;min-width:140px;" onchange="this.form.submit()">
                                <option value="">সকল কৃষক</option>
                                @foreach($farmers as $f)
                                    <option value="{{ $f->id }}" {{ (string)$farmerId === (string)$f->id ? 'selected' : '' }}>
                                        {{ $f->full_name }} ({{ $f->farmer_code }})
                                    </option>
                                @endforeach
                            </select>

                            {{-- Location/Area Dropdown --}}
                            <select name="location_id" class="input" style="padding:7px 10px;font-size:12px;width:auto;min-width:140px;" onchange="this.form.submit()">
                                <option value="">সকল এলাকা / লোকেশন</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ (string)$locationId === (string)$loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->location_code }})
                                    </option>
                                @endforeach
                            </select>

                            {{-- Filter Submit Button --}}
                            <button type="submit" class="btn primary" style="padding:7px 14px;font-size:12px;">🔍 ফিল্টার</button>

                            @if($anyFilterActive)
                                <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => $status])) }}" class="btn" style="padding:7px 12px;font-size:12px;" title="সব ফিল্টার রিসেট করুন">✕ রিসেট</a>
                            @endif

                            {{-- Advanced Filters Toggle Button --}}
                            <button type="button" class="btn soft" onclick="toggleAdvancedFilters()" style="padding:7px 12px;font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                                <span>⚙️ অতিরিক্ত ফিল্টার</span>
                                @if($hasAdvancedFilters)
                                    <span style="background:#08783f;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700;">সক্রিয়</span>
                                @endif
                                <span id="advFilterChevron">{{ $hasAdvancedFilters ? '▴' : '▾' }}</span>
                            </button>
                        </div>

                        {{-- Row 2: Advanced Filters Panel (Collapsible) --}}
                        <div id="advancedFilterPanel" style="display:{{ $hasAdvancedFilters ? 'flex' : 'none' }};gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px dashed #d4ded8;">
                            
                            {{-- Date Wise Dropdown & Inputs --}}
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span style="font-size:11px;font-weight:600;color:var(--muted);">তারিখ:</span>
                                <select name="date_filter" id="dateFilterSelect" class="input" style="padding:6px 10px;font-size:12px;width:auto;" onchange="handleDateFilterChange(this)">
                                    <option value="">সকল তারিখ</option>
                                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজকের কাজ</option>
                                    <option value="tomorrow" {{ $dateFilter === 'tomorrow' ? 'selected' : '' }}>আগামীকালের কাজ</option>
                                    <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>চলতি সপ্তাহ</option>
                                    <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>চলতি মাস</option>
                                    <option value="overdue" {{ $dateFilter === 'overdue' ? 'selected' : '' }}>সময় অতিক্রান্ত (ওভারডিউ)</option>
                                    <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>নির্দিষ্ট একক তারিখ</option>
                                    <option value="range" {{ $dateFilter === 'range' ? 'selected' : '' }}>কাস্টম তারিখ রেঞ্জ</option>
                                </select>

                                {{-- Single Custom Date --}}
                                <input type="date" id="customDateInput" name="date" class="input" value="{{ $customDate }}" style="padding:5px 8px;font-size:12px;width:auto;display:{{ $dateFilter === 'custom' ? 'inline-block' : 'none' }};" onchange="this.form.submit()">

                                {{-- Custom Date Range --}}
                                <div id="dateRangeInputs" style="display:{{ $dateFilter === 'range' ? 'inline-flex' : 'none' }};align-items:center;gap:4px;">
                                    <input type="date" name="start_date" class="input" value="{{ $startDate }}" style="padding:5px 8px;font-size:12px;width:auto;" title="শুরুর তারিখ">
                                    <span style="font-size:11px;color:var(--muted);">থেকে</span>
                                    <input type="date" name="end_date" class="input" value="{{ $endDate }}" style="padding:5px 8px;font-size:12px;width:auto;" title="শেষ তারিখ">
                                </div>
                            </div>

                            {{-- Time Shift / Range Filter --}}
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span style="font-size:11px;font-weight:600;color:var(--muted);">সময়সীমা:</span>
                                <select name="time_shift" id="timeShiftSelect" class="input" style="padding:6px 10px;font-size:12px;width:auto;" onchange="handleTimeShiftChange(this)">
                                    <option value="">সকল সময়</option>
                                    <option value="morning" {{ $timeShift === 'morning' ? 'selected' : '' }}>🌅 সকাল (০৫:০০ - ১২:০০)</option>
                                    <option value="noon" {{ $timeShift === 'noon' ? 'selected' : '' }}>☀️ দুপুর (১২:০০ - ১৫:০০)</option>
                                    <option value="afternoon" {{ $timeShift === 'afternoon' ? 'selected' : '' }}>🌤️ বিকাল (১৫:০০ - ১৮:০০)</option>
                                    <option value="evening_night" {{ $timeShift === 'evening_night' ? 'selected' : '' }}>🌙 সন্ধ্যা ও রাত (১৮:০০ - রাত)</option>
                                    <option value="custom" {{ $timeShift === 'custom' ? 'selected' : '' }}>⏱️ কাস্টম সময় রেঞ্জ</option>
                                </select>

                                <div id="timeRangeInputs" style="display:{{ $timeShift === 'custom' ? 'inline-flex' : 'none' }};align-items:center;gap:4px;">
                                    <input type="time" name="time_from" class="input" value="{{ $timeFrom }}" style="padding:5px 8px;font-size:12px;width:auto;" title="শুরুর সময়">
                                    <span style="font-size:11px;color:var(--muted);">-</span>
                                    <input type="time" name="time_to" class="input" value="{{ $timeTo }}" style="padding:5px 8px;font-size:12px;width:auto;" title="শেষ সময়">
                                </div>
                            </div>

                            {{-- Priority --}}
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span style="font-size:11px;font-weight:600;color:var(--muted);">অগ্রাধিকার:</span>
                                <select name="priority" class="input" style="padding:6px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                                    <option value="">সকল অগ্রাধিকার</option>
                                    <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>সাধারণ</option>
                                    <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ</option>
                                    <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>জরুরি</option>
                                </select>
                            </div>

                            {{-- Sort By --}}
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span style="font-size:11px;font-weight:600;color:var(--muted);">ক্রম (সর্ট):</span>
                                <select name="sort_by" class="input" style="padding:6px 10px;font-size:12px;width:auto;" onchange="this.form.submit()">
                                    <option value="latest" {{ $sortBy === 'latest' ? 'selected' : '' }}>🆕 সর্বশেষ সংযোজিত (নতুন আগে)</option>
                                    <option value="date_asc" {{ $sortBy === 'date_asc' ? 'selected' : '' }}>📅 কাজের তারিখ (নিকটবর্তী আগে)</option>
                                    <option value="date_desc" {{ $sortBy === 'date_desc' ? 'selected' : '' }}>📆 কাজের তারিখ (দূরবর্তী আগে)</option>
                                    <option value="priority" {{ $sortBy === 'priority' ? 'selected' : '' }}>⚡ অগ্রাধিকার (জরুরি আগে)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn soft" style="padding:6px 12px;font-size:11px;font-weight:700;">প্রয়োগ করুন</button>
                        </div>

                        {{-- Row 3: Active Filter Badges (Chips) --}}
                        @if($anyFilterActive)
                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:10px;padding-top:8px;border-top:1px solid #e2ede6;">
                                <span style="font-size:11px;color:var(--muted);font-weight:600;">সক্রিয় ফিল্টার:</span>

                                @if($search !== '')
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        সার্চ: "{{ $search }}"
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['search' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($farmerId && $farmers->find($farmerId))
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        কৃষক: {{ $farmers->find($farmerId)->full_name }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['farmer_id' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($locationId && $locations->find($locationId))
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        এলাকা: {{ $locations->find($locationId)->name }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['location_id' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($priority)
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        অগ্রাধিকার: {{ match($priority) { 'urgent' => 'জরুরি', 'important' => 'গুরুত্বপূর্ণ', default => 'সাধারণ' } }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['priority' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($dateFilter)
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        তারিখ: {{ match($dateFilter) {
                                            'today' => 'আজকের কাজ',
                                            'tomorrow' => 'আগামীকালের কাজ',
                                            'this_week' => 'চলতি সপ্তাহ',
                                            'this_month' => 'চলতি মাস',
                                            'overdue' => 'সময় অতিক্রান্ত',
                                            'custom' => $customDate ?: 'নির্দিষ্ট তারিখ',
                                            'range' => ($startDate && $endDate ? "{$startDate} থেকে {$endDate}" : 'তারিখ রেঞ্জ'),
                                            default => $dateFilter
                                        } }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['date_filter' => null, 'date' => null, 'start_date' => null, 'end_date' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($timeShift)
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        সময়: {{ match($timeShift) {
                                            'morning' => 'সকাল (০৫-১২)',
                                            'noon' => 'দুপুর (১২-১৫)',
                                            'afternoon' => 'বিকাল (১৫-১৮)',
                                            'evening_night' => 'সন্ধ্যা ও রাত',
                                            'custom' => ($timeFrom && $timeTo ? "{$timeFrom} - {$timeTo}" : 'কাস্টম সময়'),
                                            default => $timeShift
                                        } }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['time_shift' => null, 'time_from' => null, 'time_to' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                @if($sortBy && $sortBy !== 'latest')
                                    <span class="pill" style="font-size:11px;padding:2px 8px;background:#eef7f2;border:1px solid #c2e2d0;">
                                        সর্ট: {{ match($sortBy) {
                                            'date_asc' => 'তারিখ (নিকটবর্তী আগে)',
                                            'date_desc' => 'তারিখ (দূরবর্তী আগে)',
                                            'priority' => 'অগ্রাধিকার (জরুরি আগে)',
                                            default => $sortBy
                                        } }}
                                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['sort_by' => null])) }}" style="color:var(--red);margin-left:4px;text-decoration:none;font-weight:bold;">✕</a>
                                    </span>
                                @endif

                                <a href="{{ route('admin.assignments.index', array_filter(['view_mode' => $viewMode, 'status' => $status])) }}" style="font-size:11px;color:var(--red);margin-left:auto;text-decoration:none;font-weight:700;">সব মুছুন ✕</a>
                            </div>
                        @endif
                    </form>

                    <script>
                        function toggleAdvancedFilters() {
                            var panel = document.getElementById('advancedFilterPanel');
                            var chevron = document.getElementById('advFilterChevron');
                            if (panel.style.display === 'none' || panel.style.display === '') {
                                panel.style.display = 'flex';
                                chevron.innerText = '▴';
                            } else {
                                panel.style.display = 'none';
                                chevron.innerText = '▾';
                            }
                        }

                        function handleDateFilterChange(select) {
                            var dateInput = document.getElementById('customDateInput');
                            var rangeInputs = document.getElementById('dateRangeInputs');
                            if (select.value === 'custom') {
                                if (dateInput) { dateInput.style.display = 'inline-block'; dateInput.focus(); }
                                if (rangeInputs) { rangeInputs.style.display = 'none'; }
                            } else if (select.value === 'range') {
                                if (dateInput) { dateInput.style.display = 'none'; }
                                if (rangeInputs) { rangeInputs.style.display = 'inline-flex'; }
                            } else {
                                if (dateInput) { dateInput.style.display = 'none'; dateInput.value = ''; }
                                if (rangeInputs) { rangeInputs.style.display = 'none'; }
                                select.form.submit();
                            }
                        }

                        function handleTimeShiftChange(select) {
                            var rangeInputs = document.getElementById('timeRangeInputs');
                            if (select.value === 'custom') {
                                if (rangeInputs) { rangeInputs.style.display = 'inline-flex'; }
                            } else {
                                if (rangeInputs) { rangeInputs.style.display = 'none'; }
                                select.form.submit();
                            }
                        }
                    </script>
                </div>

                {{-- Status Tabs & View Mode Switcher Header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                    <div class="tabs" style="margin:0;">
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => null])) }}" class="tab {{ !$status ? 'active' : '' }}">
                            সব ({{ $totalAssignmentsCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => 'assigned'])) }}" class="tab {{ $status === 'assigned' ? 'active' : '' }}">
                            বরাদ্দকৃত ({{ $assignedCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => 'in_progress'])) }}" class="tab {{ $status === 'in_progress' ? 'active' : '' }}">
                            চলমান ({{ $inProgressCount }})
                        </a>
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => 'completed'])) }}" class="tab {{ $status === 'completed' ? 'active' : '' }}">
                            সম্পন্ন ({{ $completedCount }})
                        </a>
                        @if($lateCount > 0 || $status === 'late')
                            <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => 'late'])) }}" class="tab {{ $status === 'late' ? 'active' : '' }}" style="{{ $lateCount > 0 ? 'color:var(--red);' : '' }}">
                                সময় অতিক্রান্ত ({{ $lateCount }})
                            </a>
                        @endif
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['status' => 'cancelled'])) }}" class="tab {{ $status === 'cancelled' ? 'active' : '' }}">
                            বাতিল ({{ $cancelledCount }})
                        </a>
                    </div>

                    {{-- View Mode Switcher (Grouped vs Flat) --}}
                    <div style="display:inline-flex;background:#edf2ef;padding:3px;border-radius:8px;border:1px solid #d4ded8;">
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['view_mode' => 'grouped', 'status' => $status])) }}"
                           style="padding:5px 12px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s;{{ $viewMode === 'grouped' ? 'background:#08783f;color:#ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#4b6354;' }}"
                           title="প্রতিটি পুনরাবৃত্তিমূলক কাজের সিরিজ ১টি রো/কার্ডে দেখাবে">
                            📁 সিরিজ গ্রুপ ভিউ
                        </a>
                        <a href="{{ route('admin.assignments.index', array_merge($queryParams, ['view_mode' => 'flat', 'status' => $status])) }}"
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
                                                <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn" style="padding:4px 8px;font-size:11px" title="সিরিজ বিস্তারিত">দেখুন</a>
                                                <button type="button" class="btn soft" onclick="toggleSeriesDrawer('{{ $assignment->recurring_group_id }}', this)" style="padding:4px 8px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px;" title="সবগুলো তারিখের তালিকা দেখুন">
                                                    <span class="chevron-icon">▾</span> <span class="btn-label">{{ $totalChildren }}টি তারিখ</span>
                                                </button>
                                                <button type="button" class="btn red" onclick="promptDeleteSeries('{{ $assignment->recurring_group_id }}', '{{ addslashes($assignment->taskTemplate->name ?? 'কাজ') }}', {{ $totalChildren }}, {{ $completedKids }})" style="padding:4px 8px;font-size:11px" title="সম্পূর্ণ সিরিজ মুছুন">মুছুন</button>
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
                                                            <th style="padding:6px 10px;">কাজ</th>
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
                                                                    <b>{{ $child->taskTemplate->name ?? '—' }}</b>
                                                                    @if($child->location)
                                                                        <br><small style="color:var(--muted)">📍 {{ $child->location->name }}</small>
                                                                    @elseif($child->taskTemplate?->category)
                                                                        <br><small style="color:var(--muted)">{{ $child->taskTemplate->category }}</small>
                                                                    @endif
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
                                                                    <div style="display:flex;gap:4px;justify-content:flex-end;align-items:center;">
                                                                        <a href="{{ route('admin.assignments.show', $child) }}" class="btn" style="padding:2px 7px;font-size:11px;">দেখুন</a>
                                                                        <a href="{{ route('admin.assignments.edit', $child) }}" class="btn soft" style="padding:2px 7px;font-size:11px;">সম্পাদনা</a>
                                                                        <form method="POST" action="{{ route('admin.assignments.destroy', $child) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই তারিখের কাজটি মুছে ফেলতে চান? এটি সফট ডিলিট হবে।');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn red" style="padding:2px 7px;font-size:11px;">মুছুন</button>
                                                                        </form>
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

                    function promptDeleteSeries(groupId, taskName, totalCount, completedCount) {
                        const modal = document.getElementById('delete-series-modal');
                        const form = document.getElementById('delete-series-form');
                        const desc = document.getElementById('delete-modal-desc');
                        const warning = document.getElementById('delete-modal-completed-warning');
                        const completedCountSpan = document.getElementById('delete-modal-completed-count');
                        const completedOptions = document.getElementById('delete-modal-completed-options');
                        const directBtn = document.getElementById('delete-modal-direct-btn');

                        if (!modal || !form) return;

                        form.action = "{{ url('admin/assignments/recurring') }}/" + groupId;

                        if (completedCount > 0) {
                            desc.innerHTML = `আপনি <b>${taskName}</b> কাজের পুনরাবৃত্তিমূলক সিরিজটি মুছে ফেলতে চাচ্ছেন (মোট <b>${totalCount}</b>টি কাজ)।`;
                            if (completedCountSpan) completedCountSpan.innerText = completedCount;
                            if (warning) warning.style.display = 'block';
                            if (completedOptions) completedOptions.style.display = 'flex';
                            if (directBtn) directBtn.style.display = 'none';
                        } else {
                            desc.innerHTML = `আপনি কি নিশ্চিত যে <b>${taskName}</b> কাজের পুনরাবৃত্তিমূলক সিরিজের সকল <b>${totalCount}</b>টি কাজ মুছে ফেলতে চান? এটি সফট ডিলিট হবে।`;
                            if (warning) warning.style.display = 'none';
                            if (completedOptions) completedOptions.style.display = 'none';
                            if (directBtn) directBtn.style.display = 'inline-block';
                        }

                        modal.style.display = 'flex';
                    }

                    function submitDeleteSeries(includeCompleted) {
                        const form = document.getElementById('delete-series-form');
                        const input = document.getElementById('delete-series-include-completed');
                        if (!form || !input) return;

                        input.value = includeCompleted ? '1' : '0';
                        form.submit();
                    }

                    function closeDeleteSeriesModal() {
                        const modal = document.getElementById('delete-series-modal');
                        if (modal) modal.style.display = 'none';
                    }
                </script>

                {{-- Smart Delete Recurring Series Modal --}}
                <div id="delete-series-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
                    <div style="background:#ffffff;border-radius:12px;max-width:480px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.04);overflow:hidden;">
                        <div style="padding:16px 20px;border-bottom:1px solid #fee2e2;display:flex;align-items:center;justify-content:space-between;background:#fef2f2;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="font-size:20px;">🗑️</span>
                                <b style="font-size:15px;color:#991b1b;">সম্পূর্ণ পুনরাবৃত্তিমূলক সিরিজ মুছুন</b>
                            </div>
                            <button type="button" onclick="closeDeleteSeriesModal()" style="border:none;background:none;font-size:18px;cursor:pointer;color:#64748b;">✕</button>
                        </div>

                        <form id="delete-series-form" method="POST" action="">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="include_completed" id="delete-series-include-completed" value="0">

                            <div style="padding:20px;">
                                <p id="delete-modal-desc" style="font-size:13.5px;color:#334155;line-height:1.6;margin-bottom:14px;"></p>

                                {{-- Warning block when completed tasks exist --}}
                                <div id="delete-modal-completed-warning" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-bottom:14px;">
                                    <div style="display:flex;gap:8px;">
                                        <span style="font-size:16px;">⚠️</span>
                                        <div style="font-size:12.5px;color:#92400e;line-height:1.5;">
                                            <b>সতর্কতা:</b> এই সিরিজের <span id="delete-modal-completed-count" style="font-weight:700;"></span>টি কাজ ইতিমধ্যে সম্পন্ন হয়েছে। আপনি কি শুধুমাত্র বাকি অসম্পূর্ণ কাজগুলো মুছতে চান, নাকি সম্পন্ন কাজসহ সম্পূর্ণ সিরিজটিই মুছে ফেলবেন?
                                        </div>
                                    </div>
                                </div>

                                {{-- Action choices when completed tasks exist --}}
                                <div id="delete-modal-completed-options" style="display:none;flex-direction:column;gap:10px;margin-bottom:8px;">
                                    <button type="button" onclick="submitDeleteSeries(false)" style="padding:10px 14px;background:#f8fafc;border:1.5px solid #cbd5e1;color:#1e293b;border-radius:8px;text-align:left;display:flex;flex-direction:column;cursor:pointer;transition:all 0.15s;">
                                        <b style="font-size:13px;color:#0f172a;">🛡️ শুধুমাত্র বাকি অসম্পূর্ণ কাজগুলো মুছুন (প্রস্তাবিত)</b>
                                        <span style="font-size:11.5px;color:#64748b;margin-top:2px;">সম্পন্ন কাজের হিসেব ও কৃষকের জমা ছবি সংরক্ষিত থাকবে, কিন্তু বাকিগুলো মুছে যাবে।</span>
                                    </button>
                                    <button type="button" onclick="submitDeleteSeries(true)" style="padding:10px 14px;background:#fef2f2;border:1.5px solid #fecaca;color:#991b1b;border-radius:8px;text-align:left;display:flex;flex-direction:column;cursor:pointer;transition:all 0.15s;">
                                        <b style="font-size:13px;color:#dc2626;">⚠️ সম্পন্ন কাজসহ সম্পূর্ণ সিরিজটিই মুছুন</b>
                                        <span style="font-size:11.5px;color:#b91c1c;margin-top:2px;">ইতিমধ্যে সম্পন্ন কাজের হিসেবসহ সিরিজের সকল কাজ সফট ডিলিট হবে।</span>
                                    </button>
                                </div>
                            </div>

                            <div style="padding:14px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px;">
                                <button type="button" class="btn soft" onclick="closeDeleteSeriesModal()" style="padding:6px 14px;font-size:12.5px;">বাতিল</button>
                                <button type="button" id="delete-modal-direct-btn" onclick="submitDeleteSeries(true)" class="btn red" style="padding:6px 16px;font-size:12.5px;font-weight:600;">
                                    হ্যাঁ, সম্পূর্ণ সিরিজ মুছুন
                                </button>
                            </div>
                        </form>
                    </div>
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
