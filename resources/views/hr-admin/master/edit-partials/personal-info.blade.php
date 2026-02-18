<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Party Name <span class="text-danger">*</span></label>
            <input type="text" 
                   name="candidate_name" 
                   class="form-control @error('candidate_name') is-invalid @enderror"
                   value="{{ old('candidate_name', $candidate->candidate_name) }}" 
                   required>
            @error('candidate_name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Father's Name</label>
            <input type="text" 
                   name="father_name" 
                   class="form-control"
                   value="{{ old('father_name', $candidate->father_name) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Email</label>
            <input type="email" 
                   name="candidate_email" 
                   class="form-control @error('candidate_email') is-invalid @enderror"
                   value="{{ old('candidate_email', $candidate->candidate_email) }}" 
                   >
            @error('candidate_email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Mobile Number <span class="text-danger">*</span></label>
            <input type="text" 
                   name="mobile_no" 
                   class="form-control @error('mobile_no') is-invalid @enderror"
                   value="{{ old('mobile_no', $candidate->mobile_no) }}" 
                   maxlength="10"
                   required>
            @error('mobile_no')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" 
                   name="date_of_birth" 
                   class="form-control"
                   value="{{ old('date_of_birth', $candidate->date_of_birth ? $candidate->date_of_birth->format('Y-m-d') : '') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select</option>
                <option value="Male" {{ $candidate->gender == 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ $candidate->gender == 'Female' ? 'selected' : '' }}>Female</option>
                <option value="Other" {{ $candidate->gender == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>PAN Number</label>
            <input type="text" 
                   name="pan_no" 
                   class="form-control text-uppercase"
                   value="{{ old('pan_no', $candidate->pan_no) }}"
                   pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}"
                   title="Please enter valid PAN (e.g., ABCDE1234F)">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Aadhaar Number</label>
            <input type="text" 
                   name="aadhaar_no" 
                   class="form-control"
                   value="{{ old('aadhaar_no', $candidate->aadhaar_no) }}"
                   pattern="\d{12}"
                   title="Please enter 12-digit Aadhaar number">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Qualification</label>
            <input type="text" 
                   name="highest_qualification" 
                   class="form-control"
                   value="{{ old('highest_qualification', $candidate->highest_qualification) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="form-group">
            <label>Address</label>
            <textarea name="address_line_1" class="form-control" rows="2">{{ old('address_line_1', $candidate->address_line_1) }}</textarea>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>City</label>
            <input type="text" name="city" class="form-control" value="{{ old('city', $candidate->city) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>State</label>
            <input type="text" name="state_residence" class="form-control" value="{{ old('state_residence', $candidate->state_residence) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>PIN Code</label>
            <input type="text" name="pin_code" class="form-control" value="{{ old('pin_code', $candidate->pin_code) }}">
        </div>
    </div>
</div>