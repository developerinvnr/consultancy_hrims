<!-- resources/views/hr-admin/dashboard.blade.php -->
@extends('layouts.guest')

@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">

	<div class="row">
		<!-- Page Header -->
		<div class="row">
			<div class="col-12">
				<div class="page-title-box d-sm-flex align-items-center justify-content-between">
					<h4 class="mb-sm-0">HR Admin Panel</h4>
					<div class="page-title-right">
						<ol class="breadcrumb m-0">
							<li class="breadcrumb-item"><a href="{{ route('hr-admin.dashboard') }}">HR Admin</a></li>
							<li class="breadcrumb-item active"></li>
						</ol>
					</div>
				</div>
			</div>
		</div>
		
    <!-- HR Admin Navigation Tabs -->
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-tabs nav-tabs-custom">
                <li class="nav-item">
                    <a class="nav-link @if(Route::is('hr-admin.dashboard')) active @endif" 
                       href="{{ route('hr-admin.dashboard') }}">
                        <i class="ri-dashboard-line me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Route::is('hr-admin.applications.*')) active @endif" 
                       href="{{ route('hr-admin.applications.new') }}">
                        <i class="ri-file-list-line me-1"></i> New Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Route::is('hr-admin.master.*')) active @endif" 
                       href="{{ route('hr-admin.master.index') }}">
                        <i class="ri-database-2-line me-1"></i> Master
                    </a>
                </li>
            </ul>
        </div>
    </div>
		<!-- Statistics Cards -->
		<div class="row">
			<!-- Statistics Cards -->
			<div class="col-xl-3 col-md-6">
				<div class="card stat-card bg-primary text-white">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-grow-1">
								<h5 class="mb-0">{{ $stats['pending_verification'] }}</h5>
								<p class="text-white-50 mb-0">Pending Verification</p>
							</div>
							<div class="flex-shrink-0">
								<i class="ri-time-line display-4 text-white-50"></i>
							</div>
						</div>
					</div>
					<div class="card-footer bg-primary border-top-0 py-2">
						<a href="{{ route('hr-admin.applications.new') }}" class="text-white d-block text-end">
							View Details <i class="ri-arrow-right-line align-middle"></i>
						</a>
					</div>
				</div>
			</div>
			
			<div class="col-xl-3 col-md-6">
				<div class="card stat-card bg-success text-white">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-grow-1">
								<h5 class="mb-0">{{ $stats['hr_verified'] }}</h5>
								<p class="text-white-50 mb-0">HR Verified</p>
							</div>
							<div class="flex-shrink-0">
								<i class="ri-check-line display-4 text-white-50"></i>
							</div>
						</div>
					</div>
					<div class="card-footer bg-success border-top-0 py-2">
						<a href="{{ route('hr-admin.applications.new') }}?status=Hr+Verified" class="text-white d-block text-end">
							View Details <i class="ri-arrow-right-line align-middle"></i>
						</a>
					</div>
				</div>
			</div>
			
			<div class="col-xl-3 col-md-6">
				<div class="card stat-card bg-info text-white">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-grow-1">
								<h5 class="mb-0">{{ $stats['pending_approval'] }}</h5>
								<p class="text-white-50 mb-0">Pending Approval</p>
							</div>
							<div class="flex-shrink-0">
								<i class="ri-user-follow-line display-4 text-white-50"></i>
							</div>
						</div>
					</div>
					<div class="card-footer bg-info border-top-0 py-2">
						<a href="{{ route('hr-admin.applications.new') }}?status=Pending+Approval" class="text-white d-block text-end">
							View Details <i class="ri-arrow-right-line align-middle"></i>
						</a>
					</div>
				</div>
			</div>
			
			<div class="col-xl-3 col-md-6">
				<div class="card stat-card bg-warning text-dark">
					<div class="card-body">
						<div class="d-flex align-items-center">
							<div class="flex-grow-1">
								<h5 class="mb-0">{{ $stats['approved'] }}</h5>
								<p class="text-muted mb-0">Approved</p>
							</div>
							<div class="flex-shrink-0">
								<i class="ri-check-double-line display-4 text-warning"></i>
							</div>
						</div>
					</div>
					<div class="card-footer bg-warning border-top-0 py-2">
						<a href="{{ route('hr-admin.applications.approved') }}" class="text-dark d-block text-end">
							View Details <i class="ri-arrow-right-line align-middle"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
</div>

	<!-- Recent Activity -->
	<div class="row mt-4">
		<div class="col-lg-8">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Recent Requisitions</h5>
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
									<th>Date</th>
								</tr>
							</thead>
							<tbody>
								@php
									$recentRequisitions = \App\Models\ManpowerRequisition::with(['submittedBy'])
										->orderBy('created_at', 'desc')
										->limit(10)
										->get();
								@endphp
								
								@foreach($recentRequisitions as $req)
								<tr>
									<td>
										<a href="{{ route('hr-admin.applications.view', $req) }}" class="text-primary">
											{{ $req->requisition_id }}
										</a>
									</td>
									<td>
										<div>{{ $req->candidate_name }}</div>
										<small class="text-muted">{{ $req->submittedBy->name ?? 'N/A' }}</small>
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
												'Rejected' => 'dark'
											];
										@endphp
										<span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}">
											{{ $req->status }}
										</span>
									</td>
									<td>{{ $req->created_at->format('d-M-Y') }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		
		<div class="col-lg-4">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Quick Actions</h5>
				</div>
				<div class="card-body">
					<div class="d-grid gap-2">
						<a href="{{ route('hr-admin.applications.new') }}" class="btn btn-primary">
							<i class="ri-file-list-line me-2"></i> Process New Applications
						</a>
						<a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-success">
							<i class="ri-checkbox-circle-line me-2"></i> Process Approved
						</a>
						<a href="{{ route('hr-admin.master.index') }}" class="btn btn-info">
							<i class="ri-database-2-line me-2"></i> Manage Employees
						</a>
						<a href="#" class="btn btn-warning">
							<i class="ri-file-download-line me-2"></i> Generate Reports
						</a>
					</div>
					
					<hr>
					
					<h6 class="mb-3">Status Distribution</h6>
					<div class="row">
						@php
							$statuses = \App\Models\ManpowerRequisition::select('status', DB::raw('count(*) as count'))
								->groupBy('status')
								->get();
						@endphp
						
						@foreach($statuses as $status)
						<div class="col-6 mb-2">
							<div class="d-flex justify-content-between">
								<span class="text-muted">{{ $status->status }}</span>
								<span class="fw-bold">{{ $status->count }}</span>
							</div>
							<div class="progress" style="height: 5px;">
								<div class="progress-bar" role="progressbar" 
									style="width: {{ ($status->count / max($stats['total_employees'], 1)) * 100 }}%">
								</div>
							</div>
						</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
@endsection