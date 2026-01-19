<!-- resources/views/hr-admin/approved-applications/process.blade.php -->
@extends('layouts.guest')

@section('page-title', 'Process Approved Application - ' . $requisition->requisition_id)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Process Approved Application</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('hr-admin.applications.approved') }}">Approved Applications</a></li>
                        <li class="breadcrumb-item active">Process</li>
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
                                <span class="badge bg-success">{{ $requisition->requisition_id }}</span>
                                <span class="ms-2">{{ $requisition->candidate_name }}</span>
                                <small class="text-muted d-block mt-1">
                                    {{ $requisition->requisition_type }} | 
                                    Approved by: {{ $requisition->approver_id }}
                                </small>
                            </h5>
                        </div>
                        <div>
                            @if($employee)
                                <span class="badge bg-info">Employee Code: {{ $employee->employee_code }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Status Progress Bar -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="progress" style="height: 10px;">
                                @php
                                    $steps = [
                                        1 => ['title' => 'Approved', 'status' => 'Approved'],
                                        2 => ['title' => 'Processed', 'status' => 'Processed'],
                                        3 => ['title' => 'Agreement Pending', 'status' => 'Agreement Pending'],
                                        4 => ['title' => 'Active', 'status' => 'Active']
                                    ];
                                    
                                    $currentStep = 1;
                                    if ($requisition->status === 'Processed') $currentStep = 2;
                                    if (isset($employee) && $employee->employment_status === 'Agreement Pending') $currentStep = 3;
                                    if (isset($employee) && $employee->employment_status === 'Active') $currentStep = 4;
                                    
                                    $progress = (($currentStep - 1) / 3) * 100;
                                @endphp
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                @foreach($steps as $key => $step)
                                    <div class="text-center">
                                        <div class="d-inline-block rounded-circle p-2 
                                            @if($key == $currentStep) bg-success text-white
                                            @elseif($key < $currentStep) bg-success text-white
                                            @else bg-light @endif"
                                            style="width: 40px; height: 40px; line-height: 24px;">
                                            {{ $key }}
                                        </div>
                                        <div class="mt-1 small">{{ $step['title'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Requisition Details -->
                    @include('requisitions.show-content', ['requisition' => $requisition])

                    <!-- Processing Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">HR Processing Actions</h6>
                                </div>
                                <div class="card-body">
                                    @if(!$employee)
                                        <!-- Step 1: Create Employee Record -->
                                        <div id="step1" class="processing-step">
                                            <h6>Step 1: Generate Employee Code & Create Record</h6>
                                            <p class="text-muted">Create employee record and generate employee code. This will also trigger API calls to external systems.</p>
                                            
                                            <form id="processForm" action="{{ route('hr-admin.applications.generate-code', $requisition) }}" method="POST">
                                                @csrf
                                                
                                                <!-- Current Reporting Manager (Read-only) -->
                                                <div class="row mb-4">
                                                    <div class="col-12">
                                                        <div class="card border">
                                                            <div class="card-header bg-light py-2">
                                                                <h6 class="mb-0">Current Reporting Details</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="mb-2">
                                                                            <label class="form-label text-muted">Reporting To:</label>
                                                                            <p class="fw-bold">{{ $requisition->reporting_to }}</p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-2">
                                                                            <label class="form-label text-muted">Reporting Manager ID:</label>
                                                                            <p class="fw-bold">{{ $requisition->reporting_manager_employee_id }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- New Reporting Manager Selection -->
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="mb-3">
                                                            <label for="reporting_manager_employee_id" class="form-label">Select Reporting Manager *</label>
                                                            <select class="form-select select2-reporting-manager" id="reporting_manager_employee_id" 
                                                                    name="reporting_manager_employee_id" required>
                                                                <option value="">-- Select Reporting Manager --</option>
                                                                
                                                                <!-- Current Reporting Manager -->
                                                                <option value="{{ $requisition->reporting_manager_employee_id }}" selected>
                                                                    {{ $requisition->reporting_to }} ({{ $requisition->reporting_manager_employee_id }}) - Current
                                                                </option>
                                                                
                                                                <!-- Department Managers -->
                                                                @if(isset($departmentManagers) && $departmentManagers->count() > 0)
                                                                    <optgroup label="Department Managers">
                                                                        @foreach($departmentManagers as $manager)
                                                                            @if($manager->employee_id != $requisition->reporting_manager_employee_id)
                                                                                <option value="{{ $manager->employee_id }}">
                                                                                    {{ $manager->emp_name }} ({{ $manager->employee_id }}) - {{ $manager->emp_designation }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endif
                                                                
                                                                <!-- Department Employees -->
                                                                @if(isset($departmentEmployees) && $departmentEmployees->count() > 0)
                                                                    <optgroup label="Department Employees">
                                                                        @foreach($departmentEmployees as $emp)
                                                                            @if($emp->employee_id != $requisition->reporting_manager_employee_id)
                                                                                <option value="{{ $emp->employee_id }}">
                                                                                    {{ $emp->emp_name }} ({{ $emp->employee_id }}) - {{ $emp->emp_designation }}
                                                                                </option>
                                                                            @endif
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endif
                                                            </select>
                                                            <small class="text-muted">Select the reporting manager from the department hierarchy</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="reporting_to" class="form-label">Reporting To Name *</label>
                                                            <input type="text" class="form-control" id="reporting_to" 
                                                                   name="reporting_to" value="{{ $requisition->reporting_to }}" required readonly>
                                                            <small class="text-muted">Will auto-fill based on selection</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="reporting_manager_user_id" class="form-label">Reporting Manager User ID *</label>
                                                            <input type="text" class="form-control" id="reporting_manager_user_id" 
                                                                   name="reporting_manager_user_id" value="{{ $requisition->reporting_manager_employee_id }}" required readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="reporting_manager_address" class="form-label">Reporting Manager Address *</label>
                                                    <textarea class="form-control" id="reporting_manager_address" name="reporting_manager_address" 
                                                              rows="2" required>{{ $requisition->reporting_manager_address }}</textarea>
                                                </div>
                                                
                                                <!-- Employee Details Preview -->
                                                <div class="card mb-4 border-info">
                                                    <div class="card-header bg-info text-white py-2">
                                                        <h6 class="mb-0">Employee Details Preview</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Employee Name:</label>
                                                                    <p class="fw-bold">{{ $requisition->candidate_name }}</p>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Employee Email:</label>
                                                                    <p class="fw-bold">{{ $requisition->candidate_email }}</p>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Department:</label>
                                                                    <p class="fw-bold">{{ $requisition->department->department_name ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Function:</label>
                                                                    <p class="fw-bold">{{ $requisition->function->function_name ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Work Location:</label>
                                                                    <p class="fw-bold">{{ $requisition->work_location_hq }}</p>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label text-muted">Remuneration:</label>
                                                                    <p class="fw-bold">₹{{ number_format($requisition->remuneration_per_month, 2) }}/month</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <i class="ri-information-line me-2"></i>
                                                    This action will:
                                                    <ul class="mb-0 mt-2">
                                                        <li>Generate a unique employee code</li>
                                                        <li>Create employee record in master database</li>
                                                        <li>Send data to Agri Samvida platform</li>
                                                        <li>Mark requisition as "Processed"</li>
                                                    </ul>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="ri-save-line me-1"></i> Generate Employee Code & Process
                                                    </button>
                                                    <a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-light">
                                                        <i class="ri-arrow-left-line me-1"></i> Cancel
                                                    </a>
                                                </div>
                                            </form>
                                        </div>
                                    @elseif($employee->employment_status === 'Agreement Pending')
                                        <!-- Step 2: Upload Agreement -->
                                        <div id="step2" class="processing-step">
                                            <h6>Step 2: Upload Agreement Documents</h6>
                                            <p class="text-muted">Employee Code: <strong>{{ $employee->employee_code }}</strong></p>
                                            
                                            <!-- Employee Details -->
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="card border">
                                                        <div class="card-header bg-light py-2">
                                                            <h6 class="mb-0">Employee Details</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p class="mb-1"><strong>Name:</strong> {{ $employee->candidate_name }}</p>
                                                            <p class="mb-1"><strong>Code:</strong> {{ $employee->employee_code }}</p>
                                                            <p class="mb-1"><strong>Department:</strong> {{ $employee->department->department_name ?? 'N/A' }}</p>
                                                            <p class="mb-1"><strong>Reporting To:</strong> {{ $employee->reporting_to }}</p>
                                                            <p class="mb-0"><strong>Reporting ID:</strong> {{ $employee->reporting_manager_employee_id }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border">
                                                        <div class="card-header bg-light py-2">
                                                            <h6 class="mb-0">API Status</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            @if($employee->agri_samvida_reference_id)
                                                                <div class="alert alert-success py-2 mb-0">
                                                                    <i class="ri-check-line me-1"></i>
                                                                    <strong>Success:</strong> {{ $employee->agri_samvida_reference_id }}
                                                                </div>
                                                            @else
                                                                <div class="alert alert-warning py-2 mb-0">
                                                                    <i class="ri-alert-line me-1"></i>
                                                                    <strong>Pending:</strong> API integration pending
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($employee->agri_samvida_reference_id)
                                                <div class="mb-4">
                                                    <div class="alert alert-success">
                                                        <i class="ri-check-line me-2"></i>
                                                        <strong>API Integration Successful!</strong><br>
                                                        Reference ID: {{ $employee->agri_samvida_reference_id }}
                                                    </div>
                                                    
                                                    <div class="card">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0">Next Steps</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <ol class="mb-0">
                                                                <li>Go to external portal and download the generated agreement</li>
                                                                <li>Upload the unsigned agreement here</li>
                                                                <li>The submitter will be notified to print and sign the agreement</li>
                                                                <li>Submitter uploads signed agreement</li>
                                                                <li>HR verifies and activates employee</li>
                                                            </ol>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <!-- Upload Unsigned Agreement -->
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Upload Unsigned Agreement</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form action="{{ route('hr-admin.master.upload-unsigned', $employee) }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="mb-3">
                                                            <label for="agreement_file" class="form-label">Unsigned Agreement PDF *</label>
                                                            <input type="file" class="form-control" id="agreement_file" name="agreement_file" accept=".pdf" required>
                                                            <small class="text-muted">Upload the agreement downloaded from the external portal</small>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="ri-upload-line me-1"></i> Upload Unsigned Agreement
                                                            </button>
                                                            <a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-light">
                                                                <i class="ri-arrow-left-line me-1"></i> Back to List
                                                            </a>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <!-- Check for existing agreements -->
                                            @if($employee->agreementDocuments->count() > 0)
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Uploaded Documents</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Type</th>
                                                                        <th>File Name</th>
                                                                        <th>Uploaded On</th>
                                                                        <th>Status</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($employee->agreementDocuments as $document)
                                                                    <tr>
                                                                        <td>
                                                                            @if($document->document_type === 'unsigned')
                                                                                <span class="badge bg-warning">Unsigned</span>
                                                                            @else
                                                                                <span class="badge bg-success">Signed</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ $document->file_name }}</td>
                                                                        <td>{{ $document->upload_date->format('d-M-Y H:i') }}</td>
                                                                        <td>
                                                                            @if($document->verification_status === 'verified')
                                                                                <span class="badge bg-success">Verified</span>
                                                                            @else
                                                                                <span class="badge bg-warning">Pending</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <a href="{{ route('hr-admin.master.download-agreement', $document) }}" 
                                                                               class="btn btn-sm btn-outline-primary">
                                                                                <i class="ri-download-line"></i>
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($employee->employment_status === 'Unsigned Agreement Uploaded')
                                        <!-- Step 3: Upload Signed Agreement -->
                                        <div id="step3" class="processing-step">
                                            <h6>Step 3: Upload Signed Agreement</h6>
                                            <p class="text-muted">
                                                Unsigned agreement has been uploaded. 
                                                The submitter has been notified to print, sign, and upload the signed agreement.
                                            </p>
                                            
                                            <!-- Check if submitter has uploaded signed agreement -->
                                            @php
                                                $signedDocument = $employee->agreementDocuments
                                                    ->where('document_type', 'signed')
                                                    ->where('verification_status', 'pending')
                                                    ->first();
                                            @endphp
                                            
                                            @if($signedDocument)
                                                <div class="alert alert-warning">
                                                    <i class="ri-time-line me-2"></i>
                                                    <strong>Signed Agreement Awaiting Verification</strong><br>
                                                    File: {{ $signedDocument->file_name }}<br>
                                                    Uploaded by submitter on: {{ $signedDocument->upload_date->format('d-M-Y H:i') }}
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('hr-admin.master.download-agreement', $signedDocument) }}" 
                                                       class="btn btn-primary">
                                                        <i class="ri-eye-line me-1"></i> View Signed Agreement
                                                    </a>
                                                    
                                                    <form action="{{ route('hr-admin.master.verify-signed', $employee) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="document_id" value="{{ $signedDocument->id }}">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="ri-check-line me-1"></i> Verify & Activate Employee
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <!-- Upload Signed Agreement Form (for HR to upload if needed) -->
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Upload Signed Agreement</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="text-muted mb-3">
                                                            If the submitter has provided the signed agreement directly, you can upload it here.
                                                        </p>
                                                        
                                                        <form action="{{ route('hr-admin.master.upload-signed', $employee) }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="mb-3">
                                                                <label for="signed_agreement" class="form-label">Signed Agreement PDF *</label>
                                                                <input type="file" class="form-control" id="signed_agreement" name="signed_agreement" accept=".pdf" required>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="ri-upload-line me-1"></i> Upload Signed Agreement & Activate
                                                                </button>
                                                                <a href="{{ route('hr-admin.applications.approved') }}" class="btn btn-light">
                                                                    <i class="ri-arrow-left-line me-1"></i> Back to List
                                                                </a>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($employee->employment_status === 'Active')
                                        <!-- Step 4: Completed -->
                                        <div id="step4" class="processing-step">
                                            <div class="alert alert-success">
                                                <i class="ri-check-double-line me-2"></i>
                                                <h5 class="alert-heading">Employee Activation Complete!</h5>
                                                <p class="mb-0">
                                                    Employee <strong>{{ $employee->candidate_name }}</strong> has been successfully activated.<br>
                                                    Employee Code: <strong>{{ $employee->employee_code }}</strong><br>
                                                    Activation Date: <strong>{{ $employee->actual_joining_date->format('d-M-Y') }}</strong>
                                                </p>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('hr-admin.master.view-employee', $employee) }}" 
                                                   class="btn btn-primary">
                                                    <i class="ri-profile-line me-1"></i> View Employee Details
                                                </a>
                                                <a href="{{ route('hr-admin.applications.approved') }}" 
                                                   class="btn btn-light">
                                                    <i class="ri-arrow-left-line me-1"></i> Back to Approved List
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script_section')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for reporting manager dropdown
    $('.select2-reporting-manager').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Reporting Manager --',
        allowClear: true,
        width: '100%'
    });

    // Employee data mapping
    const employeeData = {
        @if(isset($departmentManagers))
            @foreach($departmentManagers as $manager)
                "{{ $manager->employee_id }}": {
                    name: "{{ $manager->emp_name }}",
                    designation: "{{ $manager->emp_designation }}"
                },
            @endforeach
        @endif
        @if(isset($departmentEmployees))
            @foreach($departmentEmployees as $emp)
                "{{ $emp->employee_id }}": {
                    name: "{{ $emp->emp_name }}",
                    designation: "{{ $emp->emp_designation }}"
                },
            @endforeach
        @endif
        // Current reporting manager
        "{{ $requisition->reporting_manager_employee_id }}": {
            name: "{{ $requisition->reporting_to }}",
            designation: "Reporting Manager"
        }
    };

    // Update reporting name and user ID when selection changes
    $('#reporting_manager_employee_id').on('change', function() {
        const selectedId = $(this).val();
        const reportingAddress = "{{ $requisition->reporting_manager_address }}";
        
        if (selectedId && employeeData[selectedId]) {
            $('#reporting_to').val(employeeData[selectedId].name);
            $('#reporting_manager_user_id').val(selectedId);
            
            // You could also fetch address from database if available
            // For now, we keep the original address
        } else {
            $('#reporting_to').val('');
            $('#reporting_manager_user_id').val('');
        }
    });

    // Process form confirmation
    $('#processForm').submit(function(e) {
        e.preventDefault();
        
        const reportingManager = $('#reporting_manager_employee_id').val();
        const reportingName = $('#reporting_to').val();
        
        if (!reportingManager) {
            Swal.fire({
                icon: 'warning',
                title: 'Reporting Manager Required',
                text: 'Please select a reporting manager from the dropdown.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        Swal.fire({
            title: 'Process Employee?',
            html: `
                <div class="text-start">
                    <p><strong>Employee:</strong> {{ $requisition->candidate_name }}</p>
                    <p><strong>Reporting To:</strong> ${reportingName}</p>
                    <p><strong>Reporting ID:</strong> ${reportingManager}</p>
                    <p class="mt-3">This will generate employee code and send data to external systems.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, process it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
    
    // Agreement upload validation
    $('form[enctype="multipart/form-data"]').submit(function(e) {
        const fileInput = $(this).find('input[type="file"]');
        if (fileInput.length && !fileInput.val()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'File Required',
                text: 'Please select a file to upload.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        const fileName = fileInput.val();
        if (fileName && !fileName.toLowerCase().endsWith('.pdf')) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid File Type',
                text: 'Please upload a PDF file.',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
});
</script>
@endsection