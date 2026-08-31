<?php

namespace App\Observers;

use App\Events\RequestActivityUpdated;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Notifications\DonationConfirmationPending;
use App\Notifications\DonationConfirmed;

class RequestResponseObserver
{
    /**
     * A new response — refresh anyone with this request's detail page open
     * so it shows up without a manual reload. The "someone responded"
     * notification to the requester is dispatched separately, from the
     * controller that creates this row (RequestResponseController/its API
     * twin), not here — this Observer only covers what's true purely from
     * the row's own existence/changes.
     */
    public function created(RequestResponse $response): void
    {
        RequestActivityUpdated::dispatch($response->request_id);
    }

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
        // Broader than the notification logic below — a donor being
        // selected (status -> 'confirmed', via
        // RequestResponseController::confirm()) is activity-worthy for
        // anyone watching this request's detail page even though it isn't
        // one of the two confirmation timestamps the notification/
        // donation-history logic below cares about.
        if ($response->wasChanged(['status', 'requester_confirmed_at', 'donor_confirmed_at'])) {
            RequestActivityUpdated::dispatch($response->request_id);
        }

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
