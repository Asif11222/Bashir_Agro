@extends('layouts.admin')

@section('title', "কাজের বিবরণ — {$task->name}")

@section('content')
<div class="page" style="max-width: 1000px;">
    <div class="head">
        <div>
            <h1>কাজের বিবরণ</h1>
            <p>কাজের কোড: <span style="font-weight:700;color:var(--g800)">{{ $task->task_code }}</span> • {{ $task->name }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.create', ['task_id' => $task->id]) }}" class="btn soft">+ কাজ বরাদ্দ</a>
            <a href="{{ route('admin.tasks.edit', $task) }}" class="btn primary">তথ্য সম্পাদনা</a>
            <a href="{{ route('admin.tasks.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">▣</span>
                    <div>
                        <h2>কাজের মূল বিবরণ</h2>
                        <div class="sub">টেমপ্লেট কাঠামো ও নিয়মাবলী</div>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <span class="label">কাজের নাম</span>
                    <b style="font-size:16px;">{{ $task->name }}</b>
                </div>
                <div>
                    <span class="label">কাজের কোড</span>
                    <span style="font-weight:700;color:var(--g800)">{{ $task->task_code }}</span>
                </div>
                <div>
                    <span class="label">কাজের শ্রেণি</span>
                    <span class="pill blue"><span class="dot"></span>{{ $task->category }}</span>
                </div>
                <div>
                    <span class="label">অবস্থা</span>
                    @include('components.status-pill', ['status' => $task->status])
                </div>
                <div>
                    <span class="label">পুনরাবৃত্তি নিয়ম</span>
                    @php
                        $recurrenceMap = [
                            'once' => 'এককালীন',
                            'daily' => 'দৈনিক',
                            'weekly' => 'সাপ্তাহিক',
                            'monthly' => 'মাসিক',
                            'এককালীন' => 'এককালীন',
                            'দৈনিক' => 'দৈনিক',
                            'সাপ্তাহিক' => 'সাপ্তাহিক',
                            'মাসিক' => 'মাসিক',
                        ];
                    @endphp
                    <b>{{ $recurrenceMap[$task->recurrence_type] ?? $task->recurrence_type }}</b>
                </div>
                <div>
                    <span class="label">প্রয়োজনীয় ছবি সংখ্যা</span>
                    <b>{{ $task->required_photo_count }}টি</b>
                </div>
                @if($task->description)
                <div class="field full">
                    <span class="label">সংক্ষিপ্ত বর্ণনা</span>
                    <p style="margin:0;color:var(--text);font-size:14px;line-height:1.5;">{{ $task->description }}</p>
                </div>
                @endif
                <div class="field full">
                    <span class="label">কাজের বিস্তারিত নির্দেশাবলী</span>
                    <div style="background:var(--bg);padding:12px;border-radius:8px;font-size:13px;line-height:1.6;color:var(--text);white-space:pre-wrap;">{{ $task->instructions }}</div>
                </div>
            </div>
        </section>

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">🛡️</span>
                        <div>
                            <h2>যাচাই ও নিরাপত্তা নীতি</h2>
                            <div class="sub">মাঠ পর্যায়ে কাজ সম্পন্নের শর্ত</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--bg);border-radius:8px;">
                        <div>
                            <b style="font-size:13px;display:block;">রিয়েল-টাইম ক্যামেরা ছবি</b>
                            <small style="color:var(--muted)">মাঠ থেকে সরাসরি ছবি তোলা বাধ্যতামূলক</small>
                        </div>
                        @if($task->require_realtime_photo)
                            <span class="pill"><span class="dot"></span>চালু</span>
                        @else
                            <span class="pill red"><span class="dot"></span>বন্ধ</span>
                        @endif
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--bg);border-radius:8px;">
                        <div>
                            <b style="font-size:13px;display:block;">গ্যালারি থেকে আপলোড</b>
                            <small style="color:var(--muted)">পূর্বের সংরক্ষিত ছবি আপলোডের অনুমতি</small>
                        </div>
                        @if($task->allow_gallery_upload)
                            <span class="pill"><span class="dot"></span>অনুমোদিত</span>
                        @else
                            <span class="pill red"><span class="dot"></span>অননুমোদিত</span>
                        @endif
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--bg);border-radius:8px;">
                        <div>
                            <b style="font-size:13px;display:block;">জিও-ফেন্স ভ্যালিডেশন</b>
                            <small style="color:var(--muted)">ফার্মের নির্দিষ্ট ব্যাসার্ধের মধ্যে থাকা বাধ্যতামূলক</small>
                        </div>
                        @if($task->require_geofence)
                            <span class="pill"><span class="dot"></span>চালু</span>
                        @else
                            <span class="pill red"><span class="dot"></span>বন্ধ</span>
                        @endif
                    </div>
                </div>

                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:var(--muted)">টেমপ্লেট আইডি: #{{ $task->id }}</span>
                    <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই কাজের টেমপ্লেটটি মুছে ফেলতে চান?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn red" style="padding:6px 12px;font-size:12px">টেমপ্লেট মুছুন</button>
                    </form>
                </div>
            </section>

            <section class="card" style="margin-top:16px;">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">📋</span>
                        <div>
                            <h2>সাম্প্রতিক বরাদ্দ</h2>
                            <div class="sub">এই টেমপ্লেটের অধীনে সাম্প্রতিক কার্যসমূহ</div>
                        </div>
                    </div>
                </div>

                @if($task->taskAssignments && $task->taskAssignments->count() > 0)
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>কৃষক</th>
                                    <th>লোকেশন</th>
                                    <th>তারিখ</th>
                                    <th>অবস্থা</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($task->taskAssignments as $assignment)
                                    <tr>
                                        <td>{{ $assignment->farmer->name ?? 'অজ্ঞাত' }}</td>
                                        <td>{{ $assignment->farmLocation->name ?? 'অজ্ঞাত' }}</td>
                                        <td>{{ $assignment->due_date ? $assignment->due_date->format('d/m/Y') : '—' }}</td>
                                        <td>@include('components.status-pill', ['status' => $assignment->status])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding:24px 12px;text-align:center;color:var(--muted);font-size:13px;">
                        এই টেমপ্লেটের অধীনে এখনো কোনো কাজ মাঠ পর্যায়ে বরাদ্দ করা হয়নি।
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
