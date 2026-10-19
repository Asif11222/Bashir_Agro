@if($activeAssignment)
    <section class="card">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">{{ $isOngoing ? '◉' : '⏱' }}</span>
                <div>
                    <h2>{{ $isOngoing ? 'চলমান অপারেশন ফোকাস' : 'নির্ধারিত অপারেশন ফোকাস' }}</h2>
                    <div class="sub">
                        {{ $activeAssignment->taskTemplate->name ?? 'কাজের বিবরণ' }} • {{ $activeAssignment->taskTemplate->task_code ?? '' }}
                        @if(!empty($isGlobalActive))
                            <span style="color:var(--blue);font-weight:600">(সার্বিক চলমান মাঠ কাজ)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid">
            <div>
                <span class="label">নিয়োজিত কৃষক</span>
                <b>{{ $activeAssignment->farmer->full_name ?? '—' }}</b>
                <br><small style="color:var(--muted)">{{ $activeAssignment->farmer->farmer_code ?? '' }}</small>
            </div>
            <div>
                <span class="label">ফার্ম লোকেশন</span>
                <b>{{ $activeAssignment->location->name ?? '—' }}</b>
                <br><small style="color:var(--muted)">{{ $activeAssignment->location->location_code ?? '' }}</small>
            </div>
            <div>
                <span class="label">নির্ধারিত সময়সূচি</span>
                <b>{{ substr($activeAssignment->start_time, 0, 5) }} - {{ $activeAssignment->end_time ? substr($activeAssignment->end_time, 0, 5) : '—' }}</b>
            </div>
            <div>
                <span class="label">বর্তমান অবস্থা</span>
                <span class="pill {{ $activeAssignment->status_badge_class }}">
                    <span class="dot"></span>{{ $activeAssignment->status_label }}
                </span>
            </div>
        </div>

        <div class="section-gap">
            <span class="label">কাজের অগ্রগতি নিরীক্ষা</span>
            <div class="progress">
                <span style="width: {{ $activeAssignment->status === 'completed' ? '100%' : ($activeAssignment->status === 'in_progress' ? '65%' : ($activeAssignment->status === 'assigned' ? '5%' : '20%')) }}"></span>
            </div>
            <small style="color:var(--muted);display:block;margin-top:6px;font-size:11px;">
                @if($activeAssignment->status === 'in_progress')
                    🟢 মাঠ পর্যায়ে কাজ বর্তমানে চলমান রয়েছে
                @elseif($activeAssignment->status === 'completed')
                    ✓ কাজ সফলভাবে যাচাই ও সম্পন্ন হয়েছে
                @elseif($activeAssignment->status === 'assigned')
                    ⏱ কৃষক মোবাইল অ্যাপ থেকে কাজ শুরু করলে স্বয়ংক্রিয়ভাবে 'চলমান' হবে
                @else
                    স্ট্যাটাস: {{ $activeAssignment->status_label }}
                @endif
            </small>
        </div>

        <div class="notice warn section-gap">
            নির্ধারিত সময় শেষ হওয়ার পরও কৃষক কাজ জমা দিতে পারবেন; তখন সাবমিশনটি <b>Late</b> হিসেবে চিহ্নিত থাকবে।
        </div>
    </section>

    <section class="card">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">✓</span>
                <div>
                    <h2>যাচাই ও সুরক্ষা মানদণ্ড</h2>
                    <div class="sub">বৈধ সাবমিশন নিশ্চিতকরণের সিস্টেম শর্ত</div>
                </div>
            </div>
        </div>

        <div class="rule">
            <span>
                <b>GPS জিও-ফেন্স বাফার</b>
                <div class="sub">নির্ধারিত ব্যাসার্ধ: {{ $activeAssignment->location->geofence_radius_meters ?? 20 }} মিটারের মধ্যে উপস্থিতি</div>
            </span>
            @if($activeAssignment->taskTemplate->require_geofence ?? true)
                <span class="pill"><span class="dot"></span>চালু</span>
            @else
                <span class="pill red"><span class="dot"></span>বন্ধ</span>
            @endif
        </div>

        <div class="rule">
            <span>
                <b>রিয়েল-টাইম ক্যামেরা ছবি</b>
                <div class="sub">প্রয়োজনীয় ছবি: {{ $activeAssignment->taskTemplate->required_photo_count ?? 1 }}টি (সরাসরি তোলা বাধ্যতামূলক)</div>
            </span>
            @if($activeAssignment->taskTemplate->require_realtime_photo ?? true)
                <span class="pill"><span class="dot"></span>বাধ্যতামূলক</span>
            @else
                <span class="pill blue"><span class="dot"></span>ঐচ্ছিক</span>
            @endif
        </div>

        <div class="rule">
            <span>
                <b>গ্যালারি থেকে ছবি আপলোড</b>
                <div class="sub">পূর্বের সংরক্ষিত ফাইল আপলোড সুরক্ষা</div>
            </span>
            @if($activeAssignment->taskTemplate->allow_gallery_upload ?? false)
                <span class="pill blue"><span class="dot"></span>অনুমোদিত</span>
            @else
                <span class="pill red"><span class="dot"></span>ব্লক করা</span>
            @endif
        </div>

        <div class="rule">
            <span>
                <b>সময়সীমা পর্যবেক্ষণ</b>
                <div class="sub">আজকের নির্ধারিত সময়সূচি ট্র্যাকিং</div>
            </span>
            <span class="pill blue"><span class="dot"></span>সক্রিয়</span>
        </div>

        <div class="notice red section-gap">
            ক্যামেরা খোলা যাবে, কিন্তু GPS/জিও-ফেন্স শর্ত পূরণ না হলে বৈধ কাজ সাবমিট করা যাবে না।
        </div>
    </section>
@else
    <section class="card">
        <div class="empty">
            বর্তমানে কোনো চলমান অপারেশন নেই।
        </div>
    </section>
@endif

