<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $this->user = User::factory()->create(['employee_id' => $employee->id]);
    }

    private function createNotification(array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'type' => 'info',
            'title' => 'إشعار اختباري',
            'body' => 'محتوى الإشعار',
            'read_at' => null,
        ], $overrides));
    }

    public function test_user_can_list_notifications(): void
    {
        $this->createNotification();
        $this->createNotification(['title' => 'إشعار ثاني']);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['notifications', 'unread_count']]);
    }

    public function test_user_can_filter_unread_notifications(): void
    {
        $this->createNotification();
        $this->createNotification(['read_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications?is_read=0');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = $this->createNotification();

        $response = $this->actingAs($this->user)->putJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->createNotification();
        $this->createNotification();
        $this->createNotification();

        $response = $this->actingAs($this->user)->putJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated_count', 3);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_user_cannot_read_other_users_notification(): void
    {
        $department = Department::factory()->create();
        $otherEmployee = Employee::factory()->create(['department_id' => $department->id]);
        $otherUser = User::factory()->create(['employee_id' => $otherEmployee->id]);

        $notification = Notification::create([
            'id' => Str::uuid(),
            'user_id' => $otherUser->id,
            'type' => 'info',
            'title' => 'إشعار خاص',
            'body' => 'محتوى',
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/notifications/{$notification->id}/read");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }
}
