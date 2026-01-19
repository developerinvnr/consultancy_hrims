<!-- resources/views/hr-admin/new-applications/index.blade.php -->
@extends('layouts.guest')

@section('page-title', 'New Applications - Pending Verification')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">Pending HR Verification</h5>
                <p class="text-muted mb-0">Total: {{ $requisitions->total() }} requisitions</p>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by ID, Name, Email..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="ri-search-line"></i>
                        </button>
                        @if(request('search'))
                        <a href="{{ route('hr-admin.applications.new') }}" class="btn btn-light">
                            <i class="ri-close-line"></i>
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="120">Requisition ID</th>
                        <th width="100">Submission Date</th>
                        <th width="100">Type</th>
                        <th>Candidate Name</th>
                        <th>Candidate Email</th>
                        <th>Submitted By</th>
                        <th width="150">Department</th>
                        <th width="150">Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions as $requisition)
                    <tr>
                        <td>
                            <strong>{{ $requisition->requisition_id }}</strong>
                        </td>
                        <td>{{ $requisition->submission_date->format('d-M-Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $requisition->requisition_type == 'Contractual' ? 'primary' : ($requisition->requisition_type == 'TFA' ? 'success' : 'info') }}">
                                {{ $requisition->requisition_type }}
                            </span>
                        </td>
                        <td>
                            <div>{{ $requisition->candidate_name }}</div>
                        </td>
                        <td>
                            {{ $requisition->candidate_email }}
                        </td>
                        <td>
                            <div>{{ $requisition->submittedBy->name ?? 'N/A' }}</div>
                        </td>
                        <td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-warning">{{ $requisition->status }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('hr-admin.applications.view', $requisition) }}" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="ri-eye-line"></i>
                                </a>
                                {{--<a href="{{ route('hr-admin.applications.verify', $requisition) }}" 
                                   class="btn btn-outline-success" title="Verify">
                                    <i class="ri-check-double-line"></i>
                                </a>--}}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ri-inbox-line display-4"></i>
                                <h5 class="mt-2">No pending requisitions</h5>
                                <p>All requisitions have been processed.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($requisitions->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $requisitions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection