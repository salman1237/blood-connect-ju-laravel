<?php

namespace App\Notifications;

use App\Models\RequestResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to whichever side of a donation *hasn't* confirmed yet, the moment
 * the other side does — a nudge so mutual confirmation (and DonationConfirmed)
 * doesn't stall on one party simply forgetting to close the loop.
 */
class DonationConfirmationPending extends Notification implements ShouldQueue
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

        if ($notifiable->id === $this->response->donor_id) {
            return (new MailMessage)
                ->subject('Please confirm your donation')
                ->greeting("Hi {$notifiable->name},")
                ->line("The requester confirmed your donation for the request at {$bloodRequest->hospital_name}.")
                ->line('Please confirm on your end too so it counts toward your donation history.')
                ->action('Confirm donation', route('requests.show', $bloodRequest));
        }

        return (new MailMessage)
            ->subject('Please confirm the donation')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your donor confirmed the donation for your request at {$bloodRequest->hospital_name}.")
            ->line('Please confirm on your end too so it counts toward their donation history.')
            ->action('Confirm donation', route('requests.show', $bloodRequest));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bloodRequest = $this->response->bloodRequest;

        return [
            'type' => 'donation_confirmation_pending',
            'request_id' => $bloodRequest->id,
            'message' => $notifiable->id === $this->response->donor_id
                ? "The requester confirmed your donation at {$bloodRequest->hospital_name} — please confirm your side too."
                : "Your donor confirmed the donation for your request at {$bloodRequest->hospital_name} — please confirm your side too.",
        ];
    }
}
