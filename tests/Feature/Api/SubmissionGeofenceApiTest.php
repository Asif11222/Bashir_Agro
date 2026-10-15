<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionGeofenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_geofence_service_calculates_distance_correctly(): void
    {
        $service = new GeofenceService;

        // Exact same point
        $distanceZero = $service->calculateDistance(24.000000, 89.000000, 24.000000, 89.000000);
        $this->assertEquals(0.0, round($distanceZero, 2));

        // Roughly 111 meters difference along latitude (0.001 deg lat ~ 111m)
        $distanceShort = $service->calculateDistance(24.000000, 89.000000, 24.001000, 89.000000);
        $this->assertGreaterThan(100, $distanceShort);
        $this->assertLessThan(125, $distanceShort);
    }

    public function test_submission_succeeds_when_within_geofence(): void
    {
        Storage::fake('public');

        $farmerUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-777020',
            'full_name' => 'জামাল করিম',
            'phone' => '01711000020',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-777020',
            'name' => 'সবজি প্লট ৩',
            'type' => 'সবজি',
            'latitude' => 24.000000,
            'longitude' => 89.000000,
            'geofence_radius_meters' => 50,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-777020',
            'name' => 'ফসল সংগ্রহ',
            'category' => 'ফসল কাটা',
            'instructions' => 'ফসল সংগ্রহ করুন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'in_progress',
        ]);

        $token = $farmerUser->createToken('device')->plainTextToken;

        $photo = UploadedFile::fake()->image('harvest.jpg', 600, 600);

        // Submit from almost identical coordinates (within 5 meters)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.000010,
                'longitude' => 89.000010,
                'accuracy' => 4.5,
                'notes' => 'কাজটি সঠিকভাবে সম্পন্ন করা হয়েছে।',
                'photos' => [$photo],
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'geofence' => [
                    'passed' => true,
                ],
                'status' => 'submitted',
            ],
        ]);

        $this->assertEquals('completed', $assignment->fresh()->status);
        $this->assertDatabaseHas('task_submissions', [
            'task_assignment_id' => $assignment->id,
            'geofence_passed' => true,
        ]);
        $this->assertDatabaseHas('evidence_photos', [
            'latitude' => 24.00001,
        ]);
    }

    public function test_submission_fails_when_outside_geofence(): void
    {
        $farmerUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-777021',
            'full_name' => 'আবুল কালাম',
            'phone' => '01711000021',
            'address' => 'পাবনা',
            'primary_activity' => 'ধান',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-777021',
            'name' => 'দক্ষিণ প্লট',
            'type' => 'ধান',
            'latitude' => 24.000000,
            'longitude' => 89.000000,
            'geofence_radius_meters' => 30, // 30 meters allowed
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-777021',
            'name' => 'সার প্রয়োগ',
            'category' => 'সার',
            'instructions' => 'সার দিন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'in_progress',
        ]);

        $token = $farmerUser->createToken('device')->plainTextToken;

        // Submit from ~500 meters away
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.005000,
                'longitude' => 89.000000,
                'accuracy' => 5.0,
                'notes' => 'দূরে থেকে জমা দেওয়ার চেষ্টা',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'geofence' => [
                'passed' => false,
            ],
        ]);
        $this->assertStringContainsString('জিও-ফেন্স', $response->json('message'));
        $this->assertEquals('in_progress', $assignment->fresh()->status);
    }
}
