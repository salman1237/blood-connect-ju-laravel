<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use App\Notifications\EligibleDonorReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
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

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('notifications'));
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = $this->onboardedUser();
        $user->notify(new EligibleDonorReminder(0));
        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)->patch(route('notifications.read', $notification->id));

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->onboardedUser();
        $user->notify(new EligibleDonorReminder(0));
        $user->notify(new EligibleDonorReminder(1));

        $this->actingAs($user)->patch(route('notifications.read-all'));

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = $this->onboardedUser();
        $intruder = $this->onboardedUser();
        $owner->notify(new EligibleDonorReminder(0));
        $notification = $owner->notifications()->first();

        $response = $this->actingAs($intruder)->patch(route('notifications.read', $notification->id));

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
