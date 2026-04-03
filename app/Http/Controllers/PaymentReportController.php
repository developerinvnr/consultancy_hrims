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
     * Following the same pattern as Management Report
     */
    public function paymentReport(Request $request, HierarchyAccessService $hierarchyService)
    {
        $user = Auth::user();
        $employee = Employee::where('employee_id', $user->emp_id)->first();
        $accessLevel = $hierarchyService->getAccessLevel($employee);

        // Get filter parameters
        $financialYear = $request->get('financial_year');
        $month = $request->get('month');
        $departmentId = $request->get('department_id');
        $subDepartmentId = $request->get('sub_department', 'All');
        $status = $request->get('status');
        $paymentMode = $request->get('payment_mode');

        // Hierarchy filters (same as Management Report)
        $buId = $request->get('bu', 'All');
        $zoneId = $request->get('zone', 'All');
        $regionId = $request->get('region', 'All');
        $territoryId = $request->get('territory', 'All');
        $verticalId = $request->get('vertical', 'All');
        $employeeId = $request->get('employee', 'All');

        // Set default financial year if not provided
        if (!$financialYear) {
            $latestYear = SalaryProcessing::max('year');
            if ($latestYear) {
                $financialYear = ($latestYear - 1) . '-' . $latestYear;
            } else {
                $currentMonth = date('n');
                $currentYear = date('Y');
                $financialYear = ($currentMonth >= 4)
                    ? $currentYear . '-' . ($currentYear + 1)
                    : ($currentYear - 1) . '-' . $currentYear;
            }
        }

        // Parse financial year
        [$startYear, $endYear] = explode('-', $financialYear);

        // ========== BUILD QUERY (Same pattern as Management Report) ==========
        $query = SalaryProcessing::query()
            ->leftJoin('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
            ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')
            ->leftJoin('core_sub_department as sub_dept', 'sub_dept.id', '=', 'candidate_master.sub_department')
            ->leftJoin('core_vertical as vert', 'vert.id', '=', 'candidate_master.vertical_id')
            ->leftJoin('core_employee as emp', function ($join) {
                $join->on('emp.employee_id', '=', 'candidate_master.reporting_manager_employee_id')
                    ->whereRaw('emp.id = (SELECT MAX(id) FROM core_employee WHERE employee_id = candidate_master.reporting_manager_employee_id)');
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
            )
            ->whereIn('candidate_master.final_status', ['A', 'D']);

        // ========== APPLY HIERARCHY ACCESS (Same as Management Report) ==========
        // Only apply hierarchy for non-admin users
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $allowedEmpIdsString = array_map('strval', $allowedEmpIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIdsString);
        }

        // ========== APPLY FILTERS (Same order as Management Report) ==========
        
        // 1. Employee filter (reporting manager)
        if ($employeeId && $employeeId !== 'All') {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $teamMemberIdsString = array_map('strval', $teamMemberIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $teamMemberIdsString);
        }

        // 2. Department filter (only if selected and not 'All')
        if ($departmentId && $departmentId !== 'All') {
            $query->where('candidate_master.department_id', $departmentId);
        }

        // 3. Sub-department filter
        if ($subDepartmentId && $subDepartmentId !== 'All') {
            $query->where('candidate_master.sub_department', $subDepartmentId);
        }

        // 4. Vertical filter
        if ($verticalId && $verticalId !== 'All') {
            $query->where('candidate_master.vertical_id', $verticalId);
        }

        // 5. BU filter
        if ($buId && $buId !== 'All') {
            $query->where('candidate_master.business_unit', $buId);
        }

        // 6. Zone filter (with region mapping)
        if ($zoneId && $zoneId !== 'All') {
            $regionsUnderZone = DB::table('core_region')
                ->where('zone', $zoneId)
                ->pluck('id')
                ->toArray();
            
            if (!empty($regionsUnderZone)) {
                $query->whereIn('candidate_master.region', $regionsUnderZone);
            } else {
                $query->where('candidate_master.zone', $zoneId);
            }
        }

        // 7. Region filter (with territory mapping)
        if ($regionId && $regionId !== 'All') {
            $territoriesUnderRegion = Employee::where('region', $regionId)
                ->where('emp_status', 'A')
                ->whereNotNull('territory')
                ->where('territory', '!=', 0)
                ->distinct()
                ->pluck('territory')
                ->toArray();
            
            if (!empty($territoriesUnderRegion)) {
                $query->whereIn('candidate_master.territory', $territoriesUnderRegion);
            } else {
                $query->where('candidate_master.region', $regionId);
            }
        }

        // 8. Territory filter
        if ($territoryId && $territoryId !== 'All') {
            $query->where('candidate_master.territory', $territoryId);
        }

        // 9. Requisition type filter
        $requisitionType = $request->get('requisition_type');
        if ($requisitionType && $requisitionType !== 'All') {
            $query->where('candidate_master.requisition_type', $requisitionType);
        }

        // ========== FINANCIAL YEAR & MONTH FILTER ==========
        if ($month && $month !== 'All') {
            $year = ($month >= 4) ? $startYear : $endYear;
            $query->where('salary_processings.month', $month)
                  ->where('salary_processings.year', $year);
        } else {
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

        // 10. Payment status filter
        if ($status && $status !== 'All') {
            $query->where('salary_processings.payment_status', $status);
        }

        // 11. Payment mode filter
        if ($paymentMode && $paymentMode !== 'All') {
            $query->where('salary_processings.payment_mode', $paymentMode);
        }

        // Debug logging
        // \Log::info('=== Payment Report Debug ===');
        // \Log::info('Financial Year: ' . $financialYear);
        // \Log::info('Department filter: ' . ($departmentId ?? 'null'));
        // \Log::info('Employee filter: ' . ($employeeId ?? 'null'));
        // \Log::info('Total records before pagination: ' . $query->count());

        // Get summary statistics
        $summary = $this->getPaymentSummary(clone $query);

        // Get paginated records
        $records = $query->orderBy('salary_processings.year', 'desc')
            ->orderBy('salary_processings.month', 'desc')
            ->orderBy('salary_processings.created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // ========== GET DROPDOWN DATA (Same as Management Report) ==========
        
        // Department list (same as Management Report)
        $departments = $hierarchyService->getAssociatedDepartmentList($user->emp_id);
        
        // Sub-department list (based on access level)
        $subDepartments = $hierarchyService->getAssociatedSubDepartmentList($user->emp_id);
        $subDepartments = ['All' => 'All Sub Departments'] + $subDepartments;
        
        // Vertical list (same as Management Report)
        $verticals = $hierarchyService->getAssociatedVerticalList($user->emp_id);
        
        // Employee list (reporting managers)
        if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $employees = DB::table('core_employee')
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->prepend('All Employees', 'All')
                ->toArray();
        } else {
            $allowedEmpIds = $hierarchyService->getReportingEmployeeIds($user->emp_id);
            $employees = DB::table('core_employee')
                ->whereIn('employee_id', $allowedEmpIds)
                ->where('emp_status', 'A')
                ->orderBy('emp_name')
                ->pluck('emp_name', 'employee_id')
                ->prepend('All Employees', 'All')
                ->toArray();
        }

        // Location filters (same as Management Report)
        $showLocationFilters = $hierarchyService->shouldShowLocationFilters($user->emp_id);
        $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($user->emp_id);
        $zones = $hierarchyService->getAssociatedZoneList($user->emp_id);
        $regions = $hierarchyService->getAssociatedRegionList($user->emp_id);
        $territories = $hierarchyService->getAssociatedTerritoryList($user->emp_id);

        // Available financial years
        $availableFinancialYears = $this->getAvailableFinancialYears();

        return view('reports.payment', compact(
            'records',
            'summary',
            'financialYear',
            'month',
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
            'availableFinancialYears',
            'accessLevel'
        ));
    }

    /**
     * Get available financial years from salary_processings data
     */
    private function getAvailableFinancialYears()
    {
        $years = SalaryProcessing::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $financialYears = [];
        foreach ($years as $year) {
            $financialYears[] = ($year - 1) . '-' . $year;
            $financialYears[] = $year . '-' . ($year + 1);
        }

        $financialYears = array_unique($financialYears);
        sort($financialYears);
        
        // Add current FY if empty
        if (empty($financialYears)) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            $financialYears[] = ($currentMonth >= 4) 
                ? $currentYear . '-' . ($currentYear + 1)
                : ($currentYear - 1) . '-' . $currentYear;
        }

        return $financialYears;
    }

    /**
     * Get payment summary statistics
     */
    private function getPaymentSummary($query)
    {
        $results = $query->get();

        $totalNetPay = $results->sum('net_pay');

        $pendingCount = $results->where('payment_status', 'pending')->count();
        $processedCount = $results->where('payment_status', 'processed')->count();
        $paidCount = $results->where('payment_status', 'paid')->count();
        $heldCount = $results->where('payment_status', 'held')->count();
        $processingRelease = $results->where('status', 'release')->count();

        return [
            'total_candidates' => $results->count(),
            'total_net_pay' => $totalNetPay,
            'avg_net_pay' => $results->count() > 0 ? round($totalNetPay / $results->count(), 2) : 0,
            'pending_count' => $pendingCount,
            'processed_count' => $processedCount,
            'paid_count' => $paidCount,
            'held_count' => $heldCount,
            'processing_release' => $processingRelease,
        ];
    }

    /**
     * Get filters data when employee is selected (AJAX)
     */
    public function getPaymentFiltersByEmployee($employeeId, HierarchyAccessService $hierarchyService)
    {
        try {
            $teamMemberIds = $hierarchyService->getTeamMemberIds($employeeId);
            $teamMemberIdsString = array_map('strval', $teamMemberIds);
            $manager = Employee::where('employee_id', $employeeId)->first();

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

            $businessUnits = $hierarchyService->getAssociatedBusinessUnitList($employeeId);
            $zones = $hierarchyService->getAssociatedZoneList($employeeId);
            $regions = $hierarchyService->getAssociatedRegionList($employeeId);
            $territories = $hierarchyService->getAssociatedTerritoryList($employeeId);
            $verticals = $hierarchyService->getAssociatedVerticalList($employeeId);

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
            'requisition_type' => $request->get('requisition_type', 'All'),
        ];

        $filename = $this->generateExportFileName($filters);
        return Excel::download(new PaymentReportExport($filters, $hierarchyService), $filename);
    }

    private function generateExportFileName($filters)
    {
        $parts = ['Payment_Report'];

        if (!empty($filters['month']) && $filters['month'] != 'All') {
            $monthName = $this->getMonthName($filters['month']);
            $parts[] = $monthName;
        }

        if (!empty($filters['financial_year'])) {
            $parts[] = 'FY_' . $filters['financial_year'];
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