<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
            'title' => "{$this->urgencyLabel()} need for {$this->bloodRequest->blood_group} blood",
            'body' => "{$this->bloodRequest->hospital_name} needs {$this->bloodRequest->units_needed} unit(s) — a match for your blood group.",
            'data' => ['type' => 'new_matching_request', 'request_id' => $this->bloodRequest->id],
        ];
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
