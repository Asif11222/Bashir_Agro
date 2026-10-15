@extends('layouts.admin')

@section('title', "কাজের বরাদ্দ বিবরণ — #{$assignment->id} — আমার ফার্ম")

@section('content')
<div class="page" style="max-width: 1000px;">
    <div class="head">
        <div>
            <h1>কাজের বরাদ্দ বিবরণ</h1>
            <p>বরাদ্দ আইডি: <span style="font-weight:700;color:var(--g800)">#{{ $assignment->id }}</span> • {{ $assignment->taskTemplate->name ?? '' }} ({{ $assignment->farmer->full_name ?? '' }})</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn primary">তথ্য সম্পাদনা</a>
            <a href="{{ route('admin.assignments.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">✓</span>
                    <div>
                        <h2>বরাদ্দের মূল তথ্য</h2>
                        <div class="sub">সময়সূচি ও বর্তমান অবস্থা</div>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <span class="label">কাজের অবস্থা</span>
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
                    <span class="pill {{ $stInfo['class'] }}"><span class="dot"></span>{{ $stInfo['label'] }}</span>
                </div>
                <div>
                    <span class="label">অগ্রাধিকার</span>
                    <span class="pill {{ $assignment->priority_badge_class }}"><span class="dot"></span>{{ $assignment->priority_label }}</span>
                </div>
                <div>
                    <span class="label">বরাদ্দের তারিখ</span>
                    <b style="font-size:15px;">{{ $assignment->scheduled_date ? $assignment->scheduled_date->format('d F, Y') : '—' }}</b>
                </div>
                <div>
                    <span class="label">সময়সীমা</span>
                    <b>{{ substr($assignment->start_time, 0, 5) }} {{ $assignment->end_time ? 'থেকে ' . substr($assignment->end_time, 0, 5) : '' }}</b>
                </div>
                <div>
                    <span class="label">বরাদ্দকারী (অ্যাডমিন)</span>
                    <span>{{ $assignment->assigner->name ?? 'সিস্টেম অ্যাডমিন' }}</span>
                </div>
                <div>
                    <span class="label">বরাদ্দের সময়</span>
                    <small>{{ $assignment->assigned_at ? $assignment->assigned_at->format('d/m/Y h:i A') : '—' }}</small>
                </div>
                <div>
                    <span class="label">কাজ শুরুর সময়</span>
                    <small>{{ $assignment->started_at ? $assignment->started_at->format('d/m/Y h:i A') : 'শুরু হয়নি' }}</small>
                </div>
                <div>
                    <span class="label">কাজ সমাপ্তির সময়</span>
                    <small>{{ $assignment->completed_at ? $assignment->completed_at->format('d/m/Y h:i A') : 'সমাপ্ত হয়নি' }}</small>
                </div>
                @if($assignment->notes)
                <div class="field full">
                    <span class="label">অ্যাডমিনের বিশেষ নোট</span>
                    <p style="margin:0;padding:10px;background:var(--bg);border-radius:6px;font-size:13px;color:var(--text);">{{ $assignment->notes }}</p>
                </div>
                @endif
            </div>

            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:12px;color:var(--muted)">তৈরির তারিখ: {{ $assignment->created_at->format('d M, Y') }}</span>
                <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই কাজের বরাদ্দটি মুছে ফেলতে চান?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn red" style="padding:6px 12px;font-size:12px">বরাদ্দ মুছুন</button>
                </form>
            </div>
        </section>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">♟</span>
                        <div>
                            <h2>বরাদ্দকৃত কৃষক</h2>
                            <div class="sub">কার্যসম্পাদনে নিয়োজিত কৃষক</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <b style="font-size:16px;display:block;">{{ $assignment->farmer->full_name ?? '—' }}</b>
                        <small style="color:var(--muted)">কোড: {{ $assignment->farmer->farmer_code ?? '' }} • মোবাইল: {{ $assignment->farmer->phone ?? '' }}</small>
                        <br><small style="color:var(--text)">প্রধান কাজ: {{ $assignment->farmer->primary_activity ?? 'কৃষি' }}</small>
                    </div>
                    @if($assignment->farmer)
                        <a href="{{ route('admin.farmers.show', $assignment->farmer) }}" class="btn soft" style="font-size:12px;padding:6px 10px;">কৃষক প্রোফাইল</a>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">▣</span>
                        <div>
                            <h2>কাজের টেমপ্লেট ও নির্দেশনা</h2>
                            <div class="sub">কাজের নিয়ম ও প্রয়োজনীয় প্রমাণ</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <b style="font-size:15px;">{{ $assignment->taskTemplate->name ?? '—' }}</b>
                        <span class="pill blue">{{ $assignment->taskTemplate->category ?? '' }}</span>
                    </div>

                    <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">
                        প্রয়োজনীয় ছবি: <b>{{ $assignment->taskTemplate->required_photo_count ?? 1 }}টি</b> • 
                        ক্যামেরা: <b>{{ ($assignment->taskTemplate->require_realtime_photo ?? true) ? 'লাইভ বাধ্যতামূলক' : 'ঐচ্ছিক' }}</b> • 
                        জিও-ফেন্স: <b>{{ ($assignment->taskTemplate->require_geofence ?? true) ? 'চালু' : 'বন্ধ' }}</b>
                    </div>

                    <div style="background:var(--bg);padding:10px;border-radius:6px;font-size:13px;line-height:1.5;">
                        <span style="font-size:11px;color:var(--muted);display:block;margin-bottom:4px;">কাজের মূল নির্দেশাবলী:</span>
                        {{ $assignment->taskTemplate->instructions ?? 'কোনো বিস্তারিত নির্দেশনা নেই' }}
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⌖</span>
                        <div>
                            <h2>নির্ধারিত ফার্ম লোকেশন</h2>
                            <div class="sub">কাজের ভৌগোলিক অবস্থান ও বাফার এলাকা</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <b style="font-size:15px;display:block;">{{ $assignment->location->name ?? '—' }}</b>
                        <small style="color:var(--muted)">কোড: {{ $assignment->location->location_code ?? '' }} • ধরন: {{ $assignment->location->type ?? '' }}</small>
                        <br><small style="color:var(--g800);font-weight:600;">জিও-ফেন্স বাফার: {{ $assignment->location->geofence_radius_meters ?? 20 }} মিটার</small>
                    </div>
                    @if($assignment->location)
                        <a href="{{ route('admin.locations.show', $assignment->location) }}" class="btn soft" style="font-size:12px;padding:6px 10px;">লোকেশন ম্যাপ</a>
                    @endif
                </div>
            </section>
        </div>
    </div>

    @if($assignment->submission)
    <section class="card" style="margin-top:20px;">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox" style="background:#ecfdf5;color:#059669;">📸</span>
                <div>
                    <h2>কাজের প্রমাণ ও প্রতিবেদন যাচাই (Farmer Submission & Verification)</h2>
                    <div class="sub">কৃষক কর্তৃক প্রেরিত মাঠের ছবি, জিপিএস ও পোস্টজিআইএস (PostGIS) যাচাই প্রতিবেদন</div>
                </div>
            </div>
            <div>
                @php
                    $vStatus = $assignment->submission->verification_status ?? 'pending';
                    $vBadge = match($vStatus) {
                        'approved' => ['label' => 'যাচাইকৃত ও অনুমোদিত', 'class' => 'green'],
                        'rejected' => ['label' => 'প্রত্যাখ্যাত', 'class' => 'red'],
                        default => ['label' => 'অনুমোদনের অপেক্ষায়', 'class' => 'blue'],
                    };
                @endphp
                <span class="pill {{ $vBadge['class'] }}"><span class="dot"></span>{{ $vBadge['label'] }}</span>
            </div>
        </div>

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));margin-bottom:20px;">
            <div>
                <span class="label">জমা দেওয়ার সময়</span>
                <b>{{ $assignment->submission->submitted_at ? $assignment->submission->submitted_at->format('d F, Y — h:i A') : '—' }}</b>
            </div>
            <div>
                <span class="label">জিপিএস স্থানাঙ্ক (GPS)</span>
                <b>{{ $assignment->submission->latitude }}, {{ $assignment->submission->longitude }}</b>
                @if($assignment->submission->accuracy)
                    <small style="color:var(--muted);display:block;">নির্ভুলতা: ±{{ $assignment->submission->accuracy }} মিটার</small>
                @endif
            </div>
            <div>
                <span class="label">পোস্টজিআইএস জিও-ফেন্স যাচাই</span>
                @if($assignment->submission->geofence_passed)
                    <span class="pill" style="background:#ecfdf5;color:#059669;font-weight:600;"><span class="dot" style="background:#059669;"></span>সফল (সীমার ভেতরে)</span>
                @else
                    <span class="pill red"><span class="dot"></span>সীমার বাইরে (সতর্কবার্তা)</span>
                @endif
                <small style="color:var(--muted);display:block;margin-top:4px;">দূরত্ব: {{ $assignment->submission->distance_meters ?? 0 }} মি. (অনুমোদিত: {{ $assignment->location->geofence_radius_meters ?? 20 }} মি.)</small>
            </div>
            <div>
                <span class="label">কৃষকের নোট</span>
                <p style="margin:0;font-size:13px;color:var(--text);">{{ $assignment->submission->farmer_note ?? $assignment->submission->notes ?? 'কোনো নোট দেওয়া হয়নি' }}</p>
            </div>
        </div>

        @php
            $photos = $assignment->submission->evidencePhotos ?? collect();
            $rawPhotos = $assignment->submission->photos ?? [];
        @endphp

        <div style="margin-top:16px;">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--text);">সংযুক্ত প্রামাণ্য ছবি ({{ $photos->count() ?: count($rawPhotos) }}টি)</h3>
            <div style="display:grid;grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));gap:12px;">
                @forelse($photos as $photo)
                    <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;background:var(--bg);">
                        <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="Evidence" style="width:100%;height:140px;object-fit:cover;display:block;">
                        </a>
                        <div style="padding:8px;font-size:11px;color:var(--muted);">
                            <span>সময়: {{ $photo->captured_at ? $photo->captured_at->format('h:i A') : '—' }}</span>
                            @if($photo->latitude && $photo->longitude)
                                <div style="font-family:monospace;margin-top:2px;">{{ round($photo->latitude, 4) }}, {{ round($photo->longitude, 4) }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    @forelse($rawPhotos as $rPhoto)
                        <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;background:var(--bg);">
                            <a href="{{ asset('storage/' . $rPhoto) }}" target="_blank" rel="noopener">
                                <img src="{{ asset('storage/' . $rPhoto) }}" alt="Evidence" style="width:100%;height:140px;object-fit:cover;display:block;">
                            </a>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1;padding:16px;background:var(--bg);border-radius:6px;font-size:13px;color:var(--muted);text-align:center;">
                            কোনো ছবি আপলোড করা হয়নি।
                        </div>
                    @endforelse
                @endforelse
            </div>
        </div>

        <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:8px;color:var(--text);">অ্যাডমিন মূল্যায়ন ও অনুমোদন</h3>
            <form method="POST" action="{{ route('admin.assignments.review', $assignment) }}" style="display:flex;flex-direction:column;gap:12px;">
                @csrf
                <div>
                    <label for="review_notes" style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px;">মূল্যায়ন সংক্রান্ত মন্তব্য (ঐচ্ছিক):</label>
                    <textarea id="review_notes" name="review_notes" rows="2" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px;" placeholder="অনুমোদন বা পুনঃযাচাই সংক্রান্ত নির্দেশনা লিখুন...">{{ $assignment->submission->notes }}</textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" name="verification_status" value="approved" class="btn primary" style="background:#059669;color:#fff;">
                        ✓ প্রতিবেদন অনুমোদন করুন
                    </button>
                    <button type="submit" name="verification_status" value="rejected" class="btn red">
                        ✕ প্রত্যাখ্যান / পুনরায় জমার নির্দেশ দিন
                    </button>
                </div>
            </form>
        </div>
    </section>
    @endif
</div>
@endsection
