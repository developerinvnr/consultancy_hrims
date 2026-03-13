@extends('layouts.guest')

@section('page-title', 'HR Admin Dashboard')

@section('content')
<div class="container-fluid">
	<!-- Page Header -->
	<div class="row mb-2">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0 fs-5">
					<i class="ri-dashboard-3-line me-2"></i>HR Dashboard
				</h4>
			</div>
		</div>
	</div>

	<!-- Compact Metrics Grid - Right Aligned Numbers -->
	<div class="row g-1 mb-3">
		<!-- Row 1 -->
		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Total Requisitions</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold">{{ $stats['total_requisitions'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Active Candidates</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold">{{ $stats['active_candidates'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10">Active TFA</p>
					</div>
					<div>
						<h5 class="mb-0 fw-bold text-success">
							{{ $stats['active_by_type']['TFA'] ?? 0 }}
						</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10">Active CB</p>
					</div>
					<div>
						<h5 class="mb-0 fw-bold text-info">
							{{ $stats['active_by_type']['CB'] ?? 0 }}
						</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10">Active Contractual</p>
					</div>
					<div>
						<h5 class="mb-0 fw-bold text-primary">
							{{ $stats['active_by_type']['Contractual'] ?? 0 }}
						</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">This Month</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold text-info">{{ $stats['this_month']['submissions'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Pending Verification</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold text-warning">{{ $stats['pending_verification'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Agreement Pending</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold text-warning">{{ $stats['agreement_pending'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Unsigned Agreement Created</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold text-info">{{ $stats['unsigned_uploaded'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		<!-- Row 2 -->
		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">Agreement Completed</p>
					</div>
					<div class="flex-shrink-0">
						<h5 class="mb-0 fw-bold text-success">{{ $stats['agreement_completed'] }}</h5>
					</div>
				</div>
			</div>
		</div>

		{{--@foreach($stats['requisition_by_type'] as $type => $count)
		<div class="col-xl-2 col-md-3 col-sm-4 col-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-1 d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<p class="text-muted mb-0 fs-10 text-truncate">{{ $type }}</p>
	</div>
	<div class="flex-shrink-0">
		<h5 class="mb-0 fw-bold">{{ $count }}</h5>
	</div>
</div>
</div>
</div>
@endforeach--}}

{{-- Avg Verify Time Card --}}
<div class="col-xl-2 col-md-3 col-sm-4 col-6">
	<div class="card border-0 shadow-sm h-100">
		<div class="card-body p-1 d-flex justify-content-between align-items-center">
			<div class="flex-grow-1">
				<p class="text-muted mb-0 fs-10 text-truncate">
					Avg Verify Time
					<small class="d-block text-muted fs-9">
						{{ $stats['verification_count'] }} processed
					</small>
				</p>
			</div>
			<div class="flex-shrink-0">
				<h5 class="mb-0 fw-bold">{{ $stats['avg_times']['verification_display'] }}</h5>
			</div>
		</div>
	</div>
</div>

{{-- Avg Approval Time Card --}}
<div class="col-xl-2 col-md-3 col-sm-4 col-6">
	<div class="card border-0 shadow-sm h-100">
		<div class="card-body p-1 d-flex justify-content-between align-items-center">
			<div class="flex-grow-1">
				<p class="text-muted mb-0 fs-10 text-truncate">
					Avg Time to Approval
					<small class="d-block text-muted fs-9">
						{{ $stats['approval_count'] }} with dates
					</small>
				</p>
			</div>
			<div class="flex-shrink-0">
				<h5 class="mb-0 fw-bold {{ $stats['avg_times']['approval_display'] == 'N/A' ? 'text-muted' : '' }}">
					{{ $stats['avg_times']['approval_display'] }}
				</h5>
			</div>
		</div>
	</div>
</div>
</div>

{{--<div class="d-flex align-items-center justify-content-between text-center">

	<div>
		<div class="fw-bold text-primary">{{ $stats['pipeline']['submission'] }}</div>
<small>Submitted</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-warning">{{ $stats['pipeline']['hr_verification'] }}</div>
	<small>HR Verify</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-info">{{ $stats['pipeline']['approval'] }}</div>
	<small>Approval</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-secondary">{{ $stats['pipeline']['agreement_pending'] }}</div>
	<small>Agreement</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-primary">{{ $stats['pipeline']['unsigned_uploaded'] }}</div>
	<small>Unsigned</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-success">{{ $stats['pipeline']['signed_uploaded'] }}</div>
	<small>Signed</small>
</div>

<i class="ri-arrow-right-line"></i>

<div>
	<div class="fw-bold text-dark">{{ $stats['pipeline']['completed'] }}</div>
	<small>Completed</small>
</div>

</div>--}}

<!-- Top Submitters & Departments (Side by Side) -->
<div class="row g-2 mb-2">
	<div class="col-md-6">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body p-2">
				<h6 class="mb-2 fs-6">Top Submitters (30d)</h6>
				@forelse($stats['top_submitters'] as $submitter)
				<div class="d-flex justify-content-between align-items-center mb-1 pb-1 {{ !$loop->last ? 'border-bottom' : '' }}">
					<span class="text-truncate fs-12">{{ $submitter->submitted_by_name }}</span>
					<span class="badge bg-primary fs-10">{{ $submitter->count }}</span>
				</div>
				@empty
				<p class="text-muted text-center mb-0 fs-12">No data</p>
				@endforelse
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-body p-2">
				<h6 class="mb-2 fs-6">Top Departments</h6>
				@forelse($stats['by_department'] as $dept)
				<div class="d-flex justify-content-between align-items-center mb-1 pb-1 {{ !$loop->last ? 'border-bottom' : '' }}">
					<span class="text-truncate fs-12">{{ $dept->department->department_name ?? 'N/A' }}</span>
					<span class="badge bg-info fs-10">{{ $dept->count }}</span>
				</div>
				@empty
				<p class="text-muted text-center mb-0 fs-12">No data</p>
				@endforelse
			</div>
		</div>
	</div>
</div>

@if(isset($expiry))
<div class="row mb-3">
	<div class="col-12">
		<div class="card border-0 shadow-sm">
			<div class="card-body p-2">

				<h6 class="mb-3 fs-6">
					<i class="ri-calendar-event-line me-1"></i>
					Contract Expiry
				</h6>

				<ul class="nav nav-tabs mb-3">

					<li class="nav-item">
						<a class="nav-link {{ request('exp_tab','exp30')=='exp30'?'active':'' }}"
							href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp30']) }}">
							< 30 Days
								</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{ request('exp_tab')=='exp60'?'active':'' }}"
							href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp60']) }}">
							30 - 60 Days
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{ request('exp_tab')=='exp90'?'active':'' }}"
							href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp90']) }}">
							60 - 90 Days
						</a>
					</li>
				</ul>

				<div class="tab-content">

					<div class="tab-pane fade {{ request('exp_tab','exp30')=='exp30' ? 'show active' : '' }}">
						@include('dashboard.partials.expiry-table', ['list'=>$expiry['lt_30_days']])
					</div>

					<div class="tab-pane fade {{ request('exp_tab')=='exp60' ? 'show active' : '' }}">
						@include('dashboard.partials.expiry-table', ['list'=>$expiry['days_30_60']])
					</div>

					<div class="tab-pane fade {{ request('exp_tab')=='exp90' ? 'show active' : '' }}">
						@include('dashboard.partials.expiry-table', ['list'=>$expiry['days_60_90']])
					</div>

				</div>

			</div>
		</div>
	</div>
</div>
@endif

<!-- Recent Requisitions Table -->
@if(isset($recent_requisitions))
<div class="row">
	<div class="col-12">
		<div class="card border-0 shadow-sm">
			<div class="card-body p-2">
				<h6 class="mb-2 fs-6">Recent Requisitions</h6>
				<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
					<ul class="nav nav-tabs mb-3 sticky-tabs">

						<li class="nav-item">
							<a class="nav-link {{ $req_tab=='status'?'active':'' }}"
								href="{{ request()->fullUrlWithQuery(['req_tab'=>'status']) }}">
								Status Wise
							</a>
						</li>

						<li class="nav-item">
							<a class="nav-link {{ $req_tab=='active'?'active':'' }}"
								href="{{ url()->current() }}?req_tab=active">
								Active
							</a>
						</li>

						<li class="nav-item">
							<a class="nav-link {{ $req_tab=='inactive'?'active':'' }}"
								href="{{ url()->current() }}?req_tab=inactive">
								Inactive
							</a>
						</li>

						<li class="nav-item">
							<a class="nav-link {{ $req_tab=='rejected'?'active':'' }}"
								href="{{ url()->current() }}?req_tab=rejected">
								Rejected
							</a>
						</li>
					</ul>

					@if($req_tab == 'status')
					<div class="row mb-3">

						<div class="col-md-3">
							<select id="statusFilter" class="form-select form-select-sm">

								<option value="">All Status</option>

								<option value="Pending HR Verification"
									{{ request('status_filter') == 'Pending HR Verification' ? 'selected' : '' }}>
									Pending HR Verification
								</option>

								<option value="Pending Approval"
									{{ request('status_filter') == 'Pending Approval' ? 'selected' : '' }}>
									Pending Approval
								</option>

								<option value="Agreement Pending"
									{{ request('status_filter') == 'Agreement Pending' ? 'selected' : '' }}>
									Agreement Pending
								</option>

								<option value="Unsigned Agreement Created"
									{{ request('status_filter') == 'Unsigned Agreement Created' ? 'selected' : '' }}>
									Unsigned Agreement Created
								</option>

								<option value="Signed Agreement Uploaded"
									{{ request('status_filter') == 'Signed Agreement Uploaded' ? 'selected' : '' }}>
									Signed Agreement Uploaded
								</option>

							</select>
						</div>

						<div class="col-md-3">
							<select id="actionFilter" class="form-select form-select-sm">

								<option value="">All Actions</option>

								<option value="process"
									{{ request('action_filter') == 'process' ? 'selected' : '' }}>
									Process Button
								</option>

								<option value="upload_signed"
									{{ request('action_filter') == 'upload_signed' ? 'selected' : '' }}>
									Upload Signed Agreement
								</option>

								<option value="receive_courier"
									{{ request('action_filter') == 'receive_courier' ? 'selected' : '' }}>
									Receive Courier
								</option>

							</select>
						</div>

						<div class="col-md-2">
							<button class="btn btn-sm btn-primary" id="applyFilter">
								Apply
							</button>
						</div>

					</div>
					@endif
					<table class="table table-sm table-hover mb-0">
						<thead class="sticky-top bg-white">
							<tr>
								<th class="fs-11">ID</th>
								<th class="fs-11">Candidate</th>
								<th class="fs-11">Email</th>
								<th class="fs-11">Type</th>
								<th class="fs-11">Status</th>
								<th class="fs-11">Remark</th>
								<th class="fs-11">Courier Status</th>
								<th class="fs-11">Date</th>
								<th class="fs-11">Actions</th>
							</tr>
						</thead>
						<tbody>
							@if($recent_requisitions->count() > 0)

							@foreach($recent_requisitions as $req)
							@php
							$isProcessed = $req->candidate ? true : false;
							$candidate = $req->candidate;
							$empStatus = $candidate->candidate_status ?? null;

							// Use the data we attached in the controller
							$signedAgreement = $req->signed_agreement ?? null;
							$courierDetails = $req->courier_details ?? null;
							@endphp
							<tr>
								<td class="fs-11">
									<span class="badge bg-secondary fs-10">{{ $req->requisition_id }}</span>
								</td>
								<td class="fs-11">
									{{ $req->candidate_name }}
								</td>
								<td class="fs-11">
									<small class="text-muted fs-9">{{ $req->candidate_email }}</small>
								</td>
								<td class="fs-11">
									<span class="badge bg-{{ $req->requisition_type == 'Contractual' ? 'primary' : ($req->requisition_type == 'TFA' ? 'success' : 'info') }} fs-10">
										{{ $req->requisition_type }}
									</span>
								</td>
								<td class="fs-11">
									@if($candidate && $candidate->candidate_status)

									@php
									$status = trim($candidate->candidate_status);

									$displayStatus = ($status === 'Unsigned Agreement Created')
									? 'Unsigned Agreement Created'
									: $status;

									$statusColors = [
									'Agreement Pending' => 'warning',
									'Unsigned Agreement Created' => 'info',
									'Signed Agreement Uploaded' => 'primary',
									'Agreement Completed' => 'secondary',
									'Active' => 'success',
									'Inactive' => 'danger',
									'Rejected' => 'danger'
									];
									@endphp

									<span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} fs-10">
										{{ $displayStatus }}
									</span>

									@if($candidate->candidate_code)
									<br>
									<small class="text-muted fs-9">{{ $candidate->candidate_code }}</small>
									@endif

									@else

									@switch($req->status)

									@case('Pending HR Verification')
									<span class="badge bg-warning fs-10">Pending HR Verification</span>
									@break

									@case('Correction Required')
									<span class="badge bg-danger fs-10">Correction Required</span>
									@break

									@case('Pending Approval')
									<span class="badge bg-info fs-10">Pending Approval</span>
									@break

									@case('Approved')
									<span class="badge bg-primary fs-10">Ready to Process</span>
									@break

									@case('Rejected')
									<span class="badge bg-danger fs-10">Rejected</span>


									@if($req->rejectedBy)
									<br>
									<small class="text-muted fs-9">
										By {{ $req->rejectedBy->name }}

										@if($req->rejectedBy->hasRole('hr_admin'))
										(HR Admin)
										@elseif($req->rejectedBy->emp_id == $req->approver_id)
										(Approver)
										@endif

									</small>
									@endif

									@break

									@default
									<span class="badge bg-secondary fs-10">{{ $req->status }}</span>

									@endswitch

									@endif
								</td>

								<!-- REMARK COLUMN -->
								<td class="fs-11">

									@if($req->status == 'Rejected' && $req->rejection_reason)

									<small class="text-danger"
										title="{{ $req->rejection_reason }}">
										{{ \Illuminate\Support\Str::limit($req->rejection_reason, 50) }}
									</small>

									@else

									<span class="text-muted fs-9">-</span>

									@endif

								</td>

								<!-- COURIER STATUS COLUMN -->
								<td class="fs-11">
									@if($courierDetails)
									@if($courierDetails->received_date)
									<span class="badge bg-success fs-10">
										<i class="ri-checkbox-circle-line me-1"></i> Received
									</span>
									<small class="d-block text-muted fs-9">
										{{ \Carbon\Carbon::parse($courierDetails->received_date)->format('d M Y') }}
									</small>
									@else
									<span class="badge bg-warning fs-10">
										<i class="ri-truck-line me-1"></i> Dispatched
									</span>
									<small class="d-block text-muted fs-9">
										{{ $courierDetails->courier_name }}<br>
										Docket: {{ $courierDetails->docket_number }}<br>
										Dispatch: {{ \Carbon\Carbon::parse($courierDetails->dispatch_date)->format('d M Y') }}
									</small>
									@endif
									@elseif($signedAgreement)
									<span class="badge bg-secondary fs-10">
										<i class="ri-time-line me-1"></i> Awaiting Dispatch
									</span>
									<small class="d-block text-muted fs-9">
										Signed on: {{ \Carbon\Carbon::parse($signedAgreement->created_at)->format('d M Y') }}
									</small>
									@else
									<span class="text-muted fs-9">N/A</span>
									@endif
								</td>

								<td class="fs-11">{{ $req->created_at->format('d-M') }}</td>
								<td>
									<div class="btn-group btn-group-sm" role="group">
										<a href="{{ route('hr-admin.applications.view', $req) }}"
											class="btn btn-outline-primary" title="View">
											<i class="ri-eye-line fs-10"></i>
										</a>

										@if($req->status === 'Approved' && !$isProcessed)
										<button type="button" class="btn btn-success process-btn"
											data-bs-toggle="modal" data-bs-target="#processModal"
											data-requisition-id="{{ $req->id }}"
											data-requisition-type="{{ $req->requisition_type }}"
											data-requisition-name="{{ $req->candidate_name }}"
											data-current-reporting="{{ $req->reporting_to }}"
											data-current-manager-id="{{ $req->reporting_manager_employee_id }}">
											<i class="ri-play-line fs-10"></i>
										</button>
										@elseif($req->status === 'Pending Approval')
										<span class="badge bg-info fs-9">Awaiting Approval</span>
										@endif

										<!-- COURIER RECEIVE BUTTON -->
										@if($courierDetails && !$courierDetails->received_date)
										<button type="button"
											class="btn btn-outline-success receive-courier-btn"
											data-bs-toggle="modal"
											data-bs-target="#receiveCourierModal"
											data-requisition-id="{{ $req->id }}"
											data-agreement-id="{{ $signedAgreement->id ?? '' }}"
											data-candidate-name="{{ $req->candidate_name }}"
											data-courier-name="{{ $courierDetails->courier_name }}"
											data-docket-number="{{ $courierDetails->docket_number }}"
											data-dispatch-date="{{ \Carbon\Carbon::parse($courierDetails->dispatch_date)->format('d M Y') }}"
											title="Mark as Received">
											<i class="ri-check-double-line fs-10"></i>
										</button>
										@endif

										@if($courierDetails && $courierDetails->received_date && !$candidate->file_created_date)

										<button
											class="btn btn-sm btn-success create-file-btn"
											data-candidate-id="{{ $candidate->id }}">
											<i class="ri-folder-add-line"></i>
										</button>

										@endif

										@if($candidate)
										@php
										$hasUnsigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
										->where('document_type', 'agreement')
										->where('sign_status', 'UNSIGNED')
										->exists();

										$hasSigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
										->where('document_type', 'agreement')
										->where('sign_status', 'SIGNED')
										->exists();

										$agreementNumber = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
										->where('document_type', 'agreement')
										->where('sign_status', 'UNSIGNED')
										->value('agreement_number');

										$hasEstamp = $candidate->agreementDocuments
										->where('document_type','estamp')
										->count() > 0;
										@endphp


										@if(!$hasEstamp)

										<button type="button"
											class="btn btn-sm btn-warning upload-estamp-btn"
											data-candidate-id="{{ $candidate->id }}"
											data-candidate-code="{{ $candidate->candidate_code }}"
											data-candidate-name="{{ $candidate->candidate_name }}">
											<i class="ri-file-upload-line"></i>
										</button>

										@endif


										@if($empStatus == "Active")
										<button
											type="button"
											class="btn btn-outline-danger end-contract-btn"
											data-bs-toggle="modal"
											data-bs-target="#endContractModal"
											data-candidate-id="{{ $candidate->id }}"
											data-candidate-name="{{ $candidate->candidate_name }}"
											title="End Contract">
											<i class="ri-user-unfollow-line"></i>
										</button>
										@endif

										@if($hasUnsigned && !$hasSigned)
										<button type="button"
											class="btn btn-outline-primary upload-signed-btn"
											data-candidate-id="{{ $candidate->id }}"
											data-candidate-code="{{ $candidate->candidate_code }}"
											data-candidate-name="{{ $candidate->candidate_name }}"
											data-agreement-number="{{ $agreementNumber }}">
											<i class="ri-upload-line fs-10"></i>
										</button>
										@endif

										@endif
									</div>
								</td>
							</tr>
							@endforeach

							@else

							<tr>
								<td colspan="9" class="text-center text-muted py-4">
									No requisitions found for the selected filter.
								</td>
							</tr>
							@endif


						</tbody>
					</table>
					@if($recent_requisitions instanceof \Illuminate\Pagination\LengthAwarePaginator)
					<div class="d-flex justify-content-end mt-3">
						{{ $recent_requisitions->links('pagination::bootstrap-5') }}
					</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endif
</div>

<!-- Process Modal -->
@include('hr.modals.process-modal')

<!-- Upload Signed Agreement Modal -->
@include('hr.modals.upload-signed-modal')

<!-- Receive Courier Modal -->
@include('hr.modals.receive-courier-modal')

<!-- End Contract Modal -->
@include('hr.modals.end-contract-modal')

<!-- upload estamp Modal -->
@include('hr.modals.upload-estamp-modal')
<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
	<div class="toast-container"></div>
</div>
@endsection

@push('scripts')

<script>
	window.routes = {
		uploadEstamp: "{{ route('hr-admin.master.upload-estamp', ['candidate'=>'CANDIDATE_ID']) }}",
		fileCreated: "{{ route('hr-admin.candidate.file-created') }}",
		getManagers: "{{ route('hr-admin.applications.get-reporting-managers','__ID__') }}",
		processModal: "{{ route('hr-admin.applications.process-modal') }}"
	};
</script>

<script src="{{ asset('assets/js/hr-common.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
	$(document).ready(function() {
		// Get CSRF token from meta tag
		const csrfToken = $('meta[name="csrf-token"]').attr('content');

		// Initialize Select2 for process modal
		$('#reporting_manager_employee_id').select2({
			theme: 'bootstrap-5',
			placeholder: '-- Select Reporting Manager --',
			allowClear: true,
			dropdownParent: $('#processModal'),
			width: '100%'
		});



		function loadSignedDocuments(candidateId) {
			$('#signedDocumentsList').html(`
					<div class="text-center">
						<div class="spinner-border spinner-border-sm" role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
						Loading documents...
					</div>
				`);

			$.ajax({
				url: `/hr-admin/candidate/${candidateId}/signed-documents`,
				type: 'GET',
				headers: {
					'X-CSRF-TOKEN': csrfToken
				},
				success: function(response) {
					if (response.success && response.documents.length > 0) {
						let html = '<div class="list-group">';
						response.documents.forEach(doc => {
							html += `
                    <label class="list-group-item">
                        <input class="form-check-input me-1 document-radio" 
                               type="radio" name="document_id" 
                               value="${doc.id}" data-file-url="${doc.file_url}">
                        <div>
                            <strong>${doc.agreement_number}</strong>
                            <small class="text-muted d-block">
                                Uploaded by: ${doc.uploaded_by === 'submitter' ? 'Candidate' : 'HR'} 
                                on ${doc.created_at}
                            </small>
                            <a href="${doc.file_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="ri-eye-line"></i> View
                            </a>
                        </div>
                    </label>`;
						});
						html += '</div>';
						$('#signedDocumentsList').html(html);

						// Enable radio selection
						$('.document-radio').on('change', function() {
							$('#verifySubmitBtn').prop('disabled', false);
						});
					} else {
						$('#signedDocumentsList').html(`
                    <div class="alert alert-warning">
                        <i class="ri-alert-line"></i> No signed agreements available for verification.
                        <br>
                        <small>The candidate hasn't uploaded any signed agreements yet.</small>
                    </div>
                `);
					}
				},
				error: function() {
					$('#signedDocumentsList').html(`
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line"></i> Failed to load documents.
                </div>
            `);
				}
			});
		}



		$('#applyFilter').on('click', function() {

			let status = $('#statusFilter').val();
			let action = $('#actionFilter').val();

			let url = new URL(window.location.href);

			// Always keep tab=status
			url.searchParams.set('req_tab', 'status');

			if (status) {
				url.searchParams.set('status_filter', status);
			} else {
				url.searchParams.delete('status_filter');
			}

			if (action) {
				url.searchParams.set('action_filter', action);
			} else {
				url.searchParams.delete('action_filter');
			}

			window.location.href = url.toString();
		});


	});
</script>
<style>
	.select2-container {
		z-index: 1065 !important;
		/* Higher than Bootstrap modal */
	}

	.bg-soft-success {
		background-color: #e6f7f0 !important;
	}
</style>
@endpush