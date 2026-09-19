<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_profile_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('onboarding.show'));
    }

    public function test_complete_profile_can_reach_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'hall' => 'Al Beruni Hall',
            'department' => 'Computer Science and Engineering',
        ]);
        DonorProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * A user whose onboarding submission keeps failing (or who just wants to
     * switch accounts) must still be able to sign out from this page — it's
     * the only screen an incomplete profile can reach, so this is the only
     * place they'd ever see a sign-out control.
     */
    public function test_can_sign_out_from_the_onboarding_page(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertOk();
        $response->assertSee(route('logout'), false);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_student_must_provide_a_hall(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'department' => 'Computer Science and Engineering',
            'is_available' => '1',
        ]);

        $response->assertSessionHasErrors('hall');
    }

    public function test_staff_does_not_need_a_hall(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'department' => 'Computer Science and Engineering',
            'is_available' => '1',
        ]);

        $response->assertSessionDoesntHaveErrors('hall');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_onboarding_store_creates_donor_profile_and_updates_user(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'AB+',
            'role' => 'student',
            'gender' => 'female',
            'date_of_birth' => '2000-03-10',
            'hall' => 'Rokeya Hall',
            'batch' => '2020-21',
            'department' => 'Statistics and Data Science',
            'phone' => '01712345678',
            'is_available' => '1',
        ]);

        $user->refresh();
        $this->assertSame('Rokeya Hall', $user->hall);
        $this->assertSame('Statistics and Data Science', $user->department);
        $this->assertSame('01712345678', $user->phone);
        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $user->id,
            'blood_group' => 'AB+',
            'is_available' => true,
        ]);
    }

    public function test_accepts_an_institute_as_a_department(): void
    {
        // Departments live under faculties, but JU's institutes (IBA, IIT, etc.)
        // are a separate top-level category — both must be valid choices here.
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'hall' => 'Rokeya Hall',
            'batch' => '2020-21',
            'department' => 'Institute of Business Administration',
            'is_available' => '1',
        ]);

        $response->assertSessionDoesntHaveErrors('department');
    }

    public function test_rejects_a_hall_or_department_not_in_the_official_list(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'hall' => 'Not A Real Hall',
            'batch' => '2020-21',
            'department' => 'Not A Real Department',
            'is_available' => '1',
        ]);

        $response->assertSessionHasErrors(['hall', 'department']);
    }
}
