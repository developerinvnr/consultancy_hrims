<!-- resources/views/dashboard/partials/approver-stats.blade.php -->
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Approval Dashboard</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-warning">{{ $approval_stats['pending'] }}</h2>
                            <p class="text-muted mb-0">Pending Approvals</p>
                            <small class="text-muted">Awaiting your action</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-success">{{ $approval_stats['total_approved'] }}</h2>
                            <p class="text-muted mb-0">Approved</p>
                            <small class="text-muted">Total approved by you</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-danger">{{ $approval_stats['total_rejected'] }}</h2>
                            <p class="text-muted mb-0">Rejected</p>
                            <small class="text-muted">Total rejected by you</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>