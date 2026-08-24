<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DonorSummaryResource;
use App\Models\BloodRequest;
use App\Models\DonorProfile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Same ranking used to decide who actually got notified about this request
 * (DonorProfile::scopeMatchingRequest) — not a second, differently-sorted
 * view of it. Mirrors BloodRequestController::matchingDonors on the web.
 */
class MatchingDonorsController extends Controller
{
    public function __invoke(BloodRequest $bloodRequest): AnonymousResourceCollection
    {
        $this->authorize('view', $bloodRequest);

        $donors = DonorProfile::query()
            ->matchingRequest($bloodRequest)
            ->where('donor_profiles.is_available', true)
            ->with('user')
            ->get();

        return DonorSummaryResource::collection($donors);
    }
}
