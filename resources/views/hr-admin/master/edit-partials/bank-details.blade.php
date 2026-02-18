<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Account Holder Name</label>
            <input type="text" name="account_holder_name" class="form-control" 
                   value="{{ old('account_holder_name', $candidate->account_holder_name) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Bank Account Number</label>
            <input type="text" name="bank_account_no" class="form-control" 
                   value="{{ old('bank_account_no', $candidate->bank_account_no) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Bank Name</label>
            <input type="text" name="bank_name" class="form-control" 
                   value="{{ old('bank_name', $candidate->bank_name) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>IFSC Code</label>
            <input type="text" name="bank_ifsc" class="form-control text-uppercase" 
                   value="{{ old('bank_ifsc', $candidate->bank_ifsc) }}"
                   pattern="^[A-Z]{4}0[A-Z0-9]{6}$"
                   title="Please enter valid IFSC code">
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Bank details changes will be effective from next payroll cycle.
</div>