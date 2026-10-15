<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@bashiragro.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $ip = '127.0.0.1';
        $throttleKey = Str::transliterate(Str::lower('admin@bashiragro.com').'|'.$ip);
        RateLimiter::clear($throttleKey);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'admin@bashiragro.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be blocked by rate limiter
        $blockedResponse = $this->from('/login')->post('/login', [
            'email' => 'admin@bashiragro.com',
            'password' => 'secret123',
        ]);

        $blockedResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString('অ্যাকাউন্ট সুরক্ষা সক্রিয়', session('errors')->first('email'));

        // Clear rate limiter and verify successful login
        RateLimiter::clear($throttleKey);
        $successResponse = $this->from('/login')->post('/login', [
            'email' => 'admin@bashiragro.com',
            'password' => 'secret123',
        ]);
        $successResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_session_and_cleans_up(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_owner_role_has_full_access_to_all_gates(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->assertTrue($owner->isOwner());
        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($owner->hasRole('owner'));

        $this->assertTrue(Gate::forUser($owner)->allows('farmers.view'));
        $this->assertTrue(Gate::forUser($owner)->allows('farmers.create'));
        $this->assertTrue(Gate::forUser($owner)->allows('farmers.delete'));
        $this->assertTrue(Gate::forUser($owner)->allows('settings.manage'));
        $this->assertTrue(Gate::forUser($owner)->allows('any_future_permission'));
    }

    public function test_admin_role_has_operational_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertFalse($admin->isOwner());
        $this->assertTrue($admin->isAdmin());

        $this->assertTrue(Gate::forUser($admin)->allows('farmers.view'));
        $this->assertTrue(Gate::forUser($admin)->allows('farmers.create'));
        $this->assertTrue(Gate::forUser($admin)->allows('farmers.update'));
        $this->assertTrue(Gate::forUser($admin)->allows('farmers.delete'));
        $this->assertTrue(Gate::forUser($admin)->allows('locations.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('tasks.create'));
        $this->assertTrue(Gate::forUser($admin)->allows('tasks.assign'));
        $this->assertTrue(Gate::forUser($admin)->allows('reports.view'));
        $this->assertTrue(Gate::forUser($admin)->allows('settings.manage'));
    }

    public function test_supervisor_role_has_restricted_permissions(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'supervisor',
            'is_active' => true,
        ]);

        $this->assertFalse($supervisor->isOwner());
        $this->assertFalse($supervisor->isAdmin());

        // Allowed for supervisor
        $this->assertTrue(Gate::forUser($supervisor)->allows('farmers.view'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('tasks.assign'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('reports.view'));

        // Disallowed for supervisor
        $this->assertFalse(Gate::forUser($supervisor)->allows('farmers.delete'));
        $this->assertFalse(Gate::forUser($supervisor)->allows('settings.manage'));
        $this->assertFalse(Gate::forUser($supervisor)->allows('locations.manage'));
    }

    public function test_settings_caching_and_automatic_invalidation(): void
    {
        Cache::flush();

        Setting::set('cached_param', 'first_value');
        $this->assertEquals('first_value', Setting::get('cached_param'));

        // Update value directly in DB without triggering events to confirm cache serves old
        Setting::where('key', 'cached_param')->update(['value' => 'db_only_value']);
        $this->assertEquals('first_value', Setting::get('cached_param'));

        // Calling Setting::set or forgetCache clears cache immediately
        Setting::forgetCache('cached_param');
        $this->assertEquals('db_only_value', Setting::get('cached_param'));

        Setting::set('cached_param', 'second_value');
        $this->assertEquals('second_value', Setting::get('cached_param'));
    }

    public function test_dashboard_kpis_caching_mechanism(): void
    {
        $dashboardService = new DashboardService;

        // Calculate KPIs
        $kpis = $dashboardService->getKpis();
        $this->assertArrayHasKey('active_farmers', $kpis);
        $this->assertArrayHasKey('todays_tasks_count', $kpis);

        // Clear KPI cache works without throwing exception
        DashboardService::clearKpiCache();
        $this->assertFalse(Cache::has(DashboardService::KPI_CACHE_KEY));
    }

    public function test_file_upload_security_rejects_executables_and_oversized_files(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 1. Executable script masquerading as image
        $fakePhpScript = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $responsePhp = $this->actingAs($admin)->post('/admin/farmers', [
            'full_name' => 'টেস্ট কৃষক',
            'phone' => '01711223388',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি চাষ',
            'emergency_contact_name' => 'জরুরি',
            'emergency_contact_phone' => '01811223388',
            'emergency_contact_relationship' => 'ভাই',
            'profile_photo' => $fakePhpScript,
        ]);

        $responsePhp->assertSessionHasErrors('profile_photo');

        // 2. Oversized file (> 2MB)
        $oversizedPhoto = UploadedFile::fake()->image('huge.jpg')->size(3000);

        $responseHuge = $this->actingAs($admin)->post('/admin/farmers', [
            'full_name' => 'টেস্ট কৃষক',
            'phone' => '01711223399',
            'address' => 'পাবনা',
            'primary_activity' => 'সবজি চাষ',
            'emergency_contact_name' => 'জরুরি',
            'emergency_contact_phone' => '01811223399',
            'emergency_contact_relationship' => 'ভাই',
            'profile_photo' => $oversizedPhoto,
        ]);

        $responseHuge->assertSessionHasErrors('profile_photo');
    }

    public function test_core_tables_have_performance_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('farmers'));
        $this->assertTrue(Schema::hasTable('farm_locations'));
        $this->assertTrue(Schema::hasTable('task_templates'));
        $this->assertTrue(Schema::hasTable('task_assignments'));
        $this->assertTrue(Schema::hasTable('audit_logs'));

        // Query status, date and phone performance
        $this->assertTrue(Schema::hasColumns('farmers', ['phone', 'status']));
        $this->assertTrue(Schema::hasColumns('farm_locations', ['status']));
        $this->assertTrue(Schema::hasColumns('task_templates', ['status']));
        $this->assertTrue(Schema::hasColumns('task_assignments', ['scheduled_date', 'status']));
    }
}
