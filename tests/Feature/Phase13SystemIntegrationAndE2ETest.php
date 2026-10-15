<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase13SystemIntegrationAndE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active system administrator
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'name' => 'প্রধান অ্যাডমিন',
            'phone' => '01700000000',
        ]);
    }

    /**
     * Complete Workflow End-to-End Test:
     * Admin creates farmer -> Farmer account created -> Admin creates location
     * -> Admin creates task template -> Admin assigns task -> Farmer receives task
     * -> Farmer completes work -> Photo and GPS submitted -> PostGIS validates location
     * -> Admin reviews submission.
     */
    public function test_phase13_complete_workflow_end_to_end(): void
    {
        Storage::fake('public');

        // 1. Admin creates farmer via Web Admin Panel
        $farmerResponse = $this->actingAs($this->admin)->post(route('admin.farmers.store'), [
            'full_name' => 'আব্দুর রহিম',
            'phone' => '01711998877',
            'email' => 'rahim@bashiragro.com',
            'password' => 'pass12345',
            'address' => 'ময়মনসিংহ সদর',
            'status' => 'active',
            'primary_activity' => 'শাকসবজি চাষ',
            'experience_years' => 7,
            'emergency_contact_name' => 'করিম উল্লাহ',
            'emergency_contact_phone' => '01711000000',
            'emergency_contact_relationship' => 'ভাই',
        ]);

        $farmerResponse->assertRedirect(route('admin.farmers.index'));
        $farmerResponse->assertSessionHas('success');

        $farmer = Farmer::where('phone', '01711998877')->first();
        $this->assertNotNull($farmer);
        $this->assertNotNull($farmer->user_id);

        $farmerUser = User::find($farmer->user_id);
        $this->assertNotNull($farmerUser);
        $this->assertEquals('farmer', $farmerUser->role);

        // 2. Admin creates farm location with coordinates and geofence
        $locationResponse = $this->actingAs($this->admin)->post(route('admin.locations.store'), [
            'name' => 'পূর্বাঞ্চলীয় সবজি প্লট',
            'type' => 'সবজি ক্ষেত',
            'latitude' => 24.747100,
            'longitude' => 90.420300,
            'geofence_radius_meters' => 60,
            'address' => 'ময়মনসিংহ কৃষি জোন',
            'status' => 'active',
        ]);

        $locationResponse->assertRedirect(route('admin.locations.index'));
        $location = FarmLocation::where('name', 'পূর্বাঞ্চলীয় সবজি প্লট')->first();
        $this->assertNotNull($location);
        $this->assertEquals(60, $location->geofence_radius_meters);

        // 3. Admin creates task template requiring photo and geofence
        $taskResponse = $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'name' => 'জৈব সার প্রয়োগ ও আগাছা পরিষ্কার',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'গাছের গোড়ায় ২ কেজি হারে কম্পোস্ট সার প্রদান করুন এবং প্রমাণস্বরূপ ছবি তুলুন।',
            'required_photo_count' => 1,
            'requires_geofence_validation' => true,
            'require_realtime_photo' => true,
            'recurrence_type' => 'once',
            'status' => 'active',
        ]);

        $taskResponse->assertRedirect(route('admin.tasks.index'));
        $taskTemplate = TaskTemplate::where('name', 'জৈব সার প্রয়োগ ও আগাছা পরিষ্কার')->first();
        $this->assertNotNull($taskTemplate);

        // 4. Admin assigns task to the created farmer
        $assignmentResponse = $this->actingAs($this->admin)->post(route('admin.assignments.store'), [
            'farmer_id' => $farmer->id,
            'task_template_id' => $taskTemplate->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'priority' => 'important',
            'notes' => 'সকাল ১০টার মধ্যে সম্পন্ন করতে হবে।',
        ]);

        $assignmentResponse->assertRedirect(route('admin.assignments.index'));
        $assignment = TaskAssignment::where('farmer_id', $farmer->id)
            ->where('task_template_id', $taskTemplate->id)
            ->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('assigned', $assignment->status);

        // 5. REST API: Clear web admin session to simulate mobile client
        auth()->logout();
        Auth::forgetGuards();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => '01711998877',
            'password' => 'pass12345',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJson(['success' => true]);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Validate Token (/auth/me)
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('data.user.phone', '01711998877');

        // 6. Mobile Task Reception: Farmer fetches assignments
        $assignmentsResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/assignments');
        $assignmentsResponse->assertStatus(200);
        $assignmentsResponse->assertJsonFragment(['id' => $assignment->id]);

        // Farmer starts assignment
        $startResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/start");
        $startResponse->assertStatus(200);
        $this->assertEquals('in_progress', $assignment->fresh()->status);

        // 7. Work Completion & Evidence Submission with PostGIS Geofence Validation
        // GPS coordinate within 10 meters of target location (24.747100, 90.420300)
        $photo = UploadedFile::fake()->image('compost_evidence.jpg', 800, 600);

        $submitResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.747120,
                'longitude' => 90.420310,
                'accuracy' => 5.2,
                'notes' => 'সার প্রয়োগ সম্পন্ন এবং আগাছা পরিষ্কার করা হয়েছে।',
                'photos' => [$photo],
            ]);

        $submitResponse->assertStatus(201);
        $submitResponse->assertJson([
            'success' => true,
            'data' => [
                'geofence' => [
                    'passed' => true,
                ],
                'status' => 'submitted',
            ],
        ]);

        // PostGIS & DB verification
        $this->assertEquals('completed', $assignment->fresh()->status);
        $submission = TaskSubmission::where('task_assignment_id', $assignment->id)->first();
        $this->assertNotNull($submission);
        $this->assertTrue($submission->geofence_passed);
        $this->assertLessThan(60.0, (float) $submission->distance_meters);
        $this->assertCount(1, $submission->evidencePhotos);

        // 8. Admin Reviews Submission in Admin Panel
        $adminViewResponse = $this->actingAs($this->admin)->get(route('admin.assignments.show', $assignment));
        $adminViewResponse->assertStatus(200);
        $adminViewResponse->assertSee('কাজের প্রমাণ ও প্রতিবেদন যাচাই');
        $adminViewResponse->assertSee('সফল (সীমার ভেতরে)');

        // Admin approves the submission
        $reviewResponse = $this->actingAs($this->admin)->post(route('admin.assignments.review', $assignment), [
            'verification_status' => 'approved',
            'review_notes' => 'ছবি ও জিপিএস স্থানাঙ্ক নিখুঁতভাবে মিলেছে। চমৎকার কাজ।',
        ]);

        $reviewResponse->assertRedirect();
        $this->assertEquals('approved', $submission->fresh()->verification_status);
        $this->assertEquals('valid', $submission->fresh()->validation_status);

        // Audit Log verification
        $activity = ActivityLog::where('action', 'submission.reviewed')->latest()->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('approved', $activity->description);
    }

    /**
     * Test PostGIS Geofence Outside Radius:
     * When distance > allowed radius, API rejects submission with 422.
     */
    public function test_phase13_postgis_geofence_outside_radius_rejected(): void
    {
        Storage::fake('public');

        $farmerUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'FAR-P13-02',
            'full_name' => 'করিম উল্লাহ',
            'phone' => '01722334455',
            'address' => 'পাবনা',
            'primary_activity' => 'ধান চাষ',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-P13-02',
            'name' => 'দক্ষিণ ধানক্ষেত',
            'type' => 'ধান ক্ষেত',
            'latitude' => 24.000000,
            'longitude' => 89.000000,
            'geofence_radius_meters' => 30, // 30 meters allowed
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TSK-P13-02',
            'name' => 'সেচ প্রদান',
            'category' => 'সেচ ও পানি',
            'instructions' => 'পানি সেচ প্রদান করুন',
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '07:00',
            'status' => 'in_progress',
        ]);

        $token = $farmerUser->createToken('farmer_device')->plainTextToken;

        // Submitting from ~150 meters away (outside 30m radius)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/assignments/{$assignment->id}/submit", [
                'latitude' => 24.001350,
                'longitude' => 89.000000,
                'accuracy' => 5.0,
                'notes' => 'সেচ দেওয়া হয়েছে',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'geofence' => [
                'passed' => false,
            ],
        ]);

        $this->assertGreaterThan(30, $response->json('geofence.distance_meters'));
        // Assignment remains in progress, not completed
        $this->assertEquals('in_progress', $assignment->fresh()->status);
    }

    /**
     * Security and authorization testing:
     * - Unauthenticated access returns 401
     * - Cross-farmer access returns 403
     * - Invalid file validation returns 422
     */
    public function test_phase13_security_and_authorization(): void
    {
        $farmerAUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmerA = Farmer::create([
            'user_id' => $farmerAUser->id,
            'farmer_code' => 'FAR-A',
            'full_name' => 'কৃষক ক',
            'phone' => '01711111111',
            'address' => 'ঢাকা',
            'primary_activity' => 'ফসল চাষ',
        ]);

        $farmerBUser = User::factory()->create(['role' => 'farmer', 'is_active' => true]);
        $farmerB = Farmer::create([
            'user_id' => $farmerBUser->id,
            'farmer_code' => 'FAR-B',
            'full_name' => 'কৃষক খ',
            'phone' => '01722222222',
            'address' => 'ঢাকা',
            'primary_activity' => 'ফসল চাষ',
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-SEC-01',
            'name' => 'প্লট ১',
            'type' => 'সবজি ক্ষেত',
            'latitude' => 23.8103,
            'longitude' => 90.4125,
            'geofence_radius_meters' => 50,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TSK-SEC-01',
            'name' => 'নিরাপত্তা টেস্ট',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'নিরাপত্তা যাচাই করুন',
            'status' => 'active',
        ]);

        $assignmentA = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmerA->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'in_progress',
        ]);

        // 1. Unauthenticated access rejected
        $this->getJson('/api/v1/assignments')->assertStatus(401);
        $this->postJson("/api/v1/assignments/{$assignmentA->id}/submit", [])->assertStatus(401);

        // 2. Cross-farmer submission rejected (Farmer B submits Farmer A's task)
        $tokenB = $farmerBUser->createToken('farmer_b_device')->plainTextToken;
        $forbiddenResponse = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->postJson("/api/v1/assignments/{$assignmentA->id}/submit", [
                'latitude' => 23.8103,
                'longitude' => 90.4125,
            ]);
        $forbiddenResponse->assertStatus(403);

        // 3. File validation (Non-image file rejected)
        Auth::forgetGuards();
        $tokenA = $farmerAUser->createToken('farmer_a_device')->plainTextToken;
        $invalidFile = UploadedFile::fake()->create('malicious.sh', 500, 'application/x-sh');
        $fileResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/assignments/{$assignmentA->id}/submit", [
                'latitude' => 23.8103,
                'longitude' => 90.4125,
                'photos' => [$invalidFile],
            ]);
        $fileResponse->assertStatus(422);
    }

    /**
     * Web Admin Panel comprehensive verification:
     * Dashboard, Live Monitoring, Reports, Notifications, Audit Logs.
     */
    public function test_phase13_web_admin_panel_all_views_functional(): void
    {
        // 1. Dashboard
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('ড্যাশবোর্ড')
            ->assertSee('সক্রিয় কৃষক');

        // 2. Live Monitoring
        $this->actingAs($this->admin)->get(route('admin.monitoring'))
            ->assertStatus(200)
            ->assertSee('কার্যক্রম পর্যবেক্ষণ');

        // 3. Reports
        $this->actingAs($this->admin)->get(route('admin.reports'))
            ->assertStatus(200)
            ->assertSee('পারফরম্যান্স রিপোর্ট');

        // 4. Notifications
        $this->actingAs($this->admin)->get(route('admin.notifications'))
            ->assertStatus(200)
            ->assertSee('নোটিফিকেশন');

        // 5. Audit Log
        $this->actingAs($this->admin)->get(route('admin.audit'))
            ->assertStatus(200)
            ->assertSee('অডিট লগ');
    }

    /**
     * Database and PostGIS spatial calculation verification.
     */
    public function test_phase13_postgis_distance_calculation_accuracy(): void
    {
        $service = new GeofenceService;

        // Exactly the same coordinates = 0 meters
        $zeroDist = $service->calculateDistance(24.500000, 90.500000, 24.500000, 90.500000);
        $this->assertEquals(0.0, round($zeroDist, 2));

        // Coordinate offset ~ 100 meters
        $offsetDist = $service->calculateDistance(24.500000, 90.500000, 24.500899, 90.500000);
        $this->assertGreaterThan(95.0, $offsetDist);
        $this->assertLessThan(105.0, $offsetDist);
    }
}
