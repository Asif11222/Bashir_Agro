<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt with rate limiting protection.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "অতিরিক্ত ব্যর্থ লগইন চেষ্টার কারণে অ্যাকাউন্ট সুরক্ষা সক্রিয় হয়েছে। অনুগ্রহ করে {$seconds} সেকেন্ড পর পুনরায় চেষ্টা করুন।",
            ])->onlyInput('email');
        }

        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $authAttempt = [
            $loginField => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($authAttempt, $remember)) {
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();
            $user->update([
                'last_login_at' => now(),
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'প্রদত্ত ইমেইল/ফোন অথবা পাসওয়ার্ড সঠিক নয়।',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application with full session teardown.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $reason = $request->input('reason', $request->query('reason'));
        $redirectUrl = route('login', array_filter(['reason' => $reason]));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => 'logged_out',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }
}
