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
            // Authoritative, not left for the client to reimplement: same
            // policies the web app's own buttons are gated on, so "can I
            // respond / confirm / fulfill" never drifts between the two.
            // Gated on the same relation as `responses` below (not just a
            // style match) — BloodRequestPolicy::respond() runs its own
            // query per call, and the feed listing has no use for these
            // flags anyway (matches the web app: no respond button on the
            // card, only on the detail page), so skip it there entirely
            // rather than pay an N+1 for values nothing reads.
            'can_respond' => $this->when(
                $this->relationLoaded('responses'),
                fn () => $request->user()?->can('respond', $this->resource) ?? false,
            ),
            'can_fulfill' => $this->when(
                $this->relationLoaded('responses'),
                fn () => $request->user()?->can('fulfill', $this->resource) ?? false,
            ),
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($response) => [
                'id' => $response->id,
                'status' => $response->status,
                'donor' => [
                    'id' => $response->donor?->id,
                    'name' => $response->donor?->name,
                ],
                'requester_confirmed' => $response->requester_confirmed_at !== null,
                'donor_confirmed' => $response->donor_confirmed_at !== null,
                'is_mutually_confirmed' => $response->isMutuallyConfirmed(),
                'can_confirm' => $request->user()?->can('confirm', $response) ?? false,
                'can_confirm_donation' => $request->user()?->can('confirmDonation', $response) ?? false,
            ])),
        ];
    }
}
