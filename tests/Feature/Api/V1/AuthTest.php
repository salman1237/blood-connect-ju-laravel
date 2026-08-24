<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
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
