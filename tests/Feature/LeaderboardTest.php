<?php

namespace Tests\Feature;

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

        $response = $this->actingAs($viewer)->get('/leaderboard');

        $response->assertOk();
        $rankings = $response->viewData('rankings');
        $this->assertSame('Rokeya Hall', $rankings->first()->group_name);
        $this->assertSame(3, $rankings->first()->donations);
    }

    public function test_leaderboard_handles_no_donations_yet(): void
    {
        $viewer = $this->onboardedUser();

        $response = $this->actingAs($viewer)->get('/leaderboard');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('rankings'));
    }
}
