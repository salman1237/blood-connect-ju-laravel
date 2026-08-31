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

// A real persistent `queue:work` process now runs alongside Apache/Reverb
// (see the Dockerfile's supervisord config) — the once-a-minute
// queue:work --stop-when-empty cron this used to be is retired: it's what
// gated every queued notification (mail/FCM/broadcast) to up-to-60s
// latency, which made the new real-time broadcast channels pointless for
// anything still riding the queue (see .claude-progress.md). Kept here as
// a comment, not a schedule entry, so a future reader knows this was a
// deliberate removal, not an oversight.
