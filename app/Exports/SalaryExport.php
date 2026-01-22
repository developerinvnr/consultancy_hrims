<?php

namespace App\Exports;

use App\Models\SalaryProcessing;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = (int) $month;
        $this->year  = (int) $year;
    }

    public function collection()
    {
        return SalaryProcessing::with('candidate')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Emp Code',
            'Employee Name',
            'Bank Account Number',
            'IFSC Code',
            'Net Payable (₹)',
            'Month-Year',
        ];
    }

    public function map($salary): array
    {
        return [
            $salary->candidate->candidate_code ?? 'N/A',
            $salary->candidate->candidate_name ?? 'N/A',
            $salary->candidate->bank_account_no ?? '-',
            strtoupper($salary->candidate->bank_ifsc ?? '-'),
            number_format($salary->net_pay, 2, '.', ''),
            date('M Y', strtotime("{$salary->year}-{$salary->month}-01")),
        ];
    }
}