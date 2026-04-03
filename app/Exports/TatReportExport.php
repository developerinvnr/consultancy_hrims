<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Services\HierarchyAccessService;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class TatReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $financialYear;
    protected $startYear;
    protected $endYear;
    protected $month;
    protected $departmentId;
    protected $requisitionType;
    protected $status;
    protected $filters;
    protected $data;
    protected $stageTotals;
    protected $hierarchyService;

    // Stage configuration
    protected $stages = [
        'hr' => ['name' => 'HR', 'from' => 'submission_date', 'to' => 'hr_verification_date'],
        'approval' => ['name' => 'Approval', 'from' => 'hr_verification_date', 'to' => 'approval_date'],
        'agreement_create' => ['name' => 'Agreement Create', 'from' => 'approval_date', 'to' => 'agreement_created_date'],
        'agreement_upload' => ['name' => 'Agreement Upload', 'from' => 'agreement_created_date', 'to' => 'agreement_uploaded_date'],
        'courier_dispatch' => ['name' => 'Courier Dispatch', 'from' => 'agreement_uploaded_date', 'to' => 'dispatch_date'],
        'courier_delivery' => ['name' => 'Courier Delivery', 'from' => 'dispatch_date', 'to' => 'received_date'],
        'file_creation' => ['name' => 'File Creation', 'from' => 'received_date', 'to' => 'file_created_date'],
    ];

    public function __construct(
        $financialYear,
        $month,
        $departmentId,
        $requisitionType,
        $status,
        $buId = 'All',
        $zoneId = 'All',
        $regionId = 'All',
        $territoryId = 'All',
        $verticalId = 'All',
        $employeeId = 'All'
    ) {
        $this->financialYear = $financialYear;
        $this->month = $month;
        $this->departmentId = $departmentId;
        $this->requisitionType = $requisitionType;
        $this->status = $status;
        
        // Store all filters for display
        $this->filters = [
            'bu' => $buId,
            'zone' => $zoneId,
            'region' => $regionId,
            'territory' => $territoryId,
            'vertical' => $verticalId,
            'employee' => $employeeId,
            'department' => $departmentId,
            'requisition_type' => $requisitionType,
            'status' => $status,
            'month' => $month
        ];

        $this->hierarchyService = app(HierarchyAccessService::class);

        if (!$this->financialYear) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            $this->financialYear = ($currentMonth >= 4)
                ? $currentYear . '-' . ($currentYear + 1)
                : ($currentYear - 1) . '-' . $currentYear;
        }

        [$this->startYear, $this->endYear] = explode('-', $this->financialYear);

        $this->prepareData();
    }

    protected function prepareData()
    {
        // Get logged in user
        $user = Auth::user();
        $employee = Employee::where('employee_id', $user->emp_id)->first();

        // Build query with hierarchy joins
        $query = CandidateMaster::query()
            ->leftJoin('manpower_requisitions as mr', 'mr.id', '=', 'candidate_master.requisition_id')
            
            // Join hierarchy tables for display names
            ->leftJoin('core_business_unit as bu', 'bu.id', '=', 'candidate_master.business_unit')
            ->leftJoin('core_zone as zone', 'zone.id', '=', 'candidate_master.zone')
            ->leftJoin('core_region as region', 'region.id', '=', 'candidate_master.region')
            ->leftJoin('core_territory as territory', 'territory.id', '=', 'candidate_master.territory')
            ->leftJoin('core_vertical as vertical', 'vertical.id', '=', 'candidate_master.vertical_id')
            ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')

            // Latest Unsigned Agreement
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

            // Latest Signed Agreement
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
                'dept.department_name'
            );

        // Apply Hierarchy Access Control - FIXED to use employee records for territory mapping
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $this->hierarchyService->getReportingEmployeeIds($user->emp_id);
            $allowedEmpIds[] = $user->emp_id;
            $allowedEmpIds = array_unique($allowedEmpIds);
            $allowedEmpIdsString = array_map('strval', $allowedEmpIds);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIdsString);

            $accessLevel = $this->hierarchyService->getAccessLevel($employee);
            
            // Get territory IDs based on access level using employee records
            $territoryIds = [];

            switch ($accessLevel) {
                case 'territory':
                    if ($employee->territory && $employee->territory != 0) {
                        $territoryIds = [$employee->territory];
                    }
                    break;
                case 'region':
                    if ($employee->region && $employee->region != 0) {
                        $territoryIds = Employee::where('region', $employee->region)
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
                        $regionsInZone = Employee::where('zone', $employee->zone)
                            ->where('emp_status', 'A')
                            ->whereNotNull('region')
                            ->where('region', '!=', 0)
                            ->distinct()
                            ->pluck('region')
                            ->toArray();

                        if (!empty($regionsInZone)) {
                            $territoryIds = Employee::whereIn('region', $regionsInZone)
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
                        $zonesInBU = Employee::where('bu', $employee->bu)
                            ->where('emp_status', 'A')
                            ->whereNotNull('zone')
                            ->where('zone', '!=', 0)
                            ->distinct()
                            ->pluck('zone')
                            ->toArray();

                        if (!empty($zonesInBU)) {
                            $regionsInBU = Employee::whereIn('zone', $zonesInBU)
                                ->where('emp_status', 'A')
                                ->whereNotNull('region')
                                ->where('region', '!=', 0)
                                ->distinct()
                                ->pluck('region')
                                ->toArray();

                            if (!empty($regionsInBU)) {
                                $territoryIds = Employee::whereIn('region', $regionsInBU)
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
        if ($this->filters['bu'] && $this->filters['bu'] != 'All') {
            $query->where('candidate_master.business_unit', $this->filters['bu']);
        }

        if ($this->filters['zone'] && $this->filters['zone'] != 'All') {
            $query->where('candidate_master.zone', $this->filters['zone']);
        }

        if ($this->filters['region'] && $this->filters['region'] != 'All') {
            $query->where('candidate_master.region', $this->filters['region']);
        }

        if ($this->filters['territory'] && $this->filters['territory'] != 'All') {
            $query->where('candidate_master.territory', $this->filters['territory']);
        }

        if ($this->filters['vertical'] && $this->filters['vertical'] != 'All') {
            $query->where('candidate_master.vertical_id', $this->filters['vertical']);
        }

        if ($this->filters['employee'] && $this->filters['employee'] != 'All') {
            $teamMemberIds = $this->hierarchyService->getTeamMemberIds($this->filters['employee']);
            $teamMemberIds[] = $this->filters['employee'];
            $query->whereIn('candidate_master.reporting_manager_employee_id', $teamMemberIds);
        }

        // Apply existing filters
        if ($this->departmentId && $this->departmentId != 'All') {
            $query->where('candidate_master.department_id', $this->departmentId);
        }

        if ($this->requisitionType && $this->requisitionType !== 'All') {
            $query->where('candidate_master.requisition_type', $this->requisitionType);
        }

        if ($this->status && $this->status != 'All') {
            $query->where('mr.status', $this->status);
        }

        // Only show active candidates
        $query->whereIn('candidate_master.final_status', ['A', 'D']);

        // Date filter based on Contract Start Date
        if ($this->month && $this->month != 'All') {
            $year = ($this->month >= 4) ? $this->startYear : $this->endYear;
            $startDate = "{$year}-{$this->month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

            $query->whereNotNull('candidate_master.contract_start_date')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('candidate_master.contract_start_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('candidate_master.contract_start_date', '<=', $endDate)
                                ->where(function ($q3) use ($startDate) {
                                    $q3->whereNull('candidate_master.contract_end_date')
                                        ->orWhere('candidate_master.contract_end_date', '>=', $startDate);
                                });
                        });
                });
        } else {
            $fyStart = $this->startYear . '-04-01';
            $fyEnd = $this->endYear . '-03-31';

            $query->whereNotNull('candidate_master.contract_start_date')
                ->where(function ($q) use ($fyStart, $fyEnd) {
                    $q->whereBetween('candidate_master.contract_start_date', [$fyStart, $fyEnd])
                        ->orWhere(function ($q2) use ($fyStart, $fyEnd) {
                            $q2->where('candidate_master.contract_start_date', '<=', $fyEnd)
                                ->where(function ($q3) use ($fyStart) {
                                    $q3->whereNull('candidate_master.contract_end_date')
                                        ->orWhere('candidate_master.contract_end_date', '>=', $fyStart);
                                });
                        });
                });
        }

        $records = $query->orderByDesc('candidate_master.contract_start_date')->get();

        // Initialize stage totals
        $this->stageTotals = [];
        foreach ($this->stages as $key => $stage) {
            $this->stageTotals[$key] = [
                'total' => 0,
                'within_1' => 0,
                'within_3' => 0,
                'above_3' => 0,
                'sum_days' => 0
            ];
        }

        // Prepare data
        $this->data = [];
        
        foreach ($records as $row) {
            $record = [
                'requisition_id' => $row->requisition_id,
                'candidate_name' => $row->candidate_name,
                'submission_date' => $row->submission_date ? Carbon::parse($row->submission_date)->format('d-M-Y') : '-',
                'contract_start_date' => $row->contract_start_date ? Carbon::parse($row->contract_start_date)->format('d-M-Y') : '-',
                'business_unit' => $row->business_unit_name ?? '-',
                'zone' => $row->zone_name ?? '-',
                'region' => $row->region_name ?? '-',
                'territory' => $row->territory_name ?? '-',
                'vertical' => $row->vertical_name ?? '-',
                'department' => $row->department_name ?? '-',
                'stages' => []
            ];

            // Process each stage
            foreach ($this->stages as $key => $stage) {
                if ($key === 'file_creation') {
                    $fromDate = $row->received_date
                        ?? $row->agreement_uploaded_date
                        ?? $row->agreement_created_date
                        ?? $row->approval_date;
                    $toDate = $row->file_created_date;
                } else {
                    $fromDate = $row->{$stage['from']} ?? null;
                    $toDate = $row->{$stage['to']} ?? null;
                }

                $stageData = [
                    'date' => $toDate ? Carbon::parse($toDate)->format('d-M-Y') : '-',
                    'tat' => null,
                    'tat_text' => '-'
                ];

                if ($fromDate && $toDate) {
                    $days = max(0, ceil(Carbon::parse($fromDate)->diffInDays($toDate)));
                    $stageData['tat'] = $days;
                    
                    if ($days <= 1) {
                        $stageData['tat_text'] = 'Within 1 Day';
                    } else {
                        $stageData['tat_text'] = $days . ' Days';
                    }
                    
                    // Update totals
                    $this->stageTotals[$key]['total']++;
                    $this->stageTotals[$key]['sum_days'] += $days;
                    
                    if ($days <= 1) {
                        $this->stageTotals[$key]['within_1']++;
                    } elseif ($days <= 3) {
                        $this->stageTotals[$key]['within_3']++;
                    } else {
                        $this->stageTotals[$key]['above_3']++;
                    }
                }
                
                $record['stages'][$key] = $stageData;
            }
            
            $this->data[] = $record;
        }
        
        // Calculate averages
        foreach ($this->stageTotals as $key => &$totals) {
            $totals['avg'] = $totals['total'] > 0 
                ? round($totals['sum_days'] / $totals['total'], 2) 
                : 0;
        }
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        $headers = [
            'S No.',
            'Req ID',
            'Candidate Name',
            'Submission Date',
            'Contract Start Date',
            'Business Unit',
            'Zone',
            'Region',
            'Territory',
            'Vertical',
            'Department'
        ];

        // Add stage date headers
        foreach ($this->stages as $stage) {
            $headers[] = $stage['name'] . ' Date';
        }

        // Add stage TAT headers
        foreach ($this->stages as $stage) {
            $headers[] = $stage['name'] . ' TAT';
        }

        return $headers;
    }

    public function map($record): array
    {
        static $index = 0;
        $index++;

        $row = [
            $index,
            $record['requisition_id'],
            $record['candidate_name'],
            $record['submission_date'],
            $record['contract_start_date'],
            $record['business_unit'],
            $record['zone'],
            $record['region'],
            $record['territory'],
            $record['vertical'],
            $record['department']
        ];

        // Add stage dates
        foreach ($this->stages as $key => $stage) {
            $row[] = $record['stages'][$key]['date'];
        }

        // Add stage TAT
        foreach ($this->stages as $key => $stage) {
            $row[] = $record['stages'][$key]['tat_text'];
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->data) + 3;
                $lastColumn = chr(65 + (count($this->headings()) - 1));

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(20);

                $stageCount = count($this->stages);
                for ($i = 0; $i < $stageCount; $i++) {
                    $col = chr(76 + $i);
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                for ($i = 0; $i < $stageCount; $i++) {
                    $col = chr(76 + $stageCount + $i);
                    $sheet->getColumnDimension($col)->setWidth(15);
                }

                // Add borders
                $sheet->getStyle('A1:' . $lastColumn . ($lastRow + 1))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Style header row
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);

                // Add title
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:' . $lastColumn . '1');
                
                $title = "TAT Report (Action Wise) - {$this->financialYear}";
                $filterText = '';
                
                $filterDescriptions = [];
                if ($this->month && $this->month != 'All') {
                    $monthName = date('F', mktime(0, 0, 0, $this->month, 1));
                    $filterDescriptions[] = "Month: {$monthName}";
                }
                if ($this->filters['bu'] && $this->filters['bu'] != 'All') {
                    $filterDescriptions[] = "BU: {$this->filters['bu']}";
                }
                if ($this->filters['zone'] && $this->filters['zone'] != 'All') {
                    $filterDescriptions[] = "Zone: {$this->filters['zone']}";
                }
                if ($this->filters['region'] && $this->filters['region'] != 'All') {
                    $filterDescriptions[] = "Region: {$this->filters['region']}";
                }
                if ($this->filters['territory'] && $this->filters['territory'] != 'All') {
                    $filterDescriptions[] = "Territory: {$this->filters['territory']}";
                }
                if ($this->filters['vertical'] && $this->filters['vertical'] != 'All') {
                    $filterDescriptions[] = "Vertical: {$this->filters['vertical']}";
                }
                if ($this->requisitionType && $this->requisitionType !== 'All') {
                    $filterDescriptions[] = "Type: {$this->requisitionType}";
                }
                if ($this->status && $this->status != 'All') {
                    $filterDescriptions[] = "Status: {$this->status}";
                }
                
                if (!empty($filterDescriptions)) {
                    $title .= " (" . implode(', ', $filterDescriptions) . ")";
                }
                
                $sheet->setCellValue('A1', $title);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
                ]);

                // Add generated date
                $sheet->setCellValue('A2', 'Generated on: ' . Carbon::now()->format('d-m-Y H:i:s'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
                ]);
            },
        ];
    }
}