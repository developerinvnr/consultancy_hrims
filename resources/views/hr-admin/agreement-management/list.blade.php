@extends('layouts.guest')

@section('content')
<div class="container-fluid">
	<div class="row mb-4">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0">Agreement Management</h4>
				<div class="page-title-right">
					<ol class="breadcrumb m-0">
						<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
						<li class="breadcrumb-item active">Agreement Management</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Filters Card -->
	<div class="row mb-4">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Filter Agreements</h5>
				</div>
				<div class="card-body">
					<form method="GET" action="{{ route('hr-admin.agreement.list') }}">
						<div class="row">
							<div class="col-md-3">
								<div class="mb-3">
									<label class="form-label">Candidate Status</label>
									<select name="candidate_status" class="form-select form-select-sm">
										<option value="">All Statuses</option>
										@foreach($statusOptions as $key => $value)
										<option value="{{ $key }}" {{ request('candidate_status') == $key ? 'selected' : '' }}>
											{{ $value }}
										</option>
										@endforeach
									</select>
								</div>
							</div>
							<div class="col-md-3">
								<div class="mb-3">
									<label class="form-label">Requisition Type</label>
									<select name="requisition_type" class="form-select form-select-sm">
										<option value="">All Types</option>
										<option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
										<option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
										<option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
									</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Search</label>
									<input type="text" name="search" class="form-control form-control-sm"
										placeholder="Search by name, code, email, or requisition ID..." value="{{ request('search') }}">
								</div>
							</div>
							<div class="col-md-2 d-flex align-items-end">
								<div class="mb-3 w-100">
									<button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Agreements List -->
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="card-title mb-0">Agreement List</h5>
					<div>
						<span class="badge bg-info">Total: {{ $candidates->total() }}</span>
					</div>
				</div>
				<div class="card-body">
					@if($candidates->count() > 0)
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Candidate Code</th>
									<th>Candidate Name</th>
									<th>Email</th>
									<th>Type</th>
									<th>Status</th>
									<th>Requisition ID</th>
									<th>Submitter</th>
									<th>Created Date</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								@foreach($candidates as $candidate)
								<tr>
									<td>
										<span class="badge bg-secondary">{{ $candidate->candidate_code }}</span>
									</td>
									<td>
										<div>{{ $candidate->candidate_name }}</div>
										
									</td>
									<td>{{ $candidate->candidate_email }}</td>
									<td>
										<span class="badge bg-{{ $candidate->requisition_type == 'Contractual' ? 'primary' : ($candidate->requisition_type == 'TFA' ? 'success' : 'info') }}">
											{{ $candidate->requisition_type }}
										</span>
									</td>
									<td>
										@php
										$statusColors = [
										'Agreement Pending' => 'warning',
										'Unsigned Agreement Uploaded' => 'primary',
										'Agreement Completed' => 'success'
										];
										@endphp
										<span class="badge bg-{{ $statusColors[$candidate->candidate_status] ?? 'secondary' }}">
											{{ $candidate->candidate_status }}
										</span>
									</td>
									<td>
										@if($candidate->requisition)
										<span class="badge bg-dark">{{ $candidate->requisition->requisition_id }}</span>
										@else
										<span class="badge bg-secondary">N/A</span>
										@endif
									</td>
									<td>
										@if($candidate->requisition && $candidate->requisition->submittedBy)
										{{ $candidate->requisition->submittedBy->name }}
										@else
										<span class="text-muted">N/A</span>
										@endif
									</td>
									<td>{{ $candidate->created_at->format('d-M-Y') }}</td>
									<td>
										<div class="btn-group" role="group">
											@if($candidate->candidate_status == 'Agreement Pending')
											<button type="button"
												class="btn btn-sm btn-outline-success upload-unsigned-btn"
												data-candidate-id="{{ $candidate->id }}"
												data-candidate-code="{{ $candidate->candidate_code }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												title="Upload Unsigned Agreement">
												<i class="ri-file-upload-line"></i>
											</button>
											@endif

											@if($candidate->candidate_status == 'Unsigned Agreement Uploaded')
											<!-- View/Update Unsigned Agreement Button -->
											@php
											$unsignedAgreement = $candidate->agreementDocuments
											->where('document_type', 'unsigned')
											->first();
											@endphp

											@if($unsignedAgreement)
											<button type="button"
												class="btn btn-sm btn-outline-warning update-unsigned-btn"
												data-agreement-id="{{ $unsignedAgreement->id }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												title="Update Unsigned Agreement">
												<i class="ri-edit-line"></i>
											</button>
											@endif

											<!-- Upload Signed Agreement Button -->
											<button type="button"
												class="btn btn-sm btn-outline-info upload-signed-btn"
												data-candidate-id="{{ $candidate->id }}"
												data-candidate-code="{{ $candidate->candidate_code }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												title="Upload Signed Agreement">
												<i class="ri-file-signature-line"></i>
											</button>
											@endif

											@if($candidate->candidate_status == 'Signed Agreement Uploaded')
											<!-- View/Update Signed Agreement Button -->
											@php
											$signedAgreement = $candidate->agreementDocuments
											->where('document_type', 'signed')
											->first();
											@endphp

											@if($signedAgreement)
											<button type="button"
												class="btn btn-sm btn-outline-warning update-signed-btn"
												data-agreement-id="{{ $signedAgreement->id }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												title="Update Signed Agreement">
												<i class="ri-edit-line"></i>
											</button>
											@endif
											@endif

											@if(in_array($candidate->candidate_status, ['Unsigned Agreement Uploaded', 'Signed Agreement Uploaded']))
											<!-- View Agreements Button -->
											@php
											$unsignedAgreement = $candidate->agreementDocuments
											->where('document_type', 'unsigned')
											->first();
											$signedAgreement = $candidate->agreementDocuments
											->where('document_type', 'signed')
											->first();
											@endphp

											<button type="button"
												class="btn btn-sm btn-outline-primary view-agreements-btn"
												data-candidate-id="{{ $candidate->id }}"
												data-candidate-name="{{ $candidate->candidate_name }}"
												data-unsigned-id="{{ $unsignedAgreement->id ?? '' }}"
												data-signed-id="{{ $signedAgreement->id ?? '' }}"
												title="View Agreements">
												<i class="ri-eye-line"></i>
											</button>
											@endif

											<!-- Always show management link -->
											<a href="{{ route('hr-admin.agreement.management', $candidate) }}"
												class="btn btn-sm btn-outline-secondary" title="Manage Agreement Details">
												<i class="ri-settings-line"></i>
											</a>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>

					<!-- Pagination -->
					<div class="d-flex justify-content-center mt-3">
						{{ $candidates->links() }}
					</div>
					@else
					<div class="alert alert-info">
						<i class="ri-information-line me-2"></i> No candidates found matching your criteria.
					</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Upload Unsigned Agreement Modal -->
<div class="modal fade" id="uploadUnsignedModal" tabindex="-1" aria-labelledby="uploadUnsignedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadUnsignedModalLabel">Upload Unsigned Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadUnsignedForm" method="POST" enctype="multipart/form-data">
                @csrf
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Signed Agreement Modal -->
<div class="modal fade" id="uploadSignedModal" tabindex="-1" aria-labelledby="uploadSignedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadSignedModalLabel">Upload Signed Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadSignedForm" method="POST" enctype="multipart/form-data">
                @csrf
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Upload Signed Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Agreement Modal (for both unsigned and signed) -->
<div class="modal fade" id="updateAgreementModal" tabindex="-1" aria-labelledby="updateAgreementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateAgreementModalLabel">Update Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateAgreementForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
					<div class="row">
						<div class="col-md-6 mb-3">
                        <label class="form-label form-label-sm">Candidate</label>
                        <input type="text" class="form-control form-control-sm" id="updateCandidateInfo" readonly>
                    </div>
						<div class="col-md-6 mb-3">
                        <label class="form-label">Agreement Type</label>
                        <input type="text" class="form-control form-control-sm" id="updateAgreementType" readonly>
                    </div>
					</div>            

                    

                    <div class="mb-3">
                        <label class="form-label">Current Agreement Number</label>
                        <input type="text" class="form-control form-control-sm" id="currentAgreementNumber" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Update Agreement Number</label>
                        <input type="text" class="form-control form-control-sm" name="agreement_number"
                            placeholder="Enter new agreement number (leave empty to keep current)" maxlength="100">
                        <small class="text-muted">Leave empty if you don't want to change the agreement number</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Update Agreement File (PDF)</label>
                        <input type="file" class="form-control form-control-sm" name="agreement_file"
                            accept=".pdf">
                        <small class="text-muted">Leave empty to keep current file. Maximum size: 10MB</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <span id="updateAgreementHelpText">Update either agreement number or file, or both.</span>
                    </div>

                    <input type="hidden" name="agreement_id" id="updateAgreementId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning">Update Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Agreements Modal (Read-only) -->
<div class="modal fade" id="viewAgreementsModal" tabindex="-1" aria-labelledby="viewAgreementsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAgreementsModalLabel">View Agreements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Candidate</label>
                    <input type="text" class="form-control" id="agreementsCandidateInfo" readonly>
                </div>

                <div class="row">
                    <!-- Unsigned Agreement Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0">Unsigned Agreement</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">Agreement Number:</small>
                                    <p class="fw-bold mb-1" id="unsignedAgreementNumber">-</p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Uploaded Date:</small>
                                    <p class="mb-1" id="unsignedUploadDate">-</p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">File:</small>
                                    <p class="mb-1" id="unsignedFileName">-</p>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="viewUnsignedPdfBtn">
                                        <i class="ri-eye-line me-1"></i> View PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Signed Agreement Card -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0">Signed Agreement</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">Agreement Number:</small>
                                    <p class="fw-bold mb-1" id="signedAgreementNumber">-</p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Uploaded Date:</small>
                                    <p class="mb-1" id="signedUploadDate">-</p>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">File:</small>
                                    <p class="mb-1" id="signedFileName">-</p>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="viewSignedPdfBtn">
                                        <i class="ri-eye-line me-1"></i> View PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="agreementsCandidateId">
                <input type="hidden" id="unsignedAgreementId">
                <input type="hidden" id="signedAgreementId">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
    // Upload Unsigned Agreement Modal
    $('.upload-unsigned-btn').on('click', function() {
        const candidateId = $(this).data('candidate-id');
        const candidateCode = $(this).data('candidate-code');
        const candidateName = $(this).data('candidate-name');

        $('#candidateInfo').val(`${candidateCode} - ${candidateName}`);
        $('#unsignedCandidateId').val(candidateId);
        $('#uploadUnsignedForm').attr('action', `/hr-admin/agreement/${candidateId}/upload-unsigned`);
        $('#uploadUnsignedModal').modal('show');
    });

    // Upload Signed Agreement Modal
    $('.upload-signed-btn').on('click', function() {
        const candidateId = $(this).data('candidate-id');
        const candidateCode = $(this).data('candidate-code');
        const candidateName = $(this).data('candidate-name');

        $('#signedCandidateInfo').val(`${candidateCode} - ${candidateName}`);
        $('#signedCandidateId').val(candidateId);
        $('#uploadSignedForm').attr('action', `/hr-admin/agreement/${candidateId}/upload-signed`);
        $('#uploadSignedModal').modal('show');
    });

    // Update Unsigned Agreement
    $('.update-unsigned-btn').on('click', function() {
        const agreementId = $(this).data('agreement-id');
        const candidateName = $(this).data('candidate-name');
        
        loadAgreementForUpdate(agreementId, candidateName, 'unsigned');
    });

    // Update Signed Agreement
    $('.update-signed-btn').on('click', function() {
        const agreementId = $(this).data('agreement-id');
        const candidateName = $(this).data('candidate-name');
        
        loadAgreementForUpdate(agreementId, candidateName, 'signed');
    });

    // View Agreements Modal
    $('.view-agreements-btn').on('click', function() {
        const candidateId = $(this).data('candidate-id');
        const candidateName = $(this).data('candidate-name');
        const unsignedId = $(this).data('unsigned-id');
        const signedId = $(this).data('signed-id');
        
        $('#agreementsCandidateInfo').val(candidateName);
        $('#agreementsCandidateId').val(candidateId);
        $('#unsignedAgreementId').val(unsignedId);
        $('#signedAgreementId').val(signedId);
        
        // Load agreement details
        if (unsignedId) {
            loadAgreementDetails(unsignedId, 'unsigned');
        }
        if (signedId) {
            loadAgreementDetails(signedId, 'signed');
        }
        
        $('#viewAgreementsModal').modal('show');
    });

    // Load agreement details for viewing
    function loadAgreementDetails(agreementId, type) {
        $.ajax({
            url: `/hr-admin/agreement/${agreementId}/details`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const agreement = response.data;
                    
                    if (type === 'unsigned') {
                        $('#unsignedAgreementNumber').text(agreement.agreement_number || 'Not available');
                        $('#unsignedUploadDate').text(new Date(agreement.created_at).toLocaleDateString());
                        $('#unsignedFileName').text(agreement.file_name || 'No file uploaded');
                    } else if (type === 'signed') {
                        $('#signedAgreementNumber').text(agreement.agreement_number || 'Not available');
                        $('#signedUploadDate').text(new Date(agreement.created_at).toLocaleDateString());
                        $('#signedFileName').text(agreement.file_name || 'No file uploaded');
                    }
                }
            },
            error: function() {
                showToast('error', 'Failed to load agreement details');
            }
        });
    }

    // Load agreement details for updating
    function loadAgreementForUpdate(agreementId, candidateName, agreementType) {
        $.ajax({
            url: `/hr-admin/agreement/${agreementId}/details`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const agreement = response.data;
                    
                    // Fill update modal
                    $('#updateCandidateInfo').val(candidateName);
                    $('#updateAgreementType').val(agreementType === 'unsigned' ? 'Unsigned Agreement' : 'Signed Agreement');
                    $('#currentAgreementNumber').val(agreement.agreement_number || 'Not set');
                    $('#updateAgreementId').val(agreementId);
                    
                    // Set help text based on agreement type
                    const helpText = agreementType === 'unsigned' 
                        ? 'Update unsigned agreement number or file. Once signed agreement is uploaded, unsigned cannot be updated.'
                        : 'Update signed agreement number or file.';
                    $('#updateAgreementHelpText').text(helpText);
                    
                    $('#updateAgreementModal').modal('show');
                }
            },
            error: function() {
                showToast('error', 'Failed to load agreement details');
            }
        });
    }

    // View PDF buttons in view modal
    $('#viewUnsignedPdfBtn').on('click', function() {
        const agreementId = $('#unsignedAgreementId').val();
        if (agreementId) {
            window.open(`/hr-admin/agreement/${agreementId}/view`, '_blank');
        }
    });

    $('#viewSignedPdfBtn').on('click', function() {
        const agreementId = $('#signedAgreementId').val();
        if (agreementId) {
            window.open(`/hr-admin/agreement/${agreementId}/view`, '_blank');
        }
    });

    // Form submission handlers
    $('#uploadUnsignedForm, #uploadSignedForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(form[0]);
        const url = form.attr('action');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true)
                    .html('<i class="ri-loader-4-line ri-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('error', response.message || 'An error occurred');
                    form.find('button[type="submit"]').prop('disabled', false)
                        .html(form.attr('id') === 'uploadUnsignedForm' ? 'Upload Agreement' : 'Upload Signed Agreement');
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
                form.find('button[type="submit"]').prop('disabled', false)
                    .html(form.attr('id') === 'uploadUnsignedForm' ? 'Upload Agreement' : 'Upload Signed Agreement');
            }
        });
    });

    // Update Agreement Form Submission
    $('#updateAgreementForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(form[0]);
        const agreementId = formData.get('agreement_id');
        
        // Check if at least one field is filled
        const agreementNumber = formData.get('agreement_number');
        const agreementFile = formData.get('agreement_file');
        
        if (!agreementNumber && !agreementFile.name) {
            showToast('error', 'Please provide either agreement number or file to update');
            return;
        }
        
        $.ajax({
            url: `/hr-admin/agreement/${agreementId}/update`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            },
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true)
                    .html('<i class="ri-loader-4-line ri-spin"></i> Updating...');
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('error', response.message);
                    form.find('button[type="submit"]').prop('disabled', false).html('Update Agreement');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update agreement. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast('error', errorMessage);
                form.find('button[type="submit"]').prop('disabled', false).html('Update Agreement');
            }
        });
    });

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