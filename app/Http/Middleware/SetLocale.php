<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    const SUPPORTED_LOCALES = ['en', 'bn'];

    /**
     * A logged-in user's saved preference wins over the session (so it
     * follows them across devices); the session covers guests and anyone
     * who hasn't set a preference yet. Global, not route-gated, since the
     * toggle lives in both the guest and authenticated layouts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
