<?php

namespace App\Notifications;

use App\Models\RequestResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestResponded extends Notification
{
    use Queueable;

    public function __construct(public RequestResponse $response) {}

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
        $bloodRequest = $this->response->bloodRequest;

        return [
            'type' => 'request_responded',
            'request_id' => $bloodRequest->id,
            'donor_name' => $this->response->donor->name,
            'blood_group' => $bloodRequest->blood_group,
            'message' => "{$this->response->donor->name} can donate {$bloodRequest->blood_group} for your request at {$bloodRequest->hospital_name}.",
        ];
    }
}
