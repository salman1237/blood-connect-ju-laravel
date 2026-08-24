<?php

namespace Tests\Feature\Api\V1;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_device_token_can_be_registered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/push-tokens', [
            'token' => 'device-token-abc',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $user->id,
            'token' => 'device-token-abc',
            'device_name' => 'Pixel 8',
        ]);
    }

    public function test_registering_before_onboarding_is_allowed(): void
    {
        $user = User::factory()->create(['role' => 'student', 'department' => null]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/push-tokens', [
            'token' => 'device-token-abc',
        ]);

        $response->assertCreated();
    }

    public function test_registering_an_existing_token_reassigns_it_to_the_new_user(): void
    {
        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        PushToken::factory()->for($oldOwner)->create(['token' => 'shared-device']);

        $response = $this->actingAs($newOwner, 'sanctum')->postJson('/api/v1/push-tokens', [
            'token' => 'shared-device',
        ]);

        $response->assertCreated();
        $this->assertSame(1, PushToken::where('token', 'shared-device')->count());
        $this->assertDatabaseHas('push_tokens', ['token' => 'shared-device', 'user_id' => $newOwner->id]);
    }

    public function test_a_device_token_can_be_unregistered(): void
    {
        $user = User::factory()->create();
        PushToken::factory()->for($user)->create(['token' => 'device-token-abc']);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/push-tokens', [
            'token' => 'device-token-abc',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('push_tokens', ['token' => 'device-token-abc']);
    }

    public function test_unregistering_does_not_affect_another_users_token(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        PushToken::factory()->for($owner)->create(['token' => 'device-token-abc']);

        $response = $this->actingAs($other, 'sanctum')->deleteJson('/api/v1/push-tokens', [
            'token' => 'device-token-abc',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('push_tokens', ['token' => 'device-token-abc', 'user_id' => $owner->id]);
    }

    public function test_registering_a_token_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/push-tokens', ['token' => 'device-token-abc']);

        $response->assertUnauthorized();
    }
}
