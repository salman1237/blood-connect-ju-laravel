<?php

namespace Tests\Feature;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    use RefreshDatabase;

    private function donor(string $bloodGroup = 'A+'): User
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create(['blood_group' => $bloodGroup]);

        return $user;
    }

    public function test_first_confirmed_donation_awards_the_first_donation_badge(): void
    {
        $donor = $this->donor();

        DonationHistory::factory()->for($donor, 'donor')->create();

        $this->assertTrue($donor->badges()->where('slug', 'first-donation')->exists());
    }

    public function test_five_time_donor_badge_is_not_awarded_before_the_fifth_donation(): void
    {
        $donor = $this->donor();

        DonationHistory::factory()->for($donor, 'donor')->count(4)->create();

        $this->assertFalse($donor->badges()->where('slug', 'five-time-donor')->exists());
    }

    public function test_fifth_confirmed_donation_awards_the_five_time_donor_badge(): void
    {
        $donor = $this->donor();

        DonationHistory::factory()->for($donor, 'donor')->count(5)->create();

        $this->assertTrue($donor->badges()->where('slug', 'five-time-donor')->exists());
    }

    public function test_donating_with_a_rare_blood_type_awards_the_rare_donor_badge(): void
    {
        $donor = $this->donor('O-');

        DonationHistory::factory()->for($donor, 'donor')->create();

        $this->assertTrue($donor->badges()->where('slug', 'rare-blood-type')->exists());
    }

    public function test_donating_with_a_common_blood_type_does_not_award_the_rare_donor_badge(): void
    {
        $donor = $this->donor('O+');

        DonationHistory::factory()->for($donor, 'donor')->create();

        $this->assertFalse($donor->badges()->where('slug', 'rare-blood-type')->exists());
    }

    public function test_a_badge_is_never_awarded_twice(): void
    {
        $donor = $this->donor('O-');

        DonationHistory::factory()->for($donor, 'donor')->create();
        DonationHistory::factory()->for($donor, 'donor')->create();

        $this->assertSame(1, $donor->badges()->where('slug', 'rare-blood-type')->count());
    }
}
