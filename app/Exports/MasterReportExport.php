<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use App\Models\CoreDepartment;
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

class MasterReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $month;
    protected $year;
    protected $requisitionType;
    protected $workLocation;
    protected $departmentId;
    protected $search;
    
    public function __construct($month, $year, $requisitionType = null, $workLocation = null, $departmentId = null, $search = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->requisitionType = $requisitionType;
        $this->workLocation = $workLocation;
        $this->departmentId = $departmentId;
        $this->search = $search;
    }
    
    public function collection()
    {
        $query = CandidateMaster::where('final_status', 'A')
            ->with(['salaryProcessings' => function($q) {
                $q->where('month', $this->month)
                  ->where('year', $this->year);
            }])
            ->with('department');
        
        if ($this->requisitionType && $this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }
        
        if ($this->workLocation) {
            $query->where('work_location_hq', $this->workLocation);
        }
        
        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('candidate_code', 'like', "%{$this->search}%")
                  ->orWhere('candidate_name', 'like', "%{$this->search}%")
                  ->orWhere('mobile_no', 'like', "%{$this->search}%")
                  ->orWhere('pan_no', 'like', "%{$this->search}%")
                  ->orWhere('aadhaar_no', 'like', "%{$this->search}%");
            });
        }
        
        return $query->orderBy('candidate_code')->get();
    }
    
    public function headings(): array
    {
        $monthName = date('F', mktime(0, 0, 0, $this->month, 1));
        $title = "MASTER Party REPORT - {$monthName} {$this->year}";
        
        if ($this->requisitionType !== 'All') {
            $title .= " ({$this->requisitionType})";
        }
        if ($this->workLocation) {
            $title .= " - Location: {$this->workLocation}";
        }
        
        return [
            [$title],
            [], // Empty row for spacing
            [
                'SL No',
                'Party Code',
                'Party Name',
                'Father Name',
                'Mobile No',
                'Email',
                'Requisition Type',
                'Department',
                'Designation',
                'Work Location',
                'District',
                'State',
                'Zone',
                'Region',
                'Reporting Manager',
                'Contract Start Date',
                'Contract End Date',
                'Remuneration/Month',
                'Net Pay',
                'Bank Name',
                'Account No',
                'IFSC Code',
                'Account Holder',
                'PAN No',
                'Aadhaar No',
                'Status',
                'Processed Date'
            ]
        ];
    }
    
    public function map($candidate): array
    {
        $salary = $candidate->salaryProcessings->first();
        
        return [
            '', // Will be filled with serial number in events
            $candidate->candidate_code,
            $candidate->candidate_name,
            $candidate->father_name ?? 'N/A',
            $candidate->mobile_no,
            $candidate->candidate_email,
            $candidate->requisition_type,
            $candidate->department->name ?? 'N/A',
            $candidate->designation ?? 'N/A',
            $candidate->work_location_hq ?? 'N/A',
            $candidate->district ?? 'N/A',
            $candidate->state_work_location ?? 'N/A',
            $candidate->zone ?? 'N/A',
            $candidate->region ?? 'N/A',
            $candidate->reporting_to ?? 'N/A',
            $candidate->contract_start_date ? date('d-m-Y', strtotime($candidate->contract_start_date)) : 'N/A',
            $candidate->contract_end_date ? date('d-m-Y', strtotime($candidate->contract_end_date)) : 'N/A',
            $candidate->remuneration_per_month,
            $salary ? $salary->net_pay : 0,
            $candidate->bank_name ?? 'N/A',
            $candidate->bank_account_no ?? 'N/A',
            $candidate->bank_ifsc ?? 'N/A',
            $candidate->account_holder_name ?? 'N/A',
            $candidate->pan_no ?? 'N/A',
            $candidate->aadhaar_no ?? 'N/A',
            $salary ? 'Processed' : 'Pending',
            $salary && $salary->processed_at ? $salary->processed_at->format('d-m-Y H:i') : 'Not Processed'
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Title row style (row 1)
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '2c3e50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
            ],
            
            // Header row style (row 3)
            3 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
            ],
            
            // Status column styling
            'Z' => function($cell) {
                if ($cell->getValue() == 'Pending') {
                    return ['font' => ['italic' => true, 'color' => ['rgb' => 'FF9900']]];
                }
                return ['font' => ['bold' => true, 'color' => ['rgb' => '007F00']]];
            },
        ];
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge title row - adjust to AB (since we removed columns)
                $sheet->mergeCells('A1:AB1');
                
                // Set column widths
                $columnWidths = [
                    'A' => 8,   // SL No
                    'B' => 15,  // Employee Code
                    'C' => 25,  // Employee Name
                    'D' => 20,  // Father Name
                    'E' => 15,  // Mobile No
                    'F' => 25,  // Email
                    'G' => 15,  // Requisition Type
                    'H' => 20,  // Department
                    'I' => 20,  // Designation
                    'J' => 20,  // Work Location
                    'K' => 15,  // District
                    'L' => 15,  // State
                    'M' => 12,  // Zone
                    'N' => 12,  // Region
                    'O' => 25,  // Reporting Manager
                    'P' => 15,  // Contract Start
                    'Q' => 15,  // Contract End
                    'R' => 15,  // Remuneration/Month
                    'S' => 15,  // Net Pay
                    'T' => 20,  // Bank Name
                    'U' => 20,  // Account No
                    'V' => 15,  // IFSC Code
                    'W' => 20,  // Account Holder
                    'X' => 15,  // PAN No
                    'Y' => 20,  // Aadhaar No
                    'Z' => 12,  // Status
                    'AA' => 20  // Processed Date
                ];
                
                foreach ($columnWidths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
                
                // Apply borders to data
                $highestRow = $sheet->getHighestRow();
                $event->sheet->getStyle('A3:AA' . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Format currency columns (R and S now)
                $currencyColumns = ['R', 'S'];
                foreach ($currencyColumns as $column) {
                    $event->sheet->getStyle($column . '4:' . $column . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
                
                // Add ₹ symbol to currency columns in header row
                $event->sheet->setCellValue('R3', 'Remuneration/Month (₹)');
                $event->sheet->setCellValue('S3', 'Net Pay (₹)');
                
                // Center align columns
                $centerColumns = ['A', 'G', 'Z'];
                foreach ($centerColumns as $column) {
                    $event->sheet->getStyle($column . '4:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Right align currency columns
                $rightAlignColumns = ['R', 'S'];
                foreach ($rightAlignColumns as $column) {
                    $event->sheet->getStyle($column . '4:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                
                // Wrap text for some columns
                $wrapColumns = ['C', 'H', 'I', 'J', 'O'];
                foreach ($wrapColumns as $column) {
                    $event->sheet->getStyle($column . '4:' . $column . $highestRow)
                        ->getAlignment()
                        ->setWrapText(true);
                }
                
                // Add serial numbers
                for ($i = 4; $i <= $highestRow; $i++) {
                    $sheet->setCellValue('A' . $i, $i - 3);
                }
                
                // Auto-filter for header row
                $sheet->setAutoFilter('A3:AA3');
                
                // Freeze header rows
                $sheet->freezePane('A4');
                
                // Add generated timestamp and summary
                $generatedAt = now()->format('d-m-Y H:i:s');
                $summaryRow = $highestRow + 2;
                
                $sheet->setCellValue('A' . $summaryRow, "Report Summary:");
                $sheet->mergeCells('A' . $summaryRow . ':C' . $summaryRow);
                $event->sheet->getStyle('A' . $summaryRow)
                    ->getFont()->setBold(true)->setSize(12);
                
                $sheet->setCellValue('A' . ($summaryRow + 1), "Total Party:");
                $sheet->setCellValue('B' . ($summaryRow + 1), $this->collection()->count());
                
                $processedCount = $this->collection()->filter(function($candidate) {
                    return $candidate->salaryProcessings->isNotEmpty();
                })->count();
                
                $sheet->setCellValue('A' . ($summaryRow + 2), "Salary Processed:");
                $sheet->setCellValue('B' . ($summaryRow + 2), $processedCount);
                
                $sheet->setCellValue('A' . ($summaryRow + 3), "Pending Salary:");
                $sheet->setCellValue('B' . ($summaryRow + 3), $this->collection()->count() - $processedCount);
                
                $totalNetPay = $this->collection()->sum(function($candidate) {
                    $salary = $candidate->salaryProcessings->first();
                    return $salary ? $salary->net_pay : 0;
                });
                
                $sheet->setCellValue('A' . ($summaryRow + 4), "Total Net Pay:");
                $sheet->setCellValue('B' . ($summaryRow + 4), '₹ ' . number_format($totalNetPay, 2));
                
                // Adjust position for generated timestamp
                $sheet->setCellValue('W' . ($summaryRow + 1), "Generated on:");
                $sheet->setCellValue('X' . ($summaryRow + 1), $generatedAt);
                
                $event->sheet->getStyle('W' . ($summaryRow + 1) . ':X' . ($summaryRow + 1))
                    ->getFont()
                    ->setItalic(true)
                    ->setColor(['rgb' => '666666']);
                
                // Apply borders to summary section
                $event->sheet->getStyle('A' . $summaryRow . ':B' . ($summaryRow + 4))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}