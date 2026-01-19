@extends('layouts.guest')

@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">
	<!-- Page Header -->
	<div class="row mb-0">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0">Dashboard</h4>
				<div class="page-title-right">
					<ol class="breadcrumb m-0">
						<li class="breadcrumb-item active">Dashboard</li>
					</ol>
				</div>
			</div>
		</div>
	</div>
	<!-- Compact User Statistics -->
	<div class="row g-2 mb-0">
		<!-- Approver Statistics (if user is an approver) -->
		@if($is_approver)
		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Assigned to Me</small>
						<div class="fw-bold">{{ $approval_stats['total_assigned'] ?? 0 }}</div>
						<small class="text-muted">Total requisitions assigned</small>
					</div>
					<i class="ri-inbox-line text-primary fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Pending Review</small>
						<div class="fw-bold">{{ $approval_stats['pending'] ?? 0 }}</div>
						<small class="text-muted">Awaiting your action</small>
					</div>
					<i class="ri-time-line text-warning fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Approved by Me</small>
						<div class="fw-bold">{{ $approval_stats['total_approved'] ?? 0 }}</div>
						<small class="text-muted">With approval date</small>
					</div>
					<i class="ri-thumb-up-line text-success fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Rejected by Me</small>
						<div class="fw-bold">{{ $approval_stats['total_rejected'] ?? 0 }}</div>
						<small class="text-muted">With rejection date</small>
					</div>
					<i class="ri-thumb-down-line text-danger fs-5"></i>
				</div>
			</div>
		</div>
		@endif

		<!-- Separator if both approver and submitter -->
		@if($is_approver && $user_stats['total_submissions'] > 0)
		<div class="col-12 mt-3">
			<hr>
			<h6 class="text-muted mb-3">My Submissions Statistics</h6>
		</div>
		@endif

		<!-- Submitter Statistics (if user has submissions) -->
		@if($user_stats['total_submissions'] > 0)

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Total Submissions</small>
						<div class="fw-bold fs-6">{{ $user_stats['total_submissions'] ?? 0 }}</div>
					</div>
					<i class="ri-file-upload-line text-info fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Approved</small>
						<div class="fw-bold fs-6">{{ $user_stats['approved'] ?? 0 }}</div>
					</div>
					<i class="ri-check-double-line text-success fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Agreement Pending</small>
						<div class="fw-bold fs-6">{{ $user_stats['agreement_pending'] ?? 0 }}</div>
					</div>
					<i class="ri-file-text-line text-warning fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Agreement Completed</small>
						<div class="fw-bold fs-6">{{ $user_stats['agreement_completed'] ?? 0 }}</div>
					</div>
					<i class="ri-file-check-line text-primary fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Pending HR</small>
						<div class="fw-bold fs-6">{{ $user_stats['pending_hr_verification'] ?? 0 }}</div>
					</div>
					<i class="ri-user-search-line text-secondary fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Correction Required</small>
						<div class="fw-bold fs-6">{{ $user_stats['correction_required'] ?? 0 }}</div>
					</div>
					<i class="ri-edit-line text-danger fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Rejected</small>
						<div class="fw-bold fs-6">{{ $user_stats['rejected'] ?? 0 }}</div>
					</div>
					<i class="ri-close-circle-line text-dark fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-1 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Unsigned Uploaded</small>
						<div class="fw-bold fs-6">{{ $user_stats['unsigned_uploaded'] ?? 0 }}</div>
					</div>
					<i class="ri-file-upload-line text-info fs-5"></i>
				</div>
			</div>
		</div>

		@endif

	</div>


	<!-- Pending Approvals Section (if user is approver) -->
	@if($is_approver && $pending_approvals->count() > 0)
	<div class="row mb-0">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<div class="d-flex justify-content-between align-items-center">
						<h5 class="card-title mb-0">Pending Approvals</h5>
						<span class="badge bg-warning">{{ $pending_approvals->count() }} pending</span>
					</div>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr>
									<th>Req ID</th>
									<th>Candidate</th>
									<th>Email</th>
									<th>Department</th>
									<th>Type</th>
									<th>HR Verified On</th>
									<th>Remuneration</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								@foreach($pending_approvals as $requisition)
								<tr>
									<td>{{ $requisition->requisition_id }}</td>
									<td>
										<div>{{ $requisition->candidate_name }}</div>

									</td>
									<td>{{ $requisition->candidate_email }}</td>
									<td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
									<td>
										<span class="badge bg-{{ 
                                                $requisition->requisition_type == 'Contractual' ? 'primary' : 
                                                ($requisition->requisition_type == 'TFA' ? 'success' : 'info') 
                                            }}">
											{{ $requisition->requisition_type }}
										</span>
									</td>
									<td>
										{{ $requisition->hr_verification_date->format('d-M-Y') }}
										<br>
										<small class="text-muted">{{ $requisition->hr_verified_id }}</small>
									</td>
									<td>₹{{ number_format($requisition->remuneration_per_month, 2) }}</td>
									<td>
										<div class="btn-group btn-group-sm">
											<a href="{{ route('approver.requisition.view', $requisition) }}"
												class="btn btn-primary" title="Review">
												<i class="ri-eye-line"></i> Review
											</a>

											<!-- Quick Approve -->
											<form action="{{ route('approver.requisition.approve', $requisition) }}"
												method="POST" class="d-inline">
												@csrf
												<input type="hidden" name="approver_remarks" value="Approved via dashboard">
												<button type="submit"
													class="btn btn-success"
													title="Quick Approve">
													<i class="ri-check-line"></i>
												</button>
											</form>

											<!-- Quick Reject -->
											<button type="button"
												class="btn btn-danger quick-reject-btn"
												data-req-id="{{ $requisition->requisition_id }}"
												data-req-route="{{ route('approver.requisition.reject', $requisition) }}">
												<i class="ri-close-line"></i>
											</button>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	@endif


	<!-- Recent Requisitions -->
	@if(isset($recent_requisitions) && $recent_requisitions->count() > 0)
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">
						@if($is_approver)
						Recent Requisitions & Approvals
						@else
						My Recent Requisitions
						@endif
					</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Requisition ID</th>
									<th>Candidate</th>
									<th>Email</th>
									<th>Type</th>
									<th>Status</th>
									<th>Submitted By</th>
									<th>Date</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								@foreach($recent_requisitions as $req)
								<tr>
									<td>
										<span class="badge bg-secondary">{{ $req->requisition_id }}</span>
									</td>
									<td>
										<div>{{ $req->candidate_name }}</div>

									</td>
									<td>{{ $req->candidate_email }}</td>
									<td>
										<span class="badge bg-{{ $req->requisition_type == 'Contractual' ? 'primary' : ($req->requisition_type == 'TFA' ? 'success' : 'info') }}">
											{{ $req->requisition_type }}
										</span>
									</td>
									<td>
										@php
										$statusColors = [
										'Pending HR Verification' => 'warning',
										'Pending Approval' => 'info',
										'Approved' => 'success',
										'Correction Required' => 'danger',
										'Processed' => 'secondary',
										'Rejected' => 'dark',
										'Hr Verified' => 'success',
										'Unsigned Agreement Uploaded' => 'primary',
										'Agreement Completed' => 'success'
										];
										@endphp
										<span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}">
											{{ $req->status }}
										</span>
									</td>
									<td>{{ $req->submittedBy->name ?? 'N/A' }}</td>
									<td>{{ $req->created_at->format('d-M-Y') }}</td>
									<td>
										<div class="btn-group" role="group">
											<!-- User is submitter -->
											@if(Auth::user()->id == $req->submitted_by_user_id)
											<a href="{{ route('requisitions.show', $req) }}"
												class="btn btn-sm btn-outline-secondary" title="View">
												<i class="ri-eye-line"></i>
											</a>

											<!-- View/Download Unsigned Agreement -->
											@if($req->status == 'Unsigned Agreement Uploaded')
											<a href="{{ route('submitter.agreement.view', $req) }}"
												class="btn btn-sm btn-outline-primary" title="View Agreement">
												<i class="ri-file-text-line"></i>
											</a>
											@endif

											<!-- View Completed Agreement -->
											@if($req->status == 'Agreement Completed')
											<a href="{{ route('submitter.agreement.view', $req) }}"
												class="btn btn-sm btn-outline-success" title="View Completed Agreement">
												<i class="ri-check-double-line"></i>
											</a>
											@endif

											<!-- Correction Required -->
											@if($req->status == 'Correction Required')
											<a href="{{ route('requisitions.edit', $req) }}"
												class="btn btn-sm btn-outline-warning" title="Correct Application">
												<i class="ri-edit-line"></i>
											</a>
											@endif

											<!-- User is approver -->
											@elseif($req->approver_id == Auth::user()->employee_id && $req->status == 'Pending Approval')
											<a href="{{ route('approver.requisition.view', $req) }}"
												class="btn btn-sm btn-outline-warning" title="Review">
												<i class="ri-search-eye-line"></i>
											</a>

											<!-- Others -->
											@else
											<a href="{{ route('requisitions.show', $req) }}"
												class="btn btn-sm btn-outline-secondary" title="View">
												<i class="ri-eye-line"></i>
											</a>
											@endif
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	@endif

	<!-- Quick Create Button -->
	{{--<div class="row mt-3">
		<div class="col-12">
			<div class="card">
				<div class="card-body text-center">
					<h5 class="card-title mb-3">Need to create a new requisition?</h5>
					<a href="{{ route('requisitions.create', 'Contractual') }}" class="btn btn-primary me-2">
	<i class="ri-file-add-line me-1"></i> Create Contractual Requisition
	</a>
	<a href="{{ route('requisitions.create', 'TFA') }}" class="btn btn-success me-2">
		<i class="ri-file-add-line me-1"></i> Create TFA Requisition
	</a>
	<a href="{{ route('requisitions.create', 'CB') }}" class="btn btn-info">
		<i class="ri-file-add-line me-1"></i> Create CB Requisition
	</a>
</div>
</div>
</div>
</div> --}}
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="rejectForm" method="POST">
				@csrf
				<div class="modal-header">
					<h5 class="modal-title">Quick Reject</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Requisition ID: <strong id="rejectReqId"></strong></p>
					<div class="mb-3">
						<label for="rejection_reason" class="form-label">Reason for Rejection *</label>
						<textarea class="form-control" id="rejection_reason" name="rejection_reason"
							rows="3" placeholder="Please provide reason..." required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger">Reject</button>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection

@section('script_section')
<script>
	$(document).ready(function() {
		// Quick Reject Modal
		$('.quick-reject-btn').on('click', function() {
			const reqId = $(this).data('req-id');
			const route = $(this).data('req-route');

			$('#rejectReqId').text(reqId);
			$('#rejectForm').attr('action', route);
			$('#rejectModal').modal('show');
		});
	});
</script>
@endsection