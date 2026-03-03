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

class JVExport implements
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


        // 🔥 Calculate Year from Financial Year
        [$startYear, $endYear] = explode('-', $financialYear);
        $this->year = ($this->month >= 4) ? $startYear : $endYear;
    }

    public function collection()
    {
        $records = SalaryProcessing::with([
            'candidate.department',
            'candidate.businessUnit',
            'candidate.vertical',
            'candidate.subDepartmentRef',
            'candidate.zoneRef',
            'candidate.regionRef',
            'candidate.function',
            'candidate.workState'
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
            ->orderBy('id')
            ->get();

        $narration = "Being Contractual Expenses for the Month of "
            . Carbon::create()->month($this->month)->format('F')
            . " {$this->year}";

        $billNo = Carbon::create()->month($this->month)->format('M-y');

        $billDate = Carbon::create($this->year, $this->month, 1)
            ->endOfMonth()
            ->format('d-m-Y');

        return $records->map(function ($rec) use ($narration, $billNo, $billDate) {

            return [
                '', // DocNo
                Carbon::now()->format('d-m-Y'),
                120,
                $narration,
                '', // TDSJVNo
                '', // ReverseCharge_Yn_
                $billNo,
                $billDate,
                $rec->candidate->department->department_name ?? '',
                'N/A', // Cost Center
                $rec->candidate->businessUnit->business_unit_name ?? '',
                'All Activity',
                $rec->candidate->city ?? '',
                $rec->candidate->workState->state_name ?? '',
                'N/A',
                'All Crop',
                $rec->candidate->regionRef->region_name ?? 'N/A',
                $rec->candidate->function->function_name ?? '',
                $rec->candidate->vertical->vertical_name ?? '',
                $rec->candidate->subDepartmentRef->sub_department_name ?? '',
                $rec->candidate->zoneRef->zone_name ?? '',
                'INDIRECT-MSC-17', // DrAccount
                $rec->candidate->candidate_code,
                round($rec->net_pay, 0),
                '', // TDS
                '', // TDSPer
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
            'TDSJVNo',
            'ReverseCharge_Yn_',
            'BillNo',
            'BillDate',
            'Department',
            'Cost Center',
            'Business Unit',
            'Activity',
            'Location',
            'State',
            'Category',
            'Crop',
            'Region',
            'Function',
            'FC-Vertical',
            'Sub Department',
            'Zone',
            'DrAccount',
            'CrAccount',
            'Amount',
            'TDS',
            'TDSPer'
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

                $sheet->getStyle("A1:Z" . $sheet->getHighestRow())
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A2');
            }
        ];
    }
}
