@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Create Contractual Requisition</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('requisitions.index') }}">Requisitions</a></li>
                        <li class="breadcrumb-item active">Create Contractual</li>
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
                            <h5 class="card-title mb-0">Contractual Manpower Requisition Form</h5>
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
                        <input type="hidden" name="requisition_type" value="Contractual">
                        <input type="hidden" name="pan_filename" id="pan_filename">
                        <input type="hidden" name="pan_filepath" id="pan_filepath">
                        <input type="hidden" name="bank_filename" id="bank_filename">
                        <input type="hidden" name="bank_filepath" id="bank_filepath">
                        <input type="hidden" name="aadhaar_filename" id="aadhaar_filename">
                        <input type="hidden" name="aadhaar_filepath" id="aadhaar_filepath">
                        <input type="hidden" name="resume_filename" id="resume_filename">
                        <input type="hidden" name="resume_filepath" id="resume_filepath">
                        <input type="hidden" name="driving_filename" id="driving_filename">
                        <input type="hidden" name="driving_filepath" id="driving_filepath">

                        <!-- ==================== SECTION 1: DOCUMENT UPLOADS & VERIFICATION ==================== -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 1: Document Uploads & Verification</h6>
                                    </div>
                                    <div class="card-body">

                                        <!-- ==================== AADHAAR SECTION ==================== -->
                                        <div class="row">

                                            <!-- Aadhaar Upload -->
                                            <div class="col-md-4  mb-3">
                                                <label for="aadhaar_card" class="form-label">Upload Aadhaar<span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="aadhaar_card" name="aadhaar_card" accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Auto-fills details</small>
                                            </div>

                                            <!-- Aadhaar Number without Verify Button -->
                                            <div class="col-md-4  mb-3">
                                                <label for="aadhaar_no" class="form-label">Aadhaar Number<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="aadhaar_no" name="aadhaar_no" maxlength="12"
                                                    placeholder="Auto-fills from upload">
                                                <div class="invalid-feedback"></div>
                                                <small id="aadhaar_status_text" class="text-muted"></small>
                                            </div>

                                            <!-- Aadhaar Status (Auto-filled from API) -->
                                            <div class="col-md-4  mb-3">
                                                <label class="form-label">Aadhaar Status</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="aadhaar_verification_status" name="aadhaar_verification_status"
                                                    placeholder="Auto-filled" readonly>
                                            </div>

                                        </div>

                                        <!-- ==================== PAN SECTION ==================== -->
                                        <div class="row">

                                            <!-- PAN Upload -->
                                            <div class="col-md-3 mb-3">
                                                <label for="pan_card" class="form-label">Upload PAN<span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="pan_card" name="pan_card" accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Auto-extracts PAN</small>
                                            </div>

                                            <!-- PAN Number with Verify Button -->
                                            <div class="col-md-3 mb-3">
                                                <label for="pan_no" class="form-label">PAN Number<span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="pan_no" name="pan_no" maxlength="10"
                                                        placeholder="Enter or auto-fill">
                                                    <button class="btn btn-outline-primary btn-sm" type="button" id="verify-pan-btn" title="Verify PAN">
                                                        <i class="ri-search-line"></i>
                                                    </button>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>

                                            <!-- PAN Validity (Auto-filled from API) -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAN Validity</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="pan_verification_status" name="pan_verification_status"
                                                    placeholder="Auto-filled" readonly>
                                            </div>

                                            <!-- PAN Status (Auto-filled from API) -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAN Status</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="pan_status_2" name="pan_status_2"
                                                    placeholder="Auto-filled" readonly>
                                            </div>

                                            <!-- PAN-Aadhaar Link (Auto-filled from API) -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PAN-Aadhaar Link</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="pan_aadhaar_link_status" name="pan_aadhaar_link_status"
                                                    placeholder="Auto-filled" readonly>
                                            </div>



                                        </div>

                                        <!-- ==================== BANK SECTION ==================== -->
                                        <div class="row">

                                            <!-- Bank Document Upload -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_document" class="form-label">Upload Bank Doc<span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="bank_document" name="bank_document" accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Passbook/Cheque</small>
                                            </div>


                                            <!-- Account Number with Verify Button -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_account_no" class="form-label">Account No.<span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="bank_account_no" name="bank_account_no" maxlength="50"
                                                        placeholder="Enter account no">
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <!-- IFSC Code with Verify Button -->
                                            <div class="col-md-3 mb-3">
                                                <label for="bank_ifsc" class="form-label">IFSC Code<span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="bank_ifsc" name="bank_ifsc" maxlength="11"
                                                        placeholder="Enter IFSC">
                                                    <button class="btn btn-outline-primary btn-sm" type="button" id="verify-account-btn" title="Verify Account">
                                                        <i class="ri-search-line"></i>
                                                    </button>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>

                                            <!-- Account Holder Name -->
                                            <div class="col-md-3 mb-3">
                                                <label for="account_holder_name" class="form-label">Holder Name<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="account_holder_name" name="account_holder_name"
                                                    placeholder="As per bank records">
                                            </div>


                                        </div>
                                        <div class="row">
                                            <!-- Bank Name (Auto-filled from IFSC) -->
                                            <div class="col-md-4 mb-3">
                                                <label for="bank_name" class="form-label">Bank Name<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="bank_name" name="bank_name" readonly>
                                            </div>

                                            <!-- Branch Address (Auto-filled from IFSC) -->
                                            <div class="col-md-4 mb-3">
                                                <label for="bank_branch_address" class="form-label">Branch Address</label>
                                                <textarea class="form-control form-control-sm bg-light"
                                                    id="bank_branch_address" name="bank_branch_address"
                                                    rows="1" readonly></textarea>
                                            </div>
                                            <!-- Bank Verification Status (Auto-filled) -->
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank Status</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="bank_verification_status" name="bank_verification_status"
                                                    placeholder="Auto-filled" readonly>
                                            </div>

                                        </div>

                                        <!-- ==================== DRIVING LICENSE SECTION ==================== -->
                                        <div class="row">


                                            <!-- DL Upload -->
                                            <div class="col-md-3 mb-3">
                                                <label for="driving_licence" class="form-label">Upload DL<span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="driving_licence" name="driving_licence" accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Auto-extracts details</small>
                                            </div>

                                            <!-- DL Number with Verify Button -->
                                            <div class="col-md-3 mb-3">
                                                <label for="driving_licence_no" class="form-label">DL Number</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control form-control-sm"
                                                        id="driving_licence_no" name="driving_licence_no"
                                                        placeholder="Enter or auto-fill">
                                                    <button class="btn btn-outline-primary btn-sm" type="button" id="verify-dl-btn" title="Verify DL">
                                                        <i class="ri-search-line"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Valid From (Auto-filled) -->
                                            <div class="col-md-2 mb-3">
                                                <label for="dl_valid_from" class="form-label">Valid From</label>
                                                <input type="date" class="form-control form-control-sm bg-light"
                                                    id="dl_valid_from" name="dl_valid_from" readonly>
                                            </div>

                                            <!-- Valid To (Auto-filled) -->
                                            <div class="col-md-2 mb-3">
                                                <label for="dl_valid_to" class="form-label">Valid To</label>
                                                <input type="date" class="form-control form-control-sm bg-light"
                                                    id="dl_valid_to" name="dl_valid_to" readonly>
                                            </div>

                                            <!-- DL Status (Auto-filled) -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">DL Status</label>
                                                <input type="text" class="form-control form-control-sm bg-light"
                                                    id="dl_verification_status" name="dl_verification_status"
                                                    placeholder="Auto-filled" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <!-- Resume Upload -->
                                            <div class="col-md-6 mb-3">
                                                <label for="resume" class="form-label">Resume <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="resume" name="resume" accept=".pdf,.doc,.docx">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">PDF, DOC, DOCX</small>
                                            </div>

                                            <!-- Other Document -->
                                            <div class="col-md-6 mb-3">
                                                <label for="other_document" class="form-label">Other Document</label>
                                                <input type="file" class="form-control form-control-sm"
                                                    id="other_document" name="other_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Additional docs</small>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 2: Personal Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="candidate_name" class="form-label">Candidate's Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm"
                                                    id="candidate_name" name="candidate_name" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="father_name" class="form-label">Father's Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm"
                                                    id="father_name" name="father_name" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-select-sm"
                                                    id="date_of_birth" name="date_of_birth" required>
                                                <div class="invalid-feedback">Age must be 18 years or older</div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="gender" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="mobile_no" class="form-label">Mobile No. <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm"
                                                    id="mobile_no" name="mobile_no" maxlength="10" required>
                                                <div class="invalid-feedback">10-digit number required</div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="candidate_email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control form-select-sm"
                                                    id="candidate_email" name="candidate_email" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="alternate_email" class="form-label">Alternate Email</label>
                                                <input type="email" class="form-control form-select-sm"
                                                    id="alternate_email" name="alternate_email">
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="highest_qualification" class="form-label">
                                                    Highest Qualification <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select form-select-sm select2"
                                                    id="highest_qualification"
                                                    name="highest_qualification"
                                                    required>
                                                    <option value="">Select Qualification</option>
                                                    @foreach($educations as $education)
                                                    <option value="{{ $education->EducationId }}">
                                                        {{ $education->EducationName }} ({{ $education->EducationCode }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="college_name" class="form-label">College/University</label>
                                                <input type="text" class="form-control form-select-sm"
                                                    id="college_name" name="college_name">
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="state_residence" class="form-label">State <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm"
                                                    id="state_residence"
                                                    name="state_residence" required>
                                                    <option value="">Select State</option>
                                                    @foreach($states as $state)
                                                    <option value="{{ $state->id }}">
                                                        {{ $state->state_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm select2"
                                                    id="city"
                                                    name="city" required>
                                                    <option value="">Select City</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                                <textarea class="form-control form-select-sm"
                                                    id="address_line_1" name="address_line_1" rows="2" required></textarea>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm"
                                                    id="pin_code" name="pin_code" maxlength="6" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Work Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 3: Work Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="function_id" class="form-label">Function <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" disabled>
                                                    @foreach($functions as $function)
                                                    <option {{ $autoFillData['function_id'] == $function->id ? 'selected' : '' }}>
                                                        {{ $function->function_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="function_id" value="{{ $autoFillData['function_id'] }}">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" disabled>
                                                    @foreach($departments as $department)
                                                    <option {{ $autoFillData['department_id'] == $department->id ? 'selected' : '' }}>
                                                        {{ $department->department_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="department_id" value="{{ $autoFillData['department_id'] }}">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="sub_department_id" class="form-label">Sub-department <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" disabled>
                                                    <option value="">Select Sub-department</option>
                                                    @foreach($sub_departments as $subdepartment)
                                                    <option value="{{ $subdepartment->id }}"
                                                        {{ $autoFillData['sub_department_id'] == $subdepartment->id ? 'selected' : '' }}>
                                                        {{ $subdepartment->sub_department_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="sub_department_id" value="{{ $autoFillData['sub_department_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="vertical_id" class="form-label">Vertical <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" disabled>
                                                    @foreach($verticals as $vertical)
                                                    <option {{ $autoFillData['vertical_id'] == $vertical->id ? 'selected' : '' }}>
                                                        {{ $vertical->vertical_name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="vertical_id" value="{{ $autoFillData['vertical_id'] }}">
                                            </div>
                                        </div>

                                        <div class="row">

                                            <div class="col-md-4 mb-3">
                                                <label for="state_work_location" class="form-label">State (Work Location) <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="state_work_location" name="state_work_location" required>
                                                    <option value="">Select State</option>
                                                    @foreach($states as $state)
                                                    <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">
                                                    District <span class="text-danger">*</span>
                                                </label>

                                                <select class="form-select form-select-sm"
                                                    id="contract_district_id"
                                                    name="district_id">
                                                    <option value="">Select District</option>
                                                </select>

                                                <input type="hidden" name="district" id="contract_district_name">

                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">
                                                    Work Location / HQ <span class="text-danger">*</span>
                                                </label>

                                                <select class="form-select form-select-sm"
                                                    id="contract_work_location_id"
                                                    name="work_location_id">
                                                    <option value="">Select City</option>
                                                </select>

                                                <input type="hidden" name="work_location_hq" id="contract_work_location_name">

                                                <div class="invalid-feedback"></div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Employment Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Section 4: Employment Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Reporting To <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm"
                                                    value="{{ $autoFillData['reporting_to'] }}" readonly>
                                                <input type="hidden" name="reporting_to" value="{{ $autoFillData['reporting_to'] }}">
                                                <input type="hidden" name="reporting_manager_employee_id" value="{{ $autoFillData['reporting_manager_employee_id'] }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="contract_start_date" class="form-label">Contract Start Date<span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-select-sm"
                                                    id="contract_start_date" name="contract_start_date" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="contract_duration" class="form-label">Contract Duration<span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="contract_duration" name="contract_duration" required>
                                                    <option value="">Select Duration</option>
                                                    <option value="30">1 Month</option>
                                                    <option value="60">2 Months</option>
                                                    <option value="90">3 Months</option>
                                                    <option value="120">4 Months</option>
                                                    <option value="150">5 Months</option>
                                                    <option value="180">6 Months</option>
                                                    <option value="210">7 Months</option>
                                                    <option value="240">8 Months</option>
                                                    <option value="270">9 Months</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="contract_end_date" class="form-label">Contract End Date<span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-select-sm"
                                                    id="contract_end_date" name="contract_end_date" readonly required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" class="form-control"
                                                        id="remuneration_per_month" name="remuneration_per_month"
                                                        step="0.01" min="0" required>
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Fuel & Other Reimbursement Required?</label>
                                                <select class="form-select form-select-sm"
                                                    name="other_reimbursement_required">
                                                    <option value="">Select</option>
                                                    <option value="Y">Yes</option>
                                                    <option value="N">No</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Out of Pocket Expense Required?</label>
                                                <select class="form-select form-select-sm"
                                                    name="out_of_pocket_required">
                                                    <option value="">Select</option>
                                                    <option value="Y">Yes</option>
                                                    <option value="N">No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label for="reporting_manager_address" class="form-label">Address for Agreement Dispatch <span class="text-danger">*</span></label>
                                                <textarea class="form-control form-select-sm"
                                                    id="reporting_manager_address" name="reporting_manager_address"
                                                    rows="3" required></textarea>
                                                <div class="invalid-feedback"></div>
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

    .form-select-sm,
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
                    data: {
                        state_id: stateId
                    },
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
        // Get requisition type from hidden input
        const requisitionType = $('input[name="requisition_type"]').val();

        // ==================== AADHAAR VERIFICATION ====================
        // Auto-extract from uploaded Aadhaar only - NO MANUAL VERIFICATION

        // Process Aadhaar Card when file is selected
        $('#aadhaar_card').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            $('#aadhaar_no').prop('readonly', true);
            $('#aadhaar_status_text').text('Extracting...');
            $('#aadhaar_verification_status').val('Extracting...');

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

                        if (data.aadhaarNumber) {
                            $('#aadhaar_no')
                                .val(data.aadhaarNumber)
                                .prop('readonly', true);
                        }

                        // Store filename for submission
                        $('#aadhaar_filename').val(data.filename);
                        $('#aadhaar_filepath').val(data.filePath);

                        // Update Aadhaar Status field
                        if (data.isVerified) {
                            $('#aadhaar_verification_status').val('Verified');
                            $('#aadhaar_status_text').text('Extracted & Verified');
                            // 🔒 LOCK FIELD
                            $('#aadhaar_no').prop('readonly', true);

                        } else {
                            $('#aadhaar_verification_status').val('Pending');
                            $('#aadhaar_status_text').text('Extracted - Pending Verification');
                            // allow editing if not verified
                            $('#aadhaar_no').prop('readonly', false);
                        }

                        showToast('Aadhaar extracted successfully!', 'success');
                    } else {
                        $('#aadhaar_status_text').text('Failed - enter manually');
                        $('#aadhaar_verification_status').val('Failed');
                        showToast(response.message || 'Aadhaar extraction failed', 'warning');
                    }
                },
                error: function() {
                    $('#aadhaar_status_text').text('Failed - enter manually');
                    $('#aadhaar_verification_status').val('Failed');
                    showToast('Failed to extract Aadhaar', 'error');
                },
                complete: function() {
                    if (!$('#aadhaar_no').val()) {
                        $('#aadhaar_no').prop('readonly', false);
                    }
                }
            });
        });

        // ==================== PAN VERIFICATION ====================

        // Auto-extract from uploaded PAN
        $('#pan_card').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            extractPANFromFile(file);
        });

        // Manual PAN verification button
        $('#verify-pan-btn').on('click', function() {
            const panNo = $('#pan_no').val();
            if (!panNo || panNo.length !== 10) {
                showToast('Please enter a valid 10-character PAN number', 'warning');
                return;
            }
            verifyPANManually(panNo);
        });

        function extractPANFromFile(file) {
            // Show loading state
            $('#pan_no').prop('readonly', true).val('');
            $('#pan_verification_status').val('Extracting...');
            $('#pan_status_2').val('Extracting...');
            $('#pan_aadhaar_link_status').val('Extracting...');

            const formData = new FormData();
            formData.append('pan_file', file);
            formData.append('requisition_type', requisitionType);

            // Add timestamp to prevent caching
            const url = '{{ route("process.pan.card") }}' + '?_=' + new Date().getTime();

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;

                        // Fill PAN number
                        $('#pan_no').val(data.panNumber).prop('readonly', true);

                        // Autofill father name
                        // Autofill father name
                        if (data.fatherName && !$('#father_name').val()) {
                            $('#father_name')
                                .val(data.fatherName.toUpperCase().trim())
                                .prop('readonly', true); // 🔒 lock field
                        }

                        // Autofill DOB
                        if (data.dateOfBirth) {

                            const parts = data.dateOfBirth.split('/'); // DD/MM/YYYY
                            const formattedDOB = `${parts[2]}-${parts[1]}-${parts[0]}`;

                            $('#date_of_birth')
                                .val(formattedDOB)
                                .prop('readonly', true); // 🔒 lock ONLY if extracted

                        } else {
                            // ✅ If NOT extracted → allow manual entry
                            $('#date_of_birth')
                                .val('')
                                .prop('readonly', false);
                        }
                        // Map verification data to form fields
                        if (data.verificationData) {
                            const vData = data.verificationData;

                            // 1. PAN Validity (pan_verification_status)
                            if (vData.is_valid === true || vData.pan_status === 'Valid') {
                                $('#pan_verification_status').val('Valid');
                                // 🔒 LOCK PAN FIELD
                                $('#pan_no').prop('readonly', true);
                            } else {
                                $('#pan_verification_status').val('Invalid');
                                // allow editing
                                $('#pan_no').prop('readonly', false);
                            }

                            // 2. PAN Status (pan_status_2)
                            if (vData.individual_tax_compliance_status) {
                                $('#pan_status_2').val(vData.individual_tax_compliance_status);
                            } else if (vData.pan_status) {
                                $('#pan_status_2').val(vData.pan_status);
                            } else {
                                $('#pan_status_2').val('Unknown');
                            }

                            // 3. PAN-Aadhaar Link Status (pan_aadhaar_link_status)
                            if (vData.aadhaar_seeding_status) {
                                $('#pan_aadhaar_link_status').val(vData.aadhaar_seeding_status);
                            } else if (vData.aadhaar_seeding_status_code === 'R') {
                                $('#pan_aadhaar_link_status').val('Unsuccessful');
                            } else if (vData.aadhaar_seeding_status_code === 'S') {
                                $('#pan_aadhaar_link_status').val('Successful');
                            } else {
                                $('#pan_aadhaar_link_status').val('Unknown');
                            }

                            // Optional: Auto-fill candidate name if available and empty
                            if (vData.name && !$('#candidate_name').val()) {
                                $('#candidate_name').val(vData.name);
                                $('#candidate_name').prop('readonly', true);
                            }

                            showToast('PAN extracted and verified successfully!', 'success');
                        } else {
                            // If no verification data, set default values
                            $('#pan_verification_status').val('Pending');
                            $('#pan_status_2').val('Unverified');
                            $('#pan_aadhaar_link_status').val('Unknown');
                            showToast('PAN extracted but verification data incomplete', 'warning');
                        }

                        // Store filename for submission
                        $('#pan_filename').val(data.filename);
                        $('#pan_filepath').val(data.filePath);

                    } else {
                        showToast('Unexpected response format', 'warning');
                        resetPanFields();
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to extract PAN. Please enter manually.';

                    // Handle specific error cases
                    if (xhr.status === 409) {
                        errorMessage = 'This file was already processed. Please rename the file.';
                        // Try to recover data from response
                        if (xhr.responseJSON?.data?.panNumber) {
                            $('#pan_no').val(xhr.responseJSON.data.panNumber);
                            $('#pan_filename').val(xhr.responseJSON.data.filename);
                            $('#pan_filepath').val(xhr.responseJSON.data.filePath);
                            showToast('PAN number recovered', 'info');
                            resetPanFields('Recovered');
                            $('#pan_no').prop('readonly', false);
                            return;
                        }
                    } else if (xhr.responseJSON?.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    resetPanFields('Failed');
                    showToast(errorMessage, 'error');
                },
                complete: function() {
                    if (!$('#pan_no').val()) {
                        $('#pan_no').prop('readonly', false);
                    }
                }
            });
        }

        function verifyPANManually(panNo) {
            $('#verify-pan-btn').html('<i class="ri-loader-4-line ri-spin"></i>').prop('disabled', true);

            // Show extracting status
            $('#pan_verification_status').val('Verifying...');
            $('#pan_status_2').val('Verifying...');
            $('#pan_aadhaar_link_status').val('Verifying...');

            $.ajax({
                url: '{{ route("verify.pan.manual") }}',
                type: 'POST',
                data: {
                    pan_number: panNo,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;

                        // Map verification data similar to extraction
                        if (data.verificationData) {
                            const vData = data.verificationData;

                            $('#pan_verification_status').val(vData.is_valid === true ? 'Valid' : 'Invalid');
                            $('#pan_status_2').val(vData.individual_tax_compliance_status || vData.pan_status || 'Unknown');
                            $('#pan_aadhaar_link_status').val(vData.aadhaar_seeding_status || 'Unknown');

                            showToast('PAN verified successfully!', 'success');
                        } else {
                            $('#pan_verification_status').val('Valid');
                            $('#pan_status_2').val('Verified');
                            $('#pan_aadhaar_link_status').val('Unknown');
                            showToast('PAN verified successfully!', 'success');
                        }
                    } else {
                        $('#pan_verification_status').val('Invalid');
                        $('#pan_status_2').val('Unverified');
                        $('#pan_aadhaar_link_status').val('Unknown');
                        showToast('Invalid PAN number', 'error');
                    }
                },
                error: function() {
                    $('#pan_verification_status').val('Verification Failed');
                    $('#pan_status_2').val('Failed');
                    $('#pan_aadhaar_link_status').val('Failed');
                    showToast('Verification failed', 'error');
                },
                complete: function() {
                    $('#verify-pan-btn').html('<i class="ri-search-line"></i>').prop('disabled', false);
                }
            });
        }

        // Helper function to reset PAN fields
        function resetPanFields(status = 'Failed') {
            $('#pan_verification_status').val(status);
            $('#pan_status_2').val(status);
            $('#pan_aadhaar_link_status').val(status);
        }

        // ==================== BANK VERIFICATION ====================

        // Auto-extract from uploaded bank document
        $('#bank_document').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            extractBankFromFile(file);
        });

        // Verify account button - only enabled when both fields have values
        function updateVerifyAccountButton() {
            const accountNo = $('#bank_account_no').val();
            const ifscCode = $('#bank_ifsc').val();

            if (
                accountNo &&
                ifscCode &&
                accountNo !== 'Extracting...' &&
                ifscCode.length === 11
            ) {
                $('#verify-account-btn').prop('disabled', false);
            } else {
                $('#verify-account-btn').prop('disabled', true);
            }
        }

        // Monitor both fields for changes
        $('#bank_account_no, #bank_ifsc').on('input change', function() {
            updateVerifyAccountButton();
        });

        // Manual account verification
        $('#verify-account-btn').on('click', function() {
            const accountNo = $('#bank_account_no').val();
            const ifscCode = $('#bank_ifsc').val();

            if (!accountNo) {
                showToast('Please enter account number', 'warning');
                return;
            }
            if (!ifscCode || ifscCode.length < 11) {
                showToast('Please enter a valid 11-digit IFSC code', 'warning');
                return;
            }
            verifyBankAccount(accountNo, ifscCode);
        });

        function extractBankFromFile(file) {
            // Disable fields during extraction
            $('#bank_account_no, #bank_ifsc, #account_holder_name, #bank_name').prop('readonly', true).val('');
            $('#bank_verification_status').val('Extracting...');
            $('#bank_branch_address').val('');
            $('#verify-account-btn').prop('disabled', true);

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

                        // Account Number & IFSC
                        if (data.accountNumber) {
                            $('#bank_account_no').val(data.accountNumber);
                        }
                        if (data.ifscCode) {
                            $('#bank_ifsc').val(data.ifscCode);
                        }

                        // Holder Name
                        if (vData.beneficiary_name) {
                            $('#account_holder_name').val(vData.beneficiary_name);
                        }

                        // Bank Name
                        if (vData.ifsc_details?.name) {
                            $('#bank_name').val(vData.ifsc_details.name);
                        }

                        // Branch Address
                        if (vData.ifsc_details) {
                            const branch = vData.ifsc_details.branch || '';
                            const district = vData.ifsc_details.district || '';
                            const state = vData.ifsc_details.state || '';
                            $('#bank_branch_address').val(`${branch}, ${district}, ${state}`.replace(/^, |, $/g, ''));
                        }

                        // Status
                        if (data.isVerified || vData.verification_status === 'VERIFIED') {
                            $('#bank_verification_status').val('Verified');
                            // 🔒 LOCK BANK FIELDS
                            $('#bank_account_no').prop('readonly', true);
                            $('#bank_ifsc').prop('readonly', true);
                            $('#account_holder_name').prop('readonly', true);
                            showToast('Bank details extracted & verified!', 'success');
                        } else {
                            $('#bank_verification_status').val('Partially Verified');
                            $('#bank_account_no').prop('readonly', false);
                            $('#bank_ifsc').prop('readonly', false);
                            $('#account_holder_name').prop('readonly', false);
                            showToast('Bank details extracted. Click verify to confirm.', 'info');
                        }

                        $('#bank_filename').val(data.filename);
                        $('#bank_filepath').val(data.filePath);
                    } else {
                        $('#bank_verification_status').val('Failed');
                        showToast(response.message || 'Bank extraction failed', 'warning');
                    }
                },
                error: function(xhr) {
                    $('#bank_account_no, #bank_ifsc, #bank_name, #account_holder_name').val('');
                    $('#bank_verification_status').val('Failed');
                    showToast('Failed to extract bank details', 'error');
                },
                complete: function() {
                    // Re-enable fields
                    $('#bank_account_no, #bank_ifsc, #account_holder_name, #bank_name').prop('readonly', false);
                    // Update verify button state
                    updateVerifyAccountButton();
                }
            });
        }

        function verifyBankAccount(accountNo, ifscCode) {
            $('#verify-account-btn')
                .html('<i class="ri-loader-4-line ri-spin"></i>')
                .prop('disabled', true);

            $('#bank_verification_status').val('Verifying...');

            $.ajax({
                url: '{{ route("verify.bank.account") }}',
                type: 'POST',
                data: {
                    account_number: accountNo,
                    ifsc_code: ifscCode,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;

                        // Fill account holder name
                        if (data.account_holder_name) {
                            $('#account_holder_name').val(data.account_holder_name);
                        }

                        // Fill bank name
                        if (data.bank_name) {
                            $('#bank_name').val(data.bank_name);
                        }

                        // Fill branch address (if available)
                        if (data.branch_address) {
                            $('#bank_branch_address').val(data.branch_address);
                        }

                        // Set status
                        $('#bank_verification_status').val('Verified');
                        showToast('Bank verified successfully!', 'success');
                    } else {
                        $('#bank_verification_status').val('Failed');
                        showToast('Verification failed. Check details.', 'error');
                    }
                },
                error: function(xhr) {
                    $('#bank_verification_status').val('Failed');
                    showToast('Verification error', 'error');
                },
                complete: function() {
                    $('#verify-account-btn')
                        .html('<i class="ri-search-line"></i>')
                        .prop('disabled', false);
                }
            });
        }

        // ==================== DRIVING LICENSE VERIFICATION ====================

        // Auto-extract from uploaded DL
        $('#driving_licence').on('change', function() {
            const file = this.files[0];
            if (!file) return;
            extractDLFromFile(file);
        });

        // Manual DL verification button
        $('#verify-dl-btn').on('click', function() {
            const dlNo = $('#driving_licence_no').val();
            if (!dlNo) {
                showToast('Please enter DL number', 'warning');
                return;
            }
            verifyDLManually(dlNo);
        });

        function extractDLFromFile(file) {
            $('#driving_licence_no').prop('readonly', true).val('');
            $('#dl_valid_from').val('');
            $('#dl_valid_to').val('');
            $('#dl_verification_status').val('Extracting...');

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

                        // Set status based on dates
                        if (data.validTo) {
                            const today = new Date();
                            const validTo = new Date(data.validTo);
                            $('#dl_verification_status').val(validTo > today ? 'Valid' : 'Expired');
                        } else {
                            $('#dl_verification_status').val('Pending');
                        }

                        $('#driving_filename').val(data.filename);
                        $('#driving_filepath').val(data.filePath);

                        showToast('DL extracted!', 'info');
                    } else {
                        $('#dl_verification_status').val('Failed');
                        showToast('Failed to extract DL', 'warning');
                    }
                },
                error: function() {
                    $('#driving_licence_no').val('');
                    $('#dl_verification_status').val('Failed');
                    showToast('Failed to extract DL', 'error');
                },
                complete: function() {
                    $('#driving_licence_no').prop('readonly', false);
                }
            });
        }

        function verifyDLManually(dlNo) {
            $('#verify-dl-btn').html('<i class="ri-loader-4-line ri-spin"></i>').prop('disabled', true);

            $.ajax({
                url: '{{ route("verify.dl.manual") }}',
                type: 'POST',
                data: {
                    dl_number: dlNo,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        const data = response.data;

                        data.valid_from && $('#dl_valid_from').val(data.valid_from);
                        data.valid_to && $('#dl_valid_to').val(data.valid_to);

                        // Check validity
                        if (data.valid_to) {
                            const today = new Date();
                            const validTo = new Date(data.valid_to);
                            $('#dl_verification_status').val(validTo > today ? 'Valid' : 'Expired');
                        } else {
                            $('#dl_verification_status').val('Valid');
                        }

                        showToast('DL verified!', 'success');
                    } else {
                        $('#dl_verification_status').val('Invalid');
                        showToast('Invalid DL number', 'error');
                    }
                },
                error: function() {
                    $('#dl_verification_status').val('Failed');
                    showToast('Verification failed', 'error');
                },
                complete: function() {
                    $('#verify-dl-btn').html('<i class="ri-search-line"></i>').prop('disabled', false);
                }
            });
        }

        // ==================== PAN-AADHAAR LINK CHECK ====================

        function checkPanAadhaarLink(panNumber, aadhaarNumber) {
            $.ajax({
                url: '{{ route("check.pan.aadhaar.link") }}',
                type: 'POST',
                data: {
                    pan_number: panNumber,
                    aadhaar_number: aadhaarNumber,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        $('#pan_aadhaar_link_status').val(response.data.linkStatus);
                    } else {
                        $('#pan_aadhaar_link_status').val('Unknown');
                    }
                },
                error: function() {
                    $('#pan_aadhaar_link_status').val('Check Failed');
                }
            });
        }

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

        // Date of Birth validation
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

        // Mobile number validation
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

        // PIN code validation
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
            const maxSize = 5 * 1024 * 1024; // 5MB

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

            // Clear all previous errors
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('').hide();

            // Check if any extraction is in progress
            if (
                $('#aadhaar_status_text').text() === 'Extracting...' ||
                $('#pan_verification_status').val() === 'Extracting...' ||
                $('#bank_verification_status').val() === 'Extracting...' ||
                $('#dl_verification_status').val() === 'Extracting...'
            ) {
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

                        // Clear old errors
                        form.find('.is-invalid').removeClass('is-invalid');
                        form.find('.invalid-feedback').text('').hide();

                        let firstInvalid = null;

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
                                } else {
                                    input.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                                }

                                if (!firstInvalid) firstInvalid = input;
                            }
                        });

                        if (firstInvalid) {
                            $('html, body').animate({
                                scrollTop: firstInvalid.offset().top - 150
                            }, 400);
                        }

                        showToast('Please correct the highlighted fields', 'error');
                    } else {
                        showToast('Something went wrong. Please try again.', 'error');
                    }
                }
            });
        });

        $('#state_work_location').on('change', function() {

            const stateId = $(this).val();

            $('#contract_district_id').html('<option value="">Loading...</option>');
            $('#contract_work_location_id').html('<option value="">Select City</option>');

            if (!stateId) return;

            $.get("{{ url('/get-districts-by-state') }}", {
                state_id: stateId
            }, function(data) {

                let options = '<option value="">Select District</option>';

                data.forEach(function(district) {
                    options += `<option value="${district.id}">${district.district_name}</option>`;
                });

                $('#contract_district_id').html(options);
            });
        });

        $('#contract_district_id').on('change', function() {

            const districtId = $(this).val();
            const selectedText = $("#contract_district_id option:selected").text();

            $('#contract_district_name').val(selectedText);

            $('#contract_work_location_id').html('<option value="">Loading...</option>');

            if (!districtId) return;

            $.get("{{ url('/get-cities-by-district') }}", {
                district_id: districtId
            }, function(data) {

                let options = '<option value="">Select City</option>';

                data.forEach(function(city) {
                    options += `<option value="${city.id}">${city.city_village_name}</option>`;
                });

                $('#contract_work_location_id').html(options);
            });
        });

        $('#contract_work_location_id').on('change', function() {

            const selectedText = $("#contract_work_location_id option:selected").text();
            $('#contract_work_location_name').val(selectedText);

        });

    });
</script>
@endpush