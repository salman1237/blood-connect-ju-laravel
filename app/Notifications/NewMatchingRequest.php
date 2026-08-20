<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMatchingRequest extends Notification
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_matching_request',
            'request_id' => $this->bloodRequest->id,
            'blood_group' => $this->bloodRequest->blood_group,
            'urgency' => $this->bloodRequest->urgency,
            'message' => "New {$this->bloodRequest->urgency} request for {$this->bloodRequest->blood_group} at {$this->bloodRequest->hospital_name}.",
        ];
    }
}
