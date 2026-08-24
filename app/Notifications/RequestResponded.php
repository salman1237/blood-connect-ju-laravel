<?php

namespace App\Notifications;

use App\Models\RequestResponse;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RequestResponse $response) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = $notifiable->wantsEmailNotifications() ? ['database', 'mail'] : ['database'];

        return [...$channels, FcmChannel::class];
    }

    /**
     * @return array{title: string, body: string, data: array<string, string|int>}
     */
    public function toFcm(object $notifiable): array
    {
        $bloodRequest = $this->response->bloodRequest;

        return [
            'title' => 'New response to your request',
            'body' => "{$this->response->donor->name} can donate {$bloodRequest->blood_group} for your request at {$bloodRequest->hospital_name}.",
            'data' => ['type' => 'request_responded', 'request_id' => $bloodRequest->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bloodRequest = $this->response->bloodRequest;

        return (new MailMessage)
            ->subject("{$this->response->donor->name} can donate for your request")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->response->donor->name} ({$this->response->donor->donorProfile?->blood_group}) responded to your blood request at {$bloodRequest->hospital_name}.")
            ->action('View responders', route('requests.show', $bloodRequest))
            ->line('You can confirm them as your donor from the request page.');
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
