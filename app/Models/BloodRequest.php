<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodRequest extends Model
{
    use HasFactory;

    /** Open requests older than this auto-expire. */
    const EXPIRES_AFTER_HOURS = 72;

    /** Sort order for the urgency-then-recency feed. */
    const URGENCY_ORDER = ['critical', 'within_24h', 'planned'];

    protected $fillable = [
        'requester_id',
        'blood_group',
        'units_needed',
        'hospital_name',
        'location',
        'urgency',
        'patient_context',
        'contact_method',
        'status',
        'is_verified',
        'verified_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeUrgencyThenRecency(Builder $query): Builder
    {
        $case = 'CASE urgency '
            .implode(' ', array_map(
                fn ($urgency, $i) => "WHEN '{$urgency}' THEN {$i}",
                self::URGENCY_ORDER,
                array_keys(self::URGENCY_ORDER)
            ))
            .' END';

        return $query->orderByRaw($case)->latest();
    }
}
