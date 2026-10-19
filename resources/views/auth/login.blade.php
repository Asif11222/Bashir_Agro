<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>লগইন — আমার ফার্ম অ্যাডমিন প্যানেল</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .password-field-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-field-wrap .input {
            padding-right: 42px;
        }
        .password-toggle-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: 0;
            color: #6f7f77;
            padding: 6px 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .password-toggle-btn:hover {
            color: #0b8f4b;
            background-color: #f0f7f2;
        }
        .password-toggle-btn:focus-visible {
            outline: 2px solid #31a866;
            outline-offset: 1px;
        }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="leaf">🌱</div>
            <h1>আমার ফার্ম</h1>
            <p>কৃষি ব্যবস্থাপনা অ্যাডমিন প্যানেলে লগইন করুন</p>
        </div>

        @if (request()->query('reason') === 'idle')
            <div class="notice warn" style="margin-bottom: 16px; font-size: 13px; line-height: 1.45; display: flex; align-items: flex-start; gap: 8px;">
                <span style="font-size: 16px;">⏱</span>
                <div>
                    <b>সেশনের মেয়াদ শেষ হয়েছে</b><br>
                    নিষ্ক্রিয় থাকার কারণে অ্যাকাউন্ট সুরক্ষার্থে সেশন সমাপ্ত হয়েছে। অনুগ্রহ করে পুনরায় লগইন করুন।
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label class="label" for="email">ইমেইল বা মোবাইল নম্বর <span class="req">*</span></label>
                <input class="input" id="email" type="text" name="email" value="{{ old('email', 'admin@bashiragro.com') }}" required autofocus placeholder="admin@bashiragro.com">
            </div>

            <div style="margin-bottom: 16px;">
                <label class="label" for="password">পাসওয়ার্ড <span class="req">*</span></label>
                <div class="password-field-wrap">
                    <input class="input" id="password" type="password" name="password" required placeholder="••••••••">
                    <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="পাসওয়ার্ড দেখুন" aria-label="পাসওয়ার্ড দেখুন">
                        <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="eye-slash-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: none;">
                            <path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" x2="22" y1="2" y2="22"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; color: var(--muted);">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>মনে রাখুন</span>
                </label>
            </div>

            <button type="submit" class="btn primary" style="width: 100%; padding: 11px; font-size: 15px;">
                লগইন করুন
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pwdInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        if (!toggleBtn || !pwdInput) return;

        const eyeIcon = toggleBtn.querySelector('.eye-icon');
        const eyeSlashIcon = toggleBtn.querySelector('.eye-slash-icon');

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';

            if (eyeIcon && eyeSlashIcon) {
                eyeIcon.style.display = isPassword ? 'none' : 'block';
                eyeSlashIcon.style.display = isPassword ? 'block' : 'none';
            }

            const label = isPassword ? 'পাসওয়ার্ড লুকান' : 'পাসওয়ার্ড দেখুন';
            toggleBtn.setAttribute('title', label);
            toggleBtn.setAttribute('aria-label', label);
            pwdInput.focus();
        });
    });
</script>
</body>
</html>
