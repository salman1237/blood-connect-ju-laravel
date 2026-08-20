<?php

namespace App\Jobs;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Notifications\NewMatchingRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Runs off the request-creation request cycle — the matching query itself,
 * not just notification delivery, is deferred to the queue.
 */
class NotifyMatchingDonors implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

    public function handle(): void
    {
        $donors = DonorProfile::query()
            ->matchingRequest($this->bloodRequest)
            ->where('donor_profiles.is_available', true)
            ->with('user')
            ->get()
            ->pluck('user');

        if ($donors->isNotEmpty()) {
            Notification::send($donors, new NewMatchingRequest($this->bloodRequest));
        }
    }
}
