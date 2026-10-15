<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $groupedSettings = $this->settingsService->getGroupedSettings();

        return view('settings.index', [
            'activeTab' => 'settings',
            'title' => 'সিস্টেম সেটিংস',
            'groupedSettings' => $groupedSettings,
        ]);
    }

    /**
     * Validate and update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'farm_name' => ['required', 'string', 'max:100'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_email' => ['required', 'email', 'max:100'],
            'company_address' => ['required', 'string', 'max:255'],
            'default_task_priority' => ['required', 'string', 'in:normal,important,urgent'],
            'default_task_duration' => ['required', 'integer', 'min:10', 'max:1440'],
            'default_geofence_radius' => ['required', 'integer', 'min:5', 'max:1000'],
            'pagination_size' => ['required', 'integer', 'in:10,15,20,25,50,100'],
            'date_format' => ['required', 'string', 'in:Y-m-d,d/m/Y,d-m-Y,m/d/Y'],
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
        ]);

        $result = $this->settingsService->updateSettings($validated, auth()->user());

        if ($result['updated_count'] > 0) {
            $msg = "{$result['updated_count']} টি সেটিংস সফলভাবে হালনাগাদ করা হয়েছে।";
        } else {
            $msg = 'কোনো সেটিংসে পরিবর্তন পাওয়া যায়নি।';
        }

        return redirect()->route('admin.settings')->with('success', $msg);
    }
}
