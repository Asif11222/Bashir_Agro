@extends('layouts.admin')

@section('title', 'সিস্টেম সেটিংস — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>সিস্টেম কনফিগারেশন সেটিংস</h1>
            <p>খামার পরিচালনা, কাজের ডিফল্ট সময়সূচি, জিপিএস জিও-ফেন্স ও সিস্টেম আচরণ নিয়ন্ত্রণ করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.audit', ['module' => 'settings']) }}" class="btn soft">
                📜 সেটিংস অডিট হিস্টোরি
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="border-left:4px solid var(--red);background:#ffebee;margin-bottom:16px;padding:14px 18px;">
            <b style="color:var(--red);">অনুগ্রহ করে নিচের ভুলগুলো সংশোধন করুন:</b>
            <ul style="margin:8px 0 0 16px;color:var(--text);font-size:13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section id="mobile-headers" class="card" style="margin-bottom:22px;padding:20px;">
        <div class="card-title"><div class="title-left"><span class="iconbox">📱</span><div>
            <h2>মোবাইল অ্যাপ হেডার (Mobile Header Appearance)</h2>
            <div class="sub">প্রতিটি স্ক্রিনের ব্যানার ছবি, লেখা ও সীমিত টেক্সট স্টাইল পরিবর্তন করুন। নতুন APK প্রয়োজন নেই।</div>
        </div></div></div>
        @foreach($mobileHeaders as $screenKey => $header)
            <details style="padding:14px 0;border-top:1px solid var(--line);" {{ $loop->first ? 'open' : '' }}>
                <summary style="cursor:pointer;font-weight:700;">{{ $header['label'] }}</summary>
                <form method="POST" action="{{ route('admin.settings.mobile-headers.update', $screenKey) }}" enctype="multipart/form-data" style="padding-top:14px;">
                    @csrf @method('PUT')
                    @if($header['image_url'])
                        <img src="{{ $header['image_url'] }}" alt="বর্তমান হেডার" style="width:100%;max-width:600px;max-height:190px;object-fit:cover;border-radius:12px;display:block;margin-bottom:10px;">
                    @endif
                    <div class="branding-upload-grid" style="gap:14px;">
                        <div><label class="label">নতুন হেডার ছবি (PNG/JPG/WebP, 3MB)</label>
                            <input class="input" type="file" name="image" accept="image/png,image/jpeg,image/webp" style="width:100%;">
                            @if($header['image_path'])<label style="display:block;margin-top:8px;"><input type="checkbox" name="remove_image" value="1"> ছবি সরিয়ে ডিফল্ট ডিজাইন ব্যবহার করুন</label>@endif
                        </div>
                        <div><label class="label">হেডার শিরোনাম</label>
                            <input class="input" type="text" name="title" value="{{ $header['title'] }}" maxlength="90" required style="width:100%;">
                        </div>
                        <div><label class="label">সাবটাইটেল</label>
                            <input class="input" type="text" name="subtitle" value="{{ $header['subtitle'] }}" maxlength="150" style="width:100%;">
                        </div>
                        <div><label class="label">লেখার রঙ</label>
                            <input type="color" name="title_color" value="{{ $header['title_color'] }}" style="height:40px;width:65px;cursor:pointer;">
                        </div>
                        <div><label class="label">শিরোনামের আকার</label>
                            <select name="font_size" class="input" style="width:100%;">
                                @foreach([16,18,20,22,24,26] as $sz)
                                    <option value="{{ $sz }}" @selected((int)$header['font_size'] === $sz)>{{ $sz }}sp</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="label">লেখার অবস্থান</label>
                            <select name="alignment" class="input" style="width:100%;">
                                @foreach(['left'=>'বামে', 'center'=>'মাঝে', 'right'=>'ডানে'] as $v=>$label)
                                    <option value="{{ $v }}" @selected($header['alignment'] === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="label">ছবির উপরের অন্ধকার আবরণ</label>
                            <select name="overlay" class="input" style="width:100%;">
                                @foreach(['0.15'=>'হালকা','0.25'=>'কম','0.35'=>'মাঝারি','0.5'=>'গাঢ়','0.7'=>'বেশি'] as $v=>$label)
                                    <option value="{{ $v }}" @selected((float)$header['overlay'] === (float)$v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="shadow" value="1" @checked($header['shadow'])> লেখার ছায়া (Text Shadow)</label></div>
                    </div>
                    <button class="btn primary" type="submit" style="margin-top:14px;">এই হেডার সংরক্ষণ করুন</button>
                </form>
            </details>
        @endforeach
    </section>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="settings-grid">
            
            {{-- 0. Branding & Identity Group --}}
            <section class="card" style="grid-column: 1 / -1;">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox" style="background:#e8f5e9;color:#2e7d32;">🎨</span>
                        <div>
                            <h2>ব্র্যান্ডিং ও ভিজ্যুয়াল পরিচয় (Branding & Identity)</h2>
                            <div class="sub">অ্যাডমিন প্যানেলের প্রধান সাইট লোগো ও ব্রাউজার ট্যাবের ফেভিকন আপলোড ও পরিবর্তন করুন</div>
                        </div>
                    </div>
                </div>

                <div class="branding-upload-grid">
                    
                    {{-- Logo Upload --}}
                    <div style="border:1px solid var(--line);border-radius:12px;padding:16px;background:#fbfdfc;">
                        <label class="label" style="font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                            <span>সাইট লোগো (Site Logo)</span>
                            <span class="pill" style="font-size:10px;padding:2px 8px;">প্রস্তাবিত: PNG / JPG / WebP</span>
                        </label>

                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
                            <div id="logo-preview-box" style="width:72px;height:72px;border-radius:14px;border:2px dashed #b9d8c6;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                                @php $currentLogo = \App\Models\Setting::get('site_logo'); @endphp
                                @if($currentLogo)
                                    <img src="{{ asset('storage/' . $currentLogo) }}" id="logo-preview-img" alt="লোগো" style="max-width:100%;max-height:100%;object-fit:contain;padding:4px;">
                                @else
                                    <span id="logo-default-icon" style="font-size:32px;">🌱</span>
                                    <img src="" id="logo-preview-img" alt="লোগো" style="display:none;max-width:100%;max-height:100%;object-fit:contain;padding:4px;">
                                @endif
                            </div>

                            <div style="flex:1;">
                                <input type="file" id="site_logo" name="site_logo" accept="image/png,image/jpeg,image/webp" class="input" style="padding:6px 10px;font-size:12px;width:100%;" onchange="previewBrandingImage(this, 'logo-preview-img', 'logo-default-icon')">
                                <small style="color:var(--muted);display:block;margin-top:4px;font-size:11px;">
                                    সাইডবার ও লগইন পেজে প্রদর্শিত হবে। সর্বোচ্চ সাইজ: 2MB
                                </small>
                            </div>
                        </div>

                        @if($currentLogo)
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--red);cursor:pointer;margin-top:4px;">
                                <input type="checkbox" name="remove_site_logo" value="1">
                                <span>বর্তমান লোগো মুছে ডিফল্ট আইকন (🌱) ব্যবহার করুন</span>
                            </label>
                        @endif
                    </div>

                    {{-- Favicon Upload --}}
                    <div style="border:1px solid var(--line);border-radius:12px;padding:16px;background:#fbfdfc;">
                        <label class="label" style="font-weight:700;display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                            <span>ফেভিকন (Favicon)</span>
                            <span class="pill" style="font-size:10px;padding:2px 8px;">প্রস্তাবিত: ICO / PNG (32x32)</span>
                        </label>

                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
                            <div id="favicon-preview-box" style="width:72px;height:72px;border-radius:14px;border:2px dashed #b9d8c6;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                                @php $currentFavicon = \App\Models\Setting::get('site_favicon'); @endphp
                                @if($currentFavicon)
                                    <img src="{{ asset('storage/' . $currentFavicon) }}" id="favicon-preview-img" alt="ফেভিকন" style="width:36px;height:36px;object-fit:contain;">
                                @else
                                    <span id="favicon-default-icon" style="font-size:28px;">🌱</span>
                                    <img src="" id="favicon-preview-img" alt="ফেভিকন" style="display:none;width:36px;height:36px;object-fit:contain;">
                                @endif
                            </div>

                            <div style="flex:1;">
                                <input type="file" id="site_favicon" name="site_favicon" accept=".ico,image/x-icon,image/png,image/webp,image/jpeg" class="input" style="padding:6px 10px;font-size:12px;width:100%;" onchange="previewBrandingImage(this, 'favicon-preview-img', 'favicon-default-icon')">
                                <small style="color:var(--muted);display:block;margin-top:4px;font-size:11px;">
                                    ব্রাউজার ট্যাব ও বুকমার্কে প্রদর্শিত ছোট আইকন। সর্বোচ্চ সাইজ: 1MB
                                </small>
                            </div>
                        </div>

                        @if($currentFavicon)
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--red);cursor:pointer;margin-top:4px;">
                                <input type="checkbox" name="remove_site_favicon" value="1">
                                <span>বর্তমান ফেভিকন মুছে ডিফল্ট ফেভিকন ব্যবহার করুন</span>
                            </label>
                        @endif
                    </div>

                </div>
            </section>

            <section class="card" style="grid-column:1 / -1;padding:18px 20px;">
                <h2 style="margin:0 0 12px;">ব্র্যান্ডের পরিচিতি ও অ্যাডমিন প্রোফাইল</h2>
                <div class="branding-upload-grid">
                    <div><label class="label" for="brand_tagline">ব্র্যান্ডের ট্যাগলাইন</label>
                        <input class="input" id="brand_tagline" name="brand_tagline" maxlength="100" style="width:100%;" value="{{ old('brand_tagline', \App\Models\Setting::get('brand_tagline', 'কৃষি ব্যবস্থাপনা প্ল্যাটফর্ম')) }}">
                    </div>
                    <div><label class="label" for="account_photo">আমার প্রোফাইল ছবি (2MB)</label>
                        @if(auth()->user()?->profile_photo_path)
                            <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="প্রোফাইল" style="width:52px;height:52px;border-radius:50%;object-fit:cover;display:block;margin:6px 0;">
                        @endif
                        <input class="input" id="account_photo" type="file" name="account_photo" accept="image/png,image/jpeg,image/webp" style="width:100%;">
                    </div>
                </div>
            </section>

            {{-- 1. General Settings Group --}}
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">🌱</span>
                        <div>
                            <h2>সাধারণ খামার তথ্য (General)</h2>
                            <div class="sub">খামারের নাম ও অফিসিয়াল যোগাযোগের তথ্য</div>
                        </div>
                    </div>
                </div>

                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label class="label" for="farm_name" style="font-weight:600;display:block;margin-bottom:6px;">খামারের নাম <span style="color:var(--red)">*</span></label>
                        <input type="text" id="farm_name" name="farm_name" class="input" style="width:100%;" 
                               value="{{ old('farm_name', \App\Models\Setting::get('farm_name', 'আমার ফার্ম')) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">সিস্টেম হেডার, রিপোর্ট ও ড্যাশবোর্ডে প্রদর্শিত প্রাতিষ্ঠানিক নাম</small>
                    </div>

                    <div>
                        <label class="label" for="company_phone" style="font-weight:600;display:block;margin-bottom:6px;">যোগাযোগের ফোন নম্বর <span style="color:var(--red)">*</span></label>
                        <input type="text" id="company_phone" name="company_phone" class="input" style="width:100%;" 
                               value="{{ old('company_phone', \App\Models\Setting::get('company_phone', '+880 1700-000000')) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">কৃষক সহায়তা ও অফিসিয়াল হেল্পলাইন</small>
                    </div>

                    <div>
                        <label class="label" for="company_email" style="font-weight:600;display:block;margin-bottom:6px;">অফিসিয়াল ইমেইল <span style="color:var(--red)">*</span></label>
                        <input type="email" id="company_email" name="company_email" class="input" style="width:100%;" 
                               value="{{ old('company_email', \App\Models\Setting::get('company_email', 'contact@bashiragro.com')) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">সিস্টেম বার্তা ও নোটিফিকেশন প্রেরক ইমেইল</small>
                    </div>

                    <div>
                        <label class="label" for="company_address" style="font-weight:600;display:block;margin-bottom:6px;">খামারের প্রধান ঠিকানা <span style="color:var(--red)">*</span></label>
                        <input type="text" id="company_address" name="company_address" class="input" style="width:100%;" 
                               value="{{ old('company_address', \App\Models\Setting::get('company_address', 'পাবনা, রাজশাহী বিভাগ, বাংলাদেশ')) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">প্রধান অফিস বা খামারের ভৌগোলিক ঠিকানা</small>
                    </div>
                </div>
            </section>

            {{-- 2. Task Configuration Group --}}
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox" style="background:#e3f2fd;color:#1565c0;">▣</span>
                        <div>
                            <h2>কাজের ডিফল্ট কনফিগারেশন (Tasks)</h2>
                            <div class="sub">নতুন কাজের ডিফল্ট অগ্রাধিকার ও আনুমানিক সময়সীমা</div>
                        </div>
                    </div>
                </div>

                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label class="label" for="default_task_priority" style="font-weight:600;display:block;margin-bottom:6px;">ডিফল্ট কাজের অগ্রাধিকার <span style="color:var(--red)">*</span></label>
                        @php $currentPriority = old('default_task_priority', \App\Models\Setting::get('default_task_priority', 'normal')); @endphp
                        <select id="default_task_priority" name="default_task_priority" class="input" style="width:100%;">
                            <option value="normal" {{ $currentPriority === 'normal' ? 'selected' : '' }}>সাধারণ (Normal)</option>
                            <option value="important" {{ $currentPriority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ (Important)</option>
                            <option value="urgent" {{ $currentPriority === 'urgent' ? 'selected' : '' }}>জরুরি (Urgent)</option>
                        </select>
                        <small style="color:var(--muted);display:block;margin-top:4px;">নতুন কাজ তৈরির সময় প্রাথমিক অগ্রাধিকার স্তর</small>
                    </div>

                    <div>
                        <label class="label" for="default_task_duration" style="font-weight:600;display:block;margin-bottom:6px;">কাজের গড় আনুমানিক সময়কাল (মিনিটে) <span style="color:var(--red)">*</span></label>
                        <input type="number" id="default_task_duration" name="default_task_duration" class="input" style="width:100%;" min="10" max="1440" 
                               value="{{ old('default_task_duration', \App\Models\Setting::get('default_task_duration', 120)) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">যেমন: ১২০ মিনিট (২ ঘণ্টা)</small>
                    </div>
                </div>
            </section>

            {{-- 3. Farm Location & Geofence Group --}}
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox" style="background:#fff8e1;color:#f57f17;">⌖</span>
                        <div>
                            <h2>ফার্ম লোকেশন ও জিও-ফেন্স (Location)</h2>
                            <div class="sub">জিপিএস ভিত্তিক উপস্থিতি ও পরিধি নির্ধারণ</div>
                        </div>
                    </div>
                </div>

                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label class="label" for="default_geofence_radius" style="font-weight:600;display:block;margin-bottom:6px;">ডিফল্ট জিও-ফেন্স ব্যাসার্ধ (মিটারে) <span style="color:var(--red)">*</span></label>
                        <input type="number" id="default_geofence_radius" name="default_geofence_radius" class="input" style="width:100%;" min="5" max="1000" 
                               value="{{ old('default_geofence_radius', \App\Models\Setting::get('default_geofence_radius', 20)) }}" required>
                        <small style="color:var(--muted);display:block;margin-top:4px;">কৃষক মাঠে উপস্থিত হয়ে কাজ জমা দেওয়ার জন্য গ্রহণযোগ্য সর্বোচ্চ দূরত্ব (মিটার)</small>
                    </div>
                </div>
            </section>

            {{-- 4. System Behavior Group --}}
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox" style="background:#f3e5f5;color:#7b1fa2;">⚙</span>
                        <div>
                            <h2>সিস্টেম ও ডিসপ্লে (System)</h2>
                            <div class="sub">তালিকায় আইটেম প্রদর্শন ও তারিখের বিন্যাস</div>
                        </div>
                    </div>
                </div>

                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label class="label" for="pagination_size" style="font-weight:600;display:block;margin-bottom:6px;">তালিকায় প্রতি পৃষ্ঠায় আইটেম সংখ্যা <span style="color:var(--red)">*</span></label>
                        @php $currentPagination = (int) old('pagination_size', \App\Models\Setting::get('pagination_size', 20)); @endphp
                        <select id="pagination_size" name="pagination_size" class="input" style="width:100%;">
                            <option value="10" {{ $currentPagination === 10 ? 'selected' : '' }}>১০ টি</option>
                            <option value="15" {{ $currentPagination === 15 ? 'selected' : '' }}>১৫ টি</option>
                            <option value="20" {{ $currentPagination === 20 ? 'selected' : '' }}>২০ টি (প্রস্তাবিত)</option>
                            <option value="25" {{ $currentPagination === 25 ? 'selected' : '' }}>২৫ টি</option>
                            <option value="50" {{ $currentPagination === 50 ? 'selected' : '' }}>৫০ টি</option>
                            <option value="100" {{ $currentPagination === 100 ? 'selected' : '' }}>১০০ টি</option>
                        </select>
                        <small style="color:var(--muted);display:block;margin-top:4px;">কৃষক, কাজ, লোকেশন ও অডিট তালিকায় একসাথে কতগুলো রেকর্ড দৃশ্যমান হবে</small>
                    </div>

                    <div>
                        <label class="label" for="date_format" style="font-weight:600;display:block;margin-bottom:6px;">তারিখ প্রদর্শনের ফরম্যাট <span style="color:var(--red)">*</span></label>
                        @php $currentDateFormat = old('date_format', \App\Models\Setting::get('date_format', 'Y-m-d')); @endphp
                        <select id="date_format" name="date_format" class="input" style="width:100%;">
                            <option value="Y-m-d" {{ $currentDateFormat === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD (যেমন: 2026-09-30)</option>
                            <option value="d/m/Y" {{ $currentDateFormat === 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY (যেমন: 30/09/2026)</option>
                            <option value="d-m-Y" {{ $currentDateFormat === 'd-m-Y' ? 'selected' : '' }}>DD-MM-YYYY (যেমন: 30-09-2026)</option>
                            <option value="m/d/Y" {{ $currentDateFormat === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY (যেমন: 09/30/2026)</option>
                        </select>
                        <small style="color:var(--muted);display:block;margin-top:4px;">সিস্টেম জুড়ে তারিখ প্রদর্শনের জন্য ডিফল্ট স্টাইল</small>
                    </div>
                </div>
            </section>

        </div>

        <div class="sticky-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn">বাতিল করুন</a>
            <button type="submit" class="btn primary">
                💾 সেটিংস সংরক্ষণ করুন
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewBrandingImage(input, previewImgId, defaultIconId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById(previewImgId);
            const defaultIcon = document.getElementById(defaultIconId);
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            if (defaultIcon) {
                defaultIcon.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}
</script>
@endpush
