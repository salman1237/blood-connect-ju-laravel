<?php

namespace App\Http\Resources\Api;

use App\Models\DonorProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A donor as they appear in a list (matching-donors, donor directory later)
 * — deliberately lighter than UserResource, which is for the authenticated
 * user's own full profile.
 *
 * @mixin DonorProfile
 */
class DonorSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'blood_group' => $this->blood_group,
            'hall' => $this->user->hall,
            'department' => $this->user->department,
            'avatar_url' => $this->user->avatar_url,
            'whatsapp_url' => $this->user->phoneVisibleTo($request->user()) ? $this->user->whatsapp_url : null,
        ];
    }
}
