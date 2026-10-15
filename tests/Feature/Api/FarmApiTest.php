<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $farmerUser;

    private Farmer $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->farmerUser = User::factory()->create([
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $this->farmer = Farmer::create([
            'user_id' => $this->farmerUser->id,
            'farmer_code' => 'F-001001',
            'full_name' => 'রহিম উদ্দিন',
            'phone' => '01712345678',
            'address' => 'ঈশ্বরদী, পাবনা',
            'primary_activity' => 'ধান',
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/farms');
        $response->assertStatus(401);
    }

    public function test_farmer_can_list_their_assigned_farms(): void
    {
        $samplePolygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.5000, 24.1000],
                    [89.5050, 24.1000],
                    [89.5050, 24.1050],
                    [89.5000, 24.1050],
                    [89.5000, 24.1000],
                ],
            ],
        ];

        // Farm assigned to this farmer
        $myFarm = FarmLocation::create([
            'farmer_id' => $this->farmer->id,
            'location_code' => 'LOC-000101',
            'name' => 'উত্তর ধানের মাঠ',
            'type' => 'ধানের জমি',
            'address' => 'ঈশ্বরদী',
            'boundary_geojson' => $samplePolygon,
            'area' => 2.50,
            'area_unit' => 'একর',
            'latitude' => 24.1025,
            'longitude' => 89.5025,
            'status' => 'active',
        ]);

        // Another farm belonging to a different farmer
        $otherFarmer = Farmer::create([
            'farmer_code' => 'F-001002',
            'full_name' => 'করিম মিয়া',
            'phone' => '01812345678',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি',
            'status' => 'active',
        ]);

        FarmLocation::create([
            'farmer_id' => $otherFarmer->id,
            'location_code' => 'LOC-000102',
            'name' => 'অন্য কৃষকের জমি',
            'type' => 'সবজি',
            'address' => 'পাবনা',
            'status' => 'active',
        ]);

        $token = $this->farmerUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/farms');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
            'data' => [
                [
                    'id' => $myFarm->id,
                    'location_code' => 'LOC-000101',
                    'name' => 'উত্তর ধানের মাঠ',
                    'area' => 2.5,
                    'boundary' => $samplePolygon,
                ],
            ],
        ]);
    }

    public function test_farmer_can_view_single_farm_details(): void
    {
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.5000, 24.1000],
                    [89.5050, 24.1000],
                    [89.5050, 24.1050],
                    [89.5000, 24.1050],
                    [89.5000, 24.1000],
                ],
            ],
        ];

        $farm = FarmLocation::create([
            'farmer_id' => $this->farmer->id,
            'location_code' => 'LOC-000103',
            'name' => 'পূর্ব ধানের মাঠ',
            'type' => 'ধানের জমি',
            'address' => 'ঈশ্বরদী',
            'boundary_geojson' => $polygon,
            'area' => 1.80,
            'area_unit' => 'একর',
            'latitude' => 24.1025,
            'longitude' => 89.5025,
            'status' => 'active',
        ]);

        $token = $this->farmerUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/farms/{$farm->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $farm->id,
                'name' => 'পূর্ব ধানের মাঠ',
                'boundary' => $polygon,
                'area' => 1.8,
            ],
        ]);
    }

    public function test_admin_can_create_farm_boundary_via_api(): void
    {
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.5100, 24.1100],
                    [89.5200, 24.1100],
                    [89.5200, 24.1200],
                    [89.5100, 24.1200],
                    [89.5100, 24.1100],
                ],
            ],
        ];

        $token = $this->adminUser->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/farms', [
                'name' => 'দক্ষিণ আম বাগান',
                'type' => 'ফলের বাগান',
                'address' => 'রূপপুর, ঈশ্বরদী',
                'farmer_id' => $this->farmer->id,
                'boundary' => $polygon,
                'area_unit' => 'একর',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'দক্ষিণ আম বাগান',
                'type' => 'ফলের বাগান',
                'farmer_id' => $this->farmer->id,
            ],
        ]);

        $this->assertDatabaseHas('farm_locations', [
            'name' => 'দক্ষিণ আম বাগান',
            'farmer_id' => $this->farmer->id,
        ]);

        $farm = FarmLocation::where('name', 'দক্ষিণ আম বাগান')->first();
        $this->assertNotNull($farm);
        $this->assertNotNull($farm->boundary_geojson);
        $this->assertGreaterThan(0, (float) $farm->area);
        $this->assertNotNull($farm->latitude);
        $this->assertNotNull($farm->longitude);
    }

    public function test_admin_can_update_farm_boundary_via_api(): void
    {
        $farm = FarmLocation::create([
            'farmer_id' => $this->farmer->id,
            'location_code' => 'LOC-000104',
            'name' => 'পুরানো মাঠ',
            'type' => 'সবজি',
            'address' => 'ঈশ্বরদী',
            'status' => 'active',
        ]);

        $newPolygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [89.5300, 24.1300],
                    [89.5400, 24.1300],
                    [89.5400, 24.1400],
                    [89.5300, 24.1400],
                    [89.5300, 24.1300],
                ],
            ],
        ];

        $token = $this->adminUser->createToken('admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/farms/{$farm->id}", [
                'name' => 'হালনাগাদ মাঠ',
                'boundary' => $newPolygon,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'হালনাগাদ মাঠ',
                'boundary' => $newPolygon,
            ],
        ]);

        $this->assertEquals('হালনাগাদ মাঠ', $farm->fresh()->name);
        $this->assertNotNull($farm->fresh()->boundary_geojson);
    }

    public function test_invalid_geojson_boundary_is_rejected(): void
    {
        $token = $this->adminUser->createToken('admin')->plainTextToken;

        // Malformed geometry (not a polygon)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/farms', [
                'name' => 'ত্রুটিপূর্ণ সীমানা',
                'boundary' => [
                    'type' => 'Point',
                    'coordinates' => [89.5, 24.1],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['boundary']);
    }
}
