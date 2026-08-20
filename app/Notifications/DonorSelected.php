<?php

namespace App\Notifications;

use App\Models\RequestResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the donor when the requester picks them as their confirmed donor. */
class DonorSelected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RequestResponse $response) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailNotifications() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bloodRequest = $this->response->bloodRequest;

        return (new MailMessage)
            ->subject('You\'ve been selected to donate')
            ->greeting("Hi {$notifiable->name},")
            ->line("{$bloodRequest->requester->name} selected you to donate {$bloodRequest->blood_group} for their request at {$bloodRequest->hospital_name}.")
            ->line("Contact method: {$bloodRequest->contact_method}")
            ->action('View request', route('requests.show', $bloodRequest))
            ->line('Once the donation happens, come back and confirm it to keep your donor history and trust score up to date.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bloodRequest = $this->response->bloodRequest;

        return [
            'type' => 'donor_selected',
            'request_id' => $bloodRequest->id,
            'message' => "You've been selected to donate {$bloodRequest->blood_group} for the request at {$bloodRequest->hospital_name}.",
        ];
    }
}
