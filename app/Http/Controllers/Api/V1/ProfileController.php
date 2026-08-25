<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Account-level fields (name/email) — donor-specific fields (blood group,
 * hall, phone, ...) go through DonorProfileController instead, same split
 * as the web app's ProfileController::update() vs ::updateDonorProfile().
 */
class ProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());

        // Same as web: changing the email address means it needs
        // re-verifying — deliberately doesn't re-send the verification
        // email itself here either, matching ProfileController::update().
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->fresh()->load('donorProfile'));
    }

    /** Mirrors web's ProfileController::destroy() — password-confirmed account deletion. */
    public function destroy(Request $request): Response
    {
        $request->validate([
            'password' => ['required', 'current_password:sanctum'],
        ]);

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
