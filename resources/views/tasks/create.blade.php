@extends('layouts.admin')

@section('title', 'নতুন কাজ তৈরি করুন — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>নতুন কাজ তৈরি করুন</h1>
            <p>পুনরায় ব্যবহারযোগ্য কাজের টেমপ্লেট ও যাচাইকরণের শর্তাবলী নির্ধারণ করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.tasks.index') }}" class="btn">← কাজ তালিকায় ফিরুন</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.tasks.store') }}">
        @csrf

        <div class="form-layout-2col">
            <div class="form-main-col">
                <section class="card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">▣</span>
                            <div>
                                <h2>কাজের মূল বিবরণ</h2>
                                <div class="sub">কাজের শিরোনাম, শ্রেণি ও নির্দেশনা</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="label">কাজের নাম <span class="req">*</span></span>
                            <input class="input" type="text" name="name" value="{{ old('name') }}" placeholder="যেমন: জমিতে সার প্রয়োগ" required>
                            @error('name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">কাজ কোড</span>
                            <input class="input" type="text" value="স্বয়ংক্রিয়ভাবে তৈরি হবে (যেমন: TASK-000001)" disabled style="background:#f4f7f5;color:var(--muted)">
                        </label>

                        <label class="field">
                            <span class="label">কাজের শ্রেণি <span class="req">*</span></span>
                            <select class="select" name="category" required>
                                <option value="সবজি চাষ" {{ old('category') === 'সবজি চাষ' ? 'selected' : '' }}>সবজি চাষ</option>
                                <option value="মাছ চাষ" {{ old('category') === 'মাছ চাষ' ? 'selected' : '' }}>মাছ চাষ</option>
                                <option value="গরু পালন" {{ old('category') === 'গরু পালন' ? 'selected' : '' }}>গরু পালন</option>
                                <option value="মিশ্র কৃষি" {{ old('category') === 'মিশ্র কৃষি' ? 'selected' : '' }}>মিশ্র কৃষি</option>
                                <option value="অন্যান্য" {{ old('category') === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                            </select>
                            @error('category')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">সংক্ষিপ্ত ব্যাখ্যা (ঐচ্ছিক)</span>
                            <input class="input" type="text" name="description" value="{{ old('description') }}" placeholder="কাজের উদ্দেশ্য বা সংক্ষিপ্ত সারসংক্ষেপ">
                            @error('description')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <label class="field full">
                            <span class="label">বিস্তারিত নির্দেশনা <span class="req">*</span></span>
                            <textarea class="textarea" name="instructions" placeholder="কৃষকের জন্য করণীয় কাজের ধাপে ধাপে নির্দেশনা" required style="min-height:90px;">{{ old('instructions') }}</textarea>
                            @error('instructions')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="card section-gap">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox">✓</span>
                            <div>
                                <h2>প্রমাণ ও যাচাইকরণের শর্ত</h2>
                                <div class="sub">কৃষক কাজ সম্পন্ন করার সময় কী প্রমাণ জমা দেবেন</div>
                            </div>
                        </div>
                    </div>

                    <div class="rule">
                        <span>
                            <b>প্রয়োজনীয় ছবির সংখ্যা</b>
                            <div class="sub">সাবমিশনের জন্য সর্বনিম্ন ও সর্বোচ্চ গ্রহণযোগ্য ছবি (০-১০টি)</div>
                        </span>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <button type="button" class="btn" id="btn-minus" style="padding:6px 12px;font-size:16px;">−</button>
                            <b id="photo-count-display" style="font-size:17px;min-width:24px;text-align:center;">{{ old('required_photo_count', 3) }}</b>
                            <button type="button" class="btn" id="btn-plus" style="padding:6px 12px;font-size:16px;">+</button>
                            <input type="hidden" name="required_photo_count" id="required_photo_count" value="{{ old('required_photo_count', 3) }}">
                        </div>
                    </div>
                    @error('required_photo_count')
                        <div class="field-error">{{ $message }}</div>
                    @enderror

                    <div class="rule">
                        <span>
                            <b>রিয়েল-টাইম ক্যামেরা ছবি</b>
                            <div class="sub">ক্যামেরা চালু করে লাইভ ছবি তোলা বাধ্যতামূলক (গ্যালারি ব্লক)</div>
                        </span>
                        <input type="hidden" name="require_realtime_photo" id="require_realtime_photo" value="{{ old('require_realtime_photo', '1') }}">
                        <button type="button" class="switch {{ old('require_realtime_photo', '1') == '1' ? 'on' : '' }}" data-switch data-input="require_realtime_photo"></button>
                    </div>

                    <div class="rule">
                        <span>
                            <b>গ্যালারি থেকে আপলোডের অনুমতি</b>
                            <div class="sub">ডিভাইসের পুরনো ছবি নির্বাচন করার সুবিধা চালু করবেন কি না</div>
                        </span>
                        <input type="hidden" name="allow_gallery_upload" id="allow_gallery_upload" value="{{ old('allow_gallery_upload', '0') }}">
                        <button type="button" class="switch {{ old('allow_gallery_upload', '0') == '1' ? 'on' : '' }}" data-switch data-input="allow_gallery_upload"></button>
                    </div>

                    <div class="rule">
                        <span>
                            <b>জিও-ফেন্স যাচাই</b>
                            <div class="sub">নির্ধারিত ফার্মের সীমানার বাইরে থাকলে সাবমিশন আটকে দেওয়া হবে</div>
                        </span>
                        <input type="hidden" name="require_geofence" id="require_geofence" value="{{ old('require_geofence', '1') }}">
                        <button type="button" class="switch {{ old('require_geofence', '1') == '1' ? 'on' : '' }}" data-switch data-input="require_geofence"></button>
                    </div>

                    <div class="rule">
                        <span>
                            <b>টেমপ্লেটের অবস্থা</b>
                            <div class="sub">কাজের টেমপ্লেটটি বর্তমানে সক্রিয় হিসেবে তালিকায় থাকবে কি না</div>
                        </span>
                        <select class="select" name="status" style="width:auto;min-width:140px;">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>সক্রিয় (Active)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                        </select>
                    </div>
                </section>
            </div>

            <div class="form-side-col">
                <section class="card sticky-side-card">
                    <div class="card-title">
                        <div class="title-left">
                            <span class="iconbox" style="background:#eaf8ef;color:#08783f">ℹ</span>
                            <div>
                                <h3 style="font-size:15px;margin:0;color:var(--ink)">কাজের টেমপ্লেট নির্দেশিকা</h3>
                                <div class="sub">টেমপ্লেট ব্যবহার ও মাঠ পর্যায়ের নিয়মাবলী</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;font-size:13px;color:#3f5147;line-height:1.5;">
                        <div style="background:#f4fbf6;border:1px solid #d4eedd;padding:12px;border-radius:9px;">
                            <b style="color:#08783f;display:flex;align-items:center;gap:6px;">
                                <span>📷</span> ছবির সত্যতা যাচাই
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                রিয়েল-টাইম ক্যামেরা ছবি চালু রাখলে কৃষকের মোবাইল গ্যালারি থেকে পুরনো কোনো ছবি আপলোড করতে পারবে না।
                            </p>
                        </div>

                        <div style="background:#f0f7ff;border:1px solid #cbe2fe;padding:12px;border-radius:9px;">
                            <b style="color:#1d4ed8;display:flex;align-items:center;gap:6px;">
                                <span>🌐</span> জিও-ফেন্সিং নীতি
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                জিও-ফেন্স যাচাই সক্রিয় থাকলে কৃষক ফার্মের নির্ধারিত সীমানার মধ্যে না পৌঁছানো পর্যন্ত কাজ শুরু বা ছবি জমা দিতে পারবে না।
                            </p>
                        </div>

                        <div style="background:#fff9ed;border:1px solid #fce3b8;padding:12px;border-radius:9px;">
                            <b style="color:#a16207;display:flex;align-items:center;gap:6px;">
                                <span>⚡</span> লেট সাবমিশন ফ্ল্যাগ
                            </b>
                            <p style="margin:6px 0 0;font-size:12.5px;">
                                নির্ধারিত সময় পেরিয়ে গেলেও কৃষক সাবমিট করতে পারবেন, তবে সিস্টেম সেটিকে ড্যাশবোর্ডে <b>Late flag</b> হিসেবে চিহ্নিত করবে।
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="sticky-actions">
            <a href="{{ route('admin.tasks.index') }}" class="btn">বাতিল</a>
            <button type="submit" class="btn primary">কাজ সংরক্ষণ করুন</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const minusBtn = document.getElementById('btn-minus');
    const plusBtn = document.getElementById('btn-plus');
    const display = document.getElementById('photo-count-display');
    const input = document.getElementById('required_photo_count');

    if (minusBtn && plusBtn && display && input) {
        minusBtn.addEventListener('click', () => {
            let val = parseInt(input.value) || 0;
            if (val > 0) {
                val--;
                input.value = val;
                display.textContent = val;
            }
        });
        plusBtn.addEventListener('click', () => {
            let val = parseInt(input.value) || 0;
            if (val < 10) {
                val++;
                input.value = val;
                display.textContent = val;
            }
        });
    }
});
</script>
@endpush
