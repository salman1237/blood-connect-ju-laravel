<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use App\Support\RegistrationValidation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new account and immediately return an API token — same
     * validation rules as the web signup form (App\Support\RegistrationValidation),
     * so the two can't silently drift apart.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            ...RegistrationValidation::rules(),
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'is_active' => true,
            'email_notifications_enabled' => true,
        ]);

        // Reuses the exact same listener chain as web registration —
        // dispatches the verification email, nothing mobile-specific here.
        event(new Registered($user));

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Exchange email/password + a device name for a bearer token. Deliberately
     * does NOT go through the web guard's session-based Auth::attempt() —
     * API routes are stateless (no session middleware), so this checks the
     * password directly, the same way Sanctum's own docs recommend for
     * first-party native apps.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated. Contact an admin if you believe this is a mistake.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Exchange a Google ID token (obtained on-device via Credential Manager)
     * for a Sanctum bearer token — the API's equivalent of web's
     * GoogleAuthController, sharing the exact same account-linking rules via
     * User::findOrCreateFromGoogle() so the two clients can't drift apart.
     *
     * The ID token's audience must be the *Web* OAuth client ID
     * (config('services.google.client_id'), same one web's own Socialite
     * flow uses) — Google Identity Services on Android issues tokens
     * audienced to whatever "server client ID" the app requests with, not
     * to its own Android-type client ID, so this is intentional, not a
     * mismatch to "fix" later.
     */
    public function google(Request $request, GoogleIdTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $payload = $verifier->verify($validated['id_token'], config('services.google.client_id'));

        if (! $payload) {
            throw ValidationException::withMessages([
                'id_token' => ['That Google sign-in could not be verified. Please try again.'],
            ]);
        }

        $user = User::findOrCreateFromGoogle(
            googleId: $payload['sub'],
            email: $payload['email'],
            name: $payload['name'] ?? $payload['email'],
            avatarUrl: $payload['picture'] ?? null,
        );

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'id_token' => ['This account has been deactivated. Contact an admin if you believe this is a mistake.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->fresh()->load('donorProfile')),
        ]);
    }

    /**
     * Revoke only the token used for *this* request — not every device the
     * user is signed in on.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('donorProfile'));
    }
}
