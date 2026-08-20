<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);

        $response->assertSessionHasErrors('gender');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
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
        ]);

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com', 'role' => 'staff', 'gender' => 'female']);
    }
}
