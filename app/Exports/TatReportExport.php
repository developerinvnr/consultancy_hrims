<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
    private $financialYear, $month, $departmentId, $requisitionType, $status;

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
        if (!$this->financialYear) {
            $currentMonth = date('n');
            $currentYear = date('Y');

            $this->financialYear = ($currentMonth >= 4)
                ? $currentYear . '-' . ($currentYear + 1)
                : ($currentYear - 1) . '-' . $currentYear;
        }
        [$startYear, $endYear] = explode('-', $this->financialYear);

        // ✅ MAIN QUERY (NO GROUP BY ISSUE)
        $query = CandidateMaster::query()
            ->leftJoin('manpower_requisitions as mr', 'mr.id', '=', 'candidate_master.requisition_id')

            // Latest Agreement
            ->leftJoinSub(
                DB::table('agreement_documents')
                    ->select('candidate_id', DB::raw('MAX(id) as id'))
                    ->where('document_type', 'agreement')
                    ->where('sign_status', 'UNSIGNED') // 👈 created
                    ->groupBy('candidate_id'),
                'created_ad',
                'created_ad.candidate_id',
                '=',
                'candidate_master.id'
            )
            ->leftJoin('agreement_documents as adc', 'adc.id', '=', 'created_ad.id')

            ->leftJoinSub(
                DB::table('agreement_documents')
                    ->select('candidate_id', DB::raw('MAX(id) as id'))
                    ->where('document_type', 'agreement')
                    ->where('sign_status', 'SIGNED') // 👈 signed
                    ->groupBy('candidate_id'),
                'signed_ad',
                'signed_ad.candidate_id',
                '=',
                'candidate_master.id'
            )
            ->leftJoin('agreement_documents as ads', 'ads.id', '=', 'signed_ad.id')

            // Latest Courier
            ->leftJoinSub(
                DB::table('agreement_couriers')
                    ->select('agreement_document_id', DB::raw('MAX(id) as id'))
                    ->groupBy('agreement_document_id'),
                'latest_ac',
                'latest_ac.agreement_document_id',
                '=',
                'ads.id'
            )
            ->leftJoin('agreement_couriers as ac', 'ac.id', '=', 'latest_ac.id')

            ->select(
                'candidate_master.*',
                'mr.submission_date',
                'mr.hr_verification_date',
                'mr.approval_date',
                'candidate_master.file_created_date',
                'adc.created_at as agreement_created_date',
                'ads.created_at as agreement_uploaded_date',
                'ac.dispatch_date',
                'ac.received_date'
            );

        // ✅ FILTERS
        if ($this->departmentId) {
            $query->where('candidate_master.department_id', $this->departmentId);
        }

        if ($this->requisitionType) {
            $query->where('candidate_master.requisition_type', $this->requisitionType);
        }

        if ($this->status) {
            $query->where('mr.status', $this->status);
        }

        // ✅ DATE FILTER
        if ($this->month) {
            $year = ($this->month >= 4) ? $startYear : $endYear;
            $startDate = "{$year}-{$this->month}-01";
            $endDate = Carbon::parse($startDate)->endOfMonth();

            $query->whereBetween('mr.submission_date', [$startDate, $endDate]);
        } else {
            $query->whereBetween('mr.submission_date', [
                $startYear . '-04-01',
                $endYear . '-03-31'
            ]);
        }

        // ✅ STAGES (SAME AS UI)
        $stages = [
            'hr' => ['from' => 'submission_date', 'to' => 'hr_verification_date'],
            'approval' => ['from' => 'hr_verification_date', 'to' => 'approval_date'],

            'agreement_create' => ['from' => 'approval_date', 'to' => 'agreement_created_date'],

            // 🔥 rename properly
            'agreement_upload' => ['from' => 'agreement_created_date', 'to' => 'agreement_uploaded_date'],

            'courier_dispatch' => ['from' => 'agreement_uploaded_date', 'to' => 'dispatch_date'],
            'courier_delivery' => ['from' => 'dispatch_date', 'to' => 'received_date'],
            'file_creation' => ['from' => 'received_date', 'to' => 'file_created_date'],
        ];

        return $query->orderByDesc('mr.submission_date')->get()->map(function ($row) use ($stages) {

            $data = [
                $row->requisition_id,
                $row->candidate_name,
                $row->submission_date ? Carbon::parse($row->submission_date)->format('d-M-Y') : '-',
            ];

            // ✅ Dates
            foreach ($stages as $s) {
                $data[] = $row->{$s['to']}
                    ? Carbon::parse($row->{$s['to']})->format('d-M-Y')
                    : '-';
            }

            // ✅ TAT
            foreach ($stages as $key => $s) {

                if ($key === 'file_creation') {
                    $fromDate = $row->received_date
                        ?? $row->agreement_uploaded_date
                        ?? $row->agreement_created_date
                        ?? $row->approval_date;

                    $toDate = $row->file_created_date;
                } else {
                    $fromDate = $row->{$s['from']} ?? null;
                    $toDate = $row->{$s['to']} ?? null;
                }

                if (!empty($fromDate) && !empty($toDate)) {

                    $days = max(0, ceil(
                        \Carbon\Carbon::parse($fromDate)
                            ->diffInDays($toDate)
                    ));

                    $data[] = $days <= 1 ? 'Within 1 Day' : $days . ' Days';
                } else {
                    $data[] = '-';
                }
            }

            return $data;
        });
    }

    // ✅ HEADINGS
    public function headings(): array
    {
        $stages = [
            'HR',
            'Approval',
            'Agreement Create',
            'Agreement Upload',
            'Courier Dispatch',
            'Courier Delivery',
            'File Creation'
        ];

        $headers = ['Req ID', 'Candidate', 'Submission'];

        foreach ($stages as $s) {
            $headers[] = $s . ' Date';
        }

        foreach ($stages as $s) {
            $headers[] = $s . ' TAT';
        }

        return $headers;
    }

    // ✅ HEADER STYLE
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4CAF50']
                ],
            ],
        ];
    }

    // ✅ SHEET FORMATTING
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Borders
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    );

                // Center align
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    );

                // Wrap text
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // Auto filter
                $sheet->setAutoFilter("A1:{$highestColumn}1");

                // Freeze header
                $sheet->freezePane('A2');

                // Title Row
                $sheet->insertNewRowBefore(1, 1);
                $sheet->setCellValue('A1', 'TAT REPORT (Action Wise)');
                $sheet->mergeCells("A1:{$highestColumn}1");

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            }
        ];
    }
}
