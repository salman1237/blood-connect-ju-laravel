<?php

namespace App\Observers;

use App\Models\Badge;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;

class DonationHistoryObserver
{
    public function created(DonationHistory $donationHistory): void
    {
        $donor = $donationHistory->donor;
        $totalDonations = DonationHistory::where('donor_id', $donor->id)->count();

        if ($totalDonations === 1) {
            $this->award($donor, 'first-donation');
        }

        if ($totalDonations === 5) {
            $this->award($donor, 'five-time-donor');
        }

        if (in_array($donor->donorProfile?->blood_group, DonorProfile::RARE_BLOOD_GROUPS, true)) {
            $this->award($donor, 'rare-blood-type');
        }
    }

    private function award(User $donor, string $slug): void
    {
        $badge = Badge::where('slug', $slug)->first();

        if ($badge && ! $donor->badges()->where('badge_id', $badge->id)->exists()) {
            $donor->badges()->attach($badge->id, ['earned_at' => now()]);
        }
    }
}
