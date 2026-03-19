<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ PAN Alert (every minute)
//Schedule::command('alert:pan-inoperative')->dailyAt('14:00');
Schedule::command('alert:pan-inoperative')->everyMinute();

// ✅ Approval Reminder (daily at 10 AM)
Schedule::command('approval:reminders')->dailyAt('10:00');