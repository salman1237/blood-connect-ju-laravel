<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateDonorProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'halls' => config('juniv.halls'),
            'departments' => config('juniv.departments'),
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'batches' => User::batchOptions(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's donor-specific info (blood group, hall/department,
     * phone, availability) — the same fields onboarding collects, editable
     * afterward here.
     */
    public function updateDonorProfile(UpdateDonorProfileRequest $request): RedirectResponse
    {
        $request->user()->updateDonorProfile($request->validated());

        return Redirect::route('profile.edit')->with('status', 'donor-profile-updated');
    }

    /**
     * Upload (or replace) the user's profile photo — the initials avatar,
     * or whatever was imported from Google, stays the fallback until this
     * runs. Any previously locally-stored photo is cleaned up so replaced
     * uploads don't pile up in storage; a Google-hosted URL is left alone.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $request->user()->updateAvatar($request->file('photo'));

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    /**
     * Remove the user's photo, reverting to the initials avatar.
     */
    public function destroyPhoto(Request $request): RedirectResponse
    {
        $request->user()->removeAvatar();

        return Redirect::route('profile.edit')->with('status', 'photo-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
