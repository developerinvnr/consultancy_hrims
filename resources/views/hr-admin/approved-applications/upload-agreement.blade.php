<!-- resources/views/hr-admin/approved-applications/upload-agreement.blade.php -->
@extends('layouts.guest')

@section('page-title', 'Upload Agreement - ' . $employee->employee_code)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Upload Agreement</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('hr-admin.applications.approved') }}">Approved Applications</a></li>
                        <li class="breadcrumb-item active">Upload Agreement</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <span class="badge bg-info">{{ $employee->employee_code }}</span>
                        <span class="ms-2">{{ $employee->candidate_name }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Employee Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Employee Code:</strong> {{ $employee->employee_code }}</p>
                                    <p class="mb-1"><strong>Name:</strong> {{ $employee->candidate_name }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $employee->candidate_email }}</p>
                                    <p class="mb-1"><strong>Department:</strong> {{ $employee->department->department_name ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Reporting To:</strong> {{ $employee->reporting_to }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6>API Status</h6>
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

                    <!-- Upload Form -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Upload Unsigned Agreement</h6>
                        </div>
                        <div class="card-body">
                            @if($employee->agri_samvida_reference_id)
                                <div class="alert alert-info mb-3">
                                    <i class="ri-information-line me-2"></i>
                                    <strong>Next Steps:</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Download agreement from external portal</li>
                                        <li>Upload the unsigned agreement here</li>
                                        <li>Submitter will be notified to print and sign</li>
                                        <li>Submitter uploads signed copy for verification</li>
                                    </ol>
                                </div>
                            @endif

                            <form action="{{ route('hr-admin.applications.upload-agreement-store', $employee) }}" 
                                  method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="agreement_file" class="form-label">Unsigned Agreement PDF *</label>
                                    <input type="file" class="form-control" id="agreement_file" 
                                           name="agreement_file" accept=".pdf" required>
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

                    <!-- Existing Documents -->
                    @if($employee->agreementDocuments->count() > 0)
                        <div class="card mt-4">
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
                                                <th>Action</th>
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
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_section')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload validation
    const fileInput = document.getElementById('agreement_file');
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    
    form.addEventListener('submit', function(e) {
        if (!fileInput.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'File Required',
                text: 'Please select a PDF file to upload.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        if (!fileInput.value.toLowerCase().endsWith('.pdf')) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid File Type',
                text: 'Please upload a PDF file only.',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
});
</script>
@endsection