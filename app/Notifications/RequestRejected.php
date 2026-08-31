<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
        $channels = $notifiable->wantsEmailNotifications() ? ['database', 'mail'] : ['database'];

        return [...$channels, 'broadcast', FcmChannel::class];
    }

    /** Live-updates the notification bell (private-App.Models.User.{id}) — same payload as the database record. */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * @return array{title: string, body: string, data: array<string, string|int>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Your blood request was not approved',
            'body' => "Your request at {$this->bloodRequest->hospital_name} was reviewed and not approved.",
            'data' => ['type' => 'request_rejected', 'request_id' => $this->bloodRequest->id],
        ];
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
