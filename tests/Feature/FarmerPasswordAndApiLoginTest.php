<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerPasswordAndApiLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private FarmLocation $location;

    private TaskTemplate $taskTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->location = FarmLocation::create([
            'location_code' => 'LOC-001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->taskTemplate = TaskTemplate::create([
            'task_code' => 'TK-001',
            'name' => 'ধানের জমিতে সার প্রয়োগ',
            'category' => 'সবজি চাষ',
            'instructions' => 'নিয়ম মেনে সার প্রয়োগ করুন।',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_farmer_with_password_and_farmer_can_login_via_api(): void
    {
        // 1. Admin accesses farmer create page
        $createPageResponse = $this->actingAs($this->admin)->get('/admin/farmers/create');
        $createPageResponse->assertStatus(200);
        $createPageResponse->assertSee('লগইন ও অ্যাপ অ্যাক্সেস');
        $createPageResponse->assertSee('অস্থায়ী পাসওয়ার্ড');
        $createPageResponse->assertSee('প্রধান কাজের এলাকা');
        $createPageResponse->assertSee('অ্যাকাউন্ট তৈরির অগ্রগতি');
        $createPageResponse->assertSee('বরাদ্দকৃত সম্পদ (প্রাথমিক)');

        // 2. Admin submits farmer creation form with password and primary location
        $farmerPhone = '01712345678';
        $farmerPassword = 'FarmerSecretPass123';

        $storeResponse = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => 'মোঃ রফিকুল ইসলাম',
            'phone' => $farmerPhone,
            'password' => $farmerPassword,
            'address' => 'পাবনা সদর, পাবনা',
            'primary_activity' => 'সবজি চাষ',
            'experience_years' => 5,
            'farm_location_id' => $this->location->id,
            'initial_task_id' => $this->taskTemplate->id,
            'app_access_enabled' => '1',
            'status' => 'active',
            'emergency_contact_name' => 'মোসাম্মৎ আয়েশা',
            'emergency_contact_phone' => '01812345678',
            'emergency_contact_relationship' => 'স্বামী/স্ত্রী',
        ]);

        $storeResponse->assertRedirect('/admin/farmers');
        $storeResponse->assertSessionHas('success');

        // Verify farmer and linked user were created
        $farmer = Farmer::where('phone', $farmerPhone)->first();
        $this->assertNotNull($farmer);
        $this->assertEquals('মোঃ রফিকুল ইসলাম', $farmer->full_name);
        $this->assertTrue($farmer->farmLocations->contains($this->location->id));
        $this->assertCount(1, $farmer->taskAssignments);

        $user = User::where('phone', $farmerPhone)->first();
        $this->assertNotNull($user);
        $this->assertEquals($farmer->user_id, $user->id);
        $this->assertEquals('farmer', $user->role);
        $this->assertTrue($user->is_active);

        // 3. Farmer logs in via Mobile API HTTP request using Phone Number and Password
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => $farmerPhone,
            'password' => $farmerPassword,
            'device_name' => 'Flutter-Mobile-Device',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJson([
            'success' => true,
            'message' => 'লগইন সফল হয়েছে।',
            'data' => [
                'user' => [
                    'phone' => $farmerPhone,
                    'role' => 'farmer',
                ],
            ],
        ]);

        $responseData = $loginResponse->json('data');
        $this->assertNotEmpty($responseData['token']);
        $this->assertEquals('Bearer', $responseData['token_type']);
        $this->assertNotNull($responseData['farmer_profile']);
        $this->assertEquals($farmer->farmer_code, $responseData['farmer_profile']['farmer_code']);

        // 4. Test API login using Bengali digits in phone number (০১৭১২৩৪৫৬৭৮)
        $bnPhone = '০১৭১২৩৪৫৬৭৮';
        $loginBnResponse = $this->postJson('/api/v1/auth/login', [
            'login' => $bnPhone,
            'password' => $farmerPassword,
        ]);
        $loginBnResponse->assertStatus(200);
        $this->assertTrue($loginBnResponse->json('success'));

        // 5. Test API login using Farmer Code (e.g. F-000001)
        $loginCodeResponse = $this->postJson('/api/v1/auth/login', [
            'login' => $farmer->farmer_code,
            'password' => $farmerPassword,
        ]);
        $loginCodeResponse->assertStatus(200);
        $this->assertTrue($loginCodeResponse->json('success'));

        // 6. Test with authenticated API profile endpoint
        auth()->logout();
        $this->flushSession();

        $token = $responseData['token'];
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('data.user.phone', $farmerPhone);
        $meResponse->assertJsonPath('data.farmer_profile.farmer_code', $farmer->farmer_code);
    }
}
