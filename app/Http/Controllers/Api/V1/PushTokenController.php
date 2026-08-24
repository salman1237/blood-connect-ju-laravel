<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPushTokenRequest;
use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushTokenController extends Controller
{
    /**
     * Register (or reassign) a device's FCM token. updateOrCreate on the
     * token alone — not user_id+token — because the same physical device
     * can only point at one account's inbox at a time; logging in as a
     * different user on the same phone should move the token over, not
     * leave a stale row pointed at the old account.
     */
    public function store(RegisterPushTokenRequest $request): Response
    {
        PushToken::updateOrCreate(
            ['token' => $request->validated('token')],
            [
                'user_id' => $request->user()->id,
                'device_name' => $request->validated('device_name'),
            ],
        );

        return response()->noContent(Response::HTTP_CREATED);
    }

    /** Unregister a device's token, e.g. on logout, so it stops receiving pushes. */
    public function destroy(Request $request): Response
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->pushTokens()->where('token', $request->input('token'))->delete();

        return response()->noContent();
    }
}
