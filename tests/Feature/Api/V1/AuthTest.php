<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'mobile@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['token', 'user' => ['id', 'email']]);
        $this->assertDatabaseHas('users', ['email' => 'mobile@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registering_still_succeeds_even_if_sending_the_verification_email_would_fail(): void
    {
        // Regression: found live in production — a rejected recipient
        // domain during the (previously synchronous) verification email
        // send took the whole registration request down with a 500, even
        // though the account row had already been committed. The
        // notification is queued now, so a mail failure can only ever
        // fail later in a background job, never this response.
        Queue::fake();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'definitely-not-a-real-mailbox@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated();
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof QueuedVerifyEmail);
    }

    public function test_registration_validates_the_same_rules_as_the_web_form(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password', 'role', 'gender', 'date_of_birth']);
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_login_fails_for_a_deactivated_account(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password'), 'is_active' => false]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_user_can_fetch_their_own_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Pixel 8')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user');

        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
        $response->assertJsonPath('email', $user->email);
    }

    public function test_a_token_issued_before_deactivation_stops_working_once_deactivated(): void
    {
        // Regression: login already refuses a newly-deactivated account,
        // but EnsureAccountIsActive was only wired into the *web*
        // middleware group — a token issued earlier would otherwise keep
        // working via the API indefinitely.
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('Pixel 8')->plainTextToken;

        $user->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/user');

        $response->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_request_to_user_endpoint_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertUnauthorized();
    }

    private function fakeGoogleVerifier(?array $payload): void
    {
        $this->mock(GoogleIdTokenVerifier::class, function ($mock) use ($payload) {
            $mock->shouldReceive('verify')->andReturn($payload ?? false);
        });
    }

    public function test_new_user_is_created_and_receives_a_token_via_google(): void
    {
        $this->fakeGoogleVerifier([
            'sub' => 'google-123',
            'email' => 'newdonor@gmail.com',
            'name' => 'New Donor',
            'picture' => 'https://lh3.googleusercontent.com/fake-avatar.jpg',
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'fake-token',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'email']]);
        $this->assertDatabaseHas('users', ['email' => 'newdonor@gmail.com', 'google_id' => 'google-123']);
    }

    public function test_existing_google_user_logs_in_without_duplicating_via_the_api(): void
    {
        $existing = User::factory()->create(['email' => 'returning@gmail.com', 'google_id' => 'google-789']);

        $this->fakeGoogleVerifier([
            'sub' => 'google-789',
            'email' => 'returning@gmail.com',
            'name' => 'Returning User',
            'picture' => null,
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'fake-token',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.id', $existing->id);
        $this->assertSame(1, User::where('email', 'returning@gmail.com')->count());
    }

    public function test_existing_email_account_is_linked_to_google_via_the_api(): void
    {
        $existing = User::factory()->create(['email' => 'linkme@example.com', 'google_id' => null]);

        $this->fakeGoogleVerifier([
            'sub' => 'google-999',
            'email' => 'linkme@example.com',
            'name' => 'Link Me',
            'picture' => null,
        ]);

        $this->postJson('/api/v1/login/google', ['id_token' => 'fake-token', 'device_name' => 'Pixel 8'])->assertOk();

        $this->assertSame('google-999', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'linkme@example.com')->count());
    }

    public function test_an_invalid_google_token_is_rejected(): void
    {
        $this->fakeGoogleVerifier(null);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'garbage',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('id_token');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_sign_in_fails_for_a_deactivated_account(): void
    {
        User::factory()->create(['email' => 'deactivated@gmail.com', 'google_id' => 'google-555', 'is_active' => false]);

        $this->fakeGoogleVerifier([
            'sub' => 'google-555',
            'email' => 'deactivated@gmail.com',
            'name' => 'Deactivated User',
            'picture' => null,
        ]);

        $response = $this->postJson('/api/v1/login/google', [
            'id_token' => 'fake-token',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $tokenA = $user->createToken('Phone A')->plainTextToken;
        $tokenB = $user->createToken('Phone B')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson('/api/v1/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // The other device's token still works.
        $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->getJson('/api/v1/user')
            ->assertOk();
    }
}
