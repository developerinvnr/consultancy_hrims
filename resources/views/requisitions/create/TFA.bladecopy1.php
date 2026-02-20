@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Create TFA Requisition</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('requisitions.index') }}">Requisitions</a></li>
                        <li class="breadcrumb-item active">Create TFA</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- End page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">Temporary Field Assistant (TFA) Requisition Form</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('requisitions.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="requisition-form" method="POST" action="{{ route('requisitions.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="requisition_type" value="TFA">

                        <!-- Hidden fields for file paths -->
                        <input type="hidden" name="pan_filename" id="pan_filename">
                        <input type="hidden" name="pan_filepath" id="pan_filepath">
                        <input type="hidden" name="bank_filename" id="bank_filename">
                        <input type="hidden" name="bank_filepath" id="bank_filepath">
                        <input type="hidden" name="aadhaar_filename" id="aadhaar_filename">
                        <input type="hidden" name="aadhaar_filepath" id="aadhaar_filepath">
                        <input type="hidden" name="dl_filename" id="dl_filename">
                        <input type="hidden" name="dl_filepath" id="dl_filepath">
                        <input type="hidden" name="resume_filename" id="resume_filename">
                        <input type="hidden" name="resume_filepath" id="resume_filepath">

                        <!-- ==================== SECTION 1: DOCUMENT UPLOADS WITH DATA EXTRACTION ==================== -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 1: Document Uploads with Data Extraction</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- First Row: Resume, Driving License, PAN Card, PAN Number -->
                                        <div class="row">
                                            <!-- Resume -->
                                            <div class="col-md-3 mb-3">
                                                <label for="resume" class="form-label">Resume <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                                <small class="text-muted">PDF, DOC, DOCX (Max 5MB)</small>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- Driving License -->
                                            <div class="col-md-3 mb-3">
                                                <label for="driving_licence" class="form-label">Driving Licence <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="driving_licence" name="driving_licence" accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- PAN Card -->
                                            <div class="col-md-3 mb-3">
                                                <label for="pan_card" class="form-label">PAN Card <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="pan_card" name="pan_card" accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">Clear image/PDF for auto-extraction</small>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- PAN Number -->
                                            <div class="col-md-3 mb-3">
                                                <label for="pan_no" class="form-label">PAN Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control"
                                                        id="pan_no" name="pan_no" maxlength="10"
                                                        placeholder="Auto-fills from upload" required>
                                                    <span class="input-group-text">
                                                        <i class="ri-checkbox-circle-fill text-success d-none" id="pan-verified-icon"></i>
                                                        <i class="ri-alert-fill text-warning d-none" id="pan-warning-icon"></i>
                                                    </span>
                                                </div>
                                                <small class="text-muted" id="pan-status-text">Upload PAN to auto-extract</small>
                                                <div class="invalid-feedback">Valid PAN required</div>
                                            </div>
                                        </div>

                                        <!-- Second Row: Aadhaar Card, Aadhaar Number, Bank Document, Account Holder Name -->
                                        <div class="row">
                                            <!-- Aadhaar Card -->
                                            <div class="col-md-3 mb-3">
                                                <label for="aadhaar_card" class="form-label">Aadhaar Card <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="aadhaar_card" name="aadhaar_card" accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">Clear image/PDF of Aadhaar</small>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- Aadhaar Number -->
                                            <div class="col-md-3 mb-3">
                                                <label for="aadhaar_no" class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control"
                                                        id="aadhaar_no" name="aadhaar_no" maxlength="12"
                                                        placeholder="Auto-fills from upload" required>
                                                    <span class="input-group-text">
                                                        <i class="ri-checkbox-circle-fill text-success d-none" id="aadhaar-verified-icon"></i>
                                                        <i class="ri-alert-fill text-warning d-none" id="aadhaar-warning-icon"></i>
                                                    </span>
                                                </div>
                                                <small class="text-muted" id="aadhaar-status-text">Upload Aadhaar to auto-extract</small>
                                                <div class="invalid-feedback">Valid Aadhaar required</div>
                                            </div>

                                            <!-- Bank Document -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_document" class="form-label">Bank Document <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="bank_document" name="bank_document" accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">Passbook/Cancelled Cheque</small>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- Account Holder Name -->
                                            <div class="col-md-3 mb-3">
                                                <label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="account_holder_name" name="account_holder_name"
                                                    placeholder="Auto-fills from bank document" required>
                                                <div class="invalid-feedback">Account holder name required</div>
                                            </div>
                                        </div>

                                        <!-- Third Row: Account Number, IFSC Code, Bank Name, Other Document -->
                                        <div class="row">
                                            <!-- Account Number -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_account_no" class="form-label">Account Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control"
                                                        id="bank_account_no" name="bank_account_no" maxlength="50"
                                                        placeholder="Auto-fills from document" required>
                                                    <span class="input-group-text">
                                                        <i class="ri-checkbox-circle-fill text-success d-none" id="account-verified-icon"></i>
                                                        <i class="ri-alert-fill text-warning d-none" id="account-warning-icon"></i>
                                                    </span>
                                                </div>
                                                <div class="invalid-feedback">Valid account number required</div>
                                            </div>

                                            <!-- IFSC Code -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_ifsc" class="form-label">IFSC Code <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control"
                                                        id="bank_ifsc" name="bank_ifsc" maxlength="11"
                                                        placeholder="Auto-fills from document" required>
                                                    <span class="input-group-text">
                                                        <i class="ri-checkbox-circle-fill text-success d-none" id="ifsc-verified-icon"></i>
                                                        <i class="ri-alert-fill text-warning d-none" id="ifsc-warning-icon"></i>
                                                    </span>
                                                </div>
                                                <div class="invalid-feedback">Valid IFSC code required</div>
                                            </div>

                                            <!-- Bank Name -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="bank_name" name="bank_name"
                                                    placeholder="Auto-fills from document" required>
                                                <div class="invalid-feedback">Bank name required</div>
                                            </div>

                                            <!-- Other Document -->
                                            <div class="col-md-3 mb-3">
                                                <label for="other_document" class="form-label">Other Document (Optional)</label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="other_document" name="other_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                <small class="text-muted">Additional documents</small>
                                            </div>
                                        </div>

                                        <!-- Fourth Row: DL Number, Valid From, Valid To (from DL) -->
                                        <div class="row">
                                            <!-- DL Number -->
                                            <div class="col-md-3 mb-3">
                                                <label for="driving_licence_no" class="form-label">Driving Licence Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control"
                                                        id="driving_licence_no" name="driving_licence_no"
                                                        placeholder="Auto-fills from DL upload" required>
                                                    <span class="input-group-text">
                                                        <i class="ri-checkbox-circle-fill text-success d-none" id="dl-verified-icon"></i>
                                                        <i class="ri-alert-fill text-warning d-none" id="dl-warning-icon"></i>
                                                    </span>
                                                </div>
                                                <small class="text-muted" id="dl-status-text">Upload DL to auto-extract</small>
                                                <div class="invalid-feedback">Valid DL number required</div>
                                            </div>

                                            <!-- Valid From -->
                                            <div class="col-md-3 mb-3">
                                                <label for="dl_valid_from" class="form-label">Valid From</label>
                                                <input type="date" class="form-control form-control-sm"
                                                    id="dl_valid_from" name="dl_valid_from" readonly>
                                                <small class="text-muted">Auto-filled from DL</small>
                                            </div>

                                            <!-- Valid To -->
                                            <div class="col-md-3 mb-3">
                                                <label for="dl_valid_to" class="form-label">Valid To</label>
                                                <input type="date" class="form-control form-control-sm"
                                                    id="dl_valid_to" name="dl_valid_to" readonly>
                                                <small class="text-muted">Auto-filled from DL</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== SECTION 2: PERSONAL DETAILS (from Aadhaar) ==================== -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 2: Personal Details (Auto-filled from Documents)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="candidate_name_from_aadhaar" class="form-label">Candidate Name</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="candidate_name_from_aadhaar" name="candidate_name" readonly>
                                                <small class="text-muted">From Aadhaar</small>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="father_name" class="form-label">Father's Name</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="father_name" name="father_name" readonly>
                                                <small class="text-muted">From Aadhaar</small>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control form-control-sm bg-light"
                                                    id="date_of_birth" name="date_of_birth" readonly>
                                                <small class="text-muted">From Aadhaar</small>
                                                <div class="invalid-feedback">Age must be 18 years or older</div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select class="form-select form-select-sm bg-light" id="gender" name="gender" disabled>
                                                    <option value="">Select</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <small class="text-muted">From Aadhaar</small>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="aadhaar_no_display" class="form-label">Aadhaar Number</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="aadhaar_no_display" readonly>
                                                <small class="text-muted">From upload</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="address_line_1" class="form-label">Address</label>
                                                <textarea class="form-control form-control-sm bg-light" id="address_line_1" name="address_line_1" rows="2" readonly></textarea>
                                                <small class="text-muted">From Aadhaar</small>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="mobile_no" class="form-label">Mobile No. <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="mobile_no" name="mobile_no" maxlength="10" required>
                                                <div class="invalid-feedback">10-digit number required</div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="candidate_email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control form-control-sm"
                                                    id="candidate_email" name="candidate_email" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="alternate_email" class="form-label">Alternate Email</label>
                                                <input type="email" class="form-control form-control-sm"
                                                    id="alternate_email" name="alternate_email">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== SECTION 3: EDUCATION DETAILS ==================== -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 3: Education Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="highest_qualification" class="form-label">Highest Qualification <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm select2" id="highest_qualification" name="highest_qualification" required>
                                                    <option value="">Select Qualification</option>
                                                    @foreach($educations as $education)
                                                    <option value="{{ $education->EducationId }}" {{ old('highest_qualification') == $education->EducationId ? 'selected' : '' }}>
                                                        {{ $education->EducationName }} ({{ $education->EducationCode }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="college_name" class="form-label">College/University</label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="college_name" name="college_name">
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="pin_code" name="pin_code" maxlength="6" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="state_residence" class="form-label">State <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="state_residence" name="state_residence" required>
                                                    <option value="">Select State</option>
                                                    @foreach($states as $state)
                                                    <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm select2" id="city" name="city" required>
                                                    <option value="">Select City</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== SECTION 4: WORK INFORMATION ==================== -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 4: Work Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="function_id" class="form-label">Function <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="function_id_display" disabled>
                                                    <option value="">Select Function</option>
                                                    @foreach($functions as $function)
                                                    <option value="{{ $function->id }}"
                                                        {{ ($autoFillData['function_id'] ?? '') == $function->id ? 'selected' : '' }}>
                                                        {{ $function->function_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="function_id" value="{{ $autoFillData['function_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="department_id_display" disabled>
                                                    <option value="">Select Department</option>
                                                    @foreach($departments as $department)
                                                    <option value="{{ $department->id }}"
                                                        {{ ($autoFillData['department_id'] ?? '') == $department->id ? 'selected' : '' }}>
                                                        {{ $department->department_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="department_id" value="{{ $autoFillData['department_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="vertical_id" class="form-label">Vertical <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="vertical_id_display" disabled>
                                                    <option value="">Select Vertical</option>
                                                    @foreach($verticals as $vertical)
                                                    <option value="{{ $vertical->id }}"
                                                        {{ ($autoFillData['vertical_id'] ?? '') == $vertical->id ? 'selected' : '' }}>
                                                        {{ $vertical->vertical_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="vertical_id" value="{{ $autoFillData['vertical_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="work_location_hq" class="form-label">Work Location/HQ <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="work_location_hq" name="work_location_hq" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="state_work_location" class="form-label">State (Work) <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="state_work_location" name="state_work_location" required>
                                                    <option value="">Select State</option>
                                                    @foreach($states as $state)
                                                    <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <label for="district" class="form-label">District <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="district" name="district" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="sub_department_id" class="form-label">Sub-department <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="sub_department_id_display" disabled>
                                                    <option value="">Select Sub-department</option>
                                                    @foreach($sub_departments as $subdepartment)
                                                    <option value="{{ $subdepartment->id }}"
                                                        {{ ($autoFillData['sub_department_id'] ?? '') == $subdepartment->id ? 'selected' : '' }}>
                                                        {{ $subdepartment->sub_department_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="sub_department_id" value="{{ $autoFillData['sub_department_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="business_unit" class="form-label">Business Unit <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="business_unit_display" disabled>
                                                    <option value="">Select Business Unit</option>
                                                    @foreach($businessUnits as $unit)
                                                    <option value="{{ $unit->id }}"
                                                        {{ ($autoFillData['business_unit_id'] ?? '') == $unit->id ? 'selected' : '' }}>
                                                        {{ $unit->business_unit_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="business_unit" value="{{ $autoFillData['business_unit_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="zone" class="form-label">Zone <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="zone_display" disabled>
                                                    <option value="">Select Zone</option>
                                                    @foreach($zones as $zone)
                                                    <option value="{{ $zone->id }}"
                                                        {{ ($autoFillData['zone_id'] ?? '') == $zone->id ? 'selected' : '' }}>
                                                        {{ $zone->zone_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="zone" value="{{ $autoFillData['zone_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="region" class="form-label">Region <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="region_display" disabled>
                                                    <option value="">Select Region</option>
                                                    @foreach($regions as $region)
                                                    <option value="{{ $region->id }}"
                                                        {{ ($autoFillData['region_id'] ?? '') == $region->id ? 'selected' : '' }}>
                                                        {{ $region->region_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="region" value="{{ $autoFillData['region_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="territory" class="form-label">Territory <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="territory_display" disabled>
                                                    <option value="">Select Territory</option>
                                                    @foreach($territories as $territory)
                                                    <option value="{{ $territory->id }}"
                                                        {{ ($autoFillData['territory_id'] ?? '') == $territory->id ? 'selected' : '' }}>
                                                        {{ $territory->territory_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="territory" value="{{ $autoFillData['territory_id'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== SECTION 5: EMPLOYMENT DETAILS ==================== -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 5: Employment Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Reporting To <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    value="{{ $autoFillData['reporting_to'] ?? '' }}" readonly>
                                                <input type="hidden" name="reporting_to" value="{{ $autoFillData['reporting_to'] ?? '' }}">
                                                <input type="hidden" name="reporting_manager_employee_id" value="{{ $autoFillData['reporting_manager_employee_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="contract_start_date" class="form-label">Contract Start <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-sm"
                                                    id="contract_start_date" name="contract_start_date" required>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="contract_duration" class="form-label">Duration <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="contract_duration" name="contract_duration" required>
                                                    <option value="">Select</option>
                                                    <option value="15">15 Days</option>
                                                    <option value="30">1 Month</option>
                                                    <option value="45">45 Days</option>
                                                    <option value="60">2 Months</option>
                                                    <option value="90">3 Months</option>
                                                    <option value="120">4 Months</option>
                                                </select>
                                                <div class="invalid-feedback">Please select contract duration</div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="contract_end_date" class="form-label">Contract End <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-sm"
                                                    id="contract_end_date" name="contract_end_date" readonly required>
                                                <div class="invalid-feedback">Contract end date will be calculated automatically</div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" class="form-control"
                                                        id="remuneration_per_month" name="remuneration_per_month"
                                                        step="0.01" min="0" required>
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label for="reporting_manager_address" class="form-label">Address for Agreement Dispatch <span class="text-danger">*</span></label>
                                                <textarea class="form-control form-control-sm"
                                                    id="reporting_manager_address" name="reporting_manager_address"
                                                    rows="3" required></textarea>
                                                <div class="invalid-feedback"></div>
                                                <small class="text-muted">Include PIN code and phone number</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-light btn-sm">Reset Form</button>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line me-1"></i> Submit Requisition
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    .card.border {
        border: 1px solid rgba(0, 0, 0, .125) !important;
    }

    .card-header.bg-light {
        background-color: #f8f9fa !important;
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .invalid-feedback {
        font-size: 0.75rem;
    }

    .form-control-sm,
    .form-select-sm {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
        height: calc(1.5em + 0.5rem + 2px);
    }

    .text-muted {
        font-size: 0.75rem;
    }

    .input-group-sm>.form-control,
    .input-group-sm>.form-select,
    .input-group-sm>.input-group-text {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
        height: calc(1.5em + 0.5rem + 2px);
    }

    select[readonly],
    select[disabled] {
        background-color: #f8f9fa;
        cursor: not-allowed;
        opacity: 0.8;
    }

    .form-control[readonly],
    .form-control.bg-light {
        background-color: #f8f9fa;
        opacity: 0.8;
    }
</style>

@push('scripts')
<script src="{{ asset('assets/js/contract-rules.js') }}"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            theme: 'bootstrap-5'
        });

        // Load cities when state is selected
        $('#state_residence').on('change', function() {
            const stateId = $(this).val();
            const citySelect = $('#city');
            
            if (stateId) {
                citySelect.prop('disabled', true);
                citySelect.html('<option value="">Loading cities...</option>');
                
                $.ajax({
                    url: '{{ route("hr.get.cities.by.state") }}',
                    type: 'GET',
                    data: { state_id: stateId },
                    success: function(response) {
                        citySelect.html('<option value="">Select City</option>');
                        $.each(response, function(index, city) {
                            citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                        });
                        citySelect.prop('disabled', false);
                    },
                    error: function() {
                        citySelect.html('<option value="">Error loading cities</option>');
                        citySelect.prop('disabled', false);
                    }
                });
            } else {
                citySelect.html('<option value="">Select City</option>');
            }
        });

        initContractDateValidation("#contract_start_date");
        
        const requisitionType = $('input[name="requisition_type"]').val();

        // ==================== AADHAAR EXTRACTION ====================
        $('#aadhaar_card').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            updateAadhaarStatus('loading', 'Extracting Aadhaar number...');
            $('#aadhaar_no').prop('disabled', true).val('Extracting...');

            const formData = new FormData();
            formData.append('aadhaar_file', file);
            formData.append('requisition_type', requisitionType);

            $.ajax({
                url: '{{ route("process.aadhaar.card") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS' || response.status === 'PARTIAL_SUCCESS') {
                        const data = response.data;
                        
                        // Fill Aadhaar number
                        if (data.aadhaarNumber) {
                            $('#aadhaar_no').val(data.aadhaarNumber);
                            $('#aadhaar_no_display').val(data.aadhaarNumber);
                            updateAadhaarStatus('success', 'Aadhaar extracted successfully!', true);
                        }

                        // Fill personal details from Aadhaar
                        if (data.extractedData) {
                            $('#candidate_name_from_aadhaar').val(data.extractedData.name || '');
                            $('#father_name').val(data.extractedData.fatherName || '');
                            $('#date_of_birth').val(data.extractedData.dob || '');
                            $('#address_line_1').val(data.extractedData.address || '');
                            
                            if (data.extractedData.gender) {
                                $('#gender').val(data.extractedData.gender);
                            }
                        }

                        // Store file info
                        $('#aadhaar_filename').val(data.filename);
                        $('#aadhaar_filepath').val(data.filePath);
                        
                        showToast('Aadhaar extracted successfully!', 'success');
                    } else {
                        updateAadhaarStatus('error', 'Failed to extract. Enter manually.');
                        showToast('Failed to extract Aadhaar', 'error');
                    }
                },
                error: function() {
                    updateAadhaarStatus('error', 'Failed - enter manually');
                    showToast('Error extracting Aadhaar', 'error');
                },
                complete: function() {
                    $('#aadhaar_no').prop('disabled', false);
                }
            });
        });

        function updateAadhaarStatus(status, message, isVerified = false) {
            const verifiedIcon = $('#aadhaar-verified-icon');
            const warningIcon = $('#aadhaar-warning-icon');
            const statusText = $('#aadhaar-status-text');

            verifiedIcon.addClass('d-none');
            warningIcon.addClass('d-none');

            if (status === 'success') {
                if (isVerified) {
                    verifiedIcon.removeClass('d-none');
                } else {
                    warningIcon.removeClass('d-none');
                }
            } else if (status === 'error') {
                $('#aadhaar_no').addClass('is-invalid');
            }

            statusText.text(message);
        }

        // ==================== PAN EXTRACTION ====================
        $('#pan_card').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            $('#pan_no').prop('disabled', true).val('Extracting...');
            $('#pan-status-text').text('Extracting PAN...');

            const formData = new FormData();
            formData.append('pan_file', file);
            formData.append('requisition_type', requisitionType);

            $.ajax({
                url: '{{ route("process.pan.card") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;
                        
                        $('#pan_no').val(data.panNumber);
                        $('#pan-status-text').text('PAN extracted successfully');
                        
                        if (data.isVerified) {
                            $('#pan-verified-icon').removeClass('d-none');
                            $('#pan-warning-icon').addClass('d-none');
                        } else {
                            $('#pan-verified-icon').addClass('d-none');
                            $('#pan-warning-icon').removeClass('d-none');
                            $('#pan-status-text').text('PAN extracted but not verified');
                        }

                        $('#pan_filename').val(data.filename);
                        $('#pan_filepath').val(data.filePath);
                        
                        showToast('PAN extracted successfully!', 'success');
                    }
                },
                error: function() {
                    $('#pan-status-text').text('Failed - enter manually');
                    showToast('Failed to extract PAN', 'error');
                },
                complete: function() {
                    $('#pan_no').prop('disabled', false);
                }
            });
        });

        // ==================== BANK DOCUMENT EXTRACTION ====================
        $('#bank_document').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            $('#account_holder_name, #bank_account_no, #bank_ifsc, #bank_name').prop('disabled', true)
                .val('Extracting...');

            const formData = new FormData();
            formData.append('bank_file', file);
            formData.append('requisition_type', requisitionType);

            $.ajax({
                url: '{{ route("process.bank.document") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;
                        const vData = data.verificationData || {};

                        // Fill account details
                        if (data.accountNumber) {
                            $('#bank_account_no').val(data.accountNumber);
                            $('#account-verified-icon').removeClass('d-none');
                        }

                        if (data.ifscCode) {
                            $('#bank_ifsc').val(data.ifscCode);
                            $('#ifsc-verified-icon').removeClass('d-none');
                        }

                        if (vData.beneficiary_name) {
                            $('#account_holder_name').val(vData.beneficiary_name);
                        }

                        if (vData.ifsc_details?.name) {
                            $('#bank_name').val(vData.ifsc_details.name);
                        }

                        if (data.isVerified) {
                            showToast('Bank details extracted & verified!', 'success');
                        } else {
                            $('#account-warning-icon, #ifsc-warning-icon').removeClass('d-none');
                            showToast('Bank details extracted but not verified', 'warning');
                        }

                        $('#bank_filename').val(data.filename);
                        $('#bank_filepath').val(data.filePath);
                    }
                },
                error: function() {
                    showToast('Failed to extract bank details', 'error');
                },
                complete: function() {
                    $('#account_holder_name, #bank_account_no, #bank_ifsc, #bank_name').prop('disabled', false);
                }
            });
        });

        // ==================== DRIVING LICENSE EXTRACTION ====================
        $('#driving_licence').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            $('#driving_licence_no').prop('disabled', true).val('Extracting...');
            $('#dl-status-text').text('Extracting DL...');

            const formData = new FormData();
            formData.append('dl_file', file);
            formData.append('requisition_type', requisitionType);

            $.ajax({
                url: '{{ route("process.driving.license") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;

                        $('#driving_licence_no').val(data.dlNumber);
                        $('#dl_valid_from').val(data.validFrom);
                        $('#dl_valid_to').val(data.validTo);
                        
                        $('#dl-verified-icon').removeClass('d-none');
                        $('#dl-status-text').text('DL extracted successfully');

                        // Check validity
                        if (data.validTo) {
                            const today = new Date();
                            const validTo = new Date(data.validTo);
                            if (validTo <= today) {
                                $('#dl-warning-icon').removeClass('d-none');
                                $('#dl-verified-icon').addClass('d-none');
                                $('#dl-status-text').text('DL extracted but expired');
                                showToast('Warning: Driving license is expired', 'warning');
                            }
                        }

                        $('#dl_filename').val(data.filename);
                        $('#dl_filepath').val(data.filePath);
                        
                        showToast('Driving license extracted!', 'success');
                    }
                },
                error: function() {
                    $('#dl-status-text').text('Failed - enter manually');
                    showToast('Failed to extract DL', 'error');
                },
                complete: function() {
                    $('#driving_licence_no').prop('disabled', false);
                }
            });
        });

        // ==================== CONTRACT DATE CALCULATION ====================
        function calculateSeparationDate() {
            const doj = $('#contract_start_date').val();
            const duration = parseInt($('#contract_duration').val());

            if (doj && duration) {
                const dojDate = new Date(doj + "T00:00:00");
                const separationDate = new Date(dojDate);
                separationDate.setDate(separationDate.getDate() + duration - 1);

                const yyyy = separationDate.getFullYear();
                const mm = String(separationDate.getMonth() + 1).padStart(2, '0');
                const dd = String(separationDate.getDate()).padStart(2, '0');

                $('#contract_end_date').val(`${yyyy}-${mm}-${dd}`);
            }
        }

        $('#contract_start_date, #contract_duration').on('change', function() {
            if ($('#contract_start_date').val() && $('#contract_duration').val()) {
                calculateSeparationDate();
            }
        });

        // ==================== VALIDATIONS ====================
        $('#date_of_birth').on('change', function() {
            const birthDate = new Date($(this).val());
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 18) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('Age must be 18+').show();
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            }
        });

        $('#mobile_no').on('input', function() {
            const mobile = $(this).val();
            if (mobile.length === 10 && /^\d+$/.test(mobile)) {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            } else if (mobile.length > 0) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').show();
            }
        });

        $('#pin_code').on('input', function() {
            const pincode = $(this).val();
            if (pincode.length === 6 && /^\d+$/.test(pincode)) {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            } else if (pincode.length > 0) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').show();
            }
        });

        // File size validation
        $('input[type="file"]').on('change', function() {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024;

            if (file && file.size > maxSize) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('File must be < 5MB').show();
                this.value = '';
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            }
        });

        // ==================== TOAST FUNCTION ====================
        function showToast(message, type = 'info') {
            const bgColor = type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'warning');
            const toast = `<div class="toast align-items-center text-bg-${bgColor} border-0 show position-fixed" role="alert" style="bottom: 20px; right: 20px; z-index: 1050; min-width: 200px;">
                <div class="d-flex">
                    <div class="toast-body small py-1">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-1 m-auto" data-bs-dismiss="toast" style="font-size: 0.7rem;"></button>
                </div>
            </div>`;

            $('.toast').remove();
            $('body').append(toast);

            setTimeout(() => {
                $('.toast').remove();
            }, 5000);
        }

        // ==================== FORM SUBMISSION ====================
        $('#requisition-form').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const url = form.attr('action');
            const formData = new FormData(form[0]);

            // Clear previous errors
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            // Check if any extraction is in progress
            if ($('#aadhaar-status-text').text().includes('Extracting') ||
                $('#pan-status-text').text().includes('Extracting') ||
                $('#dl-status-text').text().includes('Extracting')) {
                showToast('Please wait for extraction to finish', 'warning');
                return;
            }

            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="ri-loader-4-line ri-spin me-1"></i> Submitting...').prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showToast('Requisition submitted successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    submitBtn.html(originalText).prop('disabled', false);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors || {};
                        
                        $.each(errors, function(field, messages) {
                            const input = form.find(`[name="${field}"]`);
                            if (input.length) {
                                input.addClass('is-invalid');
                                
                                let feedback = input.siblings('.invalid-feedback');
                                if (!feedback.length) {
                                    feedback = input.closest('.col-md-*, .input-group').find('.invalid-feedback:first');
                                }
                                
                                if (feedback.length) {
                                    feedback.text(messages[0]).show();
                                }
                            }
                        });
                        
                        showToast('Please correct the highlighted fields', 'error');
                    } else {
                        showToast('Something went wrong. Please try again.', 'error');
                    }
                }
            });
        });
    });
</script>
@endpush