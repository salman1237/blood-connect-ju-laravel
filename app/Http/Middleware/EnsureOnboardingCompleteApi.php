<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalent of EnsureOnboardingComplete — same rule (donor-facing
 * routes need a completed profile first), but a 403 JSON response instead
 * of a redirect, since there's no session-based "next page" to redirect a
 * mobile client to.
 */
class EnsureOnboardingCompleteApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasCompletedOnboarding()) {
            return response()->json([
                'message' => 'Please complete your donor profile first.',
            ], 403);
        }

        return $next($request);
    }
}
