<?php

namespace App\Notifications;

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
}
