<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorProfileTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'blood_group' => 'O-',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
            'department' => 'Computer Science and Engineering',
            'hall' => 'Rokeya Hall',
            'batch' => '2020-21',
            'phone' => '01712345678',
            'phone_has_whatsapp' => true,
            'is_available' => true,
        ], $overrides);
    }

    public function test_donor_profile_can_be_completed_via_the_api(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/donor-profile', $this->payload());

        $response->assertOk();
        $response->assertJsonPath('has_completed_onboarding', true);
        $response->assertJsonPath('donor_profile.blood_group', 'O-');

        $user->refresh();
        $this->assertSame('Computer Science and Engineering', $user->department);
        $this->assertSame('Rokeya Hall', $user->hall);
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_donor_profile_update_validates_required_fields(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/donor-profile', $this->payload(['blood_group' => null]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('blood_group');
    }

    public function test_staff_do_not_need_a_hall_or_batch(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/donor-profile', $this->payload([
            'role' => 'staff',
            'hall' => null,
            'batch' => null,
        ]));

        $response->assertOk();
        $this->assertTrue($user->fresh()->hasCompletedOnboarding());
    }

    public function test_donor_profile_update_requires_authentication(): void
    {
        $response = $this->patchJson('/api/v1/donor-profile', $this->payload());

        $response->assertUnauthorized();
    }
}
