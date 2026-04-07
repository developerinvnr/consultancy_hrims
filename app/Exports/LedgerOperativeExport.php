<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class LedgerOperativeExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records->map(function ($rec) {

            return [

                'Name'=>$rec->candidate_name,
                'Code'=>$rec->candidate_code,
                'Account Type'=>'Vendor',
                'Group'=>'FALSE',
                'Parent Code'=>$rec->requisition_type,
                'Parent Name'=>$rec->requisition_type,
                'Business Entity'=>'120',
                'Crop Vertical'=>optional($rec->vertical)->vertical_code,
                'Region'=>optional($rec->regionRef)->focus_code,
                'Address'=>$rec->address_line_1,
                'City'=>$rec->cityMaster->city_village_name ?? '-',
                'Pin'=>$rec->pin_code,
                'Email'=>$rec->candidate_email,
                'Tel No'=>$rec->mobile_no,
                'Bank Account Name'=>$rec->account_holder_name.' '.$rec->candidate_code,
                'Bank Account No'=>$rec->bank_account_no,
                'IFSC'=>$rec->bank_ifsc,
                'Mobile'=>$rec->mobile_no,
                'City Name'=>$rec->cityMaster->city_village_name ?? '-',
                'MSME No'=>'N/A',
                'MSME'=>'NO',
                'State'=>optional($rec->workState)->state_name,
                'Country'=>'IND',
                'Business Unit'=>optional($rec->businessUnit)->business_unit_code,
                'PAN'=>$rec->pan_no,
                'Department'=>optional($rec->department)->department_code,
                'Designation'=>$rec->requisition_type,
                'Grade'=>$rec->requisition_type,
                'Reporting To'=>optional($rec->reportingManager)->emp_name,
                'AADHAR'=>$rec->aadhaar_no,
                'Function'=>optional($rec->function)->function_code,
                'Sub Department'=>optional($rec->subDepartmentRef)->focus_code,
                'Zone'=>optional($rec->zoneRef)->zone_code,
                'DOJ'=>optional($rec->contract_start_date)?->format('d/m/Y'),
                'Reporting Designation'=>optional($rec->reportingManager)->emp_designation,
                'Reporting Email'=>optional($rec->reportingManager)->emp_email,
                'Reporting Contact'=>optional($rec->reportingManager)->emp_contact,
                'Emp Bank Acc No'=>$rec->bank_account_no,
                'Emp IFSC'=>$rec->bank_ifsc,
                'Emp Bank Name'=>$rec->account_holder_name.' '.$rec->candidate_code,
                'Location/HQ'=>$rec->work_location_hq,
                'Crop'=>'All Crop',
                'Transaction Type'=>'NEFT',

            ];
        });
    }

    public function headings(): array
    {
        return array_keys($this->collection()->first());
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Freeze header row
                $sheet->freezePane('A2');

                // Header background color
                $sheet->getStyle("A1:{$highestColumn}1")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('D9E1F2');

                // Add borders
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Align center
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setVertical('center');
            }

        ];
    }
}