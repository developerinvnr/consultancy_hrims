@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">HR - Requisitions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">HR</a></li>
                        <li class="breadcrumb-item active">Requisitions</li>
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
                            <h5 class="card-title mb-0">All Requisitions</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-2">
                                <!-- Filter Form -->
                                <form method="GET" action="{{ route('hr_requisitions.index') }}" class="d-flex me-2">
                                    <input type="text"
                                        name="search"
                                        class="form-control form-control-sm me-2"
                                        placeholder="Search by ID, Name, Email, Code..."
                                        value="{{ request('search') }}"
                                        style="width: 200px;">
                                    <select name="type" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                                        <option value="Contractual" {{ request('type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                                        <option value="TFA" {{ request('type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                                        <option value="CB" {{ request('type') == 'CB' ? 'selected' : '' }}>CB</option>
                                    </select>
                                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="">All Status</option>
                                        <option value="Pending HR Verification" {{ request('status') == 'Pending HR Verification' ? 'selected' : '' }}>Pending HR Verification</option>
                                        <option value="Correction Required" {{ request('status') == 'Correction Required' ? 'selected' : '' }}>Correction Required</option>
                                        <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                                        <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="Processed" {{ request('status') == 'Processed' ? 'selected' : '' }}>Processed</option>
                                        <option value="Agreement Pending" {{ request('status') == 'Agreement Pending' ? 'selected' : '' }}>Agreement Pending</option>
                                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    <select name="employee_status" class="form-select form-select-sm me-2" style="width: auto;">
                                        <option value="">All Party Status</option>
                                        <option value="Active" {{ request('employee_status') == 'Active' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="Inactive" {{ request('employee_status') == 'Inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm me-2" style="color:#000;">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <a href="{{ route('hr_requisitions.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="ri-refresh-line"></i>
                                    </a>
                                </form>

                                <!-- Create New Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-success btn-sm dropdown-toggle" style="color:#000; border-color:#28a745;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-add-line me-1"></i> Create
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'Contractual']) }}">Contractual</a></li>
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'tfa']) }}">TFA</a></li>
                                        <li><a class="dropdown-item" href="{{ route('hr_requisitions.direct.create', ['type' => 'CB']) }}">CB</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive requisition-table-wrapper">
                        <table class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th width="8%">Req ID</th>
                                    <th width="8%">Type</th>
                                    <th width="15%">Candidate</th>
                                    <th width="15%">Submitted By</th>
                                    <th width="8%">Date</th>
                                    <th width="12%">Req Status</th>
                                    <th width="12%">Party Code</th>
                                    <th width="12%">Party Status</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisitions as $requisition)
                                <tr>
                                    @php
                                    $candidate = $requisition->candidate;
                                    $isProcessed = !is_null($candidate);
                                    @endphp
                                    <td>
                                        <strong>{{ $requisition->id }}</strong>
                                    </td>
                                    <td>
                                        @php
                                        $typeColors = [
                                        'TFA' => 'info',
                                        'CB' => 'warning',
                                        'Contractual' => 'primary'
                                        ];
                                        $color = $typeColors[$requisition->requisition_type] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $requisition->requisition_type }}</span>
                                    </td>
                                    <td>
                                        <div><strong>{{ $requisition->candidate_name }}</strong></div>
                                        <small class="text-muted">{{ $requisition->candidate_email }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $requisition->submitted_by_name }}</div>
                                        <small class="text-muted">ID: {{ $requisition->submitted_by_employee_id }}</small>
                                    </td>
                                    <td>{{ $requisition->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        @php
                                        $status = $requisition->status;

                                        // change only display text
                                        $displayStatus = $status === 'Unsigned Agreement Created'
                                        ? 'Unsigned Agreement Created'
                                        : $status;

                                        $statusColors = [
                                        'Pending HR Verification' => 'warning',
                                        'Correction Required' => 'danger',
                                        'Pending Approval' => 'info',
                                        'Approved' => 'success',
                                        'Rejected' => 'dark',
                                        'Processed' => 'primary',
                                        'Agreement Pending' => 'secondary',
                                        'Completed' => 'success',
                                        'Unsigned Agreement Created' => 'info'
                                        ];

                                        $color = $statusColors[$status] ?? 'secondary';
                                        @endphp

                                        <span class="badge bg-{{ $color }}">
                                            {{ $displayStatus }}
                                        </span>
                                    </td>
                                    <td>

                                        @if($candidate)

                                        <span class="badge bg-dark">
                                            {{ $candidate->candidate_code }}
                                        </span>

                                        @else

                                        <span class="text-muted">Not Created</span>

                                        @endif

                                    </td>
                                    <td>

                                        @if($candidate)

                                        @php

                                        $status = $candidate->candidate_status;

                                        $displayStatus = $status == 'Unsigned Agreement Created'
                                        ? 'Unsigned Agreement Created'
                                        : $status;

                                        $colors = [
                                        'Agreement Pending'=>'warning',
                                        'Unsigned Agreement Created'=>'info',
                                        'Signed Agreement Uploaded'=>'primary',
                                        'Active'=>'success',
                                        'Inactive'=>'danger'
                                        ];

                                        $color = $colors[$status] ?? 'secondary';

                                        @endphp

                                        <span class="badge bg-{{ $color }}">
                                            {{ $displayStatus }}
                                        </span>

                                        @else

                                        <span class="text-muted">—</span>

                                        @endif

                                    </td>
                                    <!-- Actions column -->
                                    <!-- Actions column -->
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- View Button -->
                                            <a href="{{ route('hr-admin.applications.view', $requisition->id) }}"
                                                class="btn btn-sm btn-info" title="View" target="_blank">
                                                <i class="ri-eye-line"></i>
                                            </a>

                                            <!-- HR Review Button (for pending) -->
                                            @if(in_array($requisition->status, ['Pending HR Verification', 'Correction Required']))
                                            <a href="{{ route('requisitions.edit', $requisition->id) }}"
                                                class="btn btn-sm btn-warning" title="HR Review">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            @endif

                                            <!-- Process Button (for Approved requisitions without candidate master) -->
                                            @if($requisition->status === 'Approved' && !$isProcessed)
                                            <button type="button" class="btn btn-sm btn-success process-btn"
                                                data-bs-toggle="modal" data-bs-target="#processModal"
                                                data-requisition-id="{{ $requisition->id }}"
                                                data-requisition-name="{{ $requisition->candidate_name }}"
                                                data-requisition-type="{{ $requisition->requisition_type }}"
                                                data-current-reporting="{{ $requisition->reporting_to }}"
                                                data-current-manager-id="{{ $requisition->reporting_manager_employee_id }}">
                                                <i class="ri-play-line"></i>
                                            </button>
                                            @elseif($requisition->status === 'Pending Approval')
                                            <span class="badge bg-info fs-9">Awaiting Approval</span>
                                            @endif

                                            <!-- After Processing: Show candidate status and workflow buttons -->
                                            @if($isProcessed && $candidate)
                                            @php


                                            // Check if candidate has unsigned agreement
                                            $hasUnsigned = $candidate->agreementDocuments
                                            ->where('document_type','agreement')
                                            ->where('sign_status','UNSIGNED')
                                            ->count() > 0;

                                            $hasSigned = $candidate->agreementDocuments
                                            ->where('document_type','agreement')
                                            ->where('sign_status','SIGNED')
                                            ->count() > 0;

                                            $agreementNumber = optional(
                                            $candidate->agreementDocuments
                                            ->where('document_type','agreement')
                                            ->where('sign_status','UNSIGNED')
                                            ->first()
                                            )->agreement_number;

                                            $hasEstamp = $candidate->agreementDocuments
                                            ->where('document_type','estamp')
                                            ->count() > 0;
                                            @endphp


                                            <!-- EDIT PARTY BUTTON - ADD THIS LINE -->
                                            <a href="{{ route('hr-admin.edit-party', $candidate->id) }}"
                                                class="btn btn-sm btn-primary" title="Edit Party Details">
                                                <i class="ri-pencil-line"></i>
                                            </a>

                                            @if(!$hasEstamp)

                                            <button type="button"
                                                class="btn btn-sm btn-warning upload-estamp-btn"
                                                data-candidate-id="{{ $candidate->id }}"
                                                data-candidate-code="{{ $candidate->candidate_code }}"
                                                data-candidate-name="{{ $candidate->candidate_name }}">
                                                <i class="ri-file-upload-line"></i>
                                            </button>

                                            @endif

                                            <!-- Upload Signed Agreement Button (for Unsigned Agreement Uploaded status) -->
                                            @if($hasUnsigned && !$hasSigned)
                                            <button type="button"
                                                class="btn btn-sm btn-info upload-signed-btn"
                                                data-candidate-id="{{ $candidate->id }}"
                                                data-candidate-code="{{ $candidate->candidate_code ?? $candidate->employee_id }}"
                                                data-candidate-name="{{ $candidate->candidate_name }}"
                                                data-agreement-number="{{ $agreementNumber }}">
                                                <i class="ri-mail-line"></i>
                                            </button>
                                            @endif

                                            @endif {{-- closes: @if($isProcessed && $candidate) --}}
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-2">No requisitions found.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($requisitions->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $requisitions->firstItem() }} to {{ $requisitions->lastItem() }} of {{ $requisitions->total() }} entries
                        </div>
                        <div>
                            {{ $requisitions->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Modal -->
@include('hr.modals.process-modal')
<!-- Upload Signed Agreement Modal -->
@include('hr.modals.upload-signed-modal')
<!-- upload estamp Modal -->
@include('hr.modals.upload-estamp-modal')

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast-container"></div>
</div>

@endsection

@push('scripts')
<script>
    window.routes = {
        uploadEstamp: "{{ route('hr-admin.master.upload-estamp', ['candidate' => 'CANDIDATE_ID']) }}"
    };
</script>
<script src="{{ asset('assets/js/hr-common.js') }}"></script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Get CSRF token from meta tag
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        // Initialize Select2 for process modal
        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Select Reporting Manager --',
            allowClear: true,
            dropdownParent: $('#processModal'),
            width: '100%'
        });

    });
</script>
<style>
    .select2-container {
        z-index: 1065 !important;
        /* Higher than Bootstrap modal */
    }

    .table {
        font-size: 0.85rem;
    }

    .table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        background-color: #f3f6f9;
        font-weight: 600;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.6em;
        font-weight: 500;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .requisition-table-wrapper {
        max-height: 70vh;
        overflow-y: auto;
    }

    /* Sticky Header */
    .requisition-table-wrapper thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background-color: #f3f6f9;
        /* Same as your current header */
    }
</style>
@endpush