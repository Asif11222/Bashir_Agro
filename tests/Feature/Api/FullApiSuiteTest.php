<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FullApiSuiteTest extends TestCase
{
    use RefreshDatabase;

    private User $farmerUser;

    private Farmer $farmer;

    private FarmLocation $location;

    private TaskTemplate $taskTemplate;

    private TaskAssignment $assignment;

    private string $farmerToken;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create user
        $this->farmerUser = User::create([
            'name' => 'আব্দুর রহিম',
            'phone' => '01712345678',
            'email' => 'rahim@bashiragro.com',
            'password' => Hash::make('password123'),
            'role' => 'farmer',
            'is_active' => true,
        ]);

        // Create farmer profile
        $this->farmer = Farmer::create([
            'user_id' => $this->farmerUser->id,
            'farmer_code' => 'FAR-001',
            'full_name' => 'আব্দুর রহিম',
            'phone' => '01712345678',
            'status' => 'active',
            'address' => 'দক্ষিণ পাড়া, শ্যামনগর',
            'primary_activity' => 'শাকসবজি চাষ',
            'experience_years' => 8,
            'app_access_enabled' => true,
        ]);

        // Create farm location with coordinates
        $this->location = FarmLocation::create([
            'location_code' => 'LOC-001',
            'name' => 'দক্ষিণ মাঠ, ২ নং ব্লক',
            'type' => 'সবজি ক্ষেত',
            'address' => 'শ্যামনগর',
            'latitude' => 23.7808,
            'longitude' => 90.4152,
            'geofence_radius_meters' => 100,
            'status' => 'active',
        ]);
        $this->farmer->farmLocations()->attach($this->location->id);

        // Create task template
        $this->taskTemplate = TaskTemplate::create([
            'task_code' => 'TSK-001',
            'name' => 'সবজি ক্ষেত পরিদর্শন',
            'category' => 'শাকসবজি চাষ',
            'instructions' => 'ক্ষেতে গিয়ে পোকামাকড় বা ফসলের অবস্থা যাচাই করুন।',
            'requires_evidence_photo' => true,
            'min_photos_required' => 1,
            'requires_geofence_validation' => true,
            'status' => 'active',
        ]);

        // Create task assignment
        $this->assignment = TaskAssignment::create([
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->taskTemplate->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'assigned',
        ]);

        // Generate token
        $this->farmerToken = $this->farmerUser->createToken('test-mobile-token', ['*'])->plainTextToken;
    }

    /**
     * 1. Test POST /api/v1/auth/login
     */
    public function test_01_auth_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01712345678',
            'password' => 'password123',
            'device_name' => 'Test Phone',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'phone', 'role'],
                    'farmer_profile',
                ],
            ]);
    }

    /**
     * 2. Test GET /api/v1/auth/me
     */
    public function test_02_auth_me(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.phone', '01712345678');
    }

    /**
     * 3. Test GET /api/v1/farmer/profile
     */
    public function test_03_farmer_profile(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson('/api/v1/farmer/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.farmer_code', 'FAR-001')
            ->assertJsonPath('data.full_name', 'আব্দুর রহিম');
    }

    /**
     * 4. Test GET /api/v1/farmers/{farmer}
     */
    public function test_04_specific_farmer(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/farmers/{$this->farmer->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->farmer->id);
    }

    /**
     * 5. Test GET /api/v1/tasks
     */
    public function test_05_tasks_list(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'task_code', 'name', 'category', 'requires_evidence_photo'],
                ],
            ]);
    }

    /**
     * 6. Test GET /api/v1/tasks/{task}
     */
    public function test_06_specific_task_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/tasks/{$this->taskTemplate->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task_code', 'TSK-001');
    }

    /**
     * 7. Test GET /api/v1/assignments
     */
    public function test_07_assignments_list(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson('/api/v1/assignments');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'scheduled_date', 'status', 'task', 'location'],
                ],
            ]);
    }

    /**
     * 8. Test GET /api/v1/assignments with filters (status and date)
     */
    public function test_08_assignments_filtered(): void
    {
        $today = now()->toDateString();
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/assignments?status=assigned&date={$today}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    /**
     * 9. Test GET /api/v1/assignments/{assignment}
     */
    public function test_09_specific_assignment_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/assignments/{$this->assignment->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->assignment->id)
            ->assertJsonPath('data.task.name', 'সবজি ক্ষেত পরিদর্শন');
    }

    /**
     * 10. Test POST /api/v1/assignments/{assignment}/start
     */
    public function test_10_start_assignment(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->postJson("/api/v1/assignments/{$this->assignment->id}/start");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'in_progress');
    }

    /**
     * 11. Test POST /api/v1/assignments/{assignment}/submit
     */
    public function test_11_submit_assignment_evidence(): void
    {
        $photo = UploadedFile::fake()->image('evidence.jpg', 600, 600);

        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->post("/api/v1/assignments/{$this->assignment->id}/submit", [
                'latitude' => 23.7808,
                'longitude' => 90.4152,
                'accuracy' => 4.5,
                'notes' => 'ক্ষেতের সেচ ও পরিচ্ছন্নতা সম্পূর্ণ সম্পন্ন হয়েছে।',
                'photos' => [$photo],
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.geofence.passed', true);
    }

    /**
     * 12. Test GET /api/v1/assignments/{assignment}/submission
     */
    public function test_12_get_assignment_submission(): void
    {
        // First submit evidence
        $photo = UploadedFile::fake()->image('evidence.jpg', 600, 600);
        $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->post("/api/v1/assignments/{$this->assignment->id}/submit", [
                'latitude' => 23.7808,
                'longitude' => 90.4152,
                'accuracy' => 4.5,
                'notes' => 'জমা দেওয়া কাজ।',
                'photos' => [$photo],
            ], [
                'Accept' => 'application/json',
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/assignments/{$this->assignment->id}/submission");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assignment_id', $this->assignment->id)
            ->assertJsonPath('data.status', 'submitted');
    }

    /**
     * 13. Test GET /api/v1/locations
     */
    public function test_13_locations_list(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson('/api/v1/locations');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'location_code', 'name', 'type', 'coordinates'],
                ],
            ]);
    }

    /**
     * 14. Test GET /api/v1/locations/{location}
     */
    public function test_14_specific_location_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->getJson("/api/v1/locations/{$this->location->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.location_code', 'LOC-001');
    }

    /**
     * 15. Test POST /api/v1/auth/logout
     */
    public function test_15_auth_logout(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->farmerToken}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 16. Test OpenAPI Specification: GET /docs/api.json
     */
    public function test_16_openapi_spec_json(): void
    {
        $response = $this->get('/docs/api.json');

        $response->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'Bashir Agro API Documentation');
    }

    /**
     * 17. Test Swagger UI Documentation: GET /api/documentation
     */
    public function test_17_swagger_ui_html(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertOk()
            ->assertSee('SwaggerUIBundle', false)
            ->assertSee('/docs/api.json', false);
    }

    /**
     * 18. Test Scramble Interactive Docs UI: GET /docs/api
     */
    public function test_18_scramble_interactive_docs_ui(): void
    {
        $response = $this->get('/docs/api');

        $response->assertOk();
    }
}
