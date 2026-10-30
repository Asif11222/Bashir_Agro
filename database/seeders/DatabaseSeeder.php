<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmerEmergencyContact;
use App\Models\FarmLocation;
use App\Models\Setting;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initial Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@bashiragro.com'],
            [
                'name' => 'আব্দুল রহমান',
                'phone' => '01700000000',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Settings
        Setting::firstOrCreate(
            ['key' => 'default_geofence_radius'],
            [
                'value' => '20',
                'type' => 'integer',
                'updated_by' => $admin->id,
            ]
        );

        // 3. Farm Locations
        $loc1 = FarmLocation::firstOrCreate(
            ['location_code' => 'LOC-001'],
            [
                'name' => 'উত্তর মাঠ',
                'type' => 'সবজি ক্ষেত',
                'address' => 'উত্তর ব্লক, কৃষি অঞ্চল-১',
                'area' => 2.50,
                'area_unit' => 'একর',
                'latitude' => 24.7471000,
                'longitude' => 90.4203000,
                'geofence_radius_meters' => 20,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $loc2 = FarmLocation::firstOrCreate(
            ['location_code' => 'LOC-002'],
            [
                'name' => 'দক্ষিণ প্লট',
                'type' => 'ধানের জমি',
                'address' => 'দক্ষিণ ব্লক, কৃষি অঞ্চল-২',
                'area' => 3.00,
                'area_unit' => 'একর',
                'latitude' => 24.7481000,
                'longitude' => 90.4213000,
                'geofence_radius_meters' => 20,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $loc3 = FarmLocation::firstOrCreate(
            ['location_code' => 'LOC-003'],
            [
                'name' => 'পূর্ব পুকুর',
                'type' => 'মাছের পুকুর',
                'address' => 'পূর্ব ব্লক, জলাশয় এলাকা',
                'area' => 1.20,
                'area_unit' => 'একর',
                'latitude' => 24.7461000,
                'longitude' => 90.4223000,
                'geofence_radius_meters' => 20,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // 4. Farmers
        $farmerUser1 = User::firstOrCreate(
            ['phone' => '01712345678'],
            [
                'name' => 'মোঃ রহিম উদ্দিন',
                'password' => '123456',
                'role' => 'farmer',
                'is_active' => true,
            ]
        );

        $farmer1 = Farmer::firstOrCreate(
            ['farmer_code' => 'F-006'],
            [
                'user_id' => $farmerUser1->id,
                'full_name' => 'মোঃ রহিম উদ্দিন',
                'phone' => '01712345678',
                'nid' => '1987542130123',
                'gender' => 'পুরুষ',
                'address' => 'গ্রাম: রূপপুর, উপজেলা: সদর',
                'primary_activity' => 'সবজি চাষ',
                'experience_years' => 7,
                'app_access_enabled' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );
        if (! $farmer1->user_id) {
            $farmer1->update(['user_id' => $farmerUser1->id]);
        }

        FarmerEmergencyContact::firstOrCreate(
            ['farmer_id' => $farmer1->id],
            [
                'name' => 'রহিমা বেগম',
                'phone' => '01799887766',
                'relationship' => 'স্বামী/স্ত্রী',
            ]
        );

        $farmerUser2 = User::firstOrCreate(
            ['phone' => '01812345678'],
            [
                'name' => 'মোঃ কামাল উদ্দিন',
                'password' => '123456',
                'role' => 'farmer',
                'is_active' => true,
            ]
        );

        $farmer2 = Farmer::firstOrCreate(
            ['farmer_code' => 'F-009'],
            [
                'user_id' => $farmerUser2->id,
                'full_name' => 'মোঃ কামাল উদ্দিন',
                'phone' => '01812345678',
                'nid' => '1990542130456',
                'gender' => 'পুরুষ',
                'address' => 'গ্রাম: হরিপুর, উপজেলা: সদর',
                'primary_activity' => 'মিশ্র কৃষি',
                'experience_years' => 5,
                'app_access_enabled' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );
        if (! $farmer2->user_id) {
            $farmer2->update(['user_id' => $farmerUser2->id]);
        }

        $farmerUser3 = User::firstOrCreate(
            ['phone' => '01912345678'],
            [
                'name' => 'মোঃ রফিকুল ইসলাম',
                'password' => '123456',
                'role' => 'farmer',
                'is_active' => true,
            ]
        );

        $farmer3 = Farmer::firstOrCreate(
            ['farmer_code' => 'F-012'],
            [
                'user_id' => $farmerUser3->id,
                'full_name' => 'মোঃ রফিকুল ইসলাম',
                'phone' => '01912345678',
                'nid' => '1992542130789',
                'gender' => 'পুরুষ',
                'address' => 'গ্রাম: ভবানীপুর, উপজেলা: সদর',
                'primary_activity' => 'মাছ চাষ',
                'experience_years' => 4,
                'app_access_enabled' => true,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );
        if (! $farmer3->user_id) {
            $farmer3->update(['user_id' => $farmerUser3->id]);
        }

        // Link farmers to locations
        $farmer1->farmLocations()->syncWithoutDetaching([$loc1->id, $loc2->id]);
        $farmer2->farmLocations()->syncWithoutDetaching([$loc2->id]);
        $farmer3->farmLocations()->syncWithoutDetaching([$loc3->id]);

        // 5. Task Templates
        $task1 = TaskTemplate::firstOrCreate(
            ['task_code' => 'TK-001'],
            [
                'name' => 'ধানের জমিতে সার প্রয়োগ',
                'category' => 'সবজি চাষ',
                'description' => 'ইউরিয়া ও টিএসপি সার নির্দিষ্ট অনুপাতে প্রয়োগ।',
                'instructions' => 'জমির নির্দিষ্ট প্লটে অনুমোদিত মাত্রায় জৈব ও রাসায়নিক সার প্রয়োগ করুন।',
                'required_photo_count' => 3,
                'require_realtime_photo' => true,
                'allow_gallery_upload' => false,
                'require_geofence' => true,
                'recurrence_type' => 'once',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        $task2 = TaskTemplate::firstOrCreate(
            ['task_code' => 'TK-002'],
            [
                'name' => 'ফসল পর্যবেক্ষণ',
                'category' => 'মিশ্র কৃষি',
                'description' => 'গাছের বৃদ্ধি ও পোকার আক্রমণ যাচাই।',
                'instructions' => 'গাছের পাতা ও পোকামাকড়ের আক্রমণ পর্যবেক্ষণ করুন এবং রিয়েল-টাইম ছবি তুলুন।',
                'required_photo_count' => 2,
                'require_realtime_photo' => true,
                'allow_gallery_upload' => false,
                'require_geofence' => true,
                'recurrence_type' => 'daily',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        $task3 = TaskTemplate::firstOrCreate(
            ['task_code' => 'TK-003'],
            [
                'name' => 'পুকুরে খাবার প্রদান',
                'category' => 'মাছ চাষ',
                'description' => 'সকাল ও বিকেলে নির্ধারিত ফিড খাবার ছড়ানো।',
                'instructions' => 'নির্ধারিত মাত্রায় খাদ্য পুকুরের চারকোণে সমানভাবে ছড়িয়ে দিন।',
                'required_photo_count' => 2,
                'require_realtime_photo' => true,
                'allow_gallery_upload' => false,
                'require_geofence' => true,
                'recurrence_type' => 'daily',
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        $selfReportedTask = TaskTemplate::firstOrCreate(
            ['task_code' => 'TK-SELF'],
            [
                'name' => 'স্ব-প্রতিবেদিত কাজ',
                'category' => 'মাঠ পর্যায়ের কাজ',
                'description' => 'কৃষক কর্তৃক মাঠ থেকে সরাসরি ছবি ও বিবরণ সহ রিপোর্টকৃত কাজ।',
                'instructions' => 'কাজের লাইভ ছবি ও বিস্তারিত বিবরণ প্রদান করুন।',
                'required_photo_count' => 1,
                'require_realtime_photo' => true,
                'allow_gallery_upload' => false,
                'require_geofence' => false,
                'recurrence_type' => 'once',
                'is_active' => true,
                'created_by' => $admin->id ?? null,
            ]
        );

        // 6. Task Assignments (for Today)
        TaskAssignment::firstOrCreate(
            [
                'task_template_id' => $task1->id,
                'farmer_id' => $farmer1->id,
                'farm_location_id' => $loc1->id,
                'scheduled_date' => today(),
            ],
            [
                'start_time' => '08:00',
                'end_time' => '10:00',
                'priority' => 'normal',
                'status' => 'in_progress',
                'assigned_by' => $admin->id,
                'assigned_at' => now()->startOfDay()->addHours(7),
                'started_at' => now()->startOfDay()->addHours(8),
            ]
        );

        TaskAssignment::firstOrCreate(
            [
                'task_template_id' => $task2->id,
                'farmer_id' => $farmer2->id,
                'farm_location_id' => $loc2->id,
                'scheduled_date' => today(),
            ],
            [
                'start_time' => '10:00',
                'end_time' => '11:00',
                'priority' => 'normal',
                'status' => 'completed',
                'assigned_by' => $admin->id,
                'assigned_at' => now()->startOfDay()->addHours(9),
                'started_at' => now()->startOfDay()->addHours(10),
                'completed_at' => now()->startOfDay()->addHours(11),
            ]
        );

        TaskAssignment::firstOrCreate(
            [
                'task_template_id' => $task3->id,
                'farmer_id' => $farmer3->id,
                'farm_location_id' => $loc3->id,
                'scheduled_date' => today(),
            ],
            [
                'start_time' => '07:00',
                'end_time' => '08:00',
                'priority' => 'high',
                'status' => 'late',
                'assigned_by' => $admin->id,
                'assigned_at' => now()->startOfDay()->addHours(6),
            ]
        );

        // 7. Demo notifications (Laravel database notification schema)
        // Stable UUIDs make repeated seeding safe and avoid duplicate notifications.
        $demoNotifications = [
            [
                'key' => 'late-submission',
                'title' => 'Late submission: পুকুরে খাবার প্রদান',
                'message' => 'মোঃ রফিকুল ইসলাম নির্ধারিত সময় শেষে সাবমিট করেছেন।',
                'severity' => 'warning',
            ],
            [
                'key' => 'geofence-validation',
                'title' => 'GPS validation failed: সার প্রয়োগ',
                'message' => 'উত্তর মাঠের জিও-ফেন্স সীমানার বাইরে থাকার কারণে সাবমিশন আটকে দেওয়া হয়েছিল।',
                'severity' => 'danger',
            ],
            [
                'key' => 'task-completed',
                'title' => 'কাজ সম্পন্ন: ফসল পর্যবেক্ষণ',
                'message' => 'মোঃ কামাল উদ্দিন সফলভাবে আজকের কাজ ও প্রমাণ ছবি আপলোড করেছেন।',
                'severity' => 'success',
            ],
        ];

        foreach ($demoNotifications as $demoNotification) {
            $notification = new SystemNotification(
                $demoNotification['title'],
                $demoNotification['message'],
                'system',
                additionalInfo: ['severity' => $demoNotification['severity']],
            );

            $admin->notifications()->firstOrCreate(
                [
                    'id' => Uuid::uuid5(
                        Uuid::NAMESPACE_URL,
                        'https://bashiragro.local/seed/notifications/'.$demoNotification['key'],
                    )->toString(),
                ],
                [
                    'type' => SystemNotification::class,
                    'data' => $notification->toArray($admin),
                ],
            );
        }

        // 8. Activity Logs
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'task_started',
            'subject_type' => TaskAssignment::class,
            'description' => 'রহিম উদ্দিন উত্তর মাঠে কাজ শুরু করেছেন',
            'created_at' => now()->subMinutes(8),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'evidence_uploaded',
            'subject_type' => TaskAssignment::class,
            'description' => 'কামাল উদ্দিন দক্ষিণ প্লটে ৩টি প্রমাণ ছবি জমা দিয়েছেন',
            'created_at' => now()->subMinutes(22),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'location_created',
            'subject_type' => FarmLocation::class,
            'description' => 'নতুন লোকেশন তৈরি করা হয়েছে (পূর্ব পুকুর)',
            'created_at' => now()->subHour(),
        ]);
    }
}
