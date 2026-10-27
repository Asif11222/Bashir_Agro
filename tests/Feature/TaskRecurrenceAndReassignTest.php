<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRecurrenceAndReassignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Farmer $farmerA;
    private Farmer $farmerB;
    private TaskTemplate $task1;
    private TaskTemplate $task2;
    private FarmLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->farmerA = Farmer::create([
            'farmer_code' => 'F-000001',
            'full_name' => 'মোঃ রহিম উদ্দিন (কৃষক ক)',
            'phone' => '01711223344',
            'primary_activity' => 'সবজি চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->farmerB = Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'মোঃ করিম মিয়া (কৃষক খ)',
            'phone' => '01811223344',
            'primary_activity' => 'মাছ চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->task1 = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সকালের খাদ্য প্রদান',
            'category' => 'মাছ চাষ',
            'instructions' => 'পুকুরে সকালে খাদ্য দিন',
            'required_photo_count' => 1,
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->task2 = TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'বিকালের অক্সিজেন পরীক্ষা',
            'category' => 'মাছ চাষ',
            'instructions' => 'পানির অক্সিজেন লেভেল মাপুন',
            'required_photo_count' => 1,
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'পুকুর ১',
            'type' => 'মৎস্য খামার',
            'address' => 'ময়মনসিংহ',
            'latitude' => 24.7471,
            'longitude' => 90.4203,
            'geofence_radius_meters' => 50,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_create_assignment_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/assignments/create');
        $response->assertStatus(200);
        $response->assertSee('নতুন কাজ বরাদ্দ করুন');
    }

    public function test_admin_can_create_daily_recurring_assignments_with_multiple_tasks(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'daily',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03', // 3 days
            'daily_tasks' => [
                [
                    'task_template_id' => $this->task1->id,
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'priority' => 'normal',
                    'notes' => 'সকালের রুটিন',
                ],
                [
                    'task_template_id' => $this->task2->id,
                    'start_time' => '16:00',
                    'end_time' => '17:00',
                    'priority' => 'important',
                    'notes' => 'বিকালের রুটিন',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/assignments');
        $response->assertSessionHas('success');

        // 3 days x 2 tasks = 6 assignments
        $this->assertEquals(6, TaskAssignment::count());

        $first = TaskAssignment::orderBy('id', 'asc')->first();
        $this->assertTrue($first->is_recurring);
        $this->assertEquals('daily', $first->recurrence_type);
        $this->assertEquals(6, $first->recurrence_total_count);
        $this->assertNotNull($first->recurring_group_id);

        // All 6 should share the exact same recurring_group_id
        $this->assertEquals(6, TaskAssignment::where('recurring_group_id', $first->recurring_group_id)->count());
    }

    public function test_admin_can_create_weekly_recurring_assignments(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'weekly',
            'start_date' => '2026-11-01', // Sunday
            'weeks_count' => 2, // 2 weeks
            'weekly_days' => ['sun', 'tue'], // 2 days per week
            'weekly_tasks' => [
                [
                    'task_template_id' => $this->task1->id,
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'priority' => 'normal',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/assignments');
        $this->assertGreaterThanOrEqual(4, TaskAssignment::count());
    }

    public function test_admin_can_create_weekly_recurring_assignments_with_custom_schedule_and_different_times_per_week(): void
    {
        // 4 weeks x 3 days (Sun, Tue, Thu) with different times per week
        $customSchedule = [
            // Week 1 (09:00 - 12:00)
            ['scheduled_date' => '2026-11-01', 'task_template_id' => $this->task1->id, 'start_time' => '09:00', 'end_time' => '12:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-03', 'task_template_id' => $this->task1->id, 'start_time' => '09:00', 'end_time' => '12:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-05', 'task_template_id' => $this->task1->id, 'start_time' => '09:00', 'end_time' => '12:00', 'priority' => 'normal'],

            // Week 2 (10:00 - 13:00)
            ['scheduled_date' => '2026-11-08', 'task_template_id' => $this->task1->id, 'start_time' => '10:00', 'end_time' => '13:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-10', 'task_template_id' => $this->task1->id, 'start_time' => '10:00', 'end_time' => '13:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-12', 'task_template_id' => $this->task1->id, 'start_time' => '10:00', 'end_time' => '13:00', 'priority' => 'normal'],

            // Week 3 (07:30 - 11:30)
            ['scheduled_date' => '2026-11-15', 'task_template_id' => $this->task1->id, 'start_time' => '07:30', 'end_time' => '11:30', 'priority' => 'important'],
            ['scheduled_date' => '2026-11-17', 'task_template_id' => $this->task1->id, 'start_time' => '07:30', 'end_time' => '11:30', 'priority' => 'important'],
            ['scheduled_date' => '2026-11-19', 'task_template_id' => $this->task1->id, 'start_time' => '07:30', 'end_time' => '11:30', 'priority' => 'important'],

            // Week 4 (16:00 - 19:00)
            ['scheduled_date' => '2026-11-22', 'task_template_id' => $this->task1->id, 'start_time' => '16:00', 'end_time' => '19:00', 'priority' => 'urgent'],
            ['scheduled_date' => '2026-11-24', 'task_template_id' => $this->task1->id, 'start_time' => '16:00', 'end_time' => '19:00', 'priority' => 'urgent'],
            ['scheduled_date' => '2026-11-26', 'task_template_id' => $this->task1->id, 'start_time' => '16:00', 'end_time' => '19:00', 'priority' => 'urgent'],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'weekly',
            'start_date' => '2026-11-01',
            'weeks_count' => 4,
            'weekly_days' => ['sun', 'tue', 'thu'],
            'weekly_timing_mode' => 'by_week',
            'custom_schedule' => $customSchedule,
        ]);

        $response->assertRedirect('/admin/assignments');
        $response->assertSessionHas('success');

        $this->assertEquals(12, TaskAssignment::count());

        // Check Week 1 assignment time
        $w1Assignment = TaskAssignment::whereDate('scheduled_date', '2026-11-01')->first();
        $this->assertNotNull($w1Assignment);
        $this->assertEquals('09:00', substr($w1Assignment->start_time, 0, 5));
        $this->assertEquals('12:00', substr($w1Assignment->end_time, 0, 5));

        // Check Week 2 assignment time
        $w2Assignment = TaskAssignment::whereDate('scheduled_date', '2026-11-08')->first();
        $this->assertNotNull($w2Assignment);
        $this->assertEquals('10:00', substr($w2Assignment->start_time, 0, 5));

        // Check Week 3 assignment time
        $w3Assignment = TaskAssignment::whereDate('scheduled_date', '2026-11-15')->first();
        $this->assertNotNull($w3Assignment);
        $this->assertEquals('07:30', substr($w3Assignment->start_time, 0, 5));
        $this->assertEquals('important', $w3Assignment->priority);

        // Check Week 4 assignment time
        $w4Assignment = TaskAssignment::whereDate('scheduled_date', '2026-11-22')->first();
        $this->assertNotNull($w4Assignment);
        $this->assertEquals('16:00', substr($w4Assignment->start_time, 0, 5));
        $this->assertEquals('urgent', $w4Assignment->priority);

        // Check recurring group
        $this->assertEquals(12, TaskAssignment::where('recurring_group_id', $w1Assignment->recurring_group_id)->count());
        $this->assertEquals(12, $w1Assignment->recurrence_total_count);
    }

    public function test_admin_can_skip_or_remove_session_in_weekly_custom_schedule(): void
    {
        // 3 sessions instead of 4 (e.g. holiday removed)
        $customSchedule = [
            ['scheduled_date' => '2026-11-01', 'task_template_id' => $this->task1->id, 'start_time' => '09:00', 'end_time' => '12:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-03', 'task_template_id' => $this->task1->id, 'start_time' => '14:00', 'end_time' => '17:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-08', 'task_template_id' => $this->task1->id, 'start_time' => '10:00', 'end_time' => '13:00', 'priority' => 'normal'],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'weekly',
            'start_date' => '2026-11-01',
            'weeks_count' => 2,
            'weekly_days' => ['sun', 'tue'],
            'weekly_timing_mode' => 'custom',
            'custom_schedule' => $customSchedule,
        ]);

        $response->assertRedirect('/admin/assignments');
        $this->assertEquals(3, TaskAssignment::count());

        $first = TaskAssignment::orderBy('id', 'asc')->first();
        $this->assertEquals(3, $first->recurrence_total_count);
    }

    public function test_admin_can_create_weekly_assignments_with_independent_days_and_multiple_pool_tasks_per_week(): void
    {
        $secondLocation = FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'বাগান প্লট ১',
            'type' => 'সবজি বাগান',
            'address' => 'ময়মনসিংহ',
            'latitude' => 24.7480,
            'longitude' => 90.4210,
            'geofence_radius_meters' => 50,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Week 1: Sun (2026-11-01), Mon (2026-11-02), Tue (2026-11-03) -> 2 tasks each = 6
        // Week 2: Sun (2026-11-08), Fri (2026-11-13) -> 2 tasks each = 4
        // Total = 10 assignments
        $customSchedule = [
            // Week 1 - Sunday (Sun)
            ['scheduled_date' => '2026-11-01', 'task_template_id' => $this->task1->id, 'farm_location_id' => $this->location->id, 'start_time' => '09:00', 'end_time' => '10:00', 'priority' => 'normal', 'notes' => 'গাছে পানি'],
            ['scheduled_date' => '2026-11-01', 'task_template_id' => $this->task2->id, 'farm_location_id' => $secondLocation->id, 'start_time' => '10:00', 'end_time' => '11:00', 'priority' => 'normal', 'notes' => 'পুকুরে পানি ও খাদ্য'],

            // Week 1 - Monday (Mon)
            ['scheduled_date' => '2026-11-02', 'task_template_id' => $this->task1->id, 'farm_location_id' => $this->location->id, 'start_time' => '09:00', 'end_time' => '10:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-02', 'task_template_id' => $this->task2->id, 'farm_location_id' => $secondLocation->id, 'start_time' => '10:00', 'end_time' => '11:00', 'priority' => 'normal'],

            // Week 1 - Tuesday (Tue) with custom time
            ['scheduled_date' => '2026-11-03', 'task_template_id' => $this->task1->id, 'farm_location_id' => $this->location->id, 'start_time' => '08:30', 'end_time' => '09:30', 'priority' => 'important'],
            ['scheduled_date' => '2026-11-03', 'task_template_id' => $this->task2->id, 'farm_location_id' => $secondLocation->id, 'start_time' => '09:30', 'end_time' => '10:30', 'priority' => 'important'],

            // Week 2 - Sunday (Sun)
            ['scheduled_date' => '2026-11-08', 'task_template_id' => $this->task1->id, 'farm_location_id' => $this->location->id, 'start_time' => '09:00', 'end_time' => '10:00', 'priority' => 'normal'],
            ['scheduled_date' => '2026-11-08', 'task_template_id' => $this->task2->id, 'farm_location_id' => $secondLocation->id, 'start_time' => '10:00', 'end_time' => '11:00', 'priority' => 'normal'],

            // Week 2 - Friday (Fri) with custom time
            ['scheduled_date' => '2026-11-13', 'task_template_id' => $this->task1->id, 'farm_location_id' => $this->location->id, 'start_time' => '14:00', 'end_time' => '15:00', 'priority' => 'urgent'],
            ['scheduled_date' => '2026-11-13', 'task_template_id' => $this->task2->id, 'farm_location_id' => $secondLocation->id, 'start_time' => '15:00', 'end_time' => '16:00', 'priority' => 'urgent'],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'weekly',
            'start_date' => '2026-11-01',
            'weeks_count' => 2,
            'weekly_days' => ['sun', 'mon', 'tue', 'fri'],
            'custom_schedule' => $customSchedule,
        ]);

        $response->assertRedirect('/admin/assignments');
        $response->assertSessionHas('success');

        $this->assertEquals(10, TaskAssignment::count());

        $first = TaskAssignment::orderBy('id', 'asc')->first();
        $this->assertEquals(10, $first->recurrence_total_count);
        $this->assertEquals('weekly', $first->recurrence_type);

        // Check Tuesday custom time
        $tueTask1 = TaskAssignment::whereDate('scheduled_date', '2026-11-03')->where('task_template_id', $this->task1->id)->first();
        $this->assertNotNull($tueTask1);
        $this->assertEquals('08:30', substr($tueTask1->start_time, 0, 5));
        $this->assertEquals('09:30', substr($tueTask1->end_time, 0, 5));
        $this->assertEquals('important', $tueTask1->priority);

        // Check Friday custom time and location
        $friTask2 = TaskAssignment::whereDate('scheduled_date', '2026-11-13')->where('task_template_id', $this->task2->id)->first();
        $this->assertNotNull($friTask2);
        $this->assertEquals('15:00', substr($friTask2->start_time, 0, 5));
        $this->assertEquals($secondLocation->id, $friTask2->farm_location_id);
        $this->assertEquals('urgent', $friTask2->priority);

        // All 10 share the exact same group
        $this->assertEquals(10, TaskAssignment::where('recurring_group_id', $first->recurring_group_id)->count());
    }

    public function test_admin_can_create_monthly_recurring_assignments(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'monthly',
            'start_date' => '2026-11-01',
            'months_count' => 2,
            'monthly_days' => [1, 15],
            'monthly_tasks' => [
                [
                    'task_template_id' => $this->task1->id,
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                    'priority' => 'normal',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/assignments');
        $this->assertEquals(4, TaskAssignment::count());
    }

    public function test_admin_can_reassign_single_task_when_farmer_is_sick(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-05',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/assignments/{$assignment->id}/reassign", [
            'new_farmer_id' => $this->farmerB->id,
            'reason' => 'কৃষক অসুস্থ ও অনুপস্থিত',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals($this->farmerB->id, $assignment->farmer_id);
        $this->assertStringContainsString('কৃষক অসুস্থ ও অনুপস্থিত', $assignment->notes);
    }

    public function test_admin_can_reassign_future_series_when_farmer_is_sick(): void
    {
        $groupId = 'REC-TEST-SERIES';

        // 3 assignments across consecutive days
        $a1 = TaskAssignment::create([
            'recurring_group_id' => $groupId,
            'is_recurring' => true,
            'recurrence_type' => 'daily',
            'recurrence_seq' => 1,
            'recurrence_total_count' => 3,
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-01',
            'start_time' => '08:00',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        $a2 = TaskAssignment::create([
            'recurring_group_id' => $groupId,
            'is_recurring' => true,
            'recurrence_type' => 'daily',
            'recurrence_seq' => 2,
            'recurrence_total_count' => 3,
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-02',
            'start_time' => '08:00',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $a3 = TaskAssignment::create([
            'recurring_group_id' => $groupId,
            'is_recurring' => true,
            'recurrence_type' => 'daily',
            'recurrence_seq' => 3,
            'recurrence_total_count' => 3,
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-03',
            'start_time' => '08:00',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Reassign from $a2 onwards with apply_to_future_series = true
        $response = $this->actingAs($this->admin)->post("/admin/assignments/{$a2->id}/reassign", [
            'new_farmer_id' => $this->farmerB->id,
            'apply_to_future_series' => 1,
            'reason' => 'কৃষক হাসপাতালে ভর্তি',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $a1->refresh();
        $a2->refresh();
        $a3->refresh();

        // a1 (completed in past) remains farmer A
        $this->assertEquals($this->farmerA->id, $a1->farmer_id);
        // a2 & a3 reassigned to farmer B
        $this->assertEquals($this->farmerB->id, $a2->farmer_id);
        $this->assertEquals($this->farmerB->id, $a3->farmer_id);
    }

    public function test_admin_can_cancel_recurring_series(): void
    {
        $groupId = 'REC-TEST-CANCEL';

        $a1 = TaskAssignment::create([
            'recurring_group_id' => $groupId,
            'is_recurring' => true,
            'recurrence_type' => 'daily',
            'recurrence_seq' => 1,
            'recurrence_total_count' => 2,
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-01',
            'start_time' => '08:00',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $a2 = TaskAssignment::create([
            'recurring_group_id' => $groupId,
            'is_recurring' => true,
            'recurrence_type' => 'daily',
            'recurrence_seq' => 2,
            'recurrence_total_count' => 2,
            'task_template_id' => $this->task1->id,
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => '2026-11-02',
            'start_time' => '08:00',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/assignments/recurring/{$groupId}/cancel", [
            'from_date' => '2026-11-01',
            'reason' => 'ভারী বৃষ্টির কারণে সাময়িক বাতিল',
        ]);

        $response->assertRedirect();

        $a1->refresh();
        $a2->refresh();
        $this->assertEquals('cancelled', $a1->status);
        $this->assertEquals('cancelled', $a2->status);
    }

    public function test_admin_can_create_multiple_tasks_on_same_day_for_same_farmer_with_same_or_different_locations(): void
    {
        $location2 = FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'বাগান প্লট ২',
            'type' => 'শাকসবজি',
            'address' => 'ময়মনসিংহ',
            'latitude' => 24.7500,
            'longitude' => 90.4300,
            'geofence_radius_meters' => 60,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $customSchedule = [
            [
                'scheduled_date' => '2026-11-01',
                'task_template_id' => $this->task1->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'priority' => 'normal',
                'notes' => 'পুকুরে পানি ও খাদ্য',
            ],
            [
                'scheduled_date' => '2026-11-01',
                'task_template_id' => $this->task2->id,
                'farm_location_id' => $location2->id,
                'start_time' => '10:00',
                'end_time' => '11:00',
                'priority' => 'important',
                'notes' => 'বাগানে গাছে পানি',
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'weekly',
            'start_date' => '2026-11-01',
            'weeks_count' => 1,
            'weekly_days' => ['sun'],
            'weekly_timing_mode' => 'custom',
            'custom_schedule' => $customSchedule,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/assignments');

        $assignments = TaskAssignment::where('farmer_id', $this->farmerA->id)
            ->whereDate('scheduled_date', '2026-11-01')
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $assignments);

        // First task: 09:00 - 10:00 at Location 1
        $this->assertEquals($this->task1->id, $assignments[0]->task_template_id);
        $this->assertEquals($this->location->id, $assignments[0]->farm_location_id);
        $this->assertStringStartsWith('09:00', (string) $assignments[0]->start_time);
        $this->assertStringStartsWith('10:00', (string) $assignments[0]->end_time);

        // Second task: 10:00 - 11:00 at Location 2
        $this->assertEquals($this->task2->id, $assignments[1]->task_template_id);
        $this->assertEquals($location2->id, $assignments[1]->farm_location_id);
        $this->assertStringStartsWith('10:00', (string) $assignments[1]->start_time);
        $this->assertStringStartsWith('11:00', (string) $assignments[1]->end_time);

        // Series ID check
        $this->assertNotEmpty($assignments[0]->recurring_group_id);
        $this->assertEquals($assignments[0]->recurring_group_id, $assignments[1]->recurring_group_id);
    }

    /**
     * Test admin can create monthly assignments with custom_schedule across multiple months on 30-day basis.
     */
    public function test_admin_can_create_monthly_assignments_with_custom_schedule_on_30_day_basis(): void
    {
        $customSchedule = [
            // Month 1 (November 2026): 1st & 15th
            [
                'scheduled_date' => '2026-11-01',
                'task_template_id' => $this->task1->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'priority' => 'normal',
                'notes' => 'মাস ১ - ১লা তারিখ সার প্রয়োগ',
            ],
            [
                'scheduled_date' => '2026-11-01',
                'task_template_id' => $this->task2->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'priority' => 'normal',
                'notes' => 'মাস ১ - ১লা তারিখ অক্সিজেন পরীক্ষা',
            ],
            [
                'scheduled_date' => '2026-11-15',
                'task_template_id' => $this->task1->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'priority' => 'important',
                'notes' => 'মাস ১ - ১৫ই তারিখ সার প্রয়োগ',
            ],
            // Month 2 (December 2026): 1st & 15th (with different start time 09:00 on 1st)
            [
                'scheduled_date' => '2026-12-01',
                'task_template_id' => $this->task1->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'priority' => 'urgent',
                'notes' => 'মাস ২ - ১লা তারিখ সকাল ৯টায় স্পেশাল সার',
            ],
            [
                'scheduled_date' => '2026-12-15',
                'task_template_id' => $this->task2->id,
                'farm_location_id' => $this->location->id,
                'start_time' => '15:00',
                'end_time' => '17:00',
                'priority' => 'normal',
                'notes' => 'মাস ২ - ১৫ই তারিখ অক্সিজেন পরীক্ষা',
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/assignments', [
            'farmer_id' => $this->farmerA->id,
            'farm_location_id' => $this->location->id,
            'recurrence_type' => 'monthly',
            'start_date' => '2026-11-01',
            'months_count' => 2,
            'monthly_days' => [1, 15],
            'custom_schedule' => $customSchedule,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/assignments');

        $assignments = TaskAssignment::where('farmer_id', $this->farmerA->id)
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();

        $this->assertCount(5, $assignments);

        // Check common recurrence group
        $groupId = $assignments[0]->recurring_group_id;
        $this->assertNotEmpty($groupId);
        foreach ($assignments as $a) {
            $this->assertEquals($groupId, $a->recurring_group_id);
            $this->assertEquals('monthly', $a->recurrence_type);
        }

        // Check month 2 custom start time 09:00
        $month2FirstTask = TaskAssignment::where('farmer_id', $this->farmerA->id)
            ->whereDate('scheduled_date', '2026-12-01')
            ->first();
        $this->assertNotNull($month2FirstTask);
        $this->assertStringStartsWith('09:00', (string) $month2FirstTask->start_time);
        $this->assertEquals('urgent', $month2FirstTask->priority);
    }
}
