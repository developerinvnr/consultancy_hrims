<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutBatchExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection()
    {
        return DB::table('salary_processings as sp')
            ->join('candidate_master as cm','cm.id','=','sp.candidate_id')
            ->where('sp.batch_id',$this->batchId)
            ->select(
                'cm.candidate_code',
                'cm.candidate_name',
                'cm.bank_account_no',
                'cm.bank_ifsc',
                'cm.bank_name',
                'sp.net_pay'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Bank Account',
            'IFSC',
            'Bank Name',
            'Amount'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $sheet->getStyle('A1:F1000')->getBorders()->getAllBorders()->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

        return [];
    }
}