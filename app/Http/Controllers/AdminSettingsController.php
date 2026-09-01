<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The org credit shown on the landing page and Settings ("Implemented &
 * funded by...", "Maintained by..."), each with its own optional logo --
 * editable here instead of hardcoded, since who funds/maintains the project
 * can change over time without a code deploy.
 */
class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', ['setting' => AppSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'funded_by' => ['nullable', 'string', 'max:255'],
            'maintained_by' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::current()->update($validated);

        return redirect()->route('admin.settings.edit')->with('status', 'settings-updated');
    }

    public function updateLogo(Request $request, string $which): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        AppSetting::current()->updateLogo($which, $request->file('photo'));

        return redirect()->route('admin.settings.edit')->with('status', 'logo-updated');
    }

    public function destroyLogo(string $which): RedirectResponse
    {
        AppSetting::current()->removeLogo($which);

        return redirect()->route('admin.settings.edit')->with('status', 'logo-removed');
    }
}
