@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Back Button and Breadcrumb -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <a href="{{ route('my-team.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="ri-arrow-left-line"></i> Back
                </a>
                <h4 class="mb-0">Candidate Details</h4>
            </div>
        </div>
    </div>

    <!-- Compact Candidate Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-md bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <span class="text-white fs-4 fw-semibold">{{ substr($candidate->candidate_name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="mb-0">{{ $candidate->candidate_name }}</h5>
                                <span class="badge bg-primary">{{ $candidate->candidate_code }}</span>
                                <span class="badge bg-info">{{ $candidate->requisition_type }}</span>
                            </div>
                            <div class="small text-muted">
                                <i class="ri-mail-line"></i> {{ $candidate->candidate_email }}
                                | <i class="ri-phone-line"></i> {{ $candidate->mobile_no }}
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-end">
                            <div class="small">
                                <span class="text-muted">Reporting To:</span><br>
                                <strong>{{ $candidate->reporting_to }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Layout -->
    <div class="row">
        <!-- Left Column - Compact Information -->
        <div class="col-lg-7">
            <!-- Basic & Personal Info Card -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="ri-user-line"></i> Basic & Personal Info</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Requisition ID</label>
                            <div class="fw-semibold">{{ $candidate->candidate_code }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Submitted By</label>
                            <div class="fw-semibold">{{ $submittedByName }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Father's Name</label>
                            <div class="fw-semibold">{{ $candidate->father_name }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Date of Birth</label>
                            <div class="fw-semibold">{{ $candidate->date_of_birth ? \Carbon\Carbon::parse($candidate->date_of_birth)->format('d-m-Y') : 'N/A' }}</div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="small text-muted mb-1">Address</label>
                            <div class="fw-semibold small">{{ $candidate->address_line_1 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment & Work Info Card -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="ri-briefcase-line"></i> Employment & Work Details</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Contract Start Date</label>
                            <div class="fw-semibold">{{ $candidate->contract_start_date ? \Carbon\Carbon::parse($candidate->contract_start_date)->format('d-m-Y') : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Remuneration</label>
                            <div class="fw-semibold">{{ $candidate->remuneration_per_month ? '₹' . number_format($candidate->remuneration_per_month) : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Work Location</label>
                            <div class="fw-semibold">{{ $candidate->work_location_hq }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">State</label>
                            <div class="fw-semibold">{{ $candidate->state_work_location }}</div>
                        </div>
                        @if($candidate->function_id)
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Function</label>
                            <div class="fw-semibold">{{ $functionName ?? 'N/A' }}</div>
                        </div>
                        @endif
                        @if($candidate->department_id)
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Department</label>
                            <div class="fw-semibold">{{ $departmentName ?? 'N/A' }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - KYC & Documents -->
        <div class="col-lg-5">
            <!-- KYC Information -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="ri-shield-check-line"></i> KYC Information</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">PAN No</label>
                            <div class="fw-semibold small">{{ $candidate->pan_no }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Aadhaar No</label>
                            <div class="fw-semibold small">{{ $candidate->aadhaar_no ? 'XXXX' . substr($candidate->aadhaar_no, -4) : 'N/A' }}</div>
                        </div>
                         <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Account Holder Name</label>
                            <div class="fw-semibold small">{{ $candidate->account_holder_name }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Bank Account</label>
                            <div class="fw-semibold small">{{ $candidate->bank_account_no ? 'XXXX' . substr($candidate->bank_account_no, -4) : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small text-muted mb-1">Bank IFSC</label>
                            <div class="fw-semibold small">{{ $candidate->bank_ifsc }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0 d-flex justify-content-between align-items-center">
                        <span><i class="ri-folder-line"></i> Documents</span>
                        <span class="badge bg-primary">{{ count($documents) }}</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(count($documents) === 0)
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="ri-information-line"></i> No documents uploaded.
                        </div>
                    @else
                        <div class="documents-list" style="max-height: 300px; overflow-y: auto;">
                            @foreach($documents as $doc)
                                <div class="document-item border-bottom py-2 {{ !$doc['has_file'] ? 'opacity-75' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="fw-semibold small {{ !$doc['has_file'] ? 'text-muted' : '' }}">
                                                    {{ $doc['document_type'] }}
                                                </span>
                                                @if(!$doc['has_file'])
                                                    <span class="badge bg-warning ms-1 small">Missing</span>
                                                @endif
                                                @if($doc['type'])
                                                    <span class="badge bg-light text-dark ms-1 small">{{ $doc['type'] }}</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                <i class="ri-time-line"></i> {{ $doc['uploaded_at'] }}
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ms-2">
                                            @if($doc['has_file'] && $doc['s3_url'])
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ $doc['s3_url'] }}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-primary"
                                                       title="View document">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ $doc['s3_url'] }}" 
                                                       download="{{ $doc['file_name'] }}"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Download">
                                                        <i class="ri-download-line"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <button class="btn btn-sm btn-light" disabled title="File not available">
                                                    <i class="ri-eye-off-line"></i>
                                                </button>
                                            @endif
                                        </div>
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
@endsection

@section('script_section')
<script>
    $(document).ready(function() {
        $('[title]').tooltip();
    });
</script>

<style>
    .avatar {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .documents-list {
        scrollbar-width: thin;
    }
    
    .documents-list::-webkit-scrollbar {
        width: 4px;
    }
    
    .documents-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .documents-list::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 2px;
    }
    
    .document-item:last-child {
        border-bottom: none !important;
    }
</style>
@endsection