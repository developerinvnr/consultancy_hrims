<div class="row">
    <div class="col-md-6 mb-3">
        <label for="father_name" class="form-label">Father's Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="father_name" name="father_name" 
               value="{{ $requisition->father_name }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="mobile_no" class="form-label">Mobile Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="mobile_no" name="mobile_no" 
               value="{{ $requisition->mobile_no }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="alternate_email" class="form-label">Alternate Email</label>
        <input type="email" class="form-control" id="alternate_email" name="alternate_email" 
               value="{{ $requisition->alternate_email }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
               value="{{ $requisition->date_of_birth->format('Y-m-d') }}" required>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="gender" class="form-label">Gender</label>
        <select class="form-select" id="gender" name="gender">
            <option value="">-- Select Gender --</option>
            <option value="Male" {{ $requisition->gender == 'Male' ? 'selected' : '' }}>Male</option>
            <option value="Female" {{ $requisition->gender == 'Female' ? 'selected' : '' }}>Female</option>
            <option value="Other" {{ $requisition->gender == 'Other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="highest_qualification" class="form-label">Highest Qualification</label>
        <input type="text" class="form-control" id="highest_qualification" name="highest_qualification" 
               value="{{ $requisition->highest_qualification }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="college_name" class="form-label">College/University</label>
        <input type="text" class="form-control" id="college_name" name="college_name" 
               value="{{ $requisition->college_name }}">
    </div>
    
    <div class="col-12 mb-3">
        <label for="address_line_1" class="form-label">Address <span class="text-danger">*</span></label>
        <textarea class="form-control" id="address_line_1" name="address_line_1" rows="2" required>{{ $requisition->address_line_1 }}</textarea>
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="city" name="city" 
               value="{{ $requisition->city }}" required>
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="state_residence" class="form-label">State (Residence) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="state_residence" name="state_residence" 
               value="{{ $requisition->state_residence }}" required>
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="pin_code" name="pin_code" 
               value="{{ $requisition->pin_code }}" required>
    </div>
</div>