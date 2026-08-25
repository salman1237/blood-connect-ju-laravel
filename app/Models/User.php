<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'gender',
        'date_of_birth',
        'is_active',
        'locale',
        'email_notifications_enabled',
        'hall',
        'department',
        'batch',
        'phone',
        'phone_has_whatsapp',
        'whatsapp_number',
        'google_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'phone_has_whatsapp' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    /** Whole years since date_of_birth — null if it was never set. */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth?->age,
        );
    }

    /**
     * A clickable wa.me link built from whichever number the user has
     * actually marked as their WhatsApp number (the main phone, unless
     * they said it doesn't have WhatsApp, in which case the separate
     * alternate number) — null if neither is set. wa.me needs the number in
     * plain international digits with no leading "+" or "0", so a local
     * "01XXXXXXXXX" gets its leading 0 swapped for the BD country code.
     */
    protected function whatsappUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->phone_has_whatsapp ? $this->phone : $this->whatsapp_number;

                if (! $number) {
                    return null;
                }

                $digits = preg_replace('/\D+/', '', $number);

                if (! str_starts_with($digits, '880')) {
                    $digits = '880'.ltrim($digits, '0');
                }

                if (strlen($digits) < 12) {
                    return null;
                }

                return "https://wa.me/{$digits}";
            },
        );
    }

    public function donorProfile(): HasOne
    {
        return $this->hasOne(DonorProfile::class);
    }

    public function donationHistory(): HasMany
    {
        return $this->hasMany(DonationHistory::class, 'donor_id');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'donor_badges', 'donor_id', 'badge_id')
            ->withPivot('earned_at');
    }

    /** Registered mobile devices — FcmChannel sends to every one of them. */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    public function bloodRequests(): HasMany
    {
        return $this->hasMany(BloodRequest::class, 'requester_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(RequestResponse::class, 'donor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVerifier(): bool
    {
        return in_array($this->role, ['verifier', 'admin'], true);
    }

    public function wantsEmailNotifications(): bool
    {
        return $this->email_notifications_enabled;
    }

    public function hasCompletedOnboarding(): bool
    {
        // Only students live in halls or have a batch — staff/faculty only
        // need a department.
        $hallSatisfied = $this->role !== 'student' || $this->hall !== null;
        $batchSatisfied = $this->role !== 'student' || $this->batch !== null;

        return $hallSatisfied
            && $batchSatisfied
            && $this->department !== null
            && $this->gender !== null
            && $this->donorProfile !== null;
    }

    /** Self-service role changes are limited to the three donor-tier roles. */
    public function canSelfServiceRole(): bool
    {
        return in_array($this->role, ['student', 'staff', 'faculty'], true);
    }

    /**
     * Overrides the framework default (sent synchronously) with a queued
     * equivalent — see App\Notifications\QueuedVerifyEmail for why.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Overrides the framework default (sent synchronously) with a queued
     * equivalent — see App\Notifications\QueuedResetPassword for why.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    /** Academic-year batches from 1970 to the present, newest first. */
    public static function batchOptions(): array
    {
        $currentYear = (int) now()->format('Y');

        return collect(range($currentYear, 1970))
            ->map(fn (int $year) => "{$year}-".substr((string) ($year + 1), 2, 2))
            ->all();
    }

    /**
     * Shared by onboarding (first-time setup) and the profile page (editing
     * afterward) — validated data from UpdateDonorProfileRequest in, both the
     * user's own fields and their donor_profiles row updated together.
     */
    public function updateDonorProfile(array $validated): void
    {
        $hasWhatsapp = (bool) ($validated['phone_has_whatsapp'] ?? true);

        $updates = [
            'department' => $validated['department'],
            'hall' => $validated['hall'] ?? null,
            'batch' => $validated['batch'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'phone_has_whatsapp' => $hasWhatsapp,
            'whatsapp_number' => $hasWhatsapp ? null : ($validated['whatsapp_number'] ?? null),
        ];

        // Never let a verifier/admin accidentally demote themselves just by
        // submitting this form — role is only self-service for the three
        // donor-tier roles, and the field is only ever rendered for them.
        if ($this->canSelfServiceRole() && isset($validated['role'])) {
            $updates['role'] = $validated['role'];
        }

        $this->update($updates);

        $this->donorProfile()->updateOrCreate(
            ['user_id' => $this->id],
            [
                'blood_group' => $validated['blood_group'],
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'last_donation_date' => $validated['last_donation_date'] ?? null,
            ]
        );
    }

    /**
     * Shared by the web profile page's upload form and the API's own
     * upload endpoint (Android) — one place for "store the file, clean up
     * whatever was there before, save the new URL" so neither client can
     * drift from the other's behavior. Any previously locally-stored photo
     * is deleted so replaced uploads don't pile up in storage; a
     * Google-hosted URL (never under our own 'avatars' disk path) is left
     * alone rather than deleted.
     */
    public function updateAvatar(UploadedFile $photo): void
    {
        $this->deleteStoredAvatarIfLocal();

        $path = $photo->store('avatars', 'public');

        $this->update(['avatar_url' => Storage::disk('public')->url($path)]);
    }

    /** Reverts to the initials-fallback avatar — see App\Http\Resources\Api\UserResource / web's x-user-avatar. */
    public function removeAvatar(): void
    {
        $this->deleteStoredAvatarIfLocal();

        $this->update(['avatar_url' => null]);
    }

    /**
     * Shared by web's GoogleAuthController (session login) and the API's own
     * Google sign-in endpoint (Android) — one place for "find by google_id,
     * else link an existing email match, else create a brand-new account"
     * so neither client can drift from the other's account-linking rules.
     * Never overwrites a photo the account already has with the Google one.
     */
    public static function findOrCreateFromGoogle(string $googleId, string $email, string $name, ?string $avatarUrl): self
    {
        $user = static::where('google_id', $googleId)->first();

        if ($user) {
            return $user;
        }

        $user = static::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $user->avatar_url ?? $avatarUrl,
            ])->save();

            return $user;
        }

        // Google has already verified this email address, so there's no
        // separate OTP/verification step for accounts created this way.
        // forceCreate (not create) because email_verified_at is
        // deliberately excluded from $fillable everywhere else.
        return static::forceCreate([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatarUrl,
            'password' => null,
            'email_verified_at' => now(),
            // Set explicitly, not left to the migration's DB default —
            // forceCreate() doesn't refresh the in-memory model, so an
            // is_active check right after this would otherwise see null.
            'is_active' => true,
            'email_notifications_enabled' => true,
        ]);
    }

    private function deleteStoredAvatarIfLocal(): void
    {
        $publicBaseUrl = Storage::disk('public')->url('');

        if ($this->avatar_url && str_starts_with($this->avatar_url, $publicBaseUrl)) {
            Storage::disk('public')->delete(str_replace($publicBaseUrl, '', $this->avatar_url));
        }
    }
}
