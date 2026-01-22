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

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            // Get active candidates
            $query = CandidateMaster::select([
                'id as candidate_id',
                'candidate_code',
                'candidate_name',
                'requisition_type',
                'remuneration_per_month',
                'date_of_joining',
                'leave_credited',
                'reporting_manager_employee_id'
            ])
                ->where('final_status', 'A')
                ->whereNotNull('date_of_joining')
                ->where('date_of_joining', '<=', Carbon::create($year, $month)->endOfMonth());

            // Filter based on user role
            if (!$user->hasRole('hr_admin')) {
                // For non-HR admins, show only candidates they manage
                $query->where('reporting_manager_employee_id', $user->emp_id);
            }

            // Filter by employee type if provided and not 'all'
            if ($employeeType && $employeeType !== 'all') {
                $query->where('requisition_type', $employeeType);
            }

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

                        // First, check if we should create a leave balance record
                        $joiningDate = Carbon::parse($candidate->date_of_joining);
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
                    'attendance' => $dayAttendance,
                    'total_present' => $totalPresent,
                    'total_absent' => $totalAbsent,
                    'cl_used' => $totalCL,
                    'lwp_days' => $totalLWP,
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
            $userId = Auth::id();

            // Get current user
            $user = Auth::user();

            // Check user roles
            $isHRAdmin = $user->hasRole('hr_admin') || $user->hasRole('admin');

            // Get candidate details first
            $candidate = CandidateMaster::find($candidateId);
            if (!$candidate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Candidate not found'
                ]);
            }

            // Check if current user is the reporting manager for this candidate
            $isReportingManager = ($user->emp_id && $candidate->reporting_manager_employee_id)
                && ($user->emp_id == $candidate->reporting_manager_employee_id);

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $isContractual = $candidate->requisition_type === 'Contractual';
            $joiningDate = Carbon::parse($candidate->date_of_joining);

            // Check if candidate was active in this month
            if ($joiningDate->year > $year || ($joiningDate->year == $year && $joiningDate->month > $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Candidate was not active in this month'
                ]);
            }

            // Get or create attendance record
            $attendance = Attendance::firstOrNew([
                'candidate_id' => $candidateId,
                'Month' => $month,
                'Year' => $year
            ]);

            // For new records, make sure candidate_id is set
            if (!$attendance->exists) {
                $attendance->candidate_id = $candidateId;
                $attendance->Month = $month;
                $attendance->Year = $year;
            }

            // Get existing attendance data to compare changes
            $existingAttendance = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $column = "A" . $day;
                $existingAttendance[$day] = $attendance->exists ? $attendance->$column : null;
            }

            // Count CL days in new attendance data
            $newCLCount = 0;
            foreach ($attendanceData as $status) {
                if ($status === 'CL') {
                    $newCLCount++;
                }
            }

            // Get existing CL count from current attendance
            $existingCLCount = 0;
            foreach ($existingAttendance as $status) {
                if ($status === 'CL') {
                    $existingCLCount++;
                }
            }

            // Calculate net CL change
            $clChange = $newCLCount - $existingCLCount;

            // Validate CL application based on user role and availability
            if ($isContractual && $clChange != 0) {
                // Get or create leave balance
                $leaveBalance = LeaveBalance::firstOrNew([
                    'CandidateID' => $candidateId,
                    'calendar_year' => $year
                ]);

                // If new leave balance record, set opening balance from candidate's leave_credited
                if (!$leaveBalance->exists) {
                    // Use leave_credited value from candidate table, default to 0 if null
                    $leaveBalance->opening_cl_balance = $candidate->leave_credited ?: 0;
                    $leaveBalance->cl_utilized = 0;
                    $leaveBalance->lwp_days_accumulated = 0;
                    $leaveBalance->contract_start_date = $joiningDate;
                    $leaveBalance->save();
                }

                // Calculate current available CL
                $availableCL = $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized;

                // **ROLE-BASED VALIDATION STARTS HERE**

                if ($clChange > 0) { // Only validate when adding CL
                    if ($isReportingManager) {
                        // Reporting Manager validations

                        // 1. Max 2 days CL per request
                        if ($clChange > 2) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Reporting Managers can apply maximum 2 days CL per request'
                            ]);
                        }

                        // 2. Max 2 days CL per month (existing + new)
                        $currentMonthCL = $existingCLCount + $clChange;
                        if ($currentMonthCL > 2) {
                            return response()->json([
                                'success' => false,
                                'message' => "Reporting Managers can apply maximum 2 days CL per month. Already have {$existingCLCount} CL this month."
                            ]);
                        }

                        // 3. Check if enough CL balance is available
                        if ($clChange > $availableCL) {
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient CL balance. Available: {$availableCL} days, Requested: {$clChange} days"
                            ]);
                        }
                    } else if ($isHRAdmin) {
                        // HR Admin validations - more flexible

                        // Only check if enough CL balance is available
                        if ($clChange > $availableCL) {
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient CL balance. Available: {$availableCL} days, Requested: {$clChange} days"
                            ]);
                        }
                    } else {
                        // Other users (if any) - no permission
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to apply CL'
                        ]);
                    }
                }
            }

            // Initialize counts
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalCL = 0;
            $totalLWP = 0;

            // Update day columns
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $column = "A" . $day;
                $oldStatus = $existingAttendance[$day] ?? null;
                $newStatus = $attendanceData[$day] ?? null;

                // If status is empty string, convert to null
                if ($newStatus === "" || $newStatus === null) {
                    $newStatus = null;
                } else {
                    // Validate status based on candidate type
                    if (!$isContractual && $newStatus === 'CL') {
                        // Non-contractual candidates cannot have CL
                        $newStatus = 'A'; // Convert CL to Absent for non-contractual
                    }
                }

                // Auto-set Sundays to 'W' unless it's 'P' (Sunday work)
                $date = Carbon::create($year, $month, $day);
                if ($date->dayOfWeek === Carbon::SUNDAY && $newStatus !== 'P') {
                    $newStatus = 'W';
                }

                $attendance->$column = $newStatus;

                // Count statuses
                if ($newStatus !== null) {
                    switch ($newStatus) {
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
                        case 'H':
                            $totalPresent++; // Holiday counts as present
                            break;
                    }
                }
            }

            // Update calculated fields
            $attendance->total_present = $totalPresent;
            $attendance->total_absent = $totalAbsent;
            $attendance->total_cl = $totalCL;
            $attendance->total_lwp = $totalLWP;
            $attendance->submitted_by = $userId;
            $attendance->status = 'submitted';

            $attendance->save();

            // Process leave deduction/restoration for contractual candidates
            $warning = null;
            $clRemaining = null;

            if ($isContractual) {
                $leaveResult = $this->processLeaveDeduction($candidateId, $month, $year, $existingAttendance, $attendanceData);
                if ($leaveResult['warning']) {
                    $warning = $leaveResult['warning'];
                }
                $clRemaining = $leaveResult['new_cl'];
            }

            $clUsed = null;

            if ($isContractual) {
                $leaveBalance = LeaveBalance::where('CandidateID', $candidateId)
                    ->where('calendar_year', $year)
                    ->first();

                if ($leaveBalance) {
                    $clUsed = $leaveBalance->cl_utilized;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'warning' => $warning,
                'cl_remaining' => $clRemaining,
                'cl_used' => $clUsed
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Attendance update error: ' . $e->getMessage());
            \Log::error('Request data: ', $request->all());

            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process leave deduction/restoration for contractual candidates
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

            $clChanges = 0; // Positive = adding CL, Negative = removing CL
            $lwpChanges = 0; // Positive = adding LWP, Negative = removing LWP

            // Calculate changes for each day
            foreach ($newAttendance as $day => $newStatus) {
                $oldStatus = $oldAttendance[$day] ?? null;

                // Skip if no change
                if ($oldStatus === $newStatus) {
                    continue;
                }

                // Handle CL changes
                if ($oldStatus === 'CL' && $newStatus !== 'CL') {
                    // Removing CL - restore leave balance
                    $clChanges--;
                } elseif ($oldStatus !== 'CL' && $newStatus === 'CL') {
                    // Adding CL - deduct leave balance
                    $clChanges++;
                }

                // Handle LWP changes
                if ($oldStatus === 'LWP' && $newStatus !== 'LWP') {
                    // Removing LWP
                    $lwpChanges--;
                } elseif ($oldStatus !== 'LWP' && $newStatus === 'LWP') {
                    // Adding LWP
                    $lwpChanges++;
                }
            }

            $warning = null;

            // Process CL changes
            if ($clChanges > 0) {
                // Adding CL - check if enough balance
                $availableCL = $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized;

                if ($clChanges <= $availableCL) {
                    // Enough balance - deduct from CL
                    $leaveBalance->cl_utilized += $clChanges;
                } else {
                    // Not enough CL balance - use what's available, rest as LWP
                    $clDeducted = $availableCL;
                    $lwpFromCL = $clChanges - $clDeducted;

                    $leaveBalance->cl_utilized += $clDeducted;
                    $leaveBalance->lwp_days_accumulated += $lwpFromCL;

                    $warning = "Insufficient CL balance. {$clDeducted} days deducted from CL, {$lwpFromCL} days marked as LWP.";
                }
            } elseif ($clChanges < 0) {
                // Removing CL - restore leave balance (but not more than was utilized)
                $clRestored = min(abs($clChanges), $leaveBalance->cl_utilized);
                $leaveBalance->cl_utilized -= $clRestored;
            }

            // Process LWP changes (independent of CL changes)
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

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $sundays = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day);
                if ($date->dayOfWeek === Carbon::SUNDAY) {
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
            $candidates = CandidateMaster::select([
                'id',
                'candidate_name'
            ])
                ->where('final_status', 'A')
                ->whereNotNull('date_of_joining')
                ->orderBy('candidate_name')
                ->get();

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
     * Submit Sunday work request
     */
    public function submitSundayWork(Request $request)
    {
        //dd($request->all());
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
            $userId = Auth::id();

            foreach ($candidateIds as $candidateId) {
                foreach ($sundayDates as $sundayDate) {
                    // Check if already exists
                    $existing = SundayWorkRequest::where('candidate_id', $candidateId)
                        ->where('sunday_date', $sundayDate)
                        ->first();

                    if ($existing) {
                        continue; // Skip if already exists
                    }

                    // Create simplified Sunday work request
                    $sundayWork = new SundayWorkRequest();
                    $sundayWork->candidate_id = $candidateId;
                    $sundayWork->month = $month;
                    $sundayWork->year = $year;
                    $sundayWork->sunday_date = $sundayDate;
                    $sundayWork->remark = $remark;
                    $sundayWork->requested_by = $userId;
                    $sundayWork->status = 'pending';

                    // Handle attachment (optional)
                    if ($request->hasFile('attachment')) {
                        $path = $request->file('attachment')->store('sunday-work-attachments', 'public');
                        $sundayWork->attachment_path = $path;
                    }

                    $sundayWork->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sunday work request submitted successfully'
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
