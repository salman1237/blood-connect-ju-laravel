<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMatchingRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmailNotifications() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->urgencyLabel()} need for {$this->bloodRequest->blood_group} blood near you")
            ->greeting("Hi {$notifiable->name},")
            ->line("A new blood request needs {$this->bloodRequest->blood_group} — a match for your blood group.")
            ->line("Hospital: {$this->bloodRequest->hospital_name}".($this->bloodRequest->location ? ", {$this->bloodRequest->location}" : ''))
            ->line("Units needed: {$this->bloodRequest->units_needed} · Urgency: {$this->urgencyLabel()}")
            ->action('View request', route('requests.show', $this->bloodRequest))
            ->line('Not available to donate right now? You can turn this off in your profile.');
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

    private function urgencyLabel(): string
    {
        return match ($this->bloodRequest->urgency) {
            'critical' => 'Critical',
            'within_24h' => 'Within 24h',
            default => 'Planned',
        };
    }
}
