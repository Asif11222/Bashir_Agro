@extends('layouts.admin')

@section('title', 'নতুন কাজ বরাদ্দ করুন — আমার ফার্ম')

@section('content')
<div class="page">
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

        <div class="form-layout-2col">
            <div class="form-main-col">
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
                </section>
            </div>

            <div class="form-side-col">
                <section class="card sticky-side-card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">ℹ</span>
                            <div>
                                <h3 style="font-size:15px;margin:0;color:var(--ink)">বরাদ্দ নির্দেশিকা ও নিয়ম</h3>
                                <div class="sub">সঠিক বরাদ্দ নিশ্চিতে সহায়ক নিয়মাবলী</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;color:#3f5147;line-height:1.5;">
                        <div style="background:#f4fbf6;border:1px solid #d4eedd;padding:12px;border-radius:9px;">
                            <b style="color:#08783f;display:flex;align-items:center;gap:6px;">
                                <span>📌</span> ডুপ্লিকেট প্রতিরোধ নিয়ম
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                একজন কৃষক একই দিনে একাধিক ভিন্ন কাজ সম্পন্ন করতে পারেন। তবে <b>একই কৃষককে একই তারিখে ও একই লোকেশনে ডুপ্লিকেট কাজ</b> বরাদ্দ করা যাবে না।
                            </p>
                        </div>

                        <div style="background:#fff9ed;border:1px solid #fce3b8;padding:12px;border-radius:9px;">
                            <b style="color:#a16207;display:flex;align-items:center;gap:6px;">
                                <span>⏱</span> সময়সীমা ও বিলম্ব সতর্কতা
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                কাজ নির্ধারিত সময়ের পরে সম্পন্ন করলেও মোবাইল অ্যাপে সাবমিশন গ্রহণ হবে, তবে ড্যাশবোর্ড ও ফিল্ড রিপোর্টে তা <b>Late</b> হিসেবে চিহ্নিত থাকবে।
                            </p>
                        </div>

                        <div style="background:#f0f7ff;border:1px solid #cbe2fe;padding:12px;border-radius:9px;">
                            <b style="color:#1d4ed8;display:flex;align-items:center;gap:6px;">
                                <span>📍</span> লোকেশন জিওফেন্সিং
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                কৃষক কাজ শুরু ও ছবি তোলার সময় অ্যাপ স্বয়ংক্রিয়ভাবে জিপিএস লোকেশন ও জিওফেন্স পরিধি যাচাই করবে।
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="sticky-actions" style="margin-top:24px;">
            <a href="{{ route('admin.assignments.index') }}" class="btn">বাতিল</a>
            <button type="submit" class="btn primary">✓ কাজ বরাদ্দ সম্পন্ন করুন</button>
        </div>
    </form>
</div>
@endsection
