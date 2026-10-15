<?php

namespace Tests\Feature;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTemplateManagementTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_task_management(): void
    {
        $response = $this->get('/admin/tasks');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/admin/tasks/create');
        $responseCreate->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_task_list(): void
    {
        TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সারের প্রয়োগ',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'ইউরিয়া ও ডিএপি সার প্রয়োগ করুন।',
            'required_photo_count' => 2,
            'require_realtime_photo' => true,
            'allow_gallery_upload' => false,
            'require_geofence' => true,
            'recurrence_type' => 'weekly',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/tasks');

        $response->assertStatus(200);
        $response->assertSee('কাজ ব্যবস্থাপনা');
        $response->assertSee('সারের প্রয়োগ');
        $response->assertSee('TASK-000001');
        $response->assertSee('সার ও পুষ্টি');
    }

    public function test_admin_can_create_task_template_with_sequential_code(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/tasks', [
            'name' => 'কীটনাশক ছিটানো',
            'category' => 'কীটনাশক',
            'description' => 'পোকার আক্রমণ রোধে স্প্রে করা।',
            'instructions' => 'মাস্ক ও গ্লাভস পরে স্প্রে করবেন।',
            'required_photo_count' => 3,
            'require_realtime_photo' => '1',
            'allow_gallery_upload' => '0',
            'require_geofence' => '1',
            'recurrence_type' => 'once',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.tasks.index'));
        $response->assertSessionHas('success');

        $task = TaskTemplate::where('name', 'কীটনাশক ছিটানো')->first();
        $this->assertNotNull($task);
        $this->assertEquals('TASK-000001', $task->task_code);
        $this->assertEquals('কীটনাশক', $task->category);
        $this->assertEquals(3, $task->required_photo_count);
        $this->assertTrue($task->require_realtime_photo);
        $this->assertFalse($task->allow_gallery_upload);
        $this->assertTrue($task->require_geofence);
        $this->assertEquals('active', $task->status);
        $this->assertTrue($task->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task.created',
            'subject_type' => TaskTemplate::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_task_code_is_generated_sequentially(): void
    {
        $task1 = TaskTemplate::create([
            'task_code' => TaskTemplate::generateNextTaskCode(),
            'name' => 'কাজ ১',
            'category' => 'সেচ',
            'instructions' => 'পানি দিন',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->assertEquals('TASK-000001', $task1->task_code);

        $task2 = TaskTemplate::create([
            'task_code' => TaskTemplate::generateNextTaskCode(),
            'name' => 'কাজ ২',
            'category' => 'আগাছা দমন',
            'instructions' => 'আগাছা তুলুন',
            'required_photo_count' => 1,
            'recurrence_type' => 'weekly',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->assertEquals('TASK-000002', $task2->task_code);
    }

    public function test_validation_failure_when_creating_task_template(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/tasks', [
            'name' => '',
            'category' => '',
            'instructions' => '',
            'required_photo_count' => -1,
            'recurrence_type' => 'invalid_recurrence',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'category',
            'instructions',
            'required_photo_count',
            'recurrence_type',
            'status',
        ]);
    }

    public function test_admin_can_view_task_template_details(): void
    {
        $task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'পানি সেচ প্রদান',
            'category' => 'সেচ',
            'description' => 'নিয়মিত সেচ',
            'instructions' => 'প্রতিটি ব্লকে ২ ইঞ্চি পানি নিশ্চিত করুন।',
            'required_photo_count' => 1,
            'require_realtime_photo' => true,
            'allow_gallery_upload' => false,
            'require_geofence' => true,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/tasks/{$task->id}");

        $response->assertStatus(200);
        $response->assertSee('কাজের বিবরণ');
        $response->assertSee('পানি সেচ প্রদান');
        $response->assertSee('TASK-000001');
        $response->assertSee('প্রতিটি ব্লকে ২ ইঞ্চি পানি নিশ্চিত করুন।');
    }

    public function test_admin_can_update_task_template(): void
    {
        $task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'পুরাতন কাজ',
            'category' => 'সেচ',
            'instructions' => 'পুরাতন নির্দেশাবলী',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/tasks/{$task->id}", [
            'name' => 'আপডেটেড কাজ',
            'category' => 'ফসল সংগ্রহ',
            'description' => 'ফসল কাটার কাজ',
            'instructions' => 'সতর্কতার সাথে ফসল সংগ্রহ করুন।',
            'required_photo_count' => 4,
            'require_realtime_photo' => '1',
            'allow_gallery_upload' => '1',
            'require_geofence' => '0',
            'recurrence_type' => 'once',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.tasks.index'));

        $this->assertDatabaseHas('task_templates', [
            'id' => $task->id,
            'name' => 'আপডেটেড কাজ',
            'category' => 'ফসল সংগ্রহ',
            'required_photo_count' => 4,
            'allow_gallery_upload' => true,
            'require_geofence' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task.updated',
            'subject_type' => TaskTemplate::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_status_change_logs_activation_or_deactivation(): void
    {
        $task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'কাজ সক্রিয়',
            'category' => 'সেচ',
            'instructions' => 'নির্দেশনা',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Change from active to inactive
        $this->actingAs($this->admin)->put("/admin/tasks/{$task->id}", [
            'name' => 'কাজ সক্রিয়',
            'category' => 'সেচ',
            'instructions' => 'নির্দেশনা',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task.deactivated',
            'subject_type' => TaskTemplate::class,
            'subject_id' => $task->id,
        ]);

        // Change from inactive to active
        $this->actingAs($this->admin)->put("/admin/tasks/{$task->id}", [
            'name' => 'কাজ সক্রিয়',
            'category' => 'সেচ',
            'instructions' => 'নির্দেশনা',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task.activated',
            'subject_type' => TaskTemplate::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_admin_can_soft_delete_task_template(): void
    {
        $task = TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'মুছে ফেলার কাজ',
            'category' => 'মাটি প্রস্তুত',
            'instructions' => 'হাল চাষ করুন',
            'required_photo_count' => 1,
            'recurrence_type' => 'once',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/tasks/{$task->id}");

        $response->assertRedirect(route('admin.tasks.index'));
        $this->assertSoftDeleted('task_templates', ['id' => $task->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'task.deleted',
            'subject_type' => TaskTemplate::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_search_task_templates_by_name_code_or_category(): void
    {
        TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সার প্রয়োগ উত্তর মাঠ',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'নির্দেশনা ১',
            'required_photo_count' => 1,
            'recurrence_type' => 'once',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'মাছকে খাবার প্রদান',
            'category' => 'মৎস্য খাদ্য',
            'instructions' => 'নির্দেশনা ২',
            'required_photo_count' => 1,
            'recurrence_type' => 'daily',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $responseName = $this->actingAs($this->admin)->get('/admin/tasks?search=উত্তর');
        $responseName->assertSee('সার প্রয়োগ উত্তর মাঠ');
        $responseName->assertDontSee('মাছকে খাবার প্রদান');

        $responseCode = $this->actingAs($this->admin)->get('/admin/tasks?search=TASK-000002');
        $responseCode->assertSee('মাছকে খাবার প্রদান');
        $responseCode->assertDontSee('সার প্রয়োগ উত্তর মাঠ');

        $responseCategory = $this->actingAs($this->admin)->get('/admin/tasks?search=মৎস্য');
        $responseCategory->assertSee('মাছকে খাবার প্রদান');
        $responseCategory->assertDontSee('সার প্রয়োগ উত্তর মাঠ');
    }

    public function test_filter_task_templates_by_status(): void
    {
        TaskTemplate::create([
            'task_code' => 'TASK-000001',
            'name' => 'সক্রিয় কাজের নাম',
            'category' => 'সেচ',
            'instructions' => 'নির্দেশনা',
            'required_photo_count' => 1,
            'recurrence_type' => 'once',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        TaskTemplate::create([
            'task_code' => 'TASK-000002',
            'name' => 'নিষ্ক্রিয় কাজের নাম',
            'category' => 'সেচ',
            'instructions' => 'নির্দেশনা',
            'required_photo_count' => 1,
            'recurrence_type' => 'once',
            'status' => 'inactive',
            'created_by' => $this->admin->id,
        ]);

        $responseActive = $this->actingAs($this->admin)->get('/admin/tasks?status=active');
        $responseActive->assertSee('সক্রিয় কাজের নাম');
        $responseActive->assertDontSee('নিষ্ক্রিয় কাজের নাম');

        $responseInactive = $this->actingAs($this->admin)->get('/admin/tasks?status=inactive');
        $responseInactive->assertSee('নিষ্ক্রিয় কাজের নাম');
        $responseInactive->assertDontSee('সক্রিয় কাজের নাম');
    }

    public function test_task_template_pagination_works(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            TaskTemplate::create([
                'task_code' => sprintf('TASK-%06d', $i),
                'name' => "Task {$i}",
                'category' => 'সাধারণ',
                'instructions' => 'সাধারণ কাজ',
                'required_photo_count' => 1,
                'recurrence_type' => 'once',
                'status' => 'active',
                'created_by' => $this->admin->id,
            ]);
        }

        $responsePage1 = $this->actingAs($this->admin)->get('/admin/tasks?status=active');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('Task 25'); // latest first

        $responsePage2 = $this->actingAs($this->admin)->get('/admin/tasks?status=active&page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Task 1');
    }
}
