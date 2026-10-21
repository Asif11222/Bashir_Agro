<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_global_search(): void
    {
        $response = $this->getJson(route('admin.global-search', ['q' => 'রহিম']));

        $response->assertUnauthorized();
    }

    public function test_search_returns_empty_when_query_is_less_than_two_characters(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.global-search', ['q' => 'a']));

        $response->assertOk()
            ->assertJson([
                'query' => 'a',
                'total' => 0,
                'groups' => [],
            ]);
    }

    public function test_search_finds_matching_farmers_locations_tasks_and_assignments(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'F-00001',
            'full_name' => 'কমল বিশ্বাস',
            'phone' => '01712345678',
            'primary_activity' => 'ধান চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-00001',
            'name' => 'কমলপুর মাঠ',
            'address' => 'কমলপুর, ময়মনসিংহ',
            'type' => 'ধান ক্ষেত',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TASK-001',
            'name' => 'কম্পোস্ট সার প্রদান',
            'category' => 'সার প্রয়োগ',
            'instructions' => 'সার প্রয়োগ করুন',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'assigned',
            'priority' => 'high',
            'assigned_by' => $admin->id,
        ]);

        // Query by "কম" should match farmer কামাল, location কমলপুর, task কম্পোস্ট
        $response = $this->actingAs($admin)->getJson(route('admin.global-search', ['q' => 'কম']));

        $response->assertOk();
        $data = $response->json();

        $this->assertGreaterThanOrEqual(3, $data['total']);
        $groupNames = array_column($data['groups'], 'name');
        $this->assertContains('কৃষক', $groupNames);
        $this->assertContains('ফার্ম লোকেশন', $groupNames);
        $this->assertContains('কাজের টেমপ্লেট', $groupNames);
    }

    public function test_search_finds_farmer_by_phone_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        Farmer::create([
            'farmer_code' => 'F-00002',
            'full_name' => 'সাকিব হাসান',
            'phone' => '01909876543',
            'primary_activity' => 'মৎস্য চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.global-search', ['q' => '019098']));

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('groups.0.name', 'কৃষক')
            ->assertJsonPath('groups.0.items.0.title', 'সাকিব হাসান');
    }

    public function test_search_returns_empty_when_no_records_match(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.global-search', ['q' => 'NonExistentXYZ999']));

        $response->assertOk()
            ->assertJson([
                'total' => 0,
                'groups' => [],
            ]);
    }
}
