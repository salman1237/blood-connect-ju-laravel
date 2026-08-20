<?php

namespace App\Models;

use App\Observers\RequestResponseObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(RequestResponseObserver::class)]
class RequestResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'donor_id',
        'status',
        'requester_confirmed_at',
        'donor_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'requester_confirmed_at' => 'datetime',
            'donor_confirmed_at' => 'datetime',
        ];
    }

    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class, 'request_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    /** Both sides have independently confirmed the donation actually happened. */
    public function isMutuallyConfirmed(): bool
    {
        return $this->requester_confirmed_at !== null && $this->donor_confirmed_at !== null;
    }
}
