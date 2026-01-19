<div class="row">
    <div class="col-md-6 mb-3">
        <label for="candidate_name" class="form-label">Candidate Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="candidate_name" name="candidate_name" 
               value="{{ $requisition->candidate_name }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="candidate_email" class="form-label">Candidate Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="candidate_email" name="candidate_email" 
               value="{{ $requisition->candidate_email }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="requisition_type" class="form-label">Requisition Type <span class="text-danger">*</span></label>
        <select class="form-select" id="requisition_type" name="requisition_type" required>
            <option value="">-- Select Type --</option>
            <option value="Contractual" {{ $requisition->requisition_type == 'Contractual' ? 'selected' : '' }}>Contractual</option>
            <option value="TFA" {{ $requisition->requisition_type == 'TFA' ? 'selected' : '' }}>TFA</option>
            <option value="CB" {{ $requisition->requisition_type == 'CB' ? 'selected' : '' }}>CB</option>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="submitted_by_name" class="form-label">Submitted By</label>
        <input type="text" class="form-control" id="submitted_by_name" 
               value="{{ $requisition->submitted_by_name }}" readonly>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="submitted_by_employee_id" class="form-label">Submitter Employee ID</label>
        <input type="text" class="form-control" id="submitted_by_employee_id" 
               value="{{ $requisition->submitted_by_employee_id }}" readonly>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="submission_date" class="form-label">Submission Date</label>
        <input type="text" class="form-control" id="submission_date" 
               value="{{ $requisition->submission_date->format('d-m-Y H:i') }}" readonly>
    </div>
</div>