<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\CoreDepartment;
use App\Services\SalaryCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryExport;
use App\Exports\DetailedSalaryReportExport;
use App\Exports\ManagementSalaryReportExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\HierarchyAccessService;
use Illuminate\Support\Facades\Auth;




class SalaryController extends Controller
{
    public function index()
    {
        $departments = CoreDepartment::orderBy('department_name')->get();
        return view('hr.salary.index', compact('departments'));
    }

    // Update the process method in SalaryController
    public function process(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020|max:' . (date('Y') + 1),

            'candidate_id'   => 'sometimes|integer|exists:candidate_master,id',
            'candidate_ids'  => 'sometimes|array',
            'candidate_ids.*' => 'integer|exists:candidate_master,id',

            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        // Resolve arrears
        $arrearAmount  = 0;
        $arrearDays    = 0;
        $arrearRemarks = null;


        $month = (int) $validated['month'];
        $year  = (int) $validated['year'];

        $requisitionType  = $validated['requisition_type'] ?? null;

        DB::beginTransaction();

        try {
            $salaryMonthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $salaryMonthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
            // Build candidate query
            if (!empty($validated['candidate_ids'])) {
                // Process only selected employees BUT still enforce eligibility rules
                $query = CandidateMaster::whereIn('id', $validated['candidate_ids'])
                    ->whereNotIn('id', function ($q) use ($month, $year) {
                        $q->select('candidate_id')
                            ->from('salary_processings')
                            ->where('month', $month)
                            ->where('year', $year);
                    })
                    ->whereIn('final_status', ['A', 'D'])
                    ->whereDate('contract_start_date', '<=', $salaryMonthEnd)
                    ->where(function ($q) use ($salaryMonthStart) {
                        $q->whereNull('contract_end_date')
                            ->orWhereDate('contract_end_date', '>=', $salaryMonthStart);
                    });
            } else {

                // Process all filtered employees
                $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
                    ->whereNotIn('id', function ($q) use ($month, $year) {
                        $q->select('candidate_id')
                            ->from('salary_processings')
                            ->where('month', $month)
                            ->where('year', $year);
                    })
                    ->whereDate('contract_start_date', '<=', $salaryMonthEnd)
                    ->where(function ($q) use ($salaryMonthStart) {
                        $q->whereNull('contract_end_date')
                            ->orWhereDate('contract_end_date', '>=', $salaryMonthStart);
                    });

                if ($requisitionType && $requisitionType !== 'All') {
                    $query->where('requisition_type', $requisitionType);
                }

                if ($request->filled('candidate_id')) {
                    $query->where('id', $validated['candidate_id']);
                }
            }

            $candidates = $query->get();

            if ($candidates->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No party available to process'
                ]);
            }

            $processed = 0;
            $skipped   = 0;
            $errors    = [];
            $arrearsIncluded = 0;
            $skippedAttendance = 0;
            $skippedExisting   = 0;

            $arrears = DB::table('salary_arrears')
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->keyBy('candidate_id');

            $existingProcessed = SalaryProcessing::where('month', $month)
                ->where('year', $year)
                ->pluck('candidate_id')
                ->flip();

            $agreementSignedMap = DB::table('agreement_documents')
                ->where('document_type', 'agreement')
                ->where('sign_status', 'SIGNED')
                ->pluck('candidate_id')
                ->flip();
            $courierReceivedMap = DB::table('agreement_couriers as ac')
                ->join('agreement_documents as ad', 'ac.agreement_document_id', '=', 'ad.id')
                ->whereNotNull('ac.received_date')
                ->pluck('ad.candidate_id')
                ->flip();



            foreach ($candidates as $candidate) {

                // Re-check PAN if previously inoperative
                if ($candidate->pan_status_2 !== 'Operative' && !empty($candidate->pan_no)) {
                    $panData = \App\Services\PanVerificationService::verify($candidate->pan_no);
                    if ($panData) {

                        $panStatus = $panData['individual_tax_compliance_status'] ?? null;
                        $isValid = $panData['is_valid'] ?? false;
                        $aadhaarStatus = $panData['aadhaar_seeding_status'] ?? null;

                        if ($panStatus === 'Operative') {

                            $candidate->update([
                                'pan_verification_status' => $isValid ? 'Valid' : 'Invalid',
                                'pan_status_2' => 'Operative',
                                'pan_aadhaar_link_status' => $aadhaarStatus
                            ]);

                            $candidate->pan_status_2 = 'Operative';
                        }
                    }
                }

                if ($candidate->pan_status_2 !== 'Operative') {

                    $skipped++;

                    $errors[] = [
                        'candidate_code' => $candidate->candidate_code,
                        'candidate_name' => $candidate->candidate_name,
                        'error' => 'PAN is Inoperative'
                    ];

                    continue;
                }
                if (isset($existingProcessed[$candidate->id])) {
                    $skipped++;
                    $skippedExisting++;
                    continue;
                }

                try {

                    $salaryData = SalaryCalculator::calculate($candidate, $month, $year);

                    if ($salaryData['paid_days'] == 0) {
                        $skipped++;
                        $skippedAttendance++;
                        continue;
                    }

                    $ar = $arrears[$candidate->id] ?? null;

                    $arrearAmount  = $ar->arrear_amount ?? 0;
                    $arrearDays    = $ar->arrear_days ?? 0;
                    $arrearRemarks = $ar->arrear_remarks ?? null;

                    if ($arrearAmount > 0) {
                        $arrearsIncluded++;
                    }

                    $agreementSigned = isset($agreementSignedMap[$candidate->id]);
                    $courierReceived = isset($courierReceivedMap[$candidate->id]);
                    $fileCreated = !empty($candidate->file_created_date);
                    // \Log::info('Salary Processing Debug', [
                    //     'candidate_id' => $candidate->id,
                    //     'candidate_code' => $candidate->candidate_code,
                    //     'agreementSigned' => $agreementSigned,
                    //     'courierReceived' => $courierReceived,
                    //     'fileCreated' => $fileCreated
                    // ]);

                    $autoHold = false;
                    $holdReason = null;

                    if (!$agreementSigned) {
                        $autoHold = true;
                        $holdReason = 'Agreement not signed';
                    } elseif (!$courierReceived) {
                        $autoHold = true;
                        $holdReason = 'Courier not received';
                    } elseif (!$fileCreated) {
                        $autoHold = true;
                        $holdReason = 'File not created yet';
                    }

                    // \Log::info('Salary Hold Decision', [
                    //     'candidate_id' => $candidate->id,
                    //     'autoHold' => $autoHold,
                    //     'holdReason' => $holdReason
                    // ]);
                    $totalPayable = $salaryData['net_pay'] + $arrearAmount;
                    SalaryProcessing::create(array_merge($salaryData, [
                        'candidate_id' => $candidate->id,
                        'month'        => $month,
                        'year'         => $year,

                        'arrear_amount'  => $arrearAmount,
                        'arrear_days'    => $arrearDays,
                        'arrear_remarks' => $arrearRemarks,

                        'total_payable' => $totalPayable,

                        'status'         => 'processed',
                        'payment_instruction' => $autoHold ? 'hold' : 'pending',
                        'hr_hold_remark' => $autoHold ? $holdReason : null,
                        'held_at' => $autoHold ? now() : null,
                        'processed_by'   => auth()->id(),
                        'processed_at'   => now(),
                    ]));

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

            $message = "Salary processing completed. Processed: {$processed}, Skipped: {$skipped}";

            $details = [];

            if ($skippedAttendance > 0) {
                $details[] = "{$skippedAttendance} attendance missing";
            }

            if ($skippedExisting > 0) {
                $details[] = "{$skippedExisting} already processed";
            }

            if (!empty($details)) {
                $message .= " (" . implode(', ', $details) . ")";
            }


            if ($arrearsIncluded > 0) {
                $message .= ", Arrears included: {$arrearsIncluded}";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
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

    public function saveArrear(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|integer|exists:candidate_master,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'arrear_days' => 'required|numeric|min:0|max:31',
            'arrear_amount' => 'required|numeric|min:0',
            'arrear_remarks' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {

            // 1️⃣ Save in arrear table
            DB::table('salary_arrears')->updateOrInsert(
                [
                    'candidate_id' => $request->candidate_id,
                    'month' => $request->month,
                    'year' => $request->year
                ],
                [
                    'arrear_days' => $request->arrear_days,
                    'arrear_amount' => $request->arrear_amount,
                    'arrear_remarks' => $request->arrear_remarks,
                    'created_by' => auth()->id(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // 2️⃣ 🔥 If salary already processed → update total_payable
            $salary = SalaryProcessing::where('candidate_id', $request->candidate_id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->first();

            if ($salary) {
                $salary->arrear_days = $request->arrear_days;
                $salary->arrear_amount = $request->arrear_amount;
                $salary->arrear_remarks = $request->arrear_remarks;

                // ✅ update total payable
                $salary->total_payable = $salary->net_pay + $request->arrear_amount;

                $salary->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Arrear saved successfully'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error("Arrear save error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save arrear'
            ], 500);
        }
    }

    // Update the updateArrear method (only for processed salaries)
    public function updateArrear(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'salary_id' => 'required|integer|exists:salary_processings,id',
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
            if ($salary->status !== 'processed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary is not processed yet. Please process salary first or use local calculation.'
                ], 400);
            }

            // Update arrear fields
            $salary->arrear_days = $request->arrear_days;
            $salary->arrear_amount = $request->arrear_amount;
            $salary->arrear_remarks = $request->arrear_remarks;
            $salary->total_payable = $salary->net_pay + $request->arrear_amount;
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
            'department_id' => 'nullable|integer',
        ]);

        $month = $request->month;
        $year  = $request->year;
        $requisitionType = $request->requisition_type;
        $type = $request->type ?? 'pending';

        // Build query
        //$query = CandidateMaster::where('final_status', 'A');
        $salaryMonthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $salaryMonthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereDate('contract_start_date', '<=', $salaryMonthEnd)
            ->where(function ($q) use ($salaryMonthStart) {
                $q->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', $salaryMonthStart);
            });

        if ($type === 'processed') {

            $records = SalaryProcessing::with(['candidate' => function ($q) use ($request, $requisitionType) {

                if ($request->filled('department_id')) {
                    $q->where('department_id', $request->department_id);
                }

                if ($requisitionType && $requisitionType !== 'All') {
                    $q->where('requisition_type', $requisitionType);
                }
            }])
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', 'processed') // ✅ ADD
                ->where('payment_instruction', '!=', 'release') // ✅ ADD
                ->get()
                ->filter(fn($r) => $r->candidate != null)
                ->values()
                ->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'candidate_id' => $r->candidate_id,
                        'candidate' => $r->candidate,

                        'paid_days' => $r->paid_days,
                        'monthly_salary' => $r->monthly_salary,
                        'per_day_salary' => $r->per_day_salary,

                        'extra_amount' => $r->extra_amount,
                        'deduction_amount' => $r->deduction_amount,

                        'net_pay' => $r->net_pay,
                        'total_payable' => $r->total_payable, // ✅ ADD THIS

                        'arrear_amount' => $r->arrear_amount,
                        'arrear_days' => $r->arrear_days,

                        'processed' => true,

                        'pan_status_2' => $r->candidate->pan_status_2 ?? null,
                        'can_process' => true,
                    ];
                });
            return response()->json($records);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Apply requisition_type filter if provided and not 'All'
        if ($requisitionType && $requisitionType !== 'All') {
            $query->where('requisition_type', $requisitionType);
        }

        $candidates = $query->whereNotIn('id', function ($q) use ($month, $year) {
            $q->select('candidate_id')
                ->from('salary_processings')
                ->where('month', $month)
                ->where('year', $year);
        })->get();
        //dd($salaryMonthEnd);
        $result = [];

        $arrears = DB::table('salary_arrears')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('candidate_id');

        foreach ($candidates as $candidate) {

            $ar = $arrears[$candidate->id] ?? null;
            $canProcess = $candidate->pan_status_2 === 'Operative';

            try {

                $calc = SalaryCalculator::calculate($candidate, $month, $year);

                $row = array_merge($calc, [
                    'candidate_id' => $candidate->id,
                    'candidate'    => $candidate,
                    'processed'    => false,
                    'pan_status_2' => $candidate->pan_status_2,
                    'can_process'  => $canProcess,
                    'arrear_amount' => $ar->arrear_amount ?? 0,
                    'arrear_days'  => $ar->arrear_days ?? 0,
                ]);
            } catch (\Exception $e) {

                $row = [
                    'candidate_id' => $candidate->id,
                    'candidate'    => $candidate,
                    'error'        => $e->getMessage(),
                    'processed'    => false,
                    'pan_status_2' => $candidate->pan_status_2,
                    'can_process'  => $canProcess,
                    'arrear_amount' => $ar->arrear_amount ?? 0,
                    'arrear_days'  => $ar->arrear_days ?? 0,
                ];
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
        ]);

        $count = SalaryProcessing::where('month', $request->month)
            ->where('year', $request->year)
            ->count();

        return response()->json([
            'exists' => $count > 0,
            'count'  => $count
        ]);
    }

    public function downloadPayslip($id)
    {
        $salary = SalaryProcessing::with('candidate')->findOrFail($id);

        if ($salary->payment_instruction !== 'release') {
            abort(403, 'Payslip available after payout release.');
        }

        $pdf = Pdf::loadView('hr.salary.payslip', compact('salary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(
            "Invoice_{$salary->candidate->candidate_code}_{$salary->month}_{$salary->year}.pdf"
        );
    }


    public function exportExcel(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
            'department_id' => 'nullable|integer'
        ]);

        $month = $request->month;
        $year = $request->year;
        $requisitionType = $request->requisition_type ?? 'All';
        $departmentId = $request->department_id;

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

        $monthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();


        // Build query
        $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereDate('contract_start_date', '<=', $monthEnd)
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('contract_end_date')
                    ->orWhereDate('contract_end_date', '>=', $monthStart);
            })
            ->whereHas('salaryProcessings', function ($q) use ($month, $year) {
                $q->where('month', $month)
                    ->where('year', $year)
                    ->where('status', 'Processed');
            })
            ->with([
                'function',
                'vertical',
                'department',
                'subDepartmentRef',
                'businessUnit',
                'zoneRef',
                'regionRef',
                'territoryRef',
                'cityMaster',
                'residenceState',
                'workState',
                'qualification',
                'reportingManager',
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
        // foreach ($candidates as $candidate) {
        //     \Log::info('Candidate Data:', [
        //         'id' => $candidate->id,
        //         'code' => $candidate->candidate_code,
        //         'function_id' => $candidate->function_id,
        //         'function_relation' => $candidate->function,
        //         'vertical_id' => $candidate->vertical_id,
        //         'vertical_relation' => $candidate->vertical,
        //         'department_id' => $candidate->department_id,
        //         'department_relation' => $candidate->department,
        //     ]);
        // }

        $result = [];

        foreach ($candidates as $candidate) {
            // Get salary for the specific month
            $salary = $candidate->salaryProcessings->first();

            $buName        = $candidate->businessUnit?->business_unit_name ?? '';
            $zoneName      = $candidate->zoneRef?->zone_name ?? '';
            $regionName    = $candidate->regionRef?->region_name ?? '';
            $territoryName = $candidate->territoryRef?->territory_name ?? '';


            // \Log::info('BU-Zone-Region-Territory', [
            //     'bu' => $candidate->businessUnit,
            //     'zone' => $candidate->zone,
            //     'region' => $candidate->region,
            //     'territory' => $candidate->territory,
            // ]);

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
            if ($salary) {
                $totalPayable = $salary->net_pay + $arrear;
            } else {
                $perDay = $candidate->remuneration_per_month / 30;
                $totalPayable = $perDay * $paidDays;
            }


            $result[] = [
                'id' => $candidate->id,
                'code' => $candidate->candidate_code,
                'name' => $candidate->candidate_name,
                'reporting_manager' => $candidate->reportingManager?->emp_name ?? $candidate->reportingManager?->employee_name ?? '',
                'function' => $functionName,
                'vertical' => $verticalName,
                'department' => $departmentName,
                'sub_department' => $sub_departmentName,
                'work_state' => $candidate->workState?->state_name ?? '',
                'residence_state' => $candidate->residenceState?->state_name ?? '',
                'bu' => $buName,
                'zone' => $zoneName,
                'region' => $regionName,
                'territory' => $territoryName,
                'job_location' => $candidate->work_location_hq,
                'date_of_joining' => $candidate->contract_start_date,
                'date_of_separation' => $candidate->contract_end_date,
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
    public function managementReportView(HierarchyAccessService $hierarchyService)
    {
        $user = Auth::user();

        // Get logged in employee record (optional safety check)
        $employee = \App\Models\Employee::where('employee_id', $user->emp_id)->first();

        $access_level = $hierarchyService->getAccessLevel($employee);

        // Hierarchy based location filters
        $bu_list        = $hierarchyService->getAssociatedBusinessUnitList($user->emp_id);
        $zone_list      = $hierarchyService->getAssociatedZoneList($user->emp_id);
        $region_list    = $hierarchyService->getAssociatedRegionList($user->emp_id);
        $territory_list = $hierarchyService->getAssociatedTerritoryList($user->emp_id);

        // Department restriction
        $departments = $hierarchyService->getAssociatedDepartmentList($user->emp_id);

        // 🔥 NEW: Recursive reporting employee list
        if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {

            $employee_list = DB::table('core_employee')
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->prepend('All Employees', 'All')
                ->toArray();
        } else {

            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);

            $employee_list = DB::table('core_employee')
                ->whereIn('employee_id', $allowedEmpIds)
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->toArray();
        }

        //dd($employee);

        return view('hr.salary.management-report', compact(
            'departments',
            'bu_list',
            'zone_list',
            'region_list',
            'territory_list',
            'employee_list',   // 🔥 Added
            'access_level'
        ));
    }



    /**
     * Get management report data
     */
    public function getManagementReportData(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|string',
            'department' => 'nullable|integer',
            'bu' => 'nullable|integer',
            'zone' => 'nullable|integer',
            'region' => 'nullable|integer',
            'territory' => 'nullable|integer',
            'employee' => 'nullable|integer',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $financialYear = $request->financial_year;
        [$startYear, $endYear] = explode('-', $financialYear);

        $filters = $request->only([
            'department',
            'bu',
            'zone',
            'region',
            'territory',
            'employee',
            'requisition_type'
        ]);

        $user = Auth::user();
        $hierarchyService = app(\App\Services\HierarchyAccessService::class);
        $query = CandidateMaster::whereIn('final_status', ['A', 'D']);

        // ✅ Apply hierarchy ONLY for non-admin
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {

            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);

            $query->whereIn('reporting_manager_employee_id', $allowedEmpIds);
        }

        // ✅ Attach relations properly
        $query->with([
            'salaryProcessings' => function ($q) use ($startYear, $endYear) {
                $q->where(function ($query) use ($startYear, $endYear) {
                    $query->where(function ($q1) use ($startYear) {
                        $q1->where('year', $startYear)
                            ->whereBetween('month', [4, 12]);
                    })
                        ->orWhere(function ($q2) use ($endYear) {
                            $q2->where('year', $endYear)
                                ->whereBetween('month', [1, 3]);
                        });
                })
                    ->select('candidate_id', 'month', 'year', 'net_pay');
            }
        ]);
        // Apply Filters
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
                    case 'employee':
                        $query->where('reporting_manager_employee_id', $value);
                        break;

                    case 'requisition_type':
                        $query->where('requisition_type', $value);
                        break;
                }
            }
        }

        $candidates = $query
            ->leftJoin('salary_processings as sp', 'candidate_master.id', '=', 'sp.candidate_id')
            ->select(
                'candidate_master.id',
                'candidate_master.candidate_code',
                'candidate_master.candidate_name',
                'candidate_master.contract_start_date',
                'candidate_master.contract_end_date',
                'candidate_master.last_working_date',

                DB::raw("SUM(CASE WHEN sp.month = 4 THEN sp.net_pay ELSE 0 END) as april"),
                DB::raw("SUM(CASE WHEN sp.month = 5 THEN sp.net_pay ELSE 0 END) as may"),
                DB::raw("SUM(CASE WHEN sp.month = 6 THEN sp.net_pay ELSE 0 END) as june"),
                DB::raw("SUM(CASE WHEN sp.month = 7 THEN sp.net_pay ELSE 0 END) as july"),
                DB::raw("SUM(CASE WHEN sp.month = 8 THEN sp.net_pay ELSE 0 END) as august"),
                DB::raw("SUM(CASE WHEN sp.month = 9 THEN sp.net_pay ELSE 0 END) as september"),
                DB::raw("SUM(CASE WHEN sp.month = 10 THEN sp.net_pay ELSE 0 END) as october"),
                DB::raw("SUM(CASE WHEN sp.month = 11 THEN sp.net_pay ELSE 0 END) as november"),
                DB::raw("SUM(CASE WHEN sp.month = 12 THEN sp.net_pay ELSE 0 END) as december"),
                DB::raw("SUM(CASE WHEN sp.month = 1 THEN sp.net_pay ELSE 0 END) as january"),
                DB::raw("SUM(CASE WHEN sp.month = 2 THEN sp.net_pay ELSE 0 END) as february"),
                DB::raw("SUM(CASE WHEN sp.month = 3 THEN sp.net_pay ELSE 0 END) as march"),
                DB::raw("SUM(sp.net_pay) as grand_total")
            )
            ->groupBy(
                'candidate_master.id',
                'candidate_master.candidate_code',
                'candidate_master.candidate_name',
                'candidate_master.contract_start_date',
                'candidate_master.contract_end_date',
                'candidate_master.last_working_date'
            )
            ->orderBy('candidate_master.candidate_code')
            ->get();

        $months = [
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december',
            'january',
            'february',
            'march'
        ];


        $reportData = [];
        $monthlyTotals = array_fill_keys($months, 0);
        $monthlyTotals['grand_total'] = 0;

        foreach ($candidates as $candidate) {

            $employeeData = [
                'id' => $candidate->id,
                'code' => $candidate->candidate_code,
                'name' => $candidate->candidate_name,

                // NEW FIELDS
                'contract_start_date' => $candidate->contract_start_date
                    ? Carbon::parse($candidate->contract_start_date)->format('d-m-Y') : null,

                'contract_end_date' => $candidate->contract_end_date
                    ? Carbon::parse($candidate->contract_end_date)->format('d-m-Y') : null,

                'termination_date' => $candidate->last_working_date
                    ? Carbon::parse($candidate->last_working_date)->format('d-m-Y') : null,

                'grand_total' => 0
            ];

            foreach ($months as $month) {
                $employeeData[$month] = 0;
            }

            foreach ($candidate->salaryProcessings as $salary) {

                $monthName = strtolower(date('F', mktime(0, 0, 0, $salary->month, 1)));

                $amount = $salary->net_pay ?? 0;

                $employeeData[$monthName] = $amount;
                $employeeData['grand_total'] += $amount;

                $monthlyTotals[$monthName] += $amount;
                $monthlyTotals['grand_total'] += $amount;
            }

            $reportData[] = $employeeData;
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'monthly_totals' => $monthlyTotals,
            'count' => count($reportData),
            'financial_year' => $financialYear,
            'filters' => $filters
        ]);
    }


    /**
     * Export management report to Excel
     */
    public function exportManagementReport(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|string',
            'department' => 'nullable|integer',
            'bu' => 'nullable|integer',
            'zone' => 'nullable|integer',
            'region' => 'nullable|integer',
            'territory' => 'nullable|integer',
            'requisition_type' => 'sometimes|string|in:Contractual,TFA,CB,All',
        ]);

        $financialYear  = $request->financial_year;
        $filters = $request->only(['department', 'bu', 'zone', 'region', 'territory', 'requisition_type']);

        $filename = "Management_Salary_Report_{$financialYear}";

        return Excel::download(
            new ManagementSalaryReportExport($financialYear, $filters),
            "{$filename}.xlsx"
        );
    }

    public function togglePayment(Request $request)
    {
        $request->validate([
            'salary_id' => 'required|exists:salary_processings,id',
            'action'    => 'required|in:hold,release',
            'remark'    => 'required|string|max:500'
        ]);

        DB::beginTransaction();

        try {

            $salary = SalaryProcessing::findOrFail($request->salary_id);

            if ($salary->status !== 'processed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Salary must be processed first.'
                ], 400);
            }

            if ($request->action === 'hold') {

                $salary->payment_instruction = 'hold';
                $salary->hr_hold_remark = $request->remark;
                $salary->held_at = now();
            }

            if ($request->action === 'release') {

                $candidate = CandidateMaster::find($salary->candidate_id);

                $agreementSigned = DB::table('agreement_documents')
                    ->where('candidate_id', $candidate->id)
                    ->where('document_type', 'agreement')
                    ->where('sign_status', 'SIGNED')
                    ->exists();

                $courierReceived = DB::table('agreement_couriers as ac')
                    ->join('agreement_documents as ad', 'ac.agreement_document_id', '=', 'ad.id')
                    ->where('ad.candidate_id', $candidate->id)
                    ->whereNotNull('ac.received_date')
                    ->exists();

                $fileCreated = !empty($candidate->file_created_date);

                if ($agreementSigned && $courierReceived && $fileCreated) {

                    $salary->payment_instruction = 'pending';
                    $salary->hr_release_remark = $request->remark;
                } else {

                    $missing = [];

                    if (!$agreementSigned) {
                        $missing[] = 'Agreement not signed';
                    }

                    if (!$courierReceived) {
                        $missing[] = 'Courier not received';
                    }

                    if (!$fileCreated) {
                        $missing[] = 'File not created';
                    }

                    return response()->json([
                        'success' => false,
                        'message' => implode(', ', $missing)
                    ], 422);
                }
            }

            $salary->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully.'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function hrReview()
    {
        return view('hr.salary.hr-review');
    }

    public function hrReviewList(Request $request)
    {
        $query = SalaryProcessing::with('candidate')
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->where('status', 'processed');

        // Filter by tab
        if ($request->type == 'pending') {
            $query->where('payment_instruction', 'pending');
        }

        if ($request->type == 'hold') {
            $query->where('payment_instruction', 'hold');
        }

        if ($request->type == 'release') {
            $query->where('payment_instruction', 'release');
        }

        if ($request->type == 'payment') {
            $query->where('payment_instruction', 'release')
                ->where('payment_status', 'pending');
        }

         if ($request->filled('requisition_type') && $request->requisition_type !== 'All') {
            $query->whereHas('candidate', function ($q) use ($request) {
                $q->where('requisition_type', $request->requisition_type);
            });
        }

        $records = $query->get();

        foreach ($records as $r) {

            $candidate = $r->candidate;

            $agreementSigned = DB::table('agreement_documents')
                ->where('candidate_id', $candidate->id)
                ->where('sign_status', 'SIGNED')
                ->exists();

            $courierReceived = DB::table('agreement_couriers as ac')
                ->join('agreement_documents as ad', 'ac.agreement_document_id', '=', 'ad.id')
                ->where('ad.candidate_id', $candidate->id)
                ->whereNotNull('ac.received_date')
                ->exists();

            $fileCreated = !empty($candidate->file_created_date);

            $r->setAttribute('agreement_signed', $agreementSigned);
            $r->setAttribute('courier_received', $courierReceived);
            $r->setAttribute('file_created', $fileCreated);

            // ⭐ AUTO RELEASE HOLD
            if (
                $r->payment_instruction === 'hold'
                && $agreementSigned
                && $courierReceived
                && $fileCreated
            ) {

                // remove temporary attributes so Laravel doesn't try to save them
                unset($r->agreement_signed);
                unset($r->courier_received);
                unset($r->file_created);

                $r->payment_instruction = 'pending';
                $r->hr_release_remark = 'Auto released (documents completed)';
                $r->released_at = now();

                $r->save();
            }
        }

        return response()->json($records);
    }

    public function releaseBatch(Request $request)
    {

        $request->validate([
            'salary_ids' => 'required|array'
        ]);

        DB::beginTransaction();

        try {

            $salaries = SalaryProcessing::whereIn('id', $request->salary_ids)->get();

            $totalAmount = $salaries->sum('total_payable');

            $batch = DB::table('payout_batches')->insertGetId([

                'month' => $request->month,
                'year' => $request->year,
                'batch_no' => 'BATCH-' . date('dmY'),
                'payout_date' => now(),
                'payment_status' => 'pending',
                'total_records' => count($request->salary_ids),
                'total_amount' => $totalAmount,
                'created_by' => auth()->id(),
                'created_at' => now()

            ]);

            foreach ($salaries as $salary) {

                $salary->payment_instruction = 'release';
                $salary->batch_id = $batch;
                $salary->released_at = now();
                $salary->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch released successfully'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
