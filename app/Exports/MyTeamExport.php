<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MyTeamExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $candidates;
    protected $serialNumber = 1;

    public function __construct($candidates)
    {
        $this->candidates = $candidates;
    }

    public function collection()
    {
        return $this->candidates;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Candidate Code',
            'Candidate Name',
            'Type',
            'Email',
            'Mobile',
            'Work Location',
            'Joining Date',
            'Monthly Salary',
            'Status',
        ];
    }

    public function map($candidate): array
    {
        // Format salary
        $salary = $candidate->remuneration_per_month ? 
            '₹ ' . number_format($candidate->remuneration_per_month, 2) : 'N/A';
        
        // Format joining date
        $joiningDate = $candidate->date_of_joining ? 
            Carbon::parse($candidate->date_of_joining)->format('d-m-Y') : 'N/A';
        
        // Status display
        $status = $candidate->final_status == 'A' ? 'Active' : 'Inactive';
        
        return [
            $this->serialNumber++,
            $candidate->candidate_code ?? 'N/A',
            $candidate->candidate_name ?? 'N/A',
            $candidate->requisition_type ?? 'N/A',
            $candidate->candidate_email ?? 'N/A',
            $candidate->mobile_no ?? 'N/A',
            $candidate->work_location_hq ?? 'N/A',
            $joiningDate,
            $salary,
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $totalRows = $this->candidates->count() + 1; // +1 for header
        
        // Make header row bold with background color
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        
        // Add filter to header row
        $sheet->setAutoFilter('A1:J1');
        
        // Freeze the first row
        $sheet->freezePane('A2');
        
        // Add borders to all cells
        $sheet->getStyle('A1:J' . $totalRows)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Set alignment
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal('center'); // S.No
        $sheet->getStyle('D:D')->getAlignment()->setHorizontal('center'); // Type
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal('center'); // Joining Date
        $sheet->getStyle('I:I')->getAlignment()->setHorizontal('right');  // Salary (right align for currency)
        $sheet->getStyle('J:J')->getAlignment()->setHorizontal('center'); // Status
        
        // Format salary column with currency
        $sheet->getStyle('I2:I' . $totalRows)
            ->getNumberFormat()
            ->setFormatCode('"₹" #,##0.00');
        
        // Auto-size all columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Add header background color
        return [
            1 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => '4F81BD']
                ],
                'font' => [
                    'color' => ['argb' => 'FFFFFF']
                ]
            ],
        ];
    }
}