@extends('layouts.guest')

@section('content')
<div class="container-fluid">
	<div class="row mb-4">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0">My Team</h4>
				<div class="page-title-right">
					<ol class="breadcrumb m-0">
						<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
						<li class="breadcrumb-item active">My Team</li>
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
					<h5 class="card-title mb-0">Filter Candidates</h5>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-3">
							<div class="mb-3">
								<label class="form-label">Search Candidate</label>
								<input type="text" id="searchInput" class="form-control form-control-sm"
									placeholder="Search by name, code, email, or phone...">
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<label class="form-label">Candidate Type</label>
								<select id="typeFilter" class="form-select form-select-sm">
									<option value="all">All Types</option>
									<option value="Contractual">Contractual</option>
									<option value="TFA">TFA</option>
									<option value="CB">CB</option>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<label class="form-label">Status</label>
								<select id="statusFilter" class="form-select form-select-sm">
									<option value="all">All Status</option>
									<option value="A">Active</option>
									<option value="D">Inactive</option>
								</select>
							</div>
						</div>
						<div class="col-md-3 d-flex align-items-end">
							<div class="mb-3 w-100">
								<button type="button" onclick="loadCandidates()" class="btn btn-sm btn-primary w-100">
									<i class="ri-search-line align-middle"></i> Search
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Loading Spinner -->
	<div id="loadingSpinner" class="text-center" style="display: none;">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
		<p>Loading team data...</p>
	</div>

	<!-- Candidates Table -->
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">My Team Candidates</h5>
					<div class="card-header-right">
						<span class="badge bg-light text-dark" id="totalCount">0 candidates</span>
					</div>
				</div>
				<div class="card-body">
					<div class="table-responsive" id="tableContainer" style="display: none;">
						<table class="table table-bordered table-hover" id="candidatesTable">
							<thead class="table-light">
								<tr>
									<th style="width: 50px;" class="text-center">S.No</th>
									<th style="min-width: 120px;">Candidate Code</th>
									<th style="min-width: 180px;">Candidate Name</th>
									<th style="width: 100px;" class="text-center">Type</th>
									<th style="width: 150px;">Email</th>
									<th style="width: 120px;">Mobile</th>
									<th style="width: 120px;">Work Location</th>
									<th style="width: 100px;" class="text-center">Joining Date</th>
									<th style="width: 120px;" class="text-center">Monthly Salary</th>
									<th style="width: 100px;" class="text-center">Status</th>
									<th style="width: 80px;" class="text-center">Action</th>
								</tr>
							</thead>
							<tbody id="tableBody">
								<!-- Dynamic rows will be loaded here -->
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Candidate Details Modal -->
<!-- Candidate Details Modal -->
<div class="modal fade" id="candidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="candidateModalTitle">Candidate Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="detailsLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading candidate details...</p>
                </div>

                <!-- Candidate Details Content -->
                <div id="candidateDetailsContent" style="display: none;">
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-information-line align-middle"></i> Basic Information
                        </h6>
                        <div class="row" id="basicInfoSection"></div>
                    </div>

                    <!-- Employment Details -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-briefcase-line align-middle"></i> Employment Details
                        </h6>
                        <div class="row" id="employmentInfoSection"></div>
                    </div>

                    <!-- Personal Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-user-line align-middle"></i> Personal Information
                        </h6>
                        <div class="row" id="personalInfoSection"></div>
                    </div>

                    <!-- Work Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-building-line align-middle"></i> Work Information
                        </h6>
                        <div class="row" id="workInfoSection"></div>
                    </div>

                    <!-- KYC Information -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-shield-check-line align-middle"></i> KYC Information
                        </h6>
                        <div class="row" id="kycInfoSection"></div>
                    </div>

                    <!-- Documents -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-folder-line align-middle"></i> Documents 
                            <span class="badge bg-light text-dark ms-2" id="documentsCount">0 documents</span>
                        </h6>
                        <div id="documentsSection">
                            <div class="text-center py-3" id="documentsLoading">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Loading documents...</span>
                            </div>
                            <div id="documentsContent" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_section')
<script>
	// Global variables
	let currentCandidateId = null;

	// Initialize page
	$(document).ready(function() {
		loadCandidates();

		// Add enter key support for search
		$('#searchInput').on('keypress', function(e) {
			if (e.which === 13) {
				loadCandidates();
			}
		});
	});

	// Load candidates
	function loadCandidates() {
		const search = $('#searchInput').val();
		const type = $('#typeFilter').val();
		const status = $('#statusFilter').val();

		// Show loading
		$('#loadingSpinner').show();
		$('#tableContainer').hide();

		$.ajax({
			url: '{{ route("my-team.get-candidates") }}',
			method: 'GET',
			data: {
				search: search,
				type: type,
				status: status
			},
			success: function(response) {
				$('#loadingSpinner').hide();
				if (response.success) {
					renderCandidatesTable(response.data);
				} else {
					toastr.error(response.message || 'Error loading candidates');
				}
			},
			error: function() {
				$('#loadingSpinner').hide();
				toastr.error('Error loading candidates');
			}
		});
	}

	// Render candidates table
	function renderCandidatesTable(candidates) {
		let bodyHtml = '';

		if (candidates.length === 0) {
			bodyHtml = `
                <tr>
                    <td colspan="11" class="text-center py-4">
                        <div class="text-muted">
                            <i class="ri-user-search-line" style="font-size: 48px;"></i>
                            <p class="mt-2">No candidates found</p>
                        </div>
                    </td>
                </tr>`;
		} else {
			candidates.forEach((candidate, index) => {
				const salary = candidate.remuneration_per_month ?
					'₹' + parseInt(candidate.remuneration_per_month).toLocaleString('en-IN') :
					'N/A';

				const joiningDate = candidate.date_of_joining ?
					new Date(candidate.date_of_joining).toLocaleDateString('en-GB') :
					'N/A';

				const statusBadge = candidate.final_status === 'A' ?
					'<span class="badge bg-success">Active</span>' :
					'<span class="badge bg-danger">Inactive</span>';

				const typeBadge = getTypeBadge(candidate.requisition_type);

				bodyHtml += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td><strong>${candidate.candidate_code}</strong></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-xs">
                                        <div class="avatar-title bg-light text-primary rounded-circle">
                                            ${candidate.candidate_name.charAt(0).toUpperCase()}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">${candidate.candidate_name}</h6>
                                    <small class="text-muted">${candidate.candidate_email}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">${typeBadge}</td>
                        <td>${candidate.candidate_email}</td>
                        <td>${candidate.mobile_no}</td>
                        <td>${candidate.work_location_hq || 'N/A'}</td>
                        <td class="text-center">${joiningDate}</td>
                        <td class="text-center">${salary}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info view-btn" 
                                    onclick="viewCandidate(${candidate.id}, '${candidate.candidate_name}')"
                                    title="View Details">
                                <i class="ri-eye-line"></i>
                            </button>
                        </td>
                    </tr>`;
			});
		}

		$('#tableBody').html(bodyHtml);
		$('#tableContainer').show();
		$('#totalCount').text(`${candidates.length} candidates`);
	}

	// Get type badge
	function getTypeBadge(type) {
		const badges = {
			'Contractual': 'bg-primary',
			'TFA': 'bg-info',
			'CB': 'bg-secondary'
		};

		const color = badges[type] || 'bg-secondary';
		return `<span class="badge ${color}">${type}</span>`;
	}

	// View candidate details
	function viewCandidate(candidateId, candidateName) {
    currentCandidateId = candidateId;
    
    // Set modal title
    $('#candidateModalTitle').text(`Candidate Details - ${candidateName}`);
    
    // Show loading, hide content
    $('#detailsLoading').show();
    $('#candidateDetailsContent').hide();
    $('#documentsContent').hide();
    $('#documentsLoading').show();
    
    // Reset content
    $('#basicInfoSection').empty();
    $('#employmentInfoSection').empty();
    $('#personalInfoSection').empty();
    $('#workInfoSection').empty();
    $('#kycInfoSection').empty();
    $('#documentsContent').empty();
    
    // Load candidate details
    loadCandidateDetails(candidateId);
    
    // Load candidate documents
    loadCandidateDocuments(candidateId);
    
    // Show modal
    $('#candidateModal').modal('show');
}

	// Show loading in all tabs
	function showLoadingInTabs() {
		const loadingHtml = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading data...</p>
            </div>`;

		$('#basicInfoContent').html(loadingHtml);
		$('#workInfoContent').html(loadingHtml);
		$('#educationInfoContent').html(loadingHtml);
		$('#bankInfoContent').html(loadingHtml);
		$('#attendanceInfoContent').html(loadingHtml);
		$('#documentsTableBody').html(`
            <tr>
                <td colspan="4" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading documents...</span>
                </td>
            </tr>
        `);
	}

	// Load candidate details
function loadCandidateDetails(candidateId) {
    $.ajax({
        url: '{{ route("my-team.candidate.show", ":id") }}'.replace(':id', candidateId),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Render all sections
                renderInfoSection('#basicInfoSection', data.basic_info, 6);
                renderInfoSection('#employmentInfoSection', data.employment_details, 4);
                renderInfoSection('#personalInfoSection', data.personal_info, 4);
                renderInfoSection('#workInfoSection', data.work_info, 4);
                renderInfoSection('#kycInfoSection', data.kyc_info, 3);
                
                // Hide loading, show content
                $('#detailsLoading').hide();
                $('#candidateDetailsContent').show();
                
            } else {
                toastr.error(response.message || 'Error loading candidate details');
                $('#detailsLoading').html('<div class="alert alert-danger">Error loading details</div>');
            }
        },
        error: function() {
            toastr.error('Error loading candidate details');
            $('#detailsLoading').html('<div class="alert alert-danger">Error loading details</div>');
        }
    });
}
// Load candidate documents
function loadCandidateDocuments(candidateId) {
    $.ajax({
        url: '{{ route("my-team.candidate.documents", ":id") }}'.replace(':id', candidateId),
        method: 'GET',
        success: function(response) {
            console.log('Documents response:', response);
            
            if (response.success) {
                const documents = response.data;
                
                // Update documents count
                $('#documentsCount').text(`${documents.length} documents`);
                
                if (documents.length === 0) {
                    $('#documentsContent').html(`
                        <div class="alert alert-info">
                            <i class="ri-information-line"></i> No documents uploaded.
                        </div>
                    `);
                } else {
                    let documentsHtml = '';
                    documents.forEach((doc, index) => {
                        console.log('Document data:', doc);
                        
                        // Check if we have S3 URL
                        if (!doc.s3_url || doc.s3_url === 'null') {
                            documentsHtml += `
                                <div class="card mb-3 border-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">${doc.document_type}</h6>
                                                <p class="card-text text-muted mb-1">
                                                    <small><i class="ri-file-line"></i> ${doc.file_name}</small>
                                                </p>
                                                <p class="card-text text-muted mb-0">
                                                    <small><i class="ri-time-line"></i> Uploaded: ${doc.uploaded_at}</small>
                                                </p>
                                                <p class="text-warning mb-0">
                                                    <small><i class="ri-alert-line"></i> Document URL not available</small>
                                                </p>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-warning" disabled title="Document not available">
                                                    <i class="ri-eye-off-line me-1"></i> Unavailable
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            // Use S3 URL directly - no need for the view-document route
                            documentsHtml += `
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">${doc.document_type}</h6>
                                                <p class="card-text text-muted mb-1">
                                                    <small><i class="ri-file-line"></i> ${doc.file_name}</small>
                                                </p>
                                                <p class="card-text text-muted mb-0">
                                                    <small><i class="ri-time-line"></i> Uploaded: ${doc.uploaded_at}</small>
                                                </p>
                                                <p class="text-success mb-0">
                                                    <small><i class="ri-cloud-line"></i> Stored in S3</small>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="${doc.s3_url}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="ri-eye-line me-1"></i> View
                                                </a>
                                                <a href="${doc.s3_url}" 
                                                   download="${doc.file_name}"
                                                   class="btn btn-sm btn-outline-primary ms-1">
                                                    <i class="ri-download-line me-1"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        }
                    });
                    $('#documentsContent').html(documentsHtml);
                }
                
                // Hide loading, show content
                $('#documentsLoading').hide();
                $('#documentsContent').show();
                
            } else {
                console.error('Documents error:', response.message);
                $('#documentsContent').html(`
                    <div class="alert alert-danger">
                        <i class="ri-error-warning-line"></i> ${response.message || 'Error loading documents'}
                    </div>
                `);
                $('#documentsLoading').hide();
                $('#documentsContent').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            $('#documentsContent').html(`
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line"></i> Error loading documents. Please try again.
                </div>
            `);
            $('#documentsLoading').hide();
            $('#documentsContent').show();
        }
    });
}

	// Render info section
function renderInfoSection(containerId, data, cols = 4) {
    let html = '';
    const colClass = `col-md-${12/cols}`;
    
    for (const [key, value] of Object.entries(data)) {
        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        html += `
            <div class="${colClass} mb-3">
                <label class="form-label text-muted mb-1" style="font-size: 12px;">${formattedKey}:</label>
                <div class="form-control-plaintext">
                    <strong>${value || 'N/A'}</strong>
                </div>
            </div>`;
    }
    
    $(containerId).html(html);
}


</script>

<style>
	/* Custom styles for My Team */
	.nav-tabs-custom .nav-link {
		color: #495057;
		font-weight: 500;
		padding: 0.75rem 1rem;
	}

	.nav-tabs-custom .nav-link.active {
		color: #0d6efd;
		border-bottom: 2px solid #0d6efd;
	}

	.avatar-title {
		width: 36px;
		height: 36px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 600;
	}

	.view-btn {
		padding: 4px 8px;
		font-size: 14px;
	}

	.card-header-right {
		position: absolute;
		right: 20px;
		top: 20px;
	}

	/* Info section styling */
	.form-control-plaintext {
		min-height: 24px;
		padding: 0;
	}

	.border-bottom {
		border-bottom: 1px solid #e9ecef !important;
	}

	/* Tab content spacing */
	.tab-content {
		min-height: 400px;
	}

	/* Responsive adjustments */
	@media (max-width: 768px) {
		.nav-tabs-custom .nav-link {
			padding: 0.5rem;
			font-size: 12px;
		}

		.avatar-title {
			width: 30px;
			height: 30px;
			font-size: 12px;
		}
	}
</style>
@endsection