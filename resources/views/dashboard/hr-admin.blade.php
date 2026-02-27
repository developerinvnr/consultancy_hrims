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
						<p class="text-muted mb-0 fs-10 text-truncate">Unsigned Uploaded</p>
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

		@foreach($stats['requisition_by_type'] as $type => $count)
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
		@endforeach

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

	<!-- Recent Requisitions Table -->
	@if(
    isset($active_requisitions) ||
    isset($inactive_requisitions) ||
    isset($status_requisitions)
)
	<div class="row">
		<div class="col-12">
			<h6 class="mb-2 fs-6">Recent Requisitions</h6>

			<!-- Tabs -->
			<ul class="nav nav-tabs mb-3">

				<li class="nav-item">
					<button class="nav-link active"
						data-bs-toggle="tab"
						data-bs-target="#hrActiveTab">
						Active
					</button>
				</li>

				<li class="nav-item">
					<button class="nav-link"
						data-bs-toggle="tab"
						data-bs-target="#hrInactiveTab">
						Inactive
					</button>
				</li>

				<li class="nav-item">
					<button class="nav-link"
						data-bs-toggle="tab"
						data-bs-target="#hrStatusTab">
						Status-Wise
					</button>
				</li>

			</ul>


			<div class="tab-content">

				<!-- ACTIVE -->
				<div class="tab-pane fade show active" id="hrActiveTab">

					@include('dashboard.partials.requisition-table', [
					'requisitions' => $active_requisitions
					])

				</div>


				<!-- INACTIVE -->
				<div class="tab-pane fade" id="hrInactiveTab">

					@include('dashboard.partials.requisition-table', [
					'requisitions' => $inactive_requisitions
					])

				</div>


				<!-- STATUS -->
				<div class="tab-pane fade" id="hrStatusTab">

					@include('dashboard.partials.requisition-table', [
					'requisitions' => $status_requisitions
					])

				</div>

			</div>
		</div>
	</div>
	@endif
</div>

<!-- Process Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Process Approved Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="processForm" action="" method="POST">
				@csrf
				<div class="modal-body">
					<input type="hidden" name="requisition_id" id="modalRequisitionId">

					<div class="row">
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Party</label>
								<input type="text" class="form-control form-control-sm" id="modalCandidateName" readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Current Reporting Manager</label>
								<input type="text" class="form-control form-control-sm" id="currentReporting" readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Current Reporting ID</label>
								<input type="text" class="form-control form-control-sm" id="currentManagerId" readonly>
							</div>
						</div>
					</div>
					<h6>Change Reporting Manager</h6>
					<div class="mb-3">
						<label for="reporting_manager_employee_id" class="form-label-sm">Reporting Manager *</label>
						<select class="form-select form-select-sm select2-modal" id="reporting_manager_employee_id"
							name="reporting_manager_employee_id" required>
							<option value="">-- Select Reporting Manager --</option>
							<!-- Options will be populated via AJAX -->
						</select>
						<small class="text-muted">Select the reporting manager from the department hierarchy</small>
					</div>

					<div class="row">
						<div class="col-md-4">
							<div class="mb-3">
								<label for="reporting_to" class="form-label">Reporting To Name *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_to"
									name="reporting_to" required readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label for="reporting_manager_id" class="form-label-sm">Reporting Manager ID *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_manager_id"
									name="reporting_manager_id" required readonly>
							</div>
						</div>

						<div class="col-md-4">
							<label for="team_id" class="form-label small">
								Team <span class="text-danger">*</span>
							</label>

							<select name="team_id"
								id="team_id"
								class="form-select form-select-sm"
								required>

								<option value="">Select Team</option>
								<option value="1">BTS-RnD FCzzz</option>
								<option value="2">Contractual</option>
								<option value="3">Marketing</option>
								<option value="4">PD VC+FC</option>
								<option value="5">Production VC KushDutt Sir</option>
								<option value="6">Production VC</option>
								<option value="7">QA VC+FC</option>
								<option value="8">RnD VC</option>
								<option value="9">Sales (P Srinivas Sir) 2</option>
								<option value="10">Sales (P Srinivas Sir)</option>
								<option value="11">TFA-CB</option>
							</select>

						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success">
						<i class="ri-save-line me-1"></i> Generate Party Code & Process
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Upload Unsigned Agreement Modal -->
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

<!-- Upload Signed Agreement Modal -->
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
						<input type="text" class="form-control" id="signedAgreementNumber" name="agreement_number" required
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


<!-- Receive Courier Modal -->
<div class="modal fade" id="receiveCourierModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Mark Courier as Received</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<form id="receiveCourierForm">
				@csrf
				<div class="modal-body">
					<div class="alert alert-info">
						<i class="ri-information-line me-2"></i>
						Confirm that the courier has been received by the candidate.
					</div>

					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control" id="receiveCandidateName" readonly>
					</div>

					<div class="mb-3 bg-light p-2 rounded">
						<label class="form-label">Courier Details</label>
						<div class="row small">
							<div class="col-6">
								<strong>Courier:</strong> <span id="receiveCourierName"></span>
							</div>
							<div class="col-6">
								<strong>Docket:</strong> <span id="receiveDocketNumber"></span>
							</div>
							<div class="col-6 mt-1">
								<strong>Dispatch Date:</strong> <span id="receiveDispatchDate"></span>
							</div>
						</div>
					</div>

					<div class="mb-3">
						<label class="form-label">Received Date <span class="text-danger">*</span></label>
						<input type="date"
							class="form-control"
							name="received_date"
							id="receivedDate"
							value="{{ date('Y-m-d') }}"
							readonly
							style="background-color: #e9ecef; cursor: not-allowed;">
						<small class="text-muted">Today's date (auto-filled, cannot be changed)</small>
					</div>

					<input type="hidden" name="requisition_id" id="receiveRequisitionId">
					<input type="hidden" name="agreement_id" id="receiveAgreementId">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success" id="receiveCourierBtn">
						<i class="ri-check-double-line me-1"></i> Confirm Received
					</button>
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

@push('scripts')

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

		// Process Modal Show Event
		$('#processModal').on('shown.bs.modal', function(event) {
			let button = $(event.relatedTarget);

			let requisitionType = button.data('requisition-type');
			let teamSelect = $('#team_id');

			if (requisitionType === 'TFA' || requisitionType === 'CB') {
				teamSelect.val('11');
				teamSelect.prop('disabled', true);

				if (!$('#hiddenTeamInput').length) {
					$('<input>').attr({
						type: 'hidden',
						id: 'hiddenTeamInput',
						name: 'team_id',
						value: '11'
					}).appendTo('#processForm');
				}
			} else {
				teamSelect.val('');
				teamSelect.prop('disabled', false);
				$('#hiddenTeamInput').remove();
			}

			let requisitionId = button.data('requisition-id');
			let candidateName = button.data('requisition-name');
			let currentReporting = button.data('current-reporting');
			let currentManagerId = button.data('current-manager-id');

			let modal = $(this);
			modal.find('#modalRequisitionId').val(requisitionId);
			modal.find('#modalCandidateName').val(candidateName);
			modal.find('#currentReporting').val(currentReporting);
			modal.find('#currentManagerId').val(currentManagerId);

			let select = $('#reporting_manager_employee_id');
			select.html('<option value="">Loading...</option>');
			select.trigger('change');

			// AJAX call to load managers
			$.ajax({
				url: '{{ url("hr-admin/applications/get-reporting-managers") }}/' + requisitionId,
				type: 'GET',
				success: function(response) {
					if (!response.success) {
						select.html('<option value="">No data found</option>');
						select.trigger('change');
						return;
					}

					let data = response.data;
					select.empty();
					select.append('<option value="">-- Select Reporting Manager --</option>');

					// Current manager
					if (data.current) {
						select.append(`
                        <option value="${data.current.reporting_manager_employee_id}" selected>
                            ${data.current.reporting_to} (${data.current.reporting_manager_employee_id}) - Current
                        </option>
                    `);

						$('#reporting_to').val(data.current.reporting_to);
						$('#reporting_manager_id').val(data.current.reporting_manager_employee_id);
					}

					// Managers
					if (data.managers?.length) {
						select.append('<optgroup label="Department Managers">');
						data.managers.forEach(m => {
							if (!data.current || m.employee_id != data.current.reporting_manager_employee_id) {
								select.append(`
                                <option value="${m.employee_id}">
                                    ${m.emp_name} (${m.employee_id}) - ${m.emp_designation}
                                </option>
                            `);
							}
						});
					}

					// Employees
					if (data.employees?.length) {
						select.append('<optgroup label="Department Employees">');
						data.employees.forEach(e => {
							if (!data.current || e.employee_id != data.current.reporting_manager_employee_id) {
								select.append(`
                                <option value="${e.employee_id}">
                                    ${e.emp_name} (${e.employee_id}) - ${e.emp_designation}
                                </option>
                            `);
							}
						});
					}

					select.trigger('change.select2');
				},
				error: function() {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'Failed to load reporting managers'
					});
				}
			});
		});

		// Update fields when dropdown changes
		$('#reporting_manager_employee_id').on('change', function() {
			let selectedText = $(this).find('option:selected').text();
			let selectedValue = $(this).val();

			if (selectedValue) {
				let name = selectedText.split('(')[0].trim();
				$('#reporting_to').val(name);
				$('#reporting_manager_id').val(selectedValue);
			}
		});

		// Submit process form
		$('#processForm').on('submit', function(e) {
			e.preventDefault();
			let formData = $(this).serialize();

			Swal.fire({
				title: 'Process Employee?',
				html: '<p>This will generate party code.</p>',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#198754',
				confirmButtonText: 'Yes, process it',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					return $.ajax({
						url: '{{ route("hr-admin.applications.process-modal") }}',
						type: 'POST',
						data: formData
					});
				}
			}).then((result) => {
				if (result.isConfirmed && result.value?.success) {
					Swal.fire('Success', result.value.message, 'success')
						.then(() => location.reload());
				} else if (result.isConfirmed) {
					Swal.fire('Error', result.value?.message || 'Something went wrong', 'error');
				}
			});
		});

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
			const agreementNumber = $(this).data('agreement-number');

			$('#signedCandidateInfo').val(`${candidateCode} - ${candidateName}`);
			$('#signedCandidateId').val(candidateId);
			$('#signedAgreementNumber').val(agreementNumber);

			$('#uploadSignedModal').modal('show');
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


		// Receive Courier Modal - Populate data when opened
		$('#receiveCourierModal').on('show.bs.modal', function(event) {
			var button = $(event.relatedTarget); // Button that triggered the modal

			// Extract data from button attributes
			var requisitionId = button.data('requisition-id');
			var agreementId = button.data('agreement-id');
			var candidateName = button.data('candidate-name');
			var courierName = button.data('courier-name');
			var docketNumber = button.data('docket-number');
			var dispatchDate = button.data('dispatch-date');

			// Update the modal fields
			var modal = $(this);
			modal.find('#receiveRequisitionId').val(requisitionId);
			modal.find('#receiveAgreementId').val(agreementId);
			modal.find('#receiveCandidateName').val(candidateName);
			modal.find('#receiveCourierName').text(courierName);
			modal.find('#receiveDocketNumber').text(docketNumber);
			modal.find('#receiveDispatchDate').text(dispatchDate);

			// Set default received date to today
			var today = new Date().toISOString().split('T')[0];
			modal.find('input[name="received_date"]').val(today);
		});

		// Handle form submission
		$('#receiveCourierForm').on('submit', function(e) {
			e.preventDefault();

			var form = $(this);
			var formData = form.serialize();
			var requisitionId = $('#receiveRequisitionId').val();
			var agreementId = $('#receiveAgreementId').val();
			var submitBtn = $('#receiveCourierBtn');

			Swal.fire({
				title: 'Confirm Receipt',
				text: 'Are you sure you want to mark this courier as received?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#28a745',
				confirmButtonText: 'Yes, mark as received',
				cancelButtonText: 'Cancel',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Processing...');

					return $.ajax({
						url: '/hr-admin/agreement/' + requisitionId + '/courier-received/' + agreementId,
						type: 'POST',
						data: formData,
						headers: {
							'X-CSRF-TOKEN': csrfToken
						}
					});
				}
			}).then((result) => {
				if (result.isConfirmed && result.value && result.value.success) {
					Swal.fire('Success!', result.value.message, 'success');
					setTimeout(() => {
						window.location.reload();
					}, 1500);
				} else if (result.isConfirmed) {
					Swal.fire('Error!', result.value?.message || 'Something went wrong', 'error');
					submitBtn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Confirm Received');
				}
			}).catch((error) => {
				Swal.fire('Error!', 'Failed to process request', 'error');
				submitBtn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Confirm Received');
			});
		});


	});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const urlParams = new URLSearchParams(window.location.search);

    let targetTab = null;

    if (urlParams.has('inactive_page')) {
        targetTab = '#hrInactiveTab';
    }
    else if (urlParams.has('status_page')) {
        targetTab = '#hrStatusTab';
    }
    else if (urlParams.has('active_page')) {
        targetTab = '#hrActiveTab';
    }

    if (targetTab) {
        const triggerEl = document.querySelector(`[data-bs-target="${targetTab}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }

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