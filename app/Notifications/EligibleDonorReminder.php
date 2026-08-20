<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Periodic re-engagement email — sent by the donors:remind-eligible command
 * to donors who are eligible to donate again but haven't been reminded
 * recently (App\Models\DonorProfile::scopeDueForReminder).
 */
class EligibleDonorReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $matchingOpenRequests) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailNotifications() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("You're eligible to donate again")
            ->greeting("Hi {$notifiable->name},")
            ->line("It's been a while since your last donation (or you haven't donated yet) — you're eligible to give blood again.");

        if ($this->matchingOpenRequests > 0) {
            $message->line('There '.($this->matchingOpenRequests === 1 ? 'is' : 'are')." currently {$this->matchingOpenRequests} open ".str('request')->plural($this->matchingOpenRequests).' you could help with.');
        }

        return $message
            ->action('Browse open requests', route('dashboard'))
            ->line('You can update your donor profile at any time from your account settings.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'eligible_donor_reminder',
            'message' => "You're eligible to donate again — {$this->matchingOpenRequests} open requests match your blood group.",
        ];
    }
}
