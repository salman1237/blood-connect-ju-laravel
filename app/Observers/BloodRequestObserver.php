<?php

namespace App\Observers;

use App\Events\RequestActivityUpdated;
use App\Events\RequestFeedUpdated;
use App\Jobs\NotifyMatchingDonors;
use App\Models\BloodRequest;

class BloodRequestObserver
{
    public function created(BloodRequest $bloodRequest): void
    {
        NotifyMatchingDonors::dispatch($bloodRequest);

        RequestFeedUpdated::dispatch($bloodRequest->id, 'created');
    }

    /**
     * Only status/is_verified changes are feed-worthy — guards against
     * broadcasting on every unrelated save (e.g. a future column added to
     * this model that nothing here needs to react to).
     */
    public function updated(BloodRequest $bloodRequest): void
    {
        if (! $bloodRequest->wasChanged(['status', 'is_verified'])) {
            return;
        }

        RequestFeedUpdated::dispatch($bloodRequest->id, 'updated');

        // Also refresh anyone with this specific request's own detail page
        // open — a status/verification change is exactly the kind of thing
        // that page shows.
        RequestActivityUpdated::dispatch($bloodRequest->id);
    }
}
