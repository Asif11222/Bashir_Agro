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
                                        <div>
                                            <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                            <select class="input" name="daily_tasks[0][task_template_id]">
                                                <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                                @foreach($tasks as $task)
                                                    <option value="{{ $task->id }}">{{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">ফার্ম লোকেশন (ঐচ্ছিক)</label>
                                            <select class="input" name="daily_tasks[0][farm_location_id]">
                                                <option value="">-- মূল লোকেশন (ডিফল্ট) --</option>
                                                @foreach($locations as $loc)
                                                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->location_code }}) — {{ $loc->type }}</option>
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
                        <!-- Sub-step 1: সময়কাল ও সপ্তাহের সংখ্যা (Duration & Number of Weeks) -->
                        <div class="config-subcard">
                            <b class="subcard-title" style="color:#14241d;">🗓 ১. সময়কাল ও সপ্তাহের সংখ্যা</b>
                            <div class="form-grid" style="margin-top:10px;">
                                <div>
                                    <label class="label">শুরুর তারিখ <span class="req">*</span></label>
                                    <input class="input" type="date" name="start_date" id="weekly_start_date" value="{{ old('start_date', date('Y-m-d')) }}" onchange="onWeeklyDurationOrStartChange()">
                                </div>
                                <div>
                                    <label class="label">কত সপ্তাহ চলবে? <span class="req">*</span></label>
                                    <select class="input" name="weeks_count" id="weeks_count" onchange="onWeeklyDurationOrStartChange()">
                                        <option value="1">১ সপ্তাহ (১টি সপ্তাহ)</option>
                                        <option value="2">২ সপ্তাহ (২টি সপ্তাহ)</option>
                                        <option value="3">৩ সপ্তাহ (৩টি সপ্তাহ)</option>
                                        <option value="4" selected>৪ সপ্তাহ (১ মাস)</option>
                                        <option value="6">৬ সপ্তাহ (দেড় মাস)</option>
                                        <option value="8">৮ সপ্তাহ (২ মাস)</option>
                                        <option value="12">১২ সপ্তাহ (৩ মাস)</option>
                                        <option value="24">২৪ সপ্তাহ (৬ মাস)</option>
                                    </select>
                                </div>
                                <div class="field full" style="font-size:12px;color:var(--muted);background:#f0fdf4;border:1px solid #bbf7d0;padding:8px 12px;border-radius:8px;">
                                    💡 <b>নোট:</b> নির্বাচিত শুরুর তারিখ থেকে সপ্তাহের চক্র শুরু হবে। প্রতি সপ্তাহের দিনগুলো স্বাধীনভাবে নির্বাচন ও কাজের সময় নির্ধারণ করা যাবে।
                                </div>
                            </div>
                        </div>

                        <!-- Sub-step 2: রুটিন কাজের তালিকা (Repetitive Tasks Pool) -->
                        <div class="config-subcard" style="margin-top:14px;background:#ffffff;border:1.5px solid #d1fae5;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                                <div>
                                    <b class="subcard-title" style="color:#166534;font-size:14px;">📋 ২. রুটিন কাজের পুল (Repetitive Tasks Pool)</b>
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">
                                        প্রতি সপ্তাহে যে কাজগুলো পুনরাবৃত্তি হবে তা এখানে সংজ্ঞায়িত করুন (যেমন: কাজ ১: গাছে পানি, কাজ ২: পুকুরে খাদ্য ও পানি)। প্রয়োজনে যেকোনো কাজ যোগ বা বাদ দিতে পারেন।
                                    </div>
                                </div>
                                <button type="button" class="btn soft" style="font-size:12px;padding:6px 12px;background:#eaf8ef;color:#08783f;border:1px solid #86efac;font-weight:600;" onclick="addWeeklyPoolTaskRow()">
                                    + নতুন কাজ যোগ করুন
                                </button>
                            </div>

                            <div id="weekly-tasks-pool-container" style="display:flex;flex-direction:column;gap:10px;">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                        <!-- Sub-step 3: প্রতিটি সপ্তাহের স্বাধীন দিন ও সময় নির্ধারণ (Week-by-Week Setup) -->
                        <div style="margin-top:16px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                                <div>
                                    <b style="font-size:14px;color:#14241d;">🗓️ ৩. সপ্তাহভিত্তিক কাজের দিন ও সময় নির্ধারণ (Independent Week-by-Week Setup):</b>
                                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">
                                        প্রতিটি সপ্তাহের কোন কোন দিনে কাজ হবে তা টিক দিন। নির্বাচিত দিনগুলোতে পুলের কাজগুলো বসবে এবং প্রতিটি কাজের সময় ইচ্ছামতো কাস্টমাইজ করতে পারবেন।
                                    </div>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <button type="button" class="btn soft" id="btn-copy-week1-to-all" style="font-size:12px;padding:5px 10px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;" onclick="copyWeek1ToAll()">
                                        📋 সপ্তাহ ১-এর শিডিউল সব সপ্তাহে কপি করুন
                                    </button>
                                </div>
                            </div>

                            <!-- Container for dynamically rendered week cards -->
                            <div id="week-by-week-cards-container" style="display:flex;flex-direction:column;gap:14px;">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                        <!-- Sub-step 4: চূড়ান্ত শিডিউল সারাংশ ও প্রিভিউ (Final Schedule Summary & Preview) -->
                        <div style="margin-top:16px;">
                            <div class="config-subcard" style="background:#f8faf9;border:1px solid #dcebe1;">
                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                    <div>
                                        <b style="font-size:13.5px;color:#1e3a29;">📊 চূড়ান্ত সাপ্তাহিক বরাদ্দের তালিকা (Final Schedule Preview)</b>
                                        <div id="weekly-schedule-summary-badge" style="font-size:12px;color:var(--muted);margin-top:2px;">
                                            হিসাব হচ্ছে...
                                        </div>
                                    </div>
                                    <button type="button" class="btn soft" style="font-size:11.5px;padding:4px 8px;" onclick="toggleFinalScheduleTable()">
                                        👁️ সম্পূর্ণ তালিকা দেখুন / লুকান
                                    </button>
                                </div>
                                <div id="weekly-final-schedule-table-box" style="display:none;margin-top:12px;max-height:350px;overflow-y:auto;border:1px solid #e2e8e5;border-radius:8px;background:#fff;">
                                    <table style="width:100%;font-size:12px;border-collapse:collapse;text-align:left;">
                                        <thead style="background:#f1f5f3;color:#2d4a3a;position:sticky;top:0;">
                                            <tr>
                                                <th style="padding:6px 10px;">সপ্তাহ</th>
                                                <th style="padding:6px 10px;">তারিখ ও বার</th>
                                                <th style="padding:6px 10px;">কাজের ধরন</th>
                                                <th style="padding:6px 10px;">ফার্ম লোকেশন</th>
                                                <th style="padding:6px 10px;">সময়সূচী</th>
                                                <th style="padding:6px 10px;">অগ্রাধিকার</th>
                                            </tr>
                                        </thead>
                                        <tbody id="weekly-final-schedule-table-body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs container for form submission -->
                        <div id="weekly-hidden-form-inputs" style="display:none;"></div>
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
                                        <div>
                                            <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                                            <select class="input" name="monthly_tasks[0][task_template_id]">
                                                <option value="">-- কাজের ধরন নির্বাচন করুন --</option>
                                                @foreach($tasks as $task)
                                                    <option value="{{ $task->id }}">{{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label">ফার্ম লোকেশন (ঐচ্ছিক)</label>
                                            <select class="input" name="monthly_tasks[0][farm_location_id]">
                                                <option value="">-- মূল লোকেশন (ডিফল্ট) --</option>
                                                @foreach($locations as $loc)
                                                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->location_code }}) — {{ $loc->type }}</option>
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
.weekly-pool-card {
    background: #ffffff;
    border: 1px solid #d1fae5;
    border-radius: 9px;
    padding: 12px;
}
.week-card {
    background: #ffffff;
    border: 1.5px solid #dcebe1;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    transition: border-color 0.2s ease;
}
.week-card:hover {
    border-color: #86efac;
}
.week-card-header {
    background: #f5fbf7;
    border-bottom: 1px solid #dcebe1;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.week-card-body {
    padding: 14px;
}
.day-schedule-card {
    background: #fbfdfc;
    border: 1px solid #d1fae5;
    border-left: 3.5px solid #08783f;
    border-radius: 8px;
    padding: 10px 12px;
    margin-top: 10px;
}
.day-schedule-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px dashed #dcebe1;
}
.day-task-grid-row {
    display: grid;
    grid-template-columns: 1.4fr 1.2fr 95px 95px 105px 32px;
    gap: 8px;
    align-items: center;
    background: #ffffff;
    border: 1px solid #edf4f0;
    border-radius: 6px;
    padding: 6px 10px;
    margin-bottom: 6px;
}
.day-task-grid-row:last-child {
    margin-bottom: 0;
}
.day-chip.disabled,
.day-chip:has(input:disabled) {
    opacity: 0.55;
    cursor: not-allowed;
    background: #f1f5f3 !important;
    border-color: #e2e8e5 !important;
    color: #94a3b8 !important;
}
.day-date-subtext {
    font-size: 11px;
    color: var(--muted);
    font-weight: normal;
}
.day-chip.active .day-date-subtext,
.day-chip:has(input:checked) .day-date-subtext {
    color: #08783f;
    font-weight: 600;
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
    .day-task-grid-row {
        grid-template-columns: 1fr;
        gap: 6px;
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

// Available Farm Locations for dynamic row creation
const farmLocationsOptions = `
    <option value="">-- মূল লোকেশন (ডিফল্ট) --</option>
    @foreach($locations as $loc)
        <option value="{{ $loc->id }}">{{ addslashes($loc->name) }} ({{ $loc->location_code }}) — {{ addslashes($loc->type) }}</option>
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
                <div>
                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                    <select class="input" name="daily_tasks[${index}][task_template_id]" required>
                        ${taskTemplatesOptions}
                    </select>
                </div>
                <div>
                    <label class="label">ফার্ম লোকেশন (ঐচ্ছিক)</label>
                    <select class="input" name="daily_tasks[${index}][farm_location_id]">
                        ${farmLocationsOptions}
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

@php
    $tasksListForJs = $tasks->map(function($t) {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'task_code' => $t->task_code,
            'category' => $t->category,
        ];
    })->values();

    $locationsListForJs = $locations->map(function($l) {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'location_code' => $l->location_code,
            'type' => $l->type,
        ];
    })->values();
@endphp
// Task Templates & Farm Locations JSON lists for interactive schedule generator
const taskTemplatesList = {!! json_encode($tasksListForJs) !!};
const farmLocationsList = {!! json_encode($locationsListForJs) !!};

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

// Weekday constants & mappings (Bangladesh: Sat to Fri)
const dayNamesBn = {
    'sat': 'শনিবার',
    'sun': 'রবিবার',
    'mon': 'সোমবার',
    'tue': 'মঙ্গলবার',
    'wed': 'বুধবার',
    'thu': 'বৃহস্পতিবার',
    'fri': 'শুক্রবার'
};
const dayOrder = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
const dayOffsetFromSat = {
    'sat': 0, 'sun': 1, 'mon': 2, 'tue': 3, 'wed': 4, 'thu': 5, 'fri': 6
};

const bnMonthsShort = [
    'জানু', 'ফেব্রু', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
    'জুলাই', 'আগস্ট', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'
];

function formatBnShortDate(dateObj) {
    const day = toBnDigits(dateObj.getDate());
    const month = bnMonthsShort[dateObj.getMonth()];
    return `${day} ${month}`;
}

function buildTaskOptions(selectedId) {
    let options = '<option value="">-- কাজের ধরন নির্বাচন করুন --</option>';
    taskTemplatesList.forEach(t => {
        const isSel = (String(t.id) === String(selectedId)) ? 'selected' : '';
        options += `<option value="${t.id}" ${isSel}>${t.name} (${t.task_code})</option>`;
    });
    return options;
}

function buildLocationOptions(selectedId) {
    let options = '<option value="">-- মূল লোকেশন (ডিফল্ট) --</option>';
    farmLocationsList.forEach(l => {
        const isSel = (String(l.id) === String(selectedId)) ? 'selected' : '';
        options += `<option value="${l.id}" ${isSel}>${l.name} (${l.type})</option>`;
    });
    return options;
}

function getWeeklyDateRange() {
    const startInput = document.getElementById('weekly_start_date');
    if (!startInput || !startInput.value) return null;
    const parts = startInput.value.split('-').map(Number);
    if (parts.length !== 3) return null;
    const startDate = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0);

    const weeksCount = parseInt(document.getElementById('weeks_count')?.value || '4', 10);

    // Compute Saturday of Week 0 (Bangladesh work week: Sat - Fri)
    const satOffset = (startDate.getDay() + 1) % 7;
    const startSat = new Date(startDate);
    startSat.setDate(startDate.getDate() - satOffset);

    return { startDate, startSat, weeksCount };
}

let tasksPool = [];
let weeksState = [];
let weeklyInitialized = false;

function initWeeklySchedule() {
    if (weeklyInitialized) {
        syncWeeklyCustomSchedule();
        return;
    }

    const range = getWeeklyDateRange();
    const weeksCount = range ? range.weeksCount : 4;

    // Initialize tasks pool if empty
    if (tasksPool.length === 0) {
        tasksPool = [
            {
                id: 'pool_1',
                templateId: taskTemplatesList.length > 0 ? taskTemplatesList[0].id : '',
                locationId: '',
                startTime: '09:00',
                endTime: '10:00',
                priority: 'normal',
                notes: ''
            }
        ];
        if (taskTemplatesList.length > 1) {
            tasksPool.push({
                id: 'pool_2',
                templateId: taskTemplatesList[1].id,
                locationId: '',
                startTime: '10:00',
                endTime: '11:00',
                priority: 'normal',
                notes: ''
            });
        }
    }
    renderTasksPoolUI();

    // Initialize weeksState
    weeksState = [];
    for (let w = 0; w < weeksCount; w++) {
        const defaultDays = (w === 0) ? ['sun', 'mon', 'tue'] : ['sun', 'fri'];
        const dayTasks = {};
        defaultDays.forEach(dayKey => {
            dayTasks[dayKey] = tasksPool.map(pt => ({
                id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                poolId: pt.id,
                templateId: pt.templateId,
                locationId: pt.locationId,
                startTime: pt.startTime,
                endTime: pt.endTime,
                priority: pt.priority,
                notes: pt.notes
            }));
        });

        weeksState.push({
            selectedDays: defaultDays,
            dayTasks: dayTasks
        });
    }

    weeklyInitialized = true;
    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function renderTasksPoolUI() {
    const container = document.getElementById('weekly-tasks-pool-container');
    if (!container) return;

    let html = '';
    tasksPool.forEach((task, idx) => {
        html += `
            <div class="weekly-pool-card task-repeater-row" data-pool-index="${idx}">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span class="row-badge" style="background:#08783f;color:#fff;">রুটিন কাজ #${idx + 1}</span>
                    <button type="button" class="btn-remove-row" onclick="removeWeeklyPoolTaskRow(${idx})" style="${tasksPool.length > 1 ? '' : 'display:none;'}">✕ সরান</button>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="label">কাজের ধরন (Template) <span class="req">*</span></label>
                        <select class="input pool-template-select" onchange="onPoolTaskChange(${idx}, 'templateId', this.value)" required>
                            ${buildTaskOptions(task.templateId)}
                        </select>
                    </div>
                    <div>
                        <label class="label">ফার্ম লোকেশন (প্লট / পুকুর)</label>
                        <select class="input pool-location-select" onchange="onPoolTaskChange(${idx}, 'locationId', this.value)">
                            ${buildLocationOptions(task.locationId)}
                        </select>
                    </div>
                    <div>
                        <label class="label">ডিফল্ট শুরুর সময় <span class="req">*</span></label>
                        <input class="input pool-start-time" type="time" value="${task.startTime}" onchange="onPoolTaskChange(${idx}, 'startTime', this.value)" required>
                    </div>
                    <div>
                        <label class="label">ডিফল্ট শেষ সময়</label>
                        <input class="input pool-end-time" type="time" value="${task.endTime || ''}" onchange="onPoolTaskChange(${idx}, 'endTime', this.value)">
                    </div>
                    <div>
                        <label class="label">অগ্রাধিকার</label>
                        <select class="input pool-priority-select" onchange="onPoolTaskChange(${idx}, 'priority', this.value)">
                            <option value="normal" ${task.priority === 'normal' ? 'selected' : ''}>সাধারণ (Normal)</option>
                            <option value="important" ${task.priority === 'important' ? 'selected' : ''}>গুরুত্বপূর্ণ (Important)</option>
                            <option value="urgent" ${task.priority === 'urgent' ? 'selected' : ''}>জরুরি (Urgent)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">কাজের নোট (ঐচ্ছিক)</label>
                        <input class="input pool-notes-input" type="text" value="${task.notes || ''}" placeholder="যেমন: সকালের সেচ / পরিদর্শন" oninput="onPoolTaskChange(${idx}, 'notes', this.value)">
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function addWeeklyPoolTaskRow() {
    const idx = tasksPool.length;
    let defStart = '14:00';
    let defEnd = '16:00';
    if (idx === 1) { defStart = '10:00'; defEnd = '11:00'; }
    else if (idx === 2) { defStart = '11:30'; defEnd = '13:00'; }

    const newTask = {
        id: 'pool_' + Date.now() + '_' + idx,
        templateId: taskTemplatesList.length > idx ? taskTemplatesList[idx].id : (taskTemplatesList.length > 0 ? taskTemplatesList[0].id : ''),
        locationId: '',
        startTime: defStart,
        endTime: defEnd,
        priority: 'normal',
        notes: ''
    };

    tasksPool.push(newTask);
    renderTasksPoolUI();

    // Propagate the new task into all selected days
    weeksState.forEach((wObj) => {
        wObj.selectedDays.forEach(dayKey => {
            if (!wObj.dayTasks[dayKey]) wObj.dayTasks[dayKey] = [];
            wObj.dayTasks[dayKey].push({
                id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                poolId: newTask.id,
                templateId: newTask.templateId,
                locationId: newTask.locationId,
                startTime: newTask.startTime,
                endTime: newTask.endTime,
                priority: newTask.priority,
                notes: newTask.notes
            });
        });
    });

    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function removeWeeklyPoolTaskRow(index) {
    if (tasksPool.length <= 1) return;
    const removedId = tasksPool[index].id;
    tasksPool.splice(index, 1);
    renderTasksPoolUI();

    // Remove from days
    weeksState.forEach((wObj) => {
        Object.keys(wObj.dayTasks).forEach(dayKey => {
            wObj.dayTasks[dayKey] = wObj.dayTasks[dayKey].filter(t => t.poolId !== removedId);
        });
    });

    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function onPoolTaskChange(index, field, value) {
    if (!tasksPool[index]) return;
    const poolItem = tasksPool[index];
    poolItem[field] = value;

    // Propagate template / location changes to existing day tasks that originated from this pool task
    weeksState.forEach(wObj => {
        Object.keys(wObj.dayTasks).forEach(dayKey => {
            wObj.dayTasks[dayKey].forEach(t => {
                if (t.poolId === poolItem.id) {
                    if (field === 'templateId' || field === 'locationId') {
                        t[field] = value;
                    }
                }
            });
        });
    });

    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function onWeeklyDurationOrStartChange() {
    const range = getWeeklyDateRange();
    if (!range) return;

    const { weeksCount } = range;
    while (weeksState.length < weeksCount) {
        const defaultDays = ['sun', 'mon', 'tue'];
        const dayTasks = {};
        defaultDays.forEach(dayKey => {
            dayTasks[dayKey] = tasksPool.map(pt => ({
                id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                poolId: pt.id,
                templateId: pt.templateId,
                locationId: pt.locationId,
                startTime: pt.startTime,
                endTime: pt.endTime,
                priority: pt.priority,
                notes: pt.notes
            }));
        });
        weeksState.push({
            selectedDays: defaultDays,
            dayTasks: dayTasks
        });
    }

    if (weeksState.length > weeksCount) {
        weeksState = weeksState.slice(0, weeksCount);
    }

    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function renderWeekByWeekCards() {
    const container = document.getElementById('week-by-week-cards-container');
    if (!container) return;

    const range = getWeeklyDateRange();
    if (!range) return;
    const { startDate, startSat, weeksCount } = range;

    let html = '';
    for (let w = 0; w < weeksCount; w++) {
        if (!weeksState[w]) {
            weeksState[w] = { selectedDays: [], dayTasks: {} };
        }

        const weekSat = new Date(startSat);
        weekSat.setDate(startSat.getDate() + (w * 7));
        const weekFri = new Date(weekSat);
        weekFri.setDate(weekSat.getDate() + 6);
        const dateRangeBn = `${formatBnShortDate(weekSat)} — ${formatBnShortDate(weekFri)} ${toBnDigits(weekFri.getFullYear())}`;

        const isWeek1 = (w === 0);
        const copyBtnHtml = !isWeek1 ? `
            <button type="button" class="btn soft" style="font-size:11.5px;padding:3px 8px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;" onclick="copyFromPreviousWeek(${w})">
                📋 সপ্তাহ ${toBnDigits(w)} থেকে কপি করুন
            </button>
        ` : '';

        html += `
            <div class="week-card" id="week-card-${w}" data-week-index="${w}">
                <div class="week-card-header">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span class="row-badge" style="background:#08783f;color:#ffffff;padding:3px 10px;font-size:12px;">সপ্তাহ ${toBnDigits(w + 1)}</span>
                        <b style="font-size:13px;color:#1e3a29;">${dateRangeBn}</b>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        ${copyBtnHtml}
                    </div>
                </div>
                <div class="week-card-body" id="week-card-body-${w}">
                    ${renderWeekCardBodyHtml(w, startDate, weekSat)}
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

function renderWeekCardBody(w) {
    const bodyContainer = document.getElementById(`week-card-body-${w}`);
    if (!bodyContainer) {
        renderWeekByWeekCards();
        return;
    }
    const range = getWeeklyDateRange();
    if (!range) return;
    const { startDate, startSat } = range;
    const weekSat = new Date(startSat);
    weekSat.setDate(startSat.getDate() + (w * 7));

    bodyContainer.innerHTML = renderWeekCardBodyHtml(w, startDate, weekSat);
}

function renderWeekCardBodyHtml(w, startDate, weekSat) {
    const wObj = weeksState[w] || { selectedDays: [], dayTasks: {} };
    const selectedDays = wObj.selectedDays || [];

    // 1. Day chips
    let chipsHtml = `
        <div style="margin-bottom:10px;">
            <label class="label" style="margin-bottom:6px;font-size:12.5px;color:#1e3a29;">
                সপ্তাহ ${toBnDigits(w + 1)}-এর কাজের দিন নির্বাচন করুন:
            </label>
            <div class="weekday-picker">
    `;

    dayOrder.forEach(dayKey => {
        const dayOffset = dayOffsetFromSat[dayKey];
        const candDate = new Date(weekSat);
        candDate.setDate(weekSat.getDate() + dayOffset);

        const isPast = candDate < startDate;
        const isChecked = selectedDays.includes(dayKey);
        const dayName = dayNamesBn[dayKey];
        const shortDateStr = formatBnShortDate(candDate);

        chipsHtml += `
            <label class="day-chip ${isChecked ? 'active' : ''} ${isPast ? 'disabled' : ''}">
                <input type="checkbox" ${isChecked ? 'checked' : ''} ${isPast ? 'disabled' : ''} onchange="onWeekDayToggle(${w}, '${dayKey}', this.checked)">
                <span>${dayName}</span>
                <span class="day-date-subtext">(${isPast ? 'শুরুর পূর্বে' : shortDateStr})</span>
            </label>
        `;
    });

    chipsHtml += `
            </div>
        </div>
    `;

    // 2. Schedule Boxes for each checked day
    let schedulesHtml = '';
    if (selectedDays.length === 0) {
        schedulesHtml = `
            <div style="background:#f8faf9;border:1px dashed #c9d8cf;padding:12px;border-radius:8px;text-align:center;color:var(--muted);font-size:12.5px;">
                এই সপ্তাহের কোনো দিন নির্বাচিত নেই। উপরে কাঙ্ক্ষিত দিনে টিক দিন।
            </div>
        `;
    } else {
        selectedDays.forEach(dayKey => {
            const dayOffset = dayOffsetFromSat[dayKey];
            const candDate = new Date(weekSat);
            candDate.setDate(weekSat.getDate() + dayOffset);
            const isoDate = formatISODate(candDate);
            const formattedFullDate = formatBnDate(candDate);
            const dayName = dayNamesBn[dayKey];

            const tasks = wObj.dayTasks[dayKey] || [];

            let taskRowsHtml = '';
            tasks.forEach((t, tIdx) => {
                taskRowsHtml += `
                    <div class="day-task-grid-row" data-task-index="${tIdx}">
                        <div>
                            <select class="input" style="font-size:12px;padding:4px 6px;width:100%;" onchange="onDayTaskFieldChange(${w}, '${dayKey}', ${tIdx}, 'templateId', this.value)" required>
                                ${buildTaskOptions(t.templateId)}
                            </select>
                        </div>
                        <div>
                            <select class="input" style="font-size:12px;padding:4px 6px;width:100%;" onchange="onDayTaskFieldChange(${w}, '${dayKey}', ${tIdx}, 'locationId', this.value)">
                                ${buildLocationOptions(t.locationId)}
                            </select>
                        </div>
                        <div>
                            <input type="time" class="input" style="font-size:12px;padding:4px 6px;width:100%;" value="${t.startTime}" onchange="onDayTaskFieldChange(${w}, '${dayKey}', ${tIdx}, 'startTime', this.value)" required>
                        </div>
                        <div>
                            <input type="time" class="input" style="font-size:12px;padding:4px 6px;width:100%;" value="${t.endTime || ''}" onchange="onDayTaskFieldChange(${w}, '${dayKey}', ${tIdx}, 'endTime', this.value)">
                        </div>
                        <div>
                            <select class="input" style="font-size:12px;padding:4px 6px;width:100%;" onchange="onDayTaskFieldChange(${w}, '${dayKey}', ${tIdx}, 'priority', this.value)">
                                <option value="normal" ${t.priority === 'normal' ? 'selected' : ''}>সাধারণ</option>
                                <option value="important" ${t.priority === 'important' ? 'selected' : ''}>গুরুত্বপূর্ণ</option>
                                <option value="urgent" ${t.priority === 'urgent' ? 'selected' : ''}>জরুরি</option>
                            </select>
                        </div>
                        <div style="text-align:right;">
                            <button type="button" class="btn-remove-session" title="এই কাজ বাদ দিন" onclick="removeTaskFromWeekDay(${w}, '${dayKey}', ${tIdx})">✕</button>
                        </div>
                    </div>
                `;
            });

            schedulesHtml += `
                <div class="day-schedule-card">
                    <div class="day-schedule-header">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="badge" style="background:#08783f;color:#fff;font-size:11.5px;padding:3px 8px;">🗓 ${dayName}</span>
                            <b style="font-size:12.5px;color:#1e293b;">${formattedFullDate}</b>
                            <span style="font-size:11px;color:var(--muted);">(${isoDate})</span>
                        </div>
                        <button type="button" class="btn soft" style="font-size:11px;padding:2px 8px;color:#08783f;border:1px solid #86efac;" onclick="addExtraTaskToWeekDay(${w}, '${dayKey}')">
                            + অতিরিক্ত কাজ যোগ করুন
                        </button>
                    </div>

                    <div style="display:flex;flex-direction:column;">
                        ${taskRowsHtml}
                    </div>
                </div>
            `;
        });
    }

    return chipsHtml + schedulesHtml;
}

function onWeekDayToggle(w, dayKey, isChecked) {
    if (!weeksState[w]) {
        weeksState[w] = { selectedDays: [], dayTasks: {} };
    }

    if (isChecked) {
        if (!weeksState[w].selectedDays.includes(dayKey)) {
            weeksState[w].selectedDays.push(dayKey);
            weeksState[w].selectedDays.sort((a, b) => dayOffsetFromSat[a] - dayOffsetFromSat[b]);
        }
        if (!weeksState[w].dayTasks[dayKey] || weeksState[w].dayTasks[dayKey].length === 0) {
            weeksState[w].dayTasks[dayKey] = tasksPool.map(pt => ({
                id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                poolId: pt.id,
                templateId: pt.templateId,
                locationId: pt.locationId,
                startTime: pt.startTime,
                endTime: pt.endTime,
                priority: pt.priority,
                notes: pt.notes
            }));
        }
    } else {
        weeksState[w].selectedDays = weeksState[w].selectedDays.filter(d => d !== dayKey);
    }

    renderWeekCardBody(w);
    syncWeeklyCustomSchedule();
}

function onDayTaskFieldChange(w, dayKey, tIdx, field, value) {
    if (weeksState[w] && weeksState[w].dayTasks[dayKey] && weeksState[w].dayTasks[dayKey][tIdx]) {
        weeksState[w].dayTasks[dayKey][tIdx][field] = value;
        syncWeeklyCustomSchedule();
    }
}

function removeTaskFromWeekDay(w, dayKey, tIdx) {
    if (weeksState[w] && weeksState[w].dayTasks[dayKey]) {
        weeksState[w].dayTasks[dayKey].splice(tIdx, 1);
        if (weeksState[w].dayTasks[dayKey].length === 0) {
            onWeekDayToggle(w, dayKey, false);
            return;
        }
        renderWeekCardBody(w);
        syncWeeklyCustomSchedule();
    }
}

function addExtraTaskToWeekDay(w, dayKey) {
    if (!weeksState[w]) return;
    if (!weeksState[w].dayTasks[dayKey]) weeksState[w].dayTasks[dayKey] = [];

    const count = weeksState[w].dayTasks[dayKey].length;
    let defStart = '14:00';
    let defEnd = '16:00';
    if (count === 1) { defStart = '10:00'; defEnd = '11:00'; }
    else if (count === 2) { defStart = '11:30'; defEnd = '13:00'; }

    const defTpl = taskTemplatesList.length > count ? taskTemplatesList[count].id : (taskTemplatesList.length > 0 ? taskTemplatesList[0].id : '');

    weeksState[w].dayTasks[dayKey].push({
        id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
        poolId: null,
        templateId: defTpl,
        locationId: '',
        startTime: defStart,
        endTime: defEnd,
        priority: 'normal',
        notes: ''
    });

    renderWeekCardBody(w);
    syncWeeklyCustomSchedule();
}

function copyFromPreviousWeek(w) {
    if (w <= 0 || !weeksState[w - 1]) return;
    const prev = weeksState[w - 1];

    weeksState[w] = {
        selectedDays: [...prev.selectedDays],
        dayTasks: JSON.parse(JSON.stringify(prev.dayTasks))
    };

    renderWeekCardBody(w);
    syncWeeklyCustomSchedule();
}

function copyWeek1ToAll() {
    if (!weeksState[0]) return;
    const week0 = weeksState[0];
    const range = getWeeklyDateRange();
    if (!range) return;

    for (let w = 1; w < range.weeksCount; w++) {
        weeksState[w] = {
            selectedDays: [...week0.selectedDays],
            dayTasks: JSON.parse(JSON.stringify(week0.dayTasks))
        };
    }

    renderWeekByWeekCards();
    syncWeeklyCustomSchedule();
}

function toggleFinalScheduleTable() {
    const box = document.getElementById('weekly-final-schedule-table-box');
    if (box) {
        box.style.display = (box.style.display === 'none') ? 'block' : 'none';
    }
}

function regenerateWeeklySchedule() {
    initWeeklySchedule();
}

function syncWeeklyCustomSchedule() {
    const hiddenContainer = document.getElementById('weekly-hidden-form-inputs');
    const summaryBadge = document.getElementById('weekly-schedule-summary-badge');
    const tableBody = document.getElementById('weekly-final-schedule-table-body');
    const range = getWeeklyDateRange();
    if (!range) return;

    const { startDate, startSat, weeksCount } = range;
    let globalIndex = 0;
    let hiddenHtml = '';
    let tableHtml = '';
    const uniqueDaysSet = new Set();
    let totalWorkDays = 0;

    for (let w = 0; w < weeksCount; w++) {
        if (!weeksState[w]) continue;
        const weekSat = new Date(startSat);
        weekSat.setDate(startSat.getDate() + (w * 7));

        const selectedDays = weeksState[w].selectedDays || [];
        totalWorkDays += selectedDays.length;

        selectedDays.forEach(dayKey => {
            uniqueDaysSet.add(dayKey);
            const dayOffset = dayOffsetFromSat[dayKey];
            const candDate = new Date(weekSat);
            candDate.setDate(weekSat.getDate() + dayOffset);

            if (candDate < startDate) return; // skip past dates

            const isoDate = formatISODate(candDate);
            const dayNameBn = dayNamesBn[dayKey] || dayKey;

            const dayTasks = weeksState[w].dayTasks[dayKey] || [];
            dayTasks.forEach(task => {
                if (!task.templateId || !task.startTime) return;

                const tplObj = taskTemplatesList.find(t => String(t.id) === String(task.templateId));
                const locObj = farmLocationsList.find(l => String(l.id) === String(task.locationId));
                const tplName = tplObj ? `${tplObj.name} (${tplObj.task_code})` : 'চিহ্নিত কাজ';
                const locName = locObj ? `${locObj.name} (${locObj.type})` : 'মূল লোকেশন';

                hiddenHtml += `
                    <input type="hidden" name="custom_schedule[${globalIndex}][scheduled_date]" value="${isoDate}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][task_template_id]" value="${task.templateId}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][farm_location_id]" value="${task.locationId || ''}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][start_time]" value="${task.startTime}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][end_time]" value="${task.endTime || ''}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][priority]" value="${task.priority || 'normal'}">
                    <input type="hidden" name="custom_schedule[${globalIndex}][notes]" value="${task.notes || ''}">
                `;

                tableHtml += `
                    <tr style="border-bottom:1px solid #edf4f0;">
                        <td style="padding:6px 10px;font-weight:600;color:#08783f;">সপ্তাহ ${toBnDigits(w + 1)}</td>
                        <td style="padding:6px 10px;"><b>${dayNameBn}</b> (${isoDate})</td>
                        <td style="padding:6px 10px;">${tplName}</td>
                        <td style="padding:6px 10px;color:var(--muted);">${locName}</td>
                        <td style="padding:6px 10px;font-weight:600;">${task.startTime} - ${task.endTime || '--:--'}</td>
                        <td style="padding:6px 10px;"><span class="badge" style="font-size:10px;">${task.priority}</span></td>
                    </tr>
                `;

                globalIndex++;
            });
        });
    }

    // Also append unique weekly_days[] inputs for compatibility
    uniqueDaysSet.forEach(d => {
        hiddenHtml += `<input type="hidden" name="weekly_days[]" value="${d}">`;
    });

    if (hiddenContainer) hiddenContainer.innerHTML = hiddenHtml;
    if (tableBody) tableBody.innerHTML = tableHtml || '<tr><td colspan="6" style="padding:14px;text-align:center;color:var(--muted);">কোনো কাজ বা দিন নির্ধারিত হয়নি</td></tr>';

    const summaryText = `🗓 সময়কাল: মোট <b>${toBnDigits(weeksCount)}</b> সপ্তাহ | মোট <b>${toBnDigits(totalWorkDays)}</b> দিন কাজ | সর্বমোট <b>${toBnDigits(globalIndex)}</b> টি কাজের সেশন প্রস্তুত`;
    if (summaryBadge) summaryBadge.innerHTML = summaryText;

    if (currentRecurrence === 'weekly') {
        const titleElem = document.getElementById('summary-title-text');
        const bodyElem = document.getElementById('summary-body-text');
        if (titleElem) titleElem.innerText = `সাপ্তাহিক কাজের কাস্টম শিডিউল সারসংক্ষেপ`;
        if (bodyElem) {
            bodyElem.innerHTML = summaryText + `<br><span style="font-size:12px;color:var(--muted)">প্রতিটি সপ্তাহের দিন ও সময়সূচী স্বয়ংক্রিয়ভাবে সংরক্ষিত হবে।</span>`;
        }
    }
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
                <div>
                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                    <select class="input" name="monthly_tasks[${index}][task_template_id]" required>
                        ${taskTemplatesOptions}
                    </select>
                </div>
                <div>
                    <label class="label">ফার্ম লোকেশন (ঐচ্ছিক)</label>
                    <select class="input" name="monthly_tasks[${index}][farm_location_id]">
                        ${farmLocationsOptions}
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
        syncWeeklyCustomSchedule();
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
        initWeeklySchedule();
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

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (currentRecurrence === 'weekly') {
                syncWeeklyCustomSchedule();
                const inputs = document.querySelectorAll('#weekly-hidden-form-inputs input[name^="custom_schedule"]');
                if (inputs.length === 0) {
                    e.preventDefault();
                    alert('⚠️ সাপ্তাহিক বরাদ্দের জন্য অনুগ্রহ করে অন্তত একটি সপ্তাহের দিন এবং কাজের ধরন নির্বাচন করুন।');
                    return false;
                }
            }
        });
    }

    updateTimePreview();
});
</script>
@endsection
