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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class TDSJVExport implements
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

    public function __construct($financialYear, $month, $status)
    {
        $this->financialYear = $financialYear;
        $this->month = (int) $month;
        $this->status = $status ?? 'All';

        [$startYear, $endYear] = explode('-', $financialYear);
        $this->year = ($this->month >= 4) ? $startYear : $endYear;
    }

    public function collection()
    {
        $records = SalaryProcessing::with('candidate')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->whereHas('candidate', function ($q) {
                if ($this->status !== 'All') {
                    $q->where('final_status', $this->status);
                } else {
                    $q->whereIn('final_status', ['A','D']);
                }
            })
            ->get();

        return $records->map(function ($rec) {

            $tds = round($rec->net_pay * 0.02, 0);

            $narration = "TDS deducted on Rs. "
                . round($rec->net_pay,0)
                . " @2%, Being Contractual Expenses for the Month of "
                . Carbon::create()->month($this->month)->format('F')
                . " {$this->year}";

            return [
                '', // DocNo
                Carbon::now()->format('d-m-Y'),
                120,
                $narration,
                '', // TDSVoucherNo
                $rec->candidate->candidate_code, // DrAccount
                'STAT-DUES-TDS-15', // CrAccount
                $tds,
                '' // Reference
            ];
        });
    }

    public function headings(): array
    {
        return [
            'DocNo',
            'Date',
            'Business Entity',
            'sNarration',
            'TDSVoucherNo',
            'DrAccount',
            'CrAccount',
            'Amount',
            'Reference'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle("A1:I" . $sheet->getHighestRow())
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A2');
            }
        ];
    }
}