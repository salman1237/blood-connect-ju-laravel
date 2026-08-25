<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Same reasoning as QueuedVerifyEmail — the framework default sends
 * synchronously, so a mail failure here would 500 the forgot-password form
 * instead of just failing to deliver in the background.
 */
class QueuedResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Unconditional 'mail' + push, same as the base class's unconditional
     * 'mail' — a security-relevant notification like this shouldn't be
     * gated behind the user's general email-preference toggle.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', FcmChannel::class];
    }

    /**
     * Deliberately doesn't embed the reset token/URL — it's a signed,
     * browser-only route with no Android screen to consume it, and there's
     * no reason to duplicate a sensitive one-time link into a push payload
     * when the email already carries it. This is purely a security heads-up:
     * whoever is holding this device gets told a reset was requested, even
     * if they're not the one currently signed into the app.
     *
     * @return array{title: string, body: string, data: array<string, string>}
     */
    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Password reset requested',
            'body' => "If this wasn't you, you can ignore it — nothing changes until the link in your email is used.",
            'data' => ['type' => 'password_reset_requested'],
        ];
    }
}
