<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ PAN Alert (every minute)
Schedule::command('alert:pan-inoperative')->dailyAt('09:30')->timezone('Asia/Kolkata');

// ✅ Approval Reminder (daily at 10 AM)
Schedule::command('approval:reminders')->dailyAt('10:00')->timezone('Asia/Kolkata');

/*
|--------------------------------------------------------------------------
| New Reminder Jobs
|--------------------------------------------------------------------------
*/

// Agreement upload pending reminder (every day)
Schedule::command('agreement:upload-reminders')
    ->dailyAt('10:30')->timezone('Asia/Kolkata');


// Courier details pending reminder (every day)
Schedule::command('courier:details-reminders')
    ->dailyAt('11:00')->timezone('Asia/Kolkata');


// Attendance pending reminder (every day morning)
Schedule::command('attendance:pending-reminders')
    ->dailyAt('11:30')->timezone('Asia/Kolkata');


// Correction escalation reminder (every 24 hrs if still pending)
Schedule::command('correction:reminders')
    ->dailyAt('11:50')->timezone('Asia/Kolkata');
