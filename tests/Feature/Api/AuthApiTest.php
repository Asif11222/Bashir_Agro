<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_login_with_valid_credentials(): void
    {
        $farmerUser = User::factory()->create([
            'phone' => '01711223344',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-777001',
            'full_name' => 'আব্দুর রহিম',
            'phone' => '01711223344',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি চাষ',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01711223344',
            'password' => 'password123',
            'device_name' => 'Redmi Note 12',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'phone', 'role'],
                'farmer_profile' => ['id', 'farmer_code', 'full_name'],
            ],
        ]);

        $this->assertEquals('F-777001', $response->json('data.farmer_profile.farmer_code'));
    }

    public function test_farmer_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'phone' => '01711223355',
            'password' => Hash::make('correct_password'),
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01711223355',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $farmerUser = User::factory()->create([
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-777002',
            'full_name' => 'মোবারক হোসেন',
            'phone' => '01711223366',
            'address' => 'ঈশ্বরদী',
            'primary_activity' => 'ধান চাষ',
        ]);

        $token = $farmerUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $farmerUser->id,
                    'role' => 'farmer',
                ],
                'farmer_profile' => [
                    'id' => $farmer->id,
                    'farmer_code' => 'F-777002',
                ],
            ],
        ]);
    }

    public function test_authenticated_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }
}
