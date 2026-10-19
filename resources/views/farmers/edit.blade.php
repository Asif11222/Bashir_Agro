@extends('layouts.admin')

@section('title', "কৃষক তথ্য সম্পাদনা — {$farmer->full_name}")

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কৃষক তথ্য সম্পাদনা</h1>
            <p>কৃষক আইডি: <span style="font-weight:700;color:var(--g800)">{{ $farmer->farmer_code }}</span> • {{ $farmer->full_name }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.farmers.show', $farmer) }}" class="btn soft">বিবরণ দেখুন</a>
            <a href="{{ route('admin.farmers.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.farmers.update', $farmer) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-layout-2col">
            <div class="form-main-col">
                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">♟</span>
                            <div>
                                <h2>প্রাথমিক তথ্য</h2>
                                <div class="sub">কৃষকের পরিচিতি ও যোগাযোগের তথ্য হালনাগাদ করুন</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="label">পূর্ণ নাম <span class="req">*</span></span>
                            <input class="input" type="text" name="full_name" value="{{ old('full_name', $farmer->full_name) }}" required>
                            @error('full_name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">মোবাইল নম্বর <span class="req">*</span></span>
                            <input class="input" type="text" name="phone" value="{{ old('phone', $farmer->phone) }}" required>
                            @error('phone')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">কৃষক আইডি</span>
                            <input class="input" type="text" value="{{ $farmer->farmer_code }}" disabled style="background:#f4f7f5;color:var(--muted)">
                        </label>

                        <label class="field">
                            <span class="label">জাতীয় পরিচয়পত্র নম্বর (ঐচ্ছিক)</span>
                            <input class="input" type="text" name="nid" value="{{ old('nid', $farmer->nid) }}">
                            @error('nid')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">জন্ম তারিখ</span>
                            <input class="input" type="date" name="date_of_birth" value="{{ old('date_of_birth', $farmer->date_of_birth?->format('Y-m-d')) }}">
                            @error('date_of_birth')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">লিঙ্গ</span>
                            <select class="select" name="gender">
                                <option value="পুরুষ" {{ old('gender', $farmer->gender) === 'পুরুষ' ? 'selected' : '' }}>পুরুষ</option>
                                <option value="নারী" {{ old('gender', $farmer->gender) === 'নারী' ? 'selected' : '' }}>নারী</option>
                                <option value="অন্যান্য" {{ old('gender', $farmer->gender) === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                            </select>
                            @error('gender')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field full">
                            <span class="label">বর্তমান ঠিকানা <span class="req">*</span></span>
                            <textarea class="textarea" name="address" required style="min-height:70px;">{{ old('address', $farmer->address) }}</textarea>
                            @error('address')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>

                    <div class="section-gap">
                        <span class="label">প্রধান কার্যক্রম <span class="req">*</span></span>
                        <input type="hidden" name="primary_activity" id="primary_activity_input" value="{{ old('primary_activity', $farmer->primary_activity) }}">
                        <div class="choices" data-input="primary_activity_input">
                            @php $currentAct = old('primary_activity', $farmer->primary_activity); @endphp
                            <button type="button" class="choice {{ $currentAct === 'সবজি চাষ' ? 'active' : '' }}" data-value="সবজি চাষ">সবজি চাষ</button>
                            <button type="button" class="choice {{ $currentAct === 'মাছ চাষ' ? 'active' : '' }}" data-value="মাছ চাষ">মাছ চাষ</button>
                            <button type="button" class="choice {{ $currentAct === 'গরু পালন' ? 'active' : '' }}" data-value="গরু পালন">গরু পালন</button>
                            <button type="button" class="choice {{ $currentAct === 'মিশ্র কৃষি' ? 'active' : '' }}" data-value="মিশ্র কৃষি">মিশ্র কৃষি</button>
                            <button type="button" class="choice {{ $currentAct === 'অন্যান্য' ? 'active' : '' }}" data-value="অন্যান্য">অন্যান্য</button>
                        </div>
                        @error('primary_activity')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-grid section-gap">
                        <label class="field">
                            <span class="label">অভিজ্ঞতা (বছর)</span>
                            <input class="input" type="number" name="experience_years" min="0" max="70" value="{{ old('experience_years', $farmer->experience_years) }}">
                            @error('experience_years')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">প্রধান কাজের এলাকা</span>
                            <select class="select" name="farm_location_id">
                                <option value="">নির্বাচন করুন</option>
                                @php $currentLocId = old('farm_location_id', $farmer->farmLocations->first()?->id); @endphp
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}" {{ $currentLocId == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->location_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('farm_location_id')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="card section-gap">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">☎</span>
                            <div>
                                <h2>জরুরি যোগাযোগ</h2>
                                <div class="sub">জরুরি প্রয়োজনে যোগাযোগের দায়িত্বপ্রাপ্ত ব্যক্তি</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="label">নাম <span class="req">*</span></span>
                            <input class="input" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $farmer->emergencyContact?->name) }}" required>
                            @error('emergency_contact_name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">মোবাইল <span class="req">*</span></span>
                            <input class="input" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $farmer->emergencyContact?->phone) }}" required>
                            @error('emergency_contact_phone')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field full">
                            <span class="label">সম্পর্ক <span class="req">*</span></span>
                            <select class="select" name="emergency_contact_relationship" required>
                                @php $rel = old('emergency_contact_relationship', $farmer->emergencyContact?->relationship); @endphp
                                <option value="পিতা/মাতা" {{ $rel === 'পিতা/মাতা' ? 'selected' : '' }}>পিতা/মাতা</option>
                                <option value="স্বামী/স্ত্রী" {{ $rel === 'স্বামী/স্ত্রী' ? 'selected' : '' }}>স্বামী/স্ত্রী</option>
                                <option value="ভাই/বোন" {{ $rel === 'ভাই/বোন' ? 'selected' : '' }}>ভাই/বোন</option>
                                <option value="সন্তান" {{ $rel === 'সন্তান' ? 'selected' : '' }}>সন্তান</option>
                                <option value="অন্যান্য" {{ $rel === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                            </select>
                            @error('emergency_contact_relationship')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>
                </section>

                <div class="sticky-actions" style="margin-top:20px;">
                    <a href="{{ route('admin.farmers.index') }}" class="btn">বাতিল</a>
                    <button type="submit" class="btn primary" style="padding:10px 24px;font-size:14px;">তথ্য হালনাগাদ করুন</button>
                </div>
            </div>

            <div class="form-side-col">
                <section class="card sticky-side-card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">👤</span>
                            <div>
                                <h3 style="font-size:15px;margin:0;color:var(--ink)">প্রোফাইল ছবি ও স্ট্যাটাস</h3>
                                <div class="sub">কৃষক আইডি: {{ $farmer->farmer_code }}</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;align-items:center;padding:12px 0 16px;border-bottom:1px solid var(--line);">
                        @if ($farmer->profile_photo_path)
                            <img src="{{ asset('storage/' . $farmer->profile_photo_path) }}" alt="{{ $farmer->full_name }}" style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid #d4eedd;box-shadow:0 4px 12px rgba(8,120,63,0.15);">
                        @else
                            <div style="width:84px;height:84px;border-radius:50%;background:linear-gradient(135deg,#18b85e,#076238);color:#fff;display:grid;place-items:center;font-size:32px;font-weight:700;">
                                {{ mb_substr($farmer->full_name, 0, 1) }}
                            </div>
                        @endif
                        <b style="font-size:15px;margin-top:10px;color:var(--ink)">{{ $farmer->full_name }}</b>
                        <small style="color:var(--muted)">{{ $farmer->phone }}</small>
                    </div>

                    <div style="margin-top:16px;">
                        <label class="field">
                            <span class="label">নতুন ছবি আপলোড</span>
                            <input class="input" type="file" name="profile_photo" accept="image/*">
                            @error('profile_photo')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>

                    <div style="margin-top:16px;border-top:1px solid var(--line);padding-top:16px;">
                        <div class="card-title" style="margin-bottom:8px;">
                            <div class="title-left">
                                <span class="iconbox" style="width:30px;height:30px;font-size:14px;">🔒</span>
                                <div>
                                    <h4 style="font-size:13px;margin:0;color:var(--ink)">অ্যাপ পাসওয়ার্ড</h4>
                                </div>
                            </div>
                        </div>
                        <div class="password-field-wrap">
                            <input class="input" type="password" name="password" id="edit_farmer_password" placeholder="নতুন পাসওয়ার্ড (ঐচ্ছিক)" minlength="6">
                            <button type="button" class="password-toggle-btn" id="toggle_edit_password_btn" data-toggle-password="edit_farmer_password" title="পাসওয়ার্ড দেখুন" aria-label="পাসওয়ার্ড দেখুন">
                                <svg class="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-slash-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;"><path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                            </button>
                        </div>
                        <small style="color:var(--muted);display:block;margin-top:4px;font-size:11px;">পাসওয়ার্ড পরিবর্তন না করতে চাইলে খালি রাখুন।</small>
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-top:16px;border-top:1px solid var(--line);padding-top:16px;">
                        <div class="rule" style="margin-bottom:12px;">
                            <span>
                                <b>মোবাইল অ্যাপ অ্যাক্সেস</b>
                                <div class="sub">অ্যাপে লগইন সুবিধা</div>
                            </span>
                            <input type="hidden" name="app_access_enabled" id="app_access_enabled" value="{{ old('app_access_enabled', $farmer->app_access_enabled ? '1' : '0') }}">
                            <button type="button" class="switch {{ old('app_access_enabled', $farmer->app_access_enabled ? '1' : '0') == '1' ? 'on' : '' }}" data-switch data-input="app_access_enabled"></button>
                        </div>

                        <div class="rule">
                            <span>
                                <b>অ্যাকাউন্ট অবস্থা</b>
                                <div class="sub">সক্রিয়/নিষ্ক্রিয় স্ট্যাটাস</div>
                            </span>
                            <select class="select" name="status" style="width: auto; min-width: 120px;">
                                <option value="active" {{ old('status', $farmer->status) === 'active' ? 'selected' : '' }}>সক্রিয় (Active)</option>
                                <option value="inactive" {{ old('status', $farmer->status) === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </form>
</div>
@endsection
