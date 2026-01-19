@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Agreement Management - {{ $candidate->candidate_name }}</h3>
                    <p class="mb-0">Candidate Code: {{ $candidate->candidate_code }} | Type: {{ $candidate->requisition_type }}</p>
                </div>
                <div class="card-body">
                    <!-- Status Overview -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-{{ $candidate->candidate_status == 'Agreement Pending' ? 'warning' : 'success' }}">
                                <div class="card-body">
                                    <h5>Current Status: <span class="badge bg-{{ $candidate->candidate_status == 'Agreement Pending' ? 'warning' : 'success' }}">{{ $candidate->candidate_status }}</span></h5>
                                    <p class="mb-1"><strong>Agreement Number:</strong> {{ $candidate->agreement_number ?? 'Not Assigned' }}</p>
                                    <p class="mb-1"><strong>Agreement Uploaded:</strong> {{ $candidate->agreement_upload_date ? $candidate->agreement_upload_date->format('d-M-Y') : 'No' }}</p>
                                    <p class="mb-1"><strong>Agreement Signed:</strong> {{ $candidate->agreement_signed_date ? $candidate->agreement_signed_date->format('d-M-Y') : 'No' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Submitter Information</h5>
                                    <p class="mb-1"><strong>Name:</strong> {{ $candidate->requisition->submittedBy->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $candidate->requisition->submittedBy->email ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Employee ID:</strong> {{ $candidate->requisition->submitted_by_employee_id ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                @if($candidate->candidate_status == 'Agreement Pending')
                                <a href="{{ route('hr-admin.agreement.upload-unsigned', $candidate) }}" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload Unsigned Agreement
                                </a>
                                @endif
                                
                                @if($candidate->candidate_status == 'Unsigned Agreement Uploaded' || $candidate->candidate_status == 'Agreement Verification Pending')
                                <a href="{{ route('hr-admin.agreement.upload-signed', $candidate) }}" class="btn btn-success">
                                    <i class="fas fa-file-signature"></i> Upload Signed Agreement
                                </a>
                                
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#updateAgreementModal">
                                    <i class="fas fa-edit"></i> Update Agreement
                                </button>
                                @endif
                                
                                <a href="{{ route('hr-admin.agreement.list') }}" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">Unsigned Agreement</h4>
                                </div>
                                <div class="card-body">
                                    @if($unsignedAgreement)
                                        <p><strong>Agreement Number:</strong> {{ $unsignedAgreement->agreement_number }}</p>
                                        <p><strong>Uploaded:</strong> {{ $unsignedAgreement->upload_date->format('d-M-Y H:i') }}</p>
                                        <p><strong>By:</strong> {{ $unsignedAgreement->uploaded_by_name }} ({{ $unsignedAgreement->uploaded_by_role }})</p>
                                        
                                        <div class="btn-group mt-3">
                                            <a href="{{ route('hr-admin.agreement.download', $unsignedAgreement) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <a href="{{ route('hr-admin.agreement.view', $unsignedAgreement) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No unsigned agreement uploaded yet.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">Signed Agreement</h4>
                                </div>
                                <div class="card-body">
                                    @if($signedAgreement)
                                        <p><strong>Agreement Number:</strong> {{ $signedAgreement->agreement_number }}</p>
                                        <p><strong>Uploaded:</strong> {{ $signedAgreement->upload_date->format('d-M-Y H:i') }}</p>
                                        <p><strong>By:</strong> {{ $signedAgreement->uploaded_by_name }} ({{ $signedAgreement->uploaded_by_role }})</p>
                                        @if($signedAgreement->verification_remarks)
                                            <p><strong>Remarks:</strong> {{ $signedAgreement->verification_remarks }}</p>
                                        @endif
                                        
                                        <div class="btn-group mt-3">
                                            <a href="{{ route('hr-admin.agreement.download', $signedAgreement) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <a href="{{ route('hr-admin.agreement.view', $signedAgreement) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No signed agreement uploaded yet.
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

<!-- Update Agreement Modal -->
<div class="modal fade" id="updateAgreementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('hr-admin.agreement.update', $candidate) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Update Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Agreement Number *</label>
                        <input type="text" name="agreement_number" class="form-control" value="{{ $candidate->agreement_number }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Agreement File (Optional - Leave empty to keep current)</label>
                        <input type="file" name="agreement_file" class="form-control" accept=".pdf">
                        <small class="text-muted">Max 10MB, PDF only</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Update *</label>
                        <textarea name="update_reason" class="form-control" rows="3" required placeholder="Explain why you are updating the agreement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection