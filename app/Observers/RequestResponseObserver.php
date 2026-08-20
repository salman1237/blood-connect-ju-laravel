<?php

namespace App\Observers;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Notifications\DonationConfirmationPending;
use App\Notifications\DonationConfirmed;

class RequestResponseObserver
{
    /**
     * Either confirmation timestamp changing means one side just acted.
     * Once both are set, log the donation and bump trust score — this only
     * runs on the update that completes the pair, so it fires exactly once
     * per response with no separate idempotency guard needed. Until then,
     * nudge whichever side hasn't confirmed yet, so the pair doesn't stall
     * on someone forgetting to close the loop.
     */
    public function updated(RequestResponse $response): void
    {
        $justConfirmedOneSide = $response->wasChanged('requester_confirmed_at') || $response->wasChanged('donor_confirmed_at');

        if (! $justConfirmedOneSide) {
            return;
        }

        if (! $response->isMutuallyConfirmed()) {
            $stillWaitingOn = $response->wasChanged('requester_confirmed_at') ? $response->donor : $response->bloodRequest->requester;
            $stillWaitingOn->notify(new DonationConfirmationPending($response));

            return;
        }

        DonationHistory::create([
            'donor_id' => $response->donor_id,
            'request_id' => $response->request_id,
            'confirmed_at' => now(),
        ]);

        DonorProfile::where('user_id', $response->donor_id)->increment('trust_score');

        // Fetched fresh (not via an already-loaded relation) so the mail's
        // trust-score line reflects the increment above, not a stale value.
        $response->donor->notify(new DonationConfirmed($response));
        $response->bloodRequest->requester->notify(new DonationConfirmed($response));
    }
}
