<!-- resources/views/hr-admin/new-applications/view.blade.php -->
@extends('layouts.guest')

@section('page-title', 'HR Verification - Requisition Details')

@section('content')
<div class="container-fluid">
    <!-- Compact Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">
                        <span class="badge bg-secondary fs-6">{{ $requisition->requisition_id }}</span>
                        <span class="ms-2 fs-6">{{ $requisition->candidate_name }}</span>
                    </h5>
                    <p class="text-muted mb-0 small">
                        <span class="badge bg-{{ $requisition->status === 'Pending HR Verification' ? 'warning' : 'info' }} text-dark">
                            {{ $requisition->status }}
                        </span>
                        • {{ $requisition->requisition_type }} • Submitted: {{ $requisition->submission_date->format('d-m-Y H:i') }}
                    </p>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ url()->previous() }}" class="btn btn-light btn-sm" title="Back">
                        <i class="ri-arrow-left-line"></i>
                    </a>
                    
                    @if($requisition->status === 'Pending HR Verification')
                    <button type="button" class="btn btn-warning btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#correctionModal" title="Request Correction">
                        <i class="ri-arrow-go-back-line"></i>
                    </button>
                    
                    <button type="button" class="btn btn-success btn-sm" id="verifyBtn" title="Verify Application">
                        <i class="ri-check-double-line"></i>
                    </button>
                    @endif
                    
                    @if($requisition->status === 'Hr Verified' || $showSendApprovalButton)
                    <button type="button" class="btn btn-primary btn-sm" id="sendApprovalBtn" title="Send for Approval">
                        <i class="ri-send-plane-line"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Information Cards -->
    <div class="row g-2">
        <!-- Basic & Personal Info -->
        <div class="col-md-6">
            <div class="card border">
                <div class="card-header py-1 px-2 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">Basic & Personal Information</h6>
                    @if($requisition->status === 'Pending HR Verification')
                    <button class="btn btn-xs btn-outline-primary edit-section-btn"
                            data-section="basic_info"
                            data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="ri-edit-line fs-6"></i>
                    </button>
                    @endif
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td width="40%" class="text-muted small">Requisition ID:</td>
                                <td class="fw-medium small">{{ $requisition->requisition_id }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Type:</td>
                                <td class="small">{{ $requisition->requisition_type }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Submitted By:</td>
                                <td class="small">{{ $requisition->submitted_by_name }} ({{ $requisition->submitted_by_employee_id }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Candidate Name:</td>
                                <td class="small">{{ $requisition->candidate_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Candidate Email:</td>
                                <td class="small">{{ $requisition->candidate_email }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Father's Name:</td>
                                <td class="small">{{ $requisition->father_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Mobile:</td>
                                <td class="small">{{ $requisition->mobile_no }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">DOB:</td>
                                <td class="small">{{ $requisition->date_of_birth->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Gender:</td>
                                <td class="small">{{ $requisition->gender }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Qualification:</td>
                                <td class="small">{{ $requisition->highest_qualification }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Address:</td>
                                <td class="small">{{ $requisition->address_line_1 }}, {{ $requisition->city }}, {{ $requisition->state_residence }} - {{ $requisition->pin_code }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment & Work Info -->
        <div class="col-md-6">
            <div class="card border">
                <div class="card-header py-1 px-2 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">Employment & Work Information</h6>
                    @if($requisition->status === 'Pending HR Verification')
                    <button class="btn btn-xs btn-outline-primary edit-section-btn"
                            data-section="employment_details"
                            data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="ri-edit-line fs-6"></i>
                    </button>
                    @endif
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td width="40%" class="text-muted small">Reporting To:</td>
                                <td class="small">{{ $requisition->reporting_to }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Reporting Manager ID:</td>
                                <td class="small">{{ $requisition->reporting_manager_employee_id }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Joining Date:</td>
                                <td class="small">{{ $requisition->date_of_joining_required->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Separation Date:</td>
                                <td class="small">{{ $requisition->date_of_separation->format('d-m-Y') }}</td>
                            </tr>
                            @if($requisition->agreement_duration)
                            <tr>
                                <td class="text-muted small">Agreement Duration:</td>
                                <td class="small">{{ $requisition->agreement_duration }} months</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted small">Remuneration:</td>
                                <td class="small text-success fw-medium">₹{{ number_format($requisition->remuneration_per_month, 2) }}/month</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Function:</td>
                                <td class="small">{{ $requisition->function->function_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Department:</td>
                                <td class="small">{{ $requisition->department->department_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Vertical:</td>
                                <td class="small">{{ $requisition->vertical->vertical_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Location:</td>
                                <td class="small">{{ $requisition->work_location_hq }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">State:</td>
                                <td class="small">{{ $requisition->state_work_location }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC & Organization Info -->
        <div class="col-md-6">
            <div class="card border">
                <div class="card-header py-1 px-2 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">KYC Information</h6>
                    @if($requisition->status === 'Pending HR Verification')
                    <button class="btn btn-xs btn-outline-primary edit-section-btn"
                            data-section="extracted_info"
                            data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="ri-edit-line fs-6"></i>
                    </button>
                    @endif
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            @if($requisition->pan_no)
                            <tr>
                                <td width="40%" class="text-muted small">PAN No:</td>
                                <td class="small">{{ $requisition->pan_no }}</td>
                            </tr>
                            @endif
                            @if($requisition->aadhaar_no)
                            <tr>
                                <td class="text-muted small">Aadhaar No:</td>
                                <td class="small">{{ $requisition->aadhaar_no }}</td>
                            </tr>
                            @endif
                            @if($requisition->bank_account_no)
                            <tr>
                                <td class="text-muted small">Bank A/C:</td>
                                <td class="small">{{ $requisition->bank_account_no }}</td>
                            </tr>
                            @endif
                            @if($requisition->bank_ifsc)
                            <tr>
                                <td class="text-muted small">IFSC Code:</td>
                                <td class="small">{{ $requisition->bank_ifsc }}</td>
                            </tr>
                            @endif
                            @if($requisition->bank_name)
                            <tr>
                                <td class="text-muted small">Bank Name:</td>
                                <td class="small">{{ $requisition->bank_name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Card -->
        <div class="col-md-6">
            <div class="card border h-100">
                <div class="card-header py-1 px-2 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">Documents</h6>
                    <div>
                        <span class="badge bg-primary fs-6">{{ $requisition->documents->count() }}</span>
                        @if(!empty($agreementDocuments) && count($agreementDocuments) > 0)
                        <span class="badge bg-success ms-1 fs-6">{{ count($agreementDocuments) }} A</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2">
                    <!-- Application Documents -->
                    @if($requisition->documents && $requisition->documents->count() > 0)
                    <div class="mb-3">
                        <h6 class="text-muted small mb-2">Application Documents</h6>
                        <div class="row g-1">
                            @foreach($requisition->documents as $document)
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center p-1 border rounded mb-1">
                                    <div class="d-flex align-items-center">
                                        <i class="ri-file-text-line me-1 text-primary fs-6"></i>
                                        <div>
                                            <small class="d-block text-muted">
                                                @switch($document->document_type)
                                                    @case('pan_card') PAN @break
                                                    @case('aadhaar_card') Aadhaar @break
                                                    @case('bank_document') Bank @break
                                                    @case('resume') Resume @break
                                                    @case('driving_licence') DL @break
                                                    @default {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                                                @endswitch
                                            </small>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-xs">
                                        <button class="btn btn-outline-primary btn-xs view-document-btn"
                                                data-document-url="{{ Storage::disk('s3')->url($document->file_path) }}"
                                                data-document-name="{{ $document->file_name }}">
                                            <i class="ri-eye-line fs-6"></i>
                                        </button>
                                        <a href="{{ Storage::disk('s3')->url($document->file_path) }}" 
                                           download="{{ $document->file_name }}"
                                           class="btn btn-outline-secondary btn-xs">
                                            <i class="ri-download-line fs-6"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Agreement Documents -->
                    @if(!empty($agreementDocuments) && count($agreementDocuments) > 0)
                    <div class="mt-3 pt-2 border-top">
                        <h6 class="text-muted small mb-2">Agreement Documents</h6>
                        @foreach($agreementDocuments as $doc)
                        <div class="d-flex justify-content-between align-items-center p-1 border rounded mb-1 {{ !$doc['has_file'] ? 'opacity-75' : '' }}">
                            <div class="d-flex align-items-center">
                                <i class="ri-file-contract-line me-1 {{ $doc['has_file'] ? 'text-success' : 'text-muted' }} fs-6"></i>
                                <div>
                                    <small class="d-block {{ $doc['has_file'] ? 'text-dark' : 'text-muted' }}">
                                        {{ $doc['document_type'] }}
                                        @if(!empty($doc['agreement_number']))
                                        <span class="text-muted">#{{ $doc['agreement_number'] }}</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-xs">
                                @if($doc['has_file'] && $doc['s3_url'])
                                <button class="btn btn-outline-primary btn-xs view-agreement-document-btn"
                                        data-document-url="{{ $doc['s3_url'] }}"
                                        data-document-name="{{ $doc['file_name'] }}">
                                    <i class="ri-eye-line fs-6"></i>
                                </button>
                                <a href="{{ $doc['s3_url'] }}" 
                                   download="{{ $doc['file_name'] }}"
                                   class="btn btn-outline-secondary btn-xs">
                                    <i class="ri-download-line fs-6"></i>
                                </a>
                                @else
                                <button class="btn btn-xs btn-light" disabled title="File not available">
                                    <i class="ri-eye-off-line fs-6"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="documentViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="documentModalTitle">Document View</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-1">
                <div class="text-center py-4" id="documentLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <iframe id="documentViewer" style="display:none; width:100%; height:500px; border:none;"></iframe>
                <div id="documentError" class="text-center py-4" style="display:none;">
                    <i class="ri-error-warning-line text-danger fs-1"></i>
                    <p class="mt-2 small">Unable to load document</p>
                </div>
            </div>
            <div class="modal-footer py-1">
                <a href="#" id="documentDownloadLink" class="btn btn-primary btn-sm" download>
                    <i class="ri-download-line me-1"></i> Download
                </a>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editForm" method="POST" action="{{ route('hr-admin.applications.update', $requisition) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" id="editSection">
                
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Edit <span id="sectionTitle"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2" id="editFormContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify Application Modal -->
<div class="modal fade" id="verifyApplicationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('hr-admin.applications.verify-application', $requisition) }}">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Verify Application</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="alert alert-info py-1 mb-2">
                        <i class="ri-information-line me-1"></i>
                        Please confirm that you have reviewed all information and documents.
                    </div>
                    
                    <div class="mb-2">
                        <label for="hr_verification_remarks" class="form-label small">Verification Remarks</label>
                        <textarea class="form-control form-control-sm" id="hr_verification_remarks" 
                                  name="hr_verification_remarks" rows="3"></textarea>
                        <small class="text-muted">These remarks will be saved with the verification record.</small>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ri-check-double-line me-1"></i> Verify Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Correction Request Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('hr-admin.applications.request-correction', $requisition) }}">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Request Correction</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="alert alert-warning py-1 mb-2">
                        <i class="ri-alert-line me-1"></i>
                        This will send the requisition back to the submitter for corrections.
                    </div>
                    
                    <div class="mb-2">
                        <label for="correction_remarks" class="form-label small">
                            Correction Remarks <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="correction_remarks" 
                                  name="correction_remarks" rows="3" required minlength="10"></textarea>
                        <small class="text-muted">Be specific about what needs correction.</small>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="ri-arrow-go-back-line me-1"></i> Send for Correction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Approval Modal -->
<div class="modal fade" id="sendApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('hr-admin.applications.send-approval', $requisition) }}">
                @csrf
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Send for Approval</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="alert alert-success py-1 mb-2">
                        <i class="ri-checkbox-circle-line me-1"></i>
                        Application verified. Ready to send for approval.
                    </div>
                    
                    @if(count($approvers) > 0)
                    <div class="mb-2">
                        <label for="approver_id" class="form-label small">Select Approver <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="approver_id" name="approver_id" required>
                            <option value="">-- Select Approver --</option>
                            @foreach($approvers as $approver)
                            <option value="{{ $approver['id'] }}">
                                {{ $approver['name'] }} ({{ $approver['role'] }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the appropriate approver from the hierarchy</small>
                    </div>
                    
                    <div class="mb-2">
                        <label for="approval_remarks" class="form-label small">Approval Remarks</label>
                        <textarea class="form-control form-control-sm" id="approval_remarks" 
                                  name="approval_remarks" rows="2"></textarea>
                    </div>
                    @else
                    <div class="alert alert-warning py-1">
                        <i class="ri-alert-line me-1"></i>
                        No approvers found in the hierarchy.
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    @if(count($approvers) > 0)
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ri-send-plane-line me-1"></i> Send for Approval
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Compact styling */
    body {
        font-size: 0.875rem;
    }
    .card {
        font-size: 0.8125rem;
    }
    .card-header {
        padding: 0.25rem 0.5rem;
    }
    .card-body {
        padding: 0.5rem;
    }
    .table-sm td {
        padding: 0.15rem 0;
        font-size: 0.8125rem;
    }
    .btn-xs {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }
    .btn-group-xs > .btn {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }
    .modal-header, .modal-footer {
        padding: 0.5rem 1rem;
    }
    .modal-body {
        padding: 1rem;
    }
    .badge {
        font-size: 0.7em;
        padding: 0.25em 0.5em;
    }
    .form-control-sm, .form-select-sm {
        font-size: 0.875rem;
    }
    .small {
        font-size: 0.8125rem;
    }
    .fs-6 {
        font-size: 0.875rem !important;
    }
    .row.g-2 {
        margin-top: -0.5rem;
    }
</style>
@endpush

@section('script_section')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Verify Application Button Click
        const verifyBtn = document.getElementById('verifyBtn');
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function() {
                $('#verifyApplicationModal').modal('show');
            });
        }

        // Verify Application Form Submission
        $('#verifyApplicationModal form').on('submit', function(e) {
            e.preventDefault();
            $(this).unbind('submit').submit();
        });

        // Function to open document modal
        function openDocumentModal(documentUrl, documentName) {
            $('#documentModalTitle').text(documentName);
            $('#documentDownloadLink').attr('href', documentUrl);
            $('#documentDownloadLink').attr('download', documentName);

            $('#documentLoading').show();
            $('#documentViewer').hide();
            $('#documentError').hide();

            $('#documentViewModal').modal('show');

            const iframe = document.getElementById('documentViewer');
            iframe.src = documentUrl;

            iframe.onload = function() {
                $('#documentLoading').hide();
                $('#documentViewer').show();
            };

            iframe.onerror = function() {
                $('#documentLoading').hide();
                $('#documentError').show();
            };
        }

        // Application Document View
        document.querySelectorAll('.view-document-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                openDocumentModal(
                    this.getAttribute('data-document-url'),
                    this.getAttribute('data-document-name')
                );
            });
        });

        // Agreement Document View
        document.querySelectorAll('.view-agreement-document-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                openDocumentModal(
                    this.getAttribute('data-document-url'),
                    this.getAttribute('data-document-name')
                );
            });
        });

        // Clear iframe on modal close
        $('#documentViewModal').on('hidden.bs.modal', function() {
            document.getElementById('documentViewer').src = '';
        });

        // Edit Section Modal
        document.querySelectorAll('.edit-section-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                $('#editSection').val(section);
                loadEditForm(section);
            });
        });

        function loadEditForm(section) {
            const titles = {
                'basic_info': 'Basic Information',
                'personal_info': 'Personal Information',
                'employment_details': 'Employment Details',
                'extracted_info': 'KYC Information'
            };
            $('#sectionTitle').text(titles[section] || section);
            
            $('#editFormContent').html(`
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $.ajax({
                url: '{{ route("hr-admin.applications.get-edit-form", $requisition) }}',
                method: 'GET',
                data: { section: section },
                success: function(response) {
                    $('#editFormContent').html(response);
                },
                error: function() {
                    $('#editFormContent').html(`
                        <div class="alert alert-danger py-1">
                            <i class="ri-error-warning-line me-1"></i>
                            Failed to load edit form.
                        </div>
                    `);
                }
            });
        }

        // Edit Form Submission
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'PUT',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#editModal').modal('hide');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON?.errors) {
                        let errors = '';
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errors += value[0] + '\n';
                        });
                        alert('Validation Error: ' + errors);
                    }
                }
            });
        });

        // Correction Modal
        $('#correctionModal form').on('submit', function(e) {
            if ($('#correction_remarks').val().length < 10) {
                e.preventDefault();
                alert('Please provide detailed correction remarks (minimum 10 characters).');
                return false;
            }
            e.preventDefault();
            $(this).unbind('submit').submit();
        });

        // Send Approval Button
        const sendApprovalBtn = document.getElementById('sendApprovalBtn');
        if (sendApprovalBtn) {
            sendApprovalBtn.addEventListener('click', function() {
                $('#sendApprovalModal').modal('show');
            });
        }

        // Send Approval Modal
        $('#sendApprovalModal form').on('submit', function(e) {
            if (!$('#approver_id').val()) {
                e.preventDefault();
                alert('Please select an approver.');
                return false;
            }
            e.preventDefault();
            $(this).unbind('submit').submit();
        });
    });
</script>
@endsection