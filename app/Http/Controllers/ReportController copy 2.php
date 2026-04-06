<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\CoreDepartment;
use App\Models\ManpowerRequisition;
use App\Models\AgreementCourier;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterReportExport;
use App\Exports\RemunerationReportExport;
use App\Exports\FocusMasterExport;
use App\Exports\JVExport;
use App\Exports\TDSJVExport;
use App\Exports\PaymentJVExport;
use App\Exports\TatReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\HierarchyAccessService;
use Illuminate\Support\Facades\Auth;


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
        $month = $request->get('month');
        // ✅ ADD THIS BLOCK
        if (!$financialYear) {
            $currentMonth = date('n');
            $currentYear  = date('Y');

            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }
        $status = $request->get('status', 'All');
        $requisitionType = $request->get('requisition_type', 'All');
        $workLocation = $request->get('work_location', '');
        $departmentId = $request->get('department_id', '');
        $search = $request->get('search', '');

        // Build base query
        $query = CandidateMaster::query();

        // Apply Financial Year filter
        if (!empty($financialYear)) {

            [$startYear, $endYear] = explode('-', $financialYear);

            if (!empty($month)) {

                // Map FY month to actual year
                $year = ($month >= 4) ? $startYear : $endYear;

                $startDate = "{$year}-{$month}-01";
                $endDate = \Carbon\Carbon::parse($startDate)->endOfMonth();

                $query->whereBetween('contract_start_date', [$startDate, $endDate]);
            } else {

                $startDate = $startYear . '-04-01';
                $endDate   = $endYear . '-03-31';

                $query->whereBetween('contract_start_date', [$startDate, $endDate]);
            }
        }

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
            ->with([
                'department',
                'zoneRef',
                'regionRef',
                'businessUnit',
                'vertical',
                'subDepartmentRef'
            ])
            ->orderBy('candidate_code')
            ->paginate(20)
            ->withQueryString();

        $stats = $this->getMasterReportStats(
            $financialYear,
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
            'month',
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
    private function getMasterReportStats($financialYear, $status, $requisitionType, $workLocation, $departmentId)
    {
        $candidateQuery = CandidateMaster::query();

        if (!empty($financialYear)) {
            [$startYear, $endYear] = explode('-', $financialYear);

            $startDate = $startYear . '-04-01';
            $endDate   = $endYear . '-03-31';

            $candidateQuery->whereBetween('contract_start_date', [$startDate, $endDate]);
        }

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
            ->when(!empty($financialYear), function ($q) use ($financialYear) {
                [$startYear, $endYear] = explode('-', $financialYear);

                $startDate = $startYear . '-04-01';
                $endDate   = $endYear . '-03-31';

                $q->whereBetween('candidate_master.contract_start_date', [$startDate, $endDate]);
            })
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
            'department_id' => 'nullable|integer|exists:core_department,id',
            'requisition_type' => 'nullable|string|in:TFA,CB,Contractual',
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
        $requisitionType = $request->get('requisition_type');
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
            // ✅ show only released parties
            ->where('payment_status', 'paid')
            ->whereHas('candidate', function ($q) use ($departmentId, $requisitionType) {
                $q->whereIn('final_status', ['A', 'D']);

                if (!empty($departmentId)) {
                    $q->where('department_id', $departmentId);
                }

                if (!empty($requisitionType)) {
                    $q->where('requisition_type', $requisitionType);
                }
            });

        // if (!empty($departmentId)) {
        //     $query->where('candidate_master.department_id', $departmentId);
        // }

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
     * Export master report
     */
    public function masterExport(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|string',
            'month' => 'nullable|integer|between:1,12',
            'status' => 'nullable|string|in:A,D,All',
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
            'work_location' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:core_departments,id',
            'search' => 'nullable|string|max:255',

        ]);



        return Excel::download(
            new MasterReportExport(
                $request->financial_year,
                $request->month ?? '',
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
            'financial_year' => 'required|string',
            'department_id' => 'nullable|integer|exists:core_departments,id',
            'requisition_type' => 'nullable|string',
        ]);

        $month = (int) $request->month;
        [$startYear, $endYear] = explode('-', $request->financial_year);

        $year = ($month >= 4) ? $startYear : $endYear;

        return Excel::download(
            new RemunerationReportExport(
                $month,
                $year,
                $request->department_id ?? '',
                $request->requisition_type ?? ''
            ),
            "Payout_Report_{$month}_{$year}.xlsx"
        );
    }

    public function focusMaster(Request $request)
    {
        $departmentId = $request->get('department_id');
        $search = $request->get('search');

        $query = CandidateMaster::with([
            'department',
            'subDepartmentRef',
            'vertical',
            'businessUnit',
            'zoneRef',
            'regionRef',
            'workLocation',
        ])
            ->whereIn('final_status', ['A', 'D']);

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'like', "%$search%")
                    ->orWhere('candidate_code', 'like', "%$search%")
                    ->orWhere('pan_no', 'like', "%$search%");
            });
        }

        $records = $query
            ->orderBy('candidate_code')
            ->paginate(20)
            ->withQueryString();

        $departments = CoreDepartment::orderBy('department_name')->get();

        return view('reports.focus-master', compact(
            'records',
            'departments'
        ));
    }
    /**
     * Export vendor details report
     */
    public function focusMasterExport(Request $request)
    {
        return Excel::download(
            new FocusMasterExport(
                $request->department_id,
                $request->search
            ),
            'Focus_Master_Report.xlsx'
        );
    }


    public function JVReport(Request $request)
    {
        // -------------------------------
        // 1️⃣ Get Filters
        // -------------------------------
        $financialYear = $request->get('financial_year');
        $status        = $request->get('status', 'All');
        $exportStatus = $request->get('export_status', 'All');
        $currentMonth = date('n');
        $currentYear  = date('Y');

        // -------------------------------
        // 2️⃣ Default Financial Year
        // -------------------------------
        if (!$financialYear) {
            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        // -------------------------------
        // 3️⃣ Month (FY Based)
        // -------------------------------
        $month = (int) $request->get('month', $currentMonth);

        // Map FY month to actual calendar year
        $year = ($month >= 4) ? $startYear : $endYear;
        $requisitionType = $request->get('requisition_type');
        // -------------------------------
        // 4️⃣ Build Query
        // -------------------------------
        $query = SalaryProcessing::with([
            'candidate.department',
            'candidate.businessUnit',
            'candidate.vertical',
            'candidate.subDepartmentRef',
            'candidate.zoneRef',
            'candidate.regionRef',
            'candidate.function',
            'candidate.workState',
            'candidate.workLocation'
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'processed')
            ->whereHas('candidate', function ($q) use ($status, $requisitionType) {

                if ($status !== 'All') {
                    $q->where('final_status', $status);
                } else {
                    $q->whereIn('final_status', ['A', 'D']);
                }

                if (!empty($requisitionType)) {
                    $q->where('requisition_type', $requisitionType);
                }
            });

        if ($exportStatus === 'exported') {

            $query->whereIn('id', function ($sub) {

                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'jv');
            });
        }

        if ($exportStatus === 'not_exported') {

            $query->whereNotIn('id', function ($sub) {

                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'jv');
            });
        }

        // -------------------------------
        // 5️⃣ Pagination
        // -------------------------------
        $records = $query
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        // -------------------------------
        // 6️⃣ Return View (PASS $year)
        // -------------------------------
        return view('reports.jv', [
            'records'       => $records,
            'financialYear' => $financialYear,
            'month'         => $month,
            'year'          => $year,     // 👈 FIXED
            'status'        => $status,
        ]);
    }
    public function JVExport(Request $request)
    {
        return Excel::download(
            new JVExport(
                $request->financial_year,
                $request->month,
                $request->status,
                $request->requisition_type ?? '',
                $request->export_status ?? 'All'
            ),
            'JV_Report.xlsx'
        );
    }

    public function TDSJVReport(Request $request)
    {
        $financialYear = $request->get('financial_year');
        $status        = $request->get('status', 'All');
        $exportStatus = $request->get('export_status', 'All');

        $currentMonth = date('n');
        $currentYear  = date('Y');

        // Default FY
        if (!$financialYear) {
            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        $month = (int) $request->get('month', $currentMonth);
        $year  = ($month >= 4) ? $startYear : $endYear;
        $requisitionType = $request->get('requisition_type');
        $query = SalaryProcessing::with('candidate')
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'processed')
            ->whereHas('candidate', function ($q) use ($status, $requisitionType) {
                if ($status !== 'All') {
                    $q->where('final_status', $status);
                } else {
                    $q->whereIn('final_status', ['A', 'D']);
                }

                if (!empty($requisitionType)) {
                    $q->where('requisition_type', $requisitionType);
                }
            });

        if ($exportStatus === 'exported') {
            $query->whereIn('id', function ($sub) {
                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'tds_jv');
            });
        }

        if ($exportStatus === 'not_exported') {
            $query->whereNotIn('id', function ($sub) {
                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'tds_jv');
            });
        }

        $records = $query->paginate(20)->withQueryString();

        return view('reports.tds-jv', compact(
            'records',
            'financialYear',
            'month',
            'year',
            'status',
            'exportStatus'
        ));
    }

    public function TDSJVExport(Request $request)
    {
        return Excel::download(
            new TDSJVExport(
                $request->financial_year,
                $request->month,
                $request->status,
                $request->requisition_type ?? '',
                $request->export_status ?? 'All'
            ),
            'TDS_JV_Report.xlsx'
        );
    }

    public function PaymentJVReport(Request $request)
    {
        $financialYear = $request->get('financial_year');
        $status        = $request->get('status', 'All');

        $currentMonth = date('n');
        $currentYear  = date('Y');

        // Default FY
        if (!$financialYear) {
            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        $month = (int) $request->get('month', $currentMonth);
        $year  = ($month >= 4) ? $startYear : $endYear;
        $requisitionType = $request->get('requisition_type');
        $records = SalaryProcessing::with([
            'candidate.department',
            'candidate.businessUnit',
            'candidate.vertical',
            'candidate.subDepartmentRef',
            'candidate.zoneRef',
            'candidate.regionRef',
            'candidate.function',
            'candidate.workState'
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('candidate', function ($q) use ($status, $requisitionType) {
                if ($status !== 'All') {
                    $q->where('final_status', $status);
                } else {
                    $q->whereIn('final_status', ['A', 'D']);
                }

                if (!empty($requisitionType)) {
                    $q->where('requisition_type', $requisitionType);
                }
            })
            ->paginate(20)
            ->withQueryString();

        return view('reports.payment-jv', compact(
            'records',
            'financialYear',
            'month',
            'year',
            'status'
        ));
    }

    public function PaymentJVExport(Request $request)
    {
        return Excel::download(
            new PaymentJVExport(
                $request->financial_year,
                $request->month,
                $request->status,
                $request->requisition_type ?? ''
            ),
            'Payment_JV_Report.xlsx'
        );
    }

   public function tat(Request $request, HierarchyAccessService $hierarchyService)
{
    
    
    // Get all filter parameters
    $financialYear = $request->get('financial_year');
    $month = $request->get('month');
    $departmentId = $request->get('department_id');
    $requisitionType = $request->get('requisition_type');
    $status = $request->get('status');

    // Hierarchy filters
    $buId = $request->get('bu', '');
    $zoneId = $request->get('zone', '');
    $regionId = $request->get('region', '');
    $territoryId = $request->get('territory', '');
    $verticalId = $request->get('vertical', '');
    $employeeId = $request->get('employee', '');
    $subDepartmentId = $request->get('sub_department', '');

    // Convert "All" to empty string
    if ($subDepartmentId === 'All') $subDepartmentId = '';
    if ($verticalId === 'All') $verticalId = '';
    if ($buId === 'All') $buId = '';
    if ($zoneId === 'All') $zoneId = '';
    if ($regionId === 'All') $regionId = '';
    if ($territoryId === 'All') $territoryId = '';
    if ($employeeId === 'All') $employeeId = '';
    if ($departmentId === 'All') $departmentId = '';

    // Set default financial year
    if (!$financialYear) {
        $currentMonth = date('n');
        $currentYear = date('Y');
        $financialYear = ($currentMonth >= 4)
            ? $currentYear . '-' . ($currentYear + 1)
            : ($currentYear - 1) . '-' . $currentYear;
    }

    // Parse financial year
    [$startYear, $endYear] = explode('-', $financialYear);

    // Get logged in user
    $user = Auth::user();
    $employee = \App\Models\Employee::where('employee_id', $user->emp_id)->first();
    $access_level = $hierarchyService->getAccessLevel($employee);
    
    // Check if user is in Management department
    $isManagementDept = $employee && $employee->department == 18;
    $hasFullAccess = $user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $isManagementDept;
    
   

    // ============================================
    // Get hierarchy lists for dropdowns
    // ============================================
    $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($user->emp_id);
    $zones = $hierarchyService->getAssociatedZoneList($user->emp_id);
    $regions = $hierarchyService->getAssociatedRegionList($user->emp_id);
    $territories = $hierarchyService->getAssociatedTerritoryList($user->emp_id);
    $departments = $hierarchyService->getAssociatedDepartmentList($user->emp_id);
    $subDepartments = $hierarchyService->getAssociatedSubDepartmentList($user->emp_id);
    
    // Get verticals
    $verticals = DB::table('core_vertical')
        ->where('is_active', 1)
        ->orderBy('vertical_name')
        ->pluck('vertical_name', 'id')
        ->prepend('All Verticals', '')
        ->toArray();

    // Get employees for reporting manager filter (based on hierarchy)
    if ($hasFullAccess) {
        // Full access users see all active employees
        $employees = DB::table('core_employee')
            ->where('emp_status', 'A')
            ->orderBy('emp_name')
            ->pluck('emp_name', 'employee_id')
            ->prepend('All Employees', '')
            ->toArray();
    } else {
        // Restricted users only see their team hierarchy
        $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
        $employees = DB::table('core_employee')
            ->whereIn('employee_id', $allowedEmpIds)
            ->where('emp_status', 'A')
            ->orderBy('emp_name')
            ->pluck('emp_name', 'employee_id')
            ->prepend('All Employees', '')
            ->toArray();
    }

    // ============================================
    // Build the main query
    // ============================================
    $query = CandidateMaster::query()
        ->leftJoin('manpower_requisitions as mr', 'mr.id', '=', 'candidate_master.requisition_id')
        ->leftJoin('core_business_unit as bu', 'bu.id', '=', 'candidate_master.business_unit')
        ->leftJoin('core_zone as zone', 'zone.id', '=', 'candidate_master.zone')
        ->leftJoin('core_region as region', 'region.id', '=', 'candidate_master.region')
        ->leftJoin('core_territory as territory', 'territory.id', '=', 'candidate_master.territory')
        ->leftJoin('core_vertical as vertical', 'vertical.id', '=', 'candidate_master.vertical_id')
        ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')
        ->leftJoin('core_sub_department as subdept', 'subdept.id', '=', 'candidate_master.sub_department')
        ->leftJoin('core_employee as manager', 'manager.employee_id', '=', 'candidate_master.reporting_manager_employee_id')

        // Latest Agreement (Unsigned)
        ->leftJoinSub(
            DB::table('agreement_documents')
                ->select('candidate_id', DB::raw('MAX(id) as id'))
                ->where('document_type', 'agreement')
                ->where('sign_status', 'UNSIGNED')
                ->groupBy('candidate_id'),
            'created_ad',
            'created_ad.candidate_id',
            '=',
            'candidate_master.id'
        )
        ->leftJoin('agreement_documents as adc', 'adc.id', '=', 'created_ad.id')

        // Latest Agreement (Signed)
        ->leftJoinSub(
            DB::table('agreement_documents')
                ->select('candidate_id', DB::raw('MAX(id) as id'))
                ->where('document_type', 'agreement')
                ->where('sign_status', 'SIGNED')
                ->groupBy('candidate_id'),
            'signed_ad',
            'signed_ad.candidate_id',
            '=',
            'candidate_master.id'
        )
        ->leftJoin('agreement_documents as ads', 'ads.id', '=', 'signed_ad.id')

        // Latest Courier
        ->leftJoinSub(
            DB::table('agreement_couriers')
                ->select('agreement_document_id', DB::raw('MAX(id) as id'))
                ->groupBy('agreement_document_id'),
            'latest_ac',
            'latest_ac.agreement_document_id',
            '=',
            'ads.id'
        )
        ->leftJoin('agreement_couriers as ac', 'ac.id', '=', 'latest_ac.id')

        ->select(
            'candidate_master.*',
            'mr.submission_date',
            'mr.hr_verification_date',
            'mr.approval_date',
            'candidate_master.file_created_date',
            'candidate_master.contract_start_date',
            'adc.created_at as agreement_created_date',
            'ads.created_at as agreement_uploaded_date',
            'ac.dispatch_date',
            'ac.received_date',
            'bu.business_unit_name',
            'zone.zone_name',
            'region.region_name',
            'territory.territory_name',
            'vertical.vertical_name',
            'dept.department_name',
            'subdept.sub_department_name',
            'manager.emp_name as reporting_manager_name'
        );

    // ============================================
    // Apply filters
    // ============================================
    
    // 1. Hierarchy restriction (only for non-full-access users)
    if (!$hasFullAccess) {
        $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
        $allowedEmpIdsString = array_map('strval', $allowedEmpIds);
        $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIdsString);

        }
    
    // 2. Employee filter (when a specific manager is selected)
    if ($employeeId && $employeeId !== '') {
        // Get all team members under this manager (including indirect reports)
        $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
        $teamMemberIdsString = array_map('strval', $teamMemberIds);
        $query->whereIn('candidate_master.reporting_manager_employee_id', $teamMemberIdsString);

        }
    
    // 3. Department filter
    if ($departmentId && $departmentId !== '') {
        $query->where('candidate_master.department_id', $departmentId);

        }
    
    // 4. Sub-department filter
    if ($subDepartmentId && $subDepartmentId !== '') {
        $query->where('candidate_master.sub_department', $subDepartmentId);

        }
    
    // 5. Vertical filter
    if ($verticalId && $verticalId !== '') {
        $query->where('candidate_master.vertical_id', $verticalId);

        }
    
    // 6. BU filter
    if ($buId && $buId !== '') {
        $query->where('candidate_master.business_unit', $buId);

        }
    
    // 7. Zone filter
    if ($zoneId && $zoneId !== '') {
        $query->where('candidate_master.zone', $zoneId);

        }
    
    // 8. Region filter
    if ($regionId && $regionId !== '') {
        $query->where('candidate_master.region', $regionId);

        }
    
    // 9. Territory filter
    if ($territoryId && $territoryId !== '') {
        $query->where('candidate_master.territory', $territoryId);

        }
    
    // 10. Requisition type filter
    if ($requisitionType && $requisitionType !== '') {
        $query->where('candidate_master.requisition_type', $requisitionType);
    }
    
    // 11. Status filter
    if ($status && $status !== '') {
        $query->where('mr.status', $status);
    }
    
    // 12. Date range filter
    if ($month && $month !== '') {
        $year = ($month >= 4) ? $startYear : $endYear;
        $startDate = "{$year}-{$month}-01";
        $endDate = \Carbon\Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');
        $query->whereNotNull('candidate_master.contract_start_date')
            ->whereBetween('candidate_master.contract_start_date', [$startDate, $endDate]);
    } else {
        $startDate = $startYear . '-04-01';
        $endDate = $endYear . '-03-31';
        $query->whereNotNull('candidate_master.contract_start_date')
            ->whereBetween('candidate_master.contract_start_date', [$startDate, $endDate]);
    }

    // ============================================
    // Get results
    // ============================================
    $records = $query->orderBy('candidate_master.contract_start_date', 'desc')->paginate(20);
    $allRecords = $query->get();
    

    
    // ============================================
    // Calculate TAT summaries
    // ============================================
    $stages = [
        'hr' => ['from' => 'submission_date', 'to' => 'hr_verification_date'],
        'approval' => ['from' => 'hr_verification_date', 'to' => 'approval_date'],
        'agreement_create' => ['from' => 'approval_date', 'to' => 'agreement_created_date'],
        'agreement_upload' => ['from' => 'agreement_created_date', 'to' => 'agreement_uploaded_date'],
        'courier_dispatch' => ['from' => 'agreement_uploaded_date', 'to' => 'dispatch_date'],
        'courier_delivery' => ['from' => 'dispatch_date', 'to' => 'received_date'],
        'file_creation' => ['from' => 'received_date', 'to' => 'file_created_date'],
    ];

    $stageData = [];
    foreach ($stages as $key => $s) {
        $stageData[$key] = [];
    }

    foreach ($allRecords as $row) {
        foreach ($stages as $key => $s) {
            if ($key === 'file_creation') {
                $fromDate = $row->received_date
                    ?? $row->agreement_uploaded_date
                    ?? $row->agreement_created_date
                    ?? $row->approval_date;
                $toDate = $row->file_created_date;
            } else {
                $fromDate = $row->{$s['from']} ?? null;
                $toDate = $row->{$s['to']} ?? null;
            }

            if ($fromDate && $toDate) {
                $days = max(0, ceil(
                    \Carbon\Carbon::parse($fromDate)
                        ->diffInDays($toDate)
                ));
                $stageData[$key][] = $days;
            }
        }
    }

    $getSummary = function ($data) {
        return [
            'total' => count($data),
            'avg' => count($data) ? round(array_sum($data) / count($data), 2) : 0,
            'within_1' => count(array_filter($data, fn($d) => $d <= 1)),
            'within_3' => count(array_filter($data, fn($d) => $d > 1 && $d <= 3)),
            'above_3' => count(array_filter($data, fn($d) => $d > 3)),
        ];
    };

    $summaries = [];
    foreach ($stageData as $key => $data) {
        $summaries[$key] = $getSummary($data);
    }

    return view('reports.tat', compact(
        'records',
        'summaries',
        'stages',
        'financialYear',
        'month',
        'departments',
        'departmentId',
        'requisitionType',
        'status',
        'businessUnits',
        'zones',
        'regions',
        'territories',
        'verticals',
        'employees',
        'buId',
        'zoneId',
        'regionId',
        'territoryId',
        'verticalId',
        'employeeId',
        'access_level',
        'subDepartments',
        'subDepartmentId'
    ));
}

    public function tatExport(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|string',
            'month' => 'nullable|integer|between:1,12',
            'department_id' => 'nullable|integer',
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB',
            'status' => 'nullable|string',
            // ✅ New hierarchy filters
            'bu' => 'nullable|string',
            'zone' => 'nullable|string',
            'region' => 'nullable|string',
            'territory' => 'nullable|string',
            'vertical' => 'nullable|string',
            'employee' => 'nullable|string',
        ]);

        return Excel::download(
            new TatReportExport(
                $request->financial_year,
                $request->month,
                $request->department_id,
                $request->requisition_type,
                $request->status,
                // ✅ Pass hierarchy filters
                $request->bu ?? 'All',
                $request->zone ?? 'All',
                $request->region ?? 'All',
                $request->territory ?? 'All',
                $request->vertical ?? 'All',
                $request->employee ?? 'All'
            ),
            'TAT_Report.xlsx'
        );
    }
}
