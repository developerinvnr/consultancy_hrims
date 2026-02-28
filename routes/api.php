<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

Route::get('/party-attendance-monthly', [AttendanceController::class, 'partyAttendanceMonthly']);