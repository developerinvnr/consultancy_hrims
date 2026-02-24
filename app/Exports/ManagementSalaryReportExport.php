<?php

namespace App\Exports;

use App\Models\CandidateMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Services\HierarchyAccessService;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class ManagementSalaryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
	protected $financialYear;
	protected $startYear;
	protected $endYear;
	protected $filters;
	protected $data;
	protected $monthlyTotals;
	protected $hierarchyService;

	public function __construct($financialYear, $filters = [])
	{
		$this->financialYear = $financialYear;
		$this->filters = $filters;
		$this->hierarchyService = app(HierarchyAccessService::class);

		[$this->startYear, $this->endYear] = explode('-', $financialYear);

		$this->prepareData();
	}


	protected function prepareData()
	{
		$query = CandidateMaster::whereIn('final_status', ['A', 'D'])
			->with([
				'salaryProcessings' => function ($q) {
					$q->where(function ($query) {
						$query->where(function ($q1) {
							$q1->where('year', $this->startYear)
								->whereBetween('month', [4, 12]);
						})
							->orWhere(function ($q2) {
								$q2->where('year', $this->endYear)
									->whereBetween('month', [1, 3]);
							});
					})
						->select('candidate_id', 'month', 'year', 'net_pay');
				}
			]);

		// 🔐 Apply Hierarchy Restriction
		$user = Auth::user();

		// ✅ Apply hierarchy ONLY for non-admin
		if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {

			$allowedEmpIds = $this->hierarchyService
				->getReportingEmployeeIds($user->emp_id);

			$query->whereIn('reporting_manager_employee_id', $allowedEmpIds);
		}

		// Apply UI filters
		foreach ($this->filters as $key => $value) {
			if ($value && $value !== 'All') {
				switch ($key) {
					case 'department':
						$query->where('department_id', $value);
						break;
					case 'bu':
						$query->where('business_unit', $value);
						break;
					case 'zone':
						$query->where('zone', $value);
						break;
					case 'region':
						$query->where('region', $value);
						break;
					case 'territory':
						$query->where('territory', $value);
						break;
					case 'employee':
						$query->where('reporting_manager_employee_id', $value);
						break;
					case 'requisition_type':
						$query->where('requisition_type', $value);
						break;
				}
			}
		}


		$candidates = $query->orderBy('candidate_code')->cursor();


		$this->data = [];
		$this->monthlyTotals = [
			4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0,
			10 => 0, 11 => 0, 12 => 0,
			1 => 0, 2 => 0, 3 => 0,
		];		

		$this->monthlyTotals['grand_total'] = 0;

		foreach ($candidates as $candidate) {

			$employeeData = [
				'code' => $candidate->candidate_code,
				'name' => $candidate->candidate_name,
				'monthly_salary' => array_fill_keys(range(1, 12), 0),
				'grand_total' => 0
			];

			foreach ($candidate->salaryProcessings as $salary) {
				$month = (int) $salary->month;
				$amount = (float) $salary->net_pay;

				$employeeData['monthly_salary'][$month] = $amount;
				$employeeData['grand_total'] += $amount;

				$this->monthlyTotals[$month] += $amount;
				$this->monthlyTotals['grand_total'] += $amount;
			}

			$this->data[] = $employeeData;
		}
	}



	public function collection()
	{
		return collect($this->data);
	}

	public function headings(): array
	{
		$months = [
			 'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
    'January',
    'February',
    'March'
		];

		return array_merge(
			['S No.', 'EC', 'Name'],
			$months,
			['Grand Total']
		);
	}

	public function map($employee): array
	{
		static $index = 0;
		$index++;

		$row = [
			$index,
			$employee['code'],
			$employee['name']
		];

		// Add monthly amounts
		$fyMonths = [4,5,6,7,8,9,10,11,12,1,2,3];

foreach ($fyMonths as $month) {
    $row[] = $employee['monthly_salary'][$month] ?? 0;
}
		// Add grand total
		$row[] = $employee['grand_total'];

		return $row;
	}

	public function styles(Worksheet $sheet)
	{
		return [
			1 => [
				'font' => ['bold' => true],
				'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']],
				'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
			],
		];
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();
				$lastRow = count($this->data) + 3; // +3 for title, headers, and totals row

				// Set column widths
				$sheet->getColumnDimension('A')->setWidth(8);  // S No.
				$sheet->getColumnDimension('B')->setWidth(15); // EC
				$sheet->getColumnDimension('C')->setWidth(30); // Name

				// Set column widths for months
				for ($col = 'D'; $col <= 'O'; $col++) {
					$sheet->getColumnDimension($col)->setWidth(15);
				}
				$sheet->getColumnDimension('P')->setWidth(15); // Grand Total

				// Format number columns (D to P)
				$sheet->getStyle('D2:P' . ($lastRow + 1))
					->getNumberFormat()
					->setFormatCode('#,##0.00');

				// Center align S No. and months headers
				$sheet->getStyle('A2:A' . ($lastRow + 1))
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_CENTER);

				$sheet->getStyle('D1:O1')
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_CENTER);

				// Right align amount columns
				$sheet->getStyle('D2:P' . ($lastRow + 1))
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_RIGHT);

				// Add borders
				$sheet->getStyle('A1:P' . ($lastRow + 1))
					->getBorders()
					->getAllBorders()
					->setBorderStyle(Border::BORDER_THIN);

				// Style header row
				$sheet->getStyle('A1:P1')->applyFromArray([
					'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
					'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
					'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
				]);

				// Add grand totals row
				$totalsRow = $lastRow + 1;
				$sheet->setCellValue('A' . $totalsRow, 'Grand Total');
				$sheet->mergeCells('A' . $totalsRow . ':C' . $totalsRow);

				// Add monthly totals
				$fyMonths = [4,5,6,7,8,9,10,11,12,1,2,3];

foreach ($fyMonths as $index => $month) {
    $col = chr(68 + $index); // D onwards
    $sheet->setCellValue($col . $totalsRow, $this->monthlyTotals[$month]);
}
				// Add grand total
				$sheet->setCellValue('P' . $totalsRow, $this->monthlyTotals['grand_total']);

				// Style totals row
				$sheet->getStyle('A' . $totalsRow . ':P' . $totalsRow)->applyFromArray([
					'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
					'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343a40']],
					'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
				]);

				$sheet->getStyle('D' . $totalsRow . ':P' . $totalsRow)
					->getAlignment()
					->setHorizontal(Alignment::HORIZONTAL_RIGHT);

				// Add title
				$sheet->insertNewRowBefore(1, 2);
				$sheet->mergeCells('A1:P1');

				$title = "Management Remuneration Report - {$this->financialYear}";
				$filterText = '';

				// Build filter description
				$filterDescriptions = [];
				foreach ($this->filters as $key => $value) {
					if ($value && $value !== 'All') {
						$filterDescriptions[] = ucfirst($key) . ": " . $value;
					}
				}

				if (!empty($filterDescriptions)) {
					$title .= " (" . implode(', ', $filterDescriptions) . ")";
				}

				$sheet->setCellValue('A1', $title);
				$sheet->getStyle('A1')->applyFromArray([
					'font' => ['bold' => true, 'size' => 16],
					'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
					'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']]
				]);
			},
		];
	}
}
