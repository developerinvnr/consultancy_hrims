<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\CoreDepartment; // Assuming you have Department model
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display master report page
     */
    public function master(Request $request)
    {
        // Get filter parameters with defaults
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $requisitionType = $request->get('requisition_type', 'All');
        $workLocation = $request->get('work_location', '');
        $departmentId = $request->get('department_id', '');
        $search = $request->get('search', '');
        // Build query
        $query = CandidateMaster::where('final_status', 'A')
            ->with(['salaryProcessings' => function($q) use ($month, $year) {
                $q->where('month', $month)->where('year', $year);
            }])
            ->with('department'); // Assuming you have department relationship
        
        // Apply requisition type filter
        if ($requisitionType !== 'All') {
            $query->where('requisition_type', $requisitionType);
        }
        
        // Apply work location filter
        if (!empty($workLocation)) {
            $query->where('work_location_hq', $workLocation);
        }
        
        // Apply department filter
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }
        
        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('candidate_code', 'like', "%{$search}%")
                  ->orWhere('candidate_name', 'like', "%{$search}%")
                  ->orWhere('mobile_no', 'like', "%{$search}%")
                  ->orWhere('pan_no', 'like', "%{$search}%")
                  ->orWhere('aadhaar_no', 'like', "%{$search}%")
                  ->orWhere('bank_account_no', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%");
            });
        }
        
        // Order and paginate
        $candidates = $query->orderBy('candidate_code')->paginate(20)->withQueryString();
        
        // Get unique work locations for filter dropdown
        $workLocations = CandidateMaster::where('final_status', 'A')
            ->whereNotNull('work_location_hq')
            ->where('work_location_hq', '!=', '')
            ->distinct()
            ->orderBy('work_location_hq')
            ->pluck('work_location_hq');
        
        // Get departments for filter dropdown
        $departments = CoreDepartment::orderBy('department_name')->get(); 
        // Statistics
        $stats = $this->getMasterReportStats($month, $year, $requisitionType, $workLocation, $departmentId);
        
        return view('reports.master', compact(
            'candidates', 
            'month', 
            'year', 
            'requisitionType',
            'workLocation',
            'departmentId',
            'search',
            'workLocations',
            'departments',
            'stats'
        ));
    }
    
    /**
     * Get statistics for master report
     */
    private function getMasterReportStats($month, $year, $requisitionType, $workLocation, $departmentId)
    {
        // Base query for candidates
        $candidateQuery = CandidateMaster::where('final_status', 'A');
        
        // Apply filters for candidate count
        if ($requisitionType !== 'All') {
            $candidateQuery->where('requisition_type', $requisitionType);
        }
        if (!empty($workLocation)) {
            $candidateQuery->where('work_location_hq', $workLocation);
        }
        if (!empty($departmentId)) {
            $candidateQuery->where('department_id', $departmentId);
        }
        
        // Salary processing query with same filters
        $salaryQuery = SalaryProcessing::where('month', $month)
            ->where('year', $year)
            ->join('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
            ->where('candidate_master.final_status', 'A');
        
        if ($requisitionType !== 'All') {
            $salaryQuery->where('candidate_master.requisition_type', $requisitionType);
        }
        if (!empty($workLocation)) {
            $salaryQuery->where('candidate_master.work_location_hq', $workLocation);
        }
        if (!empty($departmentId)) {
            $salaryQuery->where('candidate_master.department_id', $departmentId);
        }
        
        // Get salary statistics
        $salaryStats = $salaryQuery->select(
            DB::raw('COUNT(DISTINCT candidate_id) as processed_count'),
            DB::raw('SUM(net_pay) as total_salary'),
            DB::raw('AVG(net_pay) as avg_salary'),
            DB::raw('SUM(deduction_amount) as total_deductions'),
            DB::raw('SUM(extra_amount) as total_extras')
        )->first();
        
        return [
            'total_employees' => $candidateQuery->count(),
            'salary_processed_count' => $salaryStats->processed_count ?? 0,
            'total_salary_amount' => $salaryStats->total_salary ?? 0,
            'average_salary' => $salaryStats->avg_salary ?? 0,
            'total_deductions' => $salaryStats->total_deductions ?? 0,
            'total_extras' => $salaryStats->total_extras ?? 0,
            
            // Requisition type breakdown
            'type_breakdown' => CandidateMaster::where('final_status', 'A')
                ->when($requisitionType !== 'All', function($q) use ($requisitionType) {
                    return $q->where('requisition_type', $requisitionType);
                })
                ->when(!empty($workLocation), function($q) use ($workLocation) {
                    return $q->where('work_location_hq', $workLocation);
                })
                ->when(!empty($departmentId), function($q) use ($departmentId) {
                    return $q->where('department_id', $departmentId);
                })
                ->select('requisition_type', DB::raw('count(*) as count'))
                ->groupBy('requisition_type')
                ->pluck('count', 'requisition_type')
                ->toArray(),
            
            // Location breakdown
            'location_breakdown' => CandidateMaster::where('final_status', 'A')
                ->when($requisitionType !== 'All', function($q) use ($requisitionType) {
                    return $q->where('requisition_type', $requisitionType);
                })
                ->when(!empty($workLocation), function($q) use ($workLocation) {
                    return $q->where('work_location_hq', $workLocation);
                })
                ->when(!empty($departmentId), function($q) use ($departmentId) {
                    return $q->where('department_id', $departmentId);
                })
                ->whereNotNull('work_location_hq')
                ->where('work_location_hq', '!=', '')
                ->select('work_location_hq', DB::raw('count(*) as count'))
                ->groupBy('work_location_hq')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'work_location_hq')
                ->toArray(),
        ];
    }
    
    /**
     * Export master report to Excel
     */
    public function masterExport(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
            'work_location' => 'sometimes|string|max:255',
            'department_id' => 'sometimes|integer|exists:departments,id',
            'search' => 'sometimes|string|max:255',
        ]);
        
        $month = $request->month;
        $year = $request->year;
        $requisitionType = $request->requisition_type ?? 'All';
        $workLocation = $request->work_location ?? '';
        $departmentId = $request->department_id ?? '';
        $search = $request->search ?? '';
        
        $filename = "Master_Employee_Report_" . date('F', mktime(0, 0, 0, $month, 1)) . "_{$year}";
        if ($requisitionType !== 'All') {
            $filename .= "_{$requisitionType}";
        }
        
        return Excel::download(
            new MasterReportExport($month, $year, $requisitionType, $workLocation, $departmentId, $search),
            "{$filename}.xlsx"
        );
    }
}