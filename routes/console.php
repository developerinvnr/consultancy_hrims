<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ PAN Alert (every minute)
Schedule::command('alert:pan-inoperative')->dailyAt('10:30');

// ✅ Approval Reminder (daily at 10 AM)
Schedule::command('approval:reminders')->dailyAt('10:00');

/*
|--------------------------------------------------------------------------
| New Reminder Jobs
|--------------------------------------------------------------------------
*/

// Agreement upload pending reminder (every day)
Schedule::command('agreement:upload-reminders')
    ->dailyAt('11:00');


// Courier details pending reminder (every day)
Schedule::command('courier:details-reminders')
    ->dailyAt('10:00');


// Attendance pending reminder (every day morning)
Schedule::command('attendance:pending-reminders')
    ->dailyAt('09:30');


// Correction escalation reminder (every 24 hrs if still pending)
Schedule::command('correction:reminders')
    ->dailyAt('10:00');
