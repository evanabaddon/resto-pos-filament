<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Aktifkan pemrosesan antrean (Queue) setiap menit untuk Shared Hosting

Schedule::command('queue:work')->everyMinute();
