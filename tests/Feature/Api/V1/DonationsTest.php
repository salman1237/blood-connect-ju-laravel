<?php

namespace Tests\Feature\Api\V1;

use App\Models\Badge;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationsTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_donations_endpoint_returns_the_current_users_own_history_and_badges(): void
    {
        $user = $this->onboardedUser();
        $other = $this->onboardedUser();

        // Attach manually before creating any donation history: the
        // factory's random blood_group can itself land on a rare group, and
        // DonationHistoryObserver would then try to award this exact badge
        // too — attaching first means its own "already has it?" check
        // no-ops instead of colliding, same order DonorTest's equivalent
        // case uses for the same reason.
        $badge = Badge::where('slug', 'rare-blood-type')->first();
        $user->badges()->attach($badge->id, ['earned_at' => now()]);

        // Also auto-awards "first-donation" via DonationHistoryObserver —
        // asserted by content below, not count, same reasoning as
        // DonorTest's equivalent case.
        DonationHistory::factory()->for($user, 'donor')->create();
        DonationHistory::factory()->for($other, 'donor')->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/donations');

        $response->assertOk();
        $response->assertJsonCount(1, 'donation_history');
        $this->assertTrue(collect($response->json('badges'))->pluck('name')->contains($badge->name));
    }

    public function test_donations_endpoint_handles_no_donations_or_badges_yet(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/donations');

        $response->assertOk();
        $response->assertJsonCount(0, 'donation_history');
        $response->assertJsonCount(0, 'badges');
    }

    public function test_donations_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/donations')->assertUnauthorized();
    }
}
