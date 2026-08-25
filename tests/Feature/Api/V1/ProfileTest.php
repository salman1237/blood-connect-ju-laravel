<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_name_and_email_can_be_updated(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@juniv.edu']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'name' => 'New Name',
            'email' => 'new@juniv.edu',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');
        $response->assertJsonPath('email', 'new@juniv.edu');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@juniv.edu']);
    }

    public function test_changing_email_resets_verification_status(): void
    {
        $user = User::factory()->create(['email' => 'old@juniv.edu']);
        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'name' => $user->name,
            'email' => 'changed@juniv.edu',
        ]);

        $response->assertOk();
        $response->assertJsonPath('email_verified', false);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_keeping_the_same_email_does_not_reset_verification_status(): void
    {
        $user = User::factory()->create(['email' => 'same@juniv.edu']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'name' => 'Updated Name',
            'email' => 'same@juniv.edu',
        ]);

        $response->assertOk();
        $response->assertJsonPath('email_verified', true);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@juniv.edu']);
        $user = User::factory()->create(['email' => 'mine@juniv.edu']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'name' => $user->name,
            'email' => 'taken@juniv.edu',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_account_can_be_deleted_with_the_correct_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile', ['password' => 'password']);

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_account_deletion_requires_the_correct_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile', ['password' => 'wrong-password']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_the_account_revokes_its_tokens(): void
    {
        // A single HTTP call per test, same as AuthTest's equivalent
        // token-revocation check — Sanctum's request guard caches the
        // resolved user for the lifetime of the test's shared app
        // container, so a second call reusing the same token within one
        // test method would misleadingly still "authenticate" against
        // that cached resolution even though the token row is gone. The
        // real guarantee (the row no longer exists) is asserted directly.
        $user = User::factory()->create();
        $user->createToken('device one');
        $user->createToken('device two');
        $this->assertDatabaseCount('personal_access_tokens', 2);

        $token = $user->createToken('device three')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile', ['password' => 'password'])
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_profile_endpoints_require_authentication(): void
    {
        $this->patchJson('/api/v1/profile', ['name' => 'x', 'email' => 'x@juniv.edu'])->assertUnauthorized();
        $this->deleteJson('/api/v1/profile', ['password' => 'password'])->assertUnauthorized();
    }
}
