<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_location_management(): void
    {
        $response = $this->get('/admin/locations');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/admin/locations/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_location_list(): void
    {
        FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/locations');

        $response->assertStatus(200);
        $response->assertSee('ফার্ম লোকেশন');
        $response->assertSee('উত্তর মাঠ');
        $response->assertSee('LOC-000001');
        $response->assertSee('সবজি ক্ষেত');
    }

    public function test_admin_can_create_location_with_server_generated_code(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রহিম',
            'phone' => '01711223344',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/locations', [
            'name' => 'পূর্ব পুকুর',
            'type' => 'মাছের পুকুর',
            'address' => 'পুকুরপাড়, শান্তিপুর',
            'area' => 1.50,
            'area_unit' => 'একর',
            'latitude' => 24.7461000,
            'longitude' => 90.4223000,
            'geofence_radius_meters' => 25,
            'status' => 'active',
            'farmer_ids' => [$farmer->id],
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $response->assertSessionHas('success');

        $location = FarmLocation::where('name', 'পূর্ব পুকুর')->first();
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('/^LOC-\d{6}$/', $location->location_code);
        $this->assertEquals('মাছের পুকুর', $location->type);
        $this->assertEquals(25, $location->geofence_radius_meters);
        $this->assertTrue($location->farmers->contains($farmer->id));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'location.created',
            'subject_type' => FarmLocation::class,
            'subject_id' => $location->id,
        ]);
    }

    public function test_location_code_is_generated_sequentially(): void
    {
        $loc1 = FarmLocation::create([
            'location_code' => FarmLocation::generateNextLocationCode(),
            'name' => 'প্লট ১',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);
        $this->assertEquals('LOC-000001', $loc1->location_code);

        $loc2 = FarmLocation::create([
            'location_code' => FarmLocation::generateNextLocationCode(),
            'name' => 'প্লট ২',
            'type' => 'ধানের জমি',
            'address' => 'বগুড়া',
            'created_by' => $this->admin->id,
        ]);
        $this->assertEquals('LOC-000002', $loc2->location_code);
    }

    public function test_validation_failure_when_creating_location(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/locations', [
            'name' => '',
            'type' => '',
            'address' => '',
            'geofence_radius_meters' => 2, // min is 5
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'type',
            'address',
            'geofence_radius_meters',
            'status',
        ]);
    }

    public function test_admin_can_view_location_details(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'দক্ষিণ মাঠ',
            'type' => 'ধানের জমি',
            'address' => 'দক্ষিণ ইউনিয়ন',
            'area' => 3.20,
            'area_unit' => 'একর',
            'latitude' => 24.7500000,
            'longitude' => 90.4300000,
            'geofence_radius_meters' => 20,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/locations/{$location->id}");

        $response->assertStatus(200);
        $response->assertSee('লোকেশন বিবরণ');
        $response->assertSee('দক্ষিণ মাঠ');
        $response->assertSee('LOC-000001');
        $response->assertSee('ধানের জমি');
        $response->assertSee('3.20 একর');
    }

    public function test_admin_can_update_location(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'পুরনো নাম',
            'type' => 'সবজি ক্ষেত',
            'address' => 'পুরনো ঠিকানা',
            'area' => 1.00,
            'geofence_radius_meters' => 20,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/locations/{$location->id}", [
            'name' => 'নতুন নাম',
            'type' => 'ধানের জমি',
            'address' => 'নতুন ঠিকানা',
            'area' => 2.50,
            'area_unit' => 'একর',
            'latitude' => 24.1234567,
            'longitude' => 90.1234567,
            'geofence_radius_meters' => 30,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.locations.index'));

        $this->assertDatabaseHas('farm_locations', [
            'id' => $location->id,
            'name' => 'নতুন নাম',
            'type' => 'ধানের জমি',
            'status' => 'inactive',
            'geofence_radius_meters' => 30,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'location.updated',
            'subject_type' => FarmLocation::class,
            'subject_id' => $location->id,
        ]);
    }

    public function test_admin_can_soft_delete_location(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'মুছে ফেলার প্লট',
            'type' => 'গরুর খামার',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/locations/{$location->id}");

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertSoftDeleted('farm_locations', ['id' => $location->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'location.deleted',
            'subject_type' => FarmLocation::class,
            'subject_id' => $location->id,
        ]);
    }

    public function test_search_locations_by_name_code_type_or_address(): void
    {
        FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'গাজীপুর',
            'created_by' => $this->admin->id,
        ]);

        FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'দক্ষিণ পুকুর',
            'type' => 'মাছের পুকুর',
            'address' => 'ময়মনসিংহ',
            'created_by' => $this->admin->id,
        ]);

        $responseName = $this->actingAs($this->admin)->get('/admin/locations?search=উত্তর');
        $responseName->assertSee('উত্তর মাঠ');
        $responseName->assertDontSee('দক্ষিণ পুকুর');

        $responseCode = $this->actingAs($this->admin)->get('/admin/locations?search=LOC-000002');
        $responseCode->assertSee('দক্ষিণ পুকুর');
        $responseCode->assertDontSee('উত্তর মাঠ');

        $responseType = $this->actingAs($this->admin)->get('/admin/locations?search=সবজি');
        $responseType->assertSee('উত্তর মাঠ');
        $responseType->assertDontSee('দক্ষিণ পুকুর');
    }

    public function test_filter_locations_by_status(): void
    {
        FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'সক্রিয় প্লট',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'নিষ্ক্রিয় প্লট',
            'type' => 'ধানের জমি',
            'address' => 'ঢাকা',
            'status' => 'inactive',
            'created_by' => $this->admin->id,
        ]);

        $responseActive = $this->actingAs($this->admin)->get('/admin/locations?status=active');
        $responseActive->assertSee('সক্রিয় প্লট');
        $responseActive->assertDontSee('নিষ্ক্রিয় প্লট');

        $responseInactive = $this->actingAs($this->admin)->get('/admin/locations?status=inactive');
        $responseInactive->assertSee('নিষ্ক্রিয় প্লট');
        $responseInactive->assertDontSee('সক্রিয় প্লট');
    }

    public function test_location_pagination_works(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            FarmLocation::create([
                'location_code' => sprintf('LOC-%06d', $i),
                'name' => "Location {$i}",
                'type' => 'সবজি ক্ষেত',
                'address' => 'ঢাকা',
                'status' => 'active',
                'created_by' => $this->admin->id,
            ]);
        }

        $responsePage1 = $this->actingAs($this->admin)->get('/admin/locations?status=active');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('Location 25'); // latest first

        $responsePage2 = $this->actingAs($this->admin)->get('/admin/locations?status=active&page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Location 1');
    }

    public function test_admin_can_attach_farmer_to_location(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ সেলিম',
            'phone' => '01700112233',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/locations/{$location->id}/farmers", [
            'farmer_id' => $farmer->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue($location->fresh()->farmers->contains($farmer->id));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'location.assigned',
            'subject_type' => FarmLocation::class,
            'subject_id' => $location->id,
        ]);
    }

    public function test_admin_can_detach_farmer_from_location_without_deleting_farmer(): void
    {
        $location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ সেলিম',
            'phone' => '01700112233',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $location->farmers()->attach($farmer->id);
        $this->assertTrue($location->fresh()->farmers->contains($farmer->id));

        $response = $this->actingAs($this->admin)->delete("/admin/locations/{$location->id}/farmers/{$farmer->id}");

        $response->assertRedirect();
        $this->assertFalse($location->fresh()->farmers->contains($farmer->id));

        // Ensure farmer record is NOT deleted
        $this->assertDatabaseHas('farmers', ['id' => $farmer->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'location.unassigned',
            'subject_type' => FarmLocation::class,
            'subject_id' => $location->id,
        ]);
    }
}
