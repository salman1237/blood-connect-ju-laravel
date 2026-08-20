<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the requester when a verifier rejects their request. */
class RequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your blood request was not approved')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your request for {$this->bloodRequest->blood_group} at {$this->bloodRequest->hospital_name} was reviewed and not approved, so it's no longer visible on the live feed.")
            ->line('If this was a mistake or the details need correcting, you can post a new request with accurate information.')
            ->action('Post a new request', route('requests.create'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request_rejected',
            'request_id' => $this->bloodRequest->id,
            'message' => "Your request at {$this->bloodRequest->hospital_name} was not approved.",
        ];
    }
}
