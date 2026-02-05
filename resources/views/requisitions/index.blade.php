@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Manpower Requisitions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Requisitions</a></li>
                        <li class="breadcrumb-item active">My Requisitions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- End page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">My Requisitions</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <!-- Filter Form -->
                                <form method="GET" action="{{ route('requisitions.index') }}" class="d-flex me-2">
                                    {{--<input type="text" name="search" class="form-control form-control-sm me-2" 
                                           placeholder="Search..." value="{{ request('search') }}">--}}
                                    <select name="type" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                                        <option value="Contractual" {{ request('type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                                        @if($isSalesDepartment)
                                            <option value="TFA" {{ request('type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                                            <option value="CB" {{ request('type') == 'CB' ? 'selected' : '' }}>CB</option>
                                        @endif
                                    </select>
                                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="">All Status</option>
                                        <option value="Pending HR Verification" {{ request('status') == 'Pending HR Verification' ? 'selected' : '' }}>Pending HR Verification</option>
                                        <option value="Correction Required" {{ request('status') == 'Correction Required' ? 'selected' : '' }}>Correction Required</option>
                                        <option value="Pending Approval" {{ request('status') == 'Pending Approval' ? 'selected' : '' }}>Pending Approval</option>
                                        <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="Processed" {{ request('status') == 'Processed' ? 'selected' : '' }}>Processed</option>
                                        <option value="Agreement Pending" {{ request('status') == 'Agreement Pending' ? 'selected' : '' }}>Agreement Pending</option>
                                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm me-2" style="color:#000;">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <a href="{{ route('requisitions.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </form>
                                
                                <!-- Create New Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" style="background-color:#a5cccd; color:#000; border-color:#a5cccd;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-add-line me-1"></i> Create New
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('requisitions.create', ['type' => 'Contractual']) }}">Contractual</a></li>
                                        @if($isSalesDepartment)
                                            <li><a class="dropdown-item" href="{{ route('requisitions.create', ['type' => 'TFA']) }}">TFA</a></li>
                                            <li><a class="dropdown-item" href="{{ route('requisitions.create', ['type' => 'CB']) }}">CB</a></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th width="10%">Req ID</th>
                                    <th width="15%">Type</th>
                                    <th width="20%">Candidate Name</th>
                                    <th width="15%">Email</th>
                                    <th width="15%">Status</th>
                                    <th width="15%">Submitted Date</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisitions as $requisition)
                                <tr>
                                    <td>{{ $requisition->requisition_id }}</td>
                                    <td>
                                        <span class="badge bg-primary" style="color:#000;">{{ $requisition->requisition_type }}</span>
                                    </td>
                                    <td>{{ $requisition->candidate_name }}</td>
                                    <td>{{ $requisition->candidate_email }}</td>
                                    <td>
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
                                        <span class="badge bg-{{ $color }}">{{ $requisition->status }}</span>
                                    </td>
                                    <td>{{ $requisition->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('requisitions.show', $requisition->id) }}" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            @if($requisition->status == 'Correction Required')
                                            <a href="{{ route('requisitions.edit', $requisition->id) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            @endif

                                            <!-- View/Download Unsigned Agreement -->
											@if($requisition->status == 'Unsigned Agreement Uploaded')
											<a href="{{ route('submitter.agreement.view', $requisition) }}"
												class="btn btn-sm btn-outline-primary" title="View Agreement">
												<i class="ri-file-text-line"></i>
											</a>
											@endif

                                            	<!-- View Completed Agreement -->
											@if($requisition->status == 'Agreement Completed')
											<a href="{{ route('submitter.agreement.view', $requisition) }}"
												class="btn btn-sm btn-outline-success" title="View Completed Agreement">
												<i class="ri-check-double-line"></i>
											</a>
											@endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No requisitions found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     @if($requisitions instanceof \Illuminate\Pagination\LengthAwarePaginator)
							<div class="d-flex justify-content-end mt-3">
								{{ $requisitions->links('pagination::bootstrap-5') }}
							</div>
						@endif
                    <!-- Pagination -->
                  
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    .table {
        font-size: 0.85rem;
    }
    
    .table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        background-color: #f3f6f9;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.6em;
    }
</style>
