<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Identical to Laravel's built-in VerifyEmail, except queued. Every other
 * notification in this app already implements ShouldQueue — this one was
 * missed because it's dispatched by the framework's own MustVerifyEmail
 * trait rather than a hand-written notify() call. Sending it synchronously
 * meant a mail failure (bad recipient domain, a transient SMTP hiccup) took
 * the whole registration request down with a 500 even though the account
 * had already been created. Queuing routes it through the same
 * `queue:work --stop-when-empty` cron path (routes/console.php) every
 * other notification already uses.
 */
class QueuedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Unconditional 'mail' + push, same as the base class's unconditional
     * 'mail' — this is required-account-setup, not a preference-gated
     * notification, so it isn't gated on wantsEmailNotifications() the way
     * the domain notifications are.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', FcmChannel::class];
    }

    /**
     * No deep-link target — the signed verification URL is a browser-only
     * route, not something the Android app has a screen to consume. This is
     * purely a "go check your email" nudge; tapping just opens the app.
     *
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Verify your email',
            'body' => 'Check your inbox to verify your Blood Connect JU email address.',
            'data' => ['type' => 'verify_email'],
        ];
    }
}
