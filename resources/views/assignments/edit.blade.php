@extends('layouts.admin')

@section('title', "কাজ বরাদ্দ সম্পাদন — #{$assignment->id} — আমার ফার্ম")

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কাজ বরাদ্দ সম্পাদন</h1>
            <p>বরাদ্দ আইডি: <span style="font-weight:700;color:var(--g800)">#{{ $assignment->id }}</span> • {{ $assignment->taskTemplate->name ?? '' }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn soft">বিবরণ দেখুন</a>
            <a href="{{ route('admin.assignments.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="notice warn" style="margin-bottom: 16px; border-left: 4px solid var(--red); background: #fff5f5;">
            <b>অনুগ্রহ করে নিচের ত্রুটিগুলো সংশোধন করুন:</b>
            <ul style="margin: 6px 0 0 16px; padding: 0; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li style="color: var(--red);">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.assignments.update', $assignment) }}">
        @csrf
        @method('PUT')

        <div class="form-layout-2col">
            <div class="form-main-col">
                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">✓</span>
                            <div>
                                <h2>বরাদ্দ তথ্য হালনাগাদ</h2>
                                <div class="sub">কৃষক, কাজের অবস্থা ও সময়সূচি পরিবর্তন করুন</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div>
                            <label class="label">কৃষক নির্বাচন <span class="req">*</span></label>
                            <select class="input" name="farmer_id" required>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}" {{ old('farmer_id', $assignment->farmer_id) == $farmer->id ? 'selected' : '' }}>
                                        {{ $farmer->full_name }} ({{ $farmer->farmer_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('farmer_id')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                            <select class="input" name="task_template_id" required>
                                @foreach($tasks as $task)
                                    <option value="{{ $task->id }}" {{ old('task_template_id', $assignment->task_template_id) == $task->id ? 'selected' : '' }}>
                                        {{ $task->name }} ({{ $task->task_code }}) — {{ $task->category }}
                                    </option>
                                @endforeach
                            </select>
                            @error('task_template_id')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field full">
                            <label class="label">ফার্ম লোকেশন (প্লট / জমি / পুকুর) <span class="req">*</span></label>
                            <select class="input" name="farm_location_id" required>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('farm_location_id', $assignment->farm_location_id) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->location_code }}) — {{ $loc->type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('farm_location_id')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">বরাদ্দের তারিখ <span class="req">*</span></label>
                            <input class="input" type="date" name="scheduled_date" value="{{ old('scheduled_date', $assignment->scheduled_date ? $assignment->scheduled_date->format('Y-m-d') : '') }}" required>
                            @error('scheduled_date')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">অগ্রাধিকার <span class="req">*</span></label>
                            <select class="input" name="priority" required>
                                <option value="normal" {{ old('priority', $assignment->priority) === 'normal' ? 'selected' : '' }}>সাধারণ (Normal)</option>
                                <option value="important" {{ old('priority', $assignment->priority) === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ (Important)</option>
                                <option value="urgent" {{ old('priority', $assignment->priority) === 'urgent' ? 'selected' : '' }}>জরুরি (Urgent)</option>
                            </select>
                            @error('priority')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">শুরুর সময় <span class="req">*</span></label>
                            <input class="input" type="time" id="start_time" name="start_time" value="{{ old('start_time', substr($assignment->start_time, 0, 5)) }}" required>
                            @error('start_time')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label">শেষ সময় (সময়সীমা)</label>
                            <input class="input" type="time" id="end_time" name="end_time" value="{{ old('end_time', $assignment->end_time ? substr($assignment->end_time, 0, 5) : '') }}">
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

                        <div class="field full">
                            <label class="label">কাজের অবস্থা (Status) <span class="req">*</span></label>
                            <select class="input" name="status" required>
                                <option value="assigned" {{ old('status', $assignment->status) === 'assigned' ? 'selected' : '' }}>বরাদ্দকৃত (Assigned)</option>
                                <option value="in_progress" {{ old('status', $assignment->status) === 'in_progress' ? 'selected' : '' }}>চলমান (In Progress)</option>
                                <option value="completed" {{ old('status', $assignment->status) === 'completed' ? 'selected' : '' }}>সম্পন্ন (Completed)</option>
                                <option value="cancelled" {{ old('status', $assignment->status) === 'cancelled' ? 'selected' : '' }}>বাতিল (Cancelled)</option>
                            </select>
                            @error('status')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field full">
                            <label class="label">বিশেষ নির্দেশাবলী বা নোট (ঐচ্ছিক)</label>
                            <textarea class="input" name="notes" rows="3" placeholder="মাঠ পর্যায়ে কাজের জন্য কোনো বিশেষ নোট বা সতর্কতা থাকলে লিখুন...">{{ old('notes', $assignment->notes) }}</textarea>
                            @error('notes')
                                <div class="help" style="color:var(--red)">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="form-side-col">
                <section class="card sticky-side-card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">ℹ</span>
                            <div>
                                <h3 style="font-size:15px;margin:0;color:var(--ink)">বরাদ্দের বর্তমান অবস্থা</h3>
                                <div class="sub">আইডি #{{ $assignment->id }} সারাংশ</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;color:#3f5147;line-height:1.5;">
                        <div style="background:#f4fbf6;border:1px solid #d4eedd;padding:12px;border-radius:9px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="color:var(--muted);font-size:12px;">বর্তমান স্ট্যাটাস</span>
                                <span class="pill {{ $assignment->status === 'completed' ? '' : ($assignment->status === 'in_progress' ? 'blue' : ($assignment->status === 'cancelled' ? 'red' : 'amber')) }}">
                                    {{ $assignment->status === 'completed' ? 'সম্পন্ন' : ($assignment->status === 'in_progress' ? 'চলমান' : ($assignment->status === 'cancelled' ? 'বাতিল' : 'বরাদ্দকৃত')) }}
                                </span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:var(--muted);font-size:12px;">বরাদ্দ তৈরি</span>
                                <span style="font-weight:600;font-size:12px;">{{ $assignment->created_at ? $assignment->created_at->format('d/m/Y h:i A') : '-' }}</span>
                            </div>
                        </div>

                        <div style="background:#fff9ed;border:1px solid #fce3b8;padding:12px;border-radius:9px;">
                            <b style="color:#a16207;display:flex;align-items:center;gap:6px;">
                                <span>⚠</span> স্ট্যাটাস পরিবর্তন নিয়ম
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                স্ট্যাটাস ম্যানুয়ালি 'সম্পন্ন' বা 'বাতিল' করলে অ্যাপে কৃষকের টাস্ক লিস্ট তাৎক্ষণিকভাবে সিঙ্ক হয়ে আপডেট হবে।
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="sticky-actions">
            <a href="{{ route('admin.assignments.index') }}" class="btn">বাতিল</a>
            <button type="submit" class="btn primary">তথ্য আপডেট করুন</button>
        </div>
    </form>
</div>

<script>
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
    const dateInput = document.querySelector('input[name="scheduled_date"]');
    const badge = document.getElementById('time-preview-badge');
    const warning = document.getElementById('past-time-warning');

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

    if (warning && dateInput?.value && endInput?.value) {
        const today = new Date();
        const selectedDate = new Date(dateInput.value + 'T00:00:00');
        const isToday = today.getFullYear() === selectedDate.getFullYear() &&
                        today.getMonth() === selectedDate.getMonth() &&
                        today.getDate() === selectedDate.getDate();

        if (isToday) {
            const endParts = endInput.value.split(':');
            const endHour = parseInt(endParts[0], 10);
            const endMinute = parseInt(endParts[1] || 0, 10);
            const endDateTime = new Date();
            endDateTime.setHours(endHour, endMinute, 0, 0);

            if (today > endDateTime) {
                warning.innerHTML = `⚠️ <b>সতর্কতা:</b> নির্বাচিত শেষ সময়সীমা (<b>${endFormatted}</b>) আজকের বর্তমান সময়ের পূর্বে অতিবাহিত হয়ে গেছে! কৃষক যাতে কাজ সম্পন্ন করতে পারে, সেজন্য শেষ সময়সীমা রাত পর্যন্ত নির্ধারণ করুন (যেমন রাত ১০:০০ PM বা 22:00) অথবা তারিখ পরিবর্তন করুন।`;
                warning.style.display = 'block';
                return;
            }
        }
        warning.style.display = 'none';
    }
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
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const dateInput = document.querySelector('input[name="scheduled_date"]');

    if (startInput) startInput.addEventListener('input', updateTimePreview);
    if (endInput) endInput.addEventListener('input', updateTimePreview);
    if (dateInput) dateInput.addEventListener('change', updateTimePreview);

    updateTimePreview();
});
</script>
@endsection
