@extends('layouts.guest')

@section('page-title', 'HR Admin Dashboard')

@section('content')
<div class="container-fluid">
	<!-- Page Header -->
	<div class="row mb-0">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0">HR Admin Dashboard</h4>
				<div class="page-title-right">
					<ol class="breadcrumb m-0">
						<li class="breadcrumb-item active">HR Admin Dashboard</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Welcome Message -->
	<div class="row mb-0">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Welcome, {{ Auth::user()->name }}!</h5>
					<p class="card-text">HR Admin Dashboard - Manage employee requisitions and agreements</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Compact HR Statistics -->
	<div class="row g-2 mb-3">

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Pending Verification</small>
						<div class="fw-bold">{{ $stats['pending_verification'] }}</div>
					</div>
					<i class="ri-time-line text-warning fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Pending Approval</small>
						<div class="fw-bold">{{ $stats['pending_approval'] }}</div>
					</div>
					<i class="ri-file-paper-line text-info fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Agreement Pending</small>
						<div class="fw-bold">{{ $stats['agreement_pending'] }}</div>
					</div>
					<i class="ri-file-text-line text-primary fs-5"></i>
				</div>
			</div>
		</div>

		<div class="col-md-3 col-6">
			<div class="card border-0 shadow-sm">
				<div class="card-body py-2 d-flex justify-content-between align-items-center">
					<div>
						<small class="text-muted">Total Candidates</small>
						<div class="fw-bold">{{ $stats['total_candidates'] }}</div>
					</div>
					<i class="ri-user-line text-success fs-5"></i>
				</div>
			</div>
		</div>

	</div>


	<!-- Quick Actions -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Quick Actions</h5>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-3 mb-3">
							<a href="{{ route('hr-admin.applications.new') }}" class="btn btn-primary w-100">
								<i class="ri-file-list-line me-2"></i> New Applications
								@if($stats['pending_verification'] > 0)
								<span class="badge bg-white text-primary ms-1">{{ $stats['pending_verification'] }}</span>
								@endif
							</a>
						</div>
						<div class="col-md-3 mb-3">
							<a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-success w-100">
								<i class="ri-check-double-line me-2"></i> Approved
								@if($stats['approved'] > 0)
								<span class="badge bg-white text-success ms-1">{{ $stats['approved'] }}</span>
								@endif
							</a>
						</div>
						<div class="col-md-3 mb-3">
							<a href="{{ route('hr-admin.agreement.list') }}" class="btn btn-info w-100">
								<i class="ri-file-text-line me-2"></i> Agreements
								@if($stats['agreement_pending'] > 0)
								<span class="badge bg-white text-info ms-1">{{ $stats['agreement_pending'] }}</span>
								@endif
							</a>
						</div>
						<div class="col-md-3 mb-3">
							<a href="{{ route('hr-admin.master.index') }}" class="btn btn-warning w-100">
								<i class="ri-database-2-line me-2"></i> Master Data
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Requisitions -->
	@if(isset($recent_requisitions) && $recent_requisitions->count() > 0)
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Recent Requisitions</h5>
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
									<td>
										{{ $req->candidate_email }}
									</td>
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
											<a href="{{ route('hr-admin.applications.view', $req) }}"
												class="btn btn-sm btn-outline-primary" title="View Details">
												<i class="ri-eye-line"></i>
											</a>

											<!-- Upload Unsigned Agreement Button -->
											@if($req->status == 'Agreement Pending')
											@php
											$candidateForUpload = \App\Models\CandidateMaster::where('requisition_id', $req->id)->first();
											@endphp
											@if($candidateForUpload)
											<button type="button"
												class="btn btn-sm btn-outline-success upload-unsigned-btn"
												data-candidate-id="{{ $candidateForUpload->id }}"
												data-candidate-code="{{ $candidateForUpload->candidate_code }}"
												data-candidate-name="{{ $candidateForUpload->candidate_name }}"
												title="Upload Unsigned Agreement">
												<i class="ri-file-upload-line"></i>
											</button>
											@endif
											@endif

											<!-- Upload Signed Agreement Button (from email) -->
											@if($req->status == 'Awaiting Signed Agreement')
											@php
											$candidateForSigned = \App\Models\CandidateMaster::where('requisition_id', $req->id)->first();
											@endphp
											@if($candidateForSigned)
											<button type="button"
												class="btn btn-sm btn-outline-info upload-signed-btn"
												data-candidate-id="{{ $candidateForSigned->id }}"
												data-candidate-code="{{ $candidateForSigned->candidate_code }}"
												data-candidate-name="{{ $candidateForSigned->candidate_name }}"
												title="Upload Signed Agreement (from email)">
												<i class="ri-mail-send-line"></i>
											</button>
											@endif
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
</div>

<!-- HR Admin Modals -->
<div class="modal fade" id="uploadUnsignedModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Upload Unsigned Agreement</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<form id="uploadUnsignedForm" enctype="multipart/form-data">
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control" id="candidateInfo" readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">Agreement Number *</label>
						<input type="text" class="form-control" name="agreement_number" required
							placeholder="Enter agreement number" maxlength="100">
					</div>

					<div class="mb-3">
						<label class="form-label">Agreement File (PDF) *</label>
						<input type="file" class="form-control" name="agreement_file"
							accept=".pdf" required>
						<small class="text-muted">Maximum file size: 10MB</small>
					</div>

					<input type="hidden" name="candidate_id" id="unsignedCandidateId">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success">Upload Agreement</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="uploadSignedModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Upload Signed Agreement (from Email)</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<form id="uploadSignedEmailForm" enctype="multipart/form-data">
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control" id="signedCandidateInfo" readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">Agreement Number *</label>
						<input type="text" class="form-control" name="agreement_number" required
							placeholder="Enter agreement number" maxlength="100">
					</div>

					<div class="mb-3">
						<label class="form-label">Signed Agreement File (PDF) *</label>
						<input type="file" class="form-control" name="agreement_file"
							accept=".pdf" required>
						<small class="text-muted">Maximum file size: 10MB</small>
					</div>

					<input type="hidden" name="candidate_id" id="signedCandidateId">
					<input type="hidden" name="source" value="email">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-info">Upload Signed Agreement</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
	<div class="toast-container"></div>
</div>
@endsection

@section('script_section')
<script>
	$(document).ready(function() {
		// Get CSRF token from meta tag
		const csrfToken = $('meta[name="csrf-token"]').attr('content');

		// Upload Unsigned Agreement Modal
		$('.upload-unsigned-btn').on('click', function() {
			const candidateId = $(this).data('candidate-id');
			const candidateCode = $(this).data('candidate-code');
			const candidateName = $(this).data('candidate-name');

			$('#candidateInfo').val(`${candidateCode} - ${candidateName}`);
			$('#unsignedCandidateId').val(candidateId);
			$('#uploadUnsignedModal').modal('show');
		});

		// Upload Signed Agreement Modal (from email)
		$('.upload-signed-btn').on('click', function() {
			const candidateId = $(this).data('candidate-id');
			const candidateCode = $(this).data('candidate-code');
			const candidateName = $(this).data('candidate-name');

			$('#signedCandidateInfo').val(`${candidateCode} - ${candidateName}`);
			$('#signedCandidateId').val(candidateId);
			$('#uploadSignedModal').modal('show');
		});

		// Form submission handlers
		$('#uploadUnsignedForm').on('submit', function(e) {
			e.preventDefault();
			submitAgreementForm($(this), '/hr-admin/agreement/{candidate}/upload-unsigned');
		});

		$('#uploadSignedEmailForm').on('submit', function(e) {
			e.preventDefault();
			submitAgreementForm($(this), '/hr-admin/agreement/{candidate}/upload-signed');
		});

		function submitAgreementForm(form, baseUrl) {
			const candidateId = form.find('input[name="candidate_id"]').val();
			const url = baseUrl.replace('{candidate}', candidateId);
			const formData = new FormData(form[0]);

			$.ajax({
				url: url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				headers: {
					'X-CSRF-TOKEN': csrfToken
				},
				beforeSend: function() {
					form.find('button[type="submit"]').prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Processing...');
				},
				success: function(response) {
					if (response.success) {
						showToast('success', response.message);
						setTimeout(() => {
							window.location.reload();
						}, 1500);
					} else {
						showToast('error', response.message || 'An error occurred');
						form.find('button[type="submit"]').prop('disabled', false).html('Upload Agreement');
					}
				},
				error: function(xhr) {
					let errorMessage = 'Failed to upload. Please try again.';
					if (xhr.responseJSON && xhr.responseJSON.errors) {
						errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
					} else if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}
					showToast('error', errorMessage);
					form.find('button[type="submit"]').prop('disabled', false).html('Upload Agreement');
				}
			});
		}

		function showToast(type, message) {
			const toast = `<div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

			$('.toast-container').append(toast);
			$('.toast').last().toast('show');

			setTimeout(() => {
				$('.toast').last().remove();
			}, 5000);
		}
	});
</script>
@endsection