<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\CoreDepartment;
use App\Models\ManpowerRequisition;
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
            ->with('department')
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
                $request->requisition_type ?? ''
            ),
            'JV_Report.xlsx'
        );
    }

    public function TDSJVReport(Request $request)
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
        $query = SalaryProcessing::with('candidate')
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
            });

        $records = $query->paginate(20)->withQueryString();

        return view('reports.tds-jv', compact(
            'records',
            'financialYear',
            'month',
            'year',
            'status'
        ));
    }

    public function TDSJVExport(Request $request)
    {
        return Excel::download(
            new TDSJVExport(
                $request->financial_year,
                $request->month,
                $request->status,
                $request->requisition_type ?? ''
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

    public function tat(Request $request)
    {
        $financialYear = $request->get('financial_year');
        $month = $request->get('month');
        $departmentId = $request->get('department_id');
        $requisitionType = $request->get('requisition_type');
        $status = $request->get('status');

        // Default financial year
        if (!$financialYear) {
            $currentMonth = date('n');
            $currentYear = date('Y');

            if ($currentMonth >= 4) {
                $financialYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $financialYear = ($currentYear - 1) . '-' . $currentYear;
            }
        }

        [$startYear, $endYear] = explode('-', $financialYear);

        $query = ManpowerRequisition::query();

        // Financial year filter
        if ($month) {

            $year = ($month >= 4) ? $startYear : $endYear;

            $startDate = "{$year}-{$month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth();

            $query->whereBetween('submission_date', [$startDate, $endDate]);
        } else {

            $query->whereBetween('submission_date', [
                $startYear . '-04-01',
                $endYear . '-03-31'
            ]);
        }

        // Department filter
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        // Requisition type
        if (!empty($requisitionType)) {
            $query->where('requisition_type', $requisitionType);
        }

        // Status
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $records = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = CoreDepartment::orderBy('department_name')->get();

        return view('reports.tat', compact(
            'records',
            'financialYear',
            'month',
            'departmentId',
            'requisitionType',
            'status',
            'departments'
        ));
    }

    public function tatExport(Request $request)
    {
        return Excel::download(
            new TatReportExport(
                $request->financial_year,
                $request->month,
                $request->department_id,
                $request->requisition_type,
                $request->status
            ),
            'TAT_Report.xlsx'
        );
    }
}
