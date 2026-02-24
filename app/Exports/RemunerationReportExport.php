<?php
// app/Exports/RemunerationReportExport.php

namespace App\Exports;

use App\Models\SalaryProcessing;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemunerationReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $month;
    protected $year;
    protected $departmentId;

    public function __construct($month, $year, $departmentId)
    {
        $this->month = $month;
        $this->year = $year;
        $this->departmentId = $departmentId;
    }

    public function collection()
    {
        $query = SalaryProcessing::where('month', $this->month)
            ->where('year', $this->year)
            ->with(['candidate' => function($q) {
                $q->with('department');
            }])
            ->join('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
            ->whereIn('candidate_master.final_status', ['A', 'D'])
            ->select('salary_processings.*', 'candidate_master.candidate_code', 
                     'candidate_master.candidate_name', 'candidate_master.department_id',
                     'candidate_master.sub_department');

        if (!empty($this->departmentId)) {
            $query->where('candidate_master.department_id', $this->departmentId);
        }

        return $query->orderBy('candidate_master.candidate_code')->get();
    }

    public function headings(): array
    {
        return [
            'S.N.',
            'Code',
            'Name of Employees',
            'Department',
            'Sub Department',
            'Paid days',
            'Working Days',
            'Sunday working',
            'Total Paid Days',
            'Current month',
            'Previous month',
            'Arear',
            'Deduction',
            'Based on paid days',
            'Payment Instruction',
            'HR Remarks',
            'TDS 2%',
            'Gross up 102%',
        ];
    }

    public function map($record): array
    {
        static $serial = 0;
        $serial++;

        return [
            $serial,
            $record->candidate->candidate_code ?? 'N/A',
            $record->candidate->candidate_name ?? 'N/A',
            $record->candidate->department->department_name ?? 'N/A',
            $record->candidate->sub_department ?? 'N/A',
            $record->paid_days ?? 0,
            $record->working_days ?? 0,
            $record->sunday_working ?? 0,
            ($record->paid_days ?? 0) + ($record->sunday_working ?? 0),
            number_format($record->net_pay ?? 0, 2),
            number_format($record->previous_month_pay ?? 0, 2),
            number_format($record->arear_amount ?? 0, 2),
            number_format($record->deduction_amount ?? 0, 2),
            number_format($record->based_on_paid_days ?? 0, 2),
            $record->payment_instruction == 'hold' ? 'Hold' : 'Release',
            $record->hr_remarks ?? 'N/A',
            number_format($record->tds_amount ?? 0, 2),
            number_format($record->gross_up_amount ?? 0, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}