<?php

namespace App\Services;

use App\Models\CandidateMaster;
use App\Models\Attendance;
use App\Models\SundayWorkRequest;
use Carbon\Carbon;
use Exception;

class SalaryCalculator
{
    public static function calculate(CandidateMaster $candidate, int $month, int $year): array
    {
        // 1️⃣ Fetch attendance
        $attendance = Attendance::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $monthlySalary = (float) $candidate->remuneration_per_month;

        if ($monthlySalary <= 0) {
            throw new Exception("Salary not defined");
        }

        if (!$attendance) {
            return [
                'monthly_salary'      => round($monthlySalary),
                'per_day_salary'      => 0,
                'total_working_days'  => 26,
                'paid_days'           => 0,
                'absent_days'         => 0,
                'approved_sundays'    => 0,
                'deduction_amount'    => 0,
                'extra_amount'        => 0,
                'net_pay'             => 0,
            ];
        }

        // 2️⃣ Fixed Salary Rule
        $workingDays = 26;
        $perDay = $monthlySalary / 26;

        $totalDays = Carbon::create($year, $month)->daysInMonth;

        // 3️⃣ Contract Start & End Dates
        $joinDate = $candidate->contract_start_date
            ? Carbon::parse($candidate->contract_start_date)
            : null;

        $endDate = $candidate->contract_end_date
            ? Carbon::parse($candidate->contract_end_date)
            : null;

        $presentDays = 0;
        $absentDays = 0;

        // 4️⃣ Attendance Loop
        for ($d = 1; $d <= $totalDays; $d++) {

            $currentDate = Carbon::create($year, $month, $d);

            // Skip Sundays
            if ($currentDate->dayOfWeek == Carbon::SUNDAY) {
                continue;
            }

            // Skip before joining
            if ($joinDate && $currentDate->lt($joinDate)) {
                continue;
            }

            // Skip after relieving
            if ($endDate && $currentDate->gt($endDate)) {
                continue;
            }

            $status = $attendance->{"A{$d}"} ?? null;

            switch ($status) {
                case 'P':
                case 'CL':
                case 'OD':
                case 'H':
                    $presentDays++;
                    break;

                case 'CH':
                    $presentDays += 0.5;
                    break;

                case 'A':
                case 'LWP':
                    $absentDays++;
                    break;
            }
        }

        $actualWorkingDays = $presentDays + $absentDays;

        // 5️⃣ Apply 26-Day Payroll Rule
        // 5️⃣ Apply Payroll Rule

        $joinedThisMonth = $joinDate &&
            $joinDate->month == $month &&
            $joinDate->year == $year;

        $leftThisMonth = $endDate &&
            $endDate->month == $month &&
            $endDate->year == $year;

        if ($actualWorkingDays == 0) {

            $paidDays = 0;
            $absentDays = 0;
        } elseif ($joinedThisMonth || $leftThisMonth) {

            // Pro-rated salary for join/exit month
            $paidDays = $presentDays;
        } else {

            // Normal payroll rule
            if ($absentDays == 0) {
                $paidDays = 26;
            } else {
                $paidDays = 26 - $absentDays;
            }
        }

        // 6️⃣ Sunday Work Extra
        $approvedSundays = SundayWorkRequest::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved')
            ->count();

        $extraAmount = $approvedSundays * $perDay;

        // 7️⃣ Final Net Pay
        $deductionAmount = (26 - $paidDays) * $perDay;
        $netPay = ($paidDays * $perDay) + $extraAmount;

        return [
            'monthly_salary'      => round($monthlySalary),
            'per_day_salary'      => round($perDay),
            'total_working_days'  => 26,
            'paid_days'           => round($paidDays, 2),
            'absent_days'         => round($absentDays, 2),
            'approved_sundays'    => $approvedSundays,
            'deduction_amount'    => round($deductionAmount),
            'extra_amount'        => round($extraAmount),
            'net_pay'             => round($netPay),
        ];
    }
}
