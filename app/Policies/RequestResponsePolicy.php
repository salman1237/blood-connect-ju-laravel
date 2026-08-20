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
}
