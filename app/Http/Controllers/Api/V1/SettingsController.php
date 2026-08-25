<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\Request;

/**
 * Mirrors web's SettingsController::updateNotifications() — just the
 * email-notifications toggle. Web's settings page also has a language
 * toggle, but that's a whole-app localization concern the Android client
 * doesn't share yet, so there's nothing to mirror there.
 */
class SettingsController extends Controller
{
    public function updateNotifications(Request $request): UserResource
    {
        $request->validate(['email_notifications_enabled' => ['required', 'boolean']]);

        $user = $request->user();
        $user->update(['email_notifications_enabled' => $request->boolean('email_notifications_enabled')]);

        return new UserResource($user->fresh()->load('donorProfile'));
    }
}
