<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use App\Models\SalaryProcessing;
use App\Services\SalaryCalculator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $month;
    protected $year;
    protected $requisitionType;

    public function __construct($month, $year, $requisitionType = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->requisitionType = $requisitionType;
    }

    public function collection()
    {
        // Get candidates based on filter
        $query = CandidateMaster::where('final_status', 'A')
            ->orderBy('candidate_code');

        if ($this->requisitionType && $this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }

        $candidates = $query->get();
        
        $result = collect();
        
        foreach ($candidates as $candidate) {
            $salary = SalaryProcessing::where('candidate_id', $candidate->id)
                ->where('month', $this->month)
                ->where('year', $this->year)
                ->first();
                
            $result->push((object)[
                'candidate' => $candidate,
                'salary' => $salary,
                'processed' => !is_null($salary)
            ]);
        }
        
        return $result;
    }

    public function headings(): array
    {
        return [
            'Party Code',
            'Party Name',
            'Requisition Type',
            'Monthly Salary',
            'Extra Amount',
            'Deduction Amount',
            'Net Pay',
            'Processed Date',
            'Status'
        ];
    }

    public function map($row): array
    {
        $candidate = $row->candidate;
        
        if ($row->processed && $row->salary) {
            // Already processed - use saved data
            $salary = $row->salary;
            return [
                $candidate->candidate_code ?? '',
                $candidate->candidate_name ?? '',
                $candidate->requisition_type ?? '',
                $salary->monthly_salary ?? 0,
                $salary->extra_amount ?? 0,
                $salary->deduction_amount ?? 0,
                $salary->net_pay ?? 0,
                $salary->processed_at ? $salary->processed_at->format('d-m-Y H:i') : '',
                'Processed'
            ];
        } else {
            // Not processed - calculate preview (same as UI)
            try {
                $calc = SalaryCalculator::calculate($candidate, $this->month, $this->year);
                
                return [
                    $candidate->candidate_code ?? '',
                    $candidate->candidate_name ?? '',
                    $candidate->requisition_type ?? '',
                    $calc['monthly_salary'] ?? 0,
                    $calc['extra_amount'] ?? 0,
                    $calc['deduction_amount'] ?? 0,
                    $calc['net_pay'] ?? 0,
                    '',
                    'Pending'
                ];
            } catch (\Exception $e) {
                // If calculation fails, show 0 values
                return [
                    $candidate->candidate_code ?? '',
                    $candidate->candidate_name ?? '',
                    $candidate->requisition_type ?? '',
                    0,
                    0,
                    0,
                    0,
                    '',
                    'Pending (Error: ' . substr($e->getMessage(), 0, 30) . ')'
                ];
            }
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            
            // Style for processed rows
            'I' => [
                'font' => ['bold' => true],
            ],
            
            // Style for pending rows (italic)
            'I' => function($cell) {
                if ($cell->getValue() == 'Pending') {
                    return ['font' => ['italic' => true, 'color' => ['rgb' => 'FF9900']]];
                }
                if (strpos($cell->getValue(), 'Pending') !== false) {
                    return ['font' => ['italic' => true, 'color' => ['rgb' => 'FF0000']]];
                }
                return ['font' => ['bold' => true, 'color' => ['rgb' => '007F00']]];
            },
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Get the highest row and column
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
                
                // Apply borders to all cells
                $event->sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Format currency cells with Indian Rupee symbol
                $currencyRange = 'D2:G' . $highestRow;
                $event->sheet->getStyle($currencyRange)
                    ->getNumberFormat()
                    ->setFormatCode('₹ #,##0.00');
                
                // Set column widths
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(18); // Employee Code
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(25); // Employee Name
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(15); // Requisition Type
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(15); // Monthly Salary
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(15); // Extra Amount
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(15); // Deduction Amount
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(15); // Net Pay
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(20); // Processed Date
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(15); // Status
                
                // Center align some columns
                $event->sheet->getStyle('C1:C' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                $event->sheet->getStyle('I1:I' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Right align currency columns
                $event->sheet->getStyle('D1:G' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                
                // Format date column
                $event->sheet->getStyle('H2:H' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('dd-mm-yyyy hh:mm');
                
                // Add filter to header row
                $event->sheet->getDelegate()->setAutoFilter('A1:' . $highestColumn . '1');
                
                // Freeze the header row
                $event->sheet->getDelegate()->freezePane('A2');
                
                // Auto-size columns based on content
                foreach (range('A', $highestColumn) as $column) {
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
                }
                
                // Add title row
                $title = "Remuneration Report - " . date('F', mktime(0, 0, 0, $this->month, 1)) . " {$this->year}";
                if ($this->requisitionType && $this->requisitionType !== 'All') {
                    $title .= " ({$this->requisitionType})";
                }
                
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);
                $event->sheet->getDelegate()->mergeCells('A1:I1');
                $event->sheet->setCellValue('A1', $title);
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
                ]);
                
                // Move header row down
                $event->sheet->getDelegate()->fromArray($this->headings(), null, 'A3', true);
                
                // Style the new header row
                $event->sheet->getStyle('A3:I3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ]);
                
                // Adjust borders for new layout
                $event->sheet->getStyle('A1:I' . ($highestRow + 2))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}