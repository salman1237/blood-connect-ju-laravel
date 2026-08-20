<?php

namespace App\Observers;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\RequestResponse;

class RequestResponseObserver
{
    /**
     * The moment BOTH sides have confirmed a donation happened, log it and
     * bump the donor's trust score. Only the update that completes the pair
     * has both timestamps set and one of them freshly changed, so this
     * fires exactly once per response — no separate idempotency guard needed.
     */
    public function updated(RequestResponse $response): void
    {
        $justCompletedPair = ($response->wasChanged('requester_confirmed_at') || $response->wasChanged('donor_confirmed_at'))
            && $response->isMutuallyConfirmed();

        if (! $justCompletedPair) {
            return;
        }

        DonationHistory::create([
            'donor_id' => $response->donor_id,
            'request_id' => $response->request_id,
            'confirmed_at' => now(),
        ]);

        DonorProfile::where('user_id', $response->donor_id)->increment('trust_score');
    }
}
