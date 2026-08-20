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
        'hall',
        'department',
        'phone',
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVerifier(): bool
    {
        return in_array($this->role, ['verifier', 'admin'], true);
    }

    public function hasCompletedOnboarding(): bool
    {
        // Only students live in halls — staff/faculty only need a department.
        $hallSatisfied = $this->role !== 'student' || $this->hall !== null;

        return $hallSatisfied
            && $this->department !== null
            && $this->donorProfile !== null;
    }

    /**
     * Shared by onboarding (first-time setup) and the profile page (editing
     * afterward) — validated data from UpdateDonorProfileRequest in, both the
     * user's own fields and their donor_profiles row updated together.
     */
    public function updateDonorProfile(array $validated): void
    {
        $this->update([
            'department' => $validated['department'],
            'hall' => $validated['hall'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

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
