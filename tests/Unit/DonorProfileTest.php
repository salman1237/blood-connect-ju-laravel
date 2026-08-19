<?php

namespace Tests\Unit;

use App\Models\DonorProfile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DonorProfileTest extends TestCase
{
    public function test_eligible_when_never_donated(): void
    {
        $profile = new DonorProfile(['last_donation_date' => null]);

        $this->assertTrue($profile->is_eligible);
    }

    public function test_not_eligible_within_120_days(): void
    {
        $profile = new DonorProfile(['last_donation_date' => Carbon::now()->subDays(30)]);

        $this->assertFalse($profile->is_eligible);
    }

    public function test_not_eligible_at_exactly_120_days(): void
    {
        $profile = new DonorProfile(['last_donation_date' => Carbon::now()->subDays(120)]);

        $this->assertFalse($profile->is_eligible);
    }

    public function test_eligible_just_past_120_days(): void
    {
        $profile = new DonorProfile(['last_donation_date' => Carbon::now()->subDays(121)]);

        $this->assertTrue($profile->is_eligible);
    }

    public function test_next_eligible_date_is_null_when_never_donated(): void
    {
        $profile = new DonorProfile(['last_donation_date' => null]);

        $this->assertNull($profile->next_eligible_date);
    }

    public function test_next_eligible_date_is_120_days_after_last_donation(): void
    {
        $lastDonation = Carbon::parse('2026-01-01');
        $profile = new DonorProfile(['last_donation_date' => $lastDonation]);

        $this->assertTrue($profile->next_eligible_date->isSameDay($lastDonation->copy()->addDays(120)));
    }
}
