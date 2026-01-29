<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\CandidateMaster;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\Log;

class AttendanceExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected $month;
    protected $year;
    protected $employeeType;
    protected $user;
    protected $daysInMonth;
    protected $monthStart;
    protected $monthEnd;
    protected $collectionData;

    public function __construct($month, $year, $employeeType = 'all', $user = null)
    {
        $this->month = (int) $month;
        $this->year = (int) $year;
        $this->employeeType = $employeeType;
        $this->user = $user;
        $this->daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
        $this->monthStart = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $this->monthEnd = Carbon::create($this->year, $this->month, 1)->endOfMonth();
    }

    public function collection(): Collection
    {
        Log::info('Starting attendance export collection', [
            'user_id' => $this->user ? $this->user->id : 'null',
            'month' => $this->month,
            'year' => $this->year
        ]);

        // Build query with role-based filtering
        $query = CandidateMaster::query()
            ->select([
                'id',
                'candidate_code',
                'candidate_name',
                'requisition_type',
                'contract_start_date',
                'leave_credited',
                'remuneration_per_month',
                'reporting_manager_employee_id'
            ])
            ->where('final_status', 'A')
            ->whereNotNull('contract_start_date')
            ->where('contract_start_date', '<=', $this->monthEnd);

        // Role-based filtering
        if ($this->user) {
            if (!$this->user->hasRole('hr_admin') && !$this->user->hasRole('admin')) {
                $query->where('reporting_manager_employee_id', $this->user->emp_id);
            }
        }

        // Filter by employee type
        if ($this->employeeType && $this->employeeType !== 'all') {
            $query->where('requisition_type', $this->employeeType);
        }

        $candidates = $query->orderBy('candidate_code')->get();

        Log::info('Candidates found for export:', [
            'count' => $candidates->count(),
            'candidates' => $candidates->pluck('candidate_code')->toArray()
        ]);

        if ($candidates->isEmpty()) {
            return collect([]);
        }

        // Eager load attendance records
        $candidateIds = $candidates->pluck('id')->toArray();
        
        $attendances = Attendance::whereIn('candidate_id', $candidateIds)
            ->where('Month', $this->month)
            ->where('Year', $this->year)
            ->get()
            ->keyBy('candidate_id');
            
        // Eager load leave balances for contractual candidates
        $contractualIds = $candidates->where('requisition_type', 'Contractual')
            ->pluck('id')
            ->toArray();
            
        $leaveBalances = collect([]);
        if (!empty($contractualIds)) {
            $leaveBalances = LeaveBalance::whereIn('CandidateID', $contractualIds)
                ->where('calendar_year', $this->year)
                ->get()
                ->keyBy('CandidateID');
        }

        $rows = [];

        foreach ($candidates as $index => $candidate) {
            $attendance = $attendances[$candidate->id] ?? null;
            
            // Get leave balance for contractual candidates
            $clRemaining = 0;
            $clUsed = 0;
            
            if ($candidate->requisition_type === 'Contractual') {
                $leaveBalance = $leaveBalances[$candidate->id] ?? null;
                
                if ($leaveBalance) {
                    $clRemaining = max(0, $leaveBalance->opening_cl_balance - $leaveBalance->cl_utilized);
                    $clUsed = $leaveBalance->cl_utilized;
                } else {
                    // Fallback calculation
                    $joiningDate = Carbon::parse($candidate->contract_start_date);
                    $joiningYear = $joiningDate->year;
                    
                    if ($joiningYear < $this->year) {
                        $clRemaining = 12;
                    } elseif ($joiningYear == $this->year) {
                        if ($joiningDate->month <= $this->month) {
                            $eligibleMonths = 13 - $joiningDate->month;
                            $clRemaining = min($eligibleMonths, 12);
                        }
                    }
                    
                    if ($candidate->leave_credited && $candidate->leave_credited > 0) {
                        $clRemaining = $candidate->leave_credited;
                    }
                }
            }

            // Prepare row data
            $row = [
                'sno' => $index + 1,
                'code' => $candidate->candidate_code ?? '',
                'name' => $candidate->candidate_name ?? '',
                'type' => $candidate->requisition_type ?? '',
                'doj' => $candidate->contract_start_date 
                    ? Carbon::parse($candidate->contract_start_date)->format('d-m-Y') 
                    : '',
                'leave_credited' => $candidate->leave_credited ?? 0,
                'cl_remaining' => $clRemaining,
                'cl_used' => $clUsed,
                'daily_rate' => $candidate->remuneration_per_month 
                    ? round($candidate->remuneration_per_month / 26, 2) 
                    : 0,
                'total_present' => 0,
                'total_absent' => 0,
                'total_cl' => 0,
                'total_lwp' => 0,
            ];

            // Process each day
            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $col = "A{$day}";
                $status = $attendance ? ($attendance->$col ?? '') : '';
                
                $date = Carbon::create($this->year, $this->month, $day);
                
                // Handle Sundays
                if ($date->dayOfWeek === Carbon::SUNDAY) {
                    $status = $status === 'P' ? 'P' : 'W';
                }

                $row["day_$day"] = $status;

                // Count totals
                switch ($status) {
                    case 'P':
                    case 'H':
                        $row['total_present']++;
                        break;
                    case 'A':
                        $row['total_absent']++;
                        break;
                    case 'CL':
                        $row['total_cl']++;
                        break;
                    case 'LWP':
                        $row['total_lwp']++;
                        break;
                }
            }

            $rows[] = $row;
        }

        $this->collectionData = collect($rows);
        Log::info('Export collection prepared:', [
            'row_count' => $this->collectionData->count()
        ]);
        
        return $this->collectionData;
    }

    public function headings(): array
    {
        // Return empty array - we'll handle headers manually
        return [];
    }

    public function map($row): array
    {
        $data = [
            $row['sno'],
            $row['code'],
            $row['name'],
            $row['type'],
            $row['doj'],
            $row['leave_credited'],
        ];

        // Add day columns
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $data[] = $row["day_$d"] ?? '';
        }

        // Add summary columns
        return array_merge($data, [
            $row['total_present'],
            $row['total_absent'],
            $row['total_cl'],
            $row['cl_remaining'],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                // This event fires before data is written
            },
            
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Calculate total columns
                $totalCols = 6 + $this->daysInMonth + 4;
                $lastCol = Coordinate::stringFromColumnIndex($totalCols);
                
                // STEP 1: First, shift all existing data DOWN by 2 rows
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                Log::info('Before shifting:', [
                    'highest_row' => $highestRow,
                    'highest_column' => $highestColumn
                ]);
                
                // If we have data, shift it down by 2 rows
                if ($highestRow > 0) {
                    // Create a new range starting from row 3
                    for ($row = $highestRow; $row >= 1; $row--) {
                        for ($col = 1; $col <= $totalCols; $col++) {
                            $colLetter = Coordinate::stringFromColumnIndex($col);
                            $oldCell = "{$colLetter}{$row}";
                            $newCell = "{$colLetter}" . ($row + 2);
                            
                            // Copy cell value
                            $cellValue = $sheet->getCell($oldCell)->getValue();
                            $sheet->setCellValue($newCell, $cellValue);
                            
                            // Clear old cell
                            if ($row <= 2) {
                                $sheet->setCellValue($oldCell, null);
                            }
                        }
                    }
                }
                
                // STEP 2: Now write headers in rows 1-2
                // Basic headers
                $basicHeaders = ['S.No', 'Code', 'Name', 'Type', 'DOJ', 'Leave Credited'];
                
                $colIndex = 1;
                foreach ($basicHeaders as $header) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                    $sheet->setCellValue("{$colLetter}1", $header);
                    $sheet->mergeCells("{$colLetter}1:{$colLetter}2");
                }
                
                // Day headers
                $colIndex = 7;
                for ($day = 1; $day <= $this->daysInMonth; $day++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                    $date = Carbon::create($this->year, $this->month, $day);
                    
                    $sheet->setCellValue("{$colLetter}1", $day);
                    $sheet->setCellValue("{$colLetter}2", $date->format('D'));
                }
                
                // Summary headers
                $summaryHeaders = ['P', 'A', 'CL', 'Bal'];
                foreach ($summaryHeaders as $header) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                    $sheet->setCellValue("{$colLetter}1", $header);
                    $sheet->mergeCells("{$colLetter}1:{$colLetter}2");
                }
                
                // STEP 3: Apply styling to header rows
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2c3e50']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                ];
                
                $sheet->getStyle("A1:{$lastCol}2")->applyFromArray($headerStyle);
                
                // Set row height for headers
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(20);
                
                // STEP 4: Apply borders to all cells
                $newHighestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:{$lastCol}{$newHighestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // STEP 5: Color Sunday columns
                $colIndex = 7;
                for ($day = 1; $day <= $this->daysInMonth; $day++) {
                    $date = Carbon::create($this->year, $this->month, $day);
                    if ($date->dayOfWeek === Carbon::SUNDAY) {
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $sheet->getStyle("{$colLetter}1:{$colLetter}{$newHighestRow}")
                            ->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'f3f6ff']
                                ]
                            ]);
                    }
                    $colIndex++;
                }
                
                // STEP 6: Center align all cells
                $sheet->getStyle("A1:{$lastCol}{$newHighestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                
                // STEP 7: Freeze header rows
                $sheet->freezePane('A3');
                
                // STEP 8: Auto-filter
                $sheet->setAutoFilter("A1:{$lastCol}2");
                
                // STEP 9: Set column widths
                $widths = [
                    'A' => 6,    // S.No
                    'B' => 15,   // Code
                    'C' => 25,   // Name
                    'D' => 12,   // Type
                    'E' => 12,   // DOJ
                    'F' => 12,   // Leave Credited
                ];

                // Day columns
                $colIndex = 7;
                for ($i = 1; $i <= $this->daysInMonth; $i++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                    $widths[$colLetter] = 5;
                }

                // Summary columns
                $summaryColumns = ['P', 'A', 'CL', 'Bal'];
                foreach ($summaryColumns as $col) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                    $widths[$colLetter] = 8;
                }

                // Apply widths
                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
                
                // Log final state
                Log::info('After sheet processing:', [
                    'final_highest_row' => $newHighestRow,
                    'headers_written' => true,
                    'data_start_row' => 3
                ]);
            }
        ];
    }
}