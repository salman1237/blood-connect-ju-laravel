<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_requires_gender(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'gender' => null]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'is_available' => '1',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_onboarding_requires_a_date_of_birth(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'date_of_birth' => null]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'department' => 'Physics',
            'is_available' => '1',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_onboarding_requires_batch_for_students_but_not_staff(): void
    {
        $student = User::factory()->create(['role' => 'student', 'batch' => null]);

        $response = $this->actingAs($student)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'hall' => 'Al Beruni Hall',
            'department' => 'Physics',
            'is_available' => '1',
        ]);
        $response->assertSessionHasErrors('batch');

        $staff = User::factory()->create(['role' => 'staff', 'batch' => null]);

        $response = $this->actingAs($staff)->post('/onboarding', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'is_available' => '1',
        ]);
        $response->assertSessionDoesntHaveErrors('batch');
    }

    public function test_unchecking_whatsapp_stores_the_alternate_number(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create();

        $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'phone' => '01711111111',
            'phone_has_whatsapp' => '0',
            'whatsapp_number' => '01799999999',
            'is_available' => '1',
        ]);

        $user->refresh();
        $this->assertFalse($user->phone_has_whatsapp);
        $this->assertSame('01799999999', $user->whatsapp_number);
    }

    public function test_checking_whatsapp_clears_any_stored_alternate_number(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'phone_has_whatsapp' => false,
            'whatsapp_number' => '01799999999',
        ]);
        DonorProfile::factory()->for($user)->create();

        $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'phone' => '01711111111',
            'phone_has_whatsapp' => '1',
            'is_available' => '1',
        ]);

        $user->refresh();
        $this->assertTrue($user->phone_has_whatsapp);
        $this->assertNull($user->whatsapp_number);
    }

    public function test_self_service_user_can_change_their_own_role(): void
    {
        $user = User::factory()->create(['role' => 'student', 'hall' => 'Al Beruni Hall']);
        DonorProfile::factory()->for($user)->create();

        $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'O-',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'is_available' => '1',
        ]);

        $this->assertSame('staff', $user->fresh()->role);
    }

    public function test_verifier_role_field_is_not_rendered_and_role_is_never_changed_via_this_form(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier', 'department' => 'Physics']);
        DonorProfile::factory()->for($verifier)->create();

        $response = $this->actingAs($verifier)->get('/profile');
        $response->assertDontSee('name="role"', false);

        $this->actingAs($verifier)->patch('/profile/donor', [
            'blood_group' => 'O-',
            'role' => 'admin',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'department' => 'Physics',
            'is_available' => '1',
        ]);

        $this->assertSame('verifier', $verifier->fresh()->role);
    }

    public function test_edit_profile_can_update_blood_group_hall_department_and_batch(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'hall' => 'Al Beruni Hall',
            'department' => 'Physics',
            'batch' => '2018-19',
        ]);
        DonorProfile::factory()->for($user)->create(['blood_group' => 'O-']);

        $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'AB-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '1999-01-01',
            'hall' => 'Rokeya Hall',
            'batch' => '2021-22',
            'department' => 'Mathematics',
            'is_available' => '1',
        ]);

        $user->refresh();
        $this->assertSame('AB-', $user->donorProfile->blood_group);
        $this->assertSame('Rokeya Hall', $user->hall);
        $this->assertSame('Mathematics', $user->department);
        $this->assertSame('2021-22', $user->batch);
    }
}
