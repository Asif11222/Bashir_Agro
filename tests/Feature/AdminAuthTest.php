<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('আমার ফার্ম');
        $response->assertSee('লগইন করুন');
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bashiragro.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@bashiragro.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_authenticate_using_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '01700000000',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => '01700000000',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@bashiragro.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@bashiragro.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_admin_logout_with_idle_reason_redirects_with_reason_param(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout?reason=idle');

        $this->assertGuest();
        $response->assertRedirect('/login?reason=idle');
    }

    public function test_admin_logout_via_ajax_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/logout', ['reason' => 'idle']);

        $this->assertGuest();
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'logged_out',
            'redirect' => route('login', ['reason' => 'idle']),
        ]);
    }

    public function test_session_keep_alive_endpoint_refreshes_session_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('admin.session.keep-alive'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'active',
        ]);
    }

    public function test_session_keep_alive_endpoint_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.session.keep-alive'));

        $response->assertStatus(401);
    }

    public function test_login_screen_displays_idle_warning_banner_when_reason_is_idle(): void
    {
        $response = $this->get('/login?reason=idle');

        $response->assertStatus(200);
        $response->assertSee('সেশনের মেয়াদ শেষ হয়েছে');
        $response->assertSee('নিষ্ক্রিয় থাকার কারণে অ্যাকাউন্ট সুরক্ষার্থে সেশন সমাপ্ত হয়েছে');
    }
}
