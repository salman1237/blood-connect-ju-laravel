<?php

namespace App\Http\Resources\Api;

use App\Models\DonationHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A donor's public-ish profile page (the API twin of donors/show.blade.php)
 * — visible to any authenticated user, since browsing the directory is the
 * whole point, but deliberately omits email the way the web page does.
 * Unlike DonorSummaryResource (list rows) or UserResource (your own full
 * profile), this one also carries badges and donation history.
 *
 * @mixin User
 */
class DonorDetailResource extends JsonResource
{
    /** @param Collection<int, DonationHistory> $donationHistory */
    public function __construct(User $donor, private readonly Collection $donationHistory)
    {
        parent::__construct($donor);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $phoneVisible = $this->resource->phoneVisibleTo($request->user());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'hall' => $this->hall,
            'department' => $this->department,
            'batch' => $this->batch,
            'phone' => $phoneVisible ? $this->phone : null,
            'whatsapp_number' => $phoneVisible ? $this->whatsapp_number : null,
            'phone_has_whatsapp' => $this->phone_has_whatsapp,
            'whatsapp_url' => $phoneVisible ? $this->whatsapp_url : null,
            'avatar_url' => $this->avatar_url,
            'donor_profile' => [
                'blood_group' => $this->donorProfile->blood_group,
                'is_available' => $this->donorProfile->is_available,
                'is_eligible' => $this->donorProfile->is_eligible,
                'next_eligible_date' => $this->donorProfile->next_eligible_date?->toDateString(),
                'trust_score' => $this->donorProfile->trust_score,
            ],
            'badges' => $this->badges->map(fn ($badge) => [
                'name' => $badge->name,
                'slug' => $badge->slug,
                'description' => $badge->description,
                // The donor_badges pivot's earned_at is a raw DB timestamp
                // string, not cast anywhere (no other endpoint reads it) —
                // parsed here so it's ISO8601 like every other date in the API.
                'earned_at' => Carbon::parse($badge->pivot->earned_at)->toIso8601String(),
            ]),
            'donation_history' => $this->donationHistory->map(fn ($entry) => [
                'hospital_name' => $entry->bloodRequest?->hospital_name,
                'confirmed_at' => $entry->confirmed_at->toIso8601String(),
            ]),
        ];
    }
}
