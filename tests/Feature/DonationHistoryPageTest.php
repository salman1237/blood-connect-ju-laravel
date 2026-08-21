<?php

namespace Tests\Feature;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_donation_history_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/donations');

        $response->assertOk();
    }

    public function test_donation_history_page_shows_confirmed_donations(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create();
        DonationHistory::factory()->for($user, 'donor')->create(['confirmed_at' => now()]);

        $response = $this->actingAs($user)->get('/donations');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('donationHistory'));
    }

    public function test_donation_history_page_shows_earned_badges(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create(['blood_group' => 'O-']);
        DonationHistory::factory()->for($user, 'donor')->create();

        $response = $this->actingAs($user)->get('/donations');

        $response->assertOk();
        $response->assertSee('Rare Donor');
    }

    public function test_donation_history_page_shows_an_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/donations');

        $response->assertOk();
        $response->assertSee('No confirmed donations yet');
    }
}
