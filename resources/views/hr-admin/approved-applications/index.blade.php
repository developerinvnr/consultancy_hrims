<!-- resources/views/hr-admin/approved-applications/index.blade.php -->
@extends('layouts.guest')

@section('page-title', 'Approved Applications')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Approved Applications</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('hr-admin.dashboard') }}">HR Admin</a></li>
                        <li class="breadcrumb-item active">Approved Applications</li>
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
                    <a class="nav-link" href="{{ route('hr-admin.applications.new') }}">
                        <i class="ri-file-list-line me-1"></i> New Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('hr-admin.applications.approved') }}">
                        <i class="ri-check-double-line me-1"></i> Approved Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('hr-admin.master.index') }}">
                        <i class="ri-database-2-line me-1"></i> Employee Master
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('hr-admin.applications.approved') }}">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search by requisition ID, candidate name, or email..." 
                                           value="{{ request('search') }}">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="ri-search-line"></i> Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-light w-100">
                                    <i class="ri-refresh-line me-1"></i> Reset Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Applications Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Approved Applications</h5>
                        <span class="badge bg-success">{{ $requisitions->total() }} approved</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($requisitions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Requisition ID</th>
                                    <th>Candidate</th>
                                    <th>Type</th>
                                    <th>Department</th>
                                    <th>Approved By</th>
                                    <th>Approval Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisitions as $requisition)
                                @php
                                    // Check if already processed
                                    $isProcessed = \App\Models\EmployeeMaster::where('requisition_id', $requisition->id)->exists();
                                    $employee = \App\Models\EmployeeMaster::where('requisition_id', $requisition->id)->first();
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $requisition->requisition_id }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $requisition->candidate_name }}</div>
                                        <small class="text-muted">{{ $requisition->candidate_email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $requisition->requisition_type == 'Contractual' ? 'primary' : ($requisition->requisition_type == 'TFA' ? 'success' : 'info') }}">
                                            {{ $requisition->requisition_type }}
                                        </span>
                                    </td>
                                    <td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                                    <td>{{ $requisition->approver_id }}</td>
                                    <td>
                                        {{ $requisition->approval_date->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">{{ $requisition->approval_date->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($isProcessed)
                                            @php
                                                $statusColors = [
                                                    'Agreement Pending' => 'warning',
                                                    'Unsigned Agreement Uploaded' => 'info',
                                                    'Active' => 'success'
                                                ];
                                                $empStatus = $employee->employment_status ?? 'Agreement Pending';
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$empStatus] ?? 'secondary' }}">
                                                {{ $empStatus }}
                                            </span>
                                            @if($employee && $employee->employee_code)
                                                <br>
                                                <small class="text-muted">{{ $employee->employee_code }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Ready to Process</span>
                                        @endif
                                    </td>
                                   <td>
										<div class="btn-group btn-group-sm">
											<a href="{{ route('hr-admin.applications.view', $requisition) }}" 
											class="btn btn-outline-primary" title="View Details">
												<i class="ri-eye-line"></i>
											</a>
											
											@if(!$isProcessed)
												<!-- Process Button - Opens Modal -->
												<button type="button" class="btn btn-success process-btn" 
														data-bs-toggle="modal" data-bs-target="#processModal"
														data-requisition-id="{{ $requisition->id }}"
														data-requisition-name="{{ $requisition->candidate_name }}"
														data-current-reporting="{{ $requisition->reporting_to }}"
														data-current-manager-id="{{ $requisition->reporting_manager_employee_id }}"
														title="Process Application">
													<i class="ri-play-line"></i> Process
												</button>
											@else
												<!-- Agreement Upload Button -->
												@if($employee && $employee->employment_status === 'Agreement Pending')
													<a href="{{ route('hr-admin.applications.upload-agreement', $employee) }}" 
													class="btn btn-warning" title="Upload Agreement">
														<i class="ri-upload-line"></i> Upload Agreement
													</a>
												@elseif($employee && $employee->employment_status === 'Unsigned Agreement Uploaded')
													<a href="{{ route('hr-admin.applications.verify-signed', $employee) }}" 
													class="btn btn-info" title="Verify Signed Agreement">
														<i class="ri-check-line"></i> Verify Signed
													</a>
												@elseif($employee && $employee->employment_status === 'Active')
													<span class="badge bg-success">Active</span>
												@endif
											@endif
										</div>
									</td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $requisitions->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="ri-inbox-line display-4 text-muted"></i>
                        <h5 class="mt-3">No approved applications</h5>
                        <p class="text-muted">There are no approved applications to process at the moment.</p>
                        <a href="{{ route('hr-admin.applications.new') }}" class="btn btn-primary mt-2">
                            <i class="ri-arrow-left-line me-1"></i> Go to New Applications
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

		<!-- Process Modal -->
		<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Process Approved Application</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<form id="processForm" action="" method="POST">
						@csrf
						<div class="modal-body">				
							<input type="hidden" name="requisition_id" id="modalRequisitionId">
							
							<div class="row">
								<div class="col-md-4">
									<div class="mb-3">
										<label class="form-label-sm">Candidate</label>
										<input type="text" class="form-control form-control-sm" id="modalCandidateName" readonly>
									</div>
								</div>
									<div class="col-md-4">
									<div class="mb-3">
										<label class="form-label-sm">Current Reporting Manager</label>
										<input type="text" class="form-control form-control-sm" id="currentReporting" readonly>
									</div>
								</div>
								<div class="col-md-4">
									<div class="mb-3">
										<label class="form-label-sm">Current Reporting ID</label>
										<input type="text" class="form-control form-control-sm" id="currentManagerId" readonly>
									</div>
								</div>
							</div>							
							<h6>Change Reporting Manager</h6>							
							<div class="mb-3">
								<label for="reporting_manager_employee_id" class="form-label-sm">Reporting Manager *</label>
								<select class="form-select form-select-sm select2-modal" id="reporting_manager_employee_id" 
										name="reporting_manager_employee_id" required>
									<option value="">-- Select Reporting Manager --</option>
									<!-- Options will be populated via AJAX -->
								</select>
								<small class="text-muted">Select the reporting manager from the department hierarchy</small>
							</div>
							
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label for="reporting_to" class="form-label">Reporting To Name *</label>
										<input type="text" class="form-control form-control-sm" id="reporting_to" 
											name="reporting_to" required readonly>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label for="reporting_manager_id" class="form-label-sm">Reporting Manager ID *</label>
										<input type="text" class="form-control form-control-sm" id="reporting_manager_id" 
											name="reporting_manager_id" required readonly>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-success">
								<i class="ri-save-line me-1"></i> Generate Employee Code & Process
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

</div>
@endsection
<style>
	.select2-container {
    z-index: 1065 !important; /* Higher than Bootstrap modal */
}
	</style>
@section('script_section')
<script>
$(document).ready(function () {

    // ✅ Initialize Select2 ONLY ONCE
    $('#reporting_manager_employee_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Reporting Manager --',
        allowClear: true,
        dropdownParent: $('#processModal'),
        width: '100%'
    });

    // ✅ When modal opens, load managers via AJAX
    $('#processModal').on('shown.bs.modal', function (event) {

        let button = $(event.relatedTarget);
        let requisitionId = button.data('requisition-id');
        let candidateName = button.data('requisition-name');
        let currentReporting = button.data('current-reporting');
        let currentManagerId = button.data('current-manager-id');

        let modal = $(this);

        modal.find('#modalRequisitionId').val(requisitionId);
        modal.find('#modalCandidateName').val(candidateName);
        modal.find('#currentReporting').val(currentReporting);
        modal.find('#currentManagerId').val(currentManagerId);

        let select = $('#reporting_manager_employee_id');

        // Reset dropdown
        select.html('<option value="">Loading...</option>');
        select.trigger('change');

        // AJAX call
        $.ajax({
			url: '{{ url("hr-admin/applications/get-reporting-managers") }}/' + requisitionId,
            type: 'GET',
            success: function (response) {

                if (!response.success) {
                    select.html('<option value="">No data found</option>');
                    select.trigger('change');
                    return;
                }

                let data = response.data;

                // Clear & refill
                select.empty();
                select.append('<option value="">-- Select Reporting Manager --</option>');

                // Current manager
                if (data.current) {
                    select.append(`
                        <option value="${data.current.reporting_manager_employee_id}" selected>
                            ${data.current.reporting_to} (${data.current.reporting_manager_employee_id}) - Current
                        </option>
                    `);

                    $('#reporting_to').val(data.current.reporting_to);
                    $('#reporting_manager_id').val(data.current.reporting_manager_employee_id);
                }

                // Managers
                if (data.managers?.length) {
                    select.append('<optgroup label="Department Managers">');
                    data.managers.forEach(m => {
                        if (!data.current || m.employee_id != data.current.reporting_manager_employee_id) {
                            select.append(`
                                <option value="${m.employee_id}">
                                    ${m.emp_name} (${m.employee_id}) - ${m.emp_designation}
                                </option>
                            `);
                        }
                    });
                }

                // Employees
                if (data.employees?.length) {
                    select.append('<optgroup label="Department Employees">');
                    data.employees.forEach(e => {
                        if (!data.current || e.employee_id != data.current.reporting_manager_employee_id) {
                            select.append(`
                                <option value="${e.employee_id}">
                                    ${e.emp_name} (${e.employee_id}) - ${e.emp_designation}
                                </option>
                            `);
                        }
                    });
                }

                // Refresh select2
                select.trigger('change.select2');
            },

            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load reporting managers'
                });
            }
        });
    });

    // ✅ When dropdown changes, update fields
    $('#reporting_manager_employee_id').on('change', function () {
        let selectedText = $(this).find('option:selected').text();
        let selectedValue = $(this).val();

        if (selectedValue) {
            let name = selectedText.split('(')[0].trim();
            $('#reporting_to').val(name);
            $('#reporting_manager_id').val(selectedValue);
        }
    });

    // ✅ Submit process form
    $('#processForm').on('submit', function(e) {
        e.preventDefault();

        let formData = $(this).serialize();

        Swal.fire({
            title: 'Process Employee?',
            html: '<p>This will generate employee code.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, process it',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: '{{ route("hr-admin.applications.process-modal") }}',
                    type: 'POST',
                    data: formData
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire('Success', result.value.message, 'success')
                    .then(() => location.reload());
            } else if (result.isConfirmed) {
                Swal.fire('Error', result.value?.message || 'Something went wrong', 'error');
            }
        });
    });

});
</script>
@endsection
