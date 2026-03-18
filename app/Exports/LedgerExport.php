<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;


class LedgerExport implements FromCollection, WithHeadings, WithEvents
{
	protected $records;

	public function __construct($records)
	{
		$this->records = $records;
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {

				$sheet = $event->sheet->getDelegate();

				$highestRow = $sheet->getHighestRow();
				$highestColumn = $sheet->getHighestColumn();

				$range = "A1:{$highestColumn}{$highestRow}";

				// ✅ Apply border to ALL cells
				$sheet->getStyle($range)->applyFromArray([
					'borders' => [
						'allBorders' => [
							'borderStyle' => Border::BORDER_THIN,
						],
					],
				]);

				// ✅ Bold header
				$sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

				// ✅ Header background
				$sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
					'fill' => [
						'fillType' => 'solid',
						'startColor' => ['argb' => 'FFE9ECEF'],
					],
				]);

				// ✅ Auto column width
				foreach (range('A', $highestColumn) as $col) {
					$sheet->getColumnDimension($col)->setAutoSize(true);
				}
			},
		];
	}

	public function collection()
	{
		return $this->records->map(function ($c, $index) {

			$unsigned = $c->unsignedAgreements->first();
			$signed = $c->signedAgreements->first();

			return [
				$index + 1,
				$c->candidate_name,
				$signed?->agreement_number ?? $unsigned?->agreement_number ?? '-',
				$c->candidate_code,
				$c->function?->function_name,
				$c->department?->department_name,
				$c->subDepartmentRef?->sub_department_name,
				$c->vertical?->vertical_name,
				$c->regionRef?->region_name,
				$c->businessUnit?->business_unit_name,
				$c->zoneRef?->zone_name,
				$c->work_location_hq,
				$c->cityMaster?->city_village_name,
				$c->workState?->state_name,
				$c->address_line_1,
				$c->pin_code,
				$c->candidate_email,
				$c->mobile_no,
				$c->account_holder_name,
				$c->bank_account_no,
				$c->bank_ifsc,
				$c->pan_no,
				$c->requisition_type,
				$c->requisition_type,
				$c->reporting_to,
				$c->reportingManager?->emp_email,
				$c->aadhaar_no,
				optional($c->contract_start_date)->format('d-M-Y'),
				optional($c->contract_end_date)->format('d-M-Y'),
				$c->final_status == 'A' ? 'Active' : 'Deactive',
				$c->remuneration_per_month,
				$c->remarks,
				optional($unsigned?->created_at)->format('d-M-Y'),
				optional($unsigned?->courierDetails?->dispatch_date)->format('d-M-Y'),
				optional($signed?->created_at)->format('d-M-Y'),
				optional($signed?->courierDetails?->dispatch_date)->format('d-M-Y'),
				$c->ledger_created ? 'Created' : 'Pending',
			];
		});
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
			'Zone',
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
			'Ledger Status'
		];
	}
}
