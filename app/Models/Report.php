<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    const REASONS = [
        'spam' => 'Spam',
        'fraudulent' => 'Looks fraudulent',
        'duplicate' => 'Duplicate request',
        'inappropriate' => 'Inappropriate content',
        'other' => 'Other',
    ];

    protected $fillable = [
        'request_id',
        'reporter_id',
        'reason',
        'details',
        'status',
    ];

    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class, 'request_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
