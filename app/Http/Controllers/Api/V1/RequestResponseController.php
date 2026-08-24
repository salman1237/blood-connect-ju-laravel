<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BloodRequestResource;
use App\Models\BloodRequest;
use App\Models\RequestResponse;
use App\Notifications\DonorSelected;
use App\Notifications\RequestResponded;

/**
 * Same three-step flow as the web app's RequestResponseController: a donor
 * responds, the requester confirms which donor, then each side
 * independently confirms the donation actually happened (handled by
 * RequestResponseObserver, unchanged — this controller doesn't touch that
 * part directly, same as web).
 */
class RequestResponseController extends Controller
{
    public function store(BloodRequest $bloodRequest): BloodRequestResource
    {
        $this->authorize('respond', $bloodRequest);

        $response = $bloodRequest->responses()->create([
            'donor_id' => auth()->id(),
            'status' => 'responded',
        ]);

        $bloodRequest->requester->notify(new RequestResponded($response));

        return new BloodRequestResource($bloodRequest->fresh()->load(['requester', 'responses.donor']));
    }

    public function confirm(BloodRequest $bloodRequest, RequestResponse $response): BloodRequestResource
    {
        $this->authorize('confirm', $response);

        $response->update(['status' => 'confirmed']);

        $response->donor->notify(new DonorSelected($response));

        return new BloodRequestResource($bloodRequest->fresh()->load(['requester', 'responses.donor']));
    }

    public function confirmDonation(BloodRequest $bloodRequest, RequestResponse $response): BloodRequestResource
    {
        $this->authorize('confirmDonation', $response);

        $field = auth()->id() === $bloodRequest->requester_id ? 'requester_confirmed_at' : 'donor_confirmed_at';
        $response->update([$field => now()]);

        return new BloodRequestResource($bloodRequest->fresh()->load(['requester', 'responses.donor']));
    }
}
