<?php
// app/Exports/MasterReportExport.php

namespace App\Exports;

use App\Models\CandidateMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MasterReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $month;
    protected $year;
    protected $requisitionType;
    protected $workLocation;
    protected $departmentId;
    protected $search;

    public function __construct($month, $year, $requisitionType, $workLocation, $departmentId, $search)
    {
        $this->month = $month;
        $this->year = $year;
        $this->requisitionType = $requisitionType;
        $this->workLocation = $workLocation;
        $this->departmentId = $departmentId;
        $this->search = $search;
    }

    public function collection()
    {
        $selectedDate = Carbon::createFromDate($this->year, $this->month, 1);
        $monthStart = $selectedDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $selectedDate->copy()->endOfMonth()->toDateString();

        $query = CandidateMaster::whereIn('final_status', ['A', 'D'])
            ->whereDate('contract_start_date', '<=', $monthEnd)
            ->whereDate('contract_end_date', '>=', $monthStart)
            ->with(['salaryProcessings' => function ($q) {
                $q->where('month', $this->month)->where('year', $this->year);
            }])
            ->with('department');

        // Apply filters
        if ($this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }

        if (!empty($this->workLocation)) {
            $query->where('work_location_hq', $this->workLocation);
        }

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('candidate_code', 'like', "%{$this->search}%")
                    ->orWhere('candidate_name', 'like', "%{$this->search}%")
                    ->orWhere('mobile_no', 'like', "%{$this->search}%")
                    ->orWhere('pan_no', 'like', "%{$this->search}%")
                    ->orWhere('aadhaar_no', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('candidate_code')->get();
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Name',
            'Agreement ID',
            'Code',
            'Function',
            'Department',
            'Sub-Dept',
            'Crop Vertical',
            'Region',
            'Business Unit',
            'Location/HQ',
            'City',
            'State Name',
            'Address',
            'Pin',
            'E Mail',
            'Tel No',
            'Bank Account Name',
            'Bank Account Number',
            'IFSC Code',
            'Pan No',
            'Emp Designation',
            'Emp Grade',
            'Emp Reporting To',
            'RM Email',
            'Aadhaar No',
            'DOJ',
            'DOS',
            'Active/Deactive',
            'Remuneration',
            'Remarks',
            'Contract generate date',
            'Contract dispatch date',
            'Signed Contract Upload date',
            'Signed Contract dispatch date',
        ];
    }

    public function map($candidate): array
    {
        static $serial = 0;
        $serial++;
        
        $salary = $candidate->salaryProcessings->first();
        
        return [
            $serial,
            $candidate->candidate_name,
            $candidate->agreement_id ?? 'N/A',
            $candidate->candidate_code,
            $candidate->function ?? 'N/A',
            $candidate->department->department_name ?? 'N/A',
            $candidate->sub_department ?? 'N/A',
            $candidate->crop_vertical ?? 'N/A',
            $candidate->region ?? 'N/A',
            $candidate->business_unit ?? 'N/A',
            $candidate->work_location_hq ?? 'N/A',
            $candidate->city ?? 'N/A',
            $candidate->state_name ?? 'N/A',
            $candidate->address ?? 'N/A',
            $candidate->pin_code ?? 'N/A',
            $candidate->email ?? 'N/A',
            $candidate->mobile_no ?? 'N/A',
            $candidate->bank_account_name ?? $candidate->candidate_name,
            $candidate->bank_account_no ?? 'N/A',
            $candidate->ifsc_code ?? 'N/A',
            $candidate->pan_no ?? 'N/A',
            $candidate->designation ?? 'N/A',
            $candidate->grade ?? 'N/A',
            $candidate->reporting_to ?? 'N/A',
            $candidate->rm_email ?? 'N/A',
            $candidate->aadhaar_no ?? 'N/A',
            $candidate->date_of_joining ? Carbon::parse($candidate->date_of_joining)->format('d-M-Y') : 'N/A',
            $candidate->contract_end_date ? Carbon::parse($candidate->contract_end_date)->format('d-M-Y') : 'N/A',
            $candidate->final_status == 'A' ? 'Active' : ($candidate->final_status == 'D' ? 'Deactive' : $candidate->final_status),
            $salary ? number_format($salary->net_pay, 2) : 'N/A',
            $candidate->remarks ?? 'N/A',
            $candidate->contract_generate_date ? Carbon::parse($candidate->contract_generate_date)->format('d-M-Y') : 'N/A',
            $candidate->contract_dispatch_date ? Carbon::parse($candidate->contract_dispatch_date)->format('d-M-Y') : 'N/A',
            $candidate->signed_contract_upload_date ? Carbon::parse($candidate->signed_contract_upload_date)->format('d-M-Y') : 'N/A',
            $candidate->signed_contract_dispatch_date ? Carbon::parse($candidate->signed_contract_dispatch_date)->format('d-M-Y') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}