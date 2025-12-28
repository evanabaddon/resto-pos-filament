<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty')->everySecond();

// Re-engage inactive members every Monday at 9 AM
Schedule::command('loyalty:re-engage')->weeklyOn(1, '9:00');

// Check critical stock levels every 30 minutes
Schedule::job(new \App\Jobs\CheckCriticalStockJob)->everyThirtyMinutes();
