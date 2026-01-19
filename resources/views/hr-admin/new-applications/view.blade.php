<!-- resources/views/hr-admin/new-applications/view.blade.php -->
@extends('layouts.guest')

@section('page-title', 'HR Verification - Requisition Details')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">HR Verification - Requisition Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('hr-admin.applications.new') }}">New Applications</a></li>
                        <li class="breadcrumb-item active">Verification</li>
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
                            <h5 class="card-title mb-0">
                                <span class="badge bg-secondary">{{ $requisition->requisition_id }}</span>
                                <span class="ms-2">{{ $requisition->candidate_name }}</span>
                                <small class="text-muted d-block mt-1">
                                    {{ $requisition->requisition_type }} | Submitted by: {{ $requisition->submitted_by_name }}
                                </small>
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Back
                                </a>
                                
                                @if($requisition->status === 'Pending HR Verification')
                                <button type="button" class="btn btn-warning btn-sm" 
                                        data-bs-toggle="modal" data-bs-target="#correctionModal">
                                    <i class="ri-arrow-go-back-line me-1"></i> Request Correction
                                </button>
                                
                                <button type="button" class="btn btn-success btn-sm" id="verifyBtn">
                                    <i class="ri-check-double-line me-1"></i> Verify Application
                                </button>
                                @endif
                                 @if($requisition->status === 'Hr Verified' || $showSendApprovalButton)
                                    <button type="button" class="btn btn-primary btn-sm" id="sendApprovalBtn">
                                        <i class="ri-send-plane-line me-1"></i> Send for Approval
                                    </button>
                                   @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Status Alert -->
                    <div class="alert alert-{{ $requisition->status === 'Pending HR Verification' ? 'warning' : 'info' }} mb-4">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1">Status: {{ $requisition->status }}</h6>
                                <p class="mb-0">Submitted: {{ $requisition->submission_date->format('d M Y, h:i A') }}</p>
                            </div>
                            @if($requisition->hr_verification_remarks)
                            <div class="flex-shrink-0">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" 
                                        data-bs-target="#hrRemarks">
                                    View HR Remarks
                                </button>
                            </div>
                            @endif
                        </div>
                        @if($requisition->hr_verification_remarks)
                        <div class="collapse mt-2" id="hrRemarks">
                            <div class="card card-body">
                                <strong>HR Remarks:</strong> {{ $requisition->hr_verification_remarks }}
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Compact Information Display with Edit Icons -->
                    <div class="row">
                        <!-- Basic Information Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Basic Information</h6>
                                    @if($requisition->status === 'Pending HR Verification')
                                    <button class="btn btn-sm btn-outline-primary edit-section-btn" 
                                            data-section="basic_info"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="ri-edit-line"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td width="45%"><small class="text-muted">Requisition ID:</small></td>
                                                <td><strong>{{ $requisition->requisition_id }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Type:</small></td>
                                                <td>{{ $requisition->requisition_type }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Submitted By:</small></td>
                                                <td>{{ $requisition->submitted_by_name }}<br>
                                                    <small class="text-muted">{{ $requisition->submitted_by_employee_id }}</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Submission Date:</small></td>
                                                <td>{{ $requisition->submission_date->format('d-m-Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Candidate Name:</small></td>
                                                <td>{{ $requisition->candidate_name }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Candidate Email:</small></td>
                                                <td>{{ $requisition->candidate_email }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Details Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Employment Details</h6>
                                    @if($requisition->status === 'Pending HR Verification')
                                    <button class="btn btn-sm btn-outline-primary edit-section-btn" 
                                            data-section="employment_details"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="ri-edit-line"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td width="45%"><small class="text-muted">Reporting To:</small></td>
                                                <td>{{ $requisition->reporting_to }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Reporting Manager ID:</small></td>
                                                <td>{{ $requisition->reporting_manager_employee_id }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Joining Date:</small></td>
                                                <td>{{ $requisition->date_of_joining_required->format('d-m-Y') }}</td>
                                            </tr>
                                            <tr>
                                                <td><small class="text-muted">Separation Date:</small></td>
                                                <td>{{ $requisition->date_of_separation->format('d-m-Y') }}</td>
                                            </tr>
                                            @if($requisition->agreement_duration)
                                            <tr>
                                                <td><small class="text-muted">Agreement Duration:</small></td>
                                                <td>{{ $requisition->agreement_duration }} months</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td><small class="text-muted">Remuneration:</small></td>
                                                <td class="text-success">₹{{ number_format($requisition->remuneration_per_month, 2) }}/month</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Personal Information</h6>
                                    @if($requisition->status === 'Pending HR Verification')
                                    <button class="btn btn-sm btn-outline-primary edit-section-btn" 
                                            data-section="personal_info"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="ri-edit-line"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td><small class="text-muted">Father's Name:</small></td>
                                                    <td>{{ $requisition->father_name }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">Mobile:</small></td>
                                                    <td>{{ $requisition->mobile_no }}</td>
                                                </tr>
                                                @if($requisition->alternate_email)
                                                <tr>
                                                    <td><small class="text-muted">Alt. Email:</small></td>
                                                    <td>{{ $requisition->alternate_email }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td><small class="text-muted">DOB:</small></td>
                                                    <td>{{ $requisition->date_of_birth->format('d-m-Y') }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">Gender:</small></td>
                                                    <td>{{ $requisition->gender }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">Qualification:</small></td>
                                                    <td>{{ $requisition->highest_qualification }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">Address:</small>
                                        <p class="mb-0 small">{{ $requisition->address_line_1 }}, {{ $requisition->city }}, 
                                            {{ $requisition->state_residence }} - {{ $requisition->pin_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Information Card (Read-only - HR cannot edit) -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0">Work Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td><small class="text-muted">Function:</small></td>
                                                    <td>{{ $requisition->function->function_name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">Department:</small></td>
                                                    <td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">Vertical:</small></td>
                                                    <td>{{ $requisition->vertical->vertical_name ?? 'N/A' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td><small class="text-muted">Location:</small></td>
                                                    <td>{{ $requisition->work_location_hq }}</td>
                                                </tr>
                                                <tr>
                                                    <td><small class="text-muted">State:</small></td>
                                                    <td>{{ $requisition->state_work_location }}</td>
                                                </tr>
                                                @if($requisition->district)
                                                <tr>
                                                    <td><small class="text-muted">District:</small></td>
                                                    <td>{{ $requisition->district }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">Organization:</small>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @if($requisition->zone)
                                            <span class="badge bg-info">{{ $requisition->zone }}</span>
                                            @endif
                                            @if($requisition->region)
                                            <span class="badge bg-info">{{ $requisition->region }}</span>
                                            @endif
                                            @if($requisition->territory)
                                            <span class="badge bg-info">{{ $requisition->territory }}</span>
                                            @endif
                                            @if($requisition->business_unit)
                                            <span class="badge bg-info">{{ $requisition->business_unit }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Extracted Information Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Kyc Information</h6>
                                    @if($requisition->status === 'Pending HR Verification')
                                    <button class="btn btn-sm btn-outline-primary edit-section-btn" 
                                            data-section="extracted_info"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="ri-edit-line"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                @if($requisition->pan_no)
                                                <tr>
                                                    <td><small class="text-muted">PAN No:</small></td>
                                                    <td>{{ $requisition->pan_no }}</td>
                                                </tr>
                                                @endif
                                                @if($requisition->aadhaar_no)
                                                <tr>
                                                    <td><small class="text-muted">Aadhaar No:</small></td>
                                                    <td>{{ $requisition->aadhaar_no }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                @if($requisition->bank_account_no)
                                                <tr>
                                                    <td><small class="text-muted">Bank A/C:</small></td>
                                                    <td>{{ $requisition->bank_account_no }}</td>
                                                </tr>
                                                @endif
                                                @if($requisition->bank_ifsc)
                                                <tr>
                                                    <td><small class="text-muted">IFSC Code:</small></td>
                                                    <td>{{ $requisition->bank_ifsc }}</td>
                                                </tr>
                                                @endif
                                                @if($requisition->bank_name)
                                                <tr>
                                                    <td><small class="text-muted">Bank Name:</small></td>
                                                    <td>{{ $requisition->bank_name }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border h-100">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Documents</h6>
                                    <span class="badge bg-primary">{{ $requisition->documents->count() }}</span>
                                </div>
                                <div class="card-body">
                                    @if($requisition->documents && $requisition->documents->count() > 0)
                                    <div class="row g-2">
                                        @foreach($requisition->documents as $document)
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-file-text-line me-2 text-primary"></i>
                                                    <div>
                                                        <small class="d-block text-muted">
                                                            @switch($document->document_type)
                                                                @case('pan_card') PAN Card @break
                                                                @case('aadhaar_card') Aadhaar Card @break
                                                                @case('bank_document') Bank Document @break
                                                                @case('resume') Resume @break
                                                                @case('driving_licence') Driving Licence @break
                                                                @default {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                                                            @endswitch
                                                        </small>
                                                        <small class="text-muted">{{ $document->file_name }}</small>
                                                    </div>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary view-document-btn" 
                                                            data-document-id="{{ $document->id }}"
                                                            data-document-name="{{ $document->file_name }}"
                                                            data-document-url="{{ Storage::disk('s3')->url($document->file_path) }}">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                    <a href="{{ Storage::disk('s3')->url($document->file_path) }}" 
                                                       download="{{ $document->file_name }}"
                                                       class="btn btn-outline-secondary">
                                                        <i class="ri-download-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="text-center py-3">
                                        <i class="ri-folder-open-line display-4 text-muted"></i>
                                        <p class="text-muted mt-2">No documents uploaded</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="documentViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalTitle">Document View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="documentLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <iframe id="documentViewer" style="display:none; width:100%; height:600px; border:none;"></iframe>
                <div id="documentError" class="text-center" style="display:none;">
                    <i class="ri-error-warning-line display-4 text-danger"></i>
                    <p class="mt-3">Unable to load document</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="documentDownloadLink" class="btn btn-primary" download>
                    <i class="ri-download-line me-1"></i> Download
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit <span id="sectionTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editFormContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
                <div class="modal-header">
                    <h5 class="modal-title">Verify Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line me-1"></i>
                        Please confirm that you have reviewed all information and documents.
                    </div>
                    
                    <div class="mb-3">
                        <label for="hr_verification_remarks" class="form-label">Verification Remarks</label>
                        <textarea class="form-control" id="hr_verification_remarks" name="hr_verification_remarks" 
                                  rows="4" placeholder="Add verification remarks..."></textarea>
                        <small class="text-muted">These remarks will be saved with the verification record.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
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
                <div class="modal-header">
                    <h5 class="modal-title">Request Correction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-1"></i>
                        This will send the requisition back to the submitter for corrections.
                    </div>
                    
                    <div class="mb-3">
                        <label for="correction_remarks" class="form-label">
                            Correction Remarks <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="correction_remarks" name="correction_remarks" 
                                  rows="4" placeholder="Please specify what needs to be corrected..." required
                                  minlength="10"></textarea>
                        <small class="text-muted">Be specific about what needs correction. Minimum 10 characters.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ri-arrow-go-back-line me-1"></i> Send for Correction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Approval Modal (Will be shown after verification is complete) -->
<div class="modal fade" id="sendApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('hr-admin.applications.send-approval', $requisition) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send for Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="ri-checkbox-circle-line me-1"></i>
                        Application verified. Ready to send for approval.
                    </div>
                    
                    @if(count($approvers) > 0)
                    <div class="mb-3">
                        <label for="approver_id" class="form-label">Select Approver <span class="text-danger">*</span></label>
                        <select class="form-select" id="approver_id" name="approver_id" required>
                            <option value="">-- Select Approver --</option>
                            @foreach($approvers as $approver)
                                <option value="{{ $approver['id'] }}">
                                    {{ $approver['name'] }} ({{ $approver['role'] }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the appropriate approver from the hierarchy</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approval_remarks" class="form-label">Approval Remarks</label>
                        <textarea class="form-control" id="approval_remarks" 
                                  name="approval_remarks" rows="3"
                                  placeholder="Add any remarks for the approver..."></textarea>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-1"></i>
                        No approvers found in the hierarchy. Please add approver roles to the system.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    @if(count($approvers) > 0)
                    <button type="submit" class="btn btn-success">
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
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .table-sm td {
        padding: 0.3rem 0;
    }
    .badge {
        font-size: 0.75em;
    }
    .edit-section-btn {
        padding: 0.1rem 0.5rem;
        font-size: 0.875rem;
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
        
        Swal.fire({
            title: 'Verify Application?',
            text: "Are you sure you want to verify this application?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, verify application',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
    
    // Document View Modal
    const viewDocumentBtns = document.querySelectorAll('.view-document-btn');
    viewDocumentBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const documentUrl = this.getAttribute('data-document-url');
            const documentName = this.getAttribute('data-document-name');
            
            $('#documentModalTitle').text(documentName);
            $('#documentDownloadLink').attr('href', documentUrl);
            $('#documentDownloadLink').attr('download', documentName);
            
            // Show loading
            $('#documentLoading').show();
            $('#documentViewer').hide();
            $('#documentError').hide();
            
            $('#documentViewModal').modal('show');
            
            // Load document in iframe
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
        });
    });
    
    // Edit Section Modal
    const editSectionBtns = document.querySelectorAll('.edit-section-btn');
    editSectionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            $('#editSection').val(section);
            
            // Load form content based on section
            loadEditForm(section);
        });
    });
    
    function loadEditForm(section) {
        const sectionTitles = {
            'basic_info': 'Basic Information',
            'personal_info': 'Personal Information',
            'employment_details': 'Employment Details',
            'extracted_info': 'Extracted Information'
        };
        
        $('#sectionTitle').text(sectionTitles[section] || section);
        
        // Show loading
        $('#editFormContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
        
        // Load form via AJAX
        $.ajax({
            url: '{{ route("hr-admin.applications.get-edit-form", $requisition) }}',
            method: 'GET',
            data: { section: section },
            success: function(response) {
                $('#editFormContent').html(response);
            },
            error: function() {
                $('#editFormContent').html(`
                    <div class="alert alert-danger">
                        <i class="ri-error-warning-line me-1"></i>
                        Failed to load edit form. Please try again.
                    </div>
                `);
            }
        });
    }
    
    // Edit Form Submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = '';
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        errors += value[0] + '\n';
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errors,
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });
    
    // Correction Modal Validation
    $('#correctionModal form').on('submit', function(e) {
        const remarks = $('#correction_remarks').val();
        if (remarks.length < 10) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Remarks Required',
                text: 'Please provide detailed correction remarks (minimum 10 characters).',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        e.preventDefault();
        Swal.fire({
            title: 'Send for Correction?',
            text: "This will return the requisition to the submitter for corrections.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fd7e14',
            confirmButtonText: 'Yes, send for correction',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });

    // Send Approval Button Click
    const sendApprovalBtn = document.getElementById('sendApprovalBtn');
    if (sendApprovalBtn) {
        sendApprovalBtn.addEventListener('click', function() {
            $('#sendApprovalModal').modal('show');
        });
    }
    
    // Send Approval Modal Validation
    $('#sendApprovalModal form').on('submit', function(e) {
        if (!$('#approver_id').val()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Approver Required',
                text: 'Please select an approver.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        e.preventDefault();
        Swal.fire({
            title: 'Send for Approval?',
            text: "This will send the requisition for approval.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, send for approval',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
});
</script>
@endsection