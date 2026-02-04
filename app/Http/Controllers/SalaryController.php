<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Services\SalaryCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryExport;
use App\Exports\DetailedSalaryReportExport;
use App\Exports\ManagementSalaryReportExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class SalaryController extends Controller
{
    public function index()
    {
        return view('hr.salary.index');
    }

    // Update the process method in SalaryController
    public function process(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'force' => 'sometimes|boolean',

            'candidate_id'   => 'sometimes|integer|exists:candidate_master,id',
            'candidate_ids'  => 'sometimes|array',
            'candidate_ids.*' => 'integer|exists:candidate_master,id',

            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',

            'candidate_arrears' => 'sometimes|array',
            'local_arrears'     => 'sometimes|array',
        ]);

        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];
        $force = $validated['force'] ?? false;

        $requisitionType  = $validated['requisition_type'] ?? null;
        $candidateArrears = $validated['candidate_arrears'] ?? [];
        $localArrears     = $validated['local_arrears'] ?? [];

        DB::beginTransaction();

        try {
            // Build candidate query
            $query = CandidateMaster::where('final_status', 'A');

            if ($requisitionType && $requisitionType !== 'All') {
                $query->where('requisition_type', $requisitionType);
            }

            if ($request->filled('candidate_id')) {
                $query->where('id', $validated['candidate_id']);
            }

            if ($request->filled('candidate_ids')) {
                $query->whereIn('id', $validated['candidate_ids']);
            }

            $candidates = $query->get();

            if ($candidates->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active candidates found'
                ], 404);
            }

            $processed = 0;
            $skipped   = 0;
            $errors    = [];
            $arrearsIncluded = 0;

            foreach ($candidates as $candidate) {

                $existing = SalaryProcessing::where('candidate_id', $candidate->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                if ($existing && $existing->processed_at && !$force) {
                    $skipped++;
                    continue;
                }

                try {
                    // Core salary calculation
                    $salaryData = SalaryCalculator::calculate($candidate, $month, $year);

                    // Resolve arrears (priority based)
                    $arrearAmount  = 0;
                    $arrearDays    = 0;
                    $arrearRemarks = null;

                    if (!empty($candidateArrears[$candidate->id])) {
                        $arrearAmount  = $candidateArrears[$candidate->id]['amount'] ?? 0;
                        $arrearDays    = $candidateArrears[$candidate->id]['days'] ?? 0;
                        $arrearRemarks = $candidateArrears[$candidate->id]['remarks'] ?? null;
                        $arrearsIncluded++;
                    } else {
                        $arrearKey = "{$candidate->id}_{$month}_{$year}";
                        if (!empty($localArrears[$arrearKey])) {
                            $arrearAmount  = $localArrears[$arrearKey]['amount'] ?? 0;
                            $arrearDays    = $localArrears[$arrearKey]['days'] ?? 0;
                            $arrearRemarks = $localArrears[$arrearKey]['remarks'] ?? null;
                            $arrearsIncluded++;
                        } elseif ($existing) {
                            $arrearAmount  = $existing->arrear_amount ?? 0;
                            $arrearDays    = $existing->arrear_days ?? 0;
                            $arrearRemarks = $existing->arrear_remarks ?? null;
                        }
                    }

                    SalaryProcessing::updateOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'month'        => $month,
                            'year'         => $year,
                        ],
                        array_merge($salaryData, [
                            'arrear_amount'  => $arrearAmount,
                            'arrear_days'    => $arrearDays,
                            'arrear_remarks' => $arrearRemarks,

                            'status'         => 'processed',
                            'processed_by'   => auth()->id(),
                            'processed_at'   => now(),
                        ])
                    );

                    $processed++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'candidate_code' => $candidate->candidate_code,
                        'candidate_name' => $candidate->candidate_name,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Salary processed. Processed: {$processed}, Skipped: {$skipped}" .
                    ($arrearsIncluded ? ", Arrears included: {$arrearsIncluded}" : ''),
                'processed' => $processed,
                'skipped'   => $skipped,
                'errors'    => $errors,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Salary processing failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Update the updateArrear method (only for processed salaries)
    public function updateArrear(Request $request)
    {
        $request->validate([
            'salary_id' => 'required|integer|exists:salary_processing,id',
            'candidate_id' => 'required|integer|exists:candidate_master,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'arrear_days' => 'required|numeric|min:0|max:31',
            'arrear_amount' => 'required|numeric|min:0',
            'arrear_remarks' => 'nullable|string|max:500',
        ]);

        try {
            $salary = SalaryProcessing::findOrFail($request->salary_id);

            // Only update if salary is already processed
            if (!$salary->processed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary is not processed yet. Please process salary first or use local calculation.'
                ], 400);
            }

            // Update arrear fields
            $salary->arrear_days = $request->arrear_days;
            $salary->arrear_amount = $request->arrear_amount;
            $salary->arrear_remarks = $request->arrear_remarks;
            $salary->save();

            return response()->json([
                'success' => true,
                'message' => 'Arrear updated successfully',
                'data' => $salary
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to update arrear: " . $e->getMessage(), $request->all());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update arrear: ' . $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request)
    {
        $request->merge([
            'month' => (int) $request->month,
            'year'  => (int) $request->year,
        ]);

        $request->validate([
            'month' => 'required|integer',
            'year'  => 'required|integer',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $month = $request->month;
        $year  = $request->year;
        $requisitionType = $request->requisition_type;

        // Build query
        //$query = CandidateMaster::where('final_status', 'A');
        $salaryMonthEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = CandidateMaster::where('final_status', 'A')
            ->whereDate('contract_start_date', '<=', $salaryMonthEnd);

        // Apply requisition_type filter if provided and not 'All'
        if ($requisitionType && $requisitionType !== 'All') {
            $query->where('requisition_type', $requisitionType);
        }

        $candidates = $query->get();
        //dd($salaryMonthEnd);
        $result = [];

        foreach ($candidates as $candidate) {
            // Check if already processed
            $salary = SalaryProcessing::where('candidate_id', $candidate->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($salary) {
                // Already processed → use saved data
                $row = $salary->toArray();
                $row['candidate'] = $candidate;
                $row['processed'] = true;
            } else {
                // Not processed → calculate preview
                try {
                    $calc = SalaryCalculator::calculate($candidate, $month, $year);

                    $row = array_merge($calc, [
                        'candidate_id' => $candidate->id,
                        'candidate'    => $candidate,
                        'processed'    => false,
                        'arrear_amount' => 0,
                        'arrear_days' => 0,
                    ]);
                } catch (\Exception $e) {
                    // Attendance missing etc.
                    $row = [
                        'candidate_id' => $candidate->id,
                        'candidate'    => $candidate,
                        'error'        => $e->getMessage(),
                        'processed'    => false,
                        'arrear_amount' => 0,
                        'arrear_days' => 0,
                    ];
                }
            }

            $result[] = $row;
        }

        return response()->json($result);
    }

    public function checkExists(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year'  => 'required|integer',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $query = SalaryProcessing::where('month', $request->month)
            ->where('year', $request->year);

        // Apply requisition_type filter if provided
        if ($request->filled('requisition_type') && $request->requisition_type !== 'All') {
            $query->where('requisition_type', $request->requisition_type);
        }

        $count = $query->count();

        return response()->json([
            'exists' => $count > 0,
            'count'  => $count
        ]);
    }

    public function downloadPayslip($id)
    {
        $salary = SalaryProcessing::with('candidate')->findOrFail($id);

        if (!$salary->processed_at) {
            abort(403, 'Salary not processed yet');
        }

        $pdf = Pdf::loadView('hr.salary.payslip', compact('salary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(
            "Payslip_{$salary->candidate->candidate_code}_{$salary->month}_{$salary->year}.pdf"
        );
    }


    public function exportExcel(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $month = $request->month;
        $year = $request->year;
        $requisitionType = $request->requisition_type ?? 'All';

        $filename = "Salary_Report_{$month}_{$year}";
        if ($requisitionType !== 'All') {
            $filename .= "_{$requisitionType}";
        }

        return Excel::download(
            new SalaryExport($month, $year, $requisitionType),
            "{$filename}.xlsx"
        );
    }

    public function detailedReportView()
    {
        return view('hr.salary.detailed-report');
    }

    /**
     * Get detailed report data for preview
     */
    public function getDetailedReportData(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $month = $request->month;
        $year = $request->year;
        $requisitionType = $request->requisition_type ?? 'All';

        // Build query
        $query = CandidateMaster::where('final_status', 'A')
            ->with([
                'function',
                'vertical',
                'department',
                'subDepartmentRef',
                'businessUnit',
                'zoneRef',
                'regionRef',
                'territoryRef',
                'salaryProcessings' => function ($q) use ($month, $year) {
                    $q->where('month', $month)
                        ->where('year', $year);
                }
            ]);

        if ($requisitionType && $requisitionType !== 'All') {
            $query->where('requisition_type', $requisitionType);
        }

        $candidates = $query->orderBy('candidate_code')->get();

        // Debug: Check what data is loaded
        foreach ($candidates as $candidate) {
            \Log::info('Candidate Data:', [
                'id' => $candidate->id,
                'code' => $candidate->candidate_code,
                'function_id' => $candidate->function_id,
                'function_relation' => $candidate->function,
                'vertical_id' => $candidate->vertical_id,
                'vertical_relation' => $candidate->vertical,
                'department_id' => $candidate->department_id,
                'department_relation' => $candidate->department,
            ]);
        }

        $result = [];

        foreach ($candidates as $candidate) {
            // Get salary for the specific month
            $salary = $candidate->salaryProcessings->first();

            $buName        = $candidate->businessUnit?->business_unit_name ?? '';
            $zoneName      = $candidate->zoneRef?->zone_name ?? '';
            $regionName    = $candidate->regionRef?->region_name ?? '';
            $territoryName = $candidate->territoryRef?->territory_name ?? '';


            \Log::info('BU-Zone-Region-Territory', [
                'bu' => $candidate->businessUnit,
                'zone' => $candidate->zone,
                'region' => $candidate->region,
                'territory' => $candidate->territory,
            ]);

            //dd($buName);
            // Debug the relationships
            $functionName = $candidate->function?->function_name ?? '';
            $verticalName = $candidate->vertical?->vertical_name ?? '';
            $departmentName = $candidate->department?->department_name ?? '';
            $sub_departmentName = $candidate->subDepartmentRef?->sub_department_name ?? '';

            // Get paid days and remuneration from salary processing
            $paidDays = $salary ? $salary->paid_days : 0;
            $remuneration = $salary ? $salary->monthly_salary : ($candidate->remuneration_per_month ?? 0);
            $overtime = $salary ? $salary->extra_amount : 0;
            $arrear = $salary ? ($salary->arrear_amount ?? 0) : 0;
            $totalPayable = $salary ? ($salary->net_pay + $arrear) : $remuneration;

            $result[] = [
                'id' => $candidate->id,
                'code' => $candidate->candidate_code,
                'name' => $candidate->candidate_name,
                'function' => $functionName,
                'vertical' => $verticalName,
                'department' => $departmentName,
                'sub_department' => $sub_departmentName,
                'section' => '', // You may need to add this field to candidate_master
                'state' => $candidate->state_work_location,
                'bu' => $buName,
                'zone' => $zoneName,
                'region' => $regionName,
                'territory' => $territoryName,
                'job_location' => $candidate->work_location_hq,
                'date_of_joining' => $candidate->contract_start_date,
                'date_of_separation' => $candidate->contract_end_date,
                'state_address' => $candidate->state_residence,
                'hq' => $candidate->work_location_hq,
                'paid_days' => $paidDays,
                'remuneration' => (float) $remuneration,
                'overtime' => (float) $overtime,
                'arrear' => (float) $arrear,
                'total_payable' => (float) $totalPayable,
                'requisition_type' => $candidate->requisition_type,
                'processed' => !is_null($salary)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'count' => count($result),
            'month' => $month,
            'year' => $year,
            'requisition_type' => $requisitionType
        ]);
    }
    /**
     * Export detailed report to Excel
     */
    public function exportDetailedReport(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $month = $request->month;
        $year = $request->year;
        $requisitionType = $request->requisition_type ?? 'All';

        $filename = "Detailed_Salary_Report_{$month}_{$year}";
        if ($requisitionType !== 'All') {
            $filename .= "_{$requisitionType}";
        }

        return Excel::download(
            new DetailedSalaryReportExport($month, $year, $requisitionType),
            "{$filename}.xlsx"
        );
    }

    /**
     * Display management report view
     */
    public function managementReportView()
    {
        // Get filter options
        $departments = \App\Models\CoreDepartment::orderBy('department_name')->get();
        $businessUnits = \App\Models\CoreBusinessUnit::orderBy('business_unit_name')->get();
        $zones = \App\Models\CoreZone::orderBy('zone_name')->get();
        $regions = \App\Models\CoreRegion::orderBy('region_name')->get();
        $territories = \App\Models\CoreTerritory::orderBy('territory_name')->get();

        return view('hr.salary.management-report', compact(
            'departments',
            'businessUnits',
            'zones',
            'regions',
            'territories'
        ));
    }

    /**
     * Get management report data
     */
    public function getManagementReportData(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'department' => 'sometimes|string',
            'bu' => 'sometimes|string',
            'zone' => 'sometimes|string',
            'region' => 'sometimes|string',
            'territory' => 'sometimes|string',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $year = (int) $request->year;
        $filters = $request->only(['department', 'bu', 'zone', 'region', 'territory', 'requisition_type']);

        // Build query for active candidates
        $query = CandidateMaster::where('final_status', 'A')
            ->with([
                'department',
                'businessUnit',
                'zoneRef',
                'regionRef',
                'territoryRef',
                'salaryProcessings' => function ($q) use ($year) {
                    $q->where('year', $year)
                        ->select(
                            'candidate_id',
                            'month',
                            'year',
                            'monthly_salary',
                            'per_day_salary',
                            'total_days',
                            'paid_days',
                            'absent_days',
                            'approved_sundays',
                            'deduction_amount',
                            'extra_amount',
                            'net_pay',
                            'status',
                            'processed_by',
                            'processed_at'
                        );
                    // Note: There's no 'arrear_amount' column in your table
                }
            ]);

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value && $value !== 'All') {
                switch ($key) {
                    case 'department':
                        $query->where('department_id', $value);
                        break;
                    case 'bu':
                        $query->where('business_unit', $value);
                        break;
                    case 'zone':
                        $query->where('zone', $value);
                        break;
                    case 'region':
                        $query->where('region', $value);
                        break;
                    case 'territory':
                        $query->where('territory', $value);
                        break;
                    case 'requisition_type':
                        $query->where('requisition_type', $value);
                        break;
                }
            }
        }

        $candidates = $query->orderBy('candidate_code')->get();

        $reportData = [];
        $monthlyTotals = [
            'january' => 0,
            'february' => 0,
            'march' => 0,
            'april' => 0,
            'may' => 0,
            'june' => 0,
            'july' => 0,
            'august' => 0,
            'september' => 0,
            'october' => 0,
            'november' => 0,
            'december' => 0,
            'grand_total' => 0
        ];

        foreach ($candidates as $candidate) {
            $employeeData = [
                'id' => $candidate->id,
                'code' => $candidate->candidate_code,
                'name' => $candidate->candidate_name,
                'january' => 0,
                'february' => 0,
                'march' => 0,
                'april' => 0,
                'may' => 0,
                'june' => 0,
                'july' => 0,
                'august' => 0,
                'september' => 0,
                'october' => 0,
                'november' => 0,
                'december' => 0,
                'grand_total' => 0
            ];

            // Process salary for each month
            foreach ($candidate->salaryProcessings as $salary) {
                $monthName = strtolower(date('F', mktime(0, 0, 0, $salary->month, 1)));

                // Use net_pay directly from salary processing
                // Since there's no arrear_amount column, we use net_pay as the total
                $totalAmount = $salary->net_pay;

                $employeeData[$monthName] = $totalAmount;
                $employeeData['grand_total'] += $totalAmount;

                // Add to monthly totals
                $monthlyTotals[$monthName] += $totalAmount;
                $monthlyTotals['grand_total'] += $totalAmount;
            }

            $reportData[] = $employeeData;
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'monthly_totals' => $monthlyTotals,
            'count' => count($reportData),
            'year' => $year,
            'filters' => $filters
        ]);
    }

    /**
     * Export management report to Excel
     */
    public function exportManagementReport(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020',
            'department' => 'sometimes|string',
            'bu' => 'sometimes|string',
            'zone' => 'sometimes|string',
            'region' => 'sometimes|string',
            'territory' => 'sometimes|string',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $year = $request->year;
        $filters = $request->only(['department', 'bu', 'zone', 'region', 'territory', 'requisition_type']);

        $filename = "Management_Salary_Report_{$year}";

        return Excel::download(
            new ManagementSalaryReportExport($year, $filters),
            "{$filename}.xlsx"
        );
    }
}
