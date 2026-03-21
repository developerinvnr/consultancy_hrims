<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerExport implements FromCollection, WithHeadings, WithEvents
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records->map(function ($c) {

            return [

                $c->candidate_name,
                $c->candidate_code,
                'Vendor',
                'FALSE',
                $c->requisition_type ?? '-',
                $c->requisition_type ?? '-',
                '120',
                $c->vertical->vertical_code ?? 'NA',
                $c->regionRef->focus_code ?? 'NA',
                $c->address_line_1 ?? '-',
                $c->cityMaster->city_village_name ?? '-',
                $c->pin_code ?? '-',
                $c->candidate_email ?? '-',
                $c->mobile_no ?? '-',
                $c->account_holder_name.' '.$c->candidate_code,
                $c->bank_account_no ?? '-',
                $c->bank_ifsc ?? '-',
                $c->mobile_no ?? '-',
                $c->cityMaster->city_village_name ?? '-',
                'N/A',
                'NO',
                $c->workState->state_code ?? '-',
                'IND',
                $c->businessUnit->business_unit_code ?? 'NA',
                $c->pan_no ?? '-',
                $c->department->department_code ?? '-',
                $c->requisition_type ?? '-',
                $c->requisition_type ?? '-',
                $c->reportingManager->emp_name ?? '-',
                $c->aadhaar_no ?? '-',
                $c->function->function_name ?? 'NA',
                $c->subDepartmentRef->focus_code ?? 'NA',
                $c->zoneRef->zone_code ?? 'NA',
                optional($c->contract_start_date)->format('d/m/Y'),
                $c->reportingManager->emp_designation ?? '-',
                $c->reportingManager->emp_email ?? '',
                $c->reportingManager->emp_contact ?? '-',
                $c->bank_account_no ?? '-',
                $c->bank_ifsc ?? '-',
                $c->account_holder_name.' '.$c->candidate_code,
                $c->work_location_hq ?? '-',
                'All Crop',
                'NEFT',

            ];
        });
    }

    public function headings(): array
    {
        return [

            'Name',
            'Code',
            'Account Type',
            'Group',
            'Parent Code',
            'Parent Name',
            'Business Entity',
            'Crop Vertical',
            'Region',
            'Address',
            'City',
            'Pin',
            'Email',
            'Tel No',
            'Bank Account Name',
            'Bank Account No',
            'IFSC',
            'Mobile',
            'City Name',
            'MSME No',
            'MSME',
            'State',
            'Country',
            'Business Unit',
            'PAN',
            'Department',
            'Designation',
            'Grade',
            'Reporting To',
            'AADHAR',
            'Function',
            'Sub Department',
            'Zone',
            'DOJ',
            'Reporting Designation',
            'Reporting Email',
            'Reporting Contact',
            'Emp Bank Acc No',
            'Emp IFSC',
            'Emp Bank Name',
            'Location/HQ',
            'Crop',
            'Transaction Type'

        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $range = "A1:{$highestColumn}{$highestRow}";

                // Borders
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Header bold
                $sheet->getStyle("A1:{$highestColumn}1")
                    ->getFont()
                    ->setBold(true);

                // Header background
                $sheet->getStyle("A1:{$highestColumn}1")
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE9ECEF'],
                        ],
                    ]);

                // Auto width FIXED VERSION
                foreach ($sheet->getColumnIterator() as $column) {
                    $sheet->getColumnDimension($column->getColumnIndex())
                        ->setAutoSize(true);
                }
            },

        ];
    }
}