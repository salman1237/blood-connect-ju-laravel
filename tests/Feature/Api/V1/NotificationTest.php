<?php

namespace Tests\Feature\Api\V1;

use App\Models\DonorProfile;
use App\Models\User;
use App\Notifications\EligibleDonorReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_notifications_index_lists_the_users_own_notifications(): void
    {
        $user = $this->onboardedUser();
        $user->notify(new EligibleDonorReminder(0));

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonStructure([['id', 'message', 'request_id', 'read_at', 'created_at']]);
    }

    public function test_notifications_index_only_returns_the_callers_own(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $owner->notify(new EligibleDonorReminder(0));

        $response = $this->actingAs($other, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = $this->onboardedUser();
        $user->notify(new EligibleDonorReminder(0));
        $notification = $user->notifications()->first();

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertNoContent();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->onboardedUser();
        $user->notify(new EligibleDonorReminder(0));
        $user->notify(new EligibleDonorReminder(1));

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/notifications/read-all');

        $response->assertNoContent();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = $this->onboardedUser();
        $intruder = $this->onboardedUser();
        $owner->notify(new EligibleDonorReminder(0));
        $notification = $owner->notifications()->first();

        $response = $this->actingAs($intruder, 'sanctum')->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notifications_require_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications')->assertForbidden();
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->patchJson('/api/v1/notifications/read-all')->assertUnauthorized();
    }
}
