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
            'candidate.businessUnit',
            'candidate.workState',
            'candidate.function',
            'candidate.vertical',
            'candidate.subDepartmentRef',
            'candidate.zoneRef',
            'candidate.regionRef'
        ])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->where('status', 'processed')
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

            // ✅ PRIORITIZE total_payable, fallback to net_pay + arrear_amount
            $finalPayable = $rec->total_payable ?? ($rec->net_pay + ($rec->arrear_amount ?? 0));
            
            // TDS @ 2% (calculated on gross, where finalPayable is 98% of gross)
            $tds = $finalPayable > 0 ? ($finalPayable / 98) * 2 : 0;
            
            // Gross Up Amount (Total amount including TDS)
            $grossUp = $finalPayable + $tds;
            
            // Payment Amount = finalPayable (what actually gets paid)
            $paymentAmount = $finalPayable;

            return [
                '',                                                          // DocNo
                Carbon::now()->format('d-m-Y'),                             // Date
                '',                                                          // Time
                'BANK-26',                                                   // CashBankAC
                '',                                                          // TDSJVNo
                'Payment against expenses for '
                    . Carbon::create()->month($this->month)->format('M-y'), // sNarration
                '',                                                          // sChequeNo
                'NEFT',                                                      // TransactiontypeCode
                'All Activity',                                              // Activity
                'N/A',                                                       // Category
                $rec->candidate->regionRef->focus_code ?? 'N/A',            // Region
                'All Crop',                                                  // Crop
                'N/A',                                                       // Farm
                120,                                                         // Business Entity
                'N/A',                                                       // Cost Center
                $rec->candidate->department->department_name ?? '',         // Department
                'Payment to Other Creditors for exp.',                      // PMT Category
                $rec->candidate->businessUnit->business_unit_code ?? '',    // Business Unit
                strtoupper($rec->candidate->city ?? ''),                    // Location
                $rec->candidate->workState->state_name ?? '',               // State
                $rec->candidate->function->function_name ?? '',             // Function
                $rec->candidate->vertical->vertical_name ?? '',             // FC-Vertical
                $rec->candidate->subDepartmentRef->sub_department_name ?? '', // Sub Department
                $rec->candidate->zoneRef->zone_name ?? '',                  // Zone
                $rec->candidate->candidate_code,                            // Account
                round($paymentAmount, 0),                                   // Amount (Payment)
                '',                                                          // Reference
                '',                                                          // sRemarks
                round($grossUp, 0),                                         // TDSBillAmount (Gross Amount)
                round($tds, 0),                                             // TDS
                ''                                                           // sBRSUser
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
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

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");

                // Right align Amount, TDSBillAmount, TDS columns
                $sheet->getStyle("Z2:AB{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("Z2:AB{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        ];
    }
}