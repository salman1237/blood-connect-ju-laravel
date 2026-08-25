<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_disable_email_notifications(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => true]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/settings/notifications', [
            'email_notifications_enabled' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('email_notifications_enabled', false);
        $this->assertFalse($user->fresh()->email_notifications_enabled);
    }

    public function test_user_can_reenable_email_notifications(): void
    {
        $user = User::factory()->create(['email_notifications_enabled' => false]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/settings/notifications', [
            'email_notifications_enabled' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('email_notifications_enabled', true);
        $this->assertTrue($user->fresh()->email_notifications_enabled);
    }

    public function test_reachable_before_onboarding_is_complete(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email_notifications_enabled' => true]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/settings/notifications', [
            'email_notifications_enabled' => false,
        ]);

        $response->assertOk();
    }

    public function test_settings_endpoint_requires_authentication(): void
    {
        $this->patchJson('/api/v1/settings/notifications', ['email_notifications_enabled' => true])->assertUnauthorized();
    }
}
