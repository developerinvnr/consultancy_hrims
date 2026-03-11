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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;

class RemunerationReportExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $month;
    protected $year;
    protected $departmentId;
    protected $requisitionType;

    public function __construct($month, $year, $departmentId, $requisitionType)
    {
        $this->month = $month;
        $this->year = $year;
        $this->departmentId = $departmentId;
        $this->requisitionType = $requisitionType;
    }

    public function collection()
    {
        $query = SalaryProcessing::with([
            'candidate.department',
            'candidate.subDepartmentRef',
            'candidate.vertical',
            'candidate.businessUnit',
            'candidate.zoneRef',
            'candidate.regionRef',
            'candidate.territoryRef'
        ])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->whereHas('candidate', function ($q) {
                $q->whereIn('final_status', ['A', 'D']);

                if (!empty($this->departmentId)) {
                    $q->where('department_id', $this->departmentId);
                }

                if (!empty($this->requisitionType)) {
                    $q->where('requisition_type', $this->requisitionType);
                }
            })
            ->join('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
            ->orderBy('candidate_master.candidate_code')
            ->select('salary_processings.*')
            ->get();

        $data = collect();
        $totalFinal = 0;

        foreach ($query as $index => $record) {

            $rpm = $record->candidate->remuneration_per_month ?? 0;
            $contractAmount = $record->candidate->contract_amount ?? 0;

            $paidDays = $record->paid_days ?? 0;
            $workingDays = $record->total_days ?? 0;
            $sunday = $record->approved_sundays ?? 0;
            $totalPaidDays = $paidDays + $sunday;

            $extra = $record->extra_amount ?? 0;
            $deduction = $record->deduction_amount ?? 0;

            // Final Payable from SalaryCalculator
            $final = $record->net_pay ?? 0;

            // TDS 2%
            $tds = $final > 0 ? round(($final / 98) * 2) : 0;

            // Gross Up
            $gross = round($final + $tds);

            $totalFinal += $final;

            $data->push([
                $index + 1,
                $record->candidate->candidate_code ?? '-',
                $record->candidate->candidate_name ?? '-',
                $record->candidate->businessUnit->business_unit_code ?? '-',
                $record->candidate->vertical->vertical_code ?? '-',
                $record->candidate->zoneRef->zone_code ?? '-',
                $record->candidate->regionRef->focus_code ?? '-',
                $record->candidate->territoryRef->territory_code ?? '-',
                $record->candidate->department->department_code ?? '-',
                $record->candidate->subDepartmentRef->focus_code ?? '-',
                $paidDays,
                $workingDays,
                $sunday,
                $totalPaidDays,
                round($rpm),
                round($contractAmount),
                round($extra),
                round($deduction),
                $final,
                ucfirst($record->payment_instruction ?? 'Pending'),
                $record->hr_hold_remark ?? '-',
                $tds,
                $gross,
            ]);
        }

        // Totals Row
        $totalTds = round(($totalFinal / 98) * 2);
        $totalGross = round($totalFinal + $totalTds);

        $data->push([
            '',
            '',
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $totalFinal,
            '',
            '',
            $totalTds,
            $totalGross,
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'S.N.',
            'Code',
            'Name of Employees',
            'Business Unit',
            'Vertical',
            'Zone',
            'Region',
            'Territory',
            'Department',
            'Sub Department',
            'Paid Days',
            'Working Days',
            'Sunday Working',
            'Total Paid Days',
            'As Per Approval',
            'As Per Contract',
            'Arrear',
            'Deduction',
            'Final Payable',
            'Payment Instruction',
            'HR Remarks',
            'TDS 2%',
            'Gross Up 102%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Borders
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Header center alignment
                $sheet->getStyle("A1:{$highestColumn}1")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Freeze header row
                $sheet->freezePane('A2');

                // Number formatting with commas
                $sheet->getStyle("O2:W{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            }
        ];
    }
}
