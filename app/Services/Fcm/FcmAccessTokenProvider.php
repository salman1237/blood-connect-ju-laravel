<?php

namespace App\Services\Fcm;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mints (and caches) the OAuth2 bearer token FCM's HTTP v1 API needs.
 * Pulled out of FcmSender as its own class so tests can swap it for a fake —
 * the real fetchAuthToken() call goes out via google/auth's own internal
 * Guzzle client, which Laravel's Http::fake() has no way to intercept.
 */
class FcmAccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const CACHE_KEY = 'fcm.access_token';

    /**
     * Null means "couldn't get a token" — either Firebase isn't configured
     * (no project id / no credentials file, e.g. a dev machine) or the fetch
     * itself failed. Either way the caller just skips the send; a failure
     * is never cached, so the next call retries rather than getting stuck.
     */
    public function token(): ?string
    {
        $projectId = config('services.firebase.project_id');
        $credentialsPath = config('services.firebase.credentials');

        if (! $projectId || ! is_string($credentialsPath) || ! file_exists($credentialsPath)) {
            Log::warning('FCM not configured — skipping push send.', ['project_id' => $projectId]);

            return null;
        }

        // OAuth2 access tokens are valid for 1hr; cache for 50min so we're
        // never caught using an expired one.
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), function () use ($credentialsPath) {
            try {
                $credentials = new ServiceAccountCredentials(self::SCOPE, $credentialsPath);
                $token = $credentials->fetchAuthToken();

                return $token['access_token'] ?? null;
            } catch (Throwable $e) {
                Log::error('Failed to fetch FCM access token.', ['exception' => $e->getMessage()]);

                return null;
            }
        });
    }
}
