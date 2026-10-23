<?php

namespace App\Services;

use App\Models\Setting;

class AppAppearanceService
{
    // Screen IDs are deliberately fixed: no arbitrary setting keys or paths from requests.
    public const SCREENS = [
        'owner_dashboard' => ['label' => 'অ্যাডমিন ড্যাশবোর্ড', 'title' => 'শুভ সকাল,', 'subtitle' => 'ফার্মের সব কার্যক্রম এক নজরে দেখুন'],
        'farmers_list' => ['label' => 'কৃষকদের তালিকা', 'title' => 'কৃষকদের তালিকা', 'subtitle' => 'সকল কৃষকের বিবরণ ও বর্তমান অবস্থা'],
        'owner_locations' => ['label' => 'খামারের লোকেশনসমূহ', 'title' => 'খামারের লোকেশনসমূহ', 'subtitle' => 'অ্যাডমিন প্যানেলে যুক্ত সকল প্লট ও জমি'],
        'owner_tasks' => ['label' => 'আজকের কাজের পরিকল্পনা', 'title' => 'আজকের কাজের পরিকল্পনা', 'subtitle' => 'খামারের সব কাজ এক নজরে'],
        'farmer_activity' => ['label' => 'কৃষকের কার্যক্রম', 'title' => 'কৃষকের কার্যক্রম', 'subtitle' => ''],
        'owner_photos' => ['label' => 'কাজের বিস্তারিত', 'title' => 'কাজের বিস্তারিত', 'subtitle' => 'প্রমাণ, সময় ও অবস্থান যাচাই'],
        'farmer_dashboard' => ['label' => 'কৃষকের ড্যাশবোর্ড', 'title' => 'আজকের কাজ', 'subtitle' => ''],
    ];

    public static function screens(): array
    {
        $keys = array_map(fn ($key) => 'app_header.'.$key, array_keys(self::SCREENS));
        $stored = Setting::query()->whereIn('key', $keys)->pluck('value', 'key');
        $result = [];
        foreach (self::SCREENS as $key => $meta) {
            $overrides = json_decode($stored->get('app_header.'.$key, '{}'), true);
            $overrides = is_array($overrides) ? $overrides : [];
            $imagePath = $overrides['image_path'] ?? null;
            $result[$key] = [
                'label' => $meta['label'],
                'title' => $overrides['title'] ?? $meta['title'],
                'subtitle' => $overrides['subtitle'] ?? $meta['subtitle'],
                'image_path' => $imagePath,
                'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
                'title_color' => $overrides['title_color'] ?? '#FFFFFF',
                'font_size' => $overrides['font_size'] ?? 18,
                'alignment' => $overrides['alignment'] ?? 'center',
                'shadow' => $overrides['shadow'] ?? true,
                'overlay' => $overrides['overlay'] ?? 0.35,
            ];
        }
        return $result;
    }

    public static function apiPayload(): array
    {
        $screens = self::screens();
        foreach ($screens as &$screen) {
            unset($screen['image_path']); // Do not expose storage internals.
        }
        unset($screen);
        $logo = Setting::get('site_logo');
        return [
            'branding' => [
                'name' => Setting::get('farm_name', 'আমার ফার্ম'),
                'tagline' => Setting::get('brand_tagline', 'কৃষি ব্যবস্থাপনা প্ল্যাটফর্ম'),
                'logo_url' => $logo ? asset('storage/'.$logo) : null,
            ],
            'headers' => $screens,
        ];
    }
}
