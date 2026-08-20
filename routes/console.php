<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('requests:expire')->hourly();

// Shared hosting has no persistent queue:work daemon (no supervisor access),
// so queued jobs (notifications, etc.) are drained via the same cron that
// already fires schedule:run every minute, instead of a dedicated worker.
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
