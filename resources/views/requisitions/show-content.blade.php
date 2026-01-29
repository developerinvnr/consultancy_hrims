<!-- resources/views/requisitions/show-content.blade.php -->
<!-- Status Badge -->
<div class="row mb-3">
    <div class="col-12">
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
        <div class="alert alert-{{ $color }} py-2 mb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong class="fs-6">Status:</strong> <span class="badge bg-{{ $color }} text-dark">{{ $requisition->status }}</span>
                    @if($requisition->hr_verification_remarks)
                    <span class="ms-3"><small><strong>HR Remarks:</strong> {{ $requisition->hr_verification_remarks }}</small></span>
                    @endif
                    @if($requisition->rejection_reason)
                    <span class="ms-3"><small><strong>Rejection:</strong> {{ $requisition->rejection_reason }}</small></span>
                    @endif
                </div>
                <div class="text-muted small">
                    Submitted: {{ $requisition->submission_date->format('d-m-Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compact Information Grid -->
<div class="row g-2 mb-3">
    <!-- Basic Information -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">Basic Information</h6>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">Requisition ID:</td>
                        <td class="small fw-medium">{{ $requisition->requisition_id }}</td>
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
                </table>
            </div>
        </div>
    </div>

    <!-- Employment Details -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">Employment Details</h6>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">Reporting To:</td>
                        <td class="small">{{ $requisition->reporting_to }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Manager ID:</td>
                        <td class="small">{{ $requisition->reporting_manager_employee_id }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Contract start Date:</td>
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
                </table>
            </div>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">Personal Information</h6>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">Father's Name:</td>
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
                    @if($requisition->alternate_email)
                    <tr>
                        <td class="text-muted small">Alt. Email:</td>
                        <td class="small">{{ $requisition->alternate_email }}</td>
                    </tr>
                    @endif
                    @if($requisition->college_name)
                    <tr>
                        <td class="text-muted small">College:</td>
                        <td class="small">{{ $requisition->college_name }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">Address Information</h6>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">Address:</td>
                        <td class="small">{{ $requisition->address_line_1 }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">City:</td>
                        <td class="small">{{ $requisition->city }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">State:</td>
                        <td class="small">{{ $requisition->state_residence }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">PIN Code:</td>
                        <td class="small">{{ $requisition->pin_code }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Work Information -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">Work Information</h6>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted small">Location:</td>
                        <td class="small">{{ $requisition->work_location_hq }}</td>
                    </tr>
                    @if($requisition->district)
                    <tr>
                        <td class="text-muted small">District:</td>
                        <td class="small">{{ $requisition->district }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted small">Work State:</td>
                        <td class="small">{{ $requisition->state_work_location }}</td>
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
                </table>
            </div>
        </div>
    </div>

    <!-- KYC Information -->
    <div class="col-md-6">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <h6 class="mb-0 fs-6">KYC Information</h6>
            </div>
            <div class="card-body p-2">
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
                        <td  class="text-muted small">Account Holder Name:</td>
                        <td class="small">{{ $requisition->account_holder_name }}</td>
                    </tr>
                    @endif
                    @if($requisition->bank_account_no)
                    <tr>
                        <td class="text-muted small">Bank Account:</td>
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

    <!-- Documents -->
    <div class="col-md-12">
        <div class="card border">
            <div class="card-header bg-light py-1 px-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fs-6">Documents</h6>
                    <span class="badge bg-primary fs-6">{{ $requisition->documents->count() }}</span>
                </div>
            </div>
            <div class="card-body p-2">
                @if($requisition->documents && $requisition->documents->count() > 0)
                <div class="row g-1">
                    @foreach($requisition->documents as $document)
                    @php
                    $s3Url = Storage::disk('s3')->url($document->file_path);
                    $iconClass = [
                        'pan_card' => 'ri-bank-card-line',
                        'aadhaar_card' => 'ri-id-card-line',
                        'bank_document' => 'ri-bank-line',
                        'resume' => 'ri-file-text-line',
                        'driving_licence' => 'ri-car-line',
                        'zbm_gm_approval' => 'ri-approval-line'
                    ][$document->document_type] ?? 'ri-file-line';
                    @endphp
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center p-1 border rounded mb-1">
                            <div class="d-flex align-items-center">
                                <i class="{{ $iconClass }} me-1 text-primary fs-6"></i>
                                <div>
                                    <small class="d-block text-muted">
                                        @switch($document->document_type)
                                            @case('pan_card') PAN Card @break
                                            @case('aadhaar_card') Aadhaar Card @break
                                            @case('bank_document') Bank Document @break
                                            @case('resume') Resume @break
                                            @case('driving_licence') Driving Licence @break
                                            @case('zbm_gm_approval') ZBM/GM Approval @break
                                            @default {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                                        @endswitch
                                    </small>
                                    <small class="text-muted">{{ $document->file_name }}</small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-xs">
                                <a href="{{ $s3Url }}" 
                                   target="_blank"
                                   class="btn btn-outline-primary btn-xs"
                                   title="View">
                                    <i class="ri-eye-line fs-6"></i>
                                </a>
                                <a href="{{ $s3Url }}" 
                                   download="{{ $document->file_name }}"
                                   class="btn btn-outline-secondary btn-xs"
                                   title="Download">
                                    <i class="ri-download-line fs-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-2">
                    <i class="ri-folder-open-line text-muted fs-3"></i>
                    <p class="text-muted mt-1 small">No documents uploaded</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add compact CSS styles -->
<style>
    .compact-view .card {
        font-size: 0.8125rem;
    }
    .compact-view .card-header {
        padding: 0.25rem 0.5rem;
    }
    .compact-view .card-body {
        padding: 0.5rem;
    }
    .compact-view .table-sm td {
        padding: 0.15rem 0;
        font-size: 0.8125rem;
    }
    .compact-view .btn-xs {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }
    .compact-view .btn-group-xs > .btn {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }
    .compact-view .badge {
        font-size: 0.7em;
        padding: 0.25em 0.5em;
    }
    .compact-view .fs-6 {
        font-size: 0.875rem !important;
    }
    .compact-view .small {
        font-size: 0.8125rem;
    }
    .compact-view .row.g-2 {
        margin-top: -0.5rem;
    }
    .compact-view .alert {
        padding: 0.5rem 1rem;
    }
    .compact-view .alert .fs-6 {
        font-size: 0.875rem;
    }
</style>