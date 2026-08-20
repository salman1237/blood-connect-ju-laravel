<?php

namespace App\Policies;

use App\Models\RequestResponse;
use App\Models\User;

class RequestResponsePolicy
{
    public function confirm(User $user, RequestResponse $response): bool
    {
        return $user->id === $response->bloodRequest->requester_id
            && $response->status === 'responded';
    }

    /**
     * The post-fulfillment "did this actually happen" prompt — separate from
     * confirm() above, which is the requester picking who their donor was.
     * Both the requester and the chosen donor confirm independently; each
     * can only confirm their own side, once, and only after the request is
     * marked fulfilled.
     */
    public function confirmDonation(User $user, RequestResponse $response): bool
    {
        if ($response->status !== 'confirmed' || $response->bloodRequest->status !== 'fulfilled') {
            return false;
        }

        if ($user->id === $response->bloodRequest->requester_id) {
            return $response->requester_confirmed_at === null;
        }

        if ($user->id === $response->donor_id) {
            return $response->donor_confirmed_at === null;
        }

        return false;
    }
}
