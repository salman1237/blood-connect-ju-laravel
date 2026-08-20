<?php

namespace App\Models;

use App\Observers\BloodRequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(BloodRequestObserver::class)]
class BloodRequest extends Model
{
    use HasFactory;

    /** Open requests older than this auto-expire. */
    const EXPIRES_AFTER_HOURS = 72;

    /** Sort order for the urgency-then-recency feed. */
    const URGENCY_ORDER = ['critical', 'within_24h', 'planned'];

    /**
     * Real ABO/Rh donor compatibility, not just exact blood-group match —
     * which donor blood groups can safely give blood to a patient needing
     * the given group. O- is the universal donor, AB+ the universal
     * recipient.
     */
    const DONOR_COMPATIBILITY = [
        'O-' => ['O-'],
        'O+' => ['O-', 'O+'],
        'A-' => ['O-', 'A-'],
        'A+' => ['O-', 'O+', 'A-', 'A+'],
        'B-' => ['O-', 'B-'],
        'B+' => ['O-', 'O+', 'B-', 'B+'],
        'AB-' => ['O-', 'A-', 'B-', 'AB-'],
        'AB+' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
    ];

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
        'rejected_at',
        'rejected_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'rejected_at' => 'datetime',
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

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(RequestResponse::class, 'request_id');
    }

    /** Used for the average-response-time stat (admin dashboard, landing page). */
    public function firstResponse(): HasOne
    {
        return $this->hasOne(RequestResponse::class, 'request_id')->oldestOfMany();
    }

    /**
     * Average minutes between a request being posted and its first
     * response, across every request that's had at least one. Computed in
     * PHP rather than a raw TIMESTAMPDIFF query — that function isn't
     * portable to sqlite, which the test suite runs on.
     */
    public static function averageResponseMinutes(): ?float
    {
        return static::query()
            ->has('firstResponse')
            ->with('firstResponse')
            ->get()
            ->avg(fn (self $request) => $request->created_at->diffInMinutes($request->firstResponse->created_at));
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'request_id');
    }

    /** Donor blood groups that can safely give blood for this request. */
    public function compatibleDonorBloodGroups(): array
    {
        return self::DONOR_COMPATIBILITY[$this->blood_group];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /** Still-actionable requests a verifier hasn't ruled on yet. */
    public function scopeAwaitingVerification(Builder $query): Builder
    {
        return $query->where('is_verified', false)
            ->whereNull('rejected_at')
            ->whereIn('status', ['open', 'donor_found']);
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
