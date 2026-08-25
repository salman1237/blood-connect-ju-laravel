<?php

namespace Tests\Feature\Api\V1;

use App\Models\Badge;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_donor_directory_lists_eligible_donors_only(): void
    {
        $viewer = $this->onboardedUser();
        $eligible = $this->onboardedUser();
        $ineligible = User::factory()->create(['role' => 'staff', 'department' => 'Physics']);
        DonorProfile::factory()->for($ineligible)->create(['last_donation_date' => now()->subDays(10)]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/donors');

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($eligible->id));
        $this->assertFalse($ids->contains($ineligible->id));
    }

    public function test_donor_directory_filters_by_blood_group(): void
    {
        $viewer = $this->onboardedUser();
        $oNeg = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($oNeg)->create(['blood_group' => 'O-']);
        $abPlus = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($abPlus)->create(['blood_group' => 'AB+']);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/donors?blood_group=O-');

        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($oNeg->id));
        $this->assertFalse($ids->contains($abPlus->id));
    }

    public function test_donor_directory_filters_by_hall(): void
    {
        $viewer = $this->onboardedUser();
        $inHall = User::factory()->create(['role' => 'student', 'hall' => 'Rokeya Hall']);
        DonorProfile::factory()->for($inHall)->create();
        $elsewhere = User::factory()->create(['role' => 'student', 'hall' => 'Al Beruni Hall']);
        DonorProfile::factory()->for($elsewhere)->create();

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/donors?hall='.urlencode('Rokeya Hall'));

        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($inHall->id));
        $this->assertFalse($ids->contains($elsewhere->id));
    }

    public function test_donor_directory_searches_by_name(): void
    {
        $viewer = $this->onboardedUser();
        $match = User::factory()->create(['name' => 'Rahim Uddin', 'role' => 'staff']);
        DonorProfile::factory()->for($match)->create();
        $other = User::factory()->create(['name' => 'Karim Ahmed', 'role' => 'staff']);
        DonorProfile::factory()->for($other)->create();

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/donors?search=Rahim');

        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($match->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_donor_detail_returns_full_profile(): void
    {
        $viewer = $this->onboardedUser();
        $donor = $this->onboardedUser([
            'name' => 'Rahim Uddin',
            'role' => 'student',
            'gender' => 'male',
            'hall' => 'Al Beruni Hall',
            'department' => 'Computer Science and Engineering',
            'batch' => '2020-21',
            'phone' => '01712345678',
            'phone_has_whatsapp' => true,
        ]);
        $badge = Badge::where('slug', 'rare-blood-type')->first();
        $donor->badges()->attach($badge->id, ['earned_at' => now()]);
        // Also awards "first-donation" automatically via DonationHistoryObserver
        // — the badges list below is asserted by content, not count, because
        // of that real side effect.
        DonationHistory::factory()->for($donor, 'donor')->create();

        $response = $this->actingAs($viewer, 'sanctum')->getJson("/api/v1/donors/{$donor->id}");

        $response->assertOk();
        $response->assertJsonPath('name', 'Rahim Uddin');
        $response->assertJsonPath('hall', 'Al Beruni Hall');
        $response->assertJsonPath('department', 'Computer Science and Engineering');
        $response->assertJsonPath('batch', '2020-21');
        $response->assertJsonPath('phone', '01712345678');
        $response->assertJsonPath('whatsapp_url', 'https://wa.me/8801712345678');
        $response->assertJsonPath('donor_profile.blood_group', $donor->donorProfile->blood_group);
        $this->assertTrue(collect($response->json('badges'))->pluck('name')->contains($badge->name));
        $response->assertJsonCount(1, 'donation_history');

        // Deliberately not exposed — mirrors web's donors/show.blade.php,
        // which never renders email either.
        $this->assertArrayNotHasKey('email', $response->json());
    }

    public function test_donor_detail_404s_for_a_user_without_a_donor_profile(): void
    {
        $viewer = $this->onboardedUser();
        $notADonor = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($viewer, 'sanctum')->getJson("/api/v1/donors/{$notADonor->id}");

        $response->assertNotFound();
    }

    public function test_donor_directory_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/donors');

        $response->assertUnauthorized();
    }
}
