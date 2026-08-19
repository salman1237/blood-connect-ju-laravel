<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Redirect authenticated users with an incomplete profile to the
     * onboarding wizard before they can reach the rest of the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->hasVerifiedEmail()
            && ! $user->hasCompletedOnboarding()
            && ! $request->routeIs('onboarding.*', 'logout', 'verification.*')
        ) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
