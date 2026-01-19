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

<!-- Remove the entire modal section since we're using separate page -->
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

                // Generate the view URL using the candidate ID from the data
                const viewUrl = '{{ route("my-team.candidate.show", ":id") }}'.replace(':id', candidate.id);

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
                            <a href="${viewUrl}" 
                               class="btn btn-sm btn-outline-info"
                               title="View Details">
                                <i class="ri-eye-line"></i>
                            </a>
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

    // Remove all the modal-related functions since we're using separate page
</script>

<style>
    /* Custom styles for My Team */
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .avatar-title {
            width: 30px;
            height: 30px;
            font-size: 12px;
        }
    }
</style>
@endsection