<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('requests:expire')->hourly();

// Cooldown lives in DonorProfile::scopeDueForReminder (30 days), so the
// schedule only needs to run often enough to catch everyone within that
// window — weekly is plenty and keeps this from ever double-sending.
Schedule::command('donors:remind-eligible')->weekly()->mondays()->at('09:00');

// Shared hosting has no persistent queue:work daemon (no supervisor access),
// so queued jobs (notifications, etc.) are drained via the same cron that
// already fires schedule:run every minute, instead of a dedicated worker.
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
