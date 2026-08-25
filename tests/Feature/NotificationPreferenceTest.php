<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\EligibleDonorReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_mail_and_database_when_enabled(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => true]);

        $channels = (new EligibleDonorReminder(0))->via($user);

        // Push always rides along regardless of the email preference — it's
        // a separate toggle (whether this device has any tokens registered
        // is what actually gates delivery, not this).
        $this->assertSame(['database', 'mail', FcmChannel::class], $channels);
    }

    public function test_notification_skips_mail_when_disabled(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => false]);

        $channels = (new EligibleDonorReminder(0))->via($user);

        $this->assertSame(['database', FcmChannel::class], $channels);
    }

    public function test_in_app_notification_still_recorded_when_email_disabled(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => false]);

        $user->notify(new EligibleDonorReminder(0));

        $this->assertSame(1, $user->notifications()->count());
    }
}
