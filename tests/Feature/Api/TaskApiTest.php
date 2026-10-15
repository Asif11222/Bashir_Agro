<?php

namespace Tests\Feature\Api;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_active_tasks(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        TaskTemplate::create([
            'task_code' => 'TK-777001',
            'name' => 'সার প্রয়োগ',
            'category' => 'সার ও পুষ্টি',
            'instructions' => 'সুষম সার প্রয়োগ করুন',
            'status' => 'active',
            'is_active' => true,
        ]);

        TaskTemplate::create([
            'task_code' => 'TK-777002',
            'name' => 'নিড়ানি দেওয়া',
            'category' => 'আগাছা দমন',
            'instructions' => 'আগাছা পরিষ্কার করুন',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Inactive task should not appear
        TaskTemplate::create([
            'task_code' => 'TK-777003',
            'name' => 'পুরনো কাজ',
            'category' => 'অন্যান্য',
            'instructions' => 'পুরনো কাজের নির্দেশিকা',
            'status' => 'inactive',
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tasks');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('সার প্রয়োগ', $names);
        $this->assertContains('নিড়ানি দেওয়া', $names);
    }

    public function test_tasks_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        TaskTemplate::create([
            'task_code' => 'TK-777004',
            'name' => 'কীটনাশক স্প্রে',
            'category' => 'ফসল সুরক্ষা',
            'instructions' => 'স্প্রে করুন',
            'status' => 'active',
            'is_active' => true,
        ]);

        TaskTemplate::create([
            'task_code' => 'TK-777005',
            'name' => 'সেচ দেওয়া',
            'category' => 'সেচ',
            'instructions' => 'পানি দিন',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tasks?category='.urlencode('ফসল সুরক্ষা'));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('কীটনাশক স্প্রে', $response->json('data.0.name'));
    }
}
