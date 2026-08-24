<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
