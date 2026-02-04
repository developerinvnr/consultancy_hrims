<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
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

class DetailedSalaryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $month;
    protected $year;
    protected $requisitionType;
    protected $data;

    public function __construct($month, $year, $requisitionType = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->requisitionType = $requisitionType;
        
        // Pre-fetch data with all necessary relationships
        $this->data = $this->prepareData();
    }

    protected function prepareData()
    {
        $query = CandidateMaster::where('final_status', 'A')
            ->with([
                'function',
                'vertical',
                'department',
                'subDepartmentRef',
                'businessUnit',  // Changed from business_unit
                'zoneRef',       // Changed from zone
                'regionRef',     // Changed from region
                'territoryRef',  // Changed from territory
                'salaryProcessings' => function($q) {
                    $q->where('month', $this->month)
                      ->where('year', $this->year);
                }
            ]);

        if ($this->requisitionType && $this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }

        return $query->orderBy('candidate_code')->get();
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'S no.',
            'Code',
            'Name of Party',
            'Function',
            'Vertical',
            'Department',
            'Sub-Department',
            'Section',
            'State',
            'BU',
            'Zone',
            'Region',
            'Territory',
            'Job-Location',
            'Date of joining',
            'Date of Separation',
            'State (for address)',
            'HQ',
            'Paid days',
            'Remuneration',
            'Overtime',
            'Arrear',
            'Total Payable'
        ];
    }

    public function map($candidate): array
    {
        static $index = 0;
        $index++;

        $salary = $candidate->salaryProcessings->first();
        
        // Get paid days and remuneration
        $paidDays = $salary ? $salary->paid_days : 0;
        $remuneration = $salary ? $salary->monthly_salary : ($candidate->remuneration_per_month ?? 0);
        $overtime = $salary ? $salary->extra_amount : 0;
        $arrear = $salary ? ($salary->arrear_amount ?? 0) : 0;
        $totalPayable = $salary ? ($salary->net_pay + $arrear) : $remuneration;

        return [
            $index,
            $candidate->candidate_code,
            $candidate->candidate_name,
            $candidate->function->function_name ?? ($candidate->function_id ?? ''),  // Updated
            $candidate->vertical->vertical_name ?? ($candidate->vertical_id ?? ''),  // Updated
            $candidate->department->department_name ?? ($candidate->department_id ?? ''),  // Updated
            $candidate->subDepartmentRef->sub_department_name ?? '',  // Updated
            '', // Section - you may need to add this field
            $candidate->state_work_location,
            $candidate->businessUnit->business_unit_name ?? ($candidate->business_unit ?? ''),  // Updated
            $candidate->zoneRef->zone_name ?? ($candidate->zone ?? ''),  // Updated
            $candidate->regionRef->region_name ?? ($candidate->region ?? ''),  // Updated
            $candidate->territoryRef->territory_name ?? ($candidate->territory ?? ''),  // Updated
            $candidate->work_location_hq,
            $candidate->contract_start_date ? $candidate->contract_start_date->format('d-m-Y') : '',
            $candidate->contract_end_date ? $candidate->contract_end_date->format('d-m-Y') : '',
            $candidate->state_residence,
            $candidate->work_location_hq,
            $paidDays,
            $remuneration,
            $overtime,
            $arrear,
            $totalPayable
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Auto-size columns
                $columns = range('A', 'W'); // A to W for 23 columns
                foreach ($columns as $column) {
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
                }

                // Set specific column widths
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(30); // Name
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20); // Function
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(20); // Vertical
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(25); // Department
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(20); // Sub-Department
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(20); // BU
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(15); // Zone
                $event->sheet->getDelegate()->getColumnDimension('L')->setWidth(15); // Region
                $event->sheet->getDelegate()->getColumnDimension('M')->setWidth(15); // Territory
                $event->sheet->getDelegate()->getColumnDimension('O')->setWidth(15); // Date of joining
                $event->sheet->getDelegate()->getColumnDimension('P')->setWidth(15); // Date of separation

                // Format number columns
                $event->sheet->getStyle('S2:W1000')->getNumberFormat()->setFormatCode('#,##0.00');
                
                // Center align columns
                $event->sheet->getStyle('A2:A1000')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('S2:S1000')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Paid days
                $event->sheet->getStyle('T2:W1000')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Amount columns
                
                // Add borders
                $event->sheet->getStyle('A1:W1000')
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Freeze header row
                $event->sheet->getDelegate()->freezePane('A2');
                
                // Add filter
                $event->sheet->getDelegate()->setAutoFilter('A1:W1');
                
                // Add title
                $monthName = date('F', mktime(0, 0, 0, $this->month, 1));
                $title = "Detailed Remuneration Report - {$monthName} {$this->year}";
                if ($this->requisitionType && $this->requisitionType !== 'All') {
                    $title .= " ({$this->requisitionType})";
                }
                
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);
                $event->sheet->getDelegate()->mergeCells('A1:W1');
                $event->sheet->setCellValue('A1', $title);
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
                ]);
                
                // Move header row down
                $event->sheet->getDelegate()->fromArray($this->headings(), null, 'A3', true);
                
                // Style the new header row
                $event->sheet->getStyle('A3:W3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true]
                ]);
            },
        ];
    }
}