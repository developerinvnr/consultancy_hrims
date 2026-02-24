<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\CoreDepartment;
use App\Models\Attendance;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterReportExport;
use App\Exports\RemunerationReportExport;
use App\Exports\VendorDetailsReportExport;
use App\Exports\AttendanceReportExport;
use App\Exports\BankStatementReportExport;
use App\Exports\ContractStatusReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        // Get filter data for dropdowns
        $workLocations = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereNotNull('work_location_hq')
            ->where('work_location_hq', '!=', '')
            ->distinct()
            ->orderBy('work_location_hq')
            ->pluck('work_location_hq');

        $departments = CoreDepartment::orderBy('department_name')->get();

        return view('reports.index', compact('workLocations', 'departments'));
    }

    /**
     * Display master report
     */
    public function master(Request $request)
    {
        $financialYear = $request->get('financial_year');
        $status = $request->get('status', 'All');
        $requisitionType = $request->get('requisition_type', 'All');
        $workLocation = $request->get('work_location', '');
        $departmentId = $request->get('department_id', '');
        $search = $request->get('search', '');

        // Build base query
        $query = CandidateMaster::query();

        // Apply Status filter
        if ($status !== 'All') {
            $query->where('final_status', $status);
        } else {
            $query->whereIn('final_status', ['A', 'D']);
        }

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
            $query->where(function ($q) use ($search) {
                $q->where('candidate_code', 'like', "%{$search}%")
                    ->orWhere('candidate_name', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%")
                    ->orWhere('pan_no', 'like', "%{$search}%")
                    ->orWhere('aadhaar_no', 'like', "%{$search}%")
                    ->orWhere('bank_account_no', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%");
            });
        }

        $candidates = $query
            ->with('department')
            ->orderBy('candidate_code')
            ->paginate(20)
            ->withQueryString();

        $stats = $this->getMasterReportStats(
            $status,
            $requisitionType,
            $workLocation,
            $departmentId
        );

        $workLocations = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereNotNull('work_location_hq')
            ->where('work_location_hq', '!=', '')
            ->distinct()
            ->orderBy('work_location_hq')
            ->pluck('work_location_hq');

        $departments = CoreDepartment::orderBy('department_name')->get();

        return view('reports.master', compact(
            'candidates',
            'financialYear',
            'status',
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
    private function getMasterReportStats($status, $requisitionType, $workLocation, $departmentId)
    {
        $candidateQuery = CandidateMaster::query();

        if ($status !== 'All') {
            $candidateQuery->where('final_status', $status);
        } else {
            $candidateQuery->whereIn('final_status', ['A', 'D']);
        }

        if ($requisitionType !== 'All') {
            $candidateQuery->where('requisition_type', $requisitionType);
        }

        if (!empty($workLocation)) {
            $candidateQuery->where('work_location_hq', $workLocation);
        }

        if (!empty($departmentId)) {
            $candidateQuery->where('department_id', $departmentId);
        }

        $salaryStats = SalaryProcessing::join(
            'candidate_master',
            'salary_processings.candidate_id',
            '=',
            'candidate_master.id'
        )
            ->when($status !== 'All', function ($q) use ($status) {
                $q->where('candidate_master.final_status', $status);
            })
            ->select(
                DB::raw('COUNT(DISTINCT candidate_id) as processed_count'),
                DB::raw('SUM(net_pay) as total_salary'),
                DB::raw('AVG(net_pay) as avg_salary')
            )
            ->first();

        return [
            'total_employees' => $candidateQuery->count(),
            'salary_processed_count' => $salaryStats->processed_count ?? 0,
            'total_salary_amount' => $salaryStats->total_salary ?? 0,
            'average_salary' => $salaryStats->avg_salary ?? 0,
        ];
    }
    /**
     * Display remuneration report
     */
    public function remuneration(Request $request)
    {
        // Validate request
        $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2020',
            'department_id' => 'nullable|integer|exists:core_departments,id',
        ]);

        $financialYear = $request->get('financial_year');

        $currentMonth = date('n');
        $currentYear  = date('Y');

        if (!$financialYear) {
            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        $month = (int) $request->get('month', $currentMonth);

        // Map month to correct calendar year
        $year = ($month >= 4) ? $startYear : $endYear;
        $departmentId = $request->get('department_id', '');

        // Build query for remuneration report
        $query = SalaryProcessing::with([
            'candidate.department',
            'candidate.subDepartmentRef',
            'candidate.vertical',
            'candidate.businessUnit',
            'candidate.zoneRef',
            'candidate.regionRef',
            'candidate.territoryRef'
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('candidate', function ($q) use ($departmentId) {
                $q->whereIn('final_status', ['A', 'D']);

                if (!empty($departmentId)) {
                    $q->where('department_id', $departmentId);
                }
            });

        if (!empty($departmentId)) {
            $query->where('candidate_master.department_id', $departmentId);
        }

        $salaryRecords = $query->orderBy(CandidateMaster::select('candidate_code')->whereColumn('candidate_master.id', 'salary_processings.candidate_id'))->paginate(20)->withQueryString();

        // Get departments for filter
        $departments = CoreDepartment::orderBy('department_name')->get();

        // Calculate statistics
        $stats = [
            'total_records' => $salaryRecords->total(),
            'total_salary' => $salaryRecords->sum('net_pay'),
            'total_deductions' => $salaryRecords->sum('deduction_amount'),
            'total_extras' => $salaryRecords->sum('extra_amount'),
        ];

        return view('reports.remuneration', compact(
            'salaryRecords',
            'month',
            'year',
            'financialYear',
            'departmentId',
            'departments',
            'stats'
        ));
    }

    /**
     * Display vendor details report
     */
    public function vendorDetails(Request $request)
    {
        // Validate request
        $request->validate([
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
            'department_id' => 'nullable|integer|exists:core_departments,id',
            'work_location' => 'nullable|string|max:255',
        ]);

        $requisitionType = $request->get('requisition_type', 'All');
        $departmentId = $request->get('department_id', '');
        $workLocation = $request->get('work_location', '');

        // Build query for vendor details
        $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->with('department');

        if ($requisitionType !== 'All') {
            $query->where('requisition_type', $requisitionType);
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($workLocation)) {
            $query->where('work_location_hq', $workLocation);
        }

        $candidates = $query->orderBy('candidate_code')
            ->paginate(20)
            ->withQueryString();

        // Get filter data
        $workLocations = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereNotNull('work_location_hq')
            ->distinct()
            ->pluck('work_location_hq');

        $departments = CoreDepartment::orderBy('department_name')->get();

        return view('reports.vendor-details', compact(
            'candidates',
            'requisitionType',
            'departmentId',
            'workLocation',
            'workLocations',
            'departments'
        ));
    }

    /**
     * Export master report
     */
    public function masterExport(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|string',
            'status' => 'nullable|string|in:A,D,All',
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
            'work_location' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:core_departments,id',
            'search' => 'nullable|string|max:255',

        ]);



        return Excel::download(
            new MasterReportExport(
                $request->financial_year,
                $request->status ?? 'All',
                $request->requisition_type ?? 'All',
                $request->work_location ?? '',
                $request->department_id ?? '',
                $request->search ?? ''
            ),
            "Master_Report_{$request->financial_year}.xlsx"
        );
    }

    /**
     * Export remuneration report
     */
    public function remunerationExport(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric|between:1,12',
            'year' => 'required|numeric|min:2020',
            'department_id' => 'nullable|integer|exists:core_departments,id',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        return Excel::download(
            new RemunerationReportExport(
                $month,
                $year,
                $request->department_id ?? ''
            ),
            "Remuneration_Report_{$month}_{$year}.xlsx"
        );
    }

    /**
     * Export vendor details report
     */
    public function vendorDetailsExport(Request $request)
    {
        $request->validate([
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
            'department_id' => 'nullable|integer|exists:core_departments,id',
            'work_location' => 'nullable|string|max:255',
        ]);

        return Excel::download(
            new VendorDetailsReportExport(
                $request->requisition_type ?? 'All',
                $request->department_id ?? '',
                $request->work_location ?? ''
            ),
            "Vendor_Details_Report_" . date('Y-m-d') . ".xlsx"
        );
    }
}
