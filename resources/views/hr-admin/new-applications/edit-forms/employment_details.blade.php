<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Reporting To</label>
        <input type="text" class="form-control" value="{{ $requisition->reporting_to }}" readonly>
    </div>
    
    <div class="col-md-6 mb-3">
        <label class="form-label">Reporting Manager ID</label>
        <input type="text" class="form-control" value="{{ $requisition->reporting_manager_employee_id }}" readonly>
    </div>
    
    <div class="col-md-12 mb-3">
        <label class="form-label">Reporting Manager Address</label>
        <textarea class="form-control" readonly rows="2">{{ $requisition->reporting_manager_address }}</textarea>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="date_of_joining_required" class="form-label">Date of Joining Required <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="date_of_joining_required" name="date_of_joining_required" 
               value="{{ $requisition->date_of_joining_required->format('Y-m-d') }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="agreement_duration" class="form-label">Agreement Duration (months)</label>
        <input type="number" class="form-control" id="agreement_duration" name="agreement_duration" 
               value="{{ $requisition->agreement_duration }}" min="1">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="date_of_separation" class="form-label">Date of Separation <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="date_of_separation" name="date_of_separation" 
               value="{{ $requisition->date_of_separation->format('Y-m-d') }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">₹</span>
            <input type="number" class="form-control" id="remuneration_per_month" name="remuneration_per_month" 
                   value="{{ $requisition->remuneration_per_month }}" step="0.01" required>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="fuel_reimbursement_per_month" class="form-label">Fuel Reimbursement/Month</label>
        <div class="input-group">
            <span class="input-group-text">₹</span>
            <input type="number" class="form-control" id="fuel_reimbursement_per_month" name="fuel_reimbursement_per_month" 
                   value="{{ $requisition->fuel_reimbursement_per_month }}" step="0.01">
        </div>
    </div>
</div>