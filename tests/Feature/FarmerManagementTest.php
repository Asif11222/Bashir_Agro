<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmerEmergencyContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FarmerManagementTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_farmer_management(): void
    {
        $response = $this->get('/admin/farmers');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/admin/farmers/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_farmer_list(): void
    {
        Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রহিম উদ্দিন',
            'phone' => '01711000001',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/farmers');

        $response->assertStatus(200);
        $response->assertSee('কৃষক ব্যবস্থাপনা');
        $response->assertSee('মোঃ রহিম উদ্দিন');
        $response->assertSee('F-000001');
        $response->assertSee('সবজি চাষ');
    }

    public function test_admin_can_create_farmer_with_server_generated_code_and_emergency_contact(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('profile.jpg');

        $response = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => 'মোঃ করিম ইসলাম',
            'phone' => '01711223344',
            'nid' => '1234567890123',
            'date_of_birth' => '1990-05-15',
            'gender' => 'পুরুষ',
            'address' => 'গ্রাম: শান্তিপুর, জেলা: বগুড়া',
            'primary_activity' => 'মাছ চাষ',
            'experience_years' => 6,
            'profile_photo' => $photo,
            'app_access_enabled' => '1',
            'status' => 'active',
            'emergency_contact_name' => 'ফাতেমা বেগম',
            'emergency_contact_phone' => '01799887766',
            'emergency_contact_relationship' => 'স্বামী/স্ত্রী',
        ]);

        $response->assertRedirect(route('admin.farmers.index'));
        $response->assertSessionHas('success');

        $farmer = Farmer::where('phone', '01711223344')->first();
        $this->assertNotNull($farmer);
        $this->assertMatchesRegularExpression('/^F-\d{6}$/', $farmer->farmer_code);
        $this->assertEquals('মোঃ করিম ইসলাম', $farmer->full_name);
        $this->assertEquals('মাছ চাষ', $farmer->primary_activity);
        $this->assertTrue($farmer->app_access_enabled);
        $this->assertNotNull($farmer->profile_photo_path);
        Storage::disk('public')->assertExists($farmer->profile_photo_path);

        $this->assertDatabaseHas('farmer_emergency_contacts', [
            'farmer_id' => $farmer->id,
            'name' => 'ফাতেমা বেগম',
            'phone' => '01799887766',
            'relationship' => 'স্বামী/স্ত্রী',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'farmer.created',
            'subject_type' => Farmer::class,
            'subject_id' => $farmer->id,
        ]);
    }

    public function test_farmer_code_is_generated_sequentially(): void
    {
        $f1 = Farmer::create([
            'farmer_code' => Farmer::generateNextFarmerCode(),
            'full_name' => 'Farmer 1',
            'phone' => '01700000001',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals('F-000001', $f1->farmer_code);

        $f2 = Farmer::create([
            'farmer_code' => Farmer::generateNextFarmerCode(),
            'full_name' => 'Farmer 2',
            'phone' => '01700000002',
            'primary_activity' => 'মাছ',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals('F-000002', $f2->farmer_code);
    }

    public function test_invalid_farmer_form_data_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => '',
            'phone' => '',
            'address' => '',
            'primary_activity' => '',
            'emergency_contact_name' => '',
            'emergency_contact_phone' => '',
            'emergency_contact_relationship' => '',
        ]);

        $response->assertSessionHasErrors([
            'full_name',
            'phone',
            'address',
            'primary_activity',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
        ]);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'বিদ্যমান কৃষক',
            'phone' => '01712345678',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => 'নতুন কৃষক',
            'phone' => '01712345678',
            'address' => 'বগুড়া',
            'primary_activity' => 'মাছ চাষ',
            'emergency_contact_name' => 'যোগাযোগ',
            'emergency_contact_phone' => '01800000000',
            'emergency_contact_relationship' => 'পিতা/মাতা',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_admin_can_view_farmer_details(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রফিক',
            'phone' => '01711223399',
            'primary_activity' => 'গরু পালন',
            'address' => 'রাজশাহী',
            'created_by' => $this->admin->id,
        ]);

        FarmerEmergencyContact::create([
            'farmer_id' => $farmer->id,
            'name' => 'সালমা বেগম',
            'phone' => '01799881122',
            'relationship' => 'স্বামী/স্ত্রী',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/farmers/{$farmer->id}");

        $response->assertStatus(200);
        $response->assertSee('কৃষক বিবরণ');
        $response->assertSee('মোঃ রফিক');
        $response->assertSee('গরু পালন');
        $response->assertSee('সালমা বেগম');
    }

    public function test_admin_can_edit_farmer_and_emergency_contact(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রফিক',
            'phone' => '01711223399',
            'primary_activity' => 'গরু পালন',
            'address' => 'রাজশাহী',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        FarmerEmergencyContact::create([
            'farmer_id' => $farmer->id,
            'name' => 'সালমা বেগম',
            'phone' => '01799881122',
            'relationship' => 'স্বামী/স্ত্রী',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/farmers/{$farmer->id}", [
            'full_name' => 'মোঃ রফিক উদ্দিন',
            'phone' => '01711223399', // keeping same phone
            'address' => 'নাটোর',
            'primary_activity' => 'মিশ্র কৃষি',
            'status' => 'active',
            'emergency_contact_name' => 'সালমা বানু',
            'emergency_contact_phone' => '01799881133',
            'emergency_contact_relationship' => 'স্বামী/স্ত্রী',
        ]);

        $response->assertRedirect(route('admin.farmers.index'));

        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'full_name' => 'মোঃ রফিক উদ্দিন',
            'address' => 'নাটোর',
            'primary_activity' => 'মিশ্র কৃষি',
        ]);

        $this->assertDatabaseHas('farmer_emergency_contacts', [
            'farmer_id' => $farmer->id,
            'name' => 'সালমা বানু',
            'phone' => '01799881133',
        ]);
    }

    public function test_admin_can_deactivate_farmer(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'সক্রিয় কৃষক',
            'phone' => '01711223399',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        FarmerEmergencyContact::create([
            'farmer_id' => $farmer->id,
            'name' => 'যোগাযোগ',
            'phone' => '01700000000',
            'relationship' => 'পিতা/মাতা',
        ]);

        $this->actingAs($this->admin)->put("/admin/farmers/{$farmer->id}", [
            'full_name' => 'সক্রিয় কৃষক',
            'phone' => '01711223399',
            'address' => 'ঢাকা',
            'primary_activity' => 'সবজি চাষ',
            'status' => 'inactive',
            'emergency_contact_name' => 'যোগাযোগ',
            'emergency_contact_phone' => '01700000000',
            'emergency_contact_relationship' => 'পিতা/মাতা',
        ]);

        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'farmer.deactivated',
            'subject_id' => $farmer->id,
        ]);
    }

    public function test_permanent_delete_farmer_works(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'ডিলিটযোগ্য কৃষক',
            'phone' => '01711223399',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/farmers/{$farmer->id}");

        $response->assertRedirect(route('admin.farmers.index'));
        $this->assertDatabaseMissing('farmers', ['id' => $farmer->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'farmer.deleted',
            'subject_id' => $farmer->id,
        ]);
    }

    public function test_can_re_register_farmer_with_same_phone_after_deletion(): void
    {
        $farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'পূর্বের কৃষক',
            'phone' => '01799887766',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/farmers/{$farmer->id}");
        $this->assertDatabaseMissing('farmers', ['id' => $farmer->id]);

        $response = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => 'নতুন কৃষক একই নম্বরে',
            'phone' => '01799887766',
            'address' => 'বগুড়া',
            'primary_activity' => 'মাছ চাষ',
            'emergency_contact_name' => 'যোগাযোগ',
            'emergency_contact_phone' => '01800000000',
            'emergency_contact_relationship' => 'পিতা/মাতা',
        ]);

        $response->assertRedirect(route('admin.farmers.index'));
        $this->assertDatabaseHas('farmers', [
            'full_name' => 'নতুন কৃষক একই নম্বরে',
            'phone' => '01799887766',
        ]);
    }

    public function test_search_by_name_phone_or_code_works(): void
    {
        Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'আব্দুল কাদের',
            'phone' => '01711111111',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'created_by' => $this->admin->id,
        ]);

        Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'বেলাল হোসেন',
            'phone' => '01822222222',
            'primary_activity' => 'মাছ',
            'address' => 'চট্টগ্রাম',
            'created_by' => $this->admin->id,
        ]);

        $responseName = $this->actingAs($this->admin)->get('/admin/farmers?search=কাদের');
        $responseName->assertSee('আব্দুল কাদের');
        $responseName->assertDontSee('বেলাল হোসেন');

        $responsePhone = $this->actingAs($this->admin)->get('/admin/farmers?search=01822222222');
        $responsePhone->assertSee('বেলাল হোসেন');
        $responsePhone->assertDontSee('আব্দুল কাদের');

        $responseCode = $this->actingAs($this->admin)->get('/admin/farmers?search=F-000001');
        $responseCode->assertSee('আব্দুল কাদের');
        $responseCode->assertDontSee('বেলাল হোসেন');
    }

    public function test_status_filter_works(): void
    {
        Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'সক্রিয় চাষী',
            'phone' => '01711111111',
            'primary_activity' => 'সবজি',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'নিষ্ক্রিয় চাষী',
            'phone' => '01822222222',
            'primary_activity' => 'মাছ',
            'address' => 'চট্টগ্রাম',
            'status' => 'inactive',
            'created_by' => $this->admin->id,
        ]);

        $responseActive = $this->actingAs($this->admin)->get('/admin/farmers?status=active');
        $responseActive->assertSee('সক্রিয় চাষী');
        $responseActive->assertDontSee('নিষ্ক্রিয় চাষী');

        $responseInactive = $this->actingAs($this->admin)->get('/admin/farmers?status=inactive');
        $responseInactive->assertSee('নিষ্ক্রিয় চাষী');
        $responseInactive->assertDontSee('সক্রিয় চাষী');
    }

    public function test_pagination_works_and_preserves_query_string(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Farmer::create([
                'farmer_code' => sprintf('F-%06d', $i),
                'full_name' => "Farmer {$i}",
                'phone' => sprintf('017%08d', $i),
                'primary_activity' => 'সবজি',
                'address' => 'ঢাকা',
                'status' => 'active',
                'created_by' => $this->admin->id,
            ]);
        }

        $responsePage1 = $this->actingAs($this->admin)->get('/admin/farmers?status=active');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('Farmer 25'); // latest first

        $responsePage2 = $this->actingAs($this->admin)->get('/admin/farmers?status=active&page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Farmer 1');
    }

    public function test_profile_photo_validation_works(): void
    {
        $nonImageFile = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->actingAs($this->admin)->post('/admin/farmers', [
            'full_name' => 'ছবিবিহীন',
            'phone' => '01712340000',
            'address' => 'ঢাকা',
            'primary_activity' => 'সবজি',
            'profile_photo' => $nonImageFile,
            'emergency_contact_name' => 'যোগাযোগ',
            'emergency_contact_phone' => '01700000000',
            'emergency_contact_relationship' => 'পিতা/মাতা',
        ]);

        $response->assertSessionHasErrors(['profile_photo']);
    }
}
