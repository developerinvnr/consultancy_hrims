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
                    'date_of_joining'
                ])
                ->where('final_status', 'A')
                ->whereNotNull('date_of_joining')
                ->where('date_of_joining', '<=', Carbon::create($year, $month)->endOfMonth());
            
            if ($employeeType && $employeeType !== 'all') {
                $query->where('requisition_type', $employeeType);
            }
            
            $candidates = $query->get();
            
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
                        switch($status) {
                            case 'P': $totalPresent++; break;
                            case 'A': $totalAbsent++; break;
                            case 'CL': $totalCL++; break;
                            case 'LWP': $totalLWP++; break;
                        }
                    }
                }
                
                $result['candidates'][] = [
                    'sno' => $index + 1,
                    'candidate_id' => $candidate->candidate_id,
                    'candidate_code' => $candidate->candidate_code,
                    'candidate_name' => $candidate->candidate_name,
                    'requisition_type' => $candidate->requisition_type,
                    'attendance' => $dayAttendance,
                    'total_present' => $totalPresent,
                    'total_absent' => $totalAbsent,
                    'cl_used' => $totalCL,
                    'lwp_days' => $totalLWP,
                    'cl_remaining' => $leaveBalance ? $leaveBalance->cl_remaining : 12,
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
     * Update attendance for candidate
     */
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
            
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            
            // Get or create attendance record
            $attendance = Attendance::firstOrNew([
                'candidate_id' => $candidateId,
                'Month' => $month,
                'Year' => $year
            ]);
            
            // Initialize counts
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalCL = 0;
            $totalLWP = 0;
            
            // Update day columns
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $column = "A" . $day;
                $status = $attendanceData[$day] ?? null;
                
                // Auto-set Sundays to 'W' unless it's 'P' (Sunday work)
                $date = Carbon::create($year, $month, $day);
                if ($date->dayOfWeek === Carbon::SUNDAY && $status !== 'P') {
                    $status = 'W';
                }
                
                $attendance->$column = $status;
                
                // Count statuses
                switch($status) {
                    case 'P': $totalPresent++; break;
                    case 'A': $totalAbsent++; break;
                    case 'CL': $totalCL++; break;
                    case 'LWP': $totalLWP++; break;
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
            
            // Process leave deduction for contractual candidates
            $candidate = CandidateMaster::find($candidateId);
            $warning = null;
            $clRemaining = null;
            
            if ($candidate && $candidate->requisition_type === 'Contractual') {
                $leaveResult = $this->processLeaveDeduction($candidateId, $month, $year, $attendanceData);
                if ($leaveResult['warning']) {
                    $warning = $leaveResult['warning'];
                }
                $clRemaining = $leaveResult['new_cl'];
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'warning' => $warning,
                'cl_remaining' => $clRemaining
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance: ' . $e->getMessage()
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
                    
                    // Get candidate details
                    $candidate = CandidateMaster::find($candidateId);
                    $dailyRate = $candidate->remuneration_per_month ? $candidate->remuneration_per_month / 26 : 0;
                    $amount = $dailyRate * 1.5; // 1.5x for Sunday work (8 hours)
                    
                    // Create Sunday work request
                    $sundayWork = new SundayWorkRequest();
                    $sundayWork->candidate_id = $candidateId;
                    $sundayWork->month = $month;
                    $sundayWork->year = $year;
                    $sundayWork->sunday_date = $sundayDate;
                    $sundayWork->work_hours = 8; // Fixed 8 hours
                    $sundayWork->rate_multiplier = 1.5;
                    $sundayWork->daily_rate = $dailyRate;
                    $sundayWork->amount = $amount;
                    $sundayWork->remark = $remark;
                    $sundayWork->requested_by = $userId;
                    $sundayWork->status = 'pending';
                    
                    // Handle attachment
                    if ($request->hasFile('attachment')) {
                        $path = $request->file('attachment')->store('sunday-work-attachments', 'public');
                        $sundayWork->attachment_path = $path;
                    }
                    
                    $sundayWork->save();
                    
                    // Update attendance to mark Sunday as 'P' (Present for work)
                    $day = Carbon::parse($sundayDate)->day;
                    $attendance = Attendance::firstOrNew([
                        'candidate_id' => $candidateId,
                        'Month' => $month,
                        'Year' => $year
                    ]);
                    
                    $column = "A" . $day;
                    $attendance->$column = 'P';
                    $attendance->submitted_by = $userId;
                    $attendance->save();
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
     * Process leave deduction for contractual candidates
     */
    private function processLeaveDeduction($candidateId, $month, $year, $attendanceData)
    {
        try {
            $daysAbsent = 0;
            $clDays = 0;
            
            // Count absent days and CL days
            foreach ($attendanceData as $day => $status) {
                if ($status === 'A') {
                    $daysAbsent++;
                } elseif ($status === 'CL') {
                    $clDays++;
                }
            }
            
            $leaveBalance = LeaveBalance::firstOrNew([
                'CandidateID' => $candidateId,
                'calendar_year' => $year
            ]);
            
            // Set opening balance if new record
            if (!$leaveBalance->exists) {
                $leaveBalance->opening_cl_balance = 12;
                $leaveBalance->cl_utilized = 0;
                $leaveBalance->lwp_days_accumulated = 0;
                $candidate = CandidateMaster::find($candidateId);
                $leaveBalance->contract_start_date = $candidate->date_of_joining;
            }
            
            $previousCL = $leaveBalance->cl_remaining;
            $warning = null;
            
            // Apply leave deduction rules
            if ($daysAbsent > 0) {
                $clToDeduct = min($daysAbsent, 2, $leaveBalance->cl_remaining);
                $lwpDays = max(0, $daysAbsent - $clToDeduct);
                
                $leaveBalance->cl_utilized += $clToDeduct;
                $leaveBalance->lwp_days_accumulated += $lwpDays;
                
                // Check for warning
                if ($leaveBalance->cl_remaining <= 2) {
                    $warning = "Warning: Candidate has only {$leaveBalance->cl_remaining} CL remaining. {$lwpDays} days will be marked as LWP.";
                }
            }
            
            // Deduct CL days
            if ($clDays > 0) {
                $clToDeductFromCL = min($clDays, $leaveBalance->cl_remaining);
                $leaveBalance->cl_utilized += $clToDeductFromCL;
                
                if ($clDays > $clToDeductFromCL) {
                    $excessCL = $clDays - $clToDeductFromCL;
                    $leaveBalance->lwp_days_accumulated += $excessCL;
                    
                    if ($excessCL > 0) {
                        $warning = "Warning: Insufficient CL balance. {$excessCL} days will be marked as LWP.";
                    }
                }
            }
            
            $leaveBalance->save();
            
            return [
                'success' => true,
                'warning' => $warning,
                'previous_cl' => $previousCL,
                'new_cl' => $leaveBalance->cl_remaining,
                'lwp_days' => $leaveBalance->lwp_days_accumulated
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'warning' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}