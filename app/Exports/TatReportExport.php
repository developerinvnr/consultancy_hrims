<?php

namespace App\Exports;

use App\Models\ManpowerRequisition;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TatReportExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{

    private $financialYear;
    private $month;
    private $departmentId;
    private $requisitionType;
    private $status;

    public function __construct($financialYear, $month, $departmentId, $requisitionType, $status)
    {
        $this->financialYear = $financialYear;
        $this->month = $month;
        $this->departmentId = $departmentId;
        $this->requisitionType = $requisitionType;
        $this->status = $status;
    }

    public function collection()
    {
        [$startYear, $endYear] = explode('-', $this->financialYear);

        $query = ManpowerRequisition::query()->orderByDesc('submission_date');

        if ($this->month) {

            $year = ($this->month >= 4) ? $startYear : $endYear;

            $startDate = "{$year}-{$this->month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth();

            $query->whereBetween('submission_date', [$startDate, $endDate]);
        } else {

            $query->whereBetween('submission_date', [
                $startYear . '-04-01',
                $endYear . '-03-31'
            ]);
        }

        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }

        if ($this->requisitionType) {
            $query->where('requisition_type', $this->requisitionType);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->get()->map(function ($row) {

            $hrTat = ($row->submission_date && $row->hr_verification_date)
                ? ceil(
                    Carbon::parse($row->submission_date)
                        ->diffInSeconds(Carbon::parse($row->hr_verification_date)) / 86400
                )
                : null;

            $approvalTat = ($row->hr_verification_date && $row->approval_date)
                ? ceil(
                    Carbon::parse($row->hr_verification_date)
                        ->diffInSeconds(Carbon::parse($row->approval_date)) / 86400
                )
                : null;

            $processTat = ($row->approval_date && $row->processing_date)
                ? ceil(
                    Carbon::parse($row->approval_date)
                        ->diffInSeconds(Carbon::parse($row->processing_date)) / 86400
                )
                : null;

            $totalTat = ($row->submission_date && $row->processing_date)
                ? ceil(
                    Carbon::parse($row->submission_date)
                        ->diffInSeconds(Carbon::parse($row->processing_date)) / 86400
                )
                : null;

            return [
                $row->requisition_id,
                $row->candidate_name,
                $row->submission_date,
                $row->hr_verification_date,
                $row->approval_date,
                $row->processing_date,
                $hrTat,
                $approvalTat,
                $processTat,
                $totalTat
            ];
        });
    }
    public function headings(): array
    {
        return [
            'Req ID',
            'Candidate Name',
            'Submission Date',
            'HR Verification Date',
            'Approval Date',
            'Processing Date',
            'HR TAT (Days)',
            'Approval TAT (Days)',
            'Processing TAT (Days)',
            'Total TAT (Days)'
        ];
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

                $sheet = $event->sheet;

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Add borders
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    );

                // Add filter
                $sheet->setAutoFilter("A1:{$highestColumn}1");

                // Freeze header
                $sheet->freezePane('A2');

                // Center align TAT columns
                $sheet->getStyle("G2:I{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    );
            }
        ];
    }
}
