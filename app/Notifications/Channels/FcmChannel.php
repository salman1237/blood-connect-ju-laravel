<?php

namespace App\Notifications\Channels;

use App\Services\Fcm\FcmSender;
use App\Services\Fcm\FcmSendResult;
use Illuminate\Notifications\Notification;

/**
 * Generic push channel — any Notification that defines toFcm(): array
 * (['title' => ..., 'body' => ..., 'data' => [...]]) and lists
 * FcmChannel::class in via() gets pushed to every device the notifiable has
 * registered (User::pushTokens). Tokens FCM reports as unregistered/invalid
 * are pruned immediately.
 */
class FcmChannel
{
    public function __construct(private FcmSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm') || ! method_exists($notifiable, 'pushTokens')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        foreach ($notifiable->pushTokens as $pushToken) {
            $result = $this->sender->send(
                $pushToken->token,
                $payload['title'],
                $payload['body'],
                $payload['data'] ?? [],
            );

            if ($result === FcmSendResult::InvalidToken) {
                $pushToken->delete();
            }
        }
    }
}
