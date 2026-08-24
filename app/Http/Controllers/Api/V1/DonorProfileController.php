<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDonorProfileRequest;
use App\Http\Resources\Api\UserResource;

/**
 * Backs both the onboarding wizard and the profile-edit screen on Android —
 * same split as the web app (OnboardingController::store() and
 * ProfileController::updateDonorProfile() both call the same
 * User::updateDonorProfile() with the same UpdateDonorProfileRequest, so
 * there's one shared endpoint here instead of two that could drift apart).
 */
class DonorProfileController extends Controller
{
    public function update(UpdateDonorProfileRequest $request): UserResource
    {
        $request->user()->updateDonorProfile($request->validated());

        return new UserResource($request->user()->fresh()->load('donorProfile'));
    }
}
