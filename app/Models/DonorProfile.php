<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorProfile extends Model
{
    use HasFactory;

    /** Donors must wait this many days between donations. */
    const ELIGIBILITY_WINDOW_DAYS = 120;

    /** Rh-negative types — rare in the Bangladeshi population (~3-5%). */
    const RARE_BLOOD_GROUPS = ['A-', 'B-', 'AB-', 'O-'];

    /** Minimum gap between "you're eligible again" reminder emails to the same donor. */
    const REMINDER_COOLDOWN_DAYS = 30;

    protected $fillable = [
        'user_id',
        'blood_group',
        'last_donation_date',
        'is_available',
        'trust_score',
        'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'last_donation_date' => 'date',
            'is_available' => 'boolean',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Computed, not stored, so it never goes stale: eligible if never
     * donated, or if the last donation was more than 120 days ago.
     */
    protected function isEligible(): Attribute
    {
        return Attribute::make(
            // Both sides normalized to midnight — last_donation_date is a
            // date (no time component), so comparing it against the current
            // time-of-day would make the diff drift by up to ~24h depending
            // on when "now" happens to fall.
            get: fn () => $this->last_donation_date === null
                || $this->last_donation_date->diffInDays(now()->startOfDay()) > self::ELIGIBILITY_WINDOW_DAYS,
        );
    }

    protected function nextEligibleDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_donation_date?->addDays(self::ELIGIBILITY_WINDOW_DAYS),
        );
    }

    /** Query-level equivalent of the is_eligible accessor. */
    public function scopeEligible(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::ELIGIBILITY_WINDOW_DAYS)->startOfDay();

        return $query->where(function (Builder $q) use ($cutoff) {
            $q->whereNull('donor_profiles.last_donation_date')
                ->orWhere('donor_profiles.last_donation_date', '<', $cutoff);
        });
    }

    /** Eligible donors who haven't been sent a reminder recently. */
    public function scopeDueForReminder(Builder $query): Builder
    {
        $cooldownCutoff = now()->subDays(self::REMINDER_COOLDOWN_DAYS);

        return $query->eligible()
            ->where(function (Builder $q) use ($cooldownCutoff) {
                $q->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<', $cooldownCutoff);
            });
    }

    /**
     * Eligible donors who could give blood for the given request, ranked:
     * exact blood-group match before compatible-but-different, same
     * hall/department before elsewhere, available before unavailable.
     */
    public function scopeMatchingRequest(Builder $query, BloodRequest $request): Builder
    {
        return $query
            ->select('donor_profiles.*')
            ->join('users', 'users.id', '=', 'donor_profiles.user_id')
            ->whereIn('donor_profiles.blood_group', $request->compatibleDonorBloodGroups())
            ->where('users.id', '!=', $request->requester_id)
            ->eligible()
            ->orderByRaw('CASE WHEN donor_profiles.blood_group = ? THEN 0 ELSE 1 END', [$request->blood_group])
            ->orderByRaw(
                'CASE WHEN (? IS NOT NULL AND users.hall = ?) OR (? IS NOT NULL AND users.department = ?) THEN 0 ELSE 1 END',
                [$request->requester->hall, $request->requester->hall, $request->requester->department, $request->requester->department]
            )
            ->orderByDesc('donor_profiles.is_available');
    }
}
