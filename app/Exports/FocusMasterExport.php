<?php

namespace App\Exports;

use App\Models\CandidateMaster;
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

class FocusMasterExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $departmentId;
    protected $search;

    public function __construct($departmentId, $search)
    {
        $this->departmentId = $departmentId;
        $this->search = $search;
    }

    public function collection()
    {
        $query = CandidateMaster::with([
            'department',
            'subDepartmentRef',
            'vertical',
            'businessUnit',
            'zoneRef',
            'regionRef',
            'workState',
            'reportingManager',
            'workLocation' 
        ])
        ->whereIn('final_status', ['A','D']);

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('candidate_name', 'like', "%{$this->search}%")
                  ->orWhere('candidate_code', 'like', "%{$this->search}%")
                  ->orWhere('pan_no', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('candidate_code')->get()->map(function ($rec) {

            return [

                $rec->candidate_name,
                $rec->candidate_code,
                'Vendor',
                'FALSE',
                $rec->requisition_type ?? '-',
                $rec->requisition_type ?? '-',
                120,
                $rec->vertical->vertical_code ?? '-',
                $rec->regionRef->focus_code ?? '-',
                $rec->formatted_address,
                $rec->city ?? '-',
                $rec->pin_code ?? '-',
                $rec->candidate_email ?? '-',
                $rec->mobile_no ?? '-',
                $rec->account_holder_name . ' ' . $rec->candidate_code,
                $rec->bank_account_no ?? '-',
                $rec->bank_ifsc ?? '-',
                $rec->mobile_no ?? '-',
                $rec->city ?? '-',
                'N/A',
                'NO',
                $rec->workState->state_name ?? '-',
                'IND',
                $rec->businessUnit->business_unit_code ?? '-',
                $rec->pan_no ?? '-',
                $rec->department->department_code ?? '-',
                $rec->requisition_type ?? '-',
                $rec->requisition_type ?? '-',

                // ✅ Reporting To (IMPORTANT FIX)
                $rec->reporting_to ?? '-',

                $rec->aadhaar_no ?? '-',
                $rec->function->function_code ?? '-',
                $rec->subDepartmentRef->focus_code ?? '-',
                $rec->zoneRef->zone_code ?? '-',
                optional($rec->contract_start_date)->format('d/m/Y'),

                // Reporting details from relation
                $rec->reportingManager?->emp_designation ?? '-',
                $rec->reportingManager?->emp_email ?? '-',
                $rec->reportingManager?->emp_contact ?? '-',

                $rec->bank_account_no ?? '-',
                $rec->bank_ifsc ?? '-',
                $rec->account_holder_name . ' ' . $rec->candidate_code,
                $rec->workLocation->focus_code ?? '-',
                'All Crop',
                'NEFT',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name','Code','Account Type','Group',
            'Parent Code','Parent Name','Business Entity',
            'Crop Vertical','Region','Address','City','Pin',
            'Email','Tel No','Bank Account Name',
            'Bank Account No','IFSC','Mobile',
            'City Name','MSME No','MSME','State',
            'Country','Business Unit','PAN','Department',
            'Designation','Grade','Reporting To',
            'AADHAR','Function','Sub Department',
            'Zone','DOJ','Reporting Designation',
            'Reporting Email','Reporting Contact',
            'Emp Bank Acc No','Emp IFSC','Emp Bank Name',
            'Location/HQ','Crop','Transaction Type'
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
            AfterSheet::class => function ($event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getBorders()
                      ->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A2');
            }
        ];
    }
}