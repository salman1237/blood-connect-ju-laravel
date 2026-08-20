<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.edit'));

        $response->assertOk();
    }

    public function test_user_can_disable_email_notifications(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => true]);

        $this->actingAs($user)->patch(route('settings.notifications.update'), [
            'email_notifications_enabled' => '0',
        ]);

        $this->assertFalse($user->fresh()->email_notifications_enabled);
    }

    public function test_user_can_reenable_email_notifications(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => false]);

        $this->actingAs($user)->patch(route('settings.notifications.update'), [
            'email_notifications_enabled' => '1',
        ]);

        $this->assertTrue($user->fresh()->email_notifications_enabled);
    }
}
