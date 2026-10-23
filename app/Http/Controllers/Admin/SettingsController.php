<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    /**
     * Display system settings grouped by categories.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $groupedSettings = $this->settingsService->getGroupedSettings();

        return view('settings.index', [
            'activeTab' => 'settings',
            'title' => 'সিস্টেম সেটিংস',
            'groupedSettings' => $groupedSettings,
            'mobileHeaders' => \App\Services\AppAppearanceService::screens(),
        ]);
    }

    /**
     * Validate and update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'farm_name' => ['required', 'string', 'max:100'],
            'brand_tagline' => ['nullable', 'string', 'max:100'],
            'account_photo' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:2048'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_email' => ['required', 'email', 'max:100'],
            'company_address' => ['required', 'string', 'max:255'],
            'default_task_priority' => ['required', 'string', 'in:normal,important,urgent'],
            'default_task_duration' => ['required', 'integer', 'min:10', 'max:1440'],
            'default_geofence_radius' => ['required', 'integer', 'min:5', 'max:1000'],
            'pagination_size' => ['required', 'integer', 'in:10,15,20,25,50,100'],
            'date_format' => ['required', 'string', 'in:Y-m-d,d/m/Y,d-m-Y,m/d/Y'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:1024'],
            'remove_site_logo' => ['nullable', 'boolean'],
            'remove_site_favicon' => ['nullable', 'boolean'],
        ], [
            'farm_name.required' => 'খামারের নাম আবশ্যক।',
            'company_phone.required' => 'কোম্পানির ফোন নম্বর আবশ্যক।',
            'company_email.required' => 'কোম্পানির ইমেইল আবশ্যক।',
            'company_email.email' => 'একটি বৈধ ইমেইল ঠিকানা দিন।',
            'company_address.required' => 'কোম্পানির ঠিকানা আবশ্যক।',
            'default_task_priority.required' => 'ডিফল্ট কাজের অগ্রাধিকার আবশ্যক।',
            'default_task_priority.in' => 'অগ্রাধিকার normal, important অথবা urgent হতে হবে।',
            'default_task_duration.required' => 'ডিফল্ট কাজের সময়কাল আবশ্যক।',
            'default_task_duration.integer' => 'সময়কাল সংখ্যায় হতে হবে।',
            'default_geofence_radius.required' => 'জিও-ফেন্স ব্যাসার্ধ আবশ্যক।',
            'default_geofence_radius.integer' => 'জিও-ফেন্স ব্যাসার্ধ সংখ্যায় হতে হবে।',
            'pagination_size.required' => 'পেজিনেশন সাইজ আবশ্যক।',
            'date_format.required' => 'তারিখের ফরম্যাট আবশ্যক।',
            'site_logo.file' => 'লোগো একটি ফাইল হতে হবে।',
            'site_logo.mimes' => 'লোগো PNG, JPG, JPEG বা WebP ফরম্যাটের হতে হবে।',
            'site_logo.max' => 'লোগোর সর্বোচ্চ সাইজ ২ মেগাবাইট (2MB)।',
            'site_favicon.file' => 'ফেভিকন একটি ফাইল হতে হবে।',
            'site_favicon.mimes' => 'ফেভিকন ICO, PNG, JPG বা WebP ফরম্যাটের হতে হবে।',
            'site_favicon.max' => 'ফেভিকনের সর্বোচ্চ সাইজ ১ মেগাবাইট (1MB)।',
        ]);

        // Store new files first, commit new paths, then remove old files.
        // A failed database write must not leave a setting pointing at a deleted image.
        $uploadedFiles = [];
        $retiredFiles = [];
        $accountPhoto = null;
        $user = $request->user();
        try {
            if ($request->hasFile('account_photo')) {
                $accountPhoto = $request->file('account_photo')->store('users/photos', 'public');
                if (! $accountPhoto) throw new \RuntimeException('প্রোফাইল ছবি সংরক্ষণ করা যায়নি।');
                $uploadedFiles[] = $accountPhoto;
                if ($user->profile_photo_path) $retiredFiles[] = $user->profile_photo_path;
            }
            unset($validated['account_photo']);

            foreach (['site_logo', 'site_favicon'] as $key) {
                $old = Setting::get($key);
                if ($request->boolean('remove_'.$key)) {
                    $validated[$key] = '';
                    if ($old) $retiredFiles[] = $old;
                } elseif ($request->hasFile($key)) {
                    $path = $request->file($key)->store('branding', 'public');
                    if (! $path) throw new \RuntimeException('ব্র্যান্ডিং ছবি সংরক্ষণ করা যায়নি।');
                    $uploadedFiles[] = $path;
                    $validated[$key] = $path;
                    if ($old) $retiredFiles[] = $old;
                } else {
                    unset($validated[$key]);
                }
                unset($validated['remove_'.$key]);
            }

            $result = DB::transaction(function () use ($validated, $user, $accountPhoto) {
                if ($accountPhoto) {
                    $user->forceFill(['profile_photo_path' => $accountPhoto])->save();
                }
                return $this->settingsService->updateSettings($validated, $user);
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedFiles as $path) Storage::disk('public')->delete($path);
            throw $exception;
        }
        foreach ($retiredFiles as $path) Storage::disk('public')->delete($path);

        if ($result['updated_count'] > 0) {
            $msg = "{$result['updated_count']} টি সেটিংস সফলভাবে হালনাগাদ করা হয়েছে।";
        } else {
            $msg = 'কোনো সেটিংসে পরিবর্তন পাওয়া যায়নি।';
        }

        return redirect()->route('admin.settings')->with('success', $msg);
    }
}
