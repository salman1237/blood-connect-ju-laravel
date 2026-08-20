<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email, string $name, ?string $avatar = 'https://lh3.googleusercontent.com/fake-avatar.jpg'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn($avatar);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    public function test_new_user_is_created_and_logged_in_via_google(): void
    {
        $this->fakeGoogleUser('google-123', 'newdonor@gmail.com', 'New Donor');

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'newdonor@gmail.com',
            'google_id' => 'google-123',
        ]);
    }

    public function test_google_signup_skips_email_verification(): void
    {
        $this->fakeGoogleUser('google-456', 'verified@gmail.com', 'Verified User');

        $this->get('/auth/google/callback');

        $user = User::where('email', 'verified@gmail.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_google_user_logs_in_without_duplicating(): void
    {
        $existing = User::factory()->create([
            'email' => 'returning@gmail.com',
            'google_id' => 'google-789',
        ]);

        $this->fakeGoogleUser('google-789', 'returning@gmail.com', 'Returning User');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('email', 'returning@gmail.com')->count());
    }

    public function test_existing_email_account_is_linked_to_google_instead_of_duplicated(): void
    {
        $existing = User::factory()->create([
            'email' => 'linkme@example.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser('google-999', 'linkme@example.com', 'Link Me');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('email', 'linkme@example.com')->count());
        $this->assertSame('google-999', $existing->fresh()->google_id);
    }

    public function test_new_google_user_is_redirected_to_onboarding_on_next_request(): void
    {
        $this->fakeGoogleUser('google-111', 'onboardme@gmail.com', 'Onboard Me');

        $this->get('/auth/google/callback');

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('onboarding.show'));
    }

    public function test_new_google_user_imports_their_google_avatar(): void
    {
        $this->fakeGoogleUser('google-222', 'avatar@gmail.com', 'Avatar User', 'https://lh3.googleusercontent.com/photo.jpg');

        $this->get('/auth/google/callback');

        $user = User::where('email', 'avatar@gmail.com')->firstOrFail();
        $this->assertSame('https://lh3.googleusercontent.com/photo.jpg', $user->avatar_url);
    }

    public function test_linking_google_to_an_existing_account_without_a_photo_imports_the_avatar(): void
    {
        $existing = User::factory()->create([
            'email' => 'linkphoto@example.com',
            'google_id' => null,
            'avatar_url' => null,
        ]);

        $this->fakeGoogleUser('google-333', 'linkphoto@example.com', 'Link Photo', 'https://lh3.googleusercontent.com/linked.jpg');

        $this->get('/auth/google/callback');

        $this->assertSame('https://lh3.googleusercontent.com/linked.jpg', $existing->fresh()->avatar_url);
    }

    public function test_linking_google_never_overwrites_an_existing_photo(): void
    {
        $existing = User::factory()->create([
            'email' => 'keepphoto@example.com',
            'google_id' => null,
            'avatar_url' => 'https://blood.deshlet.com/storage/avatars/self-uploaded.jpg',
        ]);

        $this->fakeGoogleUser('google-444', 'keepphoto@example.com', 'Keep Photo', 'https://lh3.googleusercontent.com/google.jpg');

        $this->get('/auth/google/callback');

        $this->assertSame('https://blood.deshlet.com/storage/avatars/self-uploaded.jpg', $existing->fresh()->avatar_url);
    }
}
