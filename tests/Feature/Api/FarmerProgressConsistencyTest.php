<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerProgressConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_farmer_list_and_assignment_submission_share_live_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $farmer = Farmer::create([
            'farmer_code' => 'F-888001',
            'full_name' => 'Progress Test Farmer',
            'phone' => '01712399999',
            'address' => 'Test field',
            'primary_activity' => 'সবজি চাষ',
            'status' => 'active',
        ]);
        $location = FarmLocation::create([
            'location_code' => 'LOC-888001',
            'name' => 'Test Field',
            'type' => 'সবজি',
            'status' => 'active',
        ]);
        $farmer->farmLocations()->attach($location);
        $template = TaskTemplate::create([
            'task_code' => 'TK-888001',
            'name' => 'Water crop',
            'category' => 'সবজি',
            'instructions' => 'Water the plants',
            'status' => 'active',
        ]);
        $assignment = TaskAssignment::create([
            'task_template_id' => $template->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'assigned',
        ]);
        $token = $admin->createToken('admin-progress-check')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);

        $date = today()->toDateString();
        $this->getJson('/api/v1/owner/dashboard?date='.$date)->assertOk()
            ->assertJsonPath('data.farmers.0.total_tasks', 1)
            ->assertJsonPath('data.farmers.0.completed_tasks', 0)
            ->assertJsonPath('data.farmers.0.active_tasks', 1)
            ->assertJsonPath('data.farmers.0.is_assigned', true)
            ->assertJsonPath('data.farmers.0.progress_percent', 0);
        $this->getJson('/api/v1/farmers?date='.$date)->assertOk()
            ->assertJsonPath('data.0.is_assigned', true)
            ->assertJsonPath('data.0.active_tasks', 1)
            ->assertJsonPath('data.0.total_tasks', 1)
            ->assertJsonPath('data.0.progress_percent', 0);

        $assignment->update(['status' => 'completed', 'completed_at' => now()]);
        TaskSubmission::create([
            'task_assignment_id' => $assignment->id,
            'farmer_id' => $farmer->id,
            'geofence_passed' => false,
            'is_late' => true,
            'verification_status' => 'pending',
            'status' => 'submitted',
        ]);

        $this->getJson('/api/v1/owner/dashboard?date='.$date)->assertOk()
            ->assertJsonPath('data.kpis.completed_tasks', 1)
            ->assertJsonPath('data.farmers.0.progress_percent', 100)
            ->assertJsonPath('data.farmers.0.active_tasks', 0)
            ->assertJsonPath('data.farmers.0.is_assigned', false);
        $this->getJson('/api/v1/farmers?date='.$date)->assertOk()
            ->assertJsonPath('data.0.completed_tasks', 1)
            ->assertJsonPath('data.0.progress_percent', 100)
            ->assertJsonPath('data.0.active_tasks', 0)
            ->assertJsonPath('data.0.is_assigned', false);
        $this->getJson('/api/v1/assignments/'.$assignment->id)->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.submission.geofence.passed', false)
            ->assertJsonPath('data.submission.is_late', true);

        // A location and completed task history must not keep the farmer engaged.
        $this->getJson('/api/v1/farmers/'.$farmer->id)->assertOk()
            ->assertJsonPath('data.is_assigned', false)
            ->assertJsonPath('data.progress_percent', 100);

        // An unfinished assignment on another date must still count as engaged.
        $futureAssignment = TaskAssignment::create([
            'task_template_id' => $template->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today()->addDays(3),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'pending',
        ]);
        $this->getJson('/api/v1/owner/dashboard?date='.$date)->assertOk()
            ->assertJsonPath('data.farmers.0.is_assigned', true)
            ->assertJsonPath('data.farmers.0.active_tasks', 1)
            ->assertJsonPath('data.farmers.0.progress_percent', 100);
        $this->getJson('/api/v1/farmers?date='.$date)->assertOk()
            ->assertJsonPath('data.0.is_assigned', true);
        $this->getJson('/api/v1/farmers/'.$farmer->id)->assertOk()
            ->assertJsonPath('data.is_assigned', true);

        // Cancelling the last outstanding assignment frees the farmer.
        $futureAssignment->update(['status' => 'cancelled']);
        $this->getJson('/api/v1/owner/dashboard?date='.$date)->assertOk()
            ->assertJsonPath('data.farmers.0.is_assigned', false);
        $this->getJson('/api/v1/farmers?date='.$date)->assertOk()
            ->assertJsonPath('data.0.is_assigned', false);
        $this->getJson('/api/v1/farmer/profile')->assertOk()
            ->assertJsonPath('data.is_assigned', false);

        $assignment->update(['status' => 'cancelled']);
        $this->getJson('/api/v1/owner/dashboard?date='.$date)->assertOk()
            ->assertJsonPath('data.kpis.today_tasks', 0)
            ->assertJsonPath('data.farmers.0.total_tasks', 0)
            ->assertJsonPath('data.farmers.0.progress_percent', 0);
        $this->getJson('/api/v1/farmers?date='.$date)->assertOk()
            ->assertJsonPath('data.0.is_assigned', false);
    }
}
