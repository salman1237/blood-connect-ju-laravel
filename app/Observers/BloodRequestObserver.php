<?php

namespace App\Observers;

use App\Jobs\NotifyMatchingDonors;
use App\Models\BloodRequest;

class BloodRequestObserver
{
    public function created(BloodRequest $bloodRequest): void
    {
        NotifyMatchingDonors::dispatch($bloodRequest);
    }
}
