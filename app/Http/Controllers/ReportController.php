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
            ->where('payment_status', 'pending')
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
        $financialYear = $request->get('financial_year');
        $month = $request->get('month');
        $departmentId = $request->get('department_id');
        $subDepartmentId = $request->get('sub_department', 'All');

        $requisitionType = $request->get('requisition_type');
        $status = $request->get('status');

        // Hierarchy filters - get from URL, default to 'All'
        $buId = $request->get('bu', 'All');
        $zoneId = $request->get('zone', 'All');
        $regionId = $request->get('region', 'All');
        $territoryId = $request->get('territory', 'All');
        $verticalId = $request->get('vertical', 'All');
        $employeeId = $request->get('employee', 'All');

        if (!$financialYear) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            $financialYear = ($currentMonth >= 4)
                ? $currentYear . '-' . ($currentYear + 1)
                : ($currentYear - 1) . '-' . $currentYear;
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        // Get logged in user
        $user = Auth::user();
        $employee = \App\Models\Employee::where('employee_id', $user->emp_id)->first();

        // Flags to track auto-applied filters
        $autoAppliedDepartment = false;
        $autoAppliedSubDepartment = false;

        // For non-admin, non-Sales users, auto-apply their own department and sub-department
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management']) && !$hierarchyService->isSalesDepartment($user->emp_id)) {
            if (empty($departmentId) && $employee && $employee->department) {
                $departmentId = $employee->department;
                $autoAppliedDepartment = true;
            }
            if (($subDepartmentId == 'All' || empty($subDepartmentId)) && $employee && $employee->sub_department) {
                $subDepartmentId = $employee->sub_department;
                $autoAppliedSubDepartment = true;
            }
        }

        // Build base query with DISTINCT to avoid duplicates
        $query = CandidateMaster::query()
            ->select(
                'candidate_master.*',
                'mr.submission_date',
                'mr.hr_verification_date',
                'mr.approval_date',
                'candidate_master.file_created_date',
                'candidate_master.contract_start_date',
                'rm.emp_name as reporting_manager_name',
                'appr.emp_name as approver_name',
                DB::raw('(SELECT created_at FROM agreement_documents WHERE candidate_id = candidate_master.id AND document_type = "agreement" AND sign_status = "UNSIGNED" ORDER BY created_at DESC LIMIT 1) as agreement_created_date'),
                DB::raw('(SELECT created_at FROM agreement_documents WHERE candidate_id = candidate_master.id AND document_type = "agreement" AND sign_status = "SIGNED" ORDER BY created_at DESC LIMIT 1) as agreement_uploaded_date'),
                DB::raw('(SELECT dispatch_date FROM agreement_couriers WHERE agreement_document_id = (SELECT id FROM agreement_documents WHERE candidate_id = candidate_master.id AND document_type = "agreement" AND sign_status = "SIGNED" ORDER BY created_at DESC LIMIT 1) ORDER BY id DESC LIMIT 1) as dispatch_date'),
                DB::raw('(SELECT received_date FROM agreement_couriers WHERE agreement_document_id = (SELECT id FROM agreement_documents WHERE candidate_id = candidate_master.id AND document_type = "agreement" AND sign_status = "SIGNED" ORDER BY created_at DESC LIMIT 1) ORDER BY id DESC LIMIT 1) as received_date'),
                'dept.department_name',
                'sub_dept.sub_department_name'
            )
            ->leftJoin('manpower_requisitions as mr', 'mr.id', '=', 'candidate_master.requisition_id')
            ->leftJoin('core_employee as rm', 'rm.employee_id', '=', 'candidate_master.reporting_manager_employee_id')
            ->leftJoin('core_employee as appr', 'appr.employee_id', '=', 'mr.approver_id')
            ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')
            ->leftJoin('core_sub_department as sub_dept', 'sub_dept.id', '=', 'candidate_master.sub_department')
            ->whereIn('candidate_master.final_status', ['A', 'D'])
            ->distinct('candidate_master.id');

        // Apply hierarchy access control
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $allowedEmpIds[] = $user->emp_id;
            $allowedEmpIds = array_unique($allowedEmpIds);
            $allowedEmpIdsString = array_map('strval', $allowedEmpIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIdsString);

            $accessLevel = $hierarchyService->getAccessLevel($employee);

            $territoryIds = [];

            switch ($accessLevel) {
                case 'territory':
                    if ($employee->territory && $employee->territory != 0) {
                        $territoryIds = [$employee->territory];
                    }
                    break;
                case 'region':
                    if ($employee->region && $employee->region != 0) {
                        $territoryIds = \App\Models\Employee::where('region', $employee->region)
                            ->where('emp_status', 'A')
                            ->whereNotNull('territory')
                            ->where('territory', '!=', 0)
                            ->distinct()
                            ->pluck('territory')
                            ->toArray();
                    }
                    break;
                case 'zone':
                    if ($employee->zone && $employee->zone != 0) {
                        $regionsInZone = \App\Models\Employee::where('zone', $employee->zone)
                            ->where('emp_status', 'A')
                            ->whereNotNull('region')
                            ->where('region', '!=', 0)
                            ->distinct()
                            ->pluck('region')
                            ->toArray();

                        if (!empty($regionsInZone)) {
                            $territoryIds = \App\Models\Employee::whereIn('region', $regionsInZone)
                                ->where('emp_status', 'A')
                                ->whereNotNull('territory')
                                ->where('territory', '!=', 0)
                                ->distinct()
                                ->pluck('territory')
                                ->toArray();
                        }
                    }
                    break;
                case 'bu':
                    if ($employee->bu && $employee->bu != 0) {
                        $zonesInBU = \App\Models\Employee::where('bu', $employee->bu)
                            ->where('emp_status', 'A')
                            ->whereNotNull('zone')
                            ->where('zone', '!=', 0)
                            ->distinct()
                            ->pluck('zone')
                            ->toArray();

                        if (!empty($zonesInBU)) {
                            $regionsInBU = \App\Models\Employee::whereIn('zone', $zonesInBU)
                                ->where('emp_status', 'A')
                                ->whereNotNull('region')
                                ->where('region', '!=', 0)
                                ->distinct()
                                ->pluck('region')
                                ->toArray();

                            if (!empty($regionsInBU)) {
                                $territoryIds = \App\Models\Employee::whereIn('region', $regionsInBU)
                                    ->where('emp_status', 'A')
                                    ->whereNotNull('territory')
                                    ->where('territory', '!=', 0)
                                    ->distinct()
                                    ->pluck('territory')
                                    ->toArray();
                            }
                        }
                    }
                    break;
            }

            if (!empty($territoryIds)) {
                $query->whereIn('candidate_master.territory', $territoryIds);
            }
        }

        // Apply user-selected hierarchy filters
        if ($buId && $buId != 'All') {
            $query->where('candidate_master.business_unit', $buId);
        }

        if ($zoneId && $zoneId != 'All') {
            $query->where('candidate_master.zone', $zoneId);
        }

        if ($regionId && $regionId != 'All') {
            $query->where('candidate_master.region', $regionId);
        }

        if ($territoryId && $territoryId != 'All') {
            $query->where('candidate_master.territory', $territoryId);
        }

        if ($verticalId && $verticalId != 'All') {
            $query->where('candidate_master.vertical_id', $verticalId);
        }

        // Apply employee filter
        if ($employeeId && $employeeId != 'All') {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $teamMemberIds[] = $employeeId;
            $teamMemberIdsString = array_map('strval', array_unique($teamMemberIds));
            $query->whereIn('candidate_master.reporting_manager_employee_id', $teamMemberIdsString);
        }

        // Apply department filter
        if ($departmentId && $departmentId != 'All') {
            $query->where('candidate_master.department_id', $departmentId);
        }

        // Apply sub-department filter
        if ($subDepartmentId && $subDepartmentId != 'All') {
            $query->where('candidate_master.sub_department', $subDepartmentId);
        }

        // Apply other filters
        if ($requisitionType && $requisitionType !== 'All') {
            $query->where('candidate_master.requisition_type', $requisitionType);
        }

        if ($status && $status != 'All') {
            $query->where('mr.status', $status);
        }

        // Filter by contract start date
        // Filter by contract start date
        if ($month && $month != 'All') {

            $year = ($month >= 4) ? $startYear : $endYear;

            $startDate = "{$year}-{$month}-01";
            $endDate = \Carbon\Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

            $query->whereBetween(
                'candidate_master.contract_start_date',
                [$startDate, $endDate]
            );
        } else {

            $fyStart = $startYear . '-04-01';
            $fyEnd   = $endYear . '-03-31';

            $query->whereBetween(
                'candidate_master.contract_start_date',
                [$fyStart, $fyEnd]
            );
        }

        // Get all records for TAT calculation
        $summaryQuery = clone $query;
        $allRecords = $summaryQuery->get();

        // Define stages
        $stages = [
            'hr' => ['from' => 'submission_date', 'to' => 'hr_verification_date'],
            'approval' => ['from' => 'hr_verification_date', 'to' => 'approval_date'],
            'agreement_create' => ['from' => 'approval_date', 'to' => 'agreement_created_date'],
            'agreement_upload' => ['from' => 'agreement_created_date', 'to' => 'agreement_uploaded_date'],
            'courier_dispatch' => ['from' => 'agreement_uploaded_date', 'to' => 'dispatch_date'],
            'courier_delivery' => ['from' => 'dispatch_date', 'to' => 'received_date'],
            'file_creation' => ['from' => 'received_date', 'to' => 'file_created_date'],
        ];

        // Calculate TAT data
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

        // Get departments and sub-departments based on selected employee
        if ($employeeId && $employeeId != 'All') {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $teamMemberIds[] = $employeeId;
            $teamMemberIdsString = array_map('strval', array_unique($teamMemberIds));

            $departmentsList = CandidateMaster::whereIn('final_status', ['A', 'D'])
                ->whereIn('reporting_manager_employee_id', $teamMemberIdsString)
                ->whereNotNull('department_id')
                ->distinct()
                ->pluck('department_id')
                ->toArray();

            $departments = DB::table('core_department')
                ->whereIn('id', $departmentsList)
                ->where('is_active', 1)
                ->orderBy('department_name')
                ->pluck('department_name', 'id')
                ->toArray();

            $subDepartmentsList = CandidateMaster::whereIn('final_status', ['A', 'D'])
                ->whereIn('reporting_manager_employee_id', $teamMemberIdsString)
                ->whereNotNull('sub_department')
                ->distinct()
                ->pluck('sub_department')
                ->toArray();

            $subDepartments = DB::table('core_sub_department')
                ->whereIn('id', $subDepartmentsList)
                ->where('is_active', 1)
                ->orderBy('sub_department_name')
                ->pluck('sub_department_name', 'id')
                ->toArray();

            $subDepartments = ['All' => 'All Sub Departments'] + $subDepartments;
        } else {
            $departments = $hierarchyService->getAssociatedDepartmentList($user->emp_id);
            $subDepartments = DB::table('core_sub_department')
                ->where('is_active', 1)
                ->orderBy('sub_department_name')
                ->pluck('sub_department_name', 'id')
                ->toArray();
            $subDepartments = ['All' => 'All Sub Departments'] + $subDepartments;
        }

        // Get verticals
        $verticals = DB::table('core_vertical')
            ->where('is_active', 1)
            ->orderBy('vertical_name')
            ->pluck('vertical_name', 'id')
            ->toArray();
        $verticals = ['All' => 'All Verticals'] + $verticals;

        // Get employees for dropdown
        if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $employees = DB::table('core_employee')
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->toArray();
            $employees = ['All' => 'All Employees'] + $employees;
        } else {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $allowedEmpIds[] = $user->emp_id;
            $employees = DB::table('core_employee')
                ->whereIn('employee_id', $allowedEmpIds)
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->toArray();
            $employees = ['All' => 'All Employees'] + $employees;
        }

        // Get paginated records
        $records = $query->orderBy('candidate_master.contract_start_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $showLocationFilters = $hierarchyService->shouldShowLocationFilters($user->emp_id);

        $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($user->emp_id);
        $zones = $hierarchyService->getAssociatedZoneList($user->emp_id);
        $regions = $hierarchyService->getAssociatedRegionList($user->emp_id);
        $territories = $hierarchyService->getAssociatedTerritoryList($user->emp_id);

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
            'subDepartments',
            'subDepartmentId',
            'buId',
            'zoneId',
            'regionId',
            'territoryId',
            'verticalId',
            'employeeId',
            'showLocationFilters',
            'autoAppliedDepartment',
            'autoAppliedSubDepartment'
        ));
    }
    public function tatExport(Request $request)
    {  //dd($request->all());
        $request->validate([
            'financial_year' => 'required|string',
            'month' => 'nullable|integer|between:1,12',
            'department_id' => 'nullable|integer',
            'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
            'status' => 'nullable|string',
            // ✅ New hierarchy filters
            'bu' => 'nullable|string',
            'zone' => 'nullable|string',
            'region' => 'nullable|string',
            'territory' => 'nullable|string',
            'vertical' => 'nullable|string',
            'employee' => 'nullable|string',
        ]);
        //dd($request->all());

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
    /**
     * Get filters data based on selected employee
     */
    public function getEmployeeFilters($employeeId, HierarchyAccessService $hierarchyService)
    {
        try {
            $employee = \App\Models\Employee::where('employee_id', $employeeId)->first();

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found']);
            }

            // Get all team members under this manager
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $teamMemberIdsString = array_map('strval', $teamMemberIds);

            // Get all candidates under this manager's team
            $candidates = CandidateMaster::whereIn('final_status', ['A', 'D'])
                ->whereIn('reporting_manager_employee_id', $teamMemberIdsString)
                ->get();

            // Collect unique values
            $bus = [];
            $zones = [];
            $regions = [];
            $territories = [];
            $verticals = [];
            $subDepartments = [];
            $departments = [];

            foreach ($candidates as $candidate) {
                if ($candidate->business_unit && !in_array($candidate->business_unit, $bus)) {
                    $bus[] = $candidate->business_unit;
                }
                if ($candidate->zone && !in_array($candidate->zone, $zones)) {
                    $zones[] = $candidate->zone;
                }
                if ($candidate->region && !in_array($candidate->region, $regions)) {
                    $regions[] = $candidate->region;
                }
                if ($candidate->territory && !in_array($candidate->territory, $territories)) {
                    $territories[] = $candidate->territory;
                }
                if ($candidate->vertical_id && !in_array($candidate->vertical_id, $verticals)) {
                    $verticals[] = $candidate->vertical_id;
                }
                if ($candidate->sub_department && !in_array($candidate->sub_department, $subDepartments)) {
                    $subDepartments[] = $candidate->sub_department;
                }
                if ($candidate->department_id && !in_array($candidate->department_id, $departments)) {
                    $departments[] = $candidate->department_id;
                }
            }

            // Add manager's own department and sub-department to the lists
            if ($employee->department && !in_array($employee->department, $departments)) {
                $departments[] = $employee->department;
            }
            if ($employee->sub_department && !in_array($employee->sub_department, $subDepartments)) {
                $subDepartments[] = $employee->sub_department;
            }

            // Get names for the IDs
            $buList = !empty($bus) ? DB::table('core_business_unit')->whereIn('id', $bus)->pluck('business_unit_name', 'id')->toArray() : [];
            $zoneList = !empty($zones) ? DB::table('core_zone')->whereIn('id', $zones)->pluck('zone_name', 'id')->toArray() : [];
            $regionList = !empty($regions) ? DB::table('core_region')->whereIn('id', $regions)->pluck('region_name', 'id')->toArray() : [];
            $territoryList = !empty($territories) ? DB::table('core_territory')->whereIn('id', $territories)->pluck('territory_name', 'id')->toArray() : [];
            $verticalList = !empty($verticals) ? DB::table('core_vertical')->whereIn('id', $verticals)->where('is_active', 1)->pluck('vertical_name', 'id')->toArray() : [];
            $subDepartmentList = !empty($subDepartments) ? DB::table('core_sub_department')->whereIn('id', $subDepartments)->where('is_active', 1)->pluck('sub_department_name', 'id')->toArray() : [];
            $departmentList = !empty($departments) ? DB::table('core_department')->whereIn('id', $departments)->where('is_active', 1)->pluck('department_name', 'id')->toArray() : [];

            return response()->json([
                'success' => true,
                'data' => [
                    'business_units' => $buList,
                    'zones' => $zoneList,
                    'regions' => $regionList,
                    'territories' => $territoryList,
                    'verticals' => $verticalList,
                    'sub_departments' => $subDepartmentList,
                    'departments' => $departmentList,
                    // Add manager's own values for auto-selection
                    'manager_department' => $employee->department,
                    'manager_sub_department' => $employee->sub_department
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
