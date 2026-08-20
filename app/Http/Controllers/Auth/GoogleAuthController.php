<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            } else {
                // Google has already verified this email address, so there's no
                // separate OTP/verification step for accounts created this way.
                // forceCreate (not create) because email_verified_at is
                // deliberately excluded from $fillable everywhere else.
                $user = User::forceCreate([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'email_verified_at' => now(),
                    // Set explicitly, not left to the migration's DB default —
                    // forceCreate() doesn't refresh the in-memory model, so the
                    // is_active check just below would otherwise see null.
                    'is_active' => true,
                ]);
            }
        }

        if (! $user->is_active) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been deactivated. Contact an admin if you believe this is a mistake.']);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
