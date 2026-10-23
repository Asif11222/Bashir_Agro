<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_header_and_mobile_reads_it(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.settings.mobile-headers.update', 'farmers_list'), [
            'title' => 'কৃষকদের তথ্য',
            'subtitle' => 'বর্তমান কৃষকরা',
            'title_color' => '#FFAA22',
            'font_size' => 20,
            'alignment' => 'center',
            'overlay' => 0.35,
            'shadow' => '1',
            'image' => UploadedFile::fake()->image('farm.jpg'),
        ])->assertRedirect();

        $header = Setting::get('app_header.farmers_list');
        $this->assertEquals('কৃষকদের তথ্য', $header['title']);
        Storage::disk('public')->assertExists($header['image_path']);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/app-appearance')
            ->assertOk()
            ->assertJsonPath('data.headers.farmers_list.title', 'কৃষকদের তথ্য')
            ->assertJsonPath('data.headers.farmers_list.font_size', 20);
    }

    public function test_owner_profile_photo_url_is_available_to_mobile(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $owner->forceFill(['profile_photo_path' => 'users/photos/new-photo.jpg'])->save();
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.user.profile_photo_url', asset('storage/users/photos/new-photo.jpg'));
    }

    public function test_farmer_cannot_change_appearance(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($farmer)->put(route('admin.settings.mobile-headers.update', 'farmer_dashboard'), [
            'title' => 'Overwritten', 'title_color' => '#FFFFFF',
            'font_size' => 18, 'alignment' => 'center', 'overlay' => 0.35,
        ])->assertForbidden();
        $this->assertNull(Setting::query()->where('key', 'app_header.farmer_dashboard')->first());
    }

    public function test_header_upload_rejects_svg(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'owner']);
        $this->actingAs($admin)->put(route('admin.settings.mobile-headers.update', 'owner_dashboard'), [
            'title' => 'Dashboard', 'title_color' => '#FFFFFF',
            'font_size' => 18, 'alignment' => 'center', 'overlay' => 0.35,
            'image' => UploadedFile::fake()->createWithContent('danger.svg', '<svg onload="alert(1)"/>'),
        ])->assertSessionHasErrors('image');
    }
}
