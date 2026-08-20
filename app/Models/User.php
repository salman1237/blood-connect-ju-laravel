<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
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
}
