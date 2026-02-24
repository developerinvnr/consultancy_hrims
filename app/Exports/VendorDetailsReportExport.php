<?php
// app/Exports/VendorDetailsReportExport.php

namespace App\Exports;

use App\Models\CandidateMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorDetailsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $requisitionType;
    protected $departmentId;
    protected $workLocation;

    public function __construct($requisitionType, $departmentId, $workLocation)
    {
        $this->requisitionType = $requisitionType;
        $this->departmentId = $departmentId;
        $this->workLocation = $workLocation;
    }

    public function collection()
    {
        $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->with('department');

        if ($this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        if (!empty($this->workLocation)) {
            $query->where('work_location_hq', $this->workLocation);
        }

        return $query->orderBy('candidate_code')->get();
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
            'E Mail',
            'Tel No',
            'Bank Account Name',
            'Bank Account Number',
            'IFSC Code',
            'Mobile',
            'City Name',
            'MSME Numb',
            'MSME',
            'State Name',
            'Country',
            'Business Unit',
            'Pan No',
            'Department',
            'Emp Designation',
            'Emp Grade',
            'Emp Reporting To',
            'AADHAR No',
            'Function Name',
            'Sub Department',
            'Zone Name',
            'DOJ',
            'Reporting Designation',
            'Reporting Email',
            'Reporting Contact No',
            'Emp Bank Acc No',
            'Emp Bank IFSC Code',
            'Emp Bank Name',
            'Location/HQ',
            'Crop',
            'Transaction Type',
        ];
    }

    public function map($candidate): array
    {
        return [
            $candidate->candidate_name,
            $candidate->candidate_code,
            'Vendor',
            'FALSE',
            $candidate->requisition_type == 'TFA' ? 'TEMPORARY FIELD ASSISTANT' : ($candidate->requisition_type == 'CB' ? 'Counter Boy' : 'CONTRACTUAL STAFF'),
            $candidate->requisition_type == 'TFA' ? 'Temporary Field Assistant' : ($candidate->requisition_type == 'CB' ? 'Counter Boy' : 'Contractual Staff'),
            '120',
            $candidate->crop_vertical ?? 'FC',
            $candidate->region ?? 'N/A',
            $candidate->address ?? 'N/A',
            $candidate->city ?? 'N/A',
            $candidate->pin_code ?? 'N/A',
            $candidate->email ?? 'N/A',
            $candidate->mobile_no ?? 'N/A',
            $candidate->bank_account_name ?? $candidate->candidate_name,
            $candidate->bank_account_no ?? 'N/A',
            $candidate->ifsc_code ?? 'N/A',
            $candidate->mobile_no ?? 'N/A',
            $candidate->city ?? 'N/A',
            'N/A',
            'NO',
            $candidate->state_name ?? 'N/A',
            'IND',
            'BU501FC',
            $candidate->pan_no ?? 'N/A',
            $candidate->department->department_name ?? 'N/A',
            $candidate->designation ?? 'N/A',
            $candidate->grade ?? 'N/A',
            $candidate->reporting_to ?? 'N/A',
            $candidate->aadhaar_no ?? 'N/A',
            $candidate->function_name ?? 'N/A',
            $candidate->sub_department ?? 'N/A',
            $candidate->zone_name ?? 'N/A',
            $candidate->date_of_joining ? date('d/m/Y', strtotime($candidate->date_of_joining)) : 'N/A',
            $candidate->reporting_designation ?? 'N/A',
            $candidate->rm_email ?? 'N/A',
            $candidate->reporting_contact_no ?? 'N/A',
            $candidate->bank_account_no ?? 'N/A',
            $candidate->ifsc_code ?? 'N/A',
            $candidate->bank_name ?? 'N/A',
            $candidate->work_location_hq ?? 'N/A',
            'All Crop',
            'NEFT',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}