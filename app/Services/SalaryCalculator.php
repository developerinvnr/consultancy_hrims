<?php

namespace App\Services;

use App\Models\CandidateMaster;
use App\Models\Attendance;
use App\Models\SundayWorkRequest;
use Exception;

class SalaryCalculator
{
    public static function calculate(CandidateMaster $candidate, int $month, int $year): array
    {
        // 1. Fetch attendance
        $attendance = Attendance::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$attendance) {
            throw new Exception("Attendance not found for candidate {$candidate->candidate_code} ({$month}/{$year})");
        }

        // 2. Count total days & Sundays
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $sundays = 0;

        for ($d = 1; $d <= $totalDays; $d++) {
            if (date('w', strtotime("$year-$month-$d")) == 0) {
                $sundays++;
            }
        }

        $workingDays = $totalDays - $sundays;

        if ($workingDays <= 0) {
            throw new Exception("Invalid working days calculation");
        }

        // 3. Salary base
        $monthlySalary = (float) $candidate->remuneration_per_month;

        if ($monthlySalary <= 0) {
            throw new Exception("Salary not defined for {$candidate->candidate_code}");
        }

        $perDay = round($monthlySalary / $workingDays, 2);

        // 4. Attendance values
        $present = (int) $attendance->total_present;
        $cl      = (int) $attendance->total_cl;
        $absent  = (int) $attendance->total_absent;

        // 5. Approved Sundays
        $approvedSundays = SundayWorkRequest::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            //->where('status', 'approved')
            ->count();

        // 6. Paid days
        $paidDays = $present + $cl + $approvedSundays;

        // 7. Final salary
        $netPay = round($paidDays * $perDay, 2);

        // 8. Optional calculated fields (for UI display)
        $deduction = round(($absent) * $perDay, 2);
        $extra     = round($approvedSundays * $perDay, 2);

        return [
            'monthly_salary'    => $monthlySalary,
            'per_day_salary'    => $perDay,

			'total_days'        => $workingDays,
            'paid_days'         => $paidDays,
            'cl_days'           => $cl,
            'absent_days'       => $absent,
            

            'approved_sundays'  => $approvedSundays,

            // These two are optional, only for display
            'deduction_amount'  => $deduction,
            'extra_amount'      => $extra,
            'net_pay'           => $netPay,
        ];
    }
}
