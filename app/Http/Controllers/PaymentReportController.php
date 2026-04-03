<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Models\Employee;
use App\Services\HierarchyAccessService;
use Carbon\Carbon;
use App\Exports\PaymentReportExport;
use Maatwebsite\Excel\Facades\Excel;

class PaymentReportController extends Controller
{
    /**
     * Payment Report - Show salary payments with hierarchy access control
     */
    public function paymentReport(Request $request, HierarchyAccessService $hierarchyService)
    {
        // Get filter parameters
        $financialYear = $request->get('financial_year');
        $month = $request->get('month');
        $year = $request->get('year');
        $departmentId = $request->get('department_id');
        $subDepartmentId = $request->get('sub_department', 'All');
        $status = $request->get('status');
        $paymentMode = $request->get('payment_mode');

        // Hierarchy filters
        $buId = $request->get('bu', 'All');
        $zoneId = $request->get('zone', 'All');
        $regionId = $request->get('region', 'All');
        $territoryId = $request->get('territory', 'All');
        $verticalId = $request->get('vertical', 'All');
        $employeeId = $request->get('employee', 'All');

        // Set default financial year if not provided
        if (!$financialYear) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            $financialYear = ($currentMonth >= 4)
                ? $currentYear . '-' . ($currentYear + 1)
                : ($currentYear - 1) . '-' . $currentYear;
        }

        // Get logged in user
        $user = Auth::user();
        $employee = Employee::where('employee_id', $user->emp_id)->first();

        // Flags to track auto-applied filters
        $autoAppliedDepartment = false;
        $autoAppliedSubDepartment = false;

        // For non-admin, non-Sales users, auto-apply their own department and sub-department
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management']) && !$hierarchyService->isSalesDepartment($user->emp_id)) {
            if (!$request->filled('department_id') && $employee && $employee->department) {
                $departmentId = $employee->department;
                $autoAppliedDepartment = true;
            }
            if (!$request->filled('sub_department')) {
                $subDepartmentId = 'All';
            }
        }

        // Build base query for salary_processings
        $query = SalaryProcessing::query()
            ->leftJoin('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
            ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')
            ->leftJoin('core_sub_department as sub_dept', 'sub_dept.id', '=', 'candidate_master.sub_department')
            ->leftJoin('core_vertical as vert', 'vert.id', '=', 'candidate_master.vertical_id')
            ->leftJoin('core_employee as emp', function ($join) {
                $join->on('emp.employee_id', '=', 'candidate_master.reporting_manager_employee_id')
                    ->whereRaw('emp.id = (
                        SELECT MAX(id)
                        FROM core_employee
                        WHERE employee_id = candidate_master.reporting_manager_employee_id
                    )');
            })
            ->select(
                'salary_processings.*',
                'candidate_master.candidate_name',
                'candidate_master.candidate_code',
                'candidate_master.requisition_id',
                'candidate_master.requisition_type',
                'candidate_master.department_id',
                'candidate_master.sub_department',
                'candidate_master.vertical_id',
                'candidate_master.business_unit',
                'candidate_master.zone',
                'candidate_master.region',
                'candidate_master.territory',
                'candidate_master.reporting_manager_employee_id',
                'dept.department_name',
                'sub_dept.sub_department_name',
                'vert.vertical_name',
                'emp.emp_name as reporting_manager_name'
            );

        // Apply hierarchy access control
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $allowedEmpIds[] = $user->emp_id;
            $allowedEmpIds = array_unique($allowedEmpIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIds);
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

        // Apply employee filter (reporting manager)
        if ($employeeId && $employeeId != 'All') {
            $query->where('candidate_master.reporting_manager_employee_id', $employeeId);
        }

        // Apply department filter
        if ($departmentId && $departmentId != 'All') {
            $query->where('candidate_master.department_id', $departmentId);
        }

        // Apply sub-department filter
        if ($subDepartmentId && $subDepartmentId != 'All') {
            $query->where('candidate_master.sub_department', $subDepartmentId);
        }

        // ========== FIXED: Month/Year filter logic ==========
        if ($month && $year) {
            // Specific month and year selected
            $query->where('salary_processings.month', $month)
                  ->where('salary_processings.year', $year);
        } elseif ($year) {
            // Only year selected (Month = All) - Show ALL months of that year
            $query->where('salary_processings.year', $year);
        } elseif ($month) {
            // Only month selected (rare case) - Show that month across all years
            $query->where('salary_processings.month', $month);
        } elseif ($financialYear) {
            // Financial year selected - Show all months in that FY
            [$startYear, $endYear] = explode('-', $financialYear);
            $query->where(function ($q) use ($startYear, $endYear) {
                $q->where(function ($q2) use ($startYear) {
                    $q2->where('salary_processings.year', $startYear)
                       ->where('salary_processings.month', '>=', 4);
                })->orWhere(function ($q2) use ($endYear) {
                    $q2->where('salary_processings.year', $endYear)
                       ->where('salary_processings.month', '<=', 3);
                });
            });
        }
        // If NO year, NO month, NO financial year - show all data

        // Apply payment status filter
        if ($status && $status != 'All') {
            $query->where('salary_processings.payment_status', $status);
        }

        // Apply payment mode filter
        if ($paymentMode && $paymentMode != 'All') {
            $query->where('salary_processings.payment_mode', $paymentMode);
        }

        // Only show active candidates
        $query->whereIn('candidate_master.final_status', ['A', 'D']);

        // Debug: Uncomment to see the actual SQL query
        // \Log::info('Payment Report SQL: ' . $query->toSql());
        // \Log::info('Payment Report Bindings: ', $query->getBindings());

        // Get summary statistics
        $summary = $this->getPaymentSummary(clone $query);

        // Get paginated records
        $records = $query->orderBy('salary_processings.year', 'desc')
            ->orderBy('salary_processings.month', 'desc')
            ->orderBy('salary_processings.created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get dropdown data
        $departments = $this->getDepartmentsForPayment($user, $employeeId, $hierarchyService);
        $subDepartments = $this->getSubDepartmentsForPayment($user, $employeeId, $departmentId, $hierarchyService);
        $verticals = $this->getVerticals();
        $employees = $this->getEmployeesForPayment($user, $hierarchyService);

        // Add "All" option to sub-departments
        $subDepartments = ['All' => 'All Sub Departments'] + $subDepartments;

        // Get hierarchy data for location filters
        $showLocationFilters = $hierarchyService->shouldShowLocationFilters($user->emp_id);
        $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($user->emp_id);
        $zones = $hierarchyService->getAssociatedZoneList($user->emp_id);
        $regions = $hierarchyService->getAssociatedRegionList($user->emp_id);
        $territories = $hierarchyService->getAssociatedTerritoryList($user->emp_id);

        // Get available years for filter
        $availableYears = SalaryProcessing::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return view('reports.payment', compact(
            'records',
            'summary',
            'financialYear',
            'month',
            'year',
            'departmentId',
            'subDepartmentId',
            'status',
            'paymentMode',
            'businessUnits',
            'zones',
            'regions',
            'territories',
            'verticals',
            'employees',
            'departments',
            'subDepartments',
            'buId',
            'zoneId',
            'regionId',
            'territoryId',
            'verticalId',
            'employeeId',
            'showLocationFilters',
            'autoAppliedDepartment',
            'autoAppliedSubDepartment',
            'availableYears'
        ));
    }

    /**
     * Get payment summary statistics
     */
    private function getPaymentSummary($query)
    {
        $results = $query->get();

        $totalPayable = $results->sum('total_payable');
        $totalNetPay = $results->sum('net_pay');
        $totalDeductions = $results->sum('deduction_amount') + $results->sum('extra_amount');
        $totalArrear = $results->sum('arrear_amount');

        $pendingCount = $results->where('payment_status', 'pending')->count();
        $processedCount = $results->where('payment_status', 'processed')->count();
        $paidCount = $results->where('payment_status', 'paid')->count();
        $heldCount = $results->where('payment_status', 'held')->count();

        $processingPending = $results->where('status', 'pending')->count();
        $processingProcessed = $results->where('status', 'processed')->count();
        $processingRelease = $results->where('status', 'release')->count();
        $processingHold = $results->where('status', 'hold')->count();

        return [
            'total_candidates' => $results->count(),
            'total_payable' => $totalPayable,
            'total_net_pay' => $totalNetPay,
            'total_deductions' => $totalDeductions,
            'total_arrear' => $totalArrear,
            'avg_net_pay' => $results->count() > 0 ? round($totalNetPay / $results->count(), 2) : 0,
            'pending_count' => $pendingCount,
            'processed_count' => $processedCount,
            'paid_count' => $paidCount,
            'held_count' => $heldCount,
            'processing_pending' => $processingPending,
            'processing_processed' => $processingProcessed,
            'processing_release' => $processingRelease,
            'processing_hold' => $processingHold,
        ];
    }

    /**
     * Get departments for payment report dropdown
     */
    private function getDepartmentsForPayment($user, $employeeId, $hierarchyService)
    {
        if ($employeeId && $employeeId != 'All') {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $departmentsList = CandidateMaster::whereIn('final_status', ['A', 'D'])
                ->whereIn('reporting_manager_employee_id', $teamMemberIds)
                ->whereNotNull('department_id')
                ->distinct()
                ->pluck('department_id')
                ->toArray();

            return DB::table('core_department')
                ->whereIn('id', $departmentsList)
                ->where('is_active', 1)
                ->orderBy('department_name')
                ->pluck('department_name', 'id')
                ->toArray();
        }

        return $hierarchyService->getAssociatedDepartmentList($user->emp_id);
    }

    /**
     * Get sub-departments for payment report dropdown
     */
    private function getSubDepartmentsForPayment($user, $employeeId, $departmentId, $hierarchyService)
    {
        $query = DB::table('core_sub_department')->where('is_active', 1);

        if ($employeeId && $employeeId != 'All') {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $subDepartmentsList = CandidateMaster::whereIn('reporting_manager_employee_id', $teamMemberIds)
                ->whereNotNull('sub_department')
                ->distinct()
                ->pluck('sub_department')
                ->toArray();

            if (!empty($subDepartmentsList)) {
                $query->whereIn('id', $subDepartmentsList);
            }
        }

        return $query->orderBy('sub_department_name')
            ->pluck('sub_department_name', 'id')
            ->toArray();
    }

    /**
     * Get verticals for dropdown
     */
    private function getVerticals()
    {
        return DB::table('core_vertical')
            ->where('is_active', 1)
            ->orderBy('vertical_name')
            ->pluck('vertical_name', 'id')
            ->toArray();
    }

    /**
     * Get employees for payment report dropdown
     */
    private function getEmployeesForPayment($user, $hierarchyService)
    {
        if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $employees = DB::table('core_employee')
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->toArray();
        } else {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $employees = DB::table('core_employee')
                ->whereIn('employee_id', $allowedEmpIds)
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->toArray();
        }

        return ['All' => 'All Employees'] + $employees;
    }

    /**
     * Get filters data when employee is selected (AJAX)
     */
    public function getPaymentFiltersByEmployee($employeeId, HierarchyAccessService $hierarchyService)
    {
        try {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $manager = Employee::where('employee_id', $employeeId)->first();

            $departmentsList = CandidateMaster::whereIn('final_status', ['A', 'D'])
                ->whereIn('reporting_manager_employee_id', $teamMemberIds)
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
                ->whereIn('reporting_manager_employee_id', $teamMemberIds)
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

            $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($employeeId);
            $zones = $hierarchyService->getAssociatedZoneList($employeeId);
            $regions = $hierarchyService->getAssociatedRegionList($employeeId);
            $territories = $hierarchyService->getAssociatedTerritoryList($employeeId);

            $verticals = DB::table('core_vertical')
                ->where('is_active', 1)
                ->orderBy('vertical_name')
                ->pluck('vertical_name', 'id')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments,
                    'sub_departments' => $subDepartments,
                    'business_units' => $businessUnits,
                    'zones' => $zones,
                    'regions' => $regions,
                    'territories' => $territories,
                    'verticals' => $verticals,
                    'manager_department' => $manager ? $manager->department : null,
                    'manager_sub_department' => $manager ? $manager->sub_department : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export payment report to Excel
     */
    public function paymentReportExport(Request $request, HierarchyAccessService $hierarchyService)
    {
        $filters = [
            'financial_year' => $request->get('financial_year'),
            'month' => $request->get('month'),
            'year' => $request->get('year'),
            'department_id' => $request->get('department_id'),
            'sub_department' => $request->get('sub_department', 'All'),
            'status' => $request->get('status'),
            'payment_mode' => $request->get('payment_mode'),
            'bu' => $request->get('bu', 'All'),
            'zone' => $request->get('zone', 'All'),
            'region' => $request->get('region', 'All'),
            'territory' => $request->get('territory', 'All'),
            'vertical' => $request->get('vertical', 'All'),
            'employee' => $request->get('employee', 'All'),
        ];

        $filename = $this->generateExportFileName($filters);
        return Excel::download(new PaymentReportExport($filters, $hierarchyService), $filename);
    }

    /**
     * Generate export filename based on applied filters
     */
    private function generateExportFileName($filters)
    {
        $parts = ['Payment_Report'];

        if (!empty($filters['month']) && !empty($filters['year'])) {
            $monthName = $this->getMonthName($filters['month']);
            $parts[] = $monthName . '_' . $filters['year'];
        } elseif (!empty($filters['financial_year'])) {
            $parts[] = 'FY_' . $filters['financial_year'];
        } elseif (!empty($filters['year'])) {
            $parts[] = 'Year_' . $filters['year'];
        }

        if (!empty($filters['employee']) && $filters['employee'] != 'All') {
            $manager = Employee::where('employee_id', $filters['employee'])->first();
            if ($manager) {
                $parts[] = 'Manager_' . preg_replace('/[^A-Za-z0-9]/', '_', $manager->emp_name);
            }
        }

        if (!empty($filters['status']) && $filters['status'] != 'All') {
            $parts[] = ucfirst($filters['status']);
        }

        $parts[] = date('Y-m-d');
        return implode('_', $parts) . '.xlsx';
    }

    private function getMonthName($month)
    {
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        return $months[$month] ?? $month;
    }
}