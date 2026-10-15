<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>লগইন — আমার ফার্ম অ্যাডমিন প্যানেল</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="leaf">🌱</div>
            <h1>আমার ফার্ম</h1>
            <p>কৃষি ব্যবস্থাপনা অ্যাডমিন প্যানেলে লগইন করুন</p>
        </div>

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
                <input class="input" id="password" type="password" name="password" required placeholder="••••••••">
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
</body>
</html>
