<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_accepts_any_email_domain(): void
    {
        // Explicitly not restricted to a university domain — alumni and
        // others without a juniv.edu address must be able to sign up too.
        $this->post('/register', [
            'name' => 'Alumni User',
            'email' => 'someone@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'someone@gmail.com']);
    }

    public function test_registration_requires_a_valid_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_a_valid_gender(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'unspecified',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertSessionHasErrors('gender');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_a_date_of_birth(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_date_of_birth_is_saved_from_the_registration_form(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-05-20',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('1999-05-20', $user->date_of_birth->toDateString());
    }

    public function test_verification_email_is_queued_not_sent_synchronously(): void
    {
        // Regression: this notification used to be sent inline inside the
        // registration request, so a mail-transport failure (a rejected
        // recipient domain, a transient SMTP hiccup) took the whole signup
        // down with a 500 even though the account had already been
        // created. Queuing it means a mail failure can only ever fail a
        // background job, never the registration response itself.
        Queue::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof QueuedVerifyEmail);
    }

    public function test_role_and_gender_are_saved_from_the_registration_form(): void
    {
        $this->post('/register', [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'staff',
            'gender' => 'female',
            'date_of_birth' => '1990-06-15',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com', 'role' => 'staff', 'gender' => 'female']);
    }
}
