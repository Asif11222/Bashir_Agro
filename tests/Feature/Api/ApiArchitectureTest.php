<?php

namespace Tests\Feature\Api;

use App\Http\Resources\AssignmentResource;
use App\Http\Resources\FarmerResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\TaskResource;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApiArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_issue_sanctum_api_tokens(): void
    {
        $farmerUser = User::factory()->create([
            'role' => 'farmer',
            'is_active' => true,
        ]);

        $token = $farmerUser->createToken('farmer-mobile-app', ['tasks:read', 'tasks:submit']);

        $this->assertNotNull($token->plainTextToken);
        $this->assertEquals('farmer-mobile-app', $token->accessToken->name);
        $this->assertTrue($token->accessToken->can('tasks:read'));
        $this->assertTrue($token->accessToken->can('tasks:submit'));
        $this->assertFalse($token->accessToken->can('admin:full'));
    }

    public function test_user_and_farmer_profile_relationship(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $farmerUser = User::factory()->create([
            'role' => 'farmer',
            'phone' => '01711229988',
        ]);

        $farmer = Farmer::create([
            'user_id' => $farmerUser->id,
            'farmer_code' => 'F-888001',
            'full_name' => 'করিম মণ্ডল',
            'phone' => '01711229988',
            'address' => 'ঈশ্বরদী, পাবনা',
            'primary_activity' => 'সবজি চাষ',
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($farmerUser->isFarmer());
        $this->assertInstanceOf(Farmer::class, $farmerUser->farmerProfile);
        $this->assertEquals($farmer->id, $farmerUser->farmerProfile->id);
        $this->assertEquals($farmerUser->id, $farmer->user->id);
    }

    public function test_farmer_role_permissions(): void
    {
        $farmerUser = User::factory()->create(['role' => 'farmer']);

        $this->assertTrue(Gate::forUser($farmerUser)->allows('task.view'));
        $this->assertTrue(Gate::forUser($farmerUser)->allows('task.submit'));
        $this->assertTrue(Gate::forUser($farmerUser)->allows('profile.view'));

        $this->assertFalse(Gate::forUser($farmerUser)->allows('farmers.delete'));
        $this->assertFalse(Gate::forUser($farmerUser)->allows('settings.manage'));
    }

    public function test_api_resources_transform_models_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $farmer = Farmer::create([
            'farmer_code' => 'F-888002',
            'full_name' => 'রহিম শেখ',
            'phone' => '01811229988',
            'address' => 'সাঁথিয়া, পাবনা',
            'primary_activity' => 'ধান চাষ',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-888002',
            'name' => 'পূর্ব মাঠ প্লট',
            'type' => 'ধান ক্ষেত',
            'latitude' => 24.0123456,
            'longitude' => 89.1234567,
            'geofence_radius_meters' => 25,
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-888002',
            'name' => 'সেচ প্রদান',
            'category' => 'সেচ',
            'instructions' => 'পর্যাপ্ত পানি সরবরাহ করুন',
            'required_photo_count' => 2,
            'require_realtime_photo' => true,
            'require_geofence' => true,
            'created_by' => $admin->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'important',
            'status' => 'assigned',
            'assigned_by' => $admin->id,
        ]);

        $request = Request::create('/');

        // 1. FarmerResource
        $farmerArray = (new FarmerResource($farmer))->toArray($request);
        $this->assertEquals('F-888002', $farmerArray['farmer_code']);
        $this->assertEquals('রহিম শেখ', $farmerArray['full_name']);

        // 2. LocationResource
        $locationArray = (new LocationResource($location))->toArray($request);
        $this->assertEquals('LOC-888002', $locationArray['location_code']);
        $this->assertEquals(25, $locationArray['geofence_radius_meters']);
        $this->assertEquals(24.0123456, $locationArray['coordinates']['latitude']);

        // 3. TaskResource
        $taskArray = (new TaskResource($task))->toArray($request);
        $this->assertEquals('TK-888002', $taskArray['task_code']);
        $this->assertTrue($taskArray['requires_evidence_photo']);
        $this->assertEquals(2, $taskArray['min_photos_required']);

        // 4. AssignmentResource
        $assignment->load(['taskTemplate', 'farmer', 'location']);
        $assignmentArray = (new AssignmentResource($assignment))->toArray($request);
        $this->assertEquals('assigned', $assignmentArray['status']);
        $this->assertEquals('important', $assignmentArray['priority']);
    }

    public function test_task_assignment_service_lifecycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $farmer = Farmer::create([
            'farmer_code' => 'F-888003',
            'full_name' => 'জামাল হোসেন',
            'phone' => '01911229988',
            'address' => 'ঈশ্বরদী',
            'primary_activity' => 'ভুট্টা চাষ',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-888003',
            'name' => 'উত্তর প্লট',
            'type' => 'ভুট্টা ক্ষেত',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-888003',
            'name' => 'কীটনাশক স্প্রে',
            'category' => 'ফসল সুরক্ষা',
            'instructions' => 'স্প্রে সম্পন্ন করুন',
            'created_by' => $admin->id,
        ]);

        $service = new TaskAssignmentService;

        // 1. Create assignment
        $assignment = $service->createAssignment([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '09:00',
            'priority' => 'urgent',
            'status' => 'assigned',
        ], $admin);

        $this->assertDatabaseHas('task_assignments', [
            'id' => $assignment->id,
            'status' => 'assigned',
            'priority' => 'urgent',
        ]);

        // 2. Update to in_progress
        $service->updateStatus($assignment, 'in_progress', [], $admin);
        $assignment->refresh();
        $this->assertEquals('in_progress', $assignment->status);
        $this->assertNotNull($assignment->started_at);

        // 3. Update to completed
        $service->updateStatus($assignment, 'completed', [], $admin);
        $assignment->refresh();
        $this->assertEquals('completed', $assignment->status);
        $this->assertNotNull($assignment->completed_at);

        // 4. Cancel assignment
        $service->cancelAssignment($assignment, 'ভারী বৃষ্টির কারণে স্থগিত', $admin);
        $assignment->refresh();
        $this->assertEquals('cancelled', $assignment->status);
        $this->assertStringContainsString('ভারী বৃষ্টির কারণে স্থগিত', $assignment->notes);
    }

    public function test_storage_directories_prepared(): void
    {
        $this->assertTrue(File::exists(storage_path('app/public/farmers')));
        $this->assertTrue(File::exists(storage_path('app/public/tasks')));
        $this->assertTrue(File::exists(storage_path('app/public/submissions')));
    }
}
