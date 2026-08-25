<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the requester when a verifier approves their request. */
class RequestVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

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
        return [
            'title' => 'Your blood request has been verified',
            'body' => "Your request at {$this->bloodRequest->hospital_name} is now visible campus-wide with a Verified badge.",
            'data' => ['type' => 'request_verified', 'request_id' => $this->bloodRequest->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your blood request has been verified')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your request for {$this->bloodRequest->blood_group} at {$this->bloodRequest->hospital_name} has been verified and is now visible campus-wide with a Verified badge.")
            ->action('View request', route('requests.show', $this->bloodRequest));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request_verified',
            'request_id' => $this->bloodRequest->id,
            'message' => "Your request at {$this->bloodRequest->hospital_name} has been verified.",
        ];
    }
}
