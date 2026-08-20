<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorSearchTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_donors_page_lists_eligible_donors(): void
    {
        $viewer = $this->onboardedUser();
        $eligible = $this->onboardedUser();
        $ineligible = User::factory()->create(['role' => 'staff', 'department' => 'Physics']);
        DonorProfile::factory()->for($ineligible)->create(['last_donation_date' => now()->subDays(10)]);

        $response = $this->actingAs($viewer)->get('/donors');

        $response->assertOk();
        $ids = $response->viewData('donors')->pluck('user_id');
        $this->assertTrue($ids->contains($eligible->id));
        $this->assertFalse($ids->contains($ineligible->id));
    }

    public function test_donors_page_filters_by_blood_group(): void
    {
        $viewer = $this->onboardedUser();
        $oNeg = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($oNeg)->create(['blood_group' => 'O-']);
        $abPlus = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($abPlus)->create(['blood_group' => 'AB+']);

        $response = $this->actingAs($viewer)->get('/donors?blood_group=O-');

        $donors = $response->viewData('donors');
        $this->assertTrue($donors->pluck('user_id')->contains($oNeg->id));
        $this->assertFalse($donors->pluck('user_id')->contains($abPlus->id));
    }

    public function test_donors_page_filters_by_hall(): void
    {
        $viewer = $this->onboardedUser();
        $inHall = User::factory()->create(['role' => 'student', 'hall' => 'Rokeya Hall']);
        DonorProfile::factory()->for($inHall)->create();
        $elsewhere = User::factory()->create(['role' => 'student', 'hall' => 'Al Beruni Hall']);
        DonorProfile::factory()->for($elsewhere)->create();

        $response = $this->actingAs($viewer)->get('/donors?hall=Rokeya+Hall');

        $donors = $response->viewData('donors');
        $this->assertTrue($donors->pluck('user_id')->contains($inHall->id));
        $this->assertFalse($donors->pluck('user_id')->contains($elsewhere->id));
    }
}
