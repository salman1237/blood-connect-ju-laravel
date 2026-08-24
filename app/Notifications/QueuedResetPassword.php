<?php

namespace App\Notifications;

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
}
