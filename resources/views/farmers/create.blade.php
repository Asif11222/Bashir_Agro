@extends('layouts.admin')

@section('title', 'নতুন কৃষক যোগ করুন — আমার ফার্ম')

@push('styles')
<style>
/* Farmer Creation Page Styling - Clean Modern UI */
.farmer-page-wrapper {
    max-width: 1440px;
    margin: 0 auto;
    padding: 24px 24px 60px;
}

.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 16px;
}
.breadcrumb-nav a {
    color: #2774c8;
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb-nav a:hover {
    text-decoration: underline;
}
.breadcrumb-nav .sep {
    color: #a0b2a8;
    font-size: 11px;
}
.breadcrumb-nav .curr {
    color: var(--ink);
    font-weight: 600;
}

.page-header-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}
.back-btn-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.back-btn-circle:hover {
    background: #f4fbf6;
    border-color: #72bf90;
    color: var(--g800);
}
.page-title-box h1 {
    font-size: 20px;
    font-weight: 750;
    color: var(--ink);
    margin: 0;
    line-height: 1.2;
}
.page-title-box p {
    font-size: 12px;
    color: var(--muted);
    margin: 3px 0 0;
}

/* Two-column layout */
.farmer-layout-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 18px;
    align-items: start;
}
@media (max-width: 1024px) {
    .farmer-layout-grid {
        grid-template-columns: 1fr;
    }
}

.farmer-main-column {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Cards */
.farmer-card {
    background: #fff;
    border: 1px solid #e2ebe6;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(16, 74, 43, 0.03);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.card-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-header-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eaf8ef;
    color: #08783f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.card-header-icon.lock {
    background: #08783f;
    color: #fff;
}
.card-header-title {
    font-size: 14px;
    font-weight: 750;
    color: #14241d;
    margin: 0;
    line-height: 1.2;
}
.card-header-sub {
    font-size: 11px;
    color: #6f7f77;
    margin-top: 2px;
}

/* Top Row: Personal Info (Expanded for clean multi-column alignment) + Profile Photo */
.top-split-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 250px;
    gap: 18px;
    align-items: stretch;
}
@media (max-width: 900px) {
    .top-split-row {
        grid-template-columns: 1fr;
    }
}

/* Personal Info Card & Form Grid Alignment */
.personal-info-card .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 16px;
    row-gap: 14px;
    align-items: start;
}
@media (max-width: 600px) {
    .personal-info-card .form-grid {
        grid-template-columns: 1fr;
    }
}

.personal-info-card .field {
    display: flex;
    flex-direction: column;
}

.personal-info-card .field > .label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 20px;
    margin: 0 0 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
}

@media (max-width: 600px) {
    .personal-info-card .field > .label {
        white-space: normal;
    }
}

.field-badge-auto {
    font-size: 10px;
    font-weight: 600;
    color: #08783f;
    background: #eaf8ef;
    border: 1px solid #c9edd5;
    padding: 1px 7px;
    border-radius: 10px;
    line-height: 1.4;
}

.opt-text {
    font-size: 11px;
    font-weight: 500;
    color: #7b8e84;
}

/* Profile Photo Card Alignment */
.photo-card {
    display: flex;
    flex-direction: column;
}
.photo-card .photo-upload-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 12px 0;
}

/* Input Icon Wrappers */
.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.input-wrap .field-icon {
    position: absolute;
    left: 11px;
    color: #8da096;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    font-size: 13px;
}
.input-wrap .input,
.input-wrap .select {
    padding-left: 35px;
    font-size: 12.5px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #d4e0d9;
    color: #1e3328;
}
.input-wrap .input:focus,
.input-wrap .select:focus,
.textarea-wrap .textarea:focus {
    border-color: #1aa35b;
    box-shadow: 0 0 0 3px rgba(26, 163, 91, 0.12);
}

.password-toggle-btn {
    position: absolute;
    right: 10px;
    background: transparent;
    border: 0;
    color: #6d8277;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
}
.password-toggle-btn:hover {
    color: #0b8f4b;
}

.textarea-wrap {
    position: relative;
}
.textarea-wrap .field-icon {
    position: absolute;
    left: 11px;
    top: 11px;
    color: #8da096;
    pointer-events: none;
    font-size: 13px;
}
.textarea-wrap .textarea {
    padding-left: 35px;
    padding-bottom: 22px;
    font-size: 12.5px;
    min-height: 65px;
    border-radius: 8px;
    border: 1px solid #d4e0d9;
}
.textarea-counter {
    position: absolute;
    right: 10px;
    bottom: 6px;
    font-size: 10px;
    color: #8fa398;
}

/* Photo Card Styles */
.photo-upload-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 10px 0;
}
.photo-avatar-ring {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: #dbe4df;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    cursor: pointer;
    overflow: visible;
}
.photo-avatar-preview {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: none;
}
.photo-avatar-placeholder {
    color: #8fa398;
    font-size: 40px;
}
.photo-camera-badge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #08783f;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    border: 2px solid #fff;
}
.photo-title {
    font-size: 13px;
    font-weight: 700;
    color: #14241d;
    margin-bottom: 2px;
}
.photo-subtitle {
    font-size: 11px;
    color: #6f7f77;
    margin-bottom: 12px;
}
.photo-divider {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 180px;
    margin: 6px 0 10px;
    color: #a2b4aa;
    font-size: 11px;
}
.photo-divider::before,
.photo-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e1ebe5;
}
.photo-divider span {
    padding: 0 8px;
}
.photo-choose-btn {
    border: 1px solid #c9d8d0;
    background: #fff;
    color: #1e3328;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.photo-choose-btn:hover {
    border-color: #08783f;
    background: #f4fbf6;
    color: #08783f;
}

/* Activity buttons */
.activity-choices {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.activity-btn {
    border: 1px solid #d4e0d9;
    background: #fff;
    color: #273b30;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.activity-btn:hover {
    border-color: #60bc86;
    background: #f7fcf9;
}
.activity-btn.active {
    background: #effbf3;
    border-color: #40b370;
    color: #08733d;
}

/* Bottom Split Row: Emergency Contact (50%) + Login Access (50%) */
.bottom-split-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
@media (max-width: 768px) {
    .bottom-split-row {
        grid-template-columns: 1fr;
    }
}

.clear-btn {
    border: 0;
    background: transparent;
    color: #8c9e94;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.15s;
}
.clear-btn:hover {
    background: #fbeee9;
    color: #df4540;
}

.access-rule-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0 2px;
    margin-top: 6px;
}

/* Sidebar Styles */
.farmer-sidebar {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Progress List */
.progress-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 4px;
}
.progress-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
}
.progress-item-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.progress-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #eaf8ef;
    color: #08783f;
    font-size: 11px;
    font-weight: 750;
    display: flex;
    align-items: center;
    justify-content: center;
}
.progress-num.completed {
    background: #08783f;
    color: #fff;
}
.progress-title {
    font-weight: 600;
    color: #1e3328;
}
.status-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 12px;
}
.status-pill.running {
    background: #e3f7ea;
    color: #08733d;
}
.status-pill.pending {
    background: #edf2ef;
    color: #798b81;
}
.status-pill.completed {
    background: #e3f7ea;
    color: #08733d;
}

/* Allocation Boxes */
.allocation-item {
    border: 1px dashed #d0ded6;
    background: #fbfdfc;
    border-radius: 10px;
    padding: 16px 12px;
    text-align: center;
    margin-bottom: 12px;
}
.allocation-item:last-child {
    margin-bottom: 0;
}
.allocation-icon-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #edf3f0;
    color: #63776d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-size: 15px;
}
.allocation-text {
    font-size: 12px;
    color: #5d7066;
    margin-bottom: 10px;
}
.allocation-btn {
    border: 1px solid #bed6c9;
    background: #fff;
    color: #077a41;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 650;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.allocation-btn:hover {
    background: #effbf3;
    border-color: #077a41;
}

/* Sidebar action buttons */
.sidebar-actions {
    display: flex;
    gap: 10px;
    align-items: stretch;
}
.draft-submit-btn {
    order: 1;
    flex: 1;
    border: 1px solid #bfd8cb;
    background: #eaf5ee;
    color: #096839;
    border-radius: 9px;
    padding: 11px 14px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.draft-submit-btn:hover {
    background: #dfefe4;
    border-color: #9ec5b1;
}
.primary-submit-btn {
    order: 2;
    flex: 1;
    border: 0;
    background: #08783f;
    color: #fff;
    border-radius: 9px;
    padding: 11px 14px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 3px 8px rgba(8, 120, 63, 0.25);
    transition: all 0.15s ease;
}
.primary-submit-btn:hover {
    background: #066233;
    box-shadow: 0 4px 12px rgba(8, 120, 63, 0.35);
}

/* Modal styling for Quick Task Selection */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(8, 38, 22, 0.45);
    backdrop-filter: blur(2px);
    z-index: 999;
    place-items: center;
}
.modal-overlay.active {
    display: grid;
}
.modal-card {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 440px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
</style>
@endpush

@section('content')
<div class="farmer-page-wrapper">
    <!-- Breadcrumb Nav -->
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">ড্যাশবোর্ড</a>
        <span class="sep">&gt;</span>
        <a href="{{ route('admin.farmers.index') }}">কৃষক ব্যবস্থাপনা</a>
        <span class="sep">&gt;</span>
        <span class="curr">নতুন কৃষক যোগ করুন</span>
    </div>

    <!-- Title and Subtitle -->
    <div class="page-header-row">
        <a href="{{ route('admin.farmers.index') }}" class="back-btn-circle" title="কৃষক তালিকায় ফিরুন">
            &#8249;
        </a>
        <div class="page-title-box">
            <h1>নতুন কৃষক যোগ করুন</h1>
            <p>নতুন কৃষকের তথ্য যুক্ত করে অ্যাকাউন্ট তৈরি করুন</p>
        </div>
    </div>

    <!-- Main Form Form -->
    <form method="POST" action="{{ route('admin.farmers.store') }}" enctype="multipart/form-data" id="farmerForm">
        @csrf

        <div class="farmer-layout-grid">
            <!-- Left Column: Main Form Fields -->
            <div class="farmer-main-column">
                
                <!-- Top Row: Personal Info (Left) + Profile Photo (Right) -->
                <div class="top-split-row">
                    <!-- Personal Info Card -->
                    <section class="farmer-card personal-info-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <div>
                                    <h2 class="card-header-title">ব্যক্তিগত তথ্য</h2>
                                    <div class="card-header-sub">কৃষকের মৌলিক ও পরিচিতি তথ্য দিন</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <!-- Full Name -->
                            <label class="field">
                                <span class="label">পূর্ণ নাম <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <input class="input" type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" placeholder="কৃষকের পূর্ণ নাম লিখুন" required>
                                </div>
                                @error('full_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Farmer ID (Server Generated) -->
                            <label class="field">
                                <span class="label">
                                    <span>কৃষক আইডি <span class="req">*</span></span>
                                    <span class="field-badge-auto" title="সিস্টেম দ্বারা স্বয়ংক্রিয়ভাবে তৈরি হবে">স্বয়ংক্রিয়</span>
                                </span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="M7 15h4M15 15h2M7 11h10"/></svg>
                                    </span>
                                    <input class="input" type="text" value="{{ $nextFarmerCode ?? 'F-006' }}" disabled style="background:#f4f7f5;color:var(--muted);font-weight:700;" title="সিস্টেম দ্বারা স্বয়ংক্রিয়ভাবে তৈরি হবে">
                                </div>
                            </label>

                            <!-- Mobile Phone -->
                            <label class="field">
                                <span class="label">মোবাইল নম্বর <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    </span>
                                    <input class="input" type="text" name="phone" id="phone_input" value="{{ old('phone') }}" placeholder="০১XXXXXXXXX" required>
                                </div>
                                @error('phone')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- NID -->
                            <label class="field">
                                <span class="label">
                                    <span>জাতীয় পরিচয়পত্র নম্বর <span class="opt-text">(ঐচ্ছিক)</span></span>
                                </span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                    </span>
                                    <input class="input" type="text" name="nid" value="{{ old('nid') }}" placeholder="জাতীয় পরিচয়পত্র নম্বর লিখুন">
                                </div>
                                @error('nid')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Date of Birth -->
                            <label class="field">
                                <span class="label">জন্ম তারিখ <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                    </span>
                                    <input class="input" type="date" name="date_of_birth" id="dob_input" value="{{ old('date_of_birth') }}">
                                </div>
                                @error('date_of_birth')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Gender -->
                            <label class="field">
                                <span class="label">লিঙ্গ <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <select class="select" name="gender" id="gender_select">
                                        <option value="পুরুষ" {{ old('gender', 'পুরুষ') === 'পুরুষ' ? 'selected' : '' }}>পুরুষ</option>
                                        <option value="নারী" {{ old('gender') === 'নারী' ? 'selected' : '' }}>নারী</option>
                                        <option value="অন্যান্য" {{ old('gender') === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                                    </select>
                                </div>
                                @error('gender')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Address -->
                            <label class="field full">
                                <span class="label">বর্তমান ঠিকানা <span class="req">*</span></span>
                                <div class="textarea-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    </span>
                                    <textarea class="textarea" name="address" id="address_input" maxlength="200" placeholder="গ্রাম, ইউনিয়ন, উপজেলা, জেলা লিখুন" required>{{ old('address') }}</textarea>
                                    <span class="textarea-counter" id="address_counter">0/200</span>
                                </div>
                                @error('address')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>
                        </div>
                    </section>

                    <!-- Profile Photo Card -->
                    <section class="farmer-card photo-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                                </div>
                                <div>
                                    <h2 class="card-header-title">প্রোফাইল ছবি</h2>
                                    <div class="card-header-sub">কৃষকের সাম্প্রতিক ছবি আপলোড করুন</div>
                                </div>
                            </div>
                        </div>

                        <div class="photo-upload-container">
                            <input type="file" name="profile_photo" id="profile_photo_input" accept="image/*" style="display:none;">
                            
                            <div class="photo-avatar-ring" id="avatar_picker_trigger" title="ছবি নির্বাচন করতে ক্লিক করুন">
                                <img src="" alt="প্রোফাইল ছবি" class="photo-avatar-preview" id="avatar_preview_img">
                                <div class="photo-avatar-placeholder" id="avatar_placeholder_icon">
                                    <svg width="44" height="44" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="photo-camera-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                </div>
                            </div>

                            <div class="photo-title">ছবি আপলোড করুন</div>
                            <div class="photo-subtitle">JPG, PNG (সর্বোচ্চ 2MB)</div>

                            <div class="photo-divider">
                                <span>অথবা</span>
                            </div>

                            <button type="button" class="photo-choose-btn" id="photo_select_btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                ছবি নির্বাচন করুন
                            </button>
                        </div>
                        @error('profile_photo')
                            <div class="field-error" style="text-align:center;margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </section>
                </div>

                <!-- Farm / Work Profile Card (Full Width) -->
                <section class="farmer-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            </div>
                            <div>
                                <h2 class="card-header-title">ফার্ম / কাজের প্রোফাইল</h2>
                                <div class="card-header-sub">কৃষকের কাজের ধরন ও অভিজ্ঞতার তথ্য দিন</div>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Activity -->
                    <div style="margin-bottom: 14px;">
                        <span class="label">কৃষকের ধরন / প্রধান কার্যক্রম <span class="req">*</span></span>
                        <input type="hidden" name="primary_activity" id="primary_activity_input" value="{{ old('primary_activity', 'সবজি চাষ') }}">
                        <div class="activity-choices" id="activity_choices_container">
                            @php $act = old('primary_activity', 'সবজি চাষ'); @endphp
                            <button type="button" class="activity-btn {{ $act === 'সবজি চাষ' ? 'active' : '' }}" data-val="সবজি চাষ">
                                🌱 সবজি চাষ
                            </button>
                            <button type="button" class="activity-btn {{ $act === 'মাছ চাষ' ? 'active' : '' }}" data-val="মাছ চাষ">
                                🐟 মাছ চাষ
                            </button>
                            <button type="button" class="activity-btn {{ $act === 'গরু পালন' ? 'active' : '' }}" data-val="গরু পালন">
                                🐄 গরু পালন
                            </button>
                            <button type="button" class="activity-btn {{ !in_array($act, ['সবজি চাষ', 'মাছ চাষ', 'গরু পালন']) ? 'active' : '' }}" data-val="অন্যান্য">
                                ➕ অন্যান্য
                            </button>
                        </div>
                        @error('primary_activity')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-grid">
                        <!-- Experience Years -->
                        <label class="field">
                            <span class="label">কৃষি কাজে অভিজ্ঞতা (বছর) <span class="req">*</span></span>
                            <div class="input-wrap">
                                <span class="field-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                </span>
                                <input class="input" type="number" name="experience_years" id="experience_years" min="0" max="70" value="{{ old('experience_years') }}" placeholder="বছর লিখুন">
                            </div>
                            @error('experience_years')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>

                        <!-- Primary Work Area / Farm Location -->
                        <label class="field">
                            <span class="label">প্রধান কাজের এলাকা <span class="req">*</span></span>
                            <div class="input-wrap">
                                <span class="field-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </span>
                                <select class="select" name="farm_location_id" id="primary_farm_location_select">
                                    <option value="">নির্বাচন করুন</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" data-name="{{ $loc->name }}" {{ old('farm_location_id') == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }} ({{ $loc->location_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('farm_location_id')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>
                </section>

                <!-- Bottom Row: Emergency Contact (Left) + Login & App Access (Right) -->
                <div class="bottom-split-row">
                    <!-- Emergency Contact Card -->
                    <section class="farmer-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </div>
                                <div>
                                    <h2 class="card-header-title">জরুরি যোগাযোগ</h2>
                                    <div class="card-header-sub">প্রয়োজনে যোগাযোগের জন্য একজন অভিভাবক বা পরিবারের সদস্যর তথ্য দিন</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <!-- Emergency Contact Name -->
                            <label class="field">
                                <span class="label">যোগাযোগকারীর নাম <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </span>
                                    <input class="input" type="text" name="emergency_contact_name" id="em_name" value="{{ old('emergency_contact_name') }}" placeholder="নাম লিখুন" required>
                                </div>
                                @error('emergency_contact_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Emergency Contact Phone -->
                            <label class="field">
                                <span class="label">মোবাইল নম্বর <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    </span>
                                    <input class="input" type="text" name="emergency_contact_phone" id="em_phone" value="{{ old('emergency_contact_phone') }}" placeholder="০১XXXXXXXXX" required>
                                </div>
                                @error('emergency_contact_phone')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Relationship & Clear Button -->
                            <div class="field full" style="display:flex;align-items:flex-end;gap:10px;">
                                <label style="flex:1;">
                                    <span class="label">সম্পর্ক <span class="req">*</span></span>
                                    <div class="input-wrap">
                                        <span class="field-icon">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                        </span>
                                        <select class="select" name="emergency_contact_relationship" id="em_rel" required>
                                            <option value="">নির্বাচন করুন</option>
                                            <option value="পিতা/মাতা" {{ old('emergency_contact_relationship') === 'পিতা/মাতা' ? 'selected' : '' }}>পিতা/মাতা</option>
                                            <option value="স্বামী/স্ত্রী" {{ old('emergency_contact_relationship') === 'স্বামী/স্ত্রী' ? 'selected' : '' }}>স্বামী/স্ত্রী</option>
                                            <option value="ভাই/বোন" {{ old('emergency_contact_relationship') === 'ভাই/বোন' ? 'selected' : '' }}>ভাই/বোন</option>
                                            <option value="সন্তান" {{ old('emergency_contact_relationship') === 'সন্তান' ? 'selected' : '' }}>সন্তান</option>
                                            <option value="অন্যান্য" {{ old('emergency_contact_relationship') === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                                        </select>
                                    </div>
                                    @error('emergency_contact_relationship')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </label>
                                <button type="button" class="clear-btn" id="clear_emergency_btn" title="তথ্য মুছে ফেলুন">
                                    &#10005; বাতিল
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Login & App Access Card -->
                    <section class="farmer-card">
                        <div class="card-header">
                            <div class="card-header-left">
                                <div class="card-header-icon lock">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                                <div>
                                    <h2 class="card-header-title" style="color: #08783f;">লগইন ও অ্যাপ অ্যাক্সেস</h2>
                                    <div class="card-header-sub">কৃষকের জন্য অ্যাপ অ্যাক্সেস সেট করুন</div>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <!-- Username -->
                            <label class="field full">
                                <span class="label">ব্যবহারকারীর নাম (Username) <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon" style="font-weight:700;">@</span>
                                    <input class="input" type="text" name="username" id="username_input" value="{{ old('username') }}" placeholder="username লিখুন">
                                </div>
                                @error('username')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Password with Eye Toggle -->
                            <label class="field full">
                                <span class="label">অস্থায়ী পাসওয়ার্ড <span class="req">*</span></span>
                                <div class="input-wrap">
                                    <span class="field-icon">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    </span>
                                    <input class="input" type="password" name="password" id="password_input" value="{{ old('password') }}" placeholder="পাসওয়ার্ড লিখুন" minlength="6" required>
                                    <button type="button" class="password-toggle-btn" id="toggle_password_btn" title="পাসওয়ার্ড দেখুন/লুকান">
                                        <svg id="eye_icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg id="eye_slash_icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                                    </button>
                                </div>
                                <small style="font-size:10px;color:var(--muted);display:block;margin-top:2px;">কৃষক মোবাইল অ্যাপে লগইনের জন্য এই পাসওয়ার্ডটি ব্যবহার করবেন।</small>
                                @error('password')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </label>

                            <!-- Mobile App Access Toggle -->
                            <div class="access-rule-box">
                                <div>
                                    <b style="font-size:13px;color:#14241d;">মোবাইল অ্যাপ অ্যাক্সেস</b>
                                    <div style="font-size:11px;color:#6f7f77;">এই কৃষক মোবাইল অ্যাপে লগইন করতে পারবেন</div>
                                </div>
                                <input type="hidden" name="app_access_enabled" id="app_access_enabled" value="{{ old('app_access_enabled', '1') }}">
                                <button type="button" class="switch {{ old('app_access_enabled', '1') == '1' ? 'on' : '' }}" data-switch data-input="app_access_enabled" id="app_access_switch"></button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Right Column: Sidebar (Progress + Allocated Resources + Buttons) -->
            <div class="farmer-sidebar">
                
                <!-- Account Creation Progress -->
                <section class="farmer-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                            </div>
                            <div>
                                <h2 class="card-header-title">অ্যাকাউন্ট তৈরির অগ্রগতি</h2>
                                <div class="card-header-sub">সবগুলো তথ্য পূরণ করে অ্যাকাউন্ট তৈরি করুন</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-list">
                        <!-- Step 1: Personal Info -->
                        <div class="progress-item" id="step_personal">
                            <div class="progress-item-left">
                                <span class="progress-num" id="step_num_1">১</span>
                                <span class="progress-title">ব্যক্তিগত তথ্য</span>
                            </div>
                            <span class="status-pill running" id="step_pill_1">চলছে</span>
                        </div>

                        <!-- Step 2: Farm/Work Info -->
                        <div class="progress-item" id="step_farm">
                            <div class="progress-item-left">
                                <span class="progress-num" id="step_num_2">২</span>
                                <span class="progress-title">ফার্ম / কাজের তথ্য</span>
                            </div>
                            <span class="status-pill pending" id="step_pill_2">বাকি</span>
                        </div>

                        <!-- Step 3: Login & App Access -->
                        <div class="progress-item" id="step_access">
                            <div class="progress-item-left">
                                <span class="progress-num" id="step_num_3">৩</span>
                                <span class="progress-title">লগইন ও অ্যাপ অ্যাক্সেস</span>
                            </div>
                            <span class="status-pill pending" id="step_pill_3">বাকি</span>
                        </div>

                        <!-- Step 4: Emergency Contact -->
                        <div class="progress-item" id="step_emergency">
                            <div class="progress-item-left">
                                <span class="progress-num" id="step_num_4">৪</span>
                                <span class="progress-title">জরুরি যোগাযোগ</span>
                            </div>
                            <span class="status-pill pending" id="step_pill_4">বাকি</span>
                        </div>

                        <!-- Step 5: Save Info -->
                        <div class="progress-item" id="step_save">
                            <div class="progress-item-left">
                                <span class="progress-num" id="step_num_5">৫</span>
                                <span class="progress-title">তথ্য সংরক্ষণ</span>
                            </div>
                            <span class="status-pill pending" id="step_pill_5">বাকি</span>
                        </div>
                    </div>
                </section>

                <!-- Initial Allocated Resources -->
                <section class="farmer-card">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-header-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" x2="9" y1="3" y2="18"/><line x1="15" x2="15" y1="6" y2="21"/></svg>
                            </div>
                            <div>
                                <h2 class="card-header-title">বরাদ্দকৃত সম্পদ (প্রাথমিক)</h2>
                                <div class="card-header-sub">কৃষকের সাথে ফার্ম লোকেশন ও কাজ বরাদ্দ করুন</div>
                            </div>
                        </div>
                    </div>

                    <!-- Farm Location Allocation -->
                    <div class="allocation-item">
                        <div class="allocation-icon-circle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="allocation-text" id="allocated_location_display">
                            এখনো কোনো ফার্ম লোকেশন বরাদ্দ করা হয়নি
                        </div>
                        <button type="button" class="allocation-btn" id="select_location_btn">
                            + ফার্ম লোকেশন নির্বাচন করুন
                        </button>
                    </div>

                    <!-- Task Assignment Allocation -->
                    <div class="allocation-item">
                        <input type="hidden" name="initial_task_id" id="initial_task_id" value="{{ old('initial_task_id') }}">
                        <div class="allocation-icon-circle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                        </div>
                        <div class="allocation-text" id="allocated_task_display">
                            এখনো কোনো কাজ/পরিকল্পনা বরাদ্দ করা হয়নি
                        </div>
                        <button type="button" class="allocation-btn" id="select_task_btn">
                            + কাজ বরাদ্দ করুন
                        </button>
                    </div>
                </section>

                <!-- Action Buttons (Draft / Save) -->
                <div class="sidebar-actions">
                    <button type="submit" name="action" value="save" class="primary-submit-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        সংরক্ষণ করুন
                    </button>
                    <button type="submit" name="action" value="draft" class="draft-submit-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        খসড়া সংরক্ষণ
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- Task Picker Modal -->
<div class="modal-overlay" id="taskPickerModal">
    <div class="modal-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <b style="font-size:15px;color:#14241d;">প্রাথমিক কাজ বরাদ্দ করুন</b>
            <button type="button" id="closeTaskModal" style="border:0;background:none;font-size:18px;color:#8fa298;cursor:pointer;">&times;</button>
        </div>
        <p style="font-size:12px;color:#6f7f77;margin:0 0 14px;">কৃষককে যোগ করার সাথে সাথে এই কাজটি বরাদ্দ করা হবে।</p>
        
        <label class="field full" style="margin-bottom:14px;">
            <span class="label">কাজের টেমপ্লেট নির্বাচন করুন</span>
            <select class="select" id="task_modal_select">
                <option value="">কোনো কাজ নয়</option>
                @foreach($taskTemplates as $tpl)
                    <option value="{{ $tpl->id }}" data-name="{{ $tpl->name }}">
                        {{ $tpl->name }} ({{ $tpl->task_code }}) - {{ $tpl->category }}
                    </option>
                @endforeach
            </select>
        </label>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" class="btn soft" id="cancelTaskModal">বাতিল</button>
            <button type="button" class="btn primary" id="confirmTaskModal">বরাদ্দ সম্পন্ন করুন</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Password visibility toggle
    const pwdInput = document.getElementById('password_input');
    const toggleBtn = document.getElementById('toggle_password_btn');
    const eyeIcon = document.getElementById('eye_icon');
    const eyeSlashIcon = document.getElementById('eye_slash_icon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.style.display = 'none';
                eyeSlashIcon.style.display = 'block';
            } else {
                pwdInput.type = 'password';
                eyeIcon.style.display = 'block';
                eyeSlashIcon.style.display = 'none';
            }
        });
    }

    // 2. Profile photo upload preview
    const photoInput = document.getElementById('profile_photo_input');
    const chooseBtn = document.getElementById('photo_select_btn');
    const avatarTrigger = document.getElementById('avatar_picker_trigger');
    const previewImg = document.getElementById('avatar_preview_img');
    const placeholderIcon = document.getElementById('avatar_placeholder_icon');

    const openPhotoPicker = () => {
        if (photoInput) photoInput.click();
    };

    if (chooseBtn) chooseBtn.addEventListener('click', openPhotoPicker);
    if (avatarTrigger) avatarTrigger.addEventListener('click', openPhotoPicker);

    if (photoInput) {
        photoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewImg && placeholderIcon) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                        placeholderIcon.style.display = 'none';
                    }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // 3. Address counter
    const addressInput = document.getElementById('address_input');
    const addressCounter = document.getElementById('address_counter');
    if (addressInput && addressCounter) {
        const updateCounter = () => {
            addressCounter.textContent = `${addressInput.value.length}/200`;
        };
        addressInput.addEventListener('input', updateCounter);
        updateCounter();
    }

    // 4. Primary Activity buttons
    const activityButtons = document.querySelectorAll('.activity-btn');
    const primaryActivityInput = document.getElementById('primary_activity_input');
    activityButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            activityButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (primaryActivityInput) {
                primaryActivityInput.value = btn.dataset.val;
            }
            updateChecklist();
        });
    });

    // 5. Phone -> Username auto-fill suggestion (if empty)
    const phoneInput = document.getElementById('phone_input');
    const usernameInput = document.getElementById('username_input');
    if (phoneInput && usernameInput) {
        phoneInput.addEventListener('blur', () => {
            if (!usernameInput.value.trim()) {
                usernameInput.value = phoneInput.value.trim();
                updateChecklist();
            }
        });
    }

    // 6. Farm Location sync with Allocated Resources
    const locationSelect = document.getElementById('primary_farm_location_select');
    const locationDisplay = document.getElementById('allocated_location_display');
    const selectLocationBtn = document.getElementById('select_location_btn');

    const updateLocationDisplay = () => {
        if (!locationSelect || !locationDisplay) return;
        const selectedOption = locationSelect.options[locationSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            locationDisplay.innerHTML = `<b style="color:#08783f;">${selectedOption.text}</b>`;
        } else {
            locationDisplay.textContent = 'এখনো কোনো ফার্ম লোকেশন বরাদ্দ করা হয়নি';
        }
        updateChecklist();
    };

    if (locationSelect) {
        locationSelect.addEventListener('change', updateLocationDisplay);
        updateLocationDisplay();
    }

    if (selectLocationBtn && locationSelect) {
        selectLocationBtn.addEventListener('click', () => {
            locationSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            locationSelect.focus();
        });
    }

    // 7. Clear Emergency Contact
    const clearEmBtn = document.getElementById('clear_emergency_btn');
    if (clearEmBtn) {
        clearEmBtn.addEventListener('click', () => {
            document.getElementById('em_name').value = '';
            document.getElementById('em_phone').value = '';
            document.getElementById('em_rel').value = '';
            updateChecklist();
        });
    }

    // 8. Task Picker Modal Logic
    const taskModal = document.getElementById('taskPickerModal');
    const selectTaskBtn = document.getElementById('select_task_btn');
    const closeTaskModal = document.getElementById('closeTaskModal');
    const cancelTaskModal = document.getElementById('cancelTaskModal');
    const confirmTaskModal = document.getElementById('confirmTaskModal');
    const taskModalSelect = document.getElementById('task_modal_select');
    const initialTaskIdInput = document.getElementById('initial_task_id');
    const taskDisplay = document.getElementById('allocated_task_display');

    if (selectTaskBtn && taskModal) {
        selectTaskBtn.addEventListener('click', () => {
            taskModal.classList.add('active');
        });
    }
    const hideTaskModal = () => {
        if (taskModal) taskModal.classList.remove('active');
    };
    if (closeTaskModal) closeTaskModal.addEventListener('click', hideTaskModal);
    if (cancelTaskModal) cancelTaskModal.addEventListener('click', hideTaskModal);

    if (confirmTaskModal && taskModalSelect && initialTaskIdInput && taskDisplay) {
        confirmTaskModal.addEventListener('click', () => {
            const val = taskModalSelect.value;
            initialTaskIdInput.value = val;
            const selectedOpt = taskModalSelect.options[taskModalSelect.selectedIndex];
            if (val && selectedOpt) {
                taskDisplay.innerHTML = `<b style="color:#08783f;">${selectedOpt.text}</b>`;
            } else {
                taskDisplay.textContent = 'এখনো কোনো কাজ/পরিকল্পনা বরাদ্দ করা হয়নি';
            }
            hideTaskModal();
        });
    }

    // 9. Interactive Progress Tracker (Account Creation Progress)
    function setStepStatus(stepNum, status) {
        const numEl = document.getElementById(`step_num_${stepNum}`);
        const pillEl = document.getElementById(`step_pill_${stepNum}`);
        if (!numEl || !pillEl) return;

        if (status === 'completed') {
            numEl.classList.add('completed');
            numEl.innerHTML = '&#10003;';
            pillEl.className = 'status-pill completed';
            pillEl.textContent = 'সম্পন্ন';
        } else if (status === 'running') {
            numEl.classList.remove('completed');
            numEl.textContent = stepNum.toLocaleString('bn-BD');
            pillEl.className = 'status-pill running';
            pillEl.textContent = 'চলছে';
        } else {
            numEl.classList.remove('completed');
            numEl.textContent = stepNum.toLocaleString('bn-BD');
            pillEl.className = 'status-pill pending';
            pillEl.textContent = 'বাকি';
        }
    }

    function updateChecklist() {
        const fullName = document.getElementById('full_name')?.value.trim();
        const phone = document.getElementById('phone_input')?.value.trim();
        const address = document.getElementById('address_input')?.value.trim();
        const isPersonalComplete = (fullName && phone && address);

        const exp = document.getElementById('experience_years')?.value.trim();
        const loc = document.getElementById('primary_farm_location_select')?.value;
        const isFarmComplete = (loc !== '');

        const password = document.getElementById('password_input')?.value.trim();
        const isAccessComplete = (password && password.length >= 6);

        const emName = document.getElementById('em_name')?.value.trim();
        const emPhone = document.getElementById('em_phone')?.value.trim();
        const emRel = document.getElementById('em_rel')?.value;
        const isEmComplete = (emName && emPhone && emRel);

        // Step 1
        if (isPersonalComplete) {
            setStepStatus(1, 'completed');
        } else {
            setStepStatus(1, 'running');
        }

        // Step 2
        if (isFarmComplete) {
            setStepStatus(2, 'completed');
        } else if (isPersonalComplete) {
            setStepStatus(2, 'running');
        } else {
            setStepStatus(2, 'pending');
        }

        // Step 3
        if (isAccessComplete) {
            setStepStatus(3, 'completed');
        } else if (isFarmComplete) {
            setStepStatus(3, 'running');
        } else {
            setStepStatus(3, 'pending');
        }

        // Step 4
        if (isEmComplete) {
            setStepStatus(4, 'completed');
        } else if (isAccessComplete) {
            setStepStatus(4, 'running');
        } else {
            setStepStatus(4, 'pending');
        }

        // Step 5 (Save)
        if (isPersonalComplete && isAccessComplete && isEmComplete) {
            setStepStatus(5, 'running');
        } else {
            setStepStatus(5, 'pending');
        }
    }

    // Attach listeners for interactive progress tracker
    const trackInputs = ['full_name', 'phone_input', 'address_input', 'experience_years', 'primary_farm_location_select', 'password_input', 'em_name', 'em_phone', 'em_rel'];
    trackInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateChecklist);
            el.addEventListener('change', updateChecklist);
        }
    });

    updateChecklist();
});
</script>
@endpush
