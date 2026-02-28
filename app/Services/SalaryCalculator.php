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
            return [
                'monthly_salary'      => (float) $candidate->remuneration_per_month,
                'per_day_salary'      => 0,
                'total_working_days'  => 0,
                'paid_days'           => 0,
                'absent_days'         => 0,
                'approved_sundays'    => 0,
                'deduction_amount'    => 0,
                'extra_amount'        => 0,
                'net_pay'             => 0,
            ];
        }

        // 2️⃣ Month stats
       

        // Detect contract start & end month
        $contractStart = $candidate->contract_start_date
            ? date('Y-m', strtotime($candidate->contract_start_date))
            : null;

        $contractEnd = $candidate->contract_end_date
            ? date('Y-m', strtotime($candidate->contract_end_date))
            : null;

        $currentMonth = date('Y-m', strtotime("$year-$month-01"));

        

        // 3️⃣ Salary base
        $monthlySalary = (float) $candidate->remuneration_per_month;
        if ($monthlySalary <= 0) {
            throw new Exception("Salary not defined");
        }

        $totalDays = \Carbon\Carbon::create($year, $month)->daysInMonth;

        // FIXED rule: always 26 days salary base
        $workingDays = 26;

        $perDay = $monthlySalary / 26;
        // 4️⃣ Count paid & absent WORKING days only
        $presentDays = 0;
        $absentDays = 0;

        for ($d = 1; $d <= $totalDays; $d++) {

            if (date('w', strtotime("$year-$month-$d")) == 0) {
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

        // FIXED working days rule
        $workingDays = 26;

        // Paid days = presentDays (not workingDays - absentDays)
        $paidDays = $presentDays;

        // Absent days = workingDays - paidDays
        $absentDays = $workingDays - $paidDays;

        if ($absentDays < 0) {
            $absentDays = 0;
        }

        if ($paidDays > $workingDays) {
            $paidDays = $workingDays;
        }

        // 5️⃣ Approved Sunday work (EXTRA PAY)
        $approvedSundays = SundayWorkRequest::where('candidate_id', $candidate->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'approved')
            ->count();

        $extraAmount = $approvedSundays * $perDay;

        // 6️⃣ Net Pay
        if ($paidDays >= $workingDays) {
            $netPay = $monthlySalary + $extraAmount;
        } else {
            $netPay = ($paidDays * $perDay) + $extraAmount;
        }

        // 7️⃣ Final rounded values (ONLY here)
        return [
            'monthly_salary'   => round($monthlySalary),
            'per_day_salary'   => round($perDay),

            'total_working_days' => $workingDays,
            'paid_days'        => $paidDays,
            'absent_days'      => $absentDays,

            'approved_sundays' => $approvedSundays,

            'deduction_amount' => round($absentDays * $perDay),
            'extra_amount'     => round($extraAmount),

            'net_pay'          => round($netPay),
        ];
    }
}
