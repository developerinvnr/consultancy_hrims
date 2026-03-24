<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
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
    protected $requisitionType;
    protected $exportStatus;

    public function __construct($financialYear, $month, $status, $requisitionType, $exportStatus)
    {
        $this->financialYear = $financialYear;
        $this->month = (int) $month;
        $this->status = $status ?? 'All';
        $this->requisitionType = $requisitionType;
        $this->exportStatus = $exportStatus;

        [$startYear, $endYear] = explode('-', $financialYear);
        $this->year = ($this->month >= 4) ? $startYear : $endYear;
    }

    public function collection()
    {
        $query = SalaryProcessing::with('candidate')
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
            });

        if ($this->exportStatus === 'exported') {
            $query->whereIn('id', function ($sub) {

                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'tds_jv');
            });
        }

        if ($this->exportStatus === 'not_exported') {

            $query->whereNotIn('id', function ($sub) {

                $sub->select('reference_id')
                    ->from('report_exports')
                    ->where('reference_table', 'salary_processings')
                    ->where('report_type', 'tds_jv');
            });
        }

        $records = $query->get();
        // ✅ Generate batch number
        $batchNo = 'TDSJV' . time();

        // ✅ Save export history
        if ($this->exportStatus !== 'exported') {

            foreach ($records as $rec) {

                DB::table('report_exports')->updateOrInsert(

                    [
                        'reference_id'    => $rec->id,
                        'reference_table' => 'salary_processings',
                        'report_type'     => 'tds_jv',
                    ],

                    [
                        'batch_no'    => $batchNo,
                        'exported_by' => auth()->id(),
                        'exported_at' => now(),
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );
            }
        }


        return $records->map(function ($rec) {

            $tds = round($rec->net_pay * 0.02, 0);

            $narration = "TDS deducted on Rs. "
                . round($rec->net_pay, 0)
                . " @2%, Being Contractual Expenses for the Month of "
                . Carbon::create()->month($this->month)->format('F')
                . " {$this->year}";

            return [

                '',
                Carbon::now()->format('d-m-Y'),
                120,
                $narration,
                '',
                $rec->candidate->candidate_code,
                'STAT-DUES-TDS-15',
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
