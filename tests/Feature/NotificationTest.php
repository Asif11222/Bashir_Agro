<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Notifications\AssignmentCompletedNotification;
use App\Notifications\FarmerCreatedNotification;
use App\Notifications\SystemNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->get('/admin/notifications');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_notification_center(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/notifications');

        $response->assertStatus(200);
        $response->assertSee('সিস্টেম নোটিফিকেশন');
        $response->assertSee('নোটিফিকেশন সেন্টার');
        $response->assertSee('সব নোটিফিকেশন');
        $response->assertSee('অপঠিত');
        $response->assertSee('পঠিত');
    }

    public function test_admin_receives_and_sees_system_notification(): void
    {
        $admin = $this->createAdmin();

        $admin->notify(new SystemNotification(
            title: 'সার্ভার রক্ষণাবেক্ষণ নোটিশ',
            message: 'আজ রাত ১২টায় সার্ভার ৫ মিনিটের জন্য রিস্টার্ট হবে।',
            actionUrl: route('admin.dashboard')
        ));

        $this->assertEquals(1, $admin->notifications()->count());
        $this->assertEquals(1, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin)->get('/admin/notifications');

        $response->assertStatus(200);
        $response->assertSee('সার্ভার রক্ষণাবেক্ষণ নোটিশ');
        $response->assertSee('আজ রাত ১২টায় সার্ভার ৫ মিনিটের জন্য রিস্টার্ট হবে।');
        $response->assertSee('⚙ সিস্টেম');
    }

    public function test_notifications_filter_by_unread_and_read(): void
    {
        $admin = $this->createAdmin();

        $admin->notify(new SystemNotification('নোটিফিকেশন এক', 'প্রথম বিবরণ'));
        $admin->notify(new SystemNotification('নোটিফিকেশন দুই', 'দ্বিতীয় বিবরণ'));

        // Mark the first one as read
        $firstNotification = $admin->notifications()->first();
        $firstNotification->markAsRead();

        $this->assertEquals(2, $admin->notifications()->count());
        $this->assertEquals(1, $admin->unreadNotifications()->count());

        // Test unread filter
        $unreadResponse = $this->actingAs($admin)->get('/admin/notifications?filter=unread');
        $unreadResponse->assertStatus(200);
        $unreadResponse->assertSee('নোটিফিকেশন দুই');
        $unreadResponse->assertDontSee('নোটিফিকেশন এক');

        // Test read filter
        $readResponse = $this->actingAs($admin)->get('/admin/notifications?filter=read');
        $readResponse->assertStatus(200);
        $readResponse->assertSee('নোটিফিকেশন এক');
        $readResponse->assertDontSee('নোটিফিকেশন দুই');
    }

    public function test_admin_can_mark_individual_notification_as_read(): void
    {
        $admin = $this->createAdmin();

        $admin->notify(new SystemNotification('পরীক্ষামূলক নোটিফিকেশন', 'বিবরণ'));
        $notification = $admin->unreadNotifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($admin)->post("/admin/notifications/{$notification->id}/read");

        $response->assertSessionHas('success');
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(0, $admin->unreadNotifications()->count());
    }

    public function test_admin_cannot_mark_another_users_notification_as_read(): void
    {
        $admin1 = $this->createAdmin();
        $admin2 = $this->createAdmin();

        $admin2->notify(new SystemNotification('অন্য ব্যবহারকারীর নোটিফিকেশন', 'গোপন বিবরণ'));
        $notification = $admin2->notifications()->first();

        $response = $this->actingAs($admin1)->post("/admin/notifications/{$notification->id}/read");

        $response->assertStatus(404);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = $this->createAdmin();

        $admin->notify(new SystemNotification('নোটিশ ১', 'বিবরণ ১'));
        $admin->notify(new SystemNotification('নোটিশ ২', 'বিবরণ ২'));
        $admin->notify(new SystemNotification('নোটিশ ৩', 'বিবরণ ৩'));

        $this->assertEquals(3, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin)->post('/admin/notifications/read-all');

        $response->assertSessionHas('success');
        $this->assertEquals(0, $admin->unreadNotifications()->count());
        $this->assertEquals(3, $admin->notifications()->whereNotNull('read_at')->count());
    }

    public function test_admin_can_delete_notification(): void
    {
        $admin = $this->createAdmin();

        $admin->notify(new SystemNotification('মুছে ফেলার জন্য নোটিশ', 'বিবরণ'));
        $notification = $admin->notifications()->first();

        $response = $this->actingAs($admin)->delete("/admin/notifications/{$notification->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_farmer_creation_triggers_farmer_created_notification(): void
    {
        $admin = $this->createAdmin();

        $farmerData = [
            'full_name' => 'আবুল কালাম',
            'phone' => '01711002233',
            'nid' => '1234567890123',
            'gender' => 'পুরুষ',
            'address' => 'পাবনা সদর',
            'primary_activity' => 'সবজি চাষ',
            'experience_years' => 5,
            'app_access_enabled' => true,
            'status' => 'active',
            'emergency_contact_name' => 'জরুরি ব্যক্তি',
            'emergency_contact_phone' => '01811002233',
            'emergency_contact_relationship' => 'ভাই',
        ];

        $response = $this->actingAs($admin)->post('/admin/farmers', $farmerData);

        $response->assertRedirect('/admin/farmers');

        $this->assertEquals(1, $admin->notifications()->count());
        $notification = $admin->notifications()->first();
        $this->assertEquals(FarmerCreatedNotification::class, $notification->type);
        $this->assertStringContainsString('আবুল কালাম', $notification->data['message']);
        $this->assertEquals('farmer', $notification->data['module']);
    }

    public function test_task_assignment_creation_triggers_task_assigned_notification(): void
    {
        $admin = $this->createAdmin();

        $farmer = Farmer::create([
            'farmer_code' => 'F-201',
            'full_name' => 'মোবারক হোসেন',
            'phone' => '01722334455',
            'primary_activity' => 'ধান চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-201',
            'name' => 'পশ্চিম প্লট',
            'type' => 'ধান ক্ষেত',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-201',
            'name' => 'কীটনাশক ছিটানো',
            'category' => 'ফসল সুরক্ষা',
            'instructions' => 'সাবধানে স্প্রে করুন',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $assignmentData = [
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'task_template_id' => $task->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '08:30',
            'priority' => 'important',
            'status' => 'assigned',
        ];

        $response = $this->actingAs($admin)->post('/admin/assignments', $assignmentData);

        $response->assertRedirect('/admin/assignments');

        $this->assertEquals(1, $admin->notifications()->count());
        $notification = $admin->notifications()->first();
        $this->assertEquals(TaskAssignedNotification::class, $notification->type);
        $this->assertStringContainsString('মোবারক হোসেন', $notification->data['message']);
        $this->assertStringContainsString('কীটনাশক ছিটানো', $notification->data['message']);
        $this->assertEquals('assignment', $notification->data['module']);
    }

    public function test_task_assignment_completion_triggers_assignment_completed_notification(): void
    {
        $admin = $this->createAdmin();

        $farmer = Farmer::create([
            'farmer_code' => 'F-202',
            'full_name' => 'আফসার আলী',
            'phone' => '01733445566',
            'primary_activity' => 'মাছ চাষ',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $location = FarmLocation::create([
            'location_code' => 'LOC-202',
            'name' => 'পূর্ব পুকুর',
            'type' => 'মাছের পুকুর',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $task = TaskTemplate::create([
            'task_code' => 'TK-202',
            'name' => 'পানির পিএইচ পরীক্ষা',
            'category' => 'পানি পরীক্ষা',
            'instructions' => 'পিএইচ লেভেল নোট করুন',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $assignment = TaskAssignment::create([
            'task_template_id' => $task->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => today(),
            'start_time' => '09:00',
            'priority' => 'normal',
            'status' => 'in_progress',
            'assigned_by' => $admin->id,
        ]);

        // Complete the assignment
        $updateData = [
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'task_template_id' => $task->id,
            'scheduled_date' => today()->toDateString(),
            'start_time' => '09:00',
            'priority' => 'normal',
            'status' => 'completed',
        ];

        $response = $this->actingAs($admin)->put("/admin/assignments/{$assignment->id}", $updateData);

        $response->assertRedirect('/admin/assignments');

        $this->assertEquals(1, $admin->notifications()->count());
        $notification = $admin->notifications()->first();
        $this->assertEquals(AssignmentCompletedNotification::class, $notification->type);
        $this->assertStringContainsString('আফসার আলী', $notification->data['message']);
        $this->assertStringContainsString('পানির পিএইচ পরীক্ষা', $notification->data['message']);
    }
}
