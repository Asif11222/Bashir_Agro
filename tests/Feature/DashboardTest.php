<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_live_database_metrics_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'F-101',
            'full_name' => 'মোঃ রহিম উদ্দিন',
            'phone' => '01711223344',
            'primary_activity' => 'সবজি চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-101',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-101',
            'name' => 'সার প্রয়োগ',
            'category' => 'সবজি চাষ',
            'instructions' => 'সার প্রয়োগ করুন',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'in_progress',
            'assigned_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ড্যাশবোর্ড');
        $response->assertSee('মোঃ রহিম উদ্দিন');
        $response->assertSee('সার প্রয়োগ');
        $response->assertSee('উত্তর মাঠ');
        $response->assertSee('আজকের কাজ');
        $response->assertSee('সক্রিয় কৃষক');
    }

    public function test_dashboard_filters_tasks_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'F-102',
            'full_name' => 'মোঃ করিম',
            'phone' => '01799887711',
            'primary_activity' => 'মাছ চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-102',
            'name' => 'দক্ষিণ প্লট',
            'type' => 'মাছের পুকুর',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-102',
            'name' => 'মাছের খাবার',
            'category' => 'মাছ চাষ',
            'instructions' => 'খাবার দিন',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'priority' => 'normal',
            'status' => 'completed',
            'assigned_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard?status=completed');

        $response->assertStatus(200);
        $response->assertSee('মাছের খাবার');

        $responseEmpty = $this->actingAs($admin)->get('/admin/dashboard?status=late');
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertSee('আজকের কোনো কাজ খুঁজে পাওয়া যায়নি');
    }
}
