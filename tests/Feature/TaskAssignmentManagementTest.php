<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Farmer $farmer;

    private TaskTemplate $task;

    private FarmLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->farmer = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রহিম উদ্দিন',
            'phone' => '01711223344',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সার প্রয়োগ',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'গাছের গোড়ায় সার দিন',
            'required_photo_count' => 2,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_task_assignments(): void
    {
        $response = $this->get('/admin/assignments');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/admin/assignments/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_assignment_list(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/assignments');

        $response->assertStatus(200);
        $response->assertSee('কাজ বরাদ্দ ব্যবস্থাপনা');
        $response->assertSee('মোঃ রহিম উদ্দিন');
        $response->assertSee('সার প্রয়োগ');
        $response->assertSee('উত্তর মাঠ');
    }

    public function test_admin_can_create_task_assignment_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-01',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'priority' => 'important',
            'status' => 'assigned',
            'notes' => 'সতর্কতার সাথে সার ছিটাবেন।',
        ]);

        $response->assertRedirect(route('admin.assignments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('task_assignments', [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'priority' => 'important',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $assignment = TaskAssignment::where('farmer_id', $this->farmer->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('2026-10-01', $assignment->scheduled_date->format('Y-m-d'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'assignment.created',
            'subject_type' => TaskAssignment::class,
            'subject_id' => $assignment->id,
        ]);
    }

    public function test_assignment_relationships_function_correctly(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // BelongsTo relationships
        $this->assertEquals($this->farmer->id, $assignment->farmer->id);
        $this->assertEquals($this->task->id, $assignment->taskTemplate->id);
        $this->assertEquals($this->location->id, $assignment->location->id);
        $this->assertEquals($this->location->id, $assignment->farmLocation->id);
        $this->assertEquals($this->admin->id, $assignment->assigner->id);
        $this->assertEquals($this->admin->id, $assignment->assignedByUser->id);

        // HasMany inverse relationships
        $this->assertTrue($this->farmer->taskAssignments->contains($assignment));
        $this->assertTrue($this->task->taskAssignments->contains($assignment));
        $this->assertTrue($this->location->taskAssignments->contains($assignment));
        $this->assertTrue($this->admin->assignedTasks->contains($assignment));
    }

    public function test_validation_failure_when_creating_assignment(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => 99999, // non-existent
            'task_template_id' => 99999,
            'farm_location_id' => 99999,
            'scheduled_date' => 'invalid-date',
            'start_time' => 'invalid-time',
            'priority' => 'invalid-priority',
        ]);

        $response->assertSessionHasErrors([
            'farmer_id',
            'task_template_id',
            'farm_location_id',
            'scheduled_date',
            'start_time',
            'priority',
        ]);
    }

    public function test_validation_fails_if_end_time_is_before_start_time(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-01',
            'start_time' => '14:00',
            'end_time' => '12:00', // earlier than start_time
            'priority' => 'normal',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_duplicate_assignment_prevention_rule(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-05',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Attempt to create duplicate for same farmer, task, location, date, and start_time
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-05',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'urgent',
            'status' => 'assigned',
        ]);

        $response->assertSessionHasErrors(['farmer_id']);
        $this->assertEquals(1, TaskAssignment::whereDate('scheduled_date', '2026-10-05')->count());
    }

    public function test_same_task_different_time_on_same_date_is_allowed(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-06',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Second assignment with different start time (e.g. afternoon)
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-06',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'priority' => 'normal',
            'status' => 'assigned',
        ]);

        $response->assertRedirect('/admin/assignments');
        $this->assertEquals(2, TaskAssignment::whereDate('scheduled_date', '2026-10-06')->count());
    }

    public function test_admin_can_view_assignment_details(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'notes' => 'জরুরি ভিত্তিতে সম্পাদন করতে হবে।',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/assignments/{$assignment->id}");

        $response->assertStatus(200);
        $response->assertSee('কাজের বরাদ্দ বিবরণ');
        $response->assertSee('মোঃ রহিম উদ্দিন');
        $response->assertSee('সার প্রয়োগ');
        $response->assertSee('উত্তর মাঠ');
        $response->assertSee('জরুরি ভিত্তিতে সম্পাদন করতে হবে।');
    }

    public function test_admin_can_update_assignment(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/assignments/{$assignment->id}", [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-10-10',
            'start_time' => '09:30',
            'end_time' => '11:30',
            'priority' => 'urgent',
            'status' => 'assigned',
            'notes' => 'সময় পরিবর্তন করা হয়েছে।',
        ]);

        $response->assertRedirect(route('admin.assignments.index'));

        $this->assertDatabaseHas('task_assignments', [
            'id' => $assignment->id,
            'start_time' => '09:30',
            'end_time' => '11:30',
            'priority' => 'urgent',
            'notes' => 'সময় পরিবর্তন করা হয়েছে।',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'assignment.updated',
            'subject_type' => TaskAssignment::class,
            'subject_id' => $assignment->id,
        ]);
    }

    public function test_status_change_logs_status_changed_activity_and_timestamps(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Change from assigned to in_progress
        $this->actingAs($this->admin)->put("/admin/assignments/{$assignment->id}", [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'in_progress',
        ]);

        $assignment->refresh();
        $this->assertEquals('in_progress', $assignment->status);
        $this->assertNotNull($assignment->started_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'assignment.status_changed',
            'subject_type' => TaskAssignment::class,
            'subject_id' => $assignment->id,
        ]);

        // Change from in_progress to completed
        $this->actingAs($this->admin)->put("/admin/assignments/{$assignment->id}", [
            'farmer_id' => $this->farmer->id,
            'task_template_id' => $this->task->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'completed',
        ]);

        $assignment->refresh();
        $this->assertEquals('completed', $assignment->status);
        $this->assertNotNull($assignment->completed_at);
    }

    public function test_admin_can_soft_delete_assignment(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/assignments/{$assignment->id}");

        $response->assertRedirect(route('admin.assignments.index'));
        $this->assertSoftDeleted('task_assignments', ['id' => $assignment->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'assignment.deleted',
            'subject_type' => TaskAssignment::class,
            'subject_id' => $assignment->id,
        ]);
    }

    public function test_search_assignments_by_farmer_task_or_location_name(): void
    {
        $farmer2 = Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'কামাল হোসেন',
            'phone' => '01811223344',
            'primary_activity' => 'মৎস্য চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $task2 = TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'মাছের খাবার',
            'category' => 'মৎস্য',
            'instructions' => 'খাবার ছড়ান',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $location2 = FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'দক্ষিণ পুকুর',
            'type' => 'মাছের পুকুর',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Assignment 1
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Assignment 2
        TaskAssignment::create([
            'task_template_id' => $task2->id,
            'farmer_id' => $farmer2->id,
            'farm_location_id' => $location2->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'assigned_by' => $this->admin->id,
        ]);

        $responseFarmer = $this->actingAs($this->admin)->get('/admin/assignments?search=কামাল&view_mode=flat');
        $responseFarmer->assertSee('কামাল হোসেন');
        $this->assertEquals(1, $responseFarmer->viewData('assignments')->total());
        $this->assertEquals($farmer2->id, $responseFarmer->viewData('assignments')->first()->farmer_id);

        $responseTask = $this->actingAs($this->admin)->get('/admin/assignments?search=মাছের&view_mode=flat');
        $responseTask->assertSee('মাছের খাবার');
        $this->assertEquals(1, $responseTask->viewData('assignments')->total());
        $this->assertEquals($task2->id, $responseTask->viewData('assignments')->first()->task_template_id);

        $responseLoc = $this->actingAs($this->admin)->get('/admin/assignments?search=দক্ষিণ&view_mode=flat');
        $responseLoc->assertSee('দক্ষিণ পুকুর');
        $this->assertEquals(1, $responseLoc->viewData('assignments')->total());
        $this->assertEquals($location2->id, $responseLoc->viewData('assignments')->first()->farm_location_id);
    }

    public function test_filter_assignments_by_status_priority_and_date(): void
    {
        $assign1 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $assign2 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->addDays(5),
            'start_time' => '12:00',
            'priority' => 'urgent',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        $responseStatus = $this->actingAs($this->admin)->get('/admin/assignments?status=completed');
        $responseStatus->assertSee($assign2->priority_label);
        $responseStatus->assertDontSee('08:00');

        $responsePriority = $this->actingAs($this->admin)->get('/admin/assignments?priority=urgent');
        $responsePriority->assertSee($assign2->priority_label);
        $responsePriority->assertDontSee('08:00');

        $responseDate = $this->actingAs($this->admin)->get('/admin/assignments?date_filter=today');
        $responseDate->assertSee('08:00');
        $responseDate->assertDontSee('12:00');
    }

    public function test_assignment_pagination_works(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            TaskAssignment::create([
                'task_template_id' => $this->task->id,
                'farmer_id' => $this->farmer->id,
                'farm_location_id' => $this->location->id,
                'scheduled_date' => today()->subDays($i),
                'start_time' => sprintf('%02d:00', ($i % 12) + 1),
                'priority' => 'normal',
                'status' => 'assigned',
                'notes' => "Assignment {$i}",
                'assigned_by' => $this->admin->id,
            ]);
        }

        $responsePage1 = $this->actingAs($this->admin)->get('/admin/assignments');
        $responsePage1->assertStatus(200);

        $responsePage2 = $this->actingAs($this->admin)->get('/admin/assignments?page=2');
        $responsePage2->assertStatus(200);
    }

    public function test_assignments_index_defaults_to_grouped_view_and_groups_recurring_series(): void
    {
        $groupId = 'rec_test_group_' . uniqid();
        for ($i = 1; $i <= 5; $i++) {
            TaskAssignment::create([
                'recurring_group_id' => $groupId,
                'recurrence_type' => 'weekly',
                'recurrence_seq' => $i,
                'recurrence_total_count' => 5,
                'is_recurring' => true,
                'task_template_id' => $this->task->id,
                'farmer_id' => $this->farmer->id,
                'farm_location_id' => $this->location->id,
                'scheduled_date' => today()->addDays($i),
                'start_time' => '09:00',
                'priority' => 'normal',
                'status' => $i === 1 ? 'completed' : 'assigned',
                'assigned_by' => $this->admin->id,
            ]);
        }

        // In Grouped View (default), the 5 occurrences appear under 1 master row with progress bar & drawer
        $response = $this->actingAs($this->admin)->get('/admin/assignments');
        $response->assertStatus(200);
        $response->assertSee('সিরিজ গ্রুপ ভিউ');
        $response->assertSee('সাপ্তাহিক রুটিন');
        $response->assertSee('5টি সেশন');
        $response->assertSee('drawer-' . $groupId);
        $response->assertSee('20%');
        $response->assertSee('5টি তারিখ');

        // In Flat View, view_mode=flat shows each occurrence individually
        $responseFlat = $this->actingAs($this->admin)->get('/admin/assignments?view_mode=flat');
        $responseFlat->assertStatus(200);
        $responseFlat->assertSee('সব তারিখের তালিকা');
    }

    public function test_admin_can_destroy_entire_recurring_series_or_only_pending_tasks(): void
    {
        $groupId = 'rec_delete_test_' . uniqid();
        for ($i = 1; $i <= 4; $i++) {
            TaskAssignment::create([
                'recurring_group_id' => $groupId,
                'recurrence_type' => 'daily',
                'recurrence_seq' => $i,
                'recurrence_total_count' => 4,
                'is_recurring' => true,
                'task_template_id' => $this->task->id,
                'farmer_id' => $this->farmer->id,
                'farm_location_id' => $this->location->id,
                'scheduled_date' => today()->addDays($i),
                'start_time' => '08:00',
                'priority' => 'normal',
                'status' => $i === 1 ? 'completed' : 'assigned',
                'assigned_by' => $this->admin->id,
            ]);
        }

        // Test 1: Delete only pending tasks (include_completed = 0)
        $response1 = $this->actingAs($this->admin)->delete("/admin/assignments/recurring/{$groupId}", [
            'include_completed' => 0,
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        // Task #1 (completed) should still exist
        $this->assertDatabaseHas('task_assignments', [
            'recurring_group_id' => $groupId,
            'recurrence_seq' => 1,
            'status' => 'completed',
            'deleted_at' => null,
        ]);

        // Remaining 3 tasks should be soft-deleted
        $remainingCount = TaskAssignment::where('recurring_group_id', $groupId)->whereNull('deleted_at')->count();
        $this->assertEquals(1, $remainingCount);

        // Test 2: Delete entire series including completed (include_completed = 1)
        $response2 = $this->actingAs($this->admin)->delete("/admin/assignments/recurring/{$groupId}", [
            'include_completed' => 1,
        ]);
        $response2->assertRedirect();
        $this->assertEquals(0, TaskAssignment::where('recurring_group_id', $groupId)->whereNull('deleted_at')->count());
    }

    public function test_newly_created_task_assignment_appears_first_in_list(): void
    {
        // Assignment 1 (created earlier, with future scheduled date)
        $task1 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->addDays(10),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
            'created_at' => now()->subMinutes(10),
        ]);

        // Assignment 2 (created now, with current scheduled date)
        $task2 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'priority' => 'important',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/assignments?view_mode=flat');
        $response->assertStatus(200);

        $assignments = $response->viewData('assignments');
        // task2 must be first because it was created latest, even though task1 has a future scheduled date
        $this->assertEquals($task2->id, $assignments->first()->id);
    }

    public function test_filter_assignments_by_farmer_and_area(): void
    {
        $farmer2 = Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'জামাল মিয়া',
            'phone' => '01899001122',
            'primary_activity' => 'ধান চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $location2 = FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'পশ্চিম প্লট',
            'type' => 'ধান ক্ষেত',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $task1 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $task2 = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $farmer2->id,
            'farm_location_id' => $location2->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'priority' => 'urgent',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Filter by Farmer
        $resFarmer = $this->actingAs($this->admin)->get("/admin/assignments?farmer_id={$farmer2->id}&view_mode=flat");
        $resFarmer->assertStatus(200);
        $this->assertEquals(1, $resFarmer->viewData('assignments')->total());
        $this->assertEquals($task2->id, $resFarmer->viewData('assignments')->first()->id);

        // Filter by Location
        $resLoc = $this->actingAs($this->admin)->get("/admin/assignments?location_id={$location2->id}&view_mode=flat");
        $resLoc->assertStatus(200);
        $this->assertEquals(1, $resLoc->viewData('assignments')->total());
        $this->assertEquals($task2->id, $resLoc->viewData('assignments')->first()->id);
    }

    public function test_filter_assignments_by_date_range_and_time_shift(): void
    {
        $taskMorning = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-05',
            'start_time' => '07:30',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $taskAfternoon = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-20',
            'start_time' => '16:00',
            'priority' => 'important',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Filter by Date Range (2026-11-01 to 2026-11-10)
        $resRange = $this->actingAs($this->admin)->get('/admin/assignments?date_filter=range&start_date=2026-11-01&end_date=2026-11-10&view_mode=flat');
        $this->assertEquals(1, $resRange->viewData('assignments')->total());
        $this->assertEquals($taskMorning->id, $resRange->viewData('assignments')->first()->id);

        // Filter by Morning Shift
        $resMorning = $this->actingAs($this->admin)->get('/admin/assignments?time_shift=morning&view_mode=flat');
        $morningIds = $resMorning->viewData('assignments')->pluck('id')->all();
        $this->assertContains($taskMorning->id, $morningIds);
        $this->assertNotContains($taskAfternoon->id, $morningIds);

        // Filter by Afternoon Shift
        $resAfternoon = $this->actingAs($this->admin)->get('/admin/assignments?time_shift=afternoon&view_mode=flat');
        $afternoonIds = $resAfternoon->viewData('assignments')->pluck('id')->all();
        $this->assertContains($taskAfternoon->id, $afternoonIds);
        $this->assertNotContains($taskMorning->id, $afternoonIds);
    }
}

