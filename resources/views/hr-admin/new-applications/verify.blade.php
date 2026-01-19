<!-- resources/views/hr-admin/new-applications/verify.blade.php -->
@extends('layouts.guest')

@section('page-title', 'Verify Requisition')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Verify Requisition: {{ $requisition->requisition_id }}</h5>
    </div>
    
    <div class="card-body">
        <form id="verification-form" method="POST" 
              action="{{ route('hr-admin.applications.send-approval', $requisition) }}">
            @csrf
            
            <!-- Verification Checklist -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">Verification Checklist</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       id="check_all_documents" name="verification_checks[all_documents_uploaded]"
                                       value="1" required>
                                <label class="form-check-label" for="check_all_documents">
                                    <strong>✓ All required documents are uploaded</strong>
                                    <small class="d-block text-muted">
                                        Resume, PAN Card, Aadhaar Card, Driving License, Bank Document
                                    </small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       id="check_extracted_data" name="verification_checks[extracted_data_matches]"
                                       value="1" required>
                                <label class="form-check-label" for="check_extracted_data">
                                    <strong>✓ Extracted data matches the documents</strong>
                                    <small class="d-block text-muted">
                                        PAN number, Aadhaar number, Bank details match the uploaded documents
                                    </small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       id="check_form_fields" name="verification_checks[form_fields_accurate]"
                                       value="1" required>
                                <label class="form-check-label" for="check_form_fields">
                                    <strong>✓ All form fields are accurate and complete</strong>
                                    <small class="d-block text-muted">
                                        Personal, work, and employment details are correctly filled
                                    </small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       id="check_approver_hierarchy" name="verification_checks[approver_hierarchy_identified]"
                                       value="1" required>
                                <label class="form-check-label" for="check_approver_hierarchy">
                                    <strong>✓ Appropriate approver hierarchy has been identified</strong>
                                </label>
                            </div>
                            
                            <!-- Verification Remarks -->
                            <div class="mb-3">
                                <label for="verification_remarks" class="form-label">Verification Remarks</label>
                                <textarea class="form-control" id="verification_remarks" 
                                          name="verification_remarks" rows="3"
                                          placeholder="Add any verification notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approver Selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">Select Approver</h6>
                        </div>
                        <div class="card-body">
                            @if(count($approvers) > 0)
                                <div class="mb-3">
                                    <label for="approver_id" class="form-label">Select Approver <span class="text-danger">*</span></label>
                                    <select class="form-select" id="approver_id" name="approver_id" required>
                                        <option value="">-- Select Approver --</option>
                                        @foreach($approvers as $approver)
                                            <option value="{{ $approver['id'] }}">
                                                {{ $approver['name'] }} ({{ $approver['employee_id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select the appropriate approver from the hierarchy</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="ri-information-line me-1"></i>
                                    <strong>Approval Hierarchy:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($approvers as $approver)
                                            <li>{{ $approver['role'] }}: {{ $approver['name'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="ri-alert-line me-1"></i>
                                    No approvers found in the hierarchy. Please add approver roles to the system.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('hr-admin.applications.new') }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i> Back
                        </a>
                        
                        <div class="btn-group">
                            <!-- Request Correction Button -->
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#correctionModal">
                                <i class="ri-arrow-go-back-line me-1"></i> Request Correction
                            </button>
                            
                            <!-- Send for Approval Button -->
                            @if(count($approvers) > 0)
                            <button type="submit" class="btn btn-success" id="send-approval-btn">
                                <i class="ri-send-plane-line me-1"></i> Send for Approval
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Correction Request Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('hr-admin.applications.request-correction', $requisition) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Request Correction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="correction_remarks" class="form-label">Correction Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="correction_remarks" name="correction_remarks" 
                                  rows="4" placeholder="Specify what needs to be corrected..." required
                                  minlength="10"></textarea>
                        <small class="text-muted">Minimum 10 characters required</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Send Correction Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Form validation
        $('#verification-form').on('submit', function(e) {
            // Check if all checkboxes are checked
            const checkboxes = $('input[name^="verification_checks"]');
            const allChecked = checkboxes.length === checkboxes.filter(':checked').length;
            
            if (!allChecked) {
                e.preventDefault();
                alert('Please complete all verification checks before sending for approval.');
                return false;
            }
            
            // Check if approver is selected
            if (!$('#approver_id').val()) {
                e.preventDefault();
                alert('Please select an approver.');
                return false;
            }
            
            // Show confirmation
            const approverName = $('#approver_id option:selected').text();
            return confirm(`Are you sure you want to send this requisition to ${approverName} for approval?`);
        });
        
        // Correction modal validation
        $('#correctionModal form').on('submit', function(e) {
            const remarks = $('#correction_remarks').val();
            if (remarks.length < 10) {
                e.preventDefault();
                alert('Please provide detailed correction remarks (minimum 10 characters).');
                return false;
            }
            return confirm('Are you sure you want to send a correction request to the submitter?');
        });
    });
</script>
@endsection
