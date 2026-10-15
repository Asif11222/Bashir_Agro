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

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(420px, 1fr));gap:20px;">
            
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

        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
            <a href="{{ route('admin.dashboard') }}" class="btn soft">বাতিল করুন</a>
            <button type="submit" class="btn primary" style="padding:10px 24px;font-size:14px;font-weight:600;">
                💾 সেটিংস সংরক্ষণ করুন
            </button>
        </div>
    </form>
</div>
@endsection
