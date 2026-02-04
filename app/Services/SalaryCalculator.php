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
        // 1️⃣ Fetch attendance
        $attendance = Attendance::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$attendance) {
            throw new Exception("Attendance not found");
        }

        // 2️⃣ Month stats
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $sundays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            if (date('w', strtotime("$year-$month-$d")) == 0) {
                $sundays++;
            }
        }

        $workingDays = $totalDays - $sundays;
        if ($workingDays <= 0) {
            throw new Exception("Invalid working days");
        }

        // 3️⃣ Salary base
        $monthlySalary = (float) $candidate->remuneration_per_month;
        if ($monthlySalary <= 0) {
            throw new Exception("Salary not defined");
        }

        // ❗ DO NOT ROUND HERE
        $perDay = $monthlySalary / $workingDays;

        // 4️⃣ Count paid & absent WORKING days only
        $paidDays = 0;
        $absentDays = 0;

        for ($d = 1; $d <= $totalDays; $d++) {

            // Skip Sundays completely
            if (date('w', strtotime("$year-$month-$d")) == 0) {
                continue;
            }

            $status = $attendance->{"A{$d}"} ?? null;

            switch ($status) {
                case 'P':
                case 'CL':
                case 'OD':
                case 'H':
                    $paidDays += 1;
                    break;

                case 'CH':
                    $paidDays += 0.5;
                    break;

                case 'A':
                case 'LWP':
                    $absentDays += 1;
                    break;
            }
        }

        // 5️⃣ Approved Sunday work (EXTRA PAY)
        $approvedSundays = SundayWorkRequest::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved')
            ->count();

        $extraAmount = $approvedSundays * $perDay;

        // 6️⃣ Net Pay
        $netPay = ($paidDays * $perDay) + $extraAmount;

        // 7️⃣ Final rounded values (ONLY here)
        return [
            'monthly_salary'   => round($monthlySalary, 2),
            'per_day_salary'   => round($perDay, 2),

            'total_working_days' => $workingDays,
            'paid_days'        => $paidDays,
            'absent_days'      => $absentDays,

            'approved_sundays' => $approvedSundays,

            'deduction_amount' => round($absentDays * $perDay, 2),
            'extra_amount'     => round($extraAmount, 2),

            'net_pay'          => round($netPay, 2),
        ];
    }
}
