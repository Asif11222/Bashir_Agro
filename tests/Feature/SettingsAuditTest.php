<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_access_settings(): void
    {
        $response = $this->get('/admin/settings');
        $response->assertRedirect('/login');

        $putResponse = $this->put('/admin/settings', []);
        $putResponse->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_access_audit_logs(): void
    {
        $response = $this->get('/admin/audit');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertSee('সিস্টেম কনফিগারেশন সেটিংস');
        $response->assertSee('সাধারণ খামার তথ্য');
        $response->assertSee('কাজের ডিফল্ট কনফিগারেশন');
        $response->assertSee('ফার্ম লোকেশন ও জিও-ফেন্স');
        $response->assertSee('সিস্টেম ও ডিসপ্লে');
        $response->assertSee('আমার ফার্ম');
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->createAdmin();

        $payload = [
            'farm_name' => 'বশির এগ্রো লিমিটেড',
            'company_phone' => '01712345678',
            'company_email' => 'info@bashiragro.com',
            'company_address' => 'ঈশ্বরদী, পাবনা',
            'default_task_priority' => 'urgent',
            'default_task_duration' => 180,
            'default_geofence_radius' => 35,
            'pagination_size' => 25,
            'date_format' => 'd/m/Y',
        ];

        $response = $this->actingAs($admin)->put('/admin/settings', $payload);

        $response->assertRedirect('/admin/settings');
        $response->assertSessionHas('success');

        $this->assertEquals('বশির এগ্রো লিমিটেড', Setting::get('farm_name'));
        $this->assertEquals('01712345678', Setting::get('company_phone'));
        $this->assertEquals('info@bashiragro.com', Setting::get('company_email'));
        $this->assertEquals('urgent', Setting::get('default_task_priority'));
        $this->assertEquals(180, Setting::get('default_task_duration'));
        $this->assertEquals(35, Setting::get('default_geofence_radius'));
        $this->assertEquals(25, Setting::get('pagination_size'));
        $this->assertEquals('d/m/Y', Setting::get('date_format'));
    }

    public function test_settings_validation_rejects_invalid_inputs(): void
    {
        $admin = $this->createAdmin();

        $invalidPayload = [
            'farm_name' => '', // required
            'company_phone' => '', // required
            'company_email' => 'not-an-email', // invalid email
            'company_address' => '', // required
            'default_task_priority' => 'invalid_priority', // not in allowed
            'default_task_duration' => 5, // min 10
            'default_geofence_radius' => 2000, // max 1000
            'pagination_size' => 999, // not in allowed
            'date_format' => 'invalid_format',
        ];

        $response = $this->actingAs($admin)->put('/admin/settings', $invalidPayload);

        $response->assertSessionHasErrors([
            'farm_name',
            'company_phone',
            'company_email',
            'company_address',
            'default_task_priority',
            'default_task_duration',
            'default_geofence_radius',
            'pagination_size',
            'date_format',
        ]);
    }

    public function test_updating_settings_creates_audit_log_entry(): void
    {
        $admin = $this->createAdmin();

        SettingsService::ensureDefaults();

        $payload = [
            'farm_name' => 'নতুন খামার নাম',
            'company_phone' => '+880 1700-000000',
            'company_email' => 'contact@bashiragro.com',
            'company_address' => 'পাবনা, বাংলাদেশ',
            'default_task_priority' => 'normal',
            'default_task_duration' => 120,
            'default_geofence_radius' => 20,
            'pagination_size' => 20,
            'date_format' => 'Y-m-d',
        ];

        $this->actingAs($admin)->put('/admin/settings', $payload);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'settings.updated',
            'module' => 'settings',
        ]);

        $auditLog = AuditLog::where('action', 'settings.updated')->first();
        $this->assertNotNull($auditLog);
        $this->assertArrayHasKey('farm_name', $auditLog->new_values);
        $this->assertEquals('নতুন খামার নাম', $auditLog->new_values['farm_name']);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = $this->createAdmin();

        AuditService::log(
            action: 'farmer.created',
            module: 'farmer',
            description: 'নতুন কৃষক আবুল কালাম যোগ করা হয়েছে'
        );

        $response = $this->actingAs($admin)->get('/admin/audit');

        $response->assertStatus(200);
        $response->assertSee('অডিট ট্রেইল ও নিরাপত্তা লগ');
        $response->assertSee('নতুন কৃষক আবুল কালাম যোগ করা হয়েছে');
        $response->assertSee('farmer.created');
    }

    public function test_audit_logs_can_be_filtered_by_module_and_action(): void
    {
        $admin = $this->createAdmin();

        AuditService::log('farmer.created', 'farmer', 'কৃষক তৈরি');
        AuditService::log('task.deleted', 'task', 'কাজ মুছে ফেলা হয়েছে');

        // Filter by module
        $responseModule = $this->actingAs($admin)->get('/admin/audit?module=farmer');
        $responseModule->assertStatus(200);
        $responseModule->assertSee('কৃষক তৈরি');
        $responseModule->assertDontSee('কাজ মুছে ফেলা হয়েছে');

        // Filter by action
        $responseAction = $this->actingAs($admin)->get('/admin/audit?action=task.deleted');
        $responseAction->assertStatus(200);
        $responseAction->assertSee('কাজ মুছে ফেলা হয়েছে');
        $responseAction->assertDontSee('কৃষক তৈরি');
    }

    public function test_audit_logs_can_be_filtered_by_user(): void
    {
        $admin1 = $this->createAdmin();
        $admin2 = User::factory()->create([
            'name' => 'দ্বিতীয় অ্যাডমিন',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin1);
        AuditService::log('report.exported', 'reports', 'অ্যাডমিন ১ রিপোর্ট ডাউনলোড করেছে');

        $this->actingAs($admin2);
        AuditService::log('location.updated', 'location', 'অ্যাডমিন ২ লোকেশন হালনাগাদ করেছে');

        $response = $this->actingAs($admin1)->get("/admin/audit?user_id={$admin1->id}");
        $response->assertStatus(200);
        $response->assertSee('অ্যাডমিন ১ রিপোর্ট ডাউনলোড করেছে');
        $response->assertDontSee('অ্যাডমিন ২ লোকেশন হালনাগাদ করেছে');
    }

    public function test_audit_logs_search_functionality(): void
    {
        $admin = $this->createAdmin();

        AuditService::log('farmer.updated', 'farmer', 'মোবারক হোসেন এর মোবাইল পরিবর্তন');
        AuditService::log('assignment.created', 'assignment', 'ধান কাটার কাজ বরাদ্দ');

        $searchResponse = $this->actingAs($admin)->get('/admin/audit?search=মোবারক');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('মোবারক হোসেন এর মোবাইল পরিবর্তন');
        $searchResponse->assertDontSee('ধান কাটার কাজ বরাদ্দ');
    }

    public function test_audit_logs_pagination_works(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 25; $i++) {
            AuditService::log(
                action: 'system.event',
                module: 'system',
                description: "পরীক্ষামূলক অডিট ইভেন্ট #{$i}"
            );
        }

        $page1Response = $this->actingAs($admin)->get('/admin/audit?page=1');
        $page1Response->assertStatus(200);

        $page2Response = $this->actingAs($admin)->get('/admin/audit?page=2');
        $page2Response->assertStatus(200);
    }

    public function test_setting_model_casts_and_helpers(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        // Test integer
        Setting::set('test_int', 42);
        $this->assertSame(42, Setting::get('test_int'));

        // Test boolean
        Setting::set('test_bool', true);
        $this->assertTrue(Setting::get('test_bool'));

        // Test fallback default
        $this->assertSame('default_val', Setting::get('non_existing_key', 'default_val'));
    }

    public function test_admin_can_upload_and_remove_logo_and_favicon(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $logoFile = UploadedFile::fake()->image('custom_logo.png', 120, 120);
        $faviconFile = UploadedFile::fake()->image('custom_favicon.png', 32, 32);

        $payload = [
            'farm_name' => 'আমার নিজস্ব ফার্ম',
            'company_phone' => '01711223344',
            'company_email' => 'admin@customfarm.com',
            'company_address' => 'ঢাকা, বাংলাদেশ',
            'default_task_priority' => 'normal',
            'default_task_duration' => 60,
            'default_geofence_radius' => 25,
            'pagination_size' => 20,
            'date_format' => 'Y-m-d',
            'site_logo' => $logoFile,
            'site_favicon' => $faviconFile,
        ];

        $response = $this->actingAs($admin)->put('/admin/settings', $payload);
        $response->assertRedirect('/admin/settings');
        $response->assertSessionHas('success');

        $storedLogo = Setting::get('site_logo');
        $storedFavicon = Setting::get('site_favicon');

        $this->assertNotEmpty($storedLogo);
        $this->assertNotEmpty($storedFavicon);
        Storage::disk('public')->assertExists($storedLogo);
        Storage::disk('public')->assertExists($storedFavicon);

        // Test Removal
        $removePayload = [
            'farm_name' => 'আমার নিজস্ব ফার্ম',
            'company_phone' => '01711223344',
            'company_email' => 'admin@customfarm.com',
            'company_address' => 'ঢাকা, বাংলাদেশ',
            'default_task_priority' => 'normal',
            'default_task_duration' => 60,
            'default_geofence_radius' => 25,
            'pagination_size' => 20,
            'date_format' => 'Y-m-d',
            'remove_site_logo' => '1',
            'remove_site_favicon' => '1',
        ];

        $removeResponse = $this->actingAs($admin)->put('/admin/settings', $removePayload);
        $removeResponse->assertRedirect('/admin/settings');

        $this->assertEmpty(Setting::get('site_logo'));
        $this->assertEmpty(Setting::get('site_favicon'));
        Storage::disk('public')->assertMissing($storedLogo);
        Storage::disk('public')->assertMissing($storedFavicon);
    }
}
