<!-- resources/views/dashboard/partials/pending-approvals.blade.php -->
<div class="row mb-4" id="pendingApprovals">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Approvals</h5>
                    <span class="badge bg-warning">{{ $pending_approvals->count() }} pending</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Req ID</th>
                                <th>Candidate</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>HR Verified On</th>
                                <th>Remuneration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending_approvals as $requisition)
                            <tr>
                                <td>{{ $requisition->requisition_id }}</td>
                                <td>
                                    <div>{{ $requisition->candidate_name }}</div>
                                    <small class="text-muted">{{ $requisition->candidate_email }}</small>
                                </td>
                                <td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $requisition->requisition_type == 'Contractual' ? 'primary' : 
                                        ($requisition->requisition_type == 'TFA' ? 'success' : 'info') 
                                    }}">
                                        {{ $requisition->requisition_type }}
                                    </span>
                                </td>
                                <td>
                                    {{ $requisition->hr_verification_date->format('d-M-Y') }}
                                    <br>
                                    <small class="text-muted">{{ $requisition->hr_verified_id }}</small>
                                </td>
                                <td>₹{{ number_format($requisition->remuneration_per_month, 2) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('approver.requisition.view', $requisition) }}" 
                                           class="btn btn-primary" title="Review">
                                            <i class="ri-eye-line"></i> Review
                                        </a>
                                        
                                        <!-- Quick Approve -->
                                        <form action="{{ route('approver.requisition.approve', $requisition) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="approver_remarks" value="Approved via dashboard">
                                            <button type="button" 
                                                    class="btn btn-success quick-approve-btn"
                                                    data-req-id="{{ $requisition->requisition_id }}">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Quick Reject -->
                                        <button type="button" 
                                                class="btn btn-danger quick-reject-btn"
                                                data-req-id="{{ $requisition->requisition_id }}"
                                                data-req-route="{{ route('approver.requisition.reject', $requisition) }}">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
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