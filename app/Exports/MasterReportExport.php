<?php
// app/Exports/MasterReportExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use App\Models\CandidateMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class MasterReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    protected $financialYear;
    protected $status;
    protected $requisitionType;
    protected $workLocation;
    protected $departmentId;
    protected $search;

    public function __construct($financialYear, $month, $status, $requisitionType, $workLocation, $departmentId, $search)
    {
        $this->financialYear = $financialYear;
        $this->month = $month;
        $this->status = $status;
        $this->requisitionType = $requisitionType;
        $this->workLocation = $workLocation;
        $this->departmentId = $departmentId;
        $this->search = $search;
    }

    public function collection()
    {
        $query = CandidateMaster::query();

        // Financial Year Filter
        if (!empty($this->financialYear)) {
            [$startYear, $endYear] = explode('-', $this->financialYear);

            if (!empty($this->month)) {

                $year = ($this->month >= 4) ? $startYear : $endYear;

                $startDate = "{$year}-{$this->month}-01";

                $endDate = Carbon::parse($startDate)->endOfMonth();

                $query->whereBetween('contract_start_date', [$startDate, $endDate]);
            } else {

                $startDate = $startYear . '-04-01';
                $endDate   = $endYear . '-03-31';

                $query->whereBetween('contract_start_date', [$startDate, $endDate]);
            }
        }

        // Status filter
        if ($this->status !== 'All') {
            $query->where('final_status', $this->status);
        } else {
            $query->whereIn('final_status', ['A', 'D']);
        }

        // Requisition type
        if ($this->requisitionType !== 'All') {
            $query->where('requisition_type', $this->requisitionType);
        }

        // Work location
        if (!empty($this->workLocation)) {
            $query->where('work_location_hq', $this->workLocation);
        }

        // Department
        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        // Search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('candidate_code', 'like', "%{$this->search}%")
                    ->orWhere('candidate_name', 'like', "%{$this->search}%")
                    ->orWhere('mobile_no', 'like', "%{$this->search}%")
                    ->orWhere('pan_no', 'like', "%{$this->search}%")
                    ->orWhere('aadhaar_no', 'like', "%{$this->search}%");
            });
        }

        return $query->with([
            'department',
            'function',
            'subDepartmentRef',
            'vertical',
            'regionRef',
            'businessUnit',
            'cityMaster',
            'workState',
            'reportingManager'
        ])
            ->orderBy('candidate_code')
            ->get();
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

        return [
            $serial,
            $candidate->candidate_name ?? 'N/A',
            $candidate->agreement_id ?? 'N/A',
            $candidate->candidate_code ?? 'N/A',

            optional($candidate->function)->function_name ?? 'N/A',
            optional($candidate->department)->department_name ?? 'N/A',
            optional($candidate->subDepartmentRef)->sub_department_name ?? 'N/A',
            optional($candidate->vertical)->vertical_name ?? 'N/A',
            optional($candidate->regionRef)->region_name ?? 'N/A',
            optional($candidate->businessUnit)->business_unit_name ?? 'N/A',

            $candidate->work_location_hq ?? 'N/A',
            optional($candidate->cityMaster)->city_village_name ?? 'N/A',
            optional($candidate->workState)->state_name ?? 'N/A',

            $candidate->address_line_1 ?? 'N/A',
            $candidate->pin_code ?? 'N/A',
            $candidate->candidate_email ?? $candidate->alternate_email ?? 'N/A',
            $candidate->mobile_no ?? 'N/A',

            $candidate->account_holder_name ?? $candidate->candidate_name,
            $candidate->bank_account_no ?? 'N/A',
            $candidate->bank_ifsc ?? 'N/A',
            $candidate->pan_no ?? 'N/A',

            $candidate->requisition_type ?? 'N/A',
            $candidate->requisition_type ?? 'N/A', // (Designation/Grade same as blade)

            $candidate->reporting_to ?? 'N/A',
            optional($candidate->reportingManager)->emp_email ?? 'N/A',

            $candidate->aadhaar_no ?? 'N/A',

            $candidate->contract_start_date
                ? Carbon::parse($candidate->contract_start_date)->format('d-M-Y')
                : 'N/A',

            $candidate->contract_end_date
                ? Carbon::parse($candidate->contract_end_date)->format('d-M-Y')
                : 'N/A',

            $candidate->final_status == 'A'
                ? 'Active'
                : ($candidate->final_status == 'D' ? 'Deactive' : $candidate->final_status),

            number_format($candidate->remuneration_per_month ?? 0, 2),

            $candidate->remarks ?? 'N/A',

            $candidate->contract_generate_date
                ? Carbon::parse($candidate->contract_generate_date)->format('d-M-Y')
                : 'N/A',

            $candidate->contract_dispatch_date
                ? Carbon::parse($candidate->contract_dispatch_date)->format('d-M-Y')
                : 'N/A',

            $candidate->signed_contract_upload_date
                ? Carbon::parse($candidate->signed_contract_upload_date)->format('d-M-Y')
                : 'N/A',

            $candidate->signed_contract_dispatch_date
                ? Carbon::parse($candidate->signed_contract_dispatch_date)->format('d-M-Y')
                : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Full range
                $fullRange = "A1:{$highestColumn}{$highestRow}";

                // 1️⃣ Add Borders
                $sheet->getStyle($fullRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // 2️⃣ Header Background Color
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D9E1F2', // Light professional blue
                        ],
                    ],
                ]);

                // 3️⃣ Center header text
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 4️⃣ Freeze header row
                $sheet->freezePane('A2');

                // 5️⃣ Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(22);
            },
        ];
    }
}
