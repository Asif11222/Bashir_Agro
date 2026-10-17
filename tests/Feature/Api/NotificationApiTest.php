<?php

namespace Tests\Feature\Api;

use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\TaskAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function createFarmerWithUser(): array
    {
        $user = User::factory()->create([
            'role' => 'farmer',
            'is_active' => true,
        ]);

        static $counter = 1;
        $code = sprintf('F-TEST-%04d', $counter++);

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'farmer_code' => $code,
            'full_name' => $user->name,
            'phone' => '01711'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
            'primary_activity' => 'ধান চাষ',
            'app_access_enabled' => true,
            'status' => 'active',
        ]);

        return [$user, $farmer];
    }

    private function createLocation(): FarmLocation
    {
        static $counter = 1;

        return FarmLocation::create([
            'location_code' => 'LOC-NOTIF-'.$counter++,
            'name' => 'পরীক্ষামূলক মাঠ',
            'type' => 'ধান ক্ষেত',
            'status' => 'active',
        ]);
    }

    private function createTemplate(): TaskTemplate
    {
        static $counter = 1;

        return TaskTemplate::create([
            'task_code' => 'TK-NOTIF-'.$counter++,
            'name' => 'ফসল পর্যবেক্ষণ',
            'category' => 'ফসল সুরক্ষা',
            'instructions' => 'নিয়মিত পর্যবেক্ষণ করুন',
            'is_active' => true,
        ]);
    }

    private function createAssignment(Farmer $farmer): TaskAssignment
    {
        $location = $this->createLocation();
        $template = $this->createTemplate();

        return TaskAssignment::create([
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'task_template_id' => $template->id,
            'scheduled_date' => now()->toDateString(),
            'start_time' => '08:00',
            'priority' => 'important',
            'status' => 'assigned',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/notifications');
        $response->assertStatus(401);
    }

    public function test_farmer_can_fetch_notifications(): void
    {
        [$user, $farmer] = $this->createFarmerWithUser();
        $assignment = $this->createAssignment($farmer);

        $user->notify(new TaskAssignedNotification($assignment));

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'event_type',
                        'title',
                        'message',
                        'timestamp',
                        'assignment_id',
                        'is_read',
                    ],
                ],
                'meta' => [
                    'unread_count',
                    'total',
                ],
            ]);
    }

    public function test_farmer_can_mark_notification_as_read(): void
    {
        [$user, $farmer] = $this->createFarmerWithUser();
        $assignment = $this->createAssignment($farmer);

        $user->notify(new TaskAssignedNotification($assignment));

        $notificationId = $user->notifications()->first()->id;

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true)
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_farmer_can_mark_all_notifications_as_read(): void
    {
        [$user, $farmer] = $this->createFarmerWithUser();
        $assignment1 = $this->createAssignment($farmer);
        $assignment2 = $this->createAssignment($farmer);

        $user->notify(new TaskAssignedNotification($assignment1));
        $user->notify(new TaskAssignedNotification($assignment2));

        $this->assertEquals(2, $user->unreadNotifications()->count());

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_task_assignment_service_dispatches_notification_to_farmer(): void
    {
        [$user, $farmer] = $this->createFarmerWithUser();
        $admin = User::factory()->create(['role' => 'admin']);
        $location = $this->createLocation();
        $template = $this->createTemplate();

        $service = app(TaskAssignmentService::class);
        $assignment = $service->createAssignment([
            'task_template_id' => $template->id,
            'farmer_id' => $farmer->id,
            'farm_location_id' => $location->id,
            'scheduled_date' => now()->toDateString(),
            'start_time' => '08:00',
            'status' => 'assigned',
        ], $admin);

        $this->assertEquals(1, $user->notifications()->count());
        $notification = $user->notifications()->first();
        $this->assertEquals('newTask', $notification->data['event_type']);
        $this->assertEquals($assignment->id, $notification->data['assignment_id']);
    }
}
