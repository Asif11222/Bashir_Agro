@extends('layouts.admin')

@section('title', 'নতুন কাজ বরাদ্দ করুন — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>নতুন কাজ বরাদ্দ করুন</h1>
            <p>কৃষক, ফার্ম লোকেশন এবং এককালীন বা পুনরাবৃত্তিমূলক কাজের সময়সূচি নির্ধারণ করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.index') }}" class="btn">← বরাদ্দ তালিকায় ফিরুন</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="notice warn" style="margin-bottom: 16px; border-left: 4px solid var(--red); background: #fff5f5; padding: 14px 16px; border-radius: 8px;">
            <b style="color:var(--red);font-size:14px;">অনুগ্রহ করে নিচের ত্রুটিগুলো সংশোধন করুন:</b>
            <ul style="margin: 8px 0 0 16px; padding: 0; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li style="color: var(--red); margin-bottom: 4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.assignments.store') }}" id="assignmentForm">
        @csrf

        <div class="form-layout-2col">
            <div class="form-main-col">
                <!-- Step 1: Base Target Selection (Farmer & Location) -->
                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">👤</span>
                            <div>
                                <h2>বরাদ্দের প্রাপক ও অবস্থান</h2>
                                <div class="sub">কৃষক এবং ফার্মের নির্দিষ্ট প্লট বা লোকেশন নির্ধারণ</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div>
                            <label class="label">কৃষক নির্বাচন <span class="req">*</span></label>
                            <select class="input" name="farmer_id" id="farmer_select" required>
                                <option value="">-- কৃষক নির্বাচন করুন --</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}" {{ old('farmer_id', $selectedFarmerId) == $farmer->id ? 'selected' : '' }}>
                                        {{ $farmer->full_name }} ({{ $farmer->farmer_code }}) — {{ $farmer->phone }}
                                    </option>
                                @endforeach
                            </select>
                            @error('farmer_id')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">ফার্ম লোকেশন (প্লট / জমি / পুকুর) <span class="req">*</span></label>
                            <select class="input" name="farm_location_id" required>
                                <option value="">-- ফার্মের লোকেশন নির্বাচন করুন --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('farm_location_id', $selectedLocationId) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->location_code }}) — {{ $loc->type }} [রেডিয়াস: {{ $loc->geofence_radius_meters }}মি]
                                    </option>
                                @endforeach
                            </select>
                            @error('farm_location_id')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>

                <!-- Step 2: Recurrence Selector -->
                <section class="card section-gap">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">🔄</span>
                            <div>
                                <h2>কাজের পুনরাবৃত্তি ও সময়সূচী (Recurrence)</h2>
                                <div class="sub">এককালীন, দৈনিক, সাপ্তাহিক বা মাসিক কাজের রুটিন নির্ধারণ</div>
                            </div>
                        </div>
                    </div>

                    @php $activeRecurrence = old('recurrence_type', 'once'); @endphp
                    <div class="recurrence-switcher">
                        <label class="rec-tab-item {{ $activeRecurrence === 'once' ? 'active' : '' }}" onclick="switchRecurrence('once')">
                            <input type="radio" name="recurrence_type" value="once" {{ $activeRecurrence === 'once' ? 'checked' : '' }} style="display:none;">
                            <span class="tab-icon">🔘</span>
                            <div class="tab-meta">
                                <b class="tab-title">এককালীন (Once)</b>
                                <span class="tab-desc">শুধুমাত্র নির্দিষ্ট ১ দিনের কাজ</span>
                            </div>
                        </label>

                        <label class="rec-tab-item {{ $activeRecurrence === 'daily' ? 'active' : '' }}" onclick="switchRecurrence('daily')">
                            <input type="radio" name="recurrence_type" value="daily" {{ $activeRecurrence === 'daily' ? 'checked' : '' }} style="display:none;">
                            <span class="tab-icon">📅</span>
                            <div class="tab-meta">
                                <b class="tab-title">দৈনিক (Daily)</b>
                                <span class="tab-desc">প্রতিদিনের একাধিক কাজের রুটিন</span>
                            </div>
                        </label>

                        <label class="rec-tab-item {{ $activeRecurrence === 'weekly' ? 'active' : '' }}" onclick="switchRecurrence('weekly')">
                            <input type="radio" name="recurrence_type" value="weekly" {{ $activeRecurrence === 'weekly' ? 'checked' : '' }} style="display:none;">
                            <span class="tab-icon">🗓</span>
                            <div class="tab-meta">
                                <b class="tab-title">সাপ্তাহিক (Weekly)</b>
                                <span class="tab-desc">সপ্তাহের নির্দিষ্ট দিনভিত্তিক কাজ</span>
                            </div>
                        </label>

                        <label class="rec-tab-item {{ $activeRecurrence === 'monthly' ? 'active' : '' }}" onclick="switchRecurrence('monthly')">
                            <input type="radio" name="recurrence_type" value="monthly" {{ $activeRecurrence === 'monthly' ? 'checked' : '' }} style="display:none;">
                            <span class="tab-icon">📆</span>
                            <div class="tab-meta">
                                <b class="tab-title">মাসিক (Monthly)</b>
                                <span class="tab-desc">প্রতি মাসের নির্দিষ্ট তারিখের কাজ</span>
                            </div>
                        </label>
                    </div>

                    <!-- Panel 1: Once (এককালীন) -->
                    <div id="panel-once" class="rec-panel" style="{{ $activeRecurrence === 'once' ? '' : 'display:none;' }}">
                        <div class="form-grid">
                            <div class="field full">
                                <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                <select class="input" name="task_template_id" id="once_task_template_id">
                                    <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                    @foreach($tasks as $task)
                                        <option value="{{ $task->id }}" {{ old('task_template_id', $selectedTaskId) == $task->id ? 'selected' : '' }}>
                                            {{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('task_template_id')
                                    <div class="help" style="color:var(--red)">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="label">বরাদ্দের তারিখ <span class="req">*</span></label>
                                <input class="input" type="date" name="scheduled_date" id="scheduled_date" value="{{ old('scheduled_date', date('Y-m-d')) }}">
                                @error('scheduled_date')
                                    <div class="help" style="color:var(--red)">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="label">অগ্রাধিকার <span class="req">*</span></label>
                                <select class="input" name="priority">
                                    <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>সাধারণ (Normal)</option>
                                    <option value="important" {{ old('priority') === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ (Important)</option>
                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>জরুরি (Urgent)</option>
                                </select>
                                @error('priority')
                                    <div class="help" style="color:var(--red)">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="label">শুরুর সময় <span class="req">*</span></label>
                                <input class="input" type="time" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}">
                                @error('start_time')
                                    <div class="help" style="color:var(--red)">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="label">শেষ সময় (সময়সীমা)</label>
                                <input class="input" type="time" id="end_time" name="end_time" value="{{ old('end_time', '22:00') }}">
                                @error('end_time')
                                    <div class="help" style="color:var(--red)">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field full">
                                <label class="label" style="font-size:12px;color:var(--muted);margin-bottom:4px;">⏱ দ্রুত সময়সূচী নির্বাচন (Quick Presets):</label>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                    <button type="button" class="btn soft" style="font-size:11px;padding:4px 8px;" onclick="setTimePreset('08:00', '22:00')">সকাল - রাত (08:00 AM - 10:00 PM)</button>
                                    <button type="button" class="btn soft" style="font-size:11px;padding:4px 8px;" onclick="setTimePreset('08:00', '23:59')">সারাদিন (08:00 AM - 11:59 PM)</button>
                                    <button type="button" class="btn soft" style="font-size:11px;padding:4px 8px;" onclick="setTimePreset('08:00', '12:00')">সকাল শিফট (08:00 AM - 12:00 PM)</button>
                                    <button type="button" class="btn soft" style="font-size:11px;padding:4px 8px;" onclick="setTimePreset('14:00', '18:00')">বিকাল শিফট (02:00 PM - 06:00 PM)</button>
                                    <button type="button" class="btn soft" style="font-size:11px;padding:4px 8px;" onclick="setFromNowPreset()">এখন থেকে +৪ ঘণ্টা</button>
                                </div>
                                <div id="time-preview-badge" style="margin-top:8px;font-size:13px;font-weight:600;color:#08783f;background:#edfcf2;border:1px solid #bbf7d0;padding:6px 10px;border-radius:6px;display:inline-block;"></div>
                                <div id="past-time-warning" style="display:none;margin-top:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:8px 12px;border-radius:8px;font-size:12px;line-height:1.4;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 2: Daily (দৈনিক) -->
                    <div id="panel-daily" class="rec-panel" style="{{ $activeRecurrence === 'daily' ? '' : 'display:none;' }}">
                        <div class="config-subcard">
                            <b class="subcard-title">📅 দৈনিক কাজের সময়কাল (তারিখ সীমা)</b>
                            <div class="form-grid" style="margin-top:10px;">
                                <div>
                                    <label class="label">শুরুর তারিখ <span class="req">*</span></label>
                                    <input class="input" type="date" name="start_date" id="daily_start_date" value="{{ old('start_date', date('Y-m-d')) }}">
                                </div>
                                <div>
                                    <label class="label">শেষের তারিখ <span class="req">*</span></label>
                                    <input class="input" type="date" name="end_date" id="daily_end_date" value="{{ old('end_date', date('Y-m-d', strtotime('+7 days'))) }}">
                                </div>
                                <div class="field full">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span style="font-size:12px;color:var(--muted)">দ্রুত দিন নির্বাচন:</span>
                                        <button type="button" class="btn soft" style="font-size:11px;padding:3px 8px;" onclick="setDailyDuration(7)">+ ৭ দিন</button>
                                        <button type="button" class="btn soft" style="font-size:11px;padding:3px 8px;" onclick="setDailyDuration(15)">+ ১৫ দিন</button>
                                        <button type="button" class="btn soft" style="font-size:11px;padding:3px 8px;" onclick="setDailyDuration(30)">+ ৩০ দিন (১ মাস)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:18px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <b style="font-size:14px;color:#14241d;">📋 প্রতিদিন যে যে কাজ করতে হবে (একাধিক কাজ যুক্ত করুন):</b>
                                <button type="button" class="btn soft" style="font-size:12px;padding:5px 10px;" onclick="addDailyTaskRow()">
                                    + আরও একটি কাজ যোগ করুন
                                </button>
                            </div>

                            <div id="daily-tasks-container" style="display:flex;flex-direction:column;gap:10px;">
                                <!-- Repeater Item Template -->
                                <div class="task-repeater-row" data-index="0">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                        <span class="row-badge">কাজ #১</span>
                                        <button type="button" class="btn-remove-row" onclick="removeTaskRow(this)" style="display:none;">✕ সরান</button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="field full">
                                            <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                            <select class="input" name="daily_tasks[0][task_template_id]">
                                                <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                                @foreach($tasks as $task)
                                                    <option value="{{ $task->id }}">{{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">শুরুর সময় <span class="req">*</span></label>
                                            <input class="input" type="time" name="daily_tasks[0][start_time]" value="08:00">
                                        </div>
                                        <div>
                                            <label class="label">শেষ সময়</label>
                                            <input class="input" type="time" name="daily_tasks[0][end_time]" value="12:00">
                                        </div>
                                        <div>
                                            <label class="label">অগ্রাধিকার</label>
                                            <select class="input" name="daily_tasks[0][priority]">
                                                <option value="normal">সাধারণ (Normal)</option>
                                                <option value="important">গুরুত্বপূর্ণ (Important)</option>
                                                <option value="urgent">জরুরি (Urgent)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">কাজের নোট (ঐচ্ছিক)</label>
                                            <input class="input" type="text" name="daily_tasks[0][notes]" placeholder="যেমন: সকালের খাবার/সেচ">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 3: Weekly (সাপ্তাহিক) -->
                    <div id="panel-weekly" class="rec-panel" style="{{ $activeRecurrence === 'weekly' ? '' : 'display:none;' }}">
                        <!-- Step 1: Duration & Days -->
                        <div class="config-subcard">
                            <b class="subcard-title">🗓 সাপ্তাহিক সময়কাল ও বারের তালিকা</b>
                            <div class="form-grid" style="margin-top:10px;">
                                <div>
                                    <label class="label">শুরুর তারিখ <span class="req">*</span></label>
                                    <input class="input" type="date" name="start_date" id="weekly_start_date" value="{{ old('start_date', date('Y-m-d')) }}" onchange="onWeeklyConfigChange()">
                                </div>
                                <div>
                                    <label class="label">কত সপ্তাহ চলবে? <span class="req">*</span></label>
                                    <select class="input" name="weeks_count" id="weeks_count" onchange="onWeeklyConfigChange()">
                                        <option value="1">১ সপ্তাহ</option>
                                        <option value="2">২ সপ্তাহ</option>
                                        <option value="4" selected>৪ সপ্তাহ (১ মাস)</option>
                                        <option value="8">৮ সপ্তাহ (২ মাস)</option>
                                        <option value="12">১২ সপ্তাহ (৩ মাস)</option>
                                        <option value="24">২৪ সপ্তাহ (৬ মাস)</option>
                                    </select>
                                </div>
                                <div class="field full">
                                    <label class="label">সপ্তাহের কোন কোন দিন কাজ হবে? <span class="req">*</span></label>
                                    <div class="weekday-picker">
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="sat" onchange="onWeeklyConfigChange()"> <span>শনিবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="sun" checked onchange="onWeeklyConfigChange()"> <span>রবিবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="mon" onchange="onWeeklyConfigChange()"> <span>সোমবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="tue" checked onchange="onWeeklyConfigChange()"> <span>মঙ্গলবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="wed" onchange="onWeeklyConfigChange()"> <span>বুধবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="thu" checked onchange="onWeeklyConfigChange()"> <span>বৃহস্পতিবার</span></label>
                                        <label class="day-chip"><input type="checkbox" name="weekly_days[]" value="fri" onchange="onWeeklyConfigChange()"> <span>শুক্রবার</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Timing Mode Switcher -->
                        <div class="config-subcard" style="margin-top:14px;background:#fcfefd;border-color:#bbf7d0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <b class="subcard-title" style="color:#166534;">⏱ সময় নির্ধারণের পদ্ধতি (Timing Mode)</b>
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">বিভিন্ন সপ্তাহে বা বিভিন্ন দিনে আলাদা সময় নির্ধারণ করতে নিচের যেকোনো একটি পদ্ধতি বেছে নিন</div>
                                </div>
                                <span class="badge success" id="timing-mode-badge" style="font-size:11px;">একই সময় মোড সক্রিয়</span>
                            </div>

                            <div class="timing-mode-group">
                                <label class="timing-mode-btn active" id="btn-mode-same" onclick="setTimingMode('same')">
                                    <input type="radio" name="weekly_timing_mode" value="same" checked>
                                    <span>🕒 একই সময় (সকল সেশনে একই সময়)</span>
                                </label>
                                <label class="timing-mode-btn" id="btn-mode-by-day" onclick="setTimingMode('by_day')">
                                    <input type="radio" name="weekly_timing_mode" value="by_day">
                                    <span>📅 বার-ভিত্তিক সময় (যেমন রবি, মঙ্গল, বৃহস্পতি আলাদা)</span>
                                </label>
                                <label class="timing-mode-btn" id="btn-mode-by-week" onclick="setTimingMode('by_week')">
                                    <input type="radio" name="weekly_timing_mode" value="by_week">
                                    <span>🗓️ সপ্তাহ-ভিত্তিক সময় (সপ্তাহ ১, ২, ৩, ৪ আলাদা)</span>
                                </label>
                                <label class="timing-mode-btn" id="btn-mode-custom" onclick="setTimingMode('custom')">
                                    <input type="radio" name="weekly_timing_mode" value="custom">
                                    <span>✏️ প্রতিটি সেশন আলাদা কাস্টমাইজ</span>
                                </label>
                            </div>

                            <!-- Mode A: Same time inputs -->
                            <div id="timing-config-same" class="timing-config-box" style="margin-top:14px;">
                                <div class="form-grid">
                                    <div>
                                        <label class="label">সাধারণ শুরুর সময় <span class="req">*</span></label>
                                        <input class="input" type="time" id="base_weekly_start_time" value="09:00" onchange="applyBaseWeeklyTimes()">
                                    </div>
                                    <div>
                                        <label class="label">সাধারণ শেষ সময়</label>
                                        <input class="input" type="time" id="base_weekly_end_time" value="13:00" onchange="applyBaseWeeklyTimes()">
                                    </div>
                                    <div class="field full">
                                        <span style="font-size:12px;color:var(--muted)">💡 এই সময়টি সব সপ্তাহের সকল নির্বাচিত বারে স্বয়ংক্রিয়ভাবে প্রযোজ্য হবে।</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Mode B: Day-wise time inputs -->
                            <div id="timing-config-by-day" class="timing-config-box" style="display:none;margin-top:14px;">
                                <div style="font-size:12px;color:#166534;font-weight:600;margin-bottom:8px;">📌 প্রতিটি বারের জন্য আলাদা সময় নির্ধারণ করুন:</div>
                                <div id="day-wise-inputs-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));gap:10px;">
                                    <!-- Populated dynamically based on checked days -->
                                </div>
                            </div>

                            <!-- Mode C: Week-wise time inputs -->
                            <div id="timing-config-by-week" class="timing-config-box" style="display:none;margin-top:14px;">
                                <div style="font-size:12px;color:#166534;font-weight:600;margin-bottom:8px;">📌 প্রতিটি সপ্তাহের জন্য আলাদা সময় নির্ধারণ করুন:</div>
                                <div id="week-wise-inputs-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:10px;">
                                    <!-- Populated dynamically based on weeks count -->
                                </div>
                            </div>

                            <!-- Mode D: Custom note -->
                            <div id="timing-config-custom" class="timing-config-box" style="display:none;margin-top:14px;">
                                <div style="background:#e0f2fe;border:1px solid #bae6fd;border-radius:8px;padding:10px 14px;color:#0369a1;font-size:12.5px;">
                                    ℹ️ নিচে প্রতিটি সেশনের টেবিল বা কার্ড থেকে সরাসরি যেকোনো দিনের সময় বা কাজের ধরন ইচ্ছামতো পরিবর্তন করতে পারবেন।
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Base Task Selection -->
                        <div class="config-subcard" style="margin-top:14px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <b class="subcard-title">📋 মূল কাজের ধরন ও অগ্রাধিকার</b>
                                <span style="font-size:11.5px;color:var(--muted)">সকল সেশনে ডিফল্ট হিসেবে প্রযোজ্য হবে</span>
                            </div>
                            <div class="form-grid">
                                <div class="field full">
                                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                    <select class="input" id="weekly_default_template_id" onchange="applyDefaultTemplateToAll()">
                                        <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                        @foreach($tasks as $task)
                                            <option value="{{ $task->id }}">{{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label">ডিফল্ট অগ্রাধিকার</label>
                                    <select class="input" id="weekly_default_priority" onchange="applyDefaultPriorityToAll()">
                                        <option value="normal">সাধারণ (Normal)</option>
                                        <option value="important">গুরুত্বপূর্ণ (Important)</option>
                                        <option value="urgent">জরুরি (Urgent)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">কাজের নোট (ঐচ্ছিক)</label>
                                    <input class="input" type="text" id="weekly_default_notes" placeholder="যেমন: নিয়মিত সাপ্তাহিক তদারকি" oninput="applyDefaultNotesToAll()">
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Interactive Full Schedule Preview & Per-Session Time Customizer -->
                        <div style="margin-top:18px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                                <div>
                                    <b style="font-size:14px;color:#14241d;">✨ সম্পূর্ণ শিডিউল ও প্রতিটি সেশনের সময়সূচী (Interactive Preview & Editor):</b>
                                    <div style="font-size:12px;color:var(--muted);">প্রতিটি সেশনের সময়, অগ্রাধিকার বা কাজের ধরন আলাদাভাবে পরিবর্তন করতে পারবেন অথবা বাদ দিতে পারবেন</div>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <button type="button" class="btn soft" style="font-size:12px;padding:5px 10px;" onclick="regenerateWeeklySchedule(true)">
                                        🔄 নতুন করে সাজান (Reset)
                                    </button>
                                </div>
                            </div>

                            <!-- Schedule Container: Grouped by Week -->
                            <div id="weekly-schedule-groups-container">
                                <!-- Generated dynamically by JS -->
                            </div>
                        </div>

                        <!-- Hidden fallback container to maintain backward-compatibility -->
                        <div id="weekly-fallback-inputs" style="display:none;"></div>
                    </div>

                    <!-- Panel 4: Monthly (মাসিক) -->
                    <div id="panel-monthly" class="rec-panel" style="{{ $activeRecurrence === 'monthly' ? '' : 'display:none;' }}">
                        <div class="config-subcard">
                            <b class="subcard-title">📆 মাসিক সময়কাল ও তারিখের তালিকা</b>
                            <div class="form-grid" style="margin-top:10px;">
                                <div>
                                    <label class="label">শুরুর তারিখ <span class="req">*</span></label>
                                    <input class="input" type="date" name="start_date" id="monthly_start_date" value="{{ old('start_date', date('Y-m-d')) }}">
                                </div>
                                <div>
                                    <label class="label">কত মাস চলবে? <span class="req">*</span></label>
                                    <select class="input" name="months_count" id="months_count">
                                        <option value="1">১ মাস</option>
                                        <option value="2">২ মাস</option>
                                        <option value="3" selected>৩ মাস (১ কোয়ার্টার)</option>
                                        <option value="6">৬ মাস (অর্ধবছর)</option>
                                        <option value="12">১২ মাস (১ বছর)</option>
                                    </select>
                                </div>
                                <div class="field full">
                                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                                        <label class="label" style="margin:0;">প্রতি মাসের কোন কোন তারিখে কাজ হবে? <span class="req">*</span></label>
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            <span style="font-size:11.5px;color:var(--muted)">কুইক সিলেক্ট:</span>
                                            <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;" onclick="setMonthlyPreset([1, 15])">১ ও ১৫</button>
                                            <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;" onclick="setMonthlyPreset([5, 20])">৫ ও ২০</button>
                                            <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;" onclick="setMonthlyPreset([10, 25])">১০ ও ২৫</button>
                                            <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;" onclick="setMonthlyPreset([31])">মাসের শেষ দিন</button>
                                            <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;color:#dc2626;" onclick="clearMonthlyDays()">সব মুছুন</button>
                                        </div>
                                    </div>

                                    <!-- 1 to 31 Calendar Grid Picker -->
                                    <div class="month-day-grid" id="monthly-days-grid">
                                        @php
                                            $bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
                                        @endphp
                                        @for($d = 1; $d <= 31; $d++)
                                            @php
                                                $dText = str_replace(range(0, 9), $bnDigits, (string)$d);
                                            @endphp
                                            <label class="month-day-cell {{ in_array($d, [1, 15]) ? 'selected' : '' }}" id="cell-day-{{ $d }}" title="{{ $dText }} তারিখ">
                                                <input type="checkbox" name="monthly_days[]" value="{{ $d }}" {{ in_array($d, [1, 15]) ? 'checked' : '' }} onchange="onMonthlyDayChange()">
                                                <span class="cell-num">{{ $dText }}</span>
                                            </label>
                                        @endfor
                                        <div class="month-day-cell last-day-cell" id="cell-day-last" title="মাসের শেষ দিন (ফেব্রুয়ারিতে ২৮/২৯, অন্য মাসে ৩০ বা ৩১)" onclick="toggleDay31()">
                                            <span class="cell-num">🏁 মাসের শেষ দিন (৩১)</span>
                                        </div>
                                    </div>

                                    <!-- Custom Date Input Adder -->
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap;">
                                        <span style="font-size:12px;color:#3f5147;font-weight:600;">➕ কাস্টম দিন যোগ/বাদ দিন:</span>
                                        <input type="text" id="custom_month_day_input" placeholder="১-৩১" style="width:75px;padding:5px 8px;font-size:13px;border:1px solid #c9d8cf;border-radius:6px;text-align:center;" onkeydown="if(event.key==='Enter'){event.preventDefault();addCustomMonthDay();}">
                                        <button type="button" class="btn soft" style="font-size:12px;padding:5px 12px;font-weight:600;" onclick="addCustomMonthDay()">+ দিন যুক্ত করুন</button>
                                        <span id="selected-days-badge" style="font-size:12px;color:#08783f;background:#edfcf2;border:1px solid #bbf7d0;padding:4px 10px;border-radius:6px;font-weight:600;">
                                            নির্বাচিত: ১, ১৫ তারিখ (মোট ২ দিন)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:18px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <b style="font-size:14px;color:#14241d;">📋 মাসিক নির্ধারিত কাজের তালিকা:</b>
                                <button type="button" class="btn soft" style="font-size:12px;padding:5px 10px;" onclick="addMonthlyTaskRow()">
                                    + আরও একটি কাজ যোগ করুন
                                </button>
                            </div>

                            <div id="monthly-tasks-container" style="display:flex;flex-direction:column;gap:10px;">
                                <div class="task-repeater-row" data-index="0">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                        <span class="row-badge">কাজ #১</span>
                                        <button type="button" class="btn-remove-row" onclick="removeTaskRow(this)" style="display:none;">✕ সরান</button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="field full">
                                            <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                            <select class="input" name="monthly_tasks[0][task_template_id]">
                                                <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                                @foreach($tasks as $task)
                                                    <option value="{{ $task->id }}">{{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">শুরুর সময় <span class="req">*</span></label>
                                            <input class="input" type="time" name="monthly_tasks[0][start_time]" value="10:00">
                                        </div>
                                        <div>
                                            <label class="label">শেষ সময়</label>
                                            <input class="input" type="time" name="monthly_tasks[0][end_time]" value="14:00">
                                        </div>
                                        <div>
                                            <label class="label">অগ্রাধিকার</label>
                                            <select class="input" name="monthly_tasks[0][priority]">
                                                <option value="normal">সাধারণ (Normal)</option>
                                                <option value="important">গুরুত্বপূর্ণ (Important)</option>
                                                <option value="urgent">জরুরি (Urgent)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">নোট (ঐচ্ছিক)</label>
                                            <input class="input" type="text" name="monthly_tasks[0][notes]" placeholder="যেমন: মাসিক স্বাস্থ্য পরীক্ষা বা ওজন গ্রহণ">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Summary Preview Box -->
                    <div id="live-recurrence-summary" class="summary-preview-card" style="margin-top:20px;">
                        <div class="summary-icon">✨</div>
                        <div class="summary-content">
                            <b class="summary-title" id="summary-title-text">কাজের শিডিউল সারসংক্ষেপ</b>
                            <div class="summary-body" id="summary-body-text">
                                লোড হচ্ছে...
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:16px;">
                        <label class="label">সার্বিক নির্দেশাবলী বা অতিরিক্ত নোট (ঐচ্ছিক)</label>
                        <textarea class="input" name="notes" rows="2" placeholder="মাঠ পর্যায়ে কৃষকের প্রতি সাধারণ নির্দেশনা বা সতর্কতা থাকলে লিখুন...">{{ old('notes') }}</textarea>
                    </div>
                </section>
            </div>

            <!-- Sidebar Guidance -->
            <div class="form-side-col">
                <section class="card sticky-side-card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">ℹ</span>
                            <div>
                                <h3 style="font-size:15px;margin:0;color:var(--ink)">বরাদ্দ নির্দেশিকা ও নিয়ম</h3>
                                <div class="sub">পুনরাবৃত্তি ও ব্যবস্থাপনা নীতি</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;color:#3f5147;line-height:1.5;">
                        <div style="background:#f4fbf6;border:1px solid #d4eedd;padding:12px;border-radius:9px;">
                            <b style="color:#08783f;display:flex;align-items:center;gap:6px;">
                                <span>🔄</span> স্বয়ংক্রিয় সিরিজ বরাদ্দ
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                দৈনিক, সাপ্তাহিক বা মাসিক শিডিউলে নির্ধারিত সকল কাজ স্বয়ংক্রিয়ভাবে কৃষকের মোবাইল অ্যাপে ক্যালেন্ডার অনুযায়ী নির্দিষ্ট দিনে পৌঁছে যাবে।
                            </p>
                        </div>

                        <div style="background:#eef6ff;border:1px solid #bfdbfe;padding:12px;border-radius:9px;">
                            <b style="color:#1d4ed8;display:flex;align-items:center;gap:6px;">
                                <span>🏥</span> কৃষক অসুস্থ হলে করণীয়
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                যদি কোনো কৃষক হঠাৎ অসুস্থ হন, তবে বরাদ্দের বিস্তারিত পেজ থেকে এক ক্লিকেই <b>বিকল্প কৃষক প্রতিস্থাপন (Reassign)</b> করতে পারবেন—শুধুমাত্র ওইদিনের জন্য অথবা ভবিষ্যৎ সকল কাজের জন্য।
                            </p>
                        </div>

                        <div style="background:#fff9ed;border:1px solid #fce3b8;padding:12px;border-radius:9px;">
                            <b style="color:#a16207;display:flex;align-items:center;gap:6px;">
                                <span>⏱</span> একই দিনে একাধিক কাজ
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                সময় ভিন্ন হলে একই দিনে একই কাজ একাধিকবার বরাদ্দ করা যায় (যেমন: সকালের খাদ্য ও বিকালের খাদ্য)।
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="sticky-actions">
            <a href="{{ route('admin.assignments.index') }}" class="btn">বাতিল</a>
            <button type="submit" class="btn primary" id="submit-btn">✓ কাজ বরাদ্দ সম্পন্ন করুন</button>
        </div>
    </form>
</div>

<style>
.recurrence-switcher {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .recurrence-switcher {
        grid-template-columns: repeat(2, 1fr);
    }
}
.rec-tab-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #f8faf9;
    border: 2px solid #e2e8e5;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.rec-tab-item:hover {
    border-color: #8fa298;
    background: #f1f6f3;
}
.rec-tab-item.active {
    border-color: #08783f;
    background: #edfcf2;
    box-shadow: 0 2px 6px rgba(8, 120, 63, 0.12);
}
.rec-tab-item .tab-icon {
    font-size: 20px;
}
.rec-tab-item .tab-title {
    display: block;
    font-size: 13.5px;
    color: #14241d;
}
.rec-tab-item.active .tab-title {
    color: #08783f;
}
.rec-tab-item .tab-desc {
    display: block;
    font-size: 11px;
    color: #6f7f77;
    margin-top: 2px;
}
.config-subcard {
    background: #f9fbf9;
    border: 1px solid #dcebe1;
    padding: 14px 16px;
    border-radius: 10px;
}
.subcard-title {
    font-size: 13.5px;
    color: #14241d;
    display: block;
}
.weekday-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
}
.day-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    border: 1px solid #c9d8cf;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12.5px;
    color: #273b30;
    cursor: pointer;
    transition: all 0.15s ease;
}
.day-chip:has(input:checked) {
    background: #eaf8ef;
    border-color: #08783f;
    color: #08783f;
    font-weight: 600;
}
.month-day-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
    background: #ffffff;
    border: 1.5px solid #dcebe1;
    border-radius: 10px;
    padding: 10px;
    max-width: 520px;
}
@media (max-width: 600px) {
    .month-day-grid {
        grid-template-columns: repeat(7, 1fr);
        max-width: 100%;
    }
}
.month-day-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    background: #f8faf9;
    border: 1px solid #d2ded7;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #273b30;
    user-select: none;
    transition: all 0.15s ease;
}
.month-day-cell input {
    display: none;
}
.month-day-cell:hover {
    background: #eaf8ef;
    border-color: #08783f;
    color: #08783f;
}
.month-day-cell.selected,
.month-day-cell:has(input:checked) {
    background: #08783f !important;
    border-color: #08783f !important;
    color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(8, 120, 63, 0.25);
}
.month-day-cell.last-day-cell {
    grid-column: span 4;
    font-size: 12px;
}
.task-repeater-row {
    background: #ffffff;
    border: 1px solid #e0eae4;
    border-radius: 9px;
    padding: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.row-badge {
    font-size: 11.5px;
    font-weight: 700;
    background: #e2f2e7;
    color: #08783f;
    padding: 2px 8px;
    border-radius: 4px;
}
.btn-remove-row {
    background: none;
    border: none;
    color: #dc2626;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    padding: 2px 6px;
}
.btn-remove-row:hover {
    text-decoration: underline;
}
.summary-preview-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    padding: 14px 18px;
    border-radius: 10px;
}
.summary-icon {
    font-size: 24px;
    line-height: 1;
}
.summary-title {
    font-size: 14px;
    color: #166534;
    display: block;
    margin-bottom: 4px;
}
.summary-body {
    font-size: 13px;
    color: #14532d;
    line-height: 1.5;
}
.timing-mode-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.timing-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    border: 1.5px solid #d2ded7;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #273b30;
    cursor: pointer;
    transition: all 0.15s ease;
}
.timing-mode-btn:hover {
    border-color: #08783f;
    background: #f0fdf4;
}
.timing-mode-btn.active,
.timing-mode-btn:has(input:checked) {
    background: #08783f !important;
    border-color: #08783f !important;
    color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(8, 120, 63, 0.25);
}
.timing-mode-btn input {
    display: none;
}
.day-time-card, .week-time-card {
    background: #ffffff;
    border: 1px solid #d2ded7;
    border-radius: 8px;
    padding: 10px 12px;
}
.day-time-card b, .week-time-card b {
    display: block;
    font-size: 12.5px;
    color: #166534;
    margin-bottom: 6px;
}
.week-group-card {
    background: #ffffff;
    border: 1px solid #dcebe1;
    border-radius: 10px;
    margin-bottom: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.week-group-header {
    background: #f5fbf7;
    border-bottom: 1px solid #dcebe1;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.week-session-row {
    padding: 10px 14px;
    border-bottom: 1px solid #edf4f0;
    display: grid;
    grid-template-columns: 170px 1.5fr 110px 110px 110px 40px;
    gap: 10px;
    align-items: center;
    transition: background 0.15s ease;
}
.week-session-row:last-child {
    border-bottom: none;
}
.week-session-row:hover {
    background: #fafdfb;
}
.btn-remove-session {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.15s ease;
}
.btn-remove-session:hover {
    background: #dc2626;
    color: #ffffff;
}
@media (max-width: 900px) {
    .week-session-row {
        grid-template-columns: 1fr;
        gap: 8px;
        background: #fcfdfc;
        border: 1px solid #edf4f0;
        border-radius: 8px;
        margin: 8px 10px;
    }
}
</style>

<script>
// Available Task Templates for dynamic row creation
const taskTemplatesOptions = `
    <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
    @foreach($tasks as $task)
        <option value="{{ $task->id }}">{{ addslashes($task->name) }} ({{ $task->task_code }}) — {{ addslashes($task->category) }}</option>
    @endforeach
`;

let currentRecurrence = '{{ $activeRecurrence }}';

function switchRecurrence(type) {
    currentRecurrence = type;
    document.querySelectorAll('.rec-tab-item').forEach(tab => {
        const input = tab.querySelector('input');
        if (input.value === type) {
            tab.classList.add('active');
            input.checked = true;
        } else {
            tab.classList.remove('active');
            input.checked = false;
        }
    });

    document.querySelectorAll('.rec-panel').forEach(panel => {
        panel.style.display = 'none';
    });
    const activePanel = document.getElementById(`panel-${type}`);
    if (activePanel) {
        activePanel.style.display = 'block';
    }

    // Toggle required attributes for once mode
    const onceTemplateSelect = document.getElementById('once_task_template_id');
    const scheduledDateInput = document.getElementById('scheduled_date');
    const startTimeInput = document.getElementById('start_time');

    if (type === 'once') {
        if (onceTemplateSelect) onceTemplateSelect.required = true;
        if (scheduledDateInput) scheduledDateInput.required = true;
        if (startTimeInput) startTimeInput.required = true;
    } else {
        if (onceTemplateSelect) onceTemplateSelect.required = false;
        if (scheduledDateInput) scheduledDateInput.required = false;
        if (startTimeInput) startTimeInput.required = false;
    }

    if (type === 'weekly') {
        regenerateWeeklySchedule();
    }

    calculateSummary();
}

function setDailyDuration(days) {
    const startInput = document.getElementById('daily_start_date');
    const endInput = document.getElementById('daily_end_date');
    if (!startInput || !endInput) return;

    const startDate = startInput.value ? new Date(startInput.value) : new Date();
    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + (days - 1));

    endInput.value = endDate.toISOString().split('T')[0];
    calculateSummary();
}

function addDailyTaskRow() {
    const container = document.getElementById('daily-tasks-container');
    const index = container.querySelectorAll('.task-repeater-row').length;
    const rowHtml = `
        <div class="task-repeater-row" data-index="${index}">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span class="row-badge">কাজ #${index + 1}</span>
                <button type="button" class="btn-remove-row" onclick="removeTaskRow(this)">✕ সরান</button>
            </div>
            <div class="form-grid">
                <div class="field full">
                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                    <select class="input" name="daily_tasks[${index}][task_template_id]" required>
                        ${taskTemplatesOptions}
                    </select>
                </div>
                <div>
                    <label class="label">শুরুর সময় <span class="req">*</span></label>
                    <input class="input" type="time" name="daily_tasks[${index}][start_time]" value="14:00" required>
                </div>
                <div>
                    <label class="label">শেষ সময়</label>
                    <input class="input" type="time" name="daily_tasks[${index}][end_time]" value="18:00">
                </div>
                <div>
                    <label class="label">অগ্রাধিকার</label>
                    <select class="input" name="daily_tasks[${index}][priority]">
                        <option value="normal">সাধারণ (Normal)</option>
                        <option value="important">গুরুত্বপূর্ণ (Important)</option>
                        <option value="urgent">জরুরি (Urgent)</option>
                    </select>
                </div>
                <div>
                    <label class="label">কাজের নোট (ঐচ্ছিক)</label>
                    <input class="input" type="text" name="daily_tasks[${index}][notes]" placeholder="যেমন: বিকালের পরিদর্শন">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', rowHtml);
    updateRowBadges(container);
    calculateSummary();
}

// Task Templates JSON list for interactive schedule generator
const taskTemplatesList = @json($tasks->map(fn($t) => [
    'id' => $t->id,
    'name' => $t->name,
    'task_code' => $t->task_code,
    'category' => $t->category,
]));

const dayNamesBn = {
    'sun': 'রবিবার',
    'mon': 'সোমবার',
    'tue': 'মঙ্গলবার',
    'wed': 'বুধবার',
    'thu': 'বৃহস্পতিবার',
    'fri': 'শুক্রবার',
    'sat': 'শনিবার'
};
const dayIndices = {
    'sun': 0, 'mon': 1, 'tue': 2, 'wed': 3, 'thu': 4, 'fri': 5, 'sat': 6
};
const dayKeyFromIndex = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

const bnMonths = [
    'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
    'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
];

function formatBnDate(dateObj) {
    const day = toBnDigits(dateObj.getDate());
    const month = bnMonths[dateObj.getMonth()];
    const year = toBnDigits(dateObj.getFullYear());
    return `${day} ${month} ${year}`;
}

function formatISODate(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

let currentTimingMode = 'same'; // 'same', 'by_day', 'by_week', 'custom'

function setTimingMode(mode) {
    currentTimingMode = mode;
    document.querySelectorAll('.timing-mode-btn').forEach(btn => {
        const input = btn.querySelector('input');
        if (input.value === mode) {
            input.checked = true;
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    const badge = document.getElementById('timing-mode-badge');
    if (badge) {
        if (mode === 'same') badge.innerText = 'একই সময় মোড সক্রিয়';
        else if (mode === 'by_day') badge.innerText = 'বার-ভিত্তিক মোড সক্রিয়';
        else if (mode === 'by_week') badge.innerText = 'সপ্তাহ-ভিত্তিক মোড সক্রিয়';
        else if (mode === 'custom') badge.innerText = 'প্রতিটি সেশন কাস্টমাইজ মোড';
    }

    const sameBox = document.getElementById('timing-config-same');
    const dayBox = document.getElementById('timing-config-by-day');
    const weekBox = document.getElementById('timing-config-by-week');
    const customBox = document.getElementById('timing-config-custom');

    if (sameBox) sameBox.style.display = (mode === 'same') ? 'block' : 'none';
    if (dayBox) dayBox.style.display = (mode === 'by_day') ? 'block' : 'none';
    if (weekBox) weekBox.style.display = (mode === 'by_week') ? 'block' : 'none';
    if (customBox) customBox.style.display = (mode === 'custom') ? 'block' : 'none';

    if (mode === 'by_day') {
        renderDayWiseInputs();
    } else if (mode === 'by_week') {
        renderWeekWiseInputs();
    }

    regenerateWeeklySchedule();
}

function renderDayWiseInputs() {
    const grid = document.getElementById('day-wise-inputs-grid');
    if (!grid) return;

    const checkedBoxes = Array.from(document.querySelectorAll('#panel-weekly input[name="weekly_days[]"]:checked'));
    const selectedDayKeys = checkedBoxes.map(cb => cb.value);

    let html = '';
    selectedDayKeys.forEach(dayKey => {
        const dayName = dayNamesBn[dayKey] || dayKey;
        const prevStart = document.getElementById(`time_day_${dayKey}_start`)?.value || '09:00';
        const prevEnd = document.getElementById(`time_day_${dayKey}_end`)?.value || '13:00';

        html += `
            <div class="day-time-card">
                <b>🗓 ${dayName}</b>
                <div style="display:flex;gap:6px;align-items:center;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:var(--muted)">শুরু:</label>
                        <input class="input" type="time" id="time_day_${dayKey}_start" value="${prevStart}" onchange="onDayWiseTimeChange('${dayKey}')" style="padding:4px 6px;font-size:12px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:var(--muted)">শেষ:</label>
                        <input class="input" type="time" id="time_day_${dayKey}_end" value="${prevEnd}" onchange="onDayWiseTimeChange('${dayKey}')" style="padding:4px 6px;font-size:12px;">
                    </div>
                </div>
            </div>
        `;
    });

    if (selectedDayKeys.length === 0) {
        html = '<div style="color:var(--muted);font-size:12px;">কোনো দিন নির্বাচিত নেই। উপরে দিন নির্বাচন করুন।</div>';
    }

    grid.innerHTML = html;
}

function renderWeekWiseInputs() {
    const grid = document.getElementById('week-wise-inputs-grid');
    if (!grid) return;

    const weeksCount = parseInt(document.getElementById('weeks_count')?.value || '1', 10);

    let html = '';
    for (let w = 0; w < weeksCount; w++) {
        const weekNum = w + 1;
        const prevStart = document.getElementById(`time_week_${w}_start`)?.value || '09:00';
        const prevEnd = document.getElementById(`time_week_${w}_end`)?.value || '13:00';

        html += `
            <div class="week-time-card">
                <b>🗓 সপ্তাহ ${toBnDigits(weekNum)} (Week ${weekNum})</b>
                <div style="display:flex;gap:6px;align-items:center;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:var(--muted)">শুরু:</label>
                        <input class="input" type="time" id="time_week_${w}_start" value="${prevStart}" onchange="onWeekWiseTimeChange(${w})" style="padding:4px 6px;font-size:12px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:var(--muted)">শেষ:</label>
                        <input class="input" type="time" id="time_week_${w}_end" value="${prevEnd}" onchange="onWeekWiseTimeChange(${w})" style="padding:4px 6px;font-size:12px;">
                    </div>
                </div>
            </div>
        `;
    }

    grid.innerHTML = html;
}

function onDayWiseTimeChange(dayKey) {
    const startTime = document.getElementById(`time_day_${dayKey}_start`)?.value || '09:00';
    const endTime = document.getElementById(`time_day_${dayKey}_end`)?.value || '13:00';

    document.querySelectorAll(`.week-session-row[data-day="${dayKey}"]`).forEach(row => {
        const startInput = row.querySelector('.session-start-time');
        const endInput = row.querySelector('.session-end-time');
        if (startInput) startInput.value = startTime;
        if (endInput) endInput.value = endTime;
    });

    calculateSummary();
}

function onWeekWiseTimeChange(weekIndex) {
    const startTime = document.getElementById(`time_week_${weekIndex}_start`)?.value || '09:00';
    const endTime = document.getElementById(`time_week_${weekIndex}_end`)?.value || '13:00';

    document.querySelectorAll(`.week-group-card[data-week="${weekIndex}"] .week-session-row`).forEach(row => {
        const startInput = row.querySelector('.session-start-time');
        const endInput = row.querySelector('.session-end-time');
        if (startInput) startInput.value = startTime;
        if (endInput) endInput.value = endTime;
    });

    calculateSummary();
}

function promptSetWeekTime(weekIndex) {
    const currentStart = document.getElementById(`time_week_${weekIndex}_start`)?.value || '09:00';
    const currentEnd = document.getElementById(`time_week_${weekIndex}_end`)?.value || '13:00';

    const newStart = prompt(`সপ্তাহ ${toBnDigits(weekIndex + 1)}-এর সকল সেশনের শুরুর সময় (HH:MM):`, currentStart);
    if (!newStart) return;

    const newEnd = prompt(`সপ্তাহ ${toBnDigits(weekIndex + 1)}-এর সকল সেশনের শেষ সময় (HH:MM):`, currentEnd);

    const weekStartInput = document.getElementById(`time_week_${weekIndex}_start`);
    const weekEndInput = document.getElementById(`time_week_${weekIndex}_end`);
    if (weekStartInput) weekStartInput.value = newStart;
    if (weekEndInput && newEnd) weekEndInput.value = newEnd;

    document.querySelectorAll(`.week-group-card[data-week="${weekIndex}"] .week-session-row`).forEach(row => {
        const startInput = row.querySelector('.session-start-time');
        const endInput = row.querySelector('.session-end-time');
        if (startInput) startInput.value = newStart;
        if (endInput && newEnd) endInput.value = newEnd;
    });

    calculateSummary();
}

function applyBaseWeeklyTimes() {
    if (currentTimingMode !== 'same') return;
    const start = document.getElementById('base_weekly_start_time')?.value || '09:00';
    const end = document.getElementById('base_weekly_end_time')?.value || '13:00';

    document.querySelectorAll('#weekly-schedule-groups-container .session-start-time').forEach(el => el.value = start);
    document.querySelectorAll('#weekly-schedule-groups-container .session-end-time').forEach(el => el.value = end);
    updateFallbackInputs();
    calculateSummary();
}

function applyDefaultTemplateToAll() {
    const val = document.getElementById('weekly_default_template_id')?.value;
    if (!val) return;
    document.querySelectorAll('.session-template-select').forEach(select => {
        select.value = val;
    });
    updateFallbackInputs();
}

function applyDefaultPriorityToAll() {
    const val = document.getElementById('weekly_default_priority')?.value;
    if (!val) return;
    document.querySelectorAll('.session-priority-select').forEach(select => {
        select.value = val;
    });
    updateFallbackInputs();
}

function applyDefaultNotesToAll() {
    const val = document.getElementById('weekly_default_notes')?.value;
    document.querySelectorAll('.session-notes-input').forEach(input => {
        input.value = val;
    });
    updateFallbackInputs();
}

function onWeeklyConfigChange() {
    if (currentTimingMode === 'by_day') {
        renderDayWiseInputs();
    } else if (currentTimingMode === 'by_week') {
        renderWeekWiseInputs();
    }
    regenerateWeeklySchedule();
}

function computeWeeklySessions() {
    const startInput = document.getElementById('weekly_start_date');
    if (!startInput || !startInput.value) return [];

    const parts = startInput.value.split('-').map(Number);
    if (parts.length !== 3) return [];
    const startDate = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0);

    const weeksCount = parseInt(document.getElementById('weeks_count')?.value || '1', 10);
    const checkedBoxes = Array.from(document.querySelectorAll('#panel-weekly input[name="weekly_days[]"]:checked'));
    const selectedDayKeys = checkedBoxes.map(cb => cb.value);

    const targetDays = selectedDayKeys.map(k => dayIndices[k]).filter(idx => idx !== undefined);
    targetDays.sort((a, b) => a - b);

    const weeks = [];

    for (let w = 0; w < weeksCount; w++) {
        const weekBase = new Date(startDate);
        weekBase.setDate(startDate.getDate() + (w * 7));
        const currentDayOfWeek = weekBase.getDay();
        const sundayOfWeek = new Date(weekBase);
        sundayOfWeek.setDate(weekBase.getDate() - currentDayOfWeek);

        const weekSessions = [];

        for (let dIdx of targetDays) {
            const candDate = new Date(sundayOfWeek);
            candDate.setDate(sundayOfWeek.getDate() + dIdx);

            if (candDate >= startDate) {
                const dayKey = dayKeyFromIndex[dIdx];
                weekSessions.push({
                    dateObj: candDate,
                    isoDate: formatISODate(candDate),
                    dayKey: dayKey,
                    dayNameBn: dayNamesBn[dayKey],
                    formattedDateBn: formatBnDate(candDate),
                    weekIndex: w,
                });
            }
        }

        if (weekSessions.length > 0) {
            weeks.push({
                weekIndex: w,
                weekNum: w + 1,
                sessions: weekSessions
            });
        }
    }
    return weeks;
}

function buildTaskOptions(selectedId) {
    let options = '<option value="">-- কাজের ধরন নির্বাচন করুন --</option>';
    taskTemplatesList.forEach(t => {
        const isSel = (String(t.id) === String(selectedId)) ? 'selected' : '';
        options += `<option value="${t.id}" ${isSel}>${t.name} (${t.task_code}) — ${t.category}</option>`;
    });
    return options;
}

function regenerateWeeklySchedule(forceReset = false) {
    const container = document.getElementById('weekly-schedule-groups-container');
    if (!container) return;

    const existingValues = {};
    if (!forceReset) {
        document.querySelectorAll('#weekly-schedule-groups-container .week-session-row').forEach(row => {
            const date = row.dataset.date;
            if (date) {
                existingValues[date] = {
                    templateId: row.querySelector('.session-template-select')?.value,
                    startTime: row.querySelector('.session-start-time')?.value,
                    endTime: row.querySelector('.session-end-time')?.value,
                    priority: row.querySelector('.session-priority-select')?.value,
                    notes: row.querySelector('.session-notes-input')?.value,
                };
            }
        });
    }

    const defaultTemplateId = document.getElementById('weekly_default_template_id')?.value || (taskTemplatesList.length > 0 ? taskTemplatesList[0].id : '');
    const defaultPriority = document.getElementById('weekly_default_priority')?.value || 'normal';
    const defaultNotes = document.getElementById('weekly_default_notes')?.value || '';

    const defSelect = document.getElementById('weekly_default_template_id');
    if (defSelect && !defSelect.value && defaultTemplateId) {
        defSelect.value = defaultTemplateId;
    }

    const weeks = computeWeeklySessions();

    if (weeks.length === 0) {
        container.innerHTML = `
            <div style="background:#fef2f2;border:1px dashed #fca5a5;padding:16px;border-radius:8px;text-align:center;color:#991b1b;font-size:13px;">
                ⚠️ কোনো সেশন পাওয়া যায়নি। অনুগ্রহ করে শুরুর তারিখ, সপ্তাহ সংখ্যা এবং অন্তত একটি দিন নির্বাচন করুন।
            </div>
        `;
        calculateSummary();
        return;
    }

    let globalIndex = 0;
    let html = '';

    weeks.forEach(week => {
        const weekNumBn = toBnDigits(week.weekNum);
        const sessionsCountBn = toBnDigits(week.sessions.length);

        html += `
            <div class="week-group-card" data-week="${week.weekIndex}">
                <div class="week-group-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="row-badge" style="background:#08783f;color:#ffffff;padding:3px 10px;font-size:12px;">সপ্তাহ ${weekNumBn}</span>
                        <span style="font-size:13px;font-weight:600;color:#1e3a29;">(মোট ${sessionsCountBn}টি সেশন)</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <button type="button" class="btn soft" style="font-size:11.5px;padding:3px 8px;" onclick="promptSetWeekTime(${week.weekIndex})">
                            ⏱ এই সপ্তাহের সময় দ্রুত পরিবর্তন
                        </button>
                    </div>
                </div>

                <div class="week-sessions-table" style="background:#fff;">
                    <div style="display:grid;grid-template-columns:170px 1.5fr 110px 110px 110px 40px;gap:10px;padding:8px 14px;background:#f8faf9;border-bottom:1px solid #e0eae4;font-size:11.5px;font-weight:700;color:#3d5a49;">
                        <div>তারিখ ও বার</div>
                        <div>কাজের ধরন (Template)</div>
                        <div>শুরুর সময়</div>
                        <div>শেষ সময়</div>
                        <div>অগ্রাধিকার</div>
                        <div style="text-align:right;">বাদ</div>
                    </div>

                    <div class="week-sessions-body">
        `;

        week.sessions.forEach(session => {
            let startTime = '09:00';
            let endTime = '13:00';

            if (currentTimingMode === 'same') {
                startTime = document.getElementById('base_weekly_start_time')?.value || '09:00';
                endTime = document.getElementById('base_weekly_end_time')?.value || '13:00';
            } else if (currentTimingMode === 'by_day') {
                startTime = document.getElementById(`time_day_${session.dayKey}_start`)?.value || '09:00';
                endTime = document.getElementById(`time_day_${session.dayKey}_end`)?.value || '13:00';
            } else if (currentTimingMode === 'by_week') {
                startTime = document.getElementById(`time_week_${session.weekIndex}_start`)?.value || '09:00';
                endTime = document.getElementById(`time_week_${session.weekIndex}_end`)?.value || '13:00';
            }

            let templateId = defaultTemplateId;
            let priority = defaultPriority;
            let notes = defaultNotes;

            if (!forceReset && existingValues[session.isoDate]) {
                const prev = existingValues[session.isoDate];
                if (prev.startTime) startTime = prev.startTime;
                if (prev.endTime !== undefined) endTime = prev.endTime;
                if (prev.templateId) templateId = prev.templateId;
                if (prev.priority) priority = prev.priority;
                if (prev.notes !== undefined) notes = prev.notes;
            }

            html += `
                <div class="week-session-row" id="session-row-${globalIndex}" data-date="${session.isoDate}" data-day="${session.dayKey}" data-week="${session.weekIndex}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][scheduled_date]" value="${session.isoDate}">
                    <input type="hidden" class="session-notes-input" name="custom_schedule[${globalIndex}][notes]" value="${notes}">

                    <div>
                        <span class="badge" style="background:#eaf8ef;color:#08783f;font-weight:700;font-size:11.5px;margin-bottom:2px;display:inline-block;">${session.dayNameBn}</span>
                        <div style="font-size:12px;font-weight:600;color:#1e293b;">${session.formattedDateBn}</div>
                        <div style="font-size:10.5px;color:var(--muted);">${session.isoDate}</div>
                    </div>

                    <div>
                        <select class="input session-template-select" name="custom_schedule[${globalIndex}][task_template_id]" style="font-size:12px;padding:6px 8px;width:100%;" required>
                            ${buildTaskOptions(templateId)}
                        </select>
                    </div>

                    <div>
                        <input class="input session-start-time" type="time" name="custom_schedule[${globalIndex}][start_time]" value="${startTime}" style="font-size:12px;padding:5px 7px;width:100%;" required onchange="calculateSummary()">
                    </div>

                    <div>
                        <input class="input session-end-time" type="time" name="custom_schedule[${globalIndex}][end_time]" value="${endTime}" style="font-size:12px;padding:5px 7px;width:100%;" onchange="calculateSummary()">
                    </div>

                    <div>
                        <select class="input session-priority-select" name="custom_schedule[${globalIndex}][priority]" style="font-size:12px;padding:6px 8px;width:100%;">
                            <option value="normal" ${priority === 'normal' ? 'selected' : ''}>সাধারণ</option>
                            <option value="important" ${priority === 'important' ? 'selected' : ''}>গুরুত্বপূর্ণ</option>
                            <option value="urgent" ${priority === 'urgent' ? 'selected' : ''}>জরুরি</option>
                        </select>
                    </div>

                    <div style="text-align:right;">
                        <button type="button" class="btn-remove-session" title="এই সেশনটি বাদ দিন (Skip)" onclick="removeSessionRow(${globalIndex})">
                            ✕
                        </button>
                    </div>
                </div>
            `;
            globalIndex++;
        });

        html += `
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateFallbackInputs();
    calculateSummary();
}

function removeSessionRow(index) {
    const row = document.getElementById(`session-row-${index}`);
    if (!row) return;

    const parentGroup = row.closest('.week-group-card');
    row.remove();

    if (parentGroup && parentGroup.querySelectorAll('.week-session-row').length === 0) {
        parentGroup.remove();
    }

    updateFallbackInputs();
    calculateSummary();
}

function updateFallbackInputs() {
    const fallbackContainer = document.getElementById('weekly-fallback-inputs');
    if (!fallbackContainer) return;

    const defaultTemplateId = document.getElementById('weekly_default_template_id')?.value || (taskTemplatesList.length > 0 ? taskTemplatesList[0].id : '');
    const startTime = document.getElementById('base_weekly_start_time')?.value || '09:00';
    const endTime = document.getElementById('base_weekly_end_time')?.value || '13:00';

    fallbackContainer.innerHTML = `
        <input type="hidden" name="weekly_tasks[0][task_template_id]" value="${defaultTemplateId}">
        <input type="hidden" name="weekly_tasks[0][start_time]" value="${startTime}">
        <input type="hidden" name="weekly_tasks[0][end_time]" value="${endTime}">
        <input type="hidden" name="weekly_tasks[0][priority]" value="normal">
    `;
}

function addWeeklyTaskRow() {
    // Kept for backward compatibility
    regenerateWeeklySchedule();
}

function addMonthlyTaskRow() {
    const container = document.getElementById('monthly-tasks-container');
    const index = container.querySelectorAll('.task-repeater-row').length;
    const rowHtml = `
        <div class="task-repeater-row" data-index="${index}">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span class="row-badge">কাজ #${index + 1}</span>
                <button type="button" class="btn-remove-row" onclick="removeTaskRow(this)">✕ সরান</button>
            </div>
            <div class="form-grid">
                <div class="field full">
                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                    <select class="input" name="monthly_tasks[${index}][task_template_id]" required>
                        ${taskTemplatesOptions}
                    </select>
                </div>
                <div>
                    <label class="label">শুরুর সময় <span class="req">*</span></label>
                    <input class="input" type="time" name="monthly_tasks[${index}][start_time]" value="10:00" required>
                </div>
                <div>
                    <label class="label">শেষ সময়</label>
                    <input class="input" type="time" name="monthly_tasks[${index}][end_time]" value="14:00">
                </div>
                <div>
                    <label class="label">অগ্রাধিকার</label>
                    <select class="input" name="monthly_tasks[${index}][priority]">
                        <option value="normal">সাধারণ (Normal)</option>
                        <option value="important">গুরুত্বপূর্ণ (Important)</option>
                        <option value="urgent">জরুরি (Urgent)</option>
                    </select>
                </div>
                <div>
                    <label class="label">নোট (ঐচ্ছিক)</label>
                    <input class="input" type="text" name="monthly_tasks[${index}][notes]">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', rowHtml);
    updateRowBadges(container);
    calculateSummary();
}

function removeTaskRow(btn) {
    const row = btn.closest('.task-repeater-row');
    const container = row.parentElement;
    row.remove();
    updateRowBadges(container);
    calculateSummary();
}

function updateRowBadges(container) {
    const rows = container.querySelectorAll('.task-repeater-row');
    rows.forEach((row, i) => {
        const badge = row.querySelector('.row-badge');
        if (badge) badge.innerText = `কাজ #${i + 1}`;
        const removeBtn = row.querySelector('.btn-remove-row');
        if (removeBtn) {
            removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        }
    });
}

function calculateSummary() {
    const titleElem = document.getElementById('summary-title-text');
    const bodyElem = document.getElementById('summary-body-text');

    if (currentRecurrence === 'once') {
        const dateInput = document.getElementById('scheduled_date');
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');
        const dateVal = dateInput?.value || 'আজ';
        const startVal = startInput?.value || '০৮:০০';
        const endVal = endInput?.value || '২২:০০';

        titleElem.innerText = `এককালীন কাজের বরাদ্দ সারসংক্ষেপ`;
        bodyElem.innerHTML = `📅 নির্ধারিত তারিখ: <b>${dateVal}</b> | ⏱ সময়: <b>${startVal} হতে ${endVal}</b> | <b>সর্বমোট ১টি কাজ</b> তৈরি হবে।`;
        return;
    }

    if (currentRecurrence === 'daily') {
        const startInput = document.getElementById('daily_start_date');
        const endInput = document.getElementById('daily_end_date');
        const tasksCount = document.querySelectorAll('#daily-tasks-container .task-repeater-row').length || 1;

        if (startInput?.value && endInput?.value) {
            const d1 = new Date(startInput.value);
            const d2 = new Date(endInput.value);
            const diffTime = d2 - d1;
            const diffDays = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1);
            const totalTasks = diffDays * tasksCount;

            titleElem.innerText = `দৈনিক কাজের শিডিউল সারসংক্ষেপ`;
            bodyElem.innerHTML = `📅 সময়কাল: <b>${startInput.value}</b> থেকে <b>${endInput.value}</b> (মোট <b>${diffDays}</b> দিন)<br>` +
                                 `📋 প্রতিদিন কাজ: <b>${tasksCount}</b> টি | 🌟 সর্বমোট <b>${totalTasks}</b> টি কাজের অ্যাসাইনমেন্ট তৈরি হবে।`;
        }
        return;
    }

    if (currentRecurrence === 'weekly') {
        const sessionRows = document.querySelectorAll('#weekly-schedule-groups-container .week-session-row');
        const totalSessions = sessionRows.length;
        const weeksCount = parseInt(document.getElementById('weeks_count')?.value || '1', 10);
        const checkedDays = document.querySelectorAll('#panel-weekly input[name="weekly_days[]"]:checked').length;

        titleElem.innerText = `সাপ্তাহিক কাজের কাস্টম শিডিউল সারসংক্ষেপ`;
        bodyElem.innerHTML = `🗓 সময়কাল: মোট <b>${toBnDigits(weeksCount)}</b> সপ্তাহ | প্রতি সপ্তাহে <b>${toBnDigits(checkedDays)}</b> দিন নির্বাচন করা হয়েছে<br>` +
                             `🌟 সর্বমোট <b>${toBnDigits(totalSessions)}</b> টি নির্ধারিত কাজের সেশন প্রস্তুত রয়েছে।`;
        return;
    }

    if (currentRecurrence === 'monthly') {
        const monthsCount = parseInt(document.getElementById('months_count')?.value || '1', 10);
        const checkedDays = document.querySelectorAll('#panel-monthly input[name="monthly_days[]"]:checked').length;
        const tasksCount = document.querySelectorAll('#monthly-tasks-container .task-repeater-row').length || 1;
        const totalOccurrences = monthsCount * checkedDays * tasksCount;

        titleElem.innerText = `মাসিক কাজের শিডিউল সারসংক্ষেপ`;
        bodyElem.innerHTML = `📆 সময়কাল: মোট <b>${monthsCount}</b> মাস | প্রতি মাসে <b>${checkedDays}</b> দিন<br>` +
                             `📋 প্রতিটি তারিখে কাজ: <b>${tasksCount}</b> টি | 🌟 সর্বমোট <b>${totalOccurrences}</b> টি কাজের অ্যাসাইনমেন্ট তৈরি হবে।`;
        return;
    }
}

// Monthly Days Handlers
function toBnDigits(num) {
    const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return String(num).replace(/[0-9]/g, d => bn[d]);
}

function parseBnOrEnInt(str) {
    if (!str) return NaN;
    const bnMap = {'০':'0','১':'1','২':'2','৩':'3','৪':'4','৫':'5','৬':'6','৭':'7','৮':'8','৯':'9'};
    const normalized = String(str).trim().replace(/[০-৯]/g, d => bnMap[d]);
    return parseInt(normalized, 10);
}

function onMonthlyDayChange() {
    let has31 = false;
    document.querySelectorAll('#panel-monthly .month-day-cell').forEach(cell => {
        const input = cell.querySelector('input');
        if (input) {
            if (input.checked) {
                cell.classList.add('selected');
                if (input.value === '31') has31 = true;
            } else {
                cell.classList.remove('selected');
            }
        }
    });

    const lastDayCell = document.getElementById('cell-day-last');
    if (lastDayCell) {
        if (has31) {
            lastDayCell.classList.add('selected');
        } else {
            lastDayCell.classList.remove('selected');
        }
    }

    updateMonthlyDaysBadge();
    calculateSummary();
}

function toggleDay31() {
    const input31 = document.querySelector('#panel-monthly input[name="monthly_days[]"][value="31"]');
    if (input31) {
        input31.checked = !input31.checked;
        onMonthlyDayChange();
    }
}

function updateMonthlyDaysBadge() {
    const checked = Array.from(document.querySelectorAll('#panel-monthly input[name="monthly_days[]"]:checked'))
        .map(i => parseInt(i.value, 10))
        .filter((v, i, a) => a.indexOf(v) === i)
        .sort((a, b) => a - b);

    const badge = document.getElementById('selected-days-badge');
    if (!badge) return;

    if (checked.length === 0) {
        badge.innerText = 'কোনো দিন নির্বাচিত নেই';
        badge.style.color = '#dc2626';
        badge.style.background = '#fef2f2';
        badge.style.borderColor = '#fecaca';
    } else {
        const daysStr = checked.map(d => d === 31 ? 'মাসের শেষ দিন (৩১)' : `${toBnDigits(d)} তারিখ`).join(', ');
        badge.innerText = `নির্বাচিত: ${daysStr} (মোট ${toBnDigits(checked.length)} দিন)`;
        badge.style.color = '#08783f';
        badge.style.background = '#edfcf2';
        badge.style.borderColor = '#bbf7d0';
    }
}

function setMonthlyPreset(days) {
    document.querySelectorAll('#panel-monthly input[name="monthly_days[]"]').forEach(input => {
        const val = parseInt(input.value, 10);
        input.checked = days.includes(val);
    });
    onMonthlyDayChange();
}

function clearMonthlyDays() {
    document.querySelectorAll('#panel-monthly input[name="monthly_days[]"]').forEach(input => {
        input.checked = false;
    });
    onMonthlyDayChange();
}

function addCustomMonthDay() {
    const input = document.getElementById('custom_month_day_input');
    if (!input || !input.value.trim()) return;

    const val = parseBnOrEnInt(input.value);
    if (isNaN(val) || val < 1 || val > 31) {
        alert('অনুগ্রহ করে ১ থেকে ৩১ এর মধ্যে একটি সঠিক দিন প্রদান করুন।');
        return;
    }

    const targetInput = document.querySelector(`#panel-monthly input[name="monthly_days[]"][value="${val}"]`);
    if (targetInput) {
        targetInput.checked = true;
    }

    input.value = '';
    onMonthlyDayChange();
}

// Preset helpers
function formatTimeToBanglaPeriod(timeStr) {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    if (parts.length < 2) return timeStr;
    let hour = parseInt(parts[0], 10);
    const minute = parts[1];
    let period = 'রাত';
    if (hour >= 5 && hour < 12) period = 'সকাল';
    else if (hour >= 12 && hour < 15) period = 'দুপুর';
    else if (hour >= 15 && hour < 18) period = 'বিকাল';
    else if (hour >= 18 && hour < 20) period = 'সন্ধ্যা';

    const ampm = hour >= 12 ? 'PM' : 'AM';
    let displayHour = hour % 12;
    if (displayHour === 0) displayHour = 12;
    const padHour = String(displayHour).padStart(2, '0');
    return `${period} ${padHour}:${minute} ${ampm}`;
}

function updateTimePreview() {
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const badge = document.getElementById('time-preview-badge');

    const startFormatted = formatTimeToBanglaPeriod(startInput?.value);
    const endFormatted = formatTimeToBanglaPeriod(endInput?.value);

    if (badge) {
        if (startFormatted && endFormatted) {
            badge.innerHTML = `⏱ নির্ধারিত সময়সূচী: <b>${startFormatted}</b> থেকে <b>${endFormatted}</b>`;
            badge.style.display = 'inline-block';
        } else if (startFormatted) {
            badge.innerHTML = `⏱ নির্ধারিত সময়সূচী: <b>${startFormatted}</b>`;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
    calculateSummary();
}

function setTimePreset(start, end) {
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    if (startInput) startInput.value = start;
    if (endInput) endInput.value = end;
    updateTimePreview();
}

function setFromNowPreset() {
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const startHour = pad(now.getHours());
    const startMin = pad(now.getMinutes());
    now.setHours(now.getHours() + 4);
    const endHour = pad(now.getHours());
    const endMin = pad(now.getMinutes());

    if (startInput) startInput.value = `${startHour}:${startMin}`;
    if (endInput) endInput.value = `${endHour}:${endMin}`;
    updateTimePreview();
}

document.addEventListener('DOMContentLoaded', function() {
    switchRecurrence('{{ $activeRecurrence }}');
    onMonthlyDayChange();
    if ('{{ $activeRecurrence }}' === 'weekly') {
        regenerateWeeklySchedule();
    }

    // Attach listeners for inputs that affect calculations
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[type="date"], input[type="time"], select')) {
            calculateSummary();
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('input[type="checkbox"], input[type="date"], select')) {
            calculateSummary();
        }
    });

    updateTimePreview();
});
</script>
@endsection
