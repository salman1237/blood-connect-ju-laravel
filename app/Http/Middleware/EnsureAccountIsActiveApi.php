<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalent of EnsureAccountIsActive — login already refuses a
 * deactivated account (Api\V1\AuthController::login), but without this a
 * token issued *before* deactivation would keep working indefinitely,
 * same gap the web version closes for sessions.
 */
class EnsureAccountIsActiveApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'This account has been deactivated. Contact an admin if you believe this is a mistake.',
            ], 403);
        }

        return $next($request);
    }
}
