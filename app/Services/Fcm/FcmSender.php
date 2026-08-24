<?php

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around FCM's HTTP v1 API. Deliberately not using
 * kreait/firebase-php — it pulls in ext-sodium transitively (via
 * lcobucci/jwt), which isn't installed locally or on production. google/auth
 * (via FcmAccessTokenProvider) mints the same OAuth2 bearer token via
 * RS256/openssl instead, and FCM's v1 send endpoint is a single plain POST —
 * no SDK needed for that part.
 */
class FcmSender
{
    public function __construct(private FcmAccessTokenProvider $tokenProvider) {}

    /**
     * @param  array<string, string|int>  $data  custom payload — FCM requires
     *                                           every data value to be a string, converted below.
     */
    public function send(string $token, string $title, string $body, array $data = []): FcmSendResult
    {
        $accessToken = $this->tokenProvider->token();

        if ($accessToken === null) {
            return FcmSendResult::Failed;
        }

        $projectId = config('services.firebase.project_id');

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]);

        if ($response->successful()) {
            return FcmSendResult::Sent;
        }

        // FCM v1's error shape: { "error": { "status": "UNREGISTERED", ... } }
        // UNREGISTERED/NOT_FOUND = token is dead (app uninstalled, token
        // rotated). INVALID_ARGUMENT usually means a malformed token, which
        // for our purposes is equally unsalvageable — prune it too.
        $errorStatus = $response->json('error.status');

        if (in_array($errorStatus, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'], true)) {
            return FcmSendResult::InvalidToken;
        }

        Log::warning('FCM send failed.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return FcmSendResult::Failed;
    }
}
