@extends('layouts.guest')

@section('content')
<div class="container-fluid">

	<div class="row mb-2">
		<div class="col-12">
			<h4>JV Report</h4>
		</div>
	</div>

	{{-- FILTER --}}
	<div class="card mb-3 shadow-sm">
		<div class="card-body">
			<form method="GET"
				action="{{ route('reports.jv') }}"
				id="jvForm"
				class="row g-3 align-items-end">

				<div class="col-md-2">
					<label class="form-label form-label-sm">Financial Year</label>
					<select name="financial_year" class="form-select form-select-sm">
						@php
						$currentMonth = date('n');
						$currentYear = date('Y');

						if ($currentMonth >= 4) {
						$fyStart = $currentYear;
						} else {
						$fyStart = $currentYear - 1;
						}

						$startYear = $fyStart - 2;
						$endYear = $fyStart;
						@endphp

						@for($y = $startYear; $y <= $endYear; $y++)
							@php $fy=$y . '-' . ($y + 1); @endphp
							<option value="{{ $fy }}"
							{{ ($financialYear ?? '') == $fy ? 'selected' : '' }}>
							{{ $fy }}
							</option>
							@endfor
					</select>
				</div>

				<div class="col-md-2">
					<label class="form-label form-label-sm">Month</label>
					@php $fyMonths = [4,5,6,7,8,9,10,11,12,1,2,3]; @endphp
					<select name="month" class="form-select form-select-sm">
						@foreach($fyMonths as $m)
						<option value="{{ $m }}"
							{{ $month == $m ? 'selected' : '' }}>
							{{ \Carbon\Carbon::create()->month($m)->format('F') }}
						</option>
						@endforeach
					</select>
				</div>

				<div class="col-md-2">
					<label class="form-label form-label-sm">Status</label>
					<select name="status" class="form-select form-select-sm">
						<option value="All" {{ $status=='All'?'selected':'' }}>All</option>
						<option value="A" {{ $status=='A'?'selected':'' }}>Active</option>
						<option value="D" {{ $status=='D'?'selected':'' }}>Inactive</option>
					</select>
				</div>

				<div class="col-md-2">
					<label class="form-label form-label-sm">Requisition Type</label>
					<select name="requisition_type" class="form-select form-select-sm">
						<option value="">All Types</option>
						<option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
						<option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
						<option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label form-label-sm">Export Status</label>
					<select name="export_status" class="form-select form-select-sm">
						<option value="All">All</option>
						<option value="exported"
							{{ request('export_status')=='exported'?'selected':'' }}>
							Exported
						</option>
						<option value="not_exported"
							{{ request('export_status')=='not_exported'?'selected':'' }}>
							Not Exported
						</option>
					</select>
				</div>

				<div class="col-md-2 d-flex gap-2">
					<button type="submit"
						class="btn btn-sm btn-primary w-50">
						Generate
					</button>

					<button type="button"
						class="btn btn-sm btn-success w-50"
						onclick="exportJV()">
						Export
					</button>
				</div>

			</form>
		</div>
	</div>

	{{-- TABLE --}}
	<div class="vendor-scroll">
		<table class="table table-sm table-bordered table-striped">
			<thead>
				<tr>
					<th>DocNo</th>
					<th>Date</th>
					<th>Business Entity</th>
					<th>sNarration</th>
					<th>TDSJVNo</th>
					<th>ReverseCharge_Yn_</th>
					<th>BillNo</th>
					<th>BillDate</th>
					<th>Department</th>
					<th>Cost Center</th>
					<th>Business Unit</th>
					<th>Activity</th>
					<th>Location</th>
					<th>State</th>
					<th>Category</th>
					<th>Crop</th>
					<th>Region</th>
					<th>Function</th>
					<th>FC-Vertical</th>
					<th>Sub Department</th>
					<th>Zone</th>
					<th>DrAccount</th>
					<th>CrAccount</th>
					<th>Amount</th>
					<th>TDS</th>
					<th>TDSPer</th>
				</tr>
			</thead>

			<tbody>
				@forelse($records as $rec)
				@php
				$narration = "Being Contractual Expenses for the Month of "
				. \Carbon\Carbon::create()->month($month)->format('F')
				. " $year";

				$billingDate = \Carbon\Carbon::create($year, $month, 1);
				$invoiceDatePart = $billingDate->endOfMonth()->format('dmy');

				$billNo = $rec->candidate->candidate_code . '-' . $invoiceDatePart;
				$billDate = $billingDate->endOfMonth()->format('d-m-Y');

				// ✅ Calculate TDS and Gross Up
				$finalPayable = $rec->total_payable ?? ($rec->net_pay + ($rec->arrear_amount ?? 0));
				$tds = $finalPayable > 0 ? ($finalPayable / 98) * 2 : 0;
				$grossUp = $finalPayable + $tds;
				@endphp
				<tr>
					<td></td> {{-- DocNo --}}
					<td>{{ now()->format('d-m-Y') }}</td>
					<td>120</td>
					<td>{{ $narration }}</td>
					<td></td> {{-- TDSJVNo --}}
					<td></td> {{-- ReverseCharge --}}
					<td>{{ $billNo }}</td>
					<td>{{ $billDate }}</td>
					<td>{{ $rec->candidate->department->department_code ?? '' }}</td>
					<td>N/A</td> {{-- Cost Center --}}
					<td>{{ $rec->candidate->businessUnit->business_unit_code ?? '' }}</td>
					<td>All Activity</td>
					<td>{{ $rec->candidate->workLocation->focus_code ?? '' }}</td>
					<td>{{ $rec->candidate->workState->state_code ?? '' }}</td>
					<td>N/A</td>
					<td>All Crop</td>
					<td>{{ $rec->candidate->regionRef->focus_code ?? 'N/A' }}</td>
					<td>{{ $rec->candidate->function->function_code ?? '' }}</td>
					<td>{{ $rec->candidate->vertical->vertical_code ?? '' }}</td>
					<td>{{ $rec->candidate->subDepartmentRef->focus_code ?? '' }}</td>
					<td>{{ $rec->candidate->zoneRef->zone_code ?? '' }}</td>
					<td>INDIRECT-MSC-17</td>
					<td>{{ $rec->candidate->candidate_code }}</td>
					<td>{{ number_format($grossUp, 0) }}</td> {{-- ✅ Show Gross Up --}}
					<td>{{ number_format($tds, 0) }}</td> {{-- ✅ Show TDS --}}
					<td>2%</td> {{-- ✅ Show TDS % --}}
				</tr>
				@empty
				<tr>
					<td colspan="26" class="text-center">No records found</td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	<div class="mt-3">
		{{ $records->links('pagination::bootstrap-5') }}
	</div>

</div>
@endsection


<script>
	function exportJV() {
		let form = $('#jvForm');
		let params = form.serialize();
		window.location.href =
			"{{ route('reports.jv.export') }}?" + params;
	}
</script>

<style>
	.vendor-scroll {
		width: 100%;
		max-height: 70vh;
		overflow: auto;
	}

	.vendor-scroll thead th {
		position: sticky;
		top: 0;
		background: #e9ecef;
	}
</style>