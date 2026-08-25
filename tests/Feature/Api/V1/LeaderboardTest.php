<?php

namespace Tests\Feature\Api\V1;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_leaderboard_ranks_halls_and_departments_by_confirmed_donations(): void
    {
        $viewer = $this->onboardedUser();
        $rokeyaDonor = User::factory()->create(['role' => 'student', 'hall' => 'Rokeya Hall']);
        DonationHistory::factory()->for($rokeyaDonor, 'donor')->count(3)->create();
        $physicsDonor = User::factory()->create(['role' => 'staff', 'department' => 'Physics']);
        DonationHistory::factory()->for($physicsDonor, 'donor')->count(1)->create();

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/leaderboard');

        $response->assertOk();
        $response->assertJsonPath('0.group_name', 'Rokeya Hall');
        $response->assertJsonPath('0.donations', 3);
        $response->assertJsonPath('1.group_name', 'Physics');
        $response->assertJsonPath('1.donations', 1);
    }

    public function test_leaderboard_handles_no_donations_yet(): void
    {
        $viewer = $this->onboardedUser();

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/leaderboard');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_leaderboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/leaderboard');

        $response->assertUnauthorized();
    }
}
