{{-- resources/views/reports/partials/remuneration-results.blade.php --}}
<div class="remuneration-scroll">
	<table class="table table-sm table-bordered table-striped table-hover mb-0">
		<thead class="">
			<tr>
				<th>S.N.</th>
				<th>Code</th>
				<th>Name of Employees</th>
				<th>Business Unit</th>
				<th>Vertical</th>
				<th>Zone</th>
				<th>Region</th>
				<th>Territory</th>
				<th>Department</th>
				<th>Sub Department</th>
				<th>Paid days</th>
				<th>Working Days</th>
				<th>Sunday working</th>
				<th>Total Paid Days</th>
				<th>Remuneration As Per Contract</th>
				<th>Previous month</th>
				<th>Arrear</th>
				<th>Deduction</th>
				<th>Based on paid days</th>
				<th>Payment Instruction</th>
				<th>HR Remarks</th>
				<th>TDS 2%</th>
				<th>Gross up 102%</th>
			</tr>
		</thead>
		<tbody>
			@forelse($salaryRecords as $index => $record)
			<tr>
				<td>{{ $salaryRecords->firstItem() + $index }}</td>
				<td>{{ $record->candidate->candidate_code ?? '-' }}</td>
				<td>{{ $record->candidate->candidate_name ?? '-' }}</td>

				<td>{{ $record->candidate->businessUnit->business_unit_name ?? '-' }}</td>
				<td>{{ $record->candidate->vertical->vertical_name ?? '-' }}</td>
				<td>{{ $record->candidate->zoneRef->zone_name ?? '-' }}</td>
				<td>{{ $record->candidate->regionRef->region_name ?? '-' }}</td>
				<td>{{ $record->candidate->territoryRef->territory_name ?? '-' }}</td>
				<td>{{ $record->candidate->department->department_name ?? '-' }}</td>
				<td>{{ $record->candidate->subDepartmentRef->sub_department_name ?? '-' }}</td>
				<td>{{ $record->paid_days ?? 0 }}</td>
				<td>{{ $record->total_days ?? 0 }}</td>
				<td>{{ $record->approved_sundays  ?? 0 }}</td>
				<td>{{ ($record->paid_days ?? 0) + ($record->approved_sundays ?? 0) }}</td>
				<td>{{ number_format(round($record->monthly_salary ?? 0)) }}</td>
				<td>{{ number_format(round($record->previous_month_pay ?? 0)) }}</td>
				<td>{{ number_format(round($record->extra_amount ?? 0)) }}</td>
				<td>{{ number_format(round($record->deduction_amount ?? 0)) }}</td>
				@php
$basedOnPaidDays = ($record->per_day_salary ?? 0) * ($record->paid_days ?? 0);
@endphp

<td>{{ number_format(round($basedOnPaidDays)) }}</td>
				<td>
					@php
					$instruction = strtolower($record->payment_instruction ?? '');
					@endphp

					@if($instruction === 'hold')
					<span class="badge bg-warning text-dark">Hold</span>
					@elseif($instruction === 'release')
					<span class="badge bg-success">Release</span>
					@else
					<span class="badge bg-secondary">Pending</span>
					@endif
				</td>
				<td>{{ $record->hr_remarks ?? '-' }}</td>
				@php
				$netPay = round($record->net_pay ?? 0);
				$tds = round($netPay * 0.02);
				$grossUp = round($netPay + $tds);
				@endphp

				<td>{{ number_format($tds) }}</td>
				<td>{{ number_format($grossUp) }}</td>


			</tr>
			@empty
			<tr>
				<td colspan="18" class="text-center">No records found</td>
			</tr>
			@endforelse
		</tbody>
		@if($salaryRecords->count() > 0)
		<tfoot class="bg-light font-weight-bold">
			<tr>
				<td colspan="9" class="text-right">Totals:</td>
				<td>{{ number_format($salaryRecords->sum('net_pay'), 2) }}</td>
				<td>{{ number_format($salaryRecords->sum('previous_month_pay'), 2) }}</td>
				<td>{{ number_format($salaryRecords->sum('arear_amount'), 2) }}</td>
				<td>{{ number_format($salaryRecords->sum('deduction_amount'), 2) }}</td>
				<td>{{ number_format($salaryRecords->sum('based_on_paid_days'), 2) }}</td>
				<td colspan="2"></td>
				@php
				$totalNetPay = round($salaryRecords->sum('net_pay'));
				$totalTds = round($totalNetPay * 0.02);
				$totalGrossUp = round($totalNetPay + $totalTds);
				@endphp

				<td>{{ number_format($totalTds) }}</td>
				<td>{{ number_format($totalGrossUp) }}</td>
			</tr>
		</tfoot>
		@endif
	</table>
</div>

@if(isset($salaryRecords))
<div class="row mt-3">
	<div class="col-md-6">
		Showing {{ $salaryRecords->firstItem() }} to {{ $salaryRecords->lastItem() }} of {{ $salaryRecords->total() }} entries
	</div>
	<div class="col-md-6">
		<div class="float-right">
			{{ $salaryRecords->links('pagination::bootstrap-5') }}
		</div>
	</div>
</div>
@endif