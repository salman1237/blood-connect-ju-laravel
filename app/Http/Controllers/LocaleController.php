<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED_LOCALES, true), 404);

        $request->session()->put('locale', $locale);

        // Persisted per-user (not just per-session) so the choice follows a
        // logged-in donor across devices, per the brief's "session + user"
        // requirement.
        $request->user()?->update(['locale' => $locale]);

        return back();
    }
}
