<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_retrieve_only_their_assigned_tasks(): void
    {
        $farmerUser1 = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer1 = Farmer::create([
            'user_id' => $farmerUser1->id,
            'farmer_code' => 'F-777010',
            'full_name' => 'কৃষক ১',
            'phone' => '01711000001',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি',
        ]);

        $farmerUser2 = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer2 = Farmer::create([
            'user_id' => $farmerUser2->id,
            'farmer_code' => 'F-777011',
            'full_name' => 'কৃষক ২',
            'phone' => '01711000002',
            'address' => 'পাবনা',
            'primary_activity' => 'ধান',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-777010',
            'name' => 'মাঠ ১',
            'type' => 'সবজি',
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-777010',
            'name' => 'আগাছা দমন',
            'category' => 'পরিচর্যা',
            'instructions' => 'আগাছা তুলুন',
            'status' => 'active',
        ]);

        // Assignment for farmer 1
        TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer1->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'assigned',
        ]);

        // Assignment for farmer 2
        TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer2->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'assigned',
        ]);

        $token1 = $farmerUser1->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->getJson('/api/v1/assignments');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($farmer1->id, $response->json('data.0.farmer.id'));
    }

    public function test_farmer_can_start_assignment(): void
    {
        $farmerUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-777012',
            'full_name' => 'কৃষক ৩',
            'phone' => '01711000003',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-777012',
            'name' => 'মাঠ ২',
            'type' => 'সবজি',
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-777012',
            'name' => 'পানি সেচ',
            'category' => 'সেচ',
            'instructions' => 'পানি দিন',
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'assigned',
        ]);

        $token = $farmerUser->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/start");

        $response->assertStatus(200);
        $this->assertEquals('in_progress', $response->json('data.status'));
        $this->assertEquals('in_progress', $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->started_at);
    }
}
