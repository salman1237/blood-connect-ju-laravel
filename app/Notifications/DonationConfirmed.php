<?php

namespace App\Notifications;

use App\Models\RequestResponse;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to both the donor and the requester once mutual confirmation
 * completes — same event, different message per side depending on which
 * one $notifiable turns out to be.
 */
class DonationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RequestResponse $response) {}

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
        $bloodRequest = $this->response->bloodRequest;
        $isDonor = $notifiable->id === $this->response->donor_id;

        return [
            'title' => $isDonor ? 'Thanks for donating!' : 'Donation confirmed',
            'body' => $isDonor
                ? "Your donation for the request at {$bloodRequest->hospital_name} is confirmed."
                : "The donation for your request at {$bloodRequest->hospital_name} is confirmed.",
            'data' => ['type' => 'donation_confirmed', 'request_id' => $bloodRequest->id],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bloodRequest = $this->response->bloodRequest;

        if ($notifiable->id === $this->response->donor_id) {
            $trustScore = $notifiable->donorProfile?->trust_score;

            return (new MailMessage)
                ->subject('Thanks for donating!')
                ->greeting("Hi {$notifiable->name},")
                ->line("Your donation for the request at {$bloodRequest->hospital_name} has been confirmed by both sides.")
                ->line("Your trust score is now {$trustScore}.")
                ->action('View your profile', route('profile.edit'));
        }

        return (new MailMessage)
            ->subject('Donation confirmed')
            ->greeting("Hi {$notifiable->name},")
            ->line("Thanks for confirming — we hope everything went well at {$bloodRequest->hospital_name}.")
            ->action('View request', route('requests.show', $bloodRequest));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bloodRequest = $this->response->bloodRequest;

        return [
            'type' => 'donation_confirmed',
            'request_id' => $bloodRequest->id,
            'message' => $notifiable->id === $this->response->donor_id
                ? "Your donation for the request at {$bloodRequest->hospital_name} is confirmed."
                : "The donation for your request at {$bloodRequest->hospital_name} is confirmed.",
        ];
    }
}
