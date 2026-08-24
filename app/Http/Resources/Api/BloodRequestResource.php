<?php

namespace App\Http\Resources\Api;

use App\Models\BloodRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BloodRequest
 */
class BloodRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blood_group' => $this->blood_group,
            'units_needed' => $this->units_needed,
            'hospital_name' => $this->hospital_name,
            'location' => $this->location,
            'urgency' => $this->urgency,
            'patient_context' => $this->patient_context,
            'contact_method' => $this->contact_method,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'requester' => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
                'hall' => $this->requester?->hall,
                'department' => $this->requester?->department,
            ],
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($response) => [
                'id' => $response->id,
                'status' => $response->status,
                'donor' => [
                    'id' => $response->donor?->id,
                    'name' => $response->donor?->name,
                ],
                'is_mutually_confirmed' => $response->isMutuallyConfirmed(),
            ])),
        ];
    }
}
