@extends('layouts.admin')

@section('title', "কাজ বরাদ্দ সম্পাদন — #{$assignment->id} — আমার ফার্ম")

@section('content')
<div class="page" style="max-width: 900px;">
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
                    <input class="input" type="time" name="start_time" value="{{ old('start_time', substr($assignment->start_time, 0, 5)) }}" required>
                    @error('start_time')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="label">শেষ সময় (সময়সীমা)</label>
                    <input class="input" type="time" name="end_time" value="{{ old('end_time', $assignment->end_time ? substr($assignment->end_time, 0, 5) : '') }}">
                    @error('end_time')
                        <div class="help" style="color:var(--red)">{{ $message }}</div>
                    @enderror
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

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
                <a href="{{ route('admin.assignments.index') }}" class="btn">বাতিল</a>
                <button type="submit" class="btn primary">তথ্য আপডেট করুন</button>
            </div>
        </section>
    </form>
</div>
@endsection
