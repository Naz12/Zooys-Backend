<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the monthly usage reset command to run daily
Schedule::command('subscriptions:reset-usage')->daily();

// Schedule cleanup of expired presentation files to run daily
Schedule::command('presentations:cleanup-expired')->daily();
