@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Approval Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">Approver Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal">Pending Approvals</h5>
                            <h4 class="mb-0">{{ $pendingRequisitions->count() }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="ri-time-line fs-4"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal">Approved</h5>
                            <h4 class="mb-0">{{ $requisitionsHistory->where('status', 'Approved')->count() }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="ri-check-double-line fs-4"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal">Rejected</h5>
                            <h4 class="mb-0">{{ $requisitionsHistory->where('status', 'Rejected')->count() }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-danger rounded-circle">
                                    <i class="ri-close-line fs-4"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal">Total Actions</h5>
                            <h4 class="mb-0">{{ $requisitionsHistory->count() }}</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-info rounded-circle">
                                    <i class="ri-list-check fs-4"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pending Approvals</h5>
                        <span class="badge bg-warning">{{ $pendingRequisitions->count() }} pending</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($pendingRequisitions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Requisition ID</th>
                                    <th>Candidate</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Submitted On</th>
                                    <th>Remuneration</th>
                                    <th>HR Verified</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingRequisitions as $requisition)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $requisition->requisition_id }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $requisition->candidate_name }}</div>
                                        <small class="text-muted">{{ $requisition->candidate_email }}</small>
                                    </td>
                                    <td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                                    <td>{{ $requisition->requisition_type }}</td>
                                    <td>
                                        {{ $requisition->submission_date->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">{{ $requisition->submission_date->format('h:i A') }}</small>
                                    </td>
                                    <td class="text-success">
                                        ₹{{ number_format($requisition->remuneration_per_month, 2) }}
                                    </td>
                                    <td>
                                        {{ $requisition->hr_verification_date->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">by {{ $requisition->hr_verified_id }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('approver.requisition.view', $requisition) }}" 
                                               class="btn btn-primary">
                                                <i class="ri-eye-line"></i> View
                                            </a>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="showApproveModal('{{ $requisition->id }}', '{{ $requisition->requisition_id }}')">
                                                <i class="ri-check-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="showRejectModal('{{ $requisition->id }}', '{{ $requisition->requisition_id }}')">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="ri-inbox-line display-4 text-muted"></i>
                        <h5 class="mt-3">No pending approvals</h5>
                        <p class="text-muted">You don't have any requisitions pending for approval.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Approval History Table -->
    @if($requisitionsHistory->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Approval History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Requisition ID</th>
                                    <th>Candidate</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Action Date</th>
                                    <th>Remarks</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisitionsHistory as $history)
                                <tr>
                                    <td>{{ $history->requisition_id }}</td>
                                    <td>{{ $history->candidate_name }}</td>
                                    <td>{{ $history->department->department_name ?? 'N/A' }}</td>
                                    <td>
                                        @if($history->status == 'Approved')
                                        <span class="badge bg-success">Approved</span>
                                        @elseif($history->status == 'Rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @else
                                        <span class="badge bg-info">{{ $history->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->status == 'Approved' && $history->approval_date)
                                            {{ $history->approval_date->format('d M Y, h:i A') }}
                                        @elseif($history->status == 'Rejected' && $history->rejection_date)
                                            {{ $history->rejection_date->format('d M Y, h:i A') }}
                                        @else
                                            {{ $history->updated_at->format('d M Y, h:i A') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->status == 'Approved')
                                            <small>{{ Str::limit($history->approver_remarks, 50) }}</small>
                                        @elseif($history->status == 'Rejected')
                                            <small>{{ Str::limit($history->rejection_reason, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('approver.requisition.view', $history) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="ri-check-line me-1"></i>
                        Are you sure you want to approve this requisition?
                    </div>
                    <p class="mb-2">Requisition ID: <strong id="approveReqId"></strong></p>
                    
                    <div class="mb-3">
                        <label for="approver_remarks" class="form-label">Approval Remarks (Optional)</label>
                        <textarea class="form-control" id="approver_remarks" name="approver_remarks" 
                                  rows="3" placeholder="Add approval remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-check-line me-1"></i> Approve
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
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="ri-alert-line me-1"></i>
                        Are you sure you want to reject this requisition?
                    </div>
                    <p class="mb-2">Requisition ID: <strong id="rejectReqId"></strong></p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" placeholder="Please specify the reason for rejection..." 
                                  required minlength="10"></textarea>
                        <small class="text-muted">Please provide detailed reason for rejection (minimum 10 characters).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-close-line me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showApproveModal(requisitionId, requisitionIdText) {
    const form = document.getElementById('approveForm');
    form.action = `/approver/requisitions/${requisitionId}/approve`;
    document.getElementById('approveReqId').textContent = requisitionIdText;
    $('#approveModal').modal('show');
}

function showRejectModal(requisitionId, requisitionIdText) {
    const form = document.getElementById('rejectForm');
    form.action = `/approver/requisitions/${requisitionId}/reject`;
    document.getElementById('rejectReqId').textContent = requisitionIdText;
    $('#rejectModal').modal('show');
}

// Form validations
$('#approveForm').on('submit', function(e) {
    e.preventDefault();
    
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

$('#rejectForm').on('submit', function(e) {
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
</script>
@endpush