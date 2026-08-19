<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorProfile extends Model
{
    use HasFactory;

    /** Donors must wait this many days between donations. */
    const ELIGIBILITY_WINDOW_DAYS = 120;

    protected $fillable = [
        'user_id',
        'blood_group',
        'last_donation_date',
        'is_available',
        'trust_score',
    ];

    protected function casts(): array
    {
        return [
            'last_donation_date' => 'date',
            'is_available' => 'boolean',
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
}
