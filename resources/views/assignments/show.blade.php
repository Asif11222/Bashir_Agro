@extends('layouts.admin')

@section('title', "কাজের বরাদ্দ বিবরণ — #{$assignment->id} — আমার ফার্ম")

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কাজের বরাদ্দ বিবরণ</h1>
            @if($assignment->is_self_reported)
                <p>বরাদ্দ আইডি: <span style="font-weight:700;color:var(--g800)">#{{ $assignment->id }}</span> • <span class="pill" style="background:#eaf8ef;color:#08783f;font-size:11px;">🌱 স্ব-প্রতিবেদিত কাজ</span> ({{ $assignment->farmer->full_name ?? '' }})</p>
            @else
                <p>বরাদ্দ আইডি: <span style="font-weight:700;color:var(--g800)">#{{ $assignment->id }}</span> • {{ $assignment->taskTemplate->name ?? '' }} ({{ $assignment->farmer->full_name ?? '' }})</p>
            @endif
        </div>
        <div class="actions">
            @if(! $assignment->is_self_reported)
                <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn primary">তথ্য সম্পাদনা</a>
            @endif
            <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই কাজের বরাদ্দটি মুছে ফেলতে চান? এটি সফট ডিলিট হবে।');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn red">মুছে ফেলুন</button>
            </form>
            <a href="{{ route('admin.assignments.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    @if($assignment->is_self_reported)
        <div style="background:#f0fdf4;border:1.5px solid #86efac;padding:16px 20px;border-radius:10px;margin-bottom:16px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <span style="font-size:28px;">🎙️</span>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <b style="color:#166534;font-size:15.5px;">কৃষক কর্তৃক স্ব-প্রতিবেদিত কাজ</b>
                        <span class="pill" style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:bold;">মাঠের লাইভ রিপোর্ট</span>
                    </div>
                    <div style="font-size:14px;color:#14532d;margin-top:6px;line-height:1.5;background:#ffffff;padding:8px 12px;border-radius:6px;border:1px solid #bbf7d0;">
                        <b>কৃষকের মুখের বিবরণ:</b> "{{ $assignment->notes }}"
                    </div>
                    <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                        কৃষক: <b>{{ $assignment->farmer->full_name }}</b> ({{ $assignment->farmer->farmer_code }}) • মোবাইল: <b>{{ $assignment->farmer->phone }}</b> • জমাদানের সময়: <b>{{ $assignment->completed_at ? $assignment->completed_at->format('d F, Y h:i A') : $assignment->created_at->format('d F, Y h:i A') }}</b>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($assignment->is_recurring)
        <div style="background:#f0fdf4;border:1.5px solid #86efac;padding:14px 18px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:24px;">🔄</span>
                <div>
                    <b style="color:#166534;font-size:14.5px;">
                        {{ match($assignment->recurrence_type) { 'daily' => 'দৈনিক', 'weekly' => 'সাপ্তাহিক', 'monthly' => 'মাসিক', default => 'নিয়মিত' } }} পুনরাবৃত্তিমূলক কাজের সিরিজ
                    </b>
                    <div style="font-size:12.5px;color:#14532d;margin-top:2px;">
                        সিরিজ কোড: <code style="background:#dcfce7;padding:2px 6px;border-radius:4px;font-weight:700;">{{ $assignment->recurring_group_id }}</code> • 
                        সিরিজের ধাপ: <b>#{{ $assignment->recurrence_seq }}</b> (সর্বমোট {{ $assignment->recurrence_total_count }}টি কাজের মধ্যে)
                    </div>
                </div>
            </div>
            @if(!in_array($assignment->status, ['completed', 'cancelled']))
                <div>
                    <button type="button" class="btn soft" style="font-size:12px;padding:6px 12px;color:#dc2626;border-color:#fca5a5;background:#fff;" onclick="openCancelSeriesModal()">
                        ✕ সম্পূর্ণ সিরিজ বাতিল করুন
                    </button>
                </div>
            @endif
        </div>
    @endif

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
                    <b style="font-size:14px;color:var(--ink);">{{ $assignment->formatted_time_range }}</b>
                    @if($assignment->is_past_deadline && !in_array($assignment->status, ['completed', 'cancelled']))
                        <span class="pill red" style="margin-top:4px;display:inline-flex;font-size:11px"><span class="dot"></span>সময় অতিক্রান্ত (বিলম্বিত জমা সম্ভব)</span>
                    @endif
                </div>
                <div>
                    <span class="label">বরাদ্দকারী (অ্যাডমিন / নিয়োজক)</span>
                    @if($assignment->is_self_reported)
                        <span class="pill" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">🌱 কৃষক নিজে (স্ব-প্রতিবেদিত)</span>
                    @else
                        <span>{{ $assignment->assigner->name ?? 'সিস্টেম অ্যাডমিন' }}</span>
                    @endif
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
                <div>
                    <span class="label">মোট কাজের সময় (Duration)</span>
                    <b style="font-size:13.5px;color:var(--ink);">{{ $assignment->work_duration ?? '—' }}</b>
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

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <div>
                        <b style="font-size:16px;display:block;">{{ $assignment->farmer->full_name ?? '—' }}</b>
                        <small style="color:var(--muted)">কোড: {{ $assignment->farmer->farmer_code ?? '' }} • মোবাইল: {{ $assignment->farmer->phone ?? '' }}</small>
                        <br><small style="color:var(--text)">প্রধান কাজ: {{ $assignment->farmer->primary_activity ?? 'কৃষি' }}</small>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        @if($assignment->farmer)
                            <a href="{{ route('admin.farmers.show', $assignment->farmer) }}" class="btn soft" style="font-size:12px;padding:6px 10px;">কৃষক প্রোফাইল</a>
                        @endif
                        @if(!in_array($assignment->status, ['completed', 'cancelled']))
                            <button type="button" class="btn soft" style="font-size:12px;padding:6px 10px;color:#1d4ed8;border-color:#93c5fd;background:#eff6ff;" onclick="openReassignModal()">
                                🔄 কৃষক পরিবর্তন
                            </button>
                        @endif
                    </div>
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
                        'approved' => ['label' => 'যাচাইকৃত ও অনুমোদিত', 'class' => ''],
                        'rejected' => ['label' => 'প্রত্যাখ্যাত', 'class' => 'red'],
                        default => ['label' => 'অনুমোদনের অপেক্ষায়', 'class' => 'blue'],
                    };
                @endphp
                <span id="submission-status-pill" class="pill {{ $vBadge['class'] }}"><span class="dot"></span><span id="submission-status-pill-text">{{ $vBadge['label'] }}</span></span>
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

        <div id="review-section" style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h3 style="font-size:15px;font-weight:700;margin:0;color:var(--text);">অ্যাডমিন মূল্যায়ন ও অনুমোদন (Admin Verification)</h3>
                <span id="current-review-badge" class="pill {{ $vStatus === 'approved' ? '' : ($vStatus === 'rejected' ? 'red' : 'blue') }}" style="font-size:11px;">
                    <span class="dot"></span>
                    <span id="current-review-badge-text">
                        @if($vStatus === 'approved')
                            অনুমোদিত
                        @elseif($vStatus === 'rejected')
                            প্রত্যাখ্যাত
                        @else
                            অপেক্ষমান
                        @endif
                    </span>
                </span>
            </div>

            {{-- 1. DECISION SUMMARY CARD (Visible when approved or rejected) --}}
            <div id="verified-decision-card" class="verified-decision-card {{ $vStatus }}" style="{{ $vStatus === 'pending' ? 'display:none;' : '' }}">
                <div class="verified-decision-header">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="decision-icon" id="decision-card-icon">
                            {{ $vStatus === 'rejected' ? '✕' : '✓' }}
                        </div>
                        <div>
                            <div style="font-size:14.5px;font-weight:700;color:var(--text);" id="decision-card-title">
                                @if($vStatus === 'approved')
                                    প্রতিবেদনটি অনুমোদিত ও মাঠের তথ্য যাচাইকৃত
                                @elseif($vStatus === 'rejected')
                                    প্রতিবেদনটি প্রত্যাখ্যাত হয়েছে (কৃষককে পুনরায় জমার নির্দেশ দেওয়া হয়েছে)
                                @else
                                    মূল্যায়ন সম্পন্ন হয়েছে
                                @endif
                            </div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px;" id="decision-card-subtitle">
                                @if($assignment->submission->verified_at)
                                    যাচাইয়ের সময়: {{ $assignment->submission->verified_at->format('d M Y, h:i A') }}
                                @else
                                    যাচাই সম্পন্ন হয়েছে
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn outline" id="btn-toggle-edit-review" onclick="window.toggleReviewForm(true)" style="font-size:12px;padding:6px 12px;border-radius:6px;cursor:pointer;">
                            ✎ সিদ্ধান্ত পরিবর্তন বা পুনঃমূল্যায়ন করুন
                        </button>
                    </div>
                </div>

                <div class="decision-note-box" id="decision-note-container" style="{{ empty($assignment->submission->notes) ? 'display:none;' : '' }}">
                    <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">অ্যাডমিন নোট / নির্দেশনা:</div>
                    <div id="decision-card-notes" style="font-size:13px;line-height:1.45;color:var(--text);word-break:break-word;">
                        {{ $assignment->submission->notes }}
                    </div>
                </div>
            </div>

            {{-- 2. ACTION FORM CONTAINER (Visible when pending, hidden when approved/rejected unless toggled) --}}
            <div id="review-form-container" style="{{ $vStatus !== 'pending' ? 'display:none;' : '' }}">
                <div style="margin-bottom:12px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;color:#475569;">
                    মাঠের ছবি, জিপিএস ও দূরত্বের তথ্য যাচাই করে প্রতিবেদন অনুমোদন বা প্রত্যাখ্যান করুন। একবার অনুমোদন বা প্রত্যাখ্যান সংরক্ষণ করলে এই বোতামগুলো সংরক্ষিত অবস্থায় পরিবর্তিত হবে।
                </div>

                <form id="review-form" method="POST" action="{{ route('admin.assignments.review', $assignment) }}#review-section" style="display:flex;flex-direction:column;gap:12px;">
                    @csrf
                    <div>
                        <label for="review_notes" style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px;">মূল্যায়ন সংক্রান্ত মন্তব্য বা নির্দেশনা (ঐচ্ছিক):</label>
                        <textarea id="review_notes" name="review_notes" rows="2" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;line-height:1.4;" placeholder="অনুমোদন বা পুনঃযাচাই সংক্রান্ত নির্দেশনা লিখুন...">{{ $assignment->submission->notes }}</textarea>
                    </div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <button type="submit" name="verification_status" value="approved" id="btn-approve" class="btn primary" style="background:#059669;color:#fff;display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;">
                            <span class="btn-spinner" style="display:none;">⏳ </span>
                            <span class="btn-label">✓ প্রতিবেদন অনুমোদন করুন</span>
                        </button>
                        <button type="submit" name="verification_status" value="rejected" id="btn-reject" class="btn red" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;">
                            <span class="btn-spinner" style="display:none;">⏳ </span>
                            <span class="btn-label">✕ প্রত্যাখ্যান / পুনরায় জমার নির্দেশ দিন</span>
                        </button>
                        <button type="button" class="btn text" id="btn-cancel-review" onclick="window.toggleReviewForm(false)" style="{{ $vStatus === 'pending' ? 'display:none;' : '' }};font-size:12px;color:var(--muted);padding:9px 12px;cursor:pointer;">
                            বাতিল
                        </button>
                        <span id="review-submitting-text" style="display:none;font-size:12px;color:var(--muted);">সংরক্ষণ হচ্ছে...</span>
                    </div>
                </form>
            </div>
        </div>
    </section>
    @endif

    <!-- Reassign Farmer Modal -->
    <div class="modal-overlay" id="reassignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;">
        <div class="modal-card" style="background:#fff;max-width:500px;width:90%;border-radius:12px;padding:22px;box-shadow:0 10px 25px rgba(0,0,0,0.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <b style="font-size:16px;color:#14241d;">🔄 বিকল্প কৃষক প্রতিস্থাপন (Reassign Farmer)</b>
                <button type="button" onclick="closeReassignModal()" style="border:0;background:none;font-size:22px;color:#8fa298;cursor:pointer;">&times;</button>
            </div>
            <p style="font-size:12.5px;color:#6f7f77;margin:0 0 16px;line-height:1.4;">
                কৃষক অসুস্থ বা অনুপস্থিত থাকলে তার পরিবর্তে অন্য কৃষককে কাজটি অর্পণ করুন।
            </p>

            <form method="POST" action="{{ route('admin.assignments.reassign', $assignment) }}">
                @csrf
                <div style="margin-bottom:14px;">
                    <label class="label">নতুন কৃষক নির্বাচন করুন <span class="req">*</span></label>
                    <select class="input" name="new_farmer_id" required>
                        <option value="">-- কৃষক নির্বাচন করুন --</option>
                        @foreach($farmers ?? [] as $f)
                            @if($f->id !== $assignment->farmer_id)
                                <option value="{{ $f->id }}">{{ $f->full_name }} ({{ $f->farmer_code }}) — {{ $f->phone }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                @if($assignment->is_recurring)
                <div style="background:#f4fbf6;border:1px solid #d4eedd;padding:12px;border-radius:8px;margin-bottom:14px;">
                    <b style="font-size:13px;color:#08783f;display:block;margin-bottom:8px;">পুনরাবৃত্তি সিরিজের জন্য প্রযোজ্য বিকল্প:</b>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="apply_to_future_series" value="0" checked>
                        <span>শুধুমাত্র এই নির্দিষ্ট দিনের কাজের জন্য পরিবর্তন করুন</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="apply_to_future_series" value="1">
                        <span><b>এই সিরিজের ভবিষ্যৎ সকল কাজের জন্য</b> নতুন কৃষককে দায়িত্ব দিন</span>
                    </label>
                </div>
                @endif

                <div style="margin-bottom:16px;">
                    <label class="label">পরিবর্তনের কারণ বা নোট (ঐচ্ছিক)</label>
                    <input class="input" type="text" name="reason" placeholder="যেমন: কৃষক অসুস্থ / জরুরি ছুটি">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn" onclick="closeReassignModal()">বাতিল</button>
                    <button type="submit" class="btn primary">✓ কৃষক প্রতিস্থাপন নিশ্চিত করুন</button>
                </div>
            </form>
        </div>
    </div>

    @if($assignment->is_recurring)
    <!-- Cancel Series Modal -->
    <div class="modal-overlay" id="cancelSeriesModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;">
        <div class="modal-card" style="background:#fff;max-width:480px;width:90%;border-radius:12px;padding:22px;box-shadow:0 10px 25px rgba(0,0,0,0.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <b style="font-size:16px;color:#dc2626;">✕ সম্পূর্ণ পুনরাবৃত্তি সিরিজ বাতিলকরণ</b>
                <button type="button" onclick="closeCancelSeriesModal()" style="border:0;background:none;font-size:22px;color:#8fa298;cursor:pointer;">&times;</button>
            </div>
            <p style="font-size:13px;color:#4b5563;margin:0 0 14px;line-height:1.4;">
                আপনি কি নিশ্চিত যে সিরিজ <b>{{ $assignment->recurring_group_id }}</b>-এর সকল অনিষ্পন্ন কাজ বাতিল করতে চান?
            </p>

            <form method="POST" action="{{ route('admin.assignments.recurring.cancel', $assignment->recurring_group_id) }}">
                @csrf
                <div style="margin-bottom:14px;">
                    <label class="label">যে তারিখ থেকে বাতিল হবে</label>
                    <input class="input" type="date" name="from_date" value="{{ $assignment->scheduled_date ? $assignment->scheduled_date->toDateString() : date('Y-m-d') }}">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">বাতিলের কারণ (ঐচ্ছিক)</label>
                    <input class="input" type="text" name="reason" placeholder="যেমন: প্রতিকূল আবহাওয়া / পরিকল্পনা পরিবর্তন">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn" onclick="closeCancelSeriesModal()">বাতিল</button>
                    <button type="submit" class="btn red">✕ সিরিজ বাতিল নিশ্চিত করুন</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
window.toggleReviewForm = function(showForm) {
    const formContainer = document.getElementById('review-form-container');
    const decisionCard = document.getElementById('verified-decision-card');
    const cancelBtn = document.getElementById('btn-cancel-review');
    if (!formContainer) return;

    if (showForm) {
        formContainer.style.display = 'block';
        if (cancelBtn) cancelBtn.style.display = 'inline-block';
        if (decisionCard) decisionCard.style.opacity = '0.6';
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        formContainer.style.display = 'none';
        if (decisionCard) {
            decisionCard.style.display = 'block';
            decisionCard.style.opacity = '1';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const reviewForm = document.getElementById('review-form');
    if (!reviewForm) return;

    let clickedStatus = null;
    const btnApprove = document.getElementById('btn-approve');
    const btnReject = document.getElementById('btn-reject');
    const submittingText = document.getElementById('review-submitting-text');

    if (btnApprove) btnApprove.addEventListener('click', () => { clickedStatus = 'approved'; });
    if (btnReject) btnReject.addEventListener('click', () => { clickedStatus = 'rejected'; });

    reviewForm.addEventListener('submit', async (e) => {
        if (!clickedStatus) {
            clickedStatus = e.submitter ? e.submitter.value : 'approved';
        }

        e.preventDefault();

        const notes = document.getElementById('review_notes') ? document.getElementById('review_notes').value : '';
        const token = reviewForm.querySelector('input[name="_token"]')?.value;

        const targetBtn = clickedStatus === 'approved' ? btnApprove : btnReject;
        
        btnApprove.disabled = true;
        btnReject.disabled = true;
        if (targetBtn) {
            const spinner = targetBtn.querySelector('.btn-spinner');
            if (spinner) spinner.style.display = 'inline';
        }
        if (submittingText) submittingText.style.display = 'inline';

        try {
            const response = await fetch(reviewForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    verification_status: clickedStatus,
                    review_notes: notes
                })
            });

            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }

            const data = await response.json();

            // Update Decision Card & Elements
            const decisionCard = document.getElementById('verified-decision-card');
            const decisionIcon = document.getElementById('decision-card-icon');
            const decisionTitle = document.getElementById('decision-card-title');
            const decisionSubtitle = document.getElementById('decision-card-subtitle');
            const decisionNotesContainer = document.getElementById('decision-note-container');
            const decisionNotes = document.getElementById('decision-card-notes');
            const formContainer = document.getElementById('review-form-container');

            const badge = document.getElementById('current-review-badge');
            const badgeText = document.getElementById('current-review-badge-text');
            const pill = document.getElementById('submission-status-pill');
            const pillText = document.getElementById('submission-status-pill-text');

            if (clickedStatus === 'approved') {
                if (decisionCard) {
                    decisionCard.className = 'verified-decision-card approved';
                }
                if (decisionIcon) decisionIcon.textContent = '✓';
                if (decisionTitle) decisionTitle.textContent = 'প্রতিবেদনটি অনুমোদিত ও মাঠের তথ্য যাচাইকৃত';
                if (badge) {
                    badge.className = 'pill';
                    if (badgeText) badgeText.textContent = 'অনুমোদিত';
                }
                if (pill) {
                    pill.className = 'pill';
                    if (pillText) pillText.textContent = 'যাচাইকৃত ও অনুমোদিত';
                }
            } else {
                if (decisionCard) {
                    decisionCard.className = 'verified-decision-card rejected';
                }
                if (decisionIcon) decisionIcon.textContent = '✕';
                if (decisionTitle) decisionTitle.textContent = 'প্রতিবেদনটি প্রত্যাখ্যাত হয়েছে (কৃষককে পুনরায় জমার নির্দেশ দেওয়া হয়েছে)';
                if (badge) {
                    badge.className = 'pill red';
                    if (badgeText) badgeText.textContent = 'প্রত্যাখ্যাত';
                }
                if (pill) {
                    pill.className = 'pill red';
                    if (pillText) pillText.textContent = 'প্রত্যাখ্যাত';
                }
            }

            if (decisionSubtitle) {
                const now = new Date();
                decisionSubtitle.textContent = 'যাচাইয়ের সময়: ' + now.toLocaleString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
            }

            if (decisionNotes && decisionNotesContainer) {
                if (notes && notes.trim() !== '') {
                    decisionNotes.textContent = notes;
                    decisionNotesContainer.style.display = 'block';
                } else {
                    decisionNotesContainer.style.display = 'none';
                }
            }

            // Hide action form and display the permanent decision card
            if (formContainer) formContainer.style.display = 'none';
            if (decisionCard) {
                decisionCard.style.display = 'block';
                decisionCard.style.opacity = '1';
                decisionCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            if (window.showToast) {
                window.showToast(data.message || 'মূল্যায়ন সফলভাবে সংরক্ষিত হয়েছে।');
            }
        } catch (err) {
            console.warn('AJAX submission failed, falling back to standard submit:', err);
            const hiddenStatus = document.createElement('input');
            hiddenStatus.type = 'hidden';
            hiddenStatus.name = 'verification_status';
            hiddenStatus.value = clickedStatus;
            reviewForm.appendChild(hiddenStatus);
            reviewForm.submit();
            return;
        } finally {
            btnApprove.disabled = false;
            btnReject.disabled = false;
            if (targetBtn) {
                const spinner = targetBtn.querySelector('.btn-spinner');
                if (spinner) spinner.style.display = 'none';
            }
            if (submittingText) submittingText.style.display = 'none';
        }
    });
});

window.openReassignModal = function() {
    const modal = document.getElementById('reassignModal');
    if (modal) modal.style.display = 'flex';
};
window.closeReassignModal = function() {
    const modal = document.getElementById('reassignModal');
    if (modal) modal.style.display = 'none';
};
window.openCancelSeriesModal = function() {
    const modal = document.getElementById('cancelSeriesModal');
    if (modal) modal.style.display = 'flex';
};
window.closeCancelSeriesModal = function() {
    const modal = document.getElementById('cancelSeriesModal');
    if (modal) modal.style.display = 'none';
};
</script>
@endpush
