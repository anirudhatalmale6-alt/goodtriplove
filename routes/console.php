<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
| One cron entry drives all of it (see docs/DEPLOYMENT.md). Everything here
| is spread across the hour and marked withoutOverlapping, because this
| server also runs other sites: two collector passes or two model inferences
| stacking up is exactly what must not happen.
*/

// --- Video collector -------------------------------------------------
Schedule::command('gtl:collect --queries=3')
    ->hourlyAt(7)
    ->withoutOverlapping(30)
    ->runInBackground();

// videos.list is 1 unit per 50 ids, so metrics can be refreshed often.
Schedule::command('gtl:refresh-videos --limit=200')
    ->cron('21 */3 * * *')
    ->withoutOverlapping(30);

// The local model runs at night, off the visitor peak.
Schedule::command('gtl:classify')
    ->dailyAt('03:20')
    ->withoutOverlapping(60);

Schedule::command('gtl:rescore')
    ->dailyAt('04:10')
    ->withoutOverlapping(60);

// --- Queue (database driver: no Redis, no Supervisor on this server) ---
Schedule::command('queue:work --stop-when-empty --max-time=280 --tries=3')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();

// --- Security module ---------------------------------------------------
Schedule::command('security:center-health')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('security:backup-app')->dailyAt('02:30')->withoutOverlapping(120);

// --- Growth & operations module ----------------------------------------
Schedule::command('growth:health')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('growth:data-quality')->hourlyAt(35)->withoutOverlapping(30);
Schedule::command('growth:video-health')->hourlyAt(50)->withoutOverlapping(30);
Schedule::command('growth:analytics-rollup')->hourlyAt(5)->withoutOverlapping(30);
Schedule::command('growth:seo-sitemap')->dailyAt('05:00')->withoutOverlapping(60);

// --- Housekeeping -------------------------------------------------------
Schedule::command('auth:clear-resets')->daily();
Schedule::command('model:prune')->daily();
