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
        
        // ✅ Store all filters for display
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
        // ✅ Get logged in user
        $user = Auth::user();
        $employee = Employee::where('employee_id', $user->emp_id)->first();

        // ✅ Build query with hierarchy joins
        $query = CandidateMaster::query()
            ->leftJoin('manpower_requisitions as mr', 'mr.id', '=', 'candidate_master.requisition_id')
            
            // ✅ Join hierarchy tables
            ->leftJoin('core_business_unit as bu', 'bu.id', '=', 'candidate_master.business_unit')
            ->leftJoin('core_zone as zone', 'zone.id', '=', 'candidate_master.zone')
            ->leftJoin('core_region as region', 'region.id', '=', 'candidate_master.region')
            ->leftJoin('core_territory as territory', 'territory.id', '=', 'candidate_master.territory')
            ->leftJoin('core_vertical as vertical', 'vertical.id', '=', 'candidate_master.vertical_id')
            ->leftJoin('core_department as dept', 'dept.id', '=', 'candidate_master.department_id')

            // ✅ Latest Agreement
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

            // ✅ Latest Courier
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

        // ✅ Apply Hierarchy Access Control
        if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
            $allowedEmpIds = $this->hierarchyService->getReportingEmployeeIds($user->emp_id);
            $query->whereIn('candidate_master.reporting_manager_employee_id', $allowedEmpIds);
            
            $accessLevel = $this->hierarchyService->getAccessLevel($employee);
            
            switch ($accessLevel) {
                case 'territory':
                    $query->where('candidate_master.territory', $employee->territory);
                    break;
                case 'region':
                    $territoriesInRegion = DB::table('core_territory')
                        ->where('region_id', $employee->region)
                        ->pluck('id');
                    $query->whereIn('candidate_master.territory', $territoriesInRegion);
                    break;
                case 'zone':
                    $regionsInZone = DB::table('core_region')
                        ->where('zone_id', $employee->zone)
                        ->pluck('id');
                    $territoriesInZone = DB::table('core_territory')
                        ->whereIn('region_id', $regionsInZone)
                        ->pluck('id');
                    $query->whereIn('candidate_master.territory', $territoriesInZone);
                    break;
                case 'bu':
                    $zonesInBU = DB::table('core_zone')
                        ->where('bu_id', $employee->bu)
                        ->pluck('id');
                    $regionsInBU = DB::table('core_region')
                        ->whereIn('zone_id', $zonesInBU)
                        ->pluck('id');
                    $territoriesInBU = DB::table('core_territory')
                        ->whereIn('region_id', $regionsInBU)
                        ->pluck('id');
                    $query->whereIn('candidate_master.territory', $territoriesInBU);
                    break;
            }
        }

        // ✅ Apply user-selected hierarchy filters
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
            $query->where('candidate_master.reporting_manager_employee_id', $this->filters['employee']);
        }

        // ✅ Apply existing filters
        if ($this->departmentId) {
            $query->where('candidate_master.department_id', $this->departmentId);
        }

        if ($this->requisitionType) {
            $query->where('candidate_master.requisition_type', $this->requisitionType);
        }

        if ($this->status) {
            $query->where('mr.status', $this->status);
        }

        // ✅ Date filter based on Contract Start Date
        if ($this->month) {
            $year = ($this->month >= 4) ? $this->startYear : $this->endYear;
            $startDate = "{$year}-{$this->month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth();

            $query->whereNotNull('candidate_master.contract_start_date')
                  ->whereBetween('candidate_master.contract_start_date', [$startDate, $endDate]);
        } else {
            $query->whereNotNull('candidate_master.contract_start_date')
                  ->whereBetween('candidate_master.contract_start_date', [
                    $this->startYear . '-04-01',
                    $this->endYear . '-03-31'
                  ]);
        }

        $records = $query->orderByDesc('candidate_master.contract_start_date')->get();

        // ✅ Initialize stage totals
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

        // ✅ Prepare data
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
                $lastRow = count($this->data) + 3; // +3 for title, headers, and summary row
                $lastColumn = chr(65 + (count($this->headings()) - 1)); // Calculate last column letter

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(8);   // S No.
                $sheet->getColumnDimension('B')->setWidth(12);  // Req ID
                $sheet->getColumnDimension('C')->setWidth(30);  // Candidate Name
                $sheet->getColumnDimension('D')->setWidth(15);  // Submission Date
                $sheet->getColumnDimension('E')->setWidth(18);  // Contract Start Date
                $sheet->getColumnDimension('F')->setWidth(18);  // Business Unit
                $sheet->getColumnDimension('G')->setWidth(15);  // Zone
                $sheet->getColumnDimension('H')->setWidth(15);  // Region
                $sheet->getColumnDimension('I')->setWidth(15);  // Territory
                $sheet->getColumnDimension('J')->setWidth(15);  // Vertical
                $sheet->getColumnDimension('K')->setWidth(20);  // Department

                // Stage columns
                $stageCount = count($this->stages);
                for ($i = 0; $i < $stageCount; $i++) {
                    $col = chr(76 + $i); // L onwards for dates
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                for ($i = 0; $i < $stageCount; $i++) {
                    $col = chr(76 + $stageCount + $i); // TAT columns
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

                // Center align S No. column
                $sheet->getStyle('A2:A' . ($lastRow + 1))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Add summary row with stage statistics
                $summaryRow = $lastRow + 1;
                
                // Merge first few columns for summary label
                $sheet->mergeCells('A' . $summaryRow . ':C' . $summaryRow);
                $sheet->setCellValue('A' . $summaryRow, 'STAGE SUMMARY');
                $sheet->getStyle('A' . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4A261']]
                ]);

                // Add summary statistics for each stage
                $currentCol = 'D';
                $stageIndex = 0;
                foreach ($this->stages as $key => $stage) {
                    $totals = $this->stageTotals[$key];
                    $summaryText = $stage['name'] . "\n" .
                                  "Total: " . $totals['total'] . "\n" .
                                  "Avg: " . $totals['avg'] . "d\n" .
                                  "≤1d: " . $totals['within_1'] . "\n" .
                                  "1-3d: " . $totals['within_3'] . "\n" .
                                  ">3d: " . $totals['above_3'];
                    
                    $sheet->setCellValue($currentCol . $summaryRow, $summaryText);
                    $sheet->getStyle($currentCol . $summaryRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle($currentCol . $summaryRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    
                    $currentCol = chr(ord($currentCol) + 1);
                    $stageIndex++;
                    
                    // Skip TAT column in summary
                    $currentCol = chr(ord($currentCol) + 1);
                }

                // Style summary row
                $sheet->getStyle('A' . $summaryRow . ':' . $lastColumn . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP]
                ]);
                $sheet->getRowDimension($summaryRow)->setRowHeight(75);

                // Add title
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:' . $lastColumn . '1');
                
                $title = "TAT Report (Action Wise) - {$this->financialYear}";
                $filterText = '';
                
                // Build filter description
                $filterDescriptions = [];
                if ($this->month) {
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
                if ($this->requisitionType) {
                    $filterDescriptions[] = "Type: {$this->requisitionType}";
                }
                if ($this->status) {
                    $filterDescriptions[] = "Status: {$this->status}";
                }
                
                if (!empty($filterDescriptions)) {
                    $title .= " (" . implode(', ', $filterDescriptions) . ")";
                }
                
                $sheet->setCellValue('A1', $title);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
                ]);
            },
        ];
    }
}