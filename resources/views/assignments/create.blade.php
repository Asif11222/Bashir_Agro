@extends('layouts.admin')

@section('title', 'নতুন কাজ বরাদ্দ করুন — আমার ফার্ম')

@section('content')
<div class="page" style="max-width: 900px;">
    <div class="head">
        <div>
            <h1>নতুন কাজ বরাদ্দ করুন</h1>
            <p>কৃষক, কাজের টেমপ্লেট এবং ফার্ম লোকেশন নির্বাচন করে কাজ বরাদ্দ করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.index') }}" class="btn">← বরাদ্দ তালিকায় ফিরুন</a>
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

    <form method="POST" action="{{ route('admin.assignments.store') }}">
        @csrf

        <div class="notice" style="margin-bottom: 16px;">
            <b>বরাদ্দ নিয়ম:</b> একজন কৃষক একাধিক কাজ পেতে পারেন, কিন্তু একই কৃষককে একই তারিখে ও লোকেশনে ডুপ্লিকেট কাজ বরাদ্দ করা যাবে না।
        </div>

        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">✓</span>
                    <div>
                        <h2>কাজের বরাদ্দ বিবরণ</h2>
                        <div class="sub">কৃষক, কাজ, অবস্থান ও সময়সূচি নির্ধারণ</div>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <label class="label">কৃষক নির্বাচন <span class="req">*</span></label>
                    <select class="input" name="farmer_id" required>
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
                    <label class="label">কাজের টেমপ্লেট <span class="req">*</span></label>
                    <select class="input" name="task_template_id" required>
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

                <div class="field full">
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

                <div>
                    <label class="label">বরাদ্দের তারিখ <span class="req">*</span></label>
                    <input class="input" type="date" name="scheduled_date" value="{{ old('scheduled_date', date('Y-m-d')) }}" required>
                    @error('scheduled_date')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="label">অগ্রাধিকার <span class="req">*</span></label>
                    <select class="input" name="priority" required>
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
                    <input class="input" type="time" name="start_time" value="{{ old('start_time', '08:00') }}" required>
                    @error('start_time')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="label">শেষ সময় (সময়সীমা)</label>
                    <input class="input" type="time" name="end_time" value="{{ old('end_time', '10:00') }}">
                    @error('end_time')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field full">
                    <label class="label">বিশেষ নির্দেশাবলী বা নোট (ঐচ্ছিক)</label>
                    <textarea class="input" name="notes" rows="3" placeholder="মাঠ পর্যায়ে কাজের জন্য কোনো বিশেষ নোট বা সতর্কতা থাকলে লিখুন...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="notice warn" style="margin-top: 16px;">
                কাজ নির্ধারিত সময়ের পরে সম্পন্ন করলে সাবমিশন গ্রহণ হবে, তবে <b>Late</b> হিসেবে ড্যাশবোর্ড ও রিপোর্টে লাল চিহ্নিত থাকবে।
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
                <a href="{{ route('admin.assignments.index') }}" class="btn">বাতিল</a>
                <button type="submit" class="btn primary">✓ কাজ বরাদ্দ সম্পন্ন করুন</button>
            </div>
        </section>
    </form>
</div>
@endsection
