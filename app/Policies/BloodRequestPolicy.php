<?php

namespace App\Policies;

use App\Models\BloodRequest;
use App\Models\User;

/**
 * Requests aren't editable or deletable once posted — only listed, viewed,
 * created, and moved through the status lifecycle by their requester. See
 * routes/web.php: the resource route excludes edit/update/destroy on purpose.
 */
class BloodRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BloodRequest $bloodRequest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function fulfill(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->id === $bloodRequest->requester_id
            && in_array($bloodRequest->status, ['open', 'donor_found'], true);
    }

    /**
     * Can this user click "I can donate" on this request — not their own
     * request, still active, and they're an eligible, compatible donor.
     */
    public function respond(User $user, BloodRequest $bloodRequest): bool
    {
        $profile = $user->donorProfile;

        return $user->id !== $bloodRequest->requester_id
            && in_array($bloodRequest->status, ['open', 'donor_found'], true)
            && $profile !== null
            && $profile->is_eligible
            && in_array($profile->blood_group, $bloodRequest->compatibleDonorBloodGroups(), true)
            && ! $bloodRequest->responses()->where('donor_id', $user->id)->exists();
    }
}
