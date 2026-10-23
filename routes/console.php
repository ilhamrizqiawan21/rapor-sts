<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database otomatis (butuh cron `schedule:run`; atur lewat BACKUP_SCHEDULE / BACKUP_TIME).
match (config('backup.schedule')) {
    'daily' => Schedule::command('backup:run')->dailyAt(config('backup.time')),
    'weekly' => Schedule::command('backup:run')->weeklyOn(0, config('backup.time')),
    default => null,
};
