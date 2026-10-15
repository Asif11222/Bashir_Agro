<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMonitoringTest extends TestCase
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
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->location = FarmLocation::create([
            'location_code' => 'LOC-000001',
            'name' => 'উত্তর মাঠ',
            'type' => 'সবজি ক্ষেত',
            'address' => 'ঢাকা',
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
            'require_realtime_photo' => true,
            'require_geofence' => true,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_monitoring(): void
    {
        $response = $this->get('/admin/monitoring');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ড্যাশবোর্ড');
        $response->assertSee('সক্রিয় কৃষক');
        $response->assertSee('আজকের কাজ');
        $response->assertSee('সক্রিয় লোকেশন');
    }

    public function test_monitoring_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monitoring');

        $response->assertStatus(200);
        $response->assertSee('কার্যক্রম পর্যবেক্ষণ');
        $response->assertSee('আজকের মোট কাজ');
        $response->assertSee('চলমান অপারেশন');
    }

    public function test_kpi_values_match_database(): void
    {
        // Add 1 more active and 1 inactive farmer
        Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'করিম শেখ',
            'phone' => '01811223344',
            'primary_activity' => 'মৎস্য',
            'address' => 'ঢাকা',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        Farmer::create([
            'farmer_code' => 'F-000003',
            'full_name' => 'সেলিম মিয়া',
            'phone' => '01911223344',
            'primary_activity' => 'ডেইরি',
            'address' => 'ঢাকা',
            'status' => 'inactive',
            'created_by' => $this->admin->id,
        ]);

        // Add 1 more active location
        FarmLocation::create([
            'location_code' => 'LOC-000002',
            'name' => 'দক্ষিণ প্লট',
            'type' => 'ধানের জমি',
            'address' => 'বগুড়া',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Create today assignments
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'priority' => 'normal',
            'status' => 'in_progress',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'priority' => 'important',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(DashboardService::class);
        $kpis = $service->getKpis();

        $this->assertEquals(2, $kpis['active_farmers']);
        $this->assertEquals(2, $kpis['active_locations']);
        $this->assertEquals(1, $kpis['active_task_templates']);
        $this->assertEquals(2, $kpis['todays_tasks_count']);
        $this->assertEquals(1, $kpis['todays_in_progress_count']);
        $this->assertEquals(1, $kpis['todays_completed_count']);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('2'); // active farmers count
    }

    public function test_todays_assignments_display_correctly(): void
    {
        $todayAssignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:30',
            'end_time' => '10:30',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'assigned_by' => $this->admin->id,
        ]);

        $pastAssignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today()->subDays(3),
            'start_time' => '14:00',
            'priority' => 'normal',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('08:30');
        $response->assertSee($this->farmer->full_name);
        $response->assertSee($this->task->name);
        $response->assertSee($this->location->name);
        $response->assertDontSee('14:00');
    }

    public function test_dashboard_filters_work(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '08:00',
            'priority' => 'normal',
            'status' => 'in_progress',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '11:00',
            'priority' => 'normal',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        $responseInProgress = $this->actingAs($this->admin)->get('/admin/dashboard?status=in_progress');
        $responseInProgress->assertSee('08:00');
        $responseInProgress->assertDontSee('11:00');

        $responseCompleted = $this->actingAs($this->admin)->get('/admin/dashboard?status=completed');
        $responseCompleted->assertSee('11:00');
        $responseCompleted->assertDontSee('08:00');
    }

    public function test_monitoring_filters_work(): void
    {
        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '07:00',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'assigned_by' => $this->admin->id,
        ]);

        TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '13:00',
            'priority' => 'normal',
            'status' => 'completed',
            'assigned_by' => $this->admin->id,
        ]);

        // Filter by status
        $resStatus = $this->actingAs($this->admin)->get('/admin/monitoring?status=in_progress');
        $resStatus->assertSee('07:00');
        $resStatus->assertDontSee('13:00');

        // Filter by priority
        $resPriority = $this->actingAs($this->admin)->get('/admin/monitoring?priority=urgent');
        $resPriority->assertSee('07:00');
        $resPriority->assertDontSee('13:00');
    }

    public function test_monitoring_search_works(): void
    {
        $farmer2 = Farmer::create([
            'farmer_code' => 'F-000002',
            'full_name' => 'কামাল উদ্দিন',
            'phone' => '01811223344',
            'primary_activity' => 'মাছ চাষ',
            'address' => 'ময়মনসিংহ',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $task2 = TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'পুকুরে খাবার বিতরণ',
            'category' => 'মৎস্য',
            'instructions' => 'খাবার দিন।',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

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

        TaskAssignment::create([
            'task_template_id' => $task2->id,
            'farmer_id' => $farmer2->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '10:00',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Search by farmer name
        $responseFarmer = $this->actingAs($this->admin)->get('/admin/monitoring?search=কামাল');
        $responseFarmer->assertSee('কামাল উদ্দিন');
        $responseFarmer->assertDontSee('মোঃ রহিম উদ্দিন');

        // Search by task name
        $responseTask = $this->actingAs($this->admin)->get('/admin/monitoring?search=খাবার');
        $responseTask->assertSee('পুকুরে খাবার বিতরণ');
        $responseTask->assertDontSee('সার প্রয়োগ');
    }

    public function test_monitoring_relationships_load_correctly(): void
    {
        $assignment = TaskAssignment::create([
            'task_template_id' => $this->task->id,
            'farmer_id' => $this->farmer->id,
            'farm_location_id' => $this->location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'priority' => 'important',
            'status' => 'in_progress',
            'started_at' => now(),
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/monitoring');

        $response->assertStatus(200);
        $response->assertSee('চলমান অপারেশন ফোকাস');
        $response->assertSee($this->farmer->full_name);
        $response->assertSee($this->task->name);
        $response->assertSee($this->location->name);
        $response->assertSee('GPS জিও-ফেন্স বাফার');
        $response->assertSee('রিয়েল-টাইম ক্যামেরা ছবি');
    }

    public function test_recent_activity_displays_on_dashboard_and_monitoring(): void
    {
        ActivityLog::record(
            action: 'farmer.created',
            description: 'নতুন কৃষক নিবন্ধিত হয়েছে: মোঃ জামান (F-000004)',
            subject: $this->farmer
        );

        $responseDashboard = $this->actingAs($this->admin)->get('/admin/dashboard');
        $responseDashboard->assertSee('নতুন কৃষক নিবন্ধিত হয়েছে: মোঃ জামান (F-000004)');

        $responseMonitoring = $this->actingAs($this->admin)->get('/admin/monitoring');
        $responseMonitoring->assertSee('নতুন কৃষক নিবন্ধিত হয়েছে: মোঃ জামান (F-000004)');
    }
}
