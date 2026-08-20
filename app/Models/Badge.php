<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function donors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'donor_badges', 'badge_id', 'donor_id')
            ->withPivot('earned_at');
    }
}
