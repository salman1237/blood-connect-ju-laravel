<?php

namespace App\Models;

use App\Observers\DonationHistoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(DonationHistoryObserver::class)]
class DonationHistory extends Model
{
    use HasFactory;

    protected $table = 'donation_history';

    protected $fillable = [
        'donor_id',
        'request_id',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class, 'request_id');
    }
}
