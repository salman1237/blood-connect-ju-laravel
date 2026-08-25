<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertStringContainsString('avatars/', $response->json('avatar_url'));
        $this->assertStringContainsString('avatars/', $user->fresh()->avatar_url);
    }

    public function test_non_image_photo_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/profile/photo', [
            'photo' => UploadedFile::fake()->create('resume.pdf', 100),
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('photo');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_uploading_a_new_photo_deletes_the_previous_locally_stored_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->post('/api/v1/profile/photo', [
            'photo' => UploadedFile::fake()->image('first.jpg'),
        ], ['Accept' => 'application/json']);
        $firstPath = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user, 'sanctum')->post('/api/v1/profile/photo', [
            'photo' => UploadedFile::fake()->image('second.jpg'),
        ], ['Accept' => 'application/json']);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_user_can_remove_their_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->post('/api/v1/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ], ['Accept' => 'application/json']);
        $path = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/profile/photo');

        $response->assertOk();
        $this->assertNull($response->json('avatar_url'));
        $this->assertNull($user->fresh()->avatar_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_removing_a_google_imported_photo_just_clears_the_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_url' => 'https://lh3.googleusercontent.com/photo.jpg']);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/profile/photo')->assertOk();

        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_photo_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/profile/photo', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/profile/photo')->assertUnauthorized();
    }
}
