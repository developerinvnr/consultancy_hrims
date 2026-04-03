<?php
// app/Exports/PaymentReportExport.php

namespace App\Exports;

use App\Models\SalaryProcessing;
use App\Models\CandidateMaster;
use App\Models\Employee;
use App\Services\HierarchyAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PaymentReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;
    protected $hierarchyService;
    protected $user;
    protected $employee;

    public function __construct(array $filters, HierarchyAccessService $hierarchyService)
    {
        $this->filters = $filters;
        $this->hierarchyService = $hierarchyService;
        $this->user = Auth::user();
        $this->employee = Employee::where('employee_id', $this->user->emp_id)->first();
    }

    public function query()
    {
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
            )
            ->whereIn('candidate_master.final_status', ['A', 'D']);

        // Apply hierarchy access control
        if (!$this->user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $this->hierarchyService->getReportingEmployeeIds($this->user->emp_id);
            $allowedEmpIds[] = $this->user->emp_id;
            $allowedEmpIds = array_unique($allowedEmpIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIds);
        }

        // Apply filters
        $this->applyFilters($query);

        return $query->orderBy('salary_processings.year', 'desc')
            ->orderBy('salary_processings.month', 'desc')
            ->orderBy('salary_processings.created_at', 'desc');
    }

    protected function applyFilters($query)
    {
        // Hierarchy filters
        if (!empty($this->filters['bu']) && $this->filters['bu'] != 'All') {
            $query->where('candidate_master.business_unit', $this->filters['bu']);
        }

        if (!empty($this->filters['zone']) && $this->filters['zone'] != 'All') {
            $query->where('candidate_master.zone', $this->filters['zone']);
        }

        if (!empty($this->filters['region']) && $this->filters['region'] != 'All') {
            $query->where('candidate_master.region', $this->filters['region']);
        }

        if (!empty($this->filters['territory']) && $this->filters['territory'] != 'All') {
            $query->where('candidate_master.territory', $this->filters['territory']);
        }

        if (!empty($this->filters['vertical']) && $this->filters['vertical'] != 'All') {
            $query->where('candidate_master.vertical_id', $this->filters['vertical']);
        }

        // Employee filter (reporting manager)
        if (!empty($this->filters['employee']) && $this->filters['employee'] != 'All') {
            $teamMemberIds = $this->hierarchyService->getTeamMemberIds($this->filters['employee']);
            $teamMemberIdsString = array_map('strval', $teamMemberIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $teamMemberIdsString);
        }

        // Department filter
        if (!empty($this->filters['department_id']) && $this->filters['department_id'] != 'All') {
            $query->where('candidate_master.department_id', $this->filters['department_id']);
        }

        // Sub-department filter
        if (!empty($this->filters['sub_department']) && $this->filters['sub_department'] != 'All') {
            $query->where('candidate_master.sub_department', $this->filters['sub_department']);
        }

        // Month/Year filters
        $month = $this->filters['month'] ?? null;
        $year = $this->filters['year'] ?? null;
        $financialYear = $this->filters['financial_year'] ?? null;

        if ($month && $year) {
            $query->where('salary_processings.month', $month)
                  ->where('salary_processings.year', $year);
        } elseif (!$month && $year) {
            $query->where('salary_processings.year', $year);
        } elseif ($month && !$year) {
            $query->where('salary_processings.month', $month);
        } elseif ($financialYear) {
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

        // Payment status filter
        if (!empty($this->filters['status']) && $this->filters['status'] != 'All') {
            $query->where('salary_processings.payment_status', $this->filters['status']);
        }

        // Payment mode filter
        if (!empty($this->filters['payment_mode']) && $this->filters['payment_mode'] != 'All') {
            $query->where('salary_processings.payment_mode', $this->filters['payment_mode']);
        }
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Candidate Name',
            'Candidate Code',
            'Requisition ID',
            'Requisition Type',
            'Month',
            'Year',
            'Department',
            'Sub Department',
            'Vertical',
            'Business Unit',
            'Zone',
            'Region',
            'Territory',
            'Reporting Manager',
            'Monthly Salary (₹)',
            'Per Day Salary (₹)',
            'Total Days',
            'Paid Days',
            'CL Days',
            'Absent Days',
            'Approved Sundays',
            'Deduction Amount (₹)',
            'Extra Amount (₹)',
            'Arrear Amount (₹)',
            'Arrear Days',
            'Arrear Remarks',
            'Total Payable (₹)',
            'Net Pay (₹)',
            'Processing Status',
            'Payment Status',
            'Payment Mode',
            'UTR Number',
            'Payment Date',
            'Processed By',
            'Processed At',
            'Hold Remark',
            'Verification Remark',
            'Batch ID',
            'Payment Instruction'
        ];
    }

    public function map($row): array
    {
        static $sno = 0;
        $sno++;

        return [
            $sno,
            $row->candidate_name ?? '-',
            $row->candidate_code ?? '-',
            $row->requisition_id ?? '-',
            $row->requisition_type ?? '-',
            $this->getMonthName($row->month),
            $row->year,
            $row->department_name ?? '-',
            $row->sub_department_name ?? '-',
            $row->vertical_name ?? '-',
            $this->getBusinessUnitName($row->business_unit),
            $this->getZoneName($row->zone),
            $this->getRegionName($row->region),
            $this->getTerritoryName($row->territory),
            $row->reporting_manager_name ?? '-',
            number_format($row->monthly_salary ?? 0, 2),
            number_format($row->per_day_salary ?? 0, 2),
            $row->total_days ?? 0,
            $row->paid_days ?? 0,
            $row->cl_days ?? 0,
            $row->absent_days ?? 0,
            $row->approved_sundays ?? 0,
            number_format($row->deduction_amount ?? 0, 2),
            number_format($row->extra_amount ?? 0, 2),
            number_format($row->arrear_amount ?? 0, 2),
            $row->arrear_days ?? 0,
            $row->arrear_remarks ?? '-',
            number_format($row->total_payable ?? 0, 2),
            number_format($row->net_pay ?? 0, 2),
            ucfirst($row->status ?? '-'),
            ucfirst($row->payment_status ?? '-'),
            $row->payment_mode ?? '-',
            $row->utr_number ?? '-',
            $row->payment_date ? Carbon::parse($row->payment_date)->format('d-M-Y') : '-',
            $row->processed_by ?? '-',
            $row->processed_at ? Carbon::parse($row->processed_at)->format('d-M-Y H:i:s') : '-',
            $row->hr_hold_remark ?? '-',
            $row->verification_remark ?? '-',
            $row->batch_id ?? '-',
            $row->payment_instruction ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }

    public function title(): string
    {
        $title = 'Payment_Report';
        if (!empty($this->filters['month']) && !empty($this->filters['year'])) {
            $title .= '_' . $this->getMonthName($this->filters['month']) . '_' . $this->filters['year'];
        } elseif (!empty($this->filters['financial_year'])) {
            $title .= '_FY_' . $this->filters['financial_year'];
        }
        return $title;
    }

    private function getMonthName($month)
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        return $months[$month] ?? '-';
    }

    private function getBusinessUnitName($buId)
    {
        if (!$buId) return '-';
        $bu = DB::table('core_business_unit')->where('id', $buId)->first();
        return $bu->bu_name ?? '-';
    }

    private function getZoneName($zoneId)
    {
        if (!$zoneId) return '-';
        $zone = DB::table('core_zone')->where('id', $zoneId)->first();
        return $zone->zone_name ?? '-';
    }

    private function getRegionName($regionId)
    {
        if (!$regionId) return '-';
        $region = DB::table('core_region')->where('id', $regionId)->first();
        return $region->region_name ?? '-';
    }

    private function getTerritoryName($territoryId)
    {
        if (!$territoryId) return '-';
        $territory = DB::table('core_territory')->where('id', $territoryId)->first();
        return $territory->territory_name ?? '-';
    }
}