<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SalaryPaymentExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles
{

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return collect($this->records);
    }

    /**
     * Excel Column Headers
     */
    public function headings(): array
    {
        return [

            'DocNo',
            'Date',
            'Time',
            'CashBankAC',
            'TDS JVNo',
            'Narration',
            'Cheque No',
            'Transactiontype Code',
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
            'Remarks',
            'TDS Bill Amount',
            'TDS',
            'BRSUser'

        ];
    }

    /**
     * Row Mapping
     */
    public function map($row): array
    {
        return [

            '',
            now()->format('d-m-Y'),
            '',
            'BANK-26',
            '',
            'Payment against salary for ' .
                date('F-y', strtotime($row->year . '-' . $row->month . '-01')),
            '',
            'NEFT',
            'All Activity',
            'N/A',
            'N/A',
            'All Crop',
            'N/A',
            '120',
            'N/A',
            'FIN',
            'Payment to Other Creditors for exp.',
            'N/A',
            'RAIPUR',
            '22',
            'BSF',
            'CM',
            'SUB_DEPT_FIN_124',
            '',
            $row->candidate_code,
            $row->net_pay,
            '',
            '',
            '',
            '',
            ''

        ];
    }

    /**
     * Excel Styling
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}1")
            ->getFont()
            ->setBold(true);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );

        return [];
    }
}
