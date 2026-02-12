<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CandidateMaster;
use App\Models\LeaveBalance;
use App\Models\SundayWorkRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceController extends Controller
{
    /**
     * Display attendance page
     */
    public function index()
    {
        return view('hr-admin.attendance.index');
    }

    /**
     * Get attendance data for month
     */
    public function getAttendance(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'employee_type' => 'nullable|string'
        ]);

        try {
            $user = Auth::user();
            $month = $request->month;
            $year = $request->year;
            $employeeType = $request->employee_type;
            $currentDate = Carbon::now();

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // Get active candidates
            $query = CandidateMaster::select([
                'id as candidate_id',
                'candidate_code',
                'candidate_name',
                'requisition_type',
                'remuneration_per_month',
                'contract_start_date',
                'contract_end_date',
                'leave_credited',
                'reporting_manager_employee_id'
            ])
                ->whereIn('final_status', ['A', 'D'])
                ->whereNotNull('contract_start_date')
                ->where('contract_start_date', '<=', Carbon::create($year, $month)->endOfMonth());

            // Filter based on user role
            if (!$user->hasRole('hr_admin')) {
                // For non-HR admins, show only candidates they manage
                $query->where('reporting_manager_employee_id', $user->emp_id);
            }

            // Filter by employee type if provided and not 'all'
            if ($employeeType && $employeeType !== 'all') {
                $query->where('requisition_type', $employeeType);
            }
            $query->where(function ($q) use ($year, $month) {
                $monthStart = Carbon::create($year, $month, 1)->startOfMonth();

                $q->whereNull('contract_end_date')
                ->orWhere('contract_end_date', '>=', $monthStart);
            });
            $candidates = $query->get();

            // If user is not HR admin and no candidates found, return empty with message
            if (!$user->hasRole('hr_admin') && $candidates->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create($year, $month)->format('F'),
                        'days_in_month' => $daysInMonth,
                        'candidates' => [],
                        'message' => 'No team members found under your management.'
                    ]
                ]);
            }

            $result = [
                'year' => $year,
                'month' => $month,
                'month_name' => Carbon::create($year, $month)->format('F'),
                'days_in_month' => $daysInMonth,
                'current_date' => $currentDate->format('Y-m-d'),
                'current_day' => $currentDate->day,
                'candidates' => []
            ];

            foreach ($candidates as $index => $candidate) {
                $attendance = Attendance::where('candidate_id', $candidate->candidate_id)
                    ->where('Month', $month)
                    ->where('Year', $year)
                    ->first();

                $leaveBalance = LeaveBalance::where('CandidateID', $candidate->candidate_id)
                    ->where('calendar_year', $year)
                    ->first();

                // Get attendance for each day
                $dayAttendance = [];
                $totalPresent = 0;
                $totalAbsent = 0;
                $totalCL = 0;
                $totalLWP = 0;
                $totalOD = 0;
                $totalCH = 0;

                if ($attendance) {
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $column = "A" . $day;
                        $status = $attendance->$column;
                        $dayAttendance[$day] = $status;

                        // Count statuses
                        switch ($status) {
                            case 'P':
                                $totalPresent++;
                                break;
                            case 'A':
                                $totalAbsent++;
                                break;
                            case 'CL':
                                $totalCL++;
                                break;
                            case 'LWP':
                                $totalLWP++;
                                break;
                            case 'OD':
                                $totalOD++;
                                break;
                            case 'CH':
                                $totalCH++;
                                break;
                        }
                    }
                }

                // Calculate CL remaining for Contractual candidates
                $clRemaining = 0;
                if ($candidate->requisition_type === 'Contractual') {
                    if ($leaveBalance) {
                        // Calculate CL remaining from existing leave balance record
                        $clRemaining = max(0, $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized);
                    } else {
                        // If no leave balance record exists, we need to create one or calculate from candidate data
                        $joiningDate = Carbon::parse($candidate->contract_start_date);
                        $joiningYear = $joiningDate->year;

                        if ($joiningYear < $year) {
                            // Joined in previous year - full 12 days
                            $clRemaining = 12;
                        } else if ($joiningYear == $year) {
                            // Joined in current year - check if before current month
                            if ($joiningDate->month <= $month) {
                                // Eligible for prorated leave
                                $eligibleMonths = 13 - $joiningDate->month;
                                $clRemaining = min($eligibleMonths, 12);
                            } else {
                                // Not eligible yet (joining in future month)
                                $clRemaining = 0;
                            }
                        } else {
                            // Joining in future year
                            $clRemaining = 0;
                        }

                        // If candidate has leave_credited value, use it
                        if ($candidate->leave_credited && $candidate->leave_credited > 0) {
                            $clRemaining = $candidate->leave_credited;
                        }
                    }
                } else {
                    // Non-contractual candidates should have 0 CL
                    $clRemaining = 0;
                }

                $result['candidates'][] = [
                    'sno' => $index + 1,
                    'candidate_id' => $candidate->candidate_id,
                    'candidate_code' => $candidate->candidate_code,
                    'candidate_name' => $candidate->candidate_name,
                    'requisition_type' => $candidate->requisition_type,
                    'leave_credited' => $candidate->leave_credited ?: 0,
                    'contract_end_date' => $candidate->contract_end_date
                    ? Carbon::parse($candidate->contract_end_date)->format('Y-m-d')
                    : null,
                    'attendance' => $dayAttendance,
                    'total_present' => $totalPresent,
                    'total_absent' => $totalAbsent,
                    'cl_used' => $totalCL,
                    'lwp_days' => $totalLWP,
                    'od_days' => $totalOD,
                    'ch_days' => $totalCH,
                    'cl_remaining' => $clRemaining,
                    'daily_rate' => $candidate->remuneration_per_month ? $candidate->remuneration_per_month / 26 : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading attendance data: ' . $e->getMessage()
            ]);
        }
    }

    public function updateAttendance(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'candidate_id' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'attendance' => 'required|json'
        ]);

        DB::beginTransaction();

        try {

            $candidateId = $request->candidate_id;
            $month = $request->month;
            $year = $request->year;
            $attendanceData = json_decode($request->attendance, true);
            $user = Auth::user();
            $today = Carbon::today();

            $candidate = CandidateMaster::findOrFail($candidateId);
            $isContractual = $candidate->requisition_type === 'Contractual';
            $isHRAdmin = $user->hasRole('admin') || $user->hasRole('hr_admin');
            $isReportingManager = $user->emp_id &&
                $candidate->reporting_manager_employee_id == $user->emp_id;

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $isCurrentMonth = ($today->month == $month && $today->year == $year);

            /* ---------------- ROLE DATE VALIDATION ---------------- */

            if (!$isHRAdmin && $isReportingManager && $isCurrentMonth) {

                $allowedDays = [];
                $cursor = $today->copy();

                while (count($allowedDays) < 7) {
                    if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                        $allowedDays[] = $cursor->day;
                    }
                    $cursor->subDay();
                }

                foreach ($attendanceData as $day => $status) {
                    if ($status === null || $status === '') continue;

                    if (!in_array((int)$day, $allowedDays)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Reporting Managers can update only last 7 working days'
                        ]);
                    }
                }
            }

            /* ---------------- ATTENDANCE RECORD ---------------- */

            $attendance = Attendance::firstOrNew([
                'candidate_id' => $candidateId,
                'month' => $month,
                'year' => $year
            ]);

            // 🔥 THIS IS REQUIRED
            $attendance->candidate_id = $candidateId;
            $attendance->month = $month;
            $attendance->year = $year;

            /* ---------------- LEAVE BALANCE ---------------- */

            $leaveBalance = null;
            if ($isContractual) {
                $leaveBalance = LeaveBalance::firstOrCreate(
                    ['CandidateID' => $candidateId, 'calendar_year' => $year],
                    [
                        'opening_cl_balance' => $candidate->leave_credited ?? 0,
                        'cl_utilized' => 0,
                        'lwp_days_accumulated' => 0,
                        'contract_start_date' => $candidate->contract_start_date
                    ]
                );
            }

            $availableCL = $leaveBalance
                ? $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized
                : 0;

            /* ---------------- TOTALS ---------------- */

            $totalPresent = 0;
            $totalAbsent  = 0;
            $totalCL = 0;
            $totalCH = 0;
            $totalOD = 0;
            $totalLWP = 0;

            /* ---------------- DAY LOOP ---------------- */
            $contractEndDate = $candidate->contract_end_date
            ? Carbon::parse($candidate->contract_end_date)
            : null;

            for ($day = 1; $day <= $daysInMonth; $day++) {

                $status = $attendanceData[$day] ?? null;
                $date = Carbon::create($year, $month, $day);

                  if ($contractEndDate && $date->greaterThan($contractEndDate)) {
                        if (!empty($status)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Attendance cannot be filled after contract end date'
                            ]);
                        }

                        // force clear
                        $attendance->{"A{$day}"} = null;
                        continue;
                    }

                if ($date->dayOfWeek === Carbon::SUNDAY && $status !== 'P') {
                    $status = 'W';
                }

                if (!$isContractual && in_array($status, ['CL', 'CH', 'OD', 'HF'])) {
                    $status = 'A';
                }

                if ($isContractual) {
                    if ($status === 'CH' && $availableCL < 0.5) {
                        $status = 'HF';
                    }
                    if ($status === 'CL' && $availableCL < 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient CL balance'
                        ]);
                    }
                }

                $attendance->{"A{$day}"} = $status;

                switch ($status) {
                    case 'P':
                    case 'OD':
                    case 'CL':
                    case 'H':
                        $totalPresent += 1;
                        break;

                    case 'CH':
                        $totalPresent += 1;
                        $totalCH += 0.5;
                        $availableCL -= 0.5;
                        break;

                    case 'HF':
                        $totalPresent += 0.5;
                        $totalAbsent += 0.5;
                        break;

                    case 'A':
                        $totalAbsent += 1;
                        break;

                    case 'W':
                        break;
                }

                if ($status === 'CL') {
                    $totalCL += 1;
                    $availableCL -= 1;
                }

                if ($status === 'OD') {
                    $totalOD++;
                }
            }

            /* ---------------- SAVE ---------------- */

            $attendance->total_present = $totalPresent;
            $attendance->total_absent  = $totalAbsent;
            $attendance->total_cl      = $totalCL;
            $attendance->total_ch      = $totalCH;
            $attendance->total_od      = $totalOD;
            $attendance->total_lwp     = $totalLWP;
            $attendance->submitted_by  = $user->id;
            $attendance->status        = 'submitted';
            $attendance->save();

            if ($leaveBalance) {
                $leaveBalance->cl_utilized =
                    $leaveBalance->opening_cl_balance - $availableCL;
                $leaveBalance->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'cl_remaining' => $availableCL,
                'od_days' => $totalOD,
                'ch_days' => $totalCH
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



    // public function updateAttendance(Request $request)
    // {
    //     $request->validate([
    //         'candidate_id' => 'required|integer',
    //         'month' => 'required|integer|between:1,12',
    //         'year' => 'required|integer',
    //         'attendance' => 'required|json'
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $candidateId = $request->candidate_id;
    //         $month = $request->month;
    //         $year = $request->year;
    //         $attendanceData = json_decode($request->attendance, true);
    //         $userId = Auth::id();
    //         $currentDate = Carbon::now();

    //         // Get current user
    //         $user = Auth::user();

    //         // Check user roles
    //         $isHRAdmin = $user->hasRole('hr_admin') || $user->hasRole('admin');

    //         // Get candidate details first
    //         $candidate = CandidateMaster::find($candidateId);
    //         if (!$candidate) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Candidate not found'
    //             ]);
    //         }

    //         // Check if current user is the reporting manager for this candidate
    //         $isReportingManager = ($user->emp_id && $candidate->reporting_manager_employee_id)
    //             && ($user->emp_id == $candidate->reporting_manager_employee_id);

    //         $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    //         $currentDay = $currentDate->day;
    //         $currentMonth = $currentDate->month;
    //         $currentYear = $currentDate->year;

    //         // Get selected month details
    //         $selectedMonthDate = Carbon::create($year, $month, 1);
    //         $isCurrentMonth = ($year == $currentYear && $month == $currentMonth);
    //         $isPastMonth = ($year < $currentYear) || ($year == $currentYear && $month < $currentMonth);

    //         // RESTRICTIONS FOR REPORTING MANAGERS
    //         if (!$isHRAdmin && $isReportingManager) {
    //             // 1. Cannot fill future attendance
    //             if ($year > $currentYear || ($year == $currentYear && $month > $currentMonth)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Reporting Managers cannot fill attendance for future months'
    //                 ]);
    //             }

    //             // 2. Cannot fill previous month attendance
    //             if ($isPastMonth) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Reporting Managers cannot fill attendance for previous months'
    //                 ]);
    //             }
    //             if ($isCurrentMonth) {

    //                 $today = Carbon::today();
    //                 $allowedDays = [];
    //                 $dateCursor = $today->copy();

    //                 // Collect last 7 working days (exclude Sundays)
    //                 while (count($allowedDays) < 7) {
    //                     if ($dateCursor->dayOfWeek !== Carbon::SUNDAY) {
    //                         $allowedDays[] = $dateCursor->day;
    //                     }
    //                     $dateCursor->subDay();
    //                 }

    //                 foreach ($attendanceData as $day => $status) {

    //                     // Ignore untouched days
    //                     if ($status === null || $status === '') {
    //                         continue;
    //                     }

    //                     if (!in_array((int)$day, $allowedDays)) {
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => 'Reporting Managers can only fill attendance for the last 7 working days'
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         $isContractual = $candidate->requisition_type === 'Contractual';
    //         $joiningDate = Carbon::parse($candidate->contract_start_date);

    //         // Check if candidate was active in this month
    //         if ($joiningDate->year > $year || ($joiningDate->year == $year && $joiningDate->month > $month)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Candidate was not active in this month'
    //             ]);
    //         }

    //         // Get or create attendance record
    //         $attendance = Attendance::firstOrNew([
    //             'candidate_id' => $candidateId,
    //             'Month' => $month,
    //             'Year' => $year
    //         ]);

    //         if (!$attendance->exists) {
    //             $attendance->candidate_id = $candidateId;
    //             $attendance->Month = $month;
    //             $attendance->Year = $year;
    //         }

    //         // Get existing attendance data to compare changes
    //         $existingAttendance = [];
    //         for ($day = 1; $day <= $daysInMonth; $day++) {
    //             $column = "A" . $day;
    //             $existingAttendance[$day] = $attendance->exists ? $attendance->$column : null;
    //         }

    //         // Count various leave types in new attendance data
    //         $newCLCount = 0;
    //         $newCHCount = 0;
    //         $newODCount = 0;

    //         foreach ($attendanceData as $status) {
    //             if ($status === 'CL') {
    //                 $newCLCount++;
    //             } elseif ($status === 'CH') {
    //                 $newCHCount++;
    //             } elseif ($status === 'OD') {
    //                 $newODCount++;
    //             }
    //         }

    //         // Get existing counts from current attendance
    //         $existingCLCount = 0;
    //         $existingCHCount = 0;
    //         $existingODCount = 0;

    //         foreach ($existingAttendance as $status) {
    //             if ($status === 'CL') {
    //                 $existingCLCount++;
    //             } elseif ($status === 'CH') {
    //                 $existingCHCount++;
    //             } elseif ($status === 'OD') {
    //                 $existingODCount++;
    //             }
    //         }

    //         // Calculate net changes
    //         $clChange = $newCLCount - $existingCLCount;
    //         $chChange = $newCHCount - $existingCHCount;
    //         $odChange = $newODCount - $existingODCount;

    //         // Validate leave application based on user role and availability
    //         if ($isContractual && ($clChange != 0 || $chChange != 0)) {
    //             // Get or create leave balance
    //             $leaveBalance = LeaveBalance::firstOrNew([
    //                 'CandidateID' => $candidateId,
    //                 'calendar_year' => $year
    //             ]);

    //             if (!$leaveBalance->exists) {
    //                 $leaveBalance->opening_cl_balance = $candidate->leave_credited ?: 0;
    //                 $leaveBalance->cl_utilized = 0;
    //                 $leaveBalance->lwp_days_accumulated = 0;
    //                 $leaveBalance->contract_start_date = $joiningDate;
    //                 $leaveBalance->save();
    //             }

    //             // Calculate current available CL
    //             $availableCL = $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized;

    //             // Calculate total CL units needed (CH counts as 0.5 CL)
    //             $totalCLNeeded = $clChange + ($chChange * 0.5);

    //             // ROLE-BASED VALIDATION
    //             if ($totalCLNeeded > 0) { // Only validate when adding CL/CH
    //                 if ($isReportingManager) {
    //                     // Reporting Manager validations

    //                     // 1. Max 2 days CL per request (including CH as half days)
    //                     $totalRequestedDays = $clChange + ($chChange * 0.5);
    //                     if ($totalRequestedDays > 2) {
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => 'Reporting Managers can apply maximum 2 CL days (or equivalent) per request'
    //                         ]);
    //                     }

    //                     // 2. Max 2 days CL per month (existing + new)
    //                     $currentMonthCL = $existingCLCount + ($existingCHCount * 0.5) + $totalRequestedDays;
    //                     if ($currentMonthCL > 2) {
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => "Reporting Managers can apply maximum 2 CL days per month"
    //                         ]);
    //                     }

    //                     // 3. Check if enough CL balance is available
    //                     if ($totalCLNeeded > $availableCL) {
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => "Insufficient CL balance. Available: {$availableCL} days, Requested: {$totalCLNeeded} days"
    //                         ]);
    //                     }
    //                 } else if ($isHRAdmin) {
    //                     // HR Admin validations - more flexible
    //                     // Only check if enough CL balance is available
    //                     if ($totalCLNeeded > $availableCL) {
    //                         return response()->json([
    //                             'success' => false,
    //                             'message' => "Insufficient CL balance. Available: {$availableCL} days, Requested: {$totalCLNeeded} days"
    //                         ]);
    //                     }
    //                 } else {
    //                     // Other users (if any) - no permission
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => 'You do not have permission to apply CL/CH'
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Initialize counts
    //         $totalPresent = 0;
    //         $totalAbsent = 0;
    //         $totalCL = 0;
    //         $totalLWP = 0;
    //         $totalOD = 0;
    //         $totalCH = 0;

    //         // Update day columns
    //         for ($day = 1; $day <= $daysInMonth; $day++) {
    //             $column = "A" . $day;
    //             $oldStatus = $existingAttendance[$day] ?? null;
    //             $newStatus = $attendanceData[$day] ?? null;

    //             // If status is empty string, convert to null
    //             if ($newStatus === "" || $newStatus === null) {
    //                 $newStatus = null;
    //             } else {
    //                 // Validate status based on candidate type
    //                 if (!$isContractual) {
    //                     // Non-contractual candidates cannot have CL, CH, or OD
    //                     if (in_array($newStatus, ['CL', 'CH', 'OD'])) {
    //                         $newStatus = 'A';
    //                     }
    //                 }
    //             }

    //             // For reporting managers, validate future dates
    //             if (!$isHRAdmin && $isReportingManager && $isCurrentMonth) {
    //                 $date = Carbon::create($year, $month, $day);
    //                 $today = Carbon::today();

    //                 // Cannot fill future dates
    //                 if ($date->greaterThan($today)) {
    //                     $newStatus = null;
    //                 }

    //                 // Can only fill last 7 days
    //                 $daysDiff = $today->diffInDays($date);
    //                 if ($daysDiff > 7) {
    //                     $newStatus = $oldStatus; // Keep existing value or null
    //                 }
    //             }

    //             // Auto-set Sundays to 'W' unless it's 'P' (Sunday work)
    //             $date = Carbon::create($year, $month, $day);
    //             if ($date->dayOfWeek === Carbon::SUNDAY && $newStatus !== 'P') {
    //                 $newStatus = 'W';
    //             }

    //             $attendance->$column = $newStatus;

    //             // Count statuses
    //             if ($newStatus !== null) {
    //                 switch ($newStatus) {
    //                     case 'P':
    //                         $totalPresent++;
    //                         break;
    //                     case 'A':
    //                         $totalAbsent++;
    //                         break;
    //                     case 'CL':
    //                         $totalCL++;
    //                         break;
    //                     case 'LWP':
    //                         $totalLWP++;
    //                         break;
    //                     case 'OD':
    //                         $totalOD++;
    //                         $totalPresent++; // OD counts as present
    //                         break;
    //                     case 'CH':
    //                         $totalCH++;
    //                         $totalPresent++; // CH counts as present
    //                         break;
    //                     case 'H':
    //                         $totalPresent++; // Holiday counts as present
    //                         break;
    //                     case 'W':
    //                         // Sunday - no counting
    //                         break;
    //                 }
    //             }
    //         }

    //         // Update calculated fields
    //         $attendance->total_present = $totalPresent;
    //         $attendance->total_absent = $totalAbsent;
    //         $attendance->total_cl = $totalCL;
    //         $attendance->total_lwp = $totalLWP;
    //         $attendance->total_od = $totalOD;
    //         $attendance->total_ch = $totalCH;
    //         $attendance->submitted_by = $userId;
    //         $attendance->status = 'submitted';

    //         $attendance->save();

    //         // Process leave deduction/restoration for contractual candidates
    //         $warning = null;
    //         $clRemaining = null;

    //         if ($isContractual) {
    //             $leaveResult = $this->processLeaveDeduction($candidateId, $month, $year, $existingAttendance, $attendanceData);
    //             if ($leaveResult['warning']) {
    //                 $warning = $leaveResult['warning'];
    //             }
    //             $clRemaining = $leaveResult['new_cl'];
    //         }

    //         $clUsed = null;
    //         if ($isContractual) {
    //             $leaveBalance = LeaveBalance::where('CandidateID', $candidateId)
    //                 ->where('calendar_year', $year)
    //                 ->first();

    //             if ($leaveBalance) {
    //                 $clUsed = $leaveBalance->cl_utilized;
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Attendance updated successfully',
    //             'warning' => $warning,
    //             'cl_remaining' => $clRemaining,
    //             'cl_used' => $clUsed,
    //             'od_days' => $totalOD,
    //             'ch_days' => $totalCH
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('Attendance update error: ' . $e->getMessage());
    //         \Log::error('Request data: ', $request->all());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error updating attendance: ' . $e->getMessage()
    //         ]);
    //     }
    // }

    /**
     * Process leave deduction/restoration for contractual candidates
     * Updated to handle CH (half day) as 0.5 CL
     */
    private function processLeaveDeduction($candidateId, $month, $year, $oldAttendance, $newAttendance)
    {
        try {
            // Get leave balance
            $leaveBalance = LeaveBalance::where('CandidateID', $candidateId)
                ->where('calendar_year', $year)
                ->first();

            if (!$leaveBalance) {
                return [
                    'success' => false,
                    'warning' => 'Leave balance record not found',
                    'new_cl' => null
                ];
            }

            $clChanges = 0; // Positive = adding CL, Negative = removing CL (in full day units)
            $chChanges = 0; // Positive = adding CH, Negative = removing CH (in half day units)
            $lwpChanges = 0; // Positive = adding LWP, Negative = removing LWP

            // Calculate changes for each day
            foreach ($newAttendance as $day => $newStatus) {
                $oldStatus = $oldAttendance[$day] ?? null;

                // Skip if no change
                if ($oldStatus === $newStatus) {
                    continue;
                }

                // Handle CL changes (full day)
                if ($oldStatus === 'CL' && $newStatus !== 'CL') {
                    // Removing CL - restore 1 day leave balance
                    $clChanges--;
                } elseif ($oldStatus !== 'CL' && $newStatus === 'CL') {
                    // Adding CL - deduct 1 day leave balance
                    $clChanges++;
                }

                // Handle CH changes (half day)
                if ($oldStatus === 'CH' && $newStatus !== 'CH') {
                    // Removing CH - restore 0.5 day leave balance
                    $chChanges--;
                } elseif ($oldStatus !== 'CH' && $newStatus === 'CH') {
                    // Adding CH - deduct 0.5 day leave balance
                    $chChanges++;
                }

                // Handle LWP changes
                if ($oldStatus === 'LWP' && $newStatus !== 'LWP') {
                    // Removing LWP
                    $lwpChanges--;
                } elseif ($oldStatus !== 'LWP' && $newStatus === 'LWP') {
                    // Adding LWP
                    $lwpChanges++;
                }

                // Handle OD changes (no leave deduction, just counting)
                // OD is counted separately in the attendance table
            }

            $warning = null;

            // Convert CH changes to CL units (0.5 each)
            $totalCLUnitsNeeded = $clChanges + ($chChanges * 0.5);

            // Process CL and CH changes
            if ($totalCLUnitsNeeded > 0) {
                // Adding CL/CH - check if enough balance
                $availableCL = $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized;

                if ($totalCLUnitsNeeded <= $availableCL) {
                    // Enough balance - deduct from CL
                    $leaveBalance->cl_utilized += $totalCLUnitsNeeded;
                } else {
                    // Not enough CL balance - use what's available, rest as LWP
                    $clDeducted = $availableCL;
                    $lwpFromCL = $totalCLUnitsNeeded - $clDeducted;

                    $leaveBalance->cl_utilized += $clDeducted;
                    $leaveBalance->lwp_days_accumulated += $lwpFromCL;

                    $warning = "Insufficient CL balance. {$clDeducted} days deducted from CL, {$lwpFromCL} days marked as LWP.";
                }
            } elseif ($totalCLUnitsNeeded < 0) {
                // Removing CL/CH - restore leave balance (but not more than was utilized)
                $clRestored = min(abs($totalCLUnitsNeeded), $leaveBalance->cl_utilized);
                $leaveBalance->cl_utilized -= $clRestored;
            }

            // Process LWP changes (independent of CL/CH changes)
            $leaveBalance->lwp_days_accumulated += $lwpChanges;

            // Ensure non-negative values
            $leaveBalance->cl_utilized = max(0, $leaveBalance->cl_utilized);
            $leaveBalance->lwp_days_accumulated = max(0, $leaveBalance->lwp_days_accumulated);

            // Cannot utilize more than opening balance
            if ($leaveBalance->cl_utilized > $leaveBalance->opening_cl_balance) {
                $excess = $leaveBalance->cl_utilized - $leaveBalance->opening_cl_balance;
                $leaveBalance->cl_utilized = $leaveBalance->opening_cl_balance;
                $leaveBalance->lwp_days_accumulated += $excess;

                $warning = ($warning ? $warning . " " : "") .
                    "CL utilization exceeded opening balance. {$excess} days converted to LWP.";
            }

            $leaveBalance->save();

            // Calculate final CL remaining
            $finalCLRemaining = $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized;

            return [
                'success' => true,
                'warning' => $warning,
                'new_cl' => $finalCLRemaining,
                'lwp_days' => $leaveBalance->lwp_days_accumulated
            ];
        } catch (\Exception $e) {
            \Log::error('Leave deduction error: ' . $e->getMessage());
            return [
                'success' => false,
                'warning' => null,
                'error' => $e->getMessage(),
                'new_cl' => null
            ];
        }
    }

    /**
     * Get candidate attendance
     */
    public function getCandidateAttendance(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer'
        ]);

        try {
            $month = $request->month;
            $year = $request->year;
            $candidateId = $request->candidate_id;

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $attendance = Attendance::where('candidate_id', $candidateId)
                ->where('Month', $month)
                ->where('Year', $year)
                ->first();

            $attendanceData = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $column = "A" . $day;
                $attendanceData[$day] = $attendance ? $attendance->$column : null;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'month_name' => Carbon::create($year, $month)->format('F'),
                    'days_in_month' => $daysInMonth,
                    'attendance' => $attendanceData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading candidate attendance: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get Sundays for month
     */
    public function getSundays(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer'
        ]);

        try {
            $month = $request->month;
            $year = $request->year;

            $today = Carbon::today();
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $sundays = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {

                $date = Carbon::create($year, $month, $day);

                // ✅ Only past Sundays (or today if Sunday)
                if (
                    $date->dayOfWeek === Carbon::SUNDAY &&
                    $date->lessThanOrEqualTo($today)
                ) {
                    $sundays[] = [
                        'date' => $date->format('Y-m-d'),
                        'day' => $day,
                        'day_name' => $date->format('l')
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $sundays
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading Sundays: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Get active candidates
     */
    public function getActiveCandidates(Request $request)
    {
        try {
            $user = Auth::user();
            $isHRAdmin = $user->hasRole('hr_admin') || $user->hasRole('admin');

            $query = CandidateMaster::select([
                'id',
                'candidate_name',
                'requisition_type'
            ])
                ->where('final_status', 'A')
                ->whereNotNull('contract_start_date');

            // For non-HR admins, only show their team members
            if (!$isHRAdmin) {
                $query->where('reporting_manager_employee_id', $user->emp_id);
            }

            // Only Contractual candidates can have Sunday work
            $query->where('requisition_type', 'Contractual');

            $candidates = $query->orderBy('candidate_name')->get();

            return response()->json([
                'success' => true,
                'data' => $candidates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading candidates: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Submit Sunday work request - Only for Contractual candidates
     */
    public function submitSundayWork(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'sunday_dates' => 'required|array',
            'candidate_ids' => 'required|array',
            'remark' => 'required|string'
        ]);

        DB::beginTransaction();

        try {
            $month = $request->month;
            $year = $request->year;
            $sundayDates = $request->sunday_dates;
            $candidateIds = $request->candidate_ids;
            $remark = $request->remark;
            $user = Auth::user();

            // ✅ Decide approval based on role
            $isHrAdmin = $user->hasRole('hr_admin');

            foreach ($candidateIds as $candidateId) {

                $candidate = CandidateMaster::find($candidateId);
                if (!$candidate || $candidate->requisition_type !== 'Contractual') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sunday work is only allowed for Contractual candidates'
                    ]);
                }

                foreach ($sundayDates as $sundayDate) {

                    $existing = SundayWorkRequest::where('candidate_id', $candidateId)
                        ->where('sunday_date', $sundayDate)
                        ->first();

                    if ($existing) {
                        continue;
                    }

                    $sundayWork = new SundayWorkRequest();
                    $sundayWork->candidate_id = $candidateId;
                    $sundayWork->month = $month;
                    $sundayWork->year = $year;
                    $sundayWork->sunday_date = $sundayDate;
                    $sundayWork->remark = $remark;
                    $sundayWork->requested_by = $user->id;

                    // ✅ AUTO APPROVE IF HR
                    if ($isHrAdmin) {
                        $sundayWork->status = 'approved';
                        $sundayWork->approved_by = $user->id;
                        $sundayWork->approved_at = now();
                    } else {
                        $sundayWork->status = 'pending';
                    }

                    if ($request->hasFile('attachment')) {
                        $path = $request->file('attachment')
                            ->store('sunday-work-attachments', 'public');
                        $sundayWork->attachment_path = $path;
                    }

                    $sundayWork->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isHrAdmin
                    ? 'Sunday work approved successfully'
                    : 'Sunday work request submitted for approval'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error submitting Sunday work request: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export attendance data
     */
    public function export(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|digits:4',
            'employee_type' => 'nullable|string|in:all,Contractual,Consultant,Permanent'
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->back()->with('error', 'User not authenticated.');
            }

            // Log the export request
            \Log::info('Attendance Export Request:', [
                'user_id' => $user->id,
                'user_emp_id' => $user->emp_id,
                'user_roles' => $user->getRoleNames()->toArray(),
                'month' => $request->month,
                'year' => $request->year,
                'employee_type' => $request->employee_type
            ]);

            $month = $request->month;
            $year = $request->year;
            $employeeType = $request->employee_type ?? 'all';

            $monthName = Carbon::create($year, $month)->format('F');
            $filename = "Attendance_Report_{$monthName}_{$year}.xlsx";

            return Excel::download(
                new AttendanceExport($month, $year, $employeeType, $user),
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        } catch (\Exception $e) {
            \Log::error('Attendance Export Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to export attendance report. Please try again.');
        }
    }
}
