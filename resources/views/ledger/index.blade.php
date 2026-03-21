@extends('layouts.guest')

@section('content')
<div class="container-fluid">


	<div class="row mb-1">
		<div class="col-12">
			<div class="page-title-box d-flex justify-content-between align-items-center">
				<h4 class="mb-0">Ledger Management</h4>

				<div class="d-flex gap-2">
					@if($tab == 'operative')
					<button class="btn btn-sm btn-success" onclick="exportAndMoveLedger()">
						<i class="ri-file-excel-2-line"></i> Export & Move to Ledger Created
					</button>
					@endif

					@if($tab == 'created')
					<button class="btn btn-sm btn-success" onclick="exportLedger()">
						<i class="ri-file-excel-2-line"></i> Export Excel
					</button>
					@endif
				</div>

			</div>
		</div>
	</div>

	<div class="card mb-2 shadow-sm">
		<div class="card-body">
			<form method="GET" action="{{ route('ledger.index') }}" class="row g-3 align-items-end">

				<input type="hidden" name="tab" value="{{ $tab }}">

				<div class="col-md-2">
					<label class="form-label form-label-sm">Department</label>
					<select name="department_id" class="form-select form-select-sm">
						<option value="">All</option>
						@foreach($departments as $dept)
						<option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
							{{ $dept->department_name }}
						</option>
						@endforeach
					</select>
				</div>

				<div class="col-md-3">
					<label class="form-label form-label-sm">Search</label>
					<input type="text" name="search" class="form-control form-control-sm"
						value="{{ request('search') }}"
						placeholder="Name / Code">
				</div>

				<div class="col-md-3">

					<label class="form-label form-label-sm">Requisition Type</label>

					<select name="requisition_type" class="form-select form-select-sm">

<option value="">All</option>

<option value="Contractual"
{{ request('requisition_type')=='Contractual'?'selected':'' }}>
Contractual
</option>

<option value="CB"
{{ request('requisition_type')=='CB'?'selected':'' }}>
CB
</option>

<option value="TFA"
{{ request('requisition_type')=='TFA'?'selected':'' }}>
TFA
</option>

</select>

				</div>

				<div class="col-md-2">
					<button class="btn btn-sm btn-primary w-100">
						<i class="ri-filter-line"></i> Filter
					</button>
				</div>

			</form>
		</div>
	</div>

	<!-- Tabs -->
	<ul class="nav nav-tabs mb-3">
		<li class="nav-item">
			<a class="nav-link {{ $tab == 'inoperative' ? 'active' : '' }}"
href="{{ route('ledger.index', array_merge(request()->query(), ['tab' => 'inoperative'])) }}">
				PAN Inoperative
			</a>
		</li>

		<li class="nav-item">
			<a class="nav-link {{ $tab == 'operative' ? 'active' : '' }}"
href="{{ route('ledger.index', array_merge(request()->query(), ['tab' => 'operative'])) }}">
				PAN Operative
			</a>
		</li>

		<li class="nav-item">
			<a class="nav-link {{ $tab == 'created' ? 'active' : '' }}"
href="{{ route('ledger.index', array_merge(request()->query(), ['tab' => 'created'])) }}">
				Ledger Created
			</a>
		</li>
	</ul>

	<div class="card shadow-sm">
		<div class="card-body p-0">
			<div class="table-responsive" style="max-height:70vh;">
				<div class="card shadow-sm">
					<table class="table table-sm table-bordered table-striped">

						<thead>
							<tr>

								@if($tab == 'operative')
								<th>
									<input type="checkbox" id="selectAll">
								</th>
								@endif

								<th>Name</th>
								<th>Code</th>
								<th>Account Type</th>
								<th>Group</th>
								<th>Parent Code</th>
								<th>Parent Name</th>
								<th>Business Entity</th>
								<th>Crop Vertical</th>
								<th>Region</th>
								<th>Address</th>
								<th>City</th>
								<th>Pin</th>
								<th>Email</th>
								<th>Tel No</th>
								<th>Bank Account Name</th>
								<th>Bank Account No</th>
								<th>IFSC</th>
								<th>Mobile</th>
								<th>City Name</th>
								<th>MSME No</th>
								<th>MSME</th>
								<th>State</th>
								<th>Country</th>
								<th>Business Unit</th>
								<th>PAN</th>
								<th>Department</th>
								<th>Designation</th>
								<th>Grade</th>
								<th>Reporting To</th>
								<th>AADHAR</th>
								<th>Function</th>
								<th>Sub Department</th>
								<th>Zone</th>
								<th>DOJ</th>
								<th>Reporting Designation</th>
								<th>Reporting Email</th>
								<th>Reporting Contact</th>
								<th>Emp Bank Acc No</th>
								<th>Emp IFSC</th>
								<th>Emp Bank Name</th>
								<th>Location/HQ</th>
								<th>Crop</th>
								<th>Transaction Type</th>

							</tr>
						</thead>

						<tbody>

							@forelse($candidates as $candidate)

							<tr>

								@if($tab == 'operative')
								<td>
									<input type="checkbox" name="ids[]" value="{{ $candidate->id }}">
								</td>
								@endif

								<td>{{ $candidate->candidate_name }}</td>
								<td>{{ $candidate->candidate_code }}</td>
								<td>Vendor</td>
								<td>FALSE</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>120</td>
								<td>{{ $candidate->vertical->vertical_code ?? 'NA' }}</td>
								<td>{{ $candidate->regionRef->focus_code ?? 'NA' }}</td>
								<td>{{ $candidate->address_line_1 ?? '-' }}</td>
								<td>{{ $candidate->cityMaster->city_village_name ?? '-' }}</td>
								<td>{{ $candidate->pin_code ?? '-' }}</td>
								<td>{{ $candidate->candidate_email ?? '-' }}</td>
								<td>{{ $candidate->mobile_no ?? '-' }}</td>
								<td>{{ $candidate->account_holder_name }} {{ $candidate->candidate_code }}</td>
								<td>{{ $candidate->bank_account_no ?? '-' }}</td>
								<td>{{ $candidate->bank_ifsc ?? '-' }}</td>
								<td>{{ $candidate->mobile_no ?? '-' }}</td>
								<td>{{ $candidate->cityMaster->city_village_name ?? '-' }}</td>
								<td>N/A</td>
								<td>NO</td>
								<td>{{ $candidate->workState->state_code ?? '-' }}</td>
								<td>IND</td>
								<td>{{ $candidate->businessUnit->business_unit_code ?? 'NA' }}</td>
								<td>{{ $candidate->pan_no ?? '-' }}</td>
								<td>{{ $candidate->department->department_code ?? '-' }}</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>{{ $candidate->reportingManager->emp_name ?? '-' }}</td>
								<td>{{ $candidate->aadhaar_no ?? '-' }}</td>
								<td>{{ $candidate->function->function_name ?? 'NA' }}</td>
								<td>{{ $candidate->subDepartmentRef->focus_code ?? 'NA' }}</td>
								<td>{{ $candidate->zoneRef->zone_code ?? 'NA' }}</td>
								<td>{{ optional($candidate->contract_start_date)->format('d/m/Y') }}</td>
								<td>{{ $candidate->reportingManager->emp_designation ?? '-' }}</td>
								<td>{{ $candidate->reportingManager->emp_email ?? '' }}</td>
								<td>{{ $candidate->reportingManager->emp_contact ?? '-' }}</td>
								<td>{{ $candidate->bank_account_no ?? '-' }}</td>
								<td>{{ $candidate->bank_ifsc ?? '-' }}</td>
								<td>{{ $candidate->account_holder_name }} {{ $candidate->candidate_code }}</td>
								<td>{{ $candidate->work_location_hq ?? '-' }}</td>
								<td>All Crop</td>
								<td>NEFT</td>

							</tr>

							@empty

							<tr>
								<td colspan="44" class="text-center">No records found</td>
							</tr>

							@endforelse

						</tbody>

					</table>
				</div>
			</div>
		</div>

		<div class="card-footer">
			{{ $candidates->links('pagination::bootstrap-5') }}
		</div>
	</div>
	@endsection

	@push('scripts')
	<script>
		// Select All
		document.getElementById('selectAll')?.addEventListener('change', function() {
			document.querySelectorAll('input[name="ids[]"]').forEach(el => {
				el.checked = this.checked;
			});
		});

		// Move to Ledger Created
		function exportAndMoveLedger() {

			let ids = [];

			document.querySelectorAll('input[name="ids[]"]:checked')
				.forEach(el => ids.push(el.value));

			if (ids.length === 0) {
				alert('Select at least one record');
				return;
			}

			// trigger download in background
			let iframe = document.createElement('iframe');

			iframe.style.display = 'none';

			let params = new URLSearchParams(window.location.search);

			iframe.src =
			"{{ route('ledger.exportOperative') }}?ids=" +
			ids.join(',') +
			"&" +
			params.toString();

			document.body.appendChild(iframe);

			// reload page after export
			setTimeout(() => {

				window.location.reload();

			}, 1500);
		}

		function exportLedger() {
			let params = new URLSearchParams(window.location.search);
			window.location.href = "{{ route('ledger.export') }}?" + params.toString();
		}
	</script>
	<style>
		.table thead th {
			position: sticky;
			top: 0;
			background-color: #e9ecef;
			z-index: 2;
			font-weight: 600;
			font-size: 11px;
		}

		.table tbody td {
			font-size: 11px;
			white-space: nowrap;
		}
	</style>
	@endpush