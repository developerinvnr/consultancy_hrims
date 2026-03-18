@extends('layouts.guest')

@section('content')
<div class="container-fluid">
	<!-- Start page title -->
	<div class="row g-1">
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

	<div class="row g-1">
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

						<!-- ==================== SECTION 2: Personal Information ==================== -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">

									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 2: Personal Information</h6>
									</div>

									<div class="card-body">

										<!-- ===== Row 1 ===== -->
										<div class="row">

											<!-- Candidate Name -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Candidate Name<span class="text-danger">*</span></label>
												<input type="text" class="form-control form-control-sm"
													id="candidate_name" name="candidate_name">
												<div class="invalid-feedback"></div>
											</div>

											<!-- Father Name -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Father's Name<span class="text-danger">*</span></label>
												<input type="text" class="form-control form-control-sm"
													id="father_name" name="father_name">
												<div class="invalid-feedback"></div>
											</div>

											<!-- DOB -->
											<div class="col-md-3 mb-3">
												<label class="form-label">DOB<span class="text-danger">*</span></label>
												<input type="date" class="form-control form-control-sm"
													id="date_of_birth" name="date_of_birth">
												<div class="invalid-feedback">Age must be 18+</div>
											</div>

											<!-- Gender -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Gender<span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="gender" name="gender">
													<option value="">Select</option>
													<option value="Male">Male</option>
													<option value="Female">Female</option>
													<option value="Other">Other</option>
												</select>
											</div>

										</div>

										<!-- ===== Row 2 ===== -->
										<div class="row">



											<!-- Mobile -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Mobile No.<span class="text-danger">*</span></label>
												<input type="text" class="form-control form-control-sm"
													id="mobile_no" name="mobile_no" maxlength="10">
												<div class="invalid-feedback">10-digit number</div>
											</div>

											<!-- Email -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Email<span class="text-danger">*</span></label>
												<input type="email" class="form-control form-control-sm"
													id="candidate_email" name="candidate_email">
												<div class="invalid-feedback"></div>
											</div>

											<!-- Alternate Email -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Alt Email</label>
												<input type="email" class="form-control form-control-sm"
													id="alternate_email" name="alternate_email">
											</div>

											<div class="col-md-3 mb-3">
												<label for="highest_qualification" class="form-label">Highest Qualification <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm select2" id="highest_qualification" name="highest_qualification">
													<option value="">Select Qualification</option>
													@foreach($educations as $education)
													<option value="{{ $education->EducationId }}" {{ old('highest_qualification') == $education->EducationId ? 'selected' : '' }}>
														{{ $education->EducationName }} ({{ $education->EducationCode }})
													</option>
													@endforeach
												</select>
												<div class="invalid-feedback"></div>
											</div>

										</div>

										<div class="row">

											<div class="col-md-2 mb-3">
												<label for="college_name" class="form-label">College/University</label>
												<input type="text" class="form-control form-select-sm"
													id="college_name" name="college_name">
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="state_residence" class="form-label">State <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="state_residence" name="state_residence">
													<option value="">Select State</option>
													@foreach($states as $state)
													<option value="{{ $state->id }}">{{ $state->state_name }}</option>
													@endforeach
												</select>
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-2 mb-3">
												<label for="city" class="form-label">City <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm select2" id="city" name="city">
													<option value="">Select City</option>
													<!-- Cities will be loaded dynamically -->
												</select>
												<div class="invalid-feedback"></div>
											</div>
											<!-- Address -->
											<div class="col-md-3 mb-3">
												<label class="form-label">Address Line 1<span class="text-danger">*</span></label>
												<textarea class="form-control form-control-sm"
													id="address_line_1" name="address_line_1" rows="1"></textarea>
											</div>
											<div class="col-md-2 mb-3">
												<label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="pin_code" name="pin_code" maxlength="6">
												<div class="invalid-feedback"></div>
											</div>


										</div>

									</div>
								</div>
							</div>
						</div>


						<!-- ==================== SECTION 3: WORK INFORMATION ==================== -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 3: Work Information</h6>
									</div>
									<div class="card-body">
										<div class="row g-1">
											<div class="col-md-3 mb-3">
												<label for="function_id" class="form-label">Function <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="function_id_display" disabled>
													<option value="">Select Function</option>
													@foreach($functions as $function)
													<option value="{{ $function->id }}"
														{{ $autoFillData['function_id'] == $function->id ? 'selected' : '' }}>
														{{ $function->function_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="function_id" value="{{ $autoFillData['function_id'] ?? '' }}">
											</div>
											<div class="col-md-2 mb-3">
												<label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="department_id_display" disabled>
													<option value="">Select Department</option>
													@foreach($departments as $department)
													<option value="{{ $department->id }}"
														{{ $autoFillData['department_id'] == $department->id ? 'selected' : '' }}>
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
														{{ $autoFillData['vertical_id'] == $vertical->id ? 'selected' : '' }}>
														{{ $vertical->vertical_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="vertical_id" value="{{ $autoFillData['vertical_id'] ?? '' }}">
											</div>
											<div class="col-md-3 mb-3">
												<label for="state_work_location" class="form-label">State (Work) <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="state_work_location" name="state_work_location">
													<option value="">Select State</option>
													@foreach($states as $state)
													<option value="{{ $state->id }}">{{ $state->state_name }}</option>
													@endforeach
												</select>
												<div class="invalid-feedback"></div>
											</div>

											<input type="hidden" name="district" id="district_name">
											<div class="col-md-2 mb-3">
												<label for="district_id" class="form-label">
													District <span class="text-danger">*</span>
												</label>
												<select class="form-select form-select-sm"
													id="district_id"
													name="district_id">
													<option value="">Select District</option>
												</select>
												<div class="invalid-feedback"></div>
											</div>

										</div>

										<div class="row g-1">


											<div class="col-md-2 mb-3">
												<label for="work_location_id" class="form-label">
													Work Location/HQ <span class="text-danger">*</span>
												</label>
												<select class="form-select form-select-sm"
													id="work_location_id"
													name="work_location_id">
													<option value="">Select City</option>
												</select>
												<div class="invalid-feedback"></div>
											</div>

											<input type="hidden" name="work_location_hq" id="work_location_name">
											<div class="col-md-2 mb-3">
												<label for="sub_department_id" class="form-label">Sub-department <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="sub_department_id_display" disabled>
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
											<div class="col-md-2 mb-3">
												<label for="business_unit" class="form-label">Business Unit <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="business_unit_display" disabled>
													<option value="">Select Business Unit</option>
													@foreach($businessUnits as $unit)
													<option value="{{ $unit->id }}"
														{{ $autoFillData['business_unit_id'] == $unit->id ? 'selected' : '' }}>
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
														{{ $autoFillData['zone_id'] == $zone->id ? 'selected' : '' }}>
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
														{{ $autoFillData['region_id'] == $region->id ? 'selected' : '' }}>
														{{ $region->region_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="region" value="{{ $autoFillData['region_id'] ?? '' }}">
												<input type="hidden" name="region_name" value="{{ $autoFillData['region_name'] ?? '' }}">
											</div>
											<div class="col-md-2 mb-3">
												<label for="territory" class="form-label">Territory <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="territory_display" disabled>
													<option value="">Select Territory</option>
													@foreach($territories as $territory)
													<option value="{{ $territory->id }}"
														{{ $autoFillData['territory_id'] == $territory->id ? 'selected' : '' }}>
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

						<!-- ==================== SECTION 4: EMPLOYMENT DETAILS ==================== -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 4: Employment Details</h6>
									</div>
									<div class="card-body">
										<div class="row g-1">
											<div class="col-md-3 mb-3">
												<label class="form-label">Reporting To <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													value="{{ $autoFillData['reporting_to'] }}" readonly>
												<input type="hidden" name="reporting_to" value="{{ $autoFillData['reporting_to'] }}">
												<input type="hidden" name="reporting_manager_employee_id" value="{{ $autoFillData['reporting_manager_employee_id'] }}">
											</div>
											<div class="col-md-2 mb-3">
												<label for="contract_start_date" class="form-label">Contract Start<span class="text-danger">*</span></label>
												<input type="date" class="form-control form-select-sm"
													id="contract_start_date" name="contract_start_date">
											</div>
											<div class="col-md-2 mb-3">
												<label for="contract_duration" class="form-label">Duration<span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="contract_duration" name="contract_duration">
													<option value="">Select</option>
													<option value="15">15 Days</option>
													<option value="30">1 Month</option>
													<option value="45">45 Days</option>
													<option value="60">2 Months</option>
													<option value="90">3 Months</option>
													<option value="120">4 Months</option>
												</select>
											</div>
											<div class="col-md-2 mb-3">
												<label for="contract_end_date" class="form-label">Contract End<span class="text-danger">*</span></label>
												<input type="date" class="form-control form-select-sm"
													id="contract_end_date" name="contract_end_date" readonly>
											</div>
											<div class="col-md-3 mb-3">
												<label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
												<div class="input-group input-group-sm">
													<span class="input-group-text">₹</span>
													<input type="number" class="form-control form-control-sm"
														id="remuneration_per_month" name="remuneration_per_month"
														step="0.01" min="0">
												</div>
											</div>
										</div>

										<div class="row g-1">
											<div class="col-12 mb-3">
												<label for="reporting_manager_address" class="form-label">Address for Agreement Dispatch <span class="text-danger">*</span></label>
												<textarea class="form-control form-select-sm"
													id="reporting_manager_address" name="reporting_manager_address"
													rows="3"></textarea>
												<small class="text-muted">Include PIN code and phone number</small>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Form Actions -->
						<div class="row g-1">
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

	select[readonly] {
		background-color: #f8f9fa;
		cursor: not-allowed;
		opacity: 0.8;
	}

	select[readonly] option {
		background-color: white;
	}

	select[readonly]:focus {
		border-color: #ced4da;
		box-shadow: none;
	}

	.form-control[readonly] {
		background-color: #f8f9fa;
		opacity: 0.8;
	}

	/* Make Select2 same height as Bootstrap small inputs */
	.select2-container--bootstrap-5 .select2-selection {
		height: calc(1.5em + 0.5rem + 2px) !important;
		padding: 0.25rem 0.5rem !important;
		font-size: 0.875rem !important;
		display: flex;
		align-items: center;
	}

	/* Fix arrow alignment */
	.select2-container--bootstrap-5 .select2-selection__arrow {
		height: 100% !important;
	}

	/* Fix text alignment */
	.select2-container--bootstrap-5 .select2-selection__rendered {
		line-height: normal !important;
		padding-left: 0 !important;
	}

	/* Fix multiple select (if used) */
	.select2-container--bootstrap-5 .select2-selection--multiple {
		min-height: calc(1.5em + 0.5rem + 2px) !important;
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

						// ✅ ADD THIS
						$('#aadhaar_filename').val(data.filename);
						$('#aadhaar_filepath').val(data.filePath);

						$('#aadhaar_status_text').text('Extracted');

					} else {
						$('#aadhaar_status_text').text('Failed enter manually');
					}
				},
				error: function() {
					$('#aadhaar_status_text').text('Failed - enter manually');
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
						//$('#pan_no').val(data.panNumber);

						$('#pan_no').val(data.panNumber).prop('readonly', true);

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
								.prop('readonly', true); // 🔒 lock only when auto-filled

						} else {

							// ✅ IMPORTANT: allow manual selection
							$('#date_of_birth')
								.prop('readonly', false);
						}

						// Map verification data to form fields
						if (data.verificationData) {
							const vData = data.verificationData;

							// 1. PAN Validity (pan_verification_status) - Use is_valid or pan_status
							if (vData.is_valid === true || vData.pan_status === 'Valid') {
								$('#pan_verification_status').val('Valid');
							} else {
								$('#pan_verification_status').val('Invalid');
							}

							// 2. PAN Status (pan_status_2) - Use individual_tax_compliance_status
							if (vData.individual_tax_compliance_status) {
								$('#pan_status_2').val(vData.individual_tax_compliance_status);
							} else if (vData.pan_status) {
								$('#pan_status_2').val(vData.pan_status);
							} else {
								$('#pan_status_2').val('Unknown');
							}

							// 3. PAN-Aadhaar Link Status (pan_aadhaar_link_status) - Use aadhaar_seeding_status
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
							$('#candidate_name').prop('readonly', false);
							showToast('PAN extracted but verification data incomplete', 'warning');
						}

						// Store filename for submission
						$('#pan_filename').val(data.filename);
						$('#pan_filepath').val(data.filePath);

						// Check PAN-Aadhaar link if Aadhaar exists (for cross-reference)
						if ($('#aadhaar_no').val()) {
							// Optional: You can add logic here to compare if names match, etc.
						}

					} else {
						showToast('Unexpected response format', 'warning');
						$('#father_name').prop('readonly', false);
						$('#date_of_birth').prop('readonly', false);
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
			$('#bank_account_no, #bank_ifsc').prop('readonly', true).val('');
			$('#bank_verification_status').val('Extracting...');

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

						if (data.isVerified) {
							$('#verify-account-btn').prop('disabled', true);
						}
						// ✅ Account Number & IFSC
						if (data.accountNumber) {
							$('#bank_account_no').val(data.accountNumber);
						}
						if (data.ifscCode) {
							$('#bank_ifsc').val(data.ifscCode);
						}

						// ✅ Holder Name (FIXED)
						if (vData.beneficiary_name) {
							$('#account_holder_name').val(vData.beneficiary_name);
						}

						// ✅ Bank Name (FIXED)
						if (vData.ifsc_details?.name) {
							$('#bank_name').val(vData.ifsc_details.name);
						}

						// ✅ Branch Address (FIXED)
						if (vData.ifsc_details) {
							const branch = vData.ifsc_details.branch || '';
							const district = vData.ifsc_details.district || '';
							const state = vData.ifsc_details.state || '';

							$('#bank_branch_address').val(`${branch}, ${district}, ${state}`);
						}

						// ✅ Status
						if (data.isVerified || vData.verification_status === 'VERIFIED') {
							$('#bank_verification_status').val('Verified');
							showToast('Bank details extracted & verified!', 'success');
						} else {
							$('#bank_verification_status').val('Partially Verified');
						}

						$('#bank_filename').val(data.filename);
						$('#bank_filepath').val(data.filePath);
					}
				},
				error: function(xhr) {
					$('#bank_account_no, #bank_ifsc, #bank_name').val('');
					$('#bank_verification_status').val('Failed');
					showToast('Failed to extract bank details', 'error');
				},
				complete: function() {
					// Re-enable fields
					$('#bank_account_no').prop('readonly', false);
					$('#bank_ifsc').prop('readonly', false);
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

						// ✅ Fill account holder name
						if (data.account_holder_name) {
							$('#account_holder_name').val(data.account_holder_name);
						}

						// ✅ Fill bank name
						if (data.bank_name) {
							$('#bank_name').val(data.bank_name);
						}

						// ✅ Fill branch address (if available)
						if (data.branch_address) {
							$('#bank_branch_address').val(data.branch_address);
						}

						// ✅ Set status

						if (data.branch_address) {
							$('#bank_verification_status').val(data.verification_status);
						}

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


		// IFSC Verification (separate button for fetching bank details)
		$('#verify-ifsc-btn').on('click', function() {
			const ifscCode = $('#bank_ifsc').val();

			if (!ifscCode || ifscCode.length < 11) {
				showToast('Please enter a valid 11-digit IFSC code', 'warning');
				return;
			}
		});

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
						}

						$('#dl_filename').val(data.filename);
						$('#dl_filepath').val(data.filePath);

						showToast('DL extracted!', 'info');
					}
				},
				error: function(xhr) {
					$('#driving_licence_no').val('');
					$('#dl_verification_status').val('Failed');
					showToast('Failed to extract DL', 'error');
				},
				complete: function() {
					//$('#driving_licence_no, #dl_valid_from, #dl_valid_to').prop('disabled', false);
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
			const maxSize = 5 * 1024 * 1024;

			if (file && file.size > maxSize) {
				$(this).addClass('is-invalid');
				$(this).siblings('.invalid-feedback').text('File must be < 2MB').show();
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
			// ✅ CLEAN BEFORE SUBMIT
			const url = form.attr('action');
			const formData = new FormData(form[0]);

			// Clear all previous errors
			form.find('.is-invalid').removeClass('is-invalid');
			form.find('.invalid-feedback').text('').hide();

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

			$('#district_id').html('<option value="">Loading...</option>');
			$('#work_location_id').html('<option value="">Select City</option>');

			if (!stateId) return;

			$.get("{{ url('/get-districts-by-state') }}", {
				state_id: stateId
			}, function(data) {

				let options = '<option value="">Select District</option>';

				data.forEach(function(district) {
					options += `<option value="${district.id}">${district.district_name}</option>`;
				});

				$('#district_id').html(options);
			});
		});

		$('#district_id').on('change', function() {

			const districtId = $(this).val();

			const selectedText = $("#district_id option:selected").text();
			$('#district_name').val(selectedText);

			$('#work_location_id').html('<option value="">Loading...</option>');

			if (!districtId) return;

			$.get("{{ url('/get-cities-by-district') }}", {
				district_id: districtId
			}, function(data) {

				let options = '<option value="">Select City</option>';

				data.forEach(function(city) {
					options += `<option value="${city.id}">${city.city_village_name}</option>`;
				});

				$('#work_location_id').html(options);
			});
		});

		$('#work_location_id').on('change', function() {

			const selectedText = $("#work_location_id option:selected").text();
			$('#work_location_name').val(selectedText);

		});
	});
</script>
@endpush