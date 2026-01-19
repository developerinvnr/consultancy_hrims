@extends('layouts.guest')

@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Welcome, {{ Auth::user()->name }}!</h5>
                    <p class="card-text">
                        @if(Auth::user()->hasRole('hr_admin'))
                            HR Admin Dashboard - Manage employee requisitions and agreements
                        @elseif(isset($pending_approvals) && $pending_approvals->count() > 0)
                            Approver Dashboard - You have {{ $pending_approvals->count() }} pending approvals
                        @else
                            Employee Dashboard - Track your requisitions and applications
                        @endif
                    </p>
                    
                    <!-- Simple Stats for HR Admin -->
                    @if(Auth::user()->hasRole('hr_admin') && isset($hr_stats))
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="alert alert-warning mb-0">
                                <strong>{{ $hr_stats['pending_verification'] }}</strong> Pending Verification
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-success mb-0">
                                <strong>{{ $hr_stats['approved'] }}</strong> Approved
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-info mb-0">
                                <strong>{{ $hr_stats['agreement_pending'] ?? 0 }}</strong> Agreement Pending
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-secondary mb-0">
                                <strong>{{ $hr_stats['total_candidates'] ?? 0 }}</strong> Total Candidates
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Section (for approvers only) -->
    @if(isset($pending_approvals) && $pending_approvals->count() > 0)
        @include('dashboard.partials.pending-approvals')
    @endif

    <!-- Recent Requisitions with Action Buttons -->
    @if(isset($recent_requisitions) && $recent_requisitions->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Requisitions</h5>
                        @if(Auth::user()->hasRole('hr_admin'))
                            <div class="btn-group">
                                <a href="{{ route('hr-admin.applications.new') }}" class="btn btn-sm btn-primary">
                                    <i class="ri-file-list-line me-1"></i> New Applications
                                    @if(isset($hr_stats) && $hr_stats['pending_verification'] > 0)
                                        <span class="badge bg-white text-primary ms-1">{{ $hr_stats['pending_verification'] }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-sm btn-success">
                                    <i class="ri-check-double-line me-1"></i> Approved
                                    @if(isset($hr_stats) && $hr_stats['approved'] > 0)
                                        <span class="badge bg-white text-success ms-1">{{ $hr_stats['approved'] }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('hr-admin.agreement.list') }}" class="btn btn-sm btn-info">
                                    <i class="ri-file-text-line me-1"></i> Agreements
                                    @if(isset($hr_stats) && $hr_stats['agreement_pending'] > 0)
                                        <span class="badge bg-white text-info ms-1">{{ $hr_stats['agreement_pending'] }}</span>
                                    @endif
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Requisition ID</th>
                                        <th>Candidate</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Submitted By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_requisitions as $req)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $req->requisition_id }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $req->candidate_name }}</div>
                                            <small class="text-muted">{{ $req->candidate_email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $req->requisition_type == 'Contractual' ? 'primary' : ($req->requisition_type == 'TFA' ? 'success' : 'info') }}">
                                                {{ $req->requisition_type }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'Pending HR Verification' => 'warning',
                                                    'Pending Approval' => 'info',
                                                    'Approved' => 'success',
                                                    'Correction Required' => 'danger',
                                                    'Processed' => 'secondary',
                                                    'Rejected' => 'dark',
                                                    'Hr Verified' => 'success',
                                                    'Unsigned Agreement Uploaded' => 'primary',
                                                    'Signed Agreement Uploaded' => 'info',
                                                    'Agreement Completed' => 'success'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td>{{ $req->submittedBy->name ?? 'N/A' }}</td>
                                        <td>{{ $req->created_at->format('d-M-Y') }}</td>
										<td>
    <div class="btn-group" role="group">
        <!-- HR Admin Actions -->
        @if(Auth::user()->hasRole('hr_admin'))
            <a href="{{ route('hr-admin.applications.view', $req) }}" 
               class="btn btn-sm btn-outline-primary" title="View Details">
                <i class="ri-eye-line"></i>
            </a>
            
            <!-- Upload Unsigned Agreement Button -->
            @if($req->status == 'Agreement Pending')
                @php
                    $candidateForUpload = CandidateMaster::where('requisition_id', $req->id)->first();
                @endphp
                @if($candidateForUpload)
                    <button type="button" 
                            class="btn btn-sm btn-outline-success upload-unsigned-btn"
                            data-candidate-id="{{ $candidateForUpload->id }}"
                            data-candidate-code="{{ $candidateForUpload->candidate_code }}"
                            data-candidate-name="{{ $candidateForUpload->candidate_name }}"
                            title="Upload Unsigned Agreement">
                        <i class="ri-file-upload-line"></i>
                    </button>
                @endif
            @endif
            
            <!-- Upload Signed Agreement Button (from email) -->
            @if($req->status == 'Awaiting Signed Agreement')
                @php
                    $candidateForSigned = CandidateMaster::where('requisition_id', $req->id)->first();
                @endphp
                @if($candidateForSigned)
                    <button type="button" 
                            class="btn btn-sm btn-outline-info upload-signed-btn"
                            data-candidate-id="{{ $candidateForSigned->id }}"
                            data-candidate-code="{{ $candidateForSigned->candidate_code }}"
                            data-candidate-name="{{ $candidateForSigned->candidate_name }}"
                            title="Upload Signed Agreement (from email)">
                        <i class="ri-mail-send-line"></i>
                    </button>
                @endif
            @endif
            
        <!-- Submitter Actions -->
        @elseif(Auth::user()->id == $req->submitted_by_user_id)
            <a href="{{ route('requisitions.show', $req) }}" 
               class="btn btn-sm btn-outline-secondary" title="View">
                <i class="ri-eye-line"></i>
            </a>
            
            <!-- View/Download Unsigned Agreement -->
            @if($req->status == 'Unsigned Agreement Uploaded')
                <a href="{{ route('submitter.agreement.view', $req) }}" 
                   class="btn btn-sm btn-outline-primary" title="View Agreement">
                    <i class="ri-file-text-line"></i>
                </a>
            @endif
            
            <!-- View Completed Agreement -->
            @if($req->status == 'Agreement Completed')
                <a href="{{ route('submitter.agreement.view', $req) }}" 
                   class="btn btn-sm btn-outline-success" title="View Completed Agreement">
                    <i class="ri-check-double-line"></i>
                </a>
            @endif
            
        <!-- Approver Actions -->
        @elseif($req->approver_id == Auth::user()->employee_id && $req->status == 'Pending Approval')
            <a href="{{ route('approver.requisition.view', $req) }}" 
               class="btn btn-sm btn-outline-warning" title="Review">
                <i class="ri-search-eye-line"></i>
            </a>
            
        <!-- Others -->
        @else
            <a href="{{ route('requisitions.show', $req) }}" 
               class="btn btn-sm btn-outline-secondary" title="View">
                <i class="ri-eye-line"></i>
            </a>
        @endif
    </div>
</td>
                                        {{--<td>
                                            <div class="btn-group" role="group">
                                                <!-- View Button -->
                                                @if(Auth::user()->hasRole('hr_admin'))
                                                    <a href="{{ route('hr-admin.applications.view', $req) }}" 
                                                    class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    
                                                    <!-- Upload Unsigned Agreement Button -->
                                                    @if($req->status == 'Approved')
                                                        <a href="{{ route('hr-admin.agreement.upload-unsigned', $req) }}" 
                                                        class="btn btn-sm btn-outline-success" title="Upload Unsigned Agreement">
                                                            <i class="ri-file-upload-line"></i>
                                                        </a>
                                                    @endif
                                                    
                                                    <!-- Upload Signed Agreement Button -->
                                                    @if($req->status == 'Unsigned Agreement Uploaded')
                                                        <a href="{{ route('hr-admin.agreement.upload-signed', $req) }}" 
                                                        class="btn btn-sm btn-outline-info" title="Upload Signed Agreement">
                                                            <i class="ri-file-signature-line"></i>
                                                        </a>
                                                    @endif
                                                    
                                                    <!-- Manage Agreement Button -->
                                                    @if(in_array($req->status, ['Unsigned Agreement Uploaded', 'Signed Agreement Uploaded', 'Agreement Completed']))
                                                        <a href="{{ route('hr-admin.agreement.management', $req) }}" 
                                                        class="btn btn-sm btn-outline-warning" title="Manage Agreement">
                                                            <i class="ri-file-settings-line"></i>
                                                        </a>
                                                    @endif
                                                @elseif($req->approver_id == Auth::user()->employee_id && $req->status == 'Pending Approval')
                                                    <a href="{{ route('approver.requisition.view', $req) }}" 
                                                    class="btn btn-sm btn-outline-warning" title="Review">
                                                        <i class="ri-search-eye-line"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('requisitions.show', $req) }}" 
                                                    class="btn btn-sm btn-outline-secondary" title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                @endif
                                                
                                                <!-- Submitter Upload Button -->
                                                @if(Auth::user()->id == $req->submitted_by_user_id && $req->status == 'Unsigned Agreement Uploaded')
                                                    <a href="{{ route('submitter.agreement.upload-signed', $req) }}" 
                                                    class="btn btn-sm btn-outline-success" title="Upload Signed Agreement">
                                                        <i class="ri-file-upload-line"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>--}}
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Reject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Requisition ID: <strong id="rejectReqId"></strong></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" placeholder="Please provide reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection