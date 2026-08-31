<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'role' => $this->role,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'hall' => $this->hall,
            'department' => $this->department,
            'batch' => $this->batch,
            'phone' => $this->phone,
            'whatsapp_number' => $this->whatsapp_number,
            'phone_has_whatsapp' => $this->phone_has_whatsapp,
            'phone_visibility' => $this->phone_visibility,
            'whatsapp_url' => $this->whatsapp_url,
            'avatar_url' => $this->avatar_url,
            'email_notifications_enabled' => $this->email_notifications_enabled,
            'is_active' => $this->is_active,
            'is_admin' => $this->isAdmin(),
            'is_verifier' => $this->isVerifier(),
            'has_completed_onboarding' => $this->hasCompletedOnboarding(),
            'donor_profile' => $this->whenLoaded('donorProfile', fn () => [
                'blood_group' => $this->donorProfile?->blood_group,
                'is_available' => $this->donorProfile?->is_available,
                'last_donation_date' => $this->donorProfile?->last_donation_date?->toDateString(),
                'trust_score' => $this->donorProfile?->trust_score,
            ]),
        ];
    }
}
