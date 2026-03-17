@extends('layouts.guest')
@section('content')
<div class="container-fluid">
	<div class="row mb-2">
		<div class="col-12 d-flex justify-content-between align-items-center">
			<h4 class="mb-0">TAT Report (Action Wise)</h4>
			<a href="{{ route('reports.tat.export', request()->query()) }}"
				class="btn btn-sm btn-success">
				Export Excel
			</a>
		</div>
	</div>
	<div class="card shadow-sm">
		<div class="card-body p-0">
			<div class="card mb-2 shadow-sm">
				<div class="card-body">
					<form method="GET" action="{{ route('reports.tat') }}" class="row g-2 align-items-end">
						<div class="col-md-2">
							<label class="form-label form-label-sm">Financial Year</label>
							<select name="financial_year" class="form-select form-select-sm">
								<option value="2024-2025" {{ $financialYear=='2024-2025'?'selected':'' }}>2024-2025</option>
								<option value="2025-2026" {{ $financialYear=='2025-2026'?'selected':'' }}>2025-2026</option>
							</select>
						</div>

						<div class="col-md-1">
							<label class="form-label form-label-sm">Month</label>
							<select name="month" class="form-select form-select-sm">
								<option value="">All</option>
								<option value="4" {{ $month==4?'selected':'' }}>Apr</option>
								<option value="5" {{ $month==5?'selected':'' }}>May</option>
								<option value="6" {{ $month==6?'selected':'' }}>Jun</option>
								<option value="7" {{ $month==7?'selected':'' }}>Jul</option>
								<option value="8" {{ $month==8?'selected':'' }}>Aug</option>
								<option value="9" {{ $month==9?'selected':'' }}>Sep</option>
								<option value="10" {{ $month==10?'selected':'' }}>Oct</option>
								<option value="11" {{ $month==11?'selected':'' }}>Nov</option>
								<option value="12" {{ $month==12?'selected':'' }}>Dec</option>
								<option value="1" {{ $month==1?'selected':'' }}>Jan</option>
								<option value="2" {{ $month==2?'selected':'' }}>Feb</option>
								<option value="3" {{ $month==3?'selected':'' }}>Mar</option>
							</select>
						</div>

						<div class="col-md-2">
							<label class="form-label form-label-sm">Department</label>
							<select name="department_id" class="form-select form-select-sm">
								<option value="">All</option>
								@foreach($departments as $dept)
								<option value="{{ $dept->id }}"
									{{ $departmentId==$dept->id?'selected':'' }}>
									{{ $dept->department_name }}
								</option>
								@endforeach
							</select>
						</div>

						<div class="col-md-2">
							<label class="form-label form-label-sm">Requisition Type</label>
							<select name="requisition_type" class="form-select form-select-sm">
								<option value="">All</option>
								<option value="Contractual" {{ $requisitionType=='Contractual'?'selected':'' }}>Contractual</option>
								<option value="TFA" {{ $requisitionType=='TFA'?'selected':'' }}>TFA</option>
								<option value="CB" {{ $requisitionType=='CB'?'selected':'' }}>CB</option>
							</select>
						</div>

						<div class="col-md-2">
							<label class="form-label form-label-sm">Status</label>
							<select name="status" class="form-select form-select-sm">
								<option value="">All</option>
								<option value="Pending HR Verification">Pending HR Verification</option>
								<option value="Pending Approval">Pending Approval</option>
								<option value="Approved">Approved</option>
								<option value="Rejected">Rejected</option>
							</select>
						</div>
						<div class="col-md-1">
							<button class="btn btn-sm btn-primary w-100">
								Filter
							</button>
						</div>
					</form>
				</div>
			</div>

			<div class="row mb-3">

				<!-- HR Stage -->
				<div class="col-md-4">
					<div class="card shadow-sm border-start border-success border-3">
						<div class="card-body p-2">
							<h6 class="text-success mb-1">HR Verification</h6>
							<small>Total: {{ $hrSummary['total'] }}</small><br>
							<small>Avg: {{ $hrSummary['avg'] }} Days</small><br>
							<small>≤1 Day: {{ $hrSummary['within_1'] }}</small><br>
							<small>1-3 Days: {{ $hrSummary['within_3'] }}</small><br>
							<small>>3 Days: {{ $hrSummary['above_3'] }}</small>
						</div>
					</div>
				</div>

				<!-- Approval Stage -->
				<div class="col-md-4">
					<div class="card shadow-sm border-start border-warning border-3">
						<div class="card-body p-2">
							<h6 class="text-warning mb-1">Approval</h6>
							<small>Total: {{ $approvalSummary['total'] }}</small><br>
							<small>Avg: {{ $approvalSummary['avg'] }} Days</small><br>
							<small>≤1 Day: {{ $approvalSummary['within_1'] }}</small><br>
							<small>1-3 Days: {{ $approvalSummary['within_3'] }}</small><br>
							<small>>3 Days: {{ $approvalSummary['above_3'] }}</small>
						</div>
					</div>
				</div>

				<!-- Processing Stage -->
				<div class="col-md-4">
					<div class="card shadow-sm border-start border-danger border-3">
						<div class="card-body p-2">
							<h6 class="text-danger mb-1">Processing</h6>
							<small>Total: {{ $processingSummary['total'] }}</small><br>
							<small>Avg: {{ $processingSummary['avg'] }} Days</small><br>
							<small>≤1 Day: {{ $processingSummary['within_1'] }}</small><br>
							<small>1-3 Days: {{ $processingSummary['within_3'] }}</small><br>
							<small>>3 Days: {{ $processingSummary['above_3'] }}</small>
						</div>
					</div>
				</div>

			</div>

			<div class="table-responsive">
				<table class="table table-bordered table-hover table-sm mb-0">
					<thead class="table-light">
						<tr>
							<th>#</th>
							<th>Req ID</th>
							<th>Candidate</th>
							<th>Submission</th>
							<th>HR Verified</th>
							<th>Approval</th>
							<th>Processing</th>
							<th class="text-center">HR TAT</th>
							<th class="text-center">Approval TAT</th>
							<th class="text-center">Processing TAT</th>
							<th class="text-center">Total TAT</th>
						</tr>
					</thead>
					<tbody>
						@foreach($records as $index => $row)
						@php

						$hrTat = ($row->submission_date && $row->hr_verification_date)
						? ceil(
						\Carbon\Carbon::parse($row->submission_date)
						->diffInSeconds(\Carbon\Carbon::parse($row->hr_verification_date)) / 86400
						)
						: null;

						$approvalTat = ($row->hr_verification_date && $row->approval_date)
						? ceil(
						\Carbon\Carbon::parse($row->hr_verification_date)
						->diffInSeconds(\Carbon\Carbon::parse($row->approval_date)) / 86400
						)
						: null;

						$processTat = ($row->approval_date && $row->processing_date)
						? ceil(
						\Carbon\Carbon::parse($row->approval_date)
						->diffInSeconds(\Carbon\Carbon::parse($row->processing_date)) / 86400
						)
						: null;

						$totalTat = ($row->submission_date && $row->processing_date)
						? ceil(
						\Carbon\Carbon::parse($row->submission_date)
						->diffInSeconds(\Carbon\Carbon::parse($row->processing_date)) / 86400
						)
						: null;

						@endphp

						<tr>
							<td>{{ $records->firstItem() + $index }}</td>
							<td>
								<span class="badge bg-secondary">
									{{ $row->requisition_id }}
								</span>
							</td>
							<td>{{ $row->candidate_name }}</td>
							<td>{{ $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('d-M-Y') : '-' }}</td>
							<td>{{ $row->hr_verification_date ? \Carbon\Carbon::parse($row->hr_verification_date)->format('d-M-Y') : '-' }}</td>
							<td>{{ $row->approval_date ? \Carbon\Carbon::parse($row->approval_date)->format('d-M-Y') : '-' }}</td>
							<td>{{ $row->processing_date ? \Carbon\Carbon::parse($row->processing_date)->format('d-M-Y') : '-' }}</td>

							<td class="text-center">
								@if($hrTat)
								<span class="badge 
								@if($hrTat <= 1) bg-success
								@elseif($hrTat <= 3) bg-warning
								@else bg-danger
								@endif
								">
									{{ $hrTat }} Days
								</span>
								@else
								-
								@endif
							</td>

							<td class="text-center">
								@if($approvalTat)
								<span class="badge 
								@if($approvalTat <= 1) bg-success
								@elseif($approvalTat <= 3) bg-warning
								@else bg-danger
								@endif
								">
									{{ $approvalTat }} Days
								</span>
								@else
								-
								@endif
							</td>
							<td class="text-center">
								@if($processTat)
								<span class="badge 
									@if($processTat <= 1) bg-success
									@elseif($processTat <= 3) bg-warning
									@else bg-danger
									@endif
									">
									{{ $processTat }} Days
								</span>
								@else
								-
								@endif
							</td>

							<td class="text-center">
								@if($totalTat)
								<span class="badge bg-secondary">
									{{ $totalTat }} Days
								</span>
								@else
								-
								@endif
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>

			</div>
		</div>
		<div class="card-footer d-flex justify-content-end">
			{{ $records->links('pagination::bootstrap-5') }}
		</div>
	</div>
</div>
@endsection