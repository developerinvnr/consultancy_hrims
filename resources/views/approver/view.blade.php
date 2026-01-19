<!-- resources/views/approver/view.blade.php -->
@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Review Requisition for Approval</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">View Requisition</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">
                                <span class="badge bg-secondary">{{ $requisition->requisition_id }}</span>
                                <span class="ms-2">{{ $requisition->candidate_name }}</span>
                                <small class="text-muted d-block mt-1">
                                    {{ $requisition->requisition_type }} | 
                                    Submitted by: {{ $requisition->submitted_by_name }}
                                    @if($requisition->submitted_by_employee_id)
                                        ({{ $requisition->submitted_by_employee_id }})
                                    @endif
                                </small>
                            </h5>
                        </div>
                        <div>
                            <!-- Self-approval warning -->
                            @php
                                // Get submitter's emp_id (not employee_id)
                                $submitterEmpId = $requisition->submittedBy->emp_id ?? null;
                                $currentUserEmpId = Auth::user()->emp_id ?? null;
                            @endphp
                            
                            @if($submitterEmpId && $submitterEmpId == $currentUserEmpId)
                                <div class="alert alert-danger mb-0 me-3 d-inline-block py-1 px-2">
                                    <i class="ri-alert-line me-1"></i>
                                    <strong>Conflict of Interest:</strong> You submitted this requisition
                                </div>
                            @endif
                            
                            @if($requisition->status === 'Pending Approval')
                            <div class="btn-group">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal" 
                                        @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif>
                                    <i class="ri-check-line me-1"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"
                                        @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif>
                                    <i class="ri-close-line me-1"></i> Reject
                                </button>
                            </div>
                            @endif
                            <a href="{{ route('dashboard') }}" class="btn btn-light ms-2">
                                <i class="ri-arrow-left-line me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Status Alert -->
                    <div class="alert alert-{{ 
                        $requisition->status === 'Pending Approval' ? 'warning' : 
                        ($requisition->status === 'Approved' ? 'success' : 'danger') 
                    }} mb-4">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1">
                                    Status: {{ $requisition->status }}
                                    @if($requisition->status === 'Pending Approval')
                                        <small class="text-muted">(Awaiting your approval)</small>
                                    @endif
                                </h6>
                                <p class="mb-0">
                                    <strong>Submitted:</strong> {{ $requisition->submission_date->format('d M Y, h:i A') }}
                                    @if($requisition->hr_verification_date)
                                        | <strong>HR Verified:</strong> {{ $requisition->hr_verification_date->format('d M Y, h:i A') }}
                                        by {{ $requisition->hr_verified_id }}
                                    @endif
                                    @if($requisition->approval_date && $requisition->status === 'Approved')
                                        | <strong>Approved:</strong> {{ $requisition->approval_date->format('d M Y, h:i A') }}
                                    @endif
                                </p>
                                @if($requisition->hr_verification_remarks)
                                <p class="mb-0 mt-1">
                                    <strong>HR Remarks:</strong> {{ $requisition->hr_verification_remarks }}
                                </p>
                                @endif
                                @if($requisition->approver_remarks && $requisition->status === 'Approved')
                                <p class="mb-0 mt-1">
                                    <strong>Approver Remarks:</strong> {{ $requisition->approver_remarks }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Self-approval warning message -->
                    @if($submitterEmpId && $submitterEmpId == $currentUserEmpId)
                    <div class="alert alert-danger mb-4">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Important Notice:</strong> 
                        You cannot approve or reject a requisition that you submitted. 
                        This is a conflict of interest. Please contact HR to reassign this requisition to another approver.
                    </div>
                    @endif

                    <!-- Use existing show-content view -->
                    @include('requisitions.show-content', ['requisition' => $requisition])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('approver.requisition.approve', $requisition) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="ri-check-line me-1"></i>
                        Confirm approval of requisition <strong>{{ $requisition->requisition_id }}</strong>
                    </div>
                    
                    <!-- Self-approval warning in modal -->
                    @if($submitterEmpId && $submitterEmpId == $currentUserEmpId)
                    <div class="alert alert-danger">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Conflict of Interest:</strong> 
                        You cannot approve your own requisition. Please contact HR to reassign.
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="approver_remarks" class="form-label">Approval Remarks (Optional)</label>
                        <textarea class="form-control" id="approver_remarks" name="approver_remarks" 
                                  rows="3" placeholder="Add approval remarks..."
                                  @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" 
                            @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif>
                        <i class="ri-check-line me-1"></i> Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('approver.requisition.reject', $requisition) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="ri-alert-line me-1"></i>
                        Confirm rejection of requisition <strong>{{ $requisition->requisition_id }}</strong>
                    </div>
                    
                    <!-- Self-rejection warning in modal -->
                    @if($submitterEmpId && $submitterEmpId == $currentUserEmpId)
                    <div class="alert alert-danger">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Conflict of Interest:</strong> 
                        You cannot reject your own requisition. Please contact HR to reassign.
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" placeholder="Please specify the reason for rejection..." 
                                  required minlength="10"
                                  @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif></textarea>
                        <small class="text-muted">Please provide detailed reason for rejection (minimum 10 characters).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"
                            @if($submitterEmpId && $submitterEmpId == $currentUserEmpId) disabled @endif>
                        <i class="ri-close-line me-1"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script_section')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the emp_id values for comparison
    const submitterEmpId = "{{ $submitterEmpId ?? '' }}";
    const currentUserEmpId = "{{ $currentUserEmpId ?? '' }}";
    const isSelfApproval = submitterEmpId && submitterEmpId === currentUserEmpId;
    
    // Approve Modal Validation
    $('#approveModal form').on('submit', function(e) {
        e.preventDefault();
        
        // Check for self-approval
        if (isSelfApproval) {
            Swal.fire({
                icon: 'error',
                title: 'Conflict of Interest',
                text: 'You cannot approve your own requisition. Please contact HR.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        Swal.fire({
            title: 'Approve Requisition?',
            text: "This action will approve the requisition.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, approve it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
    
    // Reject Modal Validation
    $('#rejectModal form').on('submit', function(e) {
        // Check for self-rejection
        if (isSelfApproval) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Conflict of Interest',
                text: 'You cannot reject your own requisition. Please contact HR.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        const reason = $('#rejection_reason').val();
        if (reason.length < 10) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Reason Required',
                text: 'Please provide detailed rejection reason (minimum 10 characters).',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        e.preventDefault();
        Swal.fire({
            title: 'Reject Requisition?',
            text: "This action will reject the requisition.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, reject it',
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