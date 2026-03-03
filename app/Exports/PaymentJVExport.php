<?php

namespace App\Exports;

use App\Models\SalaryProcessing;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PaymentJVExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $financialYear;
    protected $month;
    protected $status;
    protected $year;
    protected $requisitionType;

    public function __construct($financialYear, $month, $status, $requisitionType)
    {
        $this->financialYear = $financialYear;
        $this->month = (int) $month;
        $this->status = $status ?? 'All';
        $this->requisitionType = $requisitionType;

        [$startYear, $endYear] = explode('-', $financialYear);
        $this->year = ($this->month >= 4) ? $startYear : $endYear;
    }

    public function collection()
    {
        $records = SalaryProcessing::with([
            'candidate.department',
            'candidate.workState',
            'candidate.function',
            'candidate.vertical',
            'candidate.subDepartmentRef',
            'candidate.zoneRef'
        ])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->whereHas('candidate', function ($q) {
                if ($this->status !== 'All') {
                    $q->where('final_status', $this->status);
                } else {
                    $q->whereIn('final_status', ['A', 'D']);
                }

                if (!empty($this->requisitionType)) {
                    $q->where('requisition_type', $this->requisitionType);
                }
            })
            ->get();

        return $records->map(function ($rec) {

            $tds = round($rec->net_pay * 0.02, 0);
            $paymentAmount = round($rec->net_pay - $tds, 0);

            return [
                '',
                Carbon::now()->format('d-m-Y'),
                '',
                'BANK-26',
                '',
                'Payment against expenses for '
                    . Carbon::create()->month($this->month)->format('M-y'),
                '',
                'NEFT',
                'All Activity',
                'N/A',
                'N/A',
                'All Crop',
                'N/A',
                120,
                'N/A',
                $rec->candidate->department->department_name ?? '',
                'Payment to Other Creditors for exp.',
                'N/A',
                strtoupper($rec->candidate->city ?? ''),
                $rec->candidate->workState->state_name ?? '',
                $rec->candidate->function->function_name ?? '',
                $rec->candidate->vertical->vertical_name ?? '',
                $rec->candidate->subDepartmentRef->sub_department_name ?? '',
                $rec->candidate->zoneRef->zone_name ?? '',
                $rec->candidate->candidate_code,
                $paymentAmount,
                '',
                '',
                round($rec->net_pay, 0),
                $tds,
                ''
            ];
        });
    }

    public function headings(): array
    {
        return [
            'DocNo',
            'Date',
            'Time',
            'CashBankAC',
            'TDSJVNo',
            'sNarration',
            'sChequeNo',
            'TransactiontypeCode',
            'Activity',
            'Category',
            'Region',
            'Crop',
            'Farm',
            'Business Entity',
            'Cost Center',
            'Department',
            'PMT Category',
            'Business Unit',
            'Location',
            'State',
            'Function',
            'FC-Vertical',
            'Sub Department',
            'Zone',
            'Account',
            'Amount',
            'Reference',
            'sRemarks',
            'TDSBillAmount',
            'TDS',
            'sBRSUser'
        ];
    }

    /* ================= STYLING ================= */

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Apply border to all cells
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Freeze header
                $sheet->freezePane('A2');

                // Enable Auto Filter
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");

                // Right align Amount columns
                $sheet->getStyle("Z2:AA{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Format numbers
                $sheet->getStyle("Z2:AA{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        ];
    }
}
