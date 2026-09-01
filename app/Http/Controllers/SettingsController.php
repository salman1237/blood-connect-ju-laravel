<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings', ['orgSetting' => AppSetting::current()]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate(['email_notifications_enabled' => ['boolean']]);

        $request->user()->update([
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
        ]);

        return redirect()->route('settings.edit')->with('status', 'notifications-updated');
    }
}
