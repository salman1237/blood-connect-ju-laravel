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
}
