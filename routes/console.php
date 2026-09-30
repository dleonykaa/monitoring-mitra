<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-daily-progress-reminder')->dailyAt('08:00')->withoutOverlapping(10);
Schedule::command('app:send-deadline-reminder')->dailyAt('08:15')->withoutOverlapping(10);
Schedule::command('app:send-checkpoint-alerts')->dailyAt('08:20')->withoutOverlapping(10);
Schedule::command('app:sync-survey-status')->hourly()->withoutOverlapping(10);
