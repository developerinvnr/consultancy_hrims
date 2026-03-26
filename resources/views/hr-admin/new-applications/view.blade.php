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
                        <span class="badge bg-secondary fs-6">{{ $requisition->request_code }}</span>
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

                    @if($requisition->status === 'Pending HR Verification')
                    <button type="button" class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#rejectModal"
                        title="Reject Application">
                        <i class="ri-close-circle-line"></i>
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
                                <td class="fw-medium small">{{ $requisition->request_code }}</td>
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
                                <td class="small">
                                    {{ $requisition->qualification->EducationName ?? 'N/A' }}
                                    @if($requisition->qualification?->EducationCode)
                                    ({{ $requisition->qualification->EducationCode }})
                                    @endif
                                </td>
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
                                <td class="text-muted small">Contract Start Date:</td>
                                <td class="small">{{ $requisition->contract_start_date->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Contract End Date:</td>
                                <td class="small">{{ $requisition->contract_end_date->format('d-m-Y') }}</td>
                            </tr>
                            @if($requisition->contract_duration)
                            <tr>
                                <td class="text-muted small">Contract Duration:</td>
                                <td class="small">
                                    {{ intval($requisition->contract_duration / 30) }}
                                    month{{ ($requisition->contract_duration / 30) > 1 ? 's' : '' }}
                                </td>
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
                                <td class="small"> {{ $requisition->workState->state_name ?? 'N/A' }}</td>
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
                            @if($requisition->account_holder_name)
                            <tr>
                                <td class="text-muted small">Account Holder Name:</td>
                                <td class="small">{{ $requisition->account_holder_name }}</td>
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

            {{-- Timeline Section --}}
            <div class="mt-2">

                <div class="card border">

                    <div class="card-header bg-light py-1 px-2">
                        <h6 class="mb-0 fs-6">
                            <i class="ri-timeline-line me-1"></i>
                            Requisition Timeline
                        </h6>
                    </div>

                    <div class="card-body p-2">

                        <div class="d-flex flex-wrap gap-2 small">

                            {{-- Submitted --}}
                            @if($requisition->submission_date)
                            <span class="badge bg-primary">
                                Submitted<br>
                                {{ $requisition->submission_date->format('d M Y') }}
                            </span>
                            @endif


                            {{-- HR Verified --}}
                            @if($requisition->hr_verification_date)
                            <span class="badge bg-info">
                                HR Verified<br>
                                {{ $requisition->hr_verification_date->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Approved --}}
                            @if($requisition->approval_date)
                            <span class="badge bg-success">
                                Approved<br>
                                {{ $requisition->approval_date->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Agreement Created --}}
                            @if(($agreements['unsigned'] ?? collect())->first())
                            <span class="badge bg-secondary">
                                Agreement Created<br>
                                {{ $agreements['unsigned']->first()->created_at->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Agreement Signed --}}
                            @if($agreements['signed'])
                            <span class="badge bg-success">
                                Agreement Signed<br>
                                {{ $agreements['signed']->updated_at->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Courier Dispatched --}}
                            @if($agreements['signed']?->courierDetails?->dispatch_date)
                            <span class="badge bg-warning text-dark">
                                Courier Sent<br>
                                {{ $agreements['signed']->courierDetails->dispatch_date->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Courier Received --}}
                            @if($agreements['signed']?->courierDetails?->received_date)
                            <span class="badge bg-success">
                                Courier Received<br>
                                {{ $agreements['signed']->courierDetails->received_date->format('d M Y') }}
                            </span>
                            @endif


                            {{-- File Created --}}
                            @if($requisition->candidate?->file_created_date)
                            <span class="badge bg-primary">
                                File Created<br>
                                {{ \Carbon\Carbon::parse($requisition->candidate->file_created_date)->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Ledger Created --}}
                            @if($requisition->candidate?->ledger_created_at)
                            <span class="badge bg-info">
                                Ledger Created<br>
                                {{ \Carbon\Carbon::parse($requisition->candidate->ledger_created_at)->format('d M Y') }}
                            </span>
                            @endif


                            {{-- Contract Cancelled --}}
                            @if($requisition->candidate?->contract_cancelled_at)
                            <span class="badge bg-danger">
                                Contract Cancelled<br>
                                {{ \Carbon\Carbon::parse($requisition->candidate->contract_cancelled_at)->format('d M Y') }}
                            </span>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

            @if(
            auth()->user()->hasRole('hr_admin')
            && !$requisition->candidate?->contract_cancelled_at
            && !$requisition->candidate?->file_created_date
            )

            <div class="col-md-12 mt-2">

                <div class="card border-danger">
                    <div class="card-body p-2 d-flex justify-content-between align-items-center">

                        <div class="small text-danger fw-medium">
                            HR can cancel this contract if candidate has not joined
                        </div>

                        <button class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#cancelContractModal">

                            <i class="ri-close-circle-line me-1"></i>
                            Cancel Contract

                        </button>

                    </div>
                </div>

            </div>

            @endif

        </div>

        <!-- Documents Card -->
        <div class="col-md-6">
            <div class="card border h-100">
                <div class="card-header py-1 px-2 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">Documents</h6>
                    <div>
                        <span class="badge bg-primary fs-6">{{ $latestDocuments->count() }}</span>
                        @if(!empty($agreementDocuments) && count($agreementDocuments) > 0)
                        <span class="badge bg-success ms-1 fs-6">{{ count($agreementDocuments) }} A</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2">
                    <!-- Application Documents - Using latestDocuments -->
                    @if($latestDocuments && $latestDocuments->count() > 0)
                    <div class="mb-3">
                        <h6 class="text-muted small mb-2">Application Documents</h6>
                        <div class="row g-1">
                            @foreach($latestDocuments as $document)

                            @php
                            $status = null;
                            $statusClass = 'secondary';

                            switch($document->document_type) {

                            case 'pan_card':
                            $status = $requisition->pan_status_2 ?? 'Pending';
                            break;

                            case 'bank_document':
                            $status = $requisition->bank_verification_status ?? 'Pending';
                            break;

                            case 'driving_licence':
                            $status = $requisition->dl_verification_status ?? 'Pending';
                            break;

                            }

                            $statusClass = match(strtolower($status ?? 'pending')) {
                            'verified', 'valid', 'successful' => 'success',
                            'pending' => 'warning',
                            'failed', 'invalid', 'rejected', 'inoperative' => 'danger',
                            default => 'secondary'
                            };
                            @endphp
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center p-1 border rounded mb-1">

                                    <div class="d-flex align-items-center">
                                        <i class="ri-file-text-line me-1 text-primary fs-6"></i>

                                        <div>
                                            <small class="d-block text-muted">
                                                {{ ucfirst(str_replace('_',' ',$document->document_type)) }}
                                                <span class="text-muted ms-1">
                                                    ({{ $document->created_at->format('d-m-Y') }})
                                                </span>

                                                {{-- ✅ Verification Status Badge --}}
                                                @if($status)
                                                <span class="badge bg-{{ $statusClass }} ms-2">
                                                    {{ ucfirst($status) }}
                                                </span>
                                                @endif

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

                    <!-- Agreement Documents (unchanged) -->
                    @if(!empty($agreementDocuments) && count($agreementDocuments) > 0)
                    <div class="mt-3 pt-2 border-top">
                        <h6 class="text-muted small mb-2">Agreement Documents</h6>

                        @foreach($agreementDocuments as $doc)

                        <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2 {{ !$doc['has_file'] ? 'opacity-75' : '' }}">

                            <div class="d-flex align-items-center">
                                <i class="ri-file-contract-line me-2 {{ $doc['has_file'] ? 'text-success' : 'text-muted' }}"></i>

                                <div>
                                    <small class="d-block fw-semibold">
                                        {{ $doc['type'] }}
                                    </small>

                                    <small class="text-muted">
                                        Status:
                                        <span class="badge bg-light text-dark">
                                            {{ $doc['sign_status'] }}
                                        </span>

                                        @if(!empty($doc['agreement_number']))
                                        <span class="ms-2">#{{ $doc['agreement_number'] }}</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="btn-group btn-group-sm">
                                @if($doc['has_file'] && $doc['s3_url'])

                                <button class="btn btn-outline-primary view-agreement-document-btn"
                                    data-document-url="{{ $doc['s3_url'] }}"
                                    data-document-name="{{ $doc['file_name'] }}">
                                    <i class="ri-eye-line"></i>
                                </button>

                                <a href="{{ $doc['s3_url'] }}"
                                    download="{{ $doc['file_name'] }}"
                                    class="btn btn-outline-secondary">
                                    <i class="ri-download-line"></i>
                                </a>

                                @else
                                <button class="btn btn-light" disabled title="File not available">
                                    <i class="ri-eye-off-line"></i>
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

                    @php
                    $isTfaOrCb = in_array($requisition->requisition_type, ['TFA', 'CB']);
                    @endphp

                    <div class="mb-2">
                        <label for="team_id" class="form-label small">
                            Team <span class="text-danger">*</span>
                        </label>

                        <select name="team_id"
                            id="team_id"
                            class="form-select form-select-sm"
                            style="border-color:#007bff;"
                            {{ $isTfaOrCb ? 'readonly disabled' : '' }}
                            required>

                            @if($isTfaOrCb)
                            {{-- TFA / CB → fixed --}}
                            <option value="11" selected>TFA-CB</option>
                            @else
                            {{-- Contractual → selectable --}}
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
                            @endif
                        </select>

                        {{-- IMPORTANT: disabled fields are NOT submitted --}}
                        @if($isTfaOrCb)
                        <input type="hidden" name="team_id" value="11">
                        @endif

                        <small class="text-muted">
                            {{ $isTfaOrCb 
            ? 'Team is fixed for TFA / CB requisitions.'
            : 'Select the appropriate team for this Contractual requisition.' }}
                        </small>
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

<!-- Reject Application Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST"
                action="{{ route('hr-admin.applications.reject', $requisition) }}">
                @csrf

                <div class="modal-header py-2">
                    <h6 class="modal-title">Reject Application</h6>
                    <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="alert alert-danger">
                        This action cannot be undone.
                    </div>

                    <label class="form-label">
                        Rejection Reason <span class="text-danger">*</span>
                    </label>

                    <textarea name="rejection_reason"
                        class="form-control"
                        required minlength="5"></textarea>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button class="btn btn-danger">
                        Reject Application
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


{{-- Cancel Contract Modal (HR) --}}
@if(
auth()->user()->hasRole('hr_admin')
&& !$requisition->candidate?->contract_cancelled_at
&& !$requisition->candidate?->file_created_date
)

<div class="modal fade"
    id="cancelContractModal"
    tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST"
                action="{{ route('requisitions.cancel-contract', $requisition->id) }}">

                @csrf

                <div class="modal-header">

                    <h6 class="modal-title text-danger">
                        Cancel Contract
                    </h6>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

                </div>


                <div class="modal-body">

                    <div class="alert alert-danger py-1 small">
                        <i class="ri-error-warning-line me-1"></i>
                        This action will cancel the contract permanently.
                    </div>

                    <label class="form-label small text-danger">
                        Reason for cancellation *
                    </label>

                    <textarea name="cancel_reason"
                        class="form-control @error('cancel_reason') is-invalid @enderror"
                        rows="3"
                        required minlength="10"
                        placeholder="Enter cancellation reason">{{ old('cancel_reason') }}</textarea>

                    @error('cancel_reason')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                    @enderror

                </div>


                <div class="modal-footer">

                    <button type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="submit"
                        class="btn btn-danger btn-sm">

                        Confirm Cancel Contract

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endif
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

    .btn-group-xs>.btn {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .modal-header,
    .modal-footer {
        padding: 0.5rem 1rem;
    }

    .modal-body {
        padding: 1rem;
    }

    .badge {
        font-size: 0.7em;
        padding: 0.25em 0.5em;
    }

    .form-control-sm,
    .form-select-sm {
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

@push('scripts')

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
                data: {
                    section: section
                },
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
                        window.location.href = "{{ route('hr-admin.applications.view', $requisition) }}";
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
@endpush