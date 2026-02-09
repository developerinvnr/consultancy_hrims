@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">HR - Manpower Requisitions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">HR</a></li>
                        <li class="breadcrumb-item active">Requisitions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- End page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">All Requisitions</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <!-- Filter Form -->
                                <form method="GET" action="{{ route('hr_requisitions.index') }}" class="d-flex me-2">
                                    {{--<input type="text" name="search" class="form-control form-control-sm me-2"
                                        placeholder="Search by ID, Name, Email..." value="{{ request('search') }}">--}}
                                    <select name="type" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                                        <option value="Contractual" {{ request('type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                                        <option value="TFA" {{ request('type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                                        <option value="CB" {{ request('type') == 'CB' ? 'selected' : '' }}>CB</option>
                                    </select>
                                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="">All Status</option>
                                        <option value="Pending HR Verification" {{ request('status') == 'Pending HR Verification' ? 'selected' : '' }}>Pending HR Verification</option>
                                        <option value="Correction Required" {{ request('status') == 'Correction Required' ? 'selected' : '' }}>Correction Required</option>
                                        <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                                        <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="Processed" {{ request('status') == 'Processed' ? 'selected' : '' }}>Processed</option>
                                        <option value="Agreement Pending" {{ request('status') == 'Agreement Pending' ? 'selected' : '' }}>Agreement Pending</option>
                                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm me-2" style="color:#000;">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <a href="{{ route('hr_requisitions.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </form>

                                <!-- Create New Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-success btn-sm dropdown-toggle" style="color:#000; border-color:#28a745;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-add-line me-1"></i> Create
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'Contractual']) }}">Contractual</a></li>
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'tfa']) }}">TFA</a></li>
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'CB']) }}">CB</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th width="10%">Req ID</th>
                                    <th width="10%">Type</th>
                                    <th width="15%">Candidate</th>
                                    <th width="15%">Submitted By</th>
                                    <th width="10%">Date</th>
                                    <th width="15%">Status</th>
                                    <th width="15%">HR Status</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisitions as $requisition)
                                <tr>
                                    <td>
                                        <strong>{{ $requisition->requisition_id }}</strong>
                                    </td>
                                    <td>
                                        @php
                                        $typeColors = [
                                        'TFA' => 'info',
                                        'CB' => 'warning',
                                        'Contractual' => 'primary'
                                        ];
                                        $color = $typeColors[$requisition->requisition_type] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $requisition->requisition_type }}</span>
                                    </td>
                                    <td>
                                        <div><strong>{{ $requisition->candidate_name }}</strong></div>
                                        <small class="text-muted">{{ $requisition->candidate_email }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $requisition->submitted_by_name }}</div>
                                        <small class="text-muted">ID: {{ $requisition->submitted_by_employee_id }}</small>
                                    </td>
                                    <td>{{ $requisition->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        @php
                                        $statusColors = [
                                        'Pending HR Verification' => 'warning',
                                        'Correction Required' => 'danger',
                                        'Pending Approval' => 'info',
                                        'Approved' => 'success',
                                        'Rejected' => 'dark',
                                        'Processed' => 'primary',
                                        'Agreement Pending' => 'secondary',
                                        'Completed' => 'success'
                                        ];
                                        $color = $statusColors[$requisition->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $requisition->status }}</span>
                                    </td>
                                    <td>
                                        @if($requisition->hr_verification_status)
                                        @php
                                        $hrStatusColors = [
                                        'Pending' => 'warning',
                                        'Verified' => 'info',
                                        'Approved' => 'success',
                                        'Rejected' => 'danger',
                                        'Correction Required' => 'danger'
                                        ];
                                        $hrColor = $hrStatusColors[$requisition->hr_verification_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $hrColor }}">{{ $requisition->hr_verification_status }}</span>
                                        @if($requisition->hr_verification_date)
                                        <br><small class="text-muted">{{ \Carbon\Carbon::parse($requisition->hr_verification_date)->format('d-m-Y') }}</small>
                                        @endif
                                        @else
                                        <span class="badge bg-light text-dark">Not Reviewed</span>
                                        @endif
                                    </td>
                                    <!-- Actions column -->
                                    <!-- Actions column -->
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- View Button -->
                                            <a href="{{ route('hr-admin.applications.view', $requisition->id) }}"
                                                class="btn btn-sm btn-info" title="View" target="_blank">
                                                <i class="ri-eye-line"></i>
                                            </a>

                                            <!-- HR Review Button (for pending) -->
                                            @if(in_array($requisition->status, ['Pending HR Verification', 'Correction Required']))
                                            <a href="{{ route('requisitions.edit', $requisition->id) }}"
                                                class="btn btn-sm btn-warning" title="HR Review">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            @endif

                                            <!-- Process Button (for Approved requisitions without candidate master) -->
                                            @php
                                            $isProcessed = \App\Models\CandidateMaster::where('requisition_id', $requisition->id)->exists();
                                            $candidate = \App\Models\CandidateMaster::where('requisition_id', $requisition->id)->first();
                                            @endphp

                                            @if($requisition->status === 'Approved' && !$isProcessed)
                                            <button type="button" class="btn btn-sm btn-success process-btn"
                                                data-bs-toggle="modal" data-bs-target="#processModal"
                                                data-requisition-id="{{ $requisition->id }}"
                                                data-requisition-name="{{ $requisition->candidate_name }}"
                                                data-current-reporting="{{ $requisition->reporting_to }}"
                                                data-current-manager-id="{{ $requisition->reporting_manager_employee_id }}">
                                                <i class="ri-play-line"></i>
                                            </button>
                                            @elseif($requisition->status === 'Pending Approval')
                                            <span class="badge bg-info fs-9">Awaiting Approval</span>
                                            @endif

                                            <!-- After Processing: Show candidate status and workflow buttons -->
                                            @if($isProcessed && $candidate)
                                            @php
                                            $statusColors = [
                                            'Agreement Pending' => 'warning',
                                            'Unsigned Agreement Uploaded' => 'info',
                                            'Agreement Completed' => 'secondary',
                                            'Active' => 'success'
                                            ];
                                            $empStatus = $candidate->candidate_status ?? 'Agreement Pending';
                                            $empColor = $statusColors[$empStatus] ?? 'secondary';

                                            // Check if candidate has unsigned agreement
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
                                            @endphp

                                            <!-- Candidate Status Badge -->
                                            <span class="badge bg-{{ $empColor }}" title="Candidate Status: {{ $empStatus }}">
                                                {{ $candidate->candidate_code ?? $candidate->employee_id }}
                                            </span>

                                            <!-- Upload Signed Agreement Button (for Unsigned Agreement Uploaded status) -->
                                            @if($hasUnsigned && !$hasSigned)
                                            <button type="button"
                                                class="btn btn-sm btn-info upload-signed-btn"
                                                data-candidate-id="{{ $candidate->id }}"
                                                data-candidate-code="{{ $candidate->candidate_code ?? $candidate->employee_id }}"
                                                data-candidate-name="{{ $candidate->candidate_name }}"
                                                data-agreement-number="{{ $agreementNumber }}">
                                                <i class="ri-mail-line"></i>
                                            </button>
                                            @endif

                                            <!-- Active Status (read-only) -->
                                            @if($empStatus == "Active")
                                            <span class="badge bg-success">Active</span>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-2">No requisitions found.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($requisitions->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $requisitions->firstItem() }} to {{ $requisitions->lastItem() }} of {{ $requisitions->total() }} entries
                        </div>
                        <div>
                            {{ $requisitions->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Approved Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="processForm" action="{{ route('hr-admin.applications.process-modal') }}" method="POST">
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

<!-- Upload Signed Agreement Modal (from Email) -->
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
        $('.select2-modal').select2({
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

        // Submit upload signed form
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
        z-index: 1065 !important; /* Higher than Bootstrap modal */
    }
    
    .table {
        font-size: 0.85rem;
    }
    
    .table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        background-color: #f3f6f9;
        font-weight: 600;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.6em;
        font-weight: 500;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>
@endsection