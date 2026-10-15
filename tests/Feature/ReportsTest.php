<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Farmer $farmer;

    private FarmLocation $location;

    private TaskTemplate $task;

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

        $this->location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ময়মনসিংহ',
            'geofence_radius_meters' => 20,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সার প্রয়োগ',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'গাছের গোড়ায় সার দিন।',
            'required_photo_count' => 2,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get('/admin/reports');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_reports_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('রিপোর্ট ও অ্যানালিটিক্স');
        $response->assertSee('সময়মতো সম্পন্ন হার');
        $response->assertSee('কাজের পারফরম্যান্স রিপোর্ট');
    }

    public function test_task_performance_report_calculations(): void
    {
        // 2 completed, 1 in_progress, 1 cancelled = 4 total, 50% completed
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'status' => 'in_progress',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '11:00',
            'status' => 'cancelled',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->getTaskPerformanceReport();
        $taskItem = $report->firstWhere('id', $this->task->id);

        $this->assertNotNull($taskItem);
        $this->assertEquals(4, $taskItem->total_assignments);
        $this->assertEquals(2, $taskItem->completed_assignments);
        $this->assertEquals(1, $taskItem->in_progress_assignments);
        $this->assertEquals(1, $taskItem->cancelled_assignments);
        $this->assertEquals(50.0, $taskItem->completion_percentage);

        $response = $this->actingAs($this->admin)->get('/admin/reports?tab=tasks&date_filter=all');
        $response->assertStatus(200);
        $response->assertSee('সার প্রয়োগ');
        $response->assertSee('50%');
    }

    public function test_farmer_activity_report_calculations(): void
    {
        // 2 completed, 1 in_progress
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'status' => 'in_progress',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->getFarmerActivityReport();
        $farmerItem = $report->firstWhere('id', $this->farmer->id);

        $this->assertNotNull($farmerItem);
        $this->assertEquals(3, $farmerItem->total_assigned_tasks);
        $this->assertEquals(2, $farmerItem->completed_tasks);
        $this->assertEquals(1, $farmerItem->pending_tasks);
        $this->assertEquals(66.7, $farmerItem->completion_rate);

        $response = $this->actingAs($this->admin)->get('/admin/reports?tab=farmers&date_filter=all');
        $response->assertStatus(200);
        $response->assertSee($this->farmer->full_name);
        $response->assertSee('66.7%');
    }

    public function test_location_performance_report_calculations(): void
    {
        $this->location->farmers()->attach($this->farmer->id);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'status' => 'assigned',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(ReportService::class);
        $report = $service->getLocationPerformanceReport();
        $locItem = $report->firstWhere('id', $this->location->id);

        $this->assertNotNull($locItem);
        $this->assertEquals(2, $locItem->assigned_tasks);
        $this->assertEquals(1, $locItem->completed_tasks);
        $this->assertEquals(1, $locItem->active_farmers);
        $this->assertEquals(50.0, $locItem->completion_rate);

        $response = $this->actingAs($this->admin)->get('/admin/reports?tab=locations&date_filter=all');
        $response->assertStatus(200);
        $response->assertSee($this->location->name);
        $response->assertSee('50%');
    }

    public function test_assignment_status_report_breakdown(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'urgent',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/reports?tab=status&priority=urgent&date_filter=all');

        $response->assertStatus(200);
        $response->assertSee('কাজের অবস্থা বিভাজন');
        $response->assertSee('সম্পন্ন (Completed)');
        $response->assertSee('চলমান (In Progress)');
        $response->assertSee('50%');
    }

    public function test_reports_date_filtering(): void
    {
        // Today's assignment
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        // Past month's assignment
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->subMonths(2),
            'start_time' => '08:00',
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(ReportService::class);

        // Filter: today
        $todayKpi = $service->getOverviewKpis(today()->toDateString(), today()->toDateString());
        $this->assertEquals(1, $todayKpi['total_assignments']);

        // Filter: all time
        $allKpi = $service->getOverviewKpis();
        $this->assertEquals(2, $allKpi['total_assignments']);

        // Via HTTP request
        $response = $this->actingAs($this->admin)->get('/admin/reports?date_filter=today');
        $response->assertStatus(200);
        $response->assertSee('1টি সম্পন্ন');
    }

    public function test_reports_search_filtering(): void
    {
        $task2 = TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'মাছের খাবার বিতরণ',
            'category' => 'মৎস্য চাষ',
            'instructions' => 'খাবার ছড়ান',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $responseSearch = $this->actingAs($this->admin)->get('/admin/reports?tab=tasks&search=মৎস্য');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('মাছের খাবার বিতরণ');
        $responseSearch->assertDontSee('সার প্রয়োগ');
    }

    public function test_activity_report_loading(): void
    {
        ActivityLog::record(
            action: 'report.generated',
            description: 'বার্ষিক কর্মক্ষমতা রিপোর্ট ডাউনলোড করা হয়েছে',
            subject: $this->farmer
        );

        $response = $this->actingAs($this->admin)->get('/admin/reports?tab=activity&date_filter=all');

        $response->assertStatus(200);
        $response->assertSee('সিস্টেম অ্যাক্টিভিটি রিপোর্ট');
        $response->assertSee('report.generated');
        $response->assertSee('বার্ষিক কর্মক্ষমতা রিপোর্ট ডাউনলোড করা হয়েছে');
    }
}
