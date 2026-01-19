<!-- resources/views/dashboard/partials/hr-stats.blade.php -->
@if(isset($hr_stats))
<div class="col-12 mb-4">
    <div class="row">
        <!-- Just show 4 key metrics in a simple row -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-primary bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h2 class="text-primary mb-1">{{ $hr_stats['pending_verification'] }}</h2>
                    <p class="text-muted mb-0">Pending Verification</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-success bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h2 class="text-success mb-1">{{ $hr_stats['approved'] }}</h2>
                    <p class="text-muted mb-0">Approved</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-info bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h2 class="text-info mb-1">{{ $hr_stats['agreement_pending'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Agreement Pending</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-dark bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h2 class="text-dark mb-1">{{ $hr_stats['total_candidates'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Total Candidates</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif