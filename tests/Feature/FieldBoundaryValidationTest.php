<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\SpatialValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldBoundaryValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmerUser;

    private Farmer $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->farmerUser = User::factory()->create([
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $this->farmer = Farmer::create([
            'user_id' => $this->farmerUser->id,
            'farmer_code' => 'F-002001',
            'full_name' => 'আব্দুর রহিম',
            'phone' => '01711998877',
            'address' => 'পাবনা',
            'primary_activity' => 'ধান',
            'status' => 'active',
        ]);
    }

    public function test_submission_accepted_when_gps_is_inside_field_boundary_polygon(): void
    {
        Storage::fake('public');

        // Define a field polygon box: Latitude 24.000 to 24.010, Longitude 89.000 to 89.010
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.0000, 24.0000],
                    [89.0100, 24.0000],
                    [89.0100, 24.0100],
                    [89.0000, 24.0100],
                    [89.0000, 24.0000],
                ],
            ],
        ];

        $location = FarmLocation::create([
            'location_code' => 'LOC-002001',
            'name' => 'উত্তর ধানের মাঠ',
            'type' => 'ধানের জমি',
            'address' => 'ঈশ্বরদী',
            'boundary_geojson' => $polygon,
            'latitude' => 24.0050,
            'longitude' => 89.0050,
            'geofence_radius_meters' => 20,
            'status' => 'active',
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-002001',
            'name' => 'কীটনাশক প্রয়োগ',
            'category' => 'কীটনাশক',
            'instructions' => 'নিয়ম মেনে স্প্রে করুন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'in_progress',
        ]);

        $token = $this->farmerUser->createToken('phone')->plainTextToken;
        $photo = UploadedFile::fake()->image('spray.jpg', 640, 480);

        // Submit from strictly INSIDE the polygon (24.005, 89.005)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.005000,
                'longitude' => 89.005000,
                'accuracy' => 5.0,
                'notes' => 'মাঠে উপস্থিত থেকে কাজ সম্পন্ন করেছি।',
                'photos' => [$photo],
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'submitted',
                'geofence' => [
                    'passed' => true,
                ],
            ],
        ]);

        $this->assertEquals('completed', $assignment->fresh()->status);
        $this->assertDatabaseHas('task_submissions', [
            'task_assignment_id' => $assignment->id,
            'geofence_passed' => true,
        ]);
    }

    public function test_submission_rejected_when_gps_is_outside_field_boundary_polygon(): void
    {
        Storage::fake('public');

        // Field polygon box: Latitude 24.000 to 24.010, Longitude 89.000 to 89.010
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.0000, 24.0000],
                    [89.0100, 24.0000],
                    [89.0100, 24.0100],
                    [89.0000, 24.0100],
                    [89.0000, 24.0000],
                ],
            ],
        ];

        $location = FarmLocation::create([
            'location_code' => 'LOC-002002',
            'name' => 'দক্ষিণ ধানক্ষেত',
            'type' => 'ধানের জমি',
            'boundary_geojson' => $polygon,
            'latitude' => 24.0050,
            'longitude' => 89.0050,
            'geofence_radius_meters' => 20,
            'status' => 'active',
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-002002',
            'name' => 'আগাছা দমন',
            'category' => 'আগাছা দমন',
            'instructions' => 'আগাছা পরিষ্কার করুন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'in_progress',
        ]);

        $token = $this->farmerUser->createToken('phone')->plainTextToken;

        // Submit from OUTSIDE the polygon (24.025, 89.005 -> ~1.6 km north of polygon)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.025000,
                'longitude' => 89.005000,
                'accuracy' => 5.0,
                'notes' => 'দূরে থেকে সাবমিট করার চেষ্টা',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'geofence' => [
                'passed' => false,
                'polygon_boundary' => true,
            ],
        ]);
        $this->assertStringContainsString('ফার্ম সীমানার বাইরে', $response->json('message'));
        $this->assertEquals('in_progress', $assignment->fresh()->status);
    }

    public function test_outside_boundary_submission_allowed_when_override_geofence_confirmed(): void
    {
        $token = $this->farmerUser->createToken('phone')->plainTextToken;

        $coords = [
            [89.0000, 24.0000],
            [89.0100, 24.0000],
            [89.0100, 24.0100],
            [89.0000, 24.0100],
            [89.0000, 24.0000],
        ];

        $location = FarmLocation::create([
            'location_code' => 'LOC-002002',
            'name' => 'নাটোর ব্লক খামার B',
            'type' => 'ধানের জমি',
            'address' => 'নাটোর',
            'latitude' => 24.005000,
            'longitude' => 89.005000,
            'geofence_radius_meters' => 20,
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [$coords],
            ],
            'status' => 'active',
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'FB-VAL-02',
            'name' => 'কীটনাশক ছিটানো',
            'category' => 'বালাই ব্যবস্থাপনা',
            'instructions' => 'কীটনাশক সঠিকভাবে স্প্রে করুন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'in_progress',
        ]);

        // Submit from outside boundary (~1.5km away) with override_geofence = true
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.025000,
                'longitude' => 89.005000,
                'accuracy' => 5.0,
                'notes' => 'জরুরি প্রয়োজনে বাইরে থেকে সাবমিট',
                'override_geofence' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $assignment->refresh();
        $this->assertEquals('completed', $assignment->status);
        $this->assertNotNull($assignment->submission);
        $this->assertFalse($assignment->submission->geofence_passed);
        $this->assertEquals('flagged', $assignment->submission->verification_status);
        $this->assertStringContainsString('মাঠের বাইরে থেকে জমা দেওয়া', $assignment->submission->farmer_note);
    }

    public function test_spatial_validation_service_calculations(): void
    {
        $service = new SpatialValidationService;

        // 1km x 1km approximate box (~247 acres / 100 hectares)
        $coords = [
            [89.0000, 24.0000],
            [89.0100, 24.0000],
            [89.0100, 24.0100],
            [89.0000, 24.0100],
            [89.0000, 24.0000],
        ];

        // Ray casting point inside
        $this->assertTrue($service->isPointInPolygon(24.0050, 89.0050, $coords));

        // Ray casting point outside
        $this->assertFalse($service->isPointInPolygon(24.0200, 89.0050, $coords));

        // Centroid calculation
        $centroid = $service->calculateCentroid($coords);
        $this->assertEquals(24.005, round($centroid['latitude'], 3));
        $this->assertEquals(89.005, round($centroid['longitude'], 3));

        // Area calculation
        $acres = $service->calculatePolygonArea($coords, 'acre');
        $this->assertGreaterThan(200, $acres);
        $this->assertLessThan(300, $acres);
    }

    public function test_admin_can_create_location_with_polygon_via_web_panel(): void
    {
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.5000, 24.2000],
                    [89.5100, 24.2000],
                    [89.5100, 24.2100],
                    [89.5000, 24.2100],
                    [89.5000, 24.2000],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/locations', [
            'name' => 'পশ্চিম প্লট ও পুকুর',
            'type' => 'মাছের পুকুর',
            'address' => 'শান্তিপুর, পাবনা',
            'area' => 3.25,
            'area_unit' => 'একর',
            'latitude' => 24.2050,
            'longitude' => 89.5050,
            'geofence_radius_meters' => 30,
            'farmer_id' => $this->farmer->id,
            'boundary' => json_encode($polygon),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $response->assertSessionHas('success');

        $created = FarmLocation::where('name', 'পশ্চিম প্লট ও পুকুর')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->farmer->id, $created->farmer_id);
        $this->assertTrue($created->hasBoundaryPolygon());
        $this->assertEquals($polygon, $created->boundary_geojson);
    }

    public function test_admin_can_update_location_polygon_via_web_panel(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-002003',
            'name' => 'পুরাতন প্লট',
            'type' => 'সবজি ক্ষেত',
            'address' => 'পাবনা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $newPolygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.6000, 24.3000],
                    [89.6100, 24.3000],
                    [89.6100, 24.3100],
                    [89.6000, 24.3100],
                    [89.6000, 24.3000],
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/admin/locations/{$location->id}", [
            'name' => 'নতুন সংশোধিত প্লট',
            'type' => 'সবজি ক্ষেত',
            'address' => 'পাবনা নতুনপাড়া',
            'area' => 1.75,
            'area_unit' => 'একর',
            'latitude' => 24.3050,
            'longitude' => 89.6050,
            'geofence_radius_meters' => 25,
            'farmer_id' => $this->farmer->id,
            'boundary' => json_encode($newPolygon),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $response->assertSessionHas('success');

        $fresh = $location->fresh();
        $this->assertEquals('নতুন সংশোধিত প্লট', $fresh->name);
        $this->assertEquals($this->farmer->id, $fresh->farmer_id);
        $this->assertTrue($fresh->hasBoundaryPolygon());
        $this->assertEquals($newPolygon, $fresh->boundary_geojson);
    }
}
