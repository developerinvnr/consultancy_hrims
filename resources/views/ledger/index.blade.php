@extends('layouts.guest')

@section('content')
<div class="container-fluid">


	<div class="row mb-1">
		<div class="col-12">
			<div class="page-title-box d-flex justify-content-between align-items-center">
				<h4 class="mb-0">Ledger Management</h4>

				<div class="d-flex gap-2">
					@if($tab == 'operative')
					<button class="btn btn-sm btn-primary" onclick="markLedgerCreated()">
						<i class="ri-check-line"></i> Move to Ledger Created
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
				href="{{ route('ledger.index', ['tab' => 'inoperative']) }}">
				PAN Inoperative
			</a>
		</li>

		<li class="nav-item">
			<a class="nav-link {{ $tab == 'operative' ? 'active' : '' }}"
				href="{{ route('ledger.index', ['tab' => 'operative']) }}">
				PAN Operative
			</a>
		</li>

		<li class="nav-item">
			<a class="nav-link {{ $tab == 'created' ? 'active' : '' }}"
				href="{{ route('ledger.index', ['tab' => 'created']) }}">
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

								<th>S.No</th>
								<th>Name</th>
								<th>Agreement ID</th>
								<th>Code</th>
								<th>Function</th>
								<th>Department</th>
								<th>Sub-Dept</th>
								<th>Crop Vertical</th>
								<th>Region</th>
								<th>Business Unit</th>
								<th>Zone</th>
								<th>Location/HQ</th>
								<th>City</th>
								<th>State Name</th>
								<th>Address</th>
								<th>Pin</th>
								<th>E Mail</th>
								<th>Tel No</th>
								<th>Bank Account Name</th>
								<th>Bank Account Number</th>
								<th>IFSC Code</th>
								<th>Pan No</th>
								<th>Emp Designation</th>
								<th>Emp Grade</th>
								<th>Emp Reporting To</th>
								<th>RM Email</th>
								<th>Aadhaar No</th>
								<th>DOJ</th>
								<th>DOS</th>
								<th>Active/Deactive</th>
								<th>Remuneration</th>
								<th>Remarks</th>
								<th>Contract generate date</th>
								<th>Contract dispatch date</th>
								<th>Signed Contract Upload date</th>
								<th>Signed Contract dispatch date</th>
								<th>Ledger Status</th>
							</tr>
						</thead>

						<tbody>
							@forelse($candidates as $index => $candidate)

							@php
							$unsignedAgreement = $candidate->unsignedAgreements->first();
							$signedAgreement = $candidate->signedAgreements->first();
							@endphp

							<tr>

								@if($tab == 'operative')
								<td>
									<input type="checkbox" name="ids[]" value="{{ $candidate->id }}">
								</td>
								@endif

								<td>{{ $candidates->firstItem() + $index }}</td>
								<td>{{ $candidate->candidate_name }}</td>

								<td>
									@if($signedAgreement && $signedAgreement->agreement_number)
									{{ $signedAgreement->agreement_number }}
									@elseif($unsignedAgreement && $unsignedAgreement->agreement_number)
									{{ $unsignedAgreement->agreement_number }}
									@else
									-
									@endif
								</td>

								<td>{{ $candidate->candidate_code }}</td>
								<td>{{ $candidate->function?->function_name ?? '-' }}</td>
								<td>{{ $candidate->department?->department_name ?? '-' }}</td>
								<td>{{ $candidate->subDepartmentRef?->sub_department_name ?? '-' }}</td>
								<td>{{ $candidate->vertical?->vertical_name ?? '-' }}</td>
								<td>{{ $candidate->regionRef?->region_name ?? '-' }}</td>
								<td>{{ $candidate->businessUnit?->business_unit_name ?? '-' }}</td>
								<td>{{ $candidate->zoneRef?->zone_name ?? '-' }}</td>
								<td>{{ $candidate->work_location_hq ?? '-' }}</td>
								<td>{{ $candidate->cityMaster?->city_village_name ?? '-' }}</td>
								<td>{{ $candidate->workState?->state_name ?? '-' }}</td>
								<td>{{ $candidate->address_line_1 ?? '-' }}</td>
								<td>{{ $candidate->pin_code ?? '-' }}</td>
								<td>{{ $candidate->candidate_email ?? '-' }}</td>
								<td>{{ $candidate->mobile_no ?? '-' }}</td>
								<td>{{ $candidate->account_holder_name ?? '-' }}</td>
								<td>{{ $candidate->bank_account_no ?? '-' }}</td>
								<td>{{ $candidate->bank_ifsc ?? '-' }}</td>
								<td>{{ $candidate->pan_no ?? '-' }}</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>{{ $candidate->requisition_type ?? '-' }}</td>
								<td>{{ $candidate->reporting_to ?? '-' }}</td>
								<td>{{ $candidate->reportingManager?->emp_email ?? '-' }}</td>
								<td>{{ $candidate->aadhaar_no ?? '-' }}</td>
								<td>{{ $candidate->contract_start_date ? \Carbon\Carbon::parse($candidate->contract_start_date)->format('d-M-Y') : '-' }}</td>
								<td>{{ $candidate->contract_end_date ? \Carbon\Carbon::parse($candidate->contract_end_date)->format('d-M-Y') : '-' }}</td>

								<td>
									@if($candidate->final_status == 'A')
									<span class="badge bg-success">Active</span>
									@else
									<span class="badge bg-danger">Deactive</span>
									@endif
								</td>

								<td>₹ {{ number_format($candidate->remuneration_per_month ?? 0, 2) }}</td>
								<td>{{ $candidate->remarks ?? '-' }}</td>

								<td>{{ $unsignedAgreement?->created_at ? \Carbon\Carbon::parse($unsignedAgreement->created_at)->format('d-M-Y') : '-' }}</td>
								<td>{{ $unsignedAgreement?->courierDetails?->dispatch_date ? \Carbon\Carbon::parse($unsignedAgreement->courierDetails->dispatch_date)->format('d-M-Y') : '-' }}</td>
								<td>{{ $signedAgreement?->created_at ? \Carbon\Carbon::parse($signedAgreement->created_at)->format('d-M-Y') : '-' }}</td>
								<td>{{ $signedAgreement?->courierDetails?->dispatch_date ? \Carbon\Carbon::parse($signedAgreement->courierDetails->dispatch_date)->format('d-M-Y') : '-' }}</td>

								<!-- ✅ NEW COLUMN -->
								<td>
									@if($candidate->ledger_created)
									<span class="badge bg-success">Created</span>
									@else
									<span class="badge bg-warning text-dark">Pending</span>
									@endif
								</td>

							</tr>

							@empty
							<tr>
								<td colspan="40" class="text-center">No data found</td>
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
		function markLedgerCreated() {

			let ids = [];

			document.querySelectorAll('input[name="ids[]"]:checked')
				.forEach(el => ids.push(el.value));

			if (ids.length === 0) {
				alert('Select at least one');
				return;
			}

			fetch("{{ route('ledger.markCreated') }}", {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
						"X-CSRF-TOKEN": "{{ csrf_token() }}"
					},
					body: JSON.stringify({
						ids: ids
					})
				})
				.then(res => res.json())
				.then(() => location.reload());
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