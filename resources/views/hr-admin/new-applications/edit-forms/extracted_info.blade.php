<div class="row">
    <div class="col-md-6 mb-3">
        <label for="pan_no" class="form-label">PAN Number</label>
        <input type="text" class="form-control" id="pan_no" name="pan_no" 
               value="{{ $requisition->pan_no }}" maxlength="10" style="text-transform:uppercase;">
        <small class="text-muted">Format: ABCDE1234F</small>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="aadhaar_no" class="form-label">Aadhaar Number</label>
        <input type="text" class="form-control" id="aadhaar_no" name="aadhaar_no" 
               value="{{ $requisition->aadhaar_no }}" maxlength="12">
        <small class="text-muted">12-digit Aadhaar number</small>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="bank_account_no" class="form-label">Bank Account Number</label>
        <input type="text" class="form-control" id="bank_account_no" name="bank_account_no" 
               value="{{ $requisition->bank_account_no }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="account_holder_name" class="form-label">Account Holder Name</label>
        <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" 
               value="{{ $requisition->account_holder_name }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="bank_ifsc" class="form-label">Bank IFSC Code</label>
        <input type="text" class="form-control" id="bank_ifsc" name="bank_ifsc" 
               value="{{ $requisition->bank_ifsc }}" style="text-transform:uppercase;">
        <small class="text-muted">Format: ABCD0123456</small>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="bank_name" class="form-label">Bank Name</label>
        <input type="text" class="form-control" id="bank_name" name="bank_name" 
               value="{{ $requisition->bank_name }}">
    </div>
</div>