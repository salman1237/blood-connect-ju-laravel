<?php

namespace App\Services;

use Google\Auth\AccessToken;

/**
 * Thin wrapper around Google\Auth\AccessToken::verify() — a vendor class
 * that can't practically be mocked in tests (a real signed Google ID token
 * isn't something a test can produce, and Mockery's "overload" trick only
 * works for classes not already loaded elsewhere in the process, which
 * this one usually is via FCM's own use of google/auth). Bound in the
 * container like any other collaborator instead, so
 * Api\V1\AuthController::google()'s tests can mock this directly.
 */
class GoogleIdTokenVerifier
{
    /**
     * @return array<string, mixed>|false
     */
    public function verify(string $idToken, string $audience): array|false
    {
        return (new AccessToken)->verify($idToken, ['audience' => $audience]);
    }
}
