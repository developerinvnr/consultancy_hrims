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
	@if(isset($recent_requisitions) && $recent_requisitions->count() > 0)
	<div class="row">
		<div class="col-12">
			<div class="card border-0 shadow-sm">
				<div class="card-body p-2">
					<h6 class="mb-2 fs-6">Recent Requisitions</h6>
					<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
						<table class="table table-sm table-hover mb-0">
							<thead class="sticky-top bg-white">
								<tr>
									<th class="fs-11">ID</th>
									<th class="fs-11">Candidate</th>
									<th class="fs-11">Email</th>
									<th class="fs-11">Type</th>
									<th class="fs-11">Status</th>
									<th class="fs-11">Date</th>
									<th class="fs-11">Actions</th>
								</tr>
							</thead>
							<tbody>
								@foreach($recent_requisitions as $req)
								@php
								$isProcessed = \App\Models\CandidateMaster::where('requisition_id', $req->id)->exists();
								$candidate = \App\Models\CandidateMaster::where('requisition_id', $req->id)->first();
								$empStatus = $candidate->candidate_status ?? null;
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

										@case('Processed')
										@php
										$statusColors = [
										'Agreement Pending' => 'warning',
										'Unsigned Agreement Uploaded' => 'info',
										'Agreement Completed' => 'secondary',
										'Active' => 'success'
										];
										@endphp

										<span class="badge bg-{{ $statusColors[$empStatus] ?? 'secondary' }} fs-10">
											{{ $empStatus }}
										</span>

										@if($candidate?->candidate_code)
										<br>
										<small class="text-muted fs-9">{{ $candidate->candidate_code }}</small>
										@endif
										@break

										@default
										<span class="badge bg-secondary fs-10">{{ $req->status }}</span>

										@endswitch

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
												data-requisition-name="{{ $req->candidate_name }}"
												data-current-reporting="{{ $req->reporting_to }}"
												data-current-manager-id="{{ $req->reporting_manager_employee_id }}">
												<i class="ri-play-line fs-10"></i>
												@elseif($req->status === 'Pending Approval')
												<span class="badge bg-info fs-9">Awaiting Approval</span>
												@endif


											@if($candidate)
											@php
											$hasUnsigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)->where('document_type', 'agreement')
											->where('sign_status', 'UNSIGNED')
											->exists();											$hasSigned =\App\Models\AgreementDocument::where('candidate_id', $candidate->id)
											->where('document_type', 'agreement')
											->where('sign_status', 'SIGNED')
											->exists();
											$submitterSigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)->where('document_type', 'agreement')->where('sign_status', 'SIGNED')->where('uploaded_by_role', 'submitter')->exists();
											$agreementNumber = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
												->where('document_type', 'agreement')
												->where('sign_status', 'UNSIGNED')
												->value('agreement_number');
											@endphp

											@if($empStatus == "Active")
											<button class="btn btn-outline-info disabled">{{ $empStatus }}</button>

											@elseif($hasUnsigned && !$hasSigned)
											<button type="button"
												class="btn btn-outline-primary upload-signed-btn"
												data-candidate-id="{{ $candidate->id }}"
												data-candidate-code="{{ $candidate->candidate_code }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												data-agreement-number="{{ $agreementNumber }}">
												<i class="ri-upload-line fs-10"></i>
											</button>
											@elseif($hasSigned && $submitterSigned)
											<button type="button"
												class="btn btn-outline-info verify-signed-modal-btn"
												data-candidate-id="{{ $candidate->id }}"
												data-candidate-code="{{ $candidate->candidate_code }}"
												data-candidate-name="{{ $candidate->candidate_name }}">
												<i class="ri-check-line fs-10"></i>
											</button>
											@endif
											@endif
										</div>
									</td>
								</tr>
								@endforeach
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
						<div class="col-md-6">
							<div class="mb-3">
								<label for="reporting_to" class="form-label">Reporting To Name *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_to"
									name="reporting_to" required readonly>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mb-3">
								<label for="reporting_manager_id" class="form-label-sm">Reporting Manager ID *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_manager_id"
									name="reporting_manager_id" required readonly>
							</div>
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

<!-- Verify Signed Agreement Modal -->
<div class="modal fade" id="verifySignedModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Verify Signed Agreement</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<form id="verifySignedForm" action="" method="POST">
				@csrf
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control" id="verifyCandidateInfo" readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">Available Signed Agreements</label>
						<div id="signedDocumentsList">
							<div class="text-center">
								<div class="spinner-border spinner-border-sm" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								Loading documents...
							</div>
						</div>
					</div>

					<input type="hidden" name="candidate_id" id="verifyCandidateId">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success" id="verifySubmitBtn" disabled>Verify & Activate Employee</button>
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


		// Verify Signed Agreement Modal
		$('.verify-signed-modal-btn').on('click', function() {
			const candidateId = $(this).data('candidate-id');
			const candidateCode = $(this).data('candidate-code');
			const candidateName = $(this).data('candidate-name');

			$('#verifyCandidateInfo').val(`${candidateCode} - ${candidateName}`);
			$('#verifyCandidateId').val(candidateId);
			$('#verifySubmitBtn').prop('disabled', true);

			// Load signed documents
			loadSignedDocuments(candidateId);

			$('#verifySignedModal').modal('show');
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

		// Submit verify form
		$('#verifySignedForm').on('submit', function(e) {
			e.preventDefault();

			if (!$('input[name="document_id"]:checked').val()) {
				showToast('error', 'Please select a document to verify');
				return;
			}

			const formData = $(this).serialize();
			const candidateId = $('#verifyCandidateId').val();

			Swal.fire({
				title: 'Verify & Activate Employee?',
				html: '<p>This will activate the employee and mark them as Active.</p>',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#198754',
				confirmButtonText: 'Yes, verify & activate',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					return $.ajax({
						url: `/hr-admin/candidate/${candidateId}/verify-signed-agreement`,
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
<style>
	.select2-container {
		z-index: 1065 !important;
		/* Higher than Bootstrap modal */
	}

	.bg-soft-success {
		background-color: #e6f7f0 !important;
	}
</style>
@endsection