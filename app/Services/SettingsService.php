<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * Default system configurations.
     */
    protected static array $defaults = [
        'farm_name' => [
            'value' => 'আমার ফার্ম',
            'type' => 'string',
            'group' => 'general',
            'description' => 'খামারের প্রাতিষ্ঠানিক নাম',
        ],
        'company_phone' => [
            'value' => '+880 1700-000000',
            'type' => 'string',
            'group' => 'general',
            'description' => 'কোম্পানি যোগাযোগের প্রধান ফোন নম্বর',
        ],
        'company_email' => [
            'value' => 'contact@bashiragro.com',
            'type' => 'string',
            'group' => 'general',
            'description' => 'অফিসিয়াল যোগাযোগ ইমেইল',
        ],
        'company_address' => [
            'value' => 'পাবনা, রাজশাহী বিভাগ, বাংলাদেশ',
            'type' => 'string',
            'group' => 'general',
            'description' => 'প্রধান কার্যালয় বা খামারের ঠিকানা',
        ],
        'default_task_priority' => [
            'value' => 'normal',
            'type' => 'string',
            'group' => 'task',
            'description' => 'নতুন কাজের ডিফল্ট অগ্রাধিকার স্তর',
        ],
        'default_task_duration' => [
            'value' => '120',
            'type' => 'integer',
            'group' => 'task',
            'description' => 'কাজের আনুমানিক গড় সময়কাল (মিনিটে)',
        ],
        'default_geofence_radius' => [
            'value' => '20',
            'type' => 'integer',
            'group' => 'location',
            'description' => 'ফার্ম লোকেশনের ডিফল্ট জিও-ফেন্স ব্যাসার্ধ (মিটারে)',
        ],
        'pagination_size' => [
            'value' => '20',
            'type' => 'integer',
            'group' => 'system',
            'description' => 'তালিকায় প্রতি পৃষ্ঠায় প্রদর্শিত আইটেম সংখ্যা',
        ],
        'date_format' => [
            'value' => 'Y-m-d',
            'type' => 'string',
            'group' => 'system',
            'description' => 'সিস্টেম তারিখ প্রদর্শনের ফরম্যাট',
        ],
        'site_logo' => [
            'value' => '',
            'type' => 'string',
            'group' => 'branding',
            'description' => 'অ্যাডমিন সাইটের প্রধান লোগো',
        ],
        'site_favicon' => [
            'value' => '',
            'type' => 'string',
            'group' => 'branding',
            'description' => 'ব্রাউজার ট্যাবে প্রদর্শিত ফেভিকন',
        ],
    ];

    /**
     * Ensure default settings exist in database.
     */
    public static function ensureDefaults(): void
    {
        foreach (self::$defaults as $key => $meta) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $meta['value'],
                    'type' => $meta['type'],
                    'group' => $meta['group'],
                    'description' => $meta['description'],
                    'is_editable' => true,
                ]
            );
        }
    }

    /**
     * Get all editable settings grouped by their category.
     *
     * @return Collection<string, Collection<int, Setting>>
     */
    public function getGroupedSettings(): Collection
    {
        self::ensureDefaults();

        return Setting::where('is_editable', true)
            ->orderBy('id')
            ->get()
            ->groupBy('group');
    }

    /**
     * Update multiple settings and generate audit logs.
     *
     * @param  array<string, mixed>  $inputData
     */
    public function updateSettings(array $inputData, ?User $user = null): array
    {
        self::ensureDefaults();

        $oldValues = [];
        $newValues = [];
        $userId = $user ? $user->id : auth()->id();

        foreach ($inputData as $key => $newValue) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting || ! $setting->is_editable) {
                continue;
            }

            $currentVal = $setting->value;
            $stringifiedNew = (string) $newValue;

            if ($currentVal !== $stringifiedNew) {
                $oldValues[$key] = $currentVal;
                $newValues[$key] = $stringifiedNew;

                $setting->setValue($newValue);
                $setting->updated_by = $userId;
                $setting->save();
            }
        }

        if (! empty($newValues)) {
            AuditService::log(
                action: 'settings.updated',
                module: 'settings',
                description: 'সিস্টেম কনফিগারেশন সেটিংস হালনাগাদ করা হয়েছে ('.count($newValues).' টি সেটিংস)',
                subject: null,
                oldValues: $oldValues,
                newValues: $newValues
            );
        }

        return [
            'updated_count' => count($newValues),
            'old' => $oldValues,
            'new' => $newValues,
        ];
    }
}
