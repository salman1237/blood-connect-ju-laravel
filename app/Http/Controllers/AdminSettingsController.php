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

    /**
     * $which is 'funded_by' or 'maintained_by' -- each credit line is its
     * own independent form (see admin/settings/edit.blade.php), not one
     * combined form for both, so saving one never touches the other.
     */
    public function update(Request $request, string $which): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::current()->update([$which => $validated['value']]);

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
