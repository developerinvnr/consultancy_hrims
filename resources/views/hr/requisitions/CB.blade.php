@extends('layouts.guest')

@section('content')
<div class="container-fluid">
	<!-- Start page title -->
	<div class="row">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
				<h4 class="mb-sm-0">HR Create CB Requisition</h4>
				<div class="page-title-right">
					<ol class="breadcrumb m-0">
						<li class="breadcrumb-item"><a href="{{ route('hr_requisitions.index') }}">HR Requisitions</a></li>
						<li class="breadcrumb-item active">Create CB</li>
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
							<h5 class="card-title mb-0">Counter Boys (CB) Requisition Form (HR Create)</h5>
						</div>
						<div class="col-md-6">
							<div class="d-flex justify-content-end">
								<a href="{{ route('hr_requisitions.index') }}" class="btn btn-secondary btn-sm">
									<i class="ri-arrow-left-line me-1"></i> Back
								</a>
							</div>
						</div>
					</div>
				</div>

				<div class="card-body">
					<form id="requisition-form" method="POST" action="{{ route('hr_requisitions.direct.store') }}" enctype="multipart/form-data">
						@csrf
						<input type="hidden" name="requisition_type" value="CB">
						<input type="hidden" name="pan_filename" id="pan_filename">
						<input type="hidden" name="pan_filepath" id="pan_filepath">
						<input type="hidden" name="bank_filename" id="bank_filename">
						<input type="hidden" name="bank_filepath" id="bank_filepath">
						<input type="hidden" name="aadhaar_filename" id="aadhaar_filename">
						<input type="hidden" name="aadhaar_filepath" id="aadhaar_filepath">

						<!-- Section 1: Personal Information -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 1: Personal Information</h6>
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
													id="date_of_birth" name="date_of_birth"
													max="{{ now()->subYears(18)->subDay()->format('Y-m-d') }}"
													required>
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
												<label for="candidate_email" class="form-label">Email <span class="text-danger"></span></label>
												<input type="email" class="form-control form-select-sm"
													id="candidate_email" name="candidate_email">
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="alternate_email" class="form-label">Alternate Email</label>
												<input type="email" class="form-control form-select-sm"
													id="alternate_email" name="alternate_email">
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="highest_qualification" class="form-label">Highest Qualification <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm select2" id="highest_qualification" name="highest_qualification" required>
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

						<!-- Section 2: Work Information (All fields editable for HR) -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 2: Work Information</h6>
									</div>
									<div class="card-body">
										<div class="row">
											<!-- Function (Editable for HR) -->
											<div class="col-md-3 mb-3">
												<label for="function_id" class="form-label">Function <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="function_id" name="function_id" required>
													<option value="">Select Function</option>
													@foreach($functions as $function)
													<option value="{{ $function->id }}">
														{{ $function->function_name }}
													</option>
													@endforeach
												</select>
											</div>
											<!-- Department (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="department_id" name="department_id" required disabled>
													<option value="">Select Function First</option>
												</select>
												<div class="invalid-feedback">Please select a department</div>
											</div>
											<!-- Vertical (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="vertical_id" class="form-label">Vertical <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="vertical_id" name="vertical_id" required disabled>
													<option value="">Select Function First</option>
												</select>
												<div class="invalid-feedback">Please select a vertical</div>
											</div>
											<div class="col-md-2 mb-3">
												<label for="work_location_hq" class="form-label">Work Location/HQ<span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="work_location_hq" name="work_location_hq" required>
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="state_work_location" class="form-label">State (Work Location) <span class="text-danger">*</span></label>
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
												<input type="text" class="form-control form-select-sm"
													id="district" name="district" required>
												<div class="invalid-feedback"></div>
											</div>
											<!-- Sub-department (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="sub_department_id" class="form-label">Sub-department</label>
												<select class="form-select form-select-sm" id="sub_department_id" name="sub_department_id" disabled>
													<option value="">Select Department First</option>
												</select>
											</div>
											<!-- Business Unit (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="business_unit" class="form-label">Business Unit</label>
												<select class="form-select form-select-sm" id="business_unit" name="business_unit" disabled>
													<option value="">Select Vertical First</option>
												</select>
											</div>
											<!-- Zone (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="zone" class="form-label">Zone</label>
												<select class="form-select form-select-sm" id="zone" name="zone" disabled>
													<option value="">Select Business Unit First</option>
												</select>
											</div>
											<!-- Region (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="region" class="form-label">Region</label>
												<select class="form-select form-select-sm" id="region" name="region" disabled>
													<option value="">Select Zone First</option>
												</select>
											</div>
											<!-- Territory (Editable for HR) -->
											<div class="col-md-2 mb-3">
												<label for="territory" class="form-label">Territory</label>
												<select class="form-select form-select-sm" id="territory" name="territory" disabled>
													<option value="">Select Region First</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Section 3: Employment Details (Editable for HR) -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 3: Employment Details</h6>
									</div>
									<div class="card-body">
										<div class="row align-items-end">
										

											 <!-- Reporting Manager (will be populated based on department) -->
                                         <div class="col-md-3 mb-3">
                                                <label class="form-label">
                                                    Reporting To <span class="text-danger">*</span>
                                                </label>

                                                <select class="form-select form-select-sm"
                                                    id="reporting_manager_id"
                                                    name="reporting_manager_id"
                                                    required>
                                                    <option value="">Select Reporting Manager</option>
                                                </select>

                                                <!-- hidden name -->
                                                <input type="hidden"
                                                    id="reporting_manager_name"
                                                    name="reporting_manager_name">
                                            </div>


											<div class="col-md-2 mb-3">
												<label class="form-label">Contract Start Date <span class="text-danger">*</span></label>
												<input type="date" class="form-control form-select-sm"
													id="contract_start_date" name="contract_start_date" required>
											</div>

											<div class="col-md-2 mb-3">
												<label class="form-label">Contract Duration <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="contract_duration"
													name="contract_duration" required>
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
												<label class="form-label">Contract End Date <span class="text-danger">*</span></label>
												<input type="date" class="form-control form-select-sm"
													id="contract_end_date" name="contract_end_date" readonly required>
											</div>
											<div class="col-md-3 mb-3">
												<label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
												<div class="input-group input-group-sm">
													<span class="input-group-text">₹</span>
													<input type="number" class="form-control"
														id="remuneration_per_month" name="remuneration_per_month"
														step="0.01" min="0" required>
												</div>
											</div>
										</div>


										<div class="row">
											
											<div class="col-md-12 mb-3">
												<label for="reporting_manager_address" class="form-label">Address for Agreement Dispatch <span class="text-danger">*</span></label>
												<textarea class="form-control form-select-sm"
													id="reporting_manager_address" name="reporting_manager_address"
													rows="3" required></textarea>
												<small class="text-muted">Include PIN code and phone number</small>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Section 4: Document Uploads with Data Extraction -->
						{{--<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 4: Document Uploads with Data Extraction</h6>
									</div>
									<div class="card-body">
										<div class="row">
											<!-- Resume -->
											<div class="col-md-3 mb-3">
												<label for="resume" class="form-label">Resume <span class="text-danger">*</span></label>
												 <input type="file"
                                                    class="form-control form-select-sm"
                                                    id="resume"
                                                    name="resume"
                                                    accept=".jpg,.jpeg,.png,.pdf">
                                                <small class="text-muted">JPG, PNG, PDF (Optional, Max 5MB)</small>
												<div class="invalid-feedback"></div>
											</div>

											<!-- Driving License -->
											<div class="col-md-3 mb-3">
												<label for="driving_licence" class="form-label">Driving Licence <span class="text-danger">*</span></label>
												<input type="file" class="form-control form-select-sm"
													id="driving_licence" name="driving_licence" accept=".pdf,.jpg,.jpeg,.png" required>
												<small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
												<div class="invalid-feedback"></div>
											</div>

											<!-- PAN Card -->
											<div class="col-md-3 mb-3">
												<label for="pan_card" class="form-label">PAN Card <span class="text-danger">*</span></label>
												<input type="file" class="form-control form-select-sm"
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
														placeholder="Auto-fill from upload" required>
													<span class="input-group-text">
														<i class="ri-checkbox-circle-fill text-success d-none" id="pan-verified-icon"></i>
														<i class="ri-alert-fill text-warning d-none" id="pan-warning-icon"></i>
													</span>
												</div>
												<small class="text-muted" id="pan-status-text">Upload PAN to auto-extract</small>
												<div class="invalid-feedback">Valid PAN required</div>
											</div>
										</div>

										<!-- Second Row -->
										<div class="row">
											<!-- Aadhaar Card -->
											<div class="col-md-3 mb-3">
												<label for="aadhaar_card" class="form-label">Aadhaar Card <span class="text-danger">*</span></label>
												<input type="file" class="form-control form-select-sm"
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
														placeholder="Auto-fill from upload" required>
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
												<input type="file" class="form-control form-select-sm"
													id="bank_document" name="bank_document" accept=".pdf,.jpg,.jpeg,.png" required>
												<small class="text-muted">Passbook/Cancelled Cheque</small>
												<div class="invalid-feedback"></div>
											</div>

											<!-- Account Holder Name -->
											<div class="col-md-3 mb-3">
												<label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="account_holder_name" name="account_holder_name"
													placeholder="As per bank records" required>
												<div class="invalid-feedback">Account holder name required</div>
											</div>
										</div>

										<!-- Third Row -->
										<div class="row">
											<!-- Account Number -->
											<div class="col-md-3 mb-3">
												<label for="bank_account_no" class="form-label">Account Number <span class="text-danger">*</span></label>
												<div class="input-group input-group-sm">
													<input type="text" class="form-control"
														id="bank_account_no" name="bank_account_no" maxlength="50"
														placeholder="Auto-extract from document" required>
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
														placeholder="Auto-extract from document" required>
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
												<input type="text" class="form-control form-select-sm"
													id="bank_name" name="bank_name"
													placeholder="Auto-extract from document" required>
												<div class="invalid-feedback">Bank name required</div>
											</div>

											<div class="col-md-3 mb-3">
												<label for="other_document" class="form-label">Other Document (Optional)</label>
												<input type="file" class="form-control form-select-sm"
													id="other_document" name="other_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
												<small class="text-muted">Additional documents</small>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>--}}

						<!-- Form Actions -->
						<div class="row">
							<div class="col-12">
								<div class="d-flex justify-content-end gap-2">
									<button type="reset" class="btn btn-light btn-sm">Reset Form</button>
									<button type="submit" class="btn btn-success btn-sm">
										<i class="ri-check-double-line me-1"></i> Create & Approve
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
</style>

@section('script_section')
<script>
	$(document).ready(function() {
		// Initialize Select2
		$('.select2').select2({
			width: '100%',
			theme: 'bootstrap-5'
		});

		// ============ DATE OF BIRTH VALIDATION ============
		$('#date_of_birth').on('change', function() {
			const dob = new Date(this.value);
			const today = new Date();

			let age = today.getFullYear() - dob.getFullYear();
			const m = today.getMonth() - dob.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
				age--;
			}

			if (age < 18) {
				$(this)
					.addClass('is-invalid')
					.siblings('.invalid-feedback')
					.text(`Age must be 18 years or older. Current age: ${age}`)
					.show();
				this.value = '';
			} else {
				$(this)
					.removeClass('is-invalid')
					.siblings('.invalid-feedback')
					.text('')
					.hide();
			}
		});

		// ============ CASCADING DROPDOWN LOGIC ============

		// 1. When Function changes: Update Vertical and Department
		$('#function_id').on('change', function() {
			const functionId = $(this).val();

			// Reset dependent dropdowns
			resetDropdown('#vertical_id');
			resetDropdown('#department_id');
			resetDropdown('#sub_department_id');
			resetDropdown('#business_unit');
			resetDropdown('#zone');
			resetDropdown('#region');
			resetDropdown('#territory');

			if (functionId) {
				// Load Verticals for this function
				loadVerticals(functionId);

				// Load Departments for this function
				loadDepartments(functionId);
			}
		});

		// 2. When Vertical changes: Update Business Unit
		$('#vertical_id').on('change', function() {
			const verticalId = $(this).val();

			// Reset dependent dropdowns
			resetDropdown('#business_unit');
			resetDropdown('#zone');
			resetDropdown('#region');
			resetDropdown('#territory');

			if (verticalId) {
				loadBusinessUnits(verticalId);
			}
		});

		// 3. When Department changes: Update Sub-department
		$('#department_id').on('change', function() {
			const departmentId = $(this).val();

			// Reset sub-department dropdown
			resetDropdown('#sub_department_id');

			// Also trigger reporting manager load
			loadReportingManagers(departmentId);

			if (departmentId) {
				loadSubDepartments(departmentId);
			}
		});

		// 4. When Business Unit changes: Update Zone
		$('#business_unit').on('change', function() {
			const businessUnitId = $(this).val();

			// Reset dependent dropdowns
			resetDropdown('#zone');
			resetDropdown('#region');
			resetDropdown('#territory');

			if (businessUnitId) {
				loadZones(businessUnitId);
			}
		});

		// 5. When Zone changes: Update Region
		$('#zone').on('change', function() {
			const zoneId = $(this).val();

			// Reset dependent dropdowns
			resetDropdown('#region');
			resetDropdown('#territory');

			if (zoneId) {
				loadRegions(zoneId);
			}
		});

		// 6. When Region changes: Update Territory
		$('#region').on('change', function() {
			const regionId = $(this).val();

			// Reset dependent dropdown
			resetDropdown('#territory');

			if (regionId) {
				loadTerritories(regionId);
			}
		});

		// ============ HELPER FUNCTIONS ============

		function resetDropdown(selector) {
			$(selector).html('<option value="">Select</option>');
			$(selector).val('');
			$(selector).prop('disabled', true);
			$(selector).trigger('change');
		}

		function loadVerticals(functionId) {
			const dropdown = $('#vertical_id');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.vertical.by.function") }}',
				type: 'POST',
				data: {
					function_id: functionId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Vertical</option>');
					if (response.length > 0) {
						$.each(response, function(index, vertical) {
							dropdown.append(`<option value="${vertical.id}">${vertical.vertical_name}</option>`);
						});
						dropdown.prop('disabled', false);
					} else {
						dropdown.append('<option value="">No verticals found</option>');
						dropdown.prop('disabled', true);
					}
				},
				error: function() {
					dropdown.html('<option value="">Error loading verticals</option>');
					dropdown.prop('disabled', true);
				}
			});
		}

		function loadDepartments(functionId) {
			const dropdown = $('#department_id');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.department.by.function") }}',
				type: 'POST',
				data: {
					function_id: functionId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Department</option>');
					if (response.length > 0) {
						$.each(response, function(index, department) {
							dropdown.append(`<option value="${department.id}">${department.department_name}</option>`);
						});
					} else {
						dropdown.append('<option value="">No departments found</option>');
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading departments</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		function loadSubDepartments(departmentId) {
			const dropdown = $('#sub_department_id');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.subdepartment.by.department") }}',
				type: 'POST',
				data: {
					department_id: departmentId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Sub-department</option>');
					if (response.length > 0) {
						$.each(response, function(index, subDept) {
							dropdown.append(`<option value="${subDept.id}">${subDept.sub_department_name}</option>`);
						});
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading sub-departments</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		function loadBusinessUnits(verticalId) {
			const dropdown = $('#business_unit');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.businessunit.by.vertical") }}',
				type: 'POST',
				data: {
					vertical_id: verticalId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Business Unit</option>');
					if (response.length > 0) {
						$.each(response, function(index, bu) {
							dropdown.append(`<option value="${bu.id}">${bu.business_unit_name}</option>`);
						});
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading business units</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		function loadZones(businessUnitId) {
			const dropdown = $('#zone');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.zone.by.bu") }}',
				type: 'POST',
				data: {
					business_unit_id: businessUnitId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Zone</option>');
					if (response.length > 0) {
						$.each(response, function(index, zone) {
							dropdown.append(`<option value="${zone.id}">${zone.zone_name}</option>`);
						});
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading zones</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		function loadRegions(zoneId) {
			const dropdown = $('#region');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.region.by.zone") }}',
				type: 'POST',
				data: {
					zone_id: zoneId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Region</option>');
					if (response.length > 0) {
						$.each(response, function(index, region) {
							dropdown.append(`<option value="${region.id}">${region.region_name}</option>`);
						});
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading regions</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		function loadTerritories(regionId) {
			const dropdown = $('#territory');
			dropdown.prop('disabled', true);
			dropdown.html('<option value="">Loading...</option>');

			$.ajax({
				url: '{{ route("hr.get.territory.by.region") }}',
				type: 'POST',
				data: {
					region_id: regionId,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					dropdown.html('<option value="">Select Territory</option>');
					if (response.length > 0) {
						$.each(response, function(index, territory) {
							dropdown.append(`<option value="${territory.id}">${territory.territory_name}</option>`);
						});
					}
					dropdown.prop('disabled', false);
				},
				error: function() {
					dropdown.html('<option value="">Error loading territories</option>');
					dropdown.prop('disabled', false);
				}
			});
		}

		
        function loadReportingManagers(departmentId) {
            const dropdown = $('#reporting_manager_id');

            dropdown.prop('disabled', true)
                .html('<option value="">Loading...</option>');

            $.ajax({
                url: '{{ route("hr.get.employees.by.department") }}',
                type: 'GET',
                data: {
                    department_id: departmentId
                },
                success: function(response) {
                    dropdown.html('<option value="">Select Reporting Manager</option>');

                    $.each(response, function(i, emp) {
                        dropdown.append(`
                    <option value="${emp.employee_id}"
                            data-name="${emp.emp_name}">
                        ${emp.emp_name} (${emp.emp_code})
                    </option>
                `);
                    });

                    dropdown.prop('disabled', false);
                }
            });
        }

		   // When reporting manager is selected, auto-fill the ID
        $('#reporting_manager_id').on('change', function() {
            const selected = $(this).find(':selected');
            const empName = selected.data('name') || '';

            $('#reporting_manager_name').val(empName);
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

		// Calculate contract end date
		function calculateContractEndDate() {
			const startDate = $('#contract_start_date').val();
			const duration = parseInt($('#contract_duration').val());

			if (startDate && duration) {
				const start = new Date(startDate);
				const endDate = new Date(start);
				endDate.setDate(start.getDate() + duration - 1);

				const formattedEndDate = endDate.toISOString().split('T')[0];
				$('#contract_end_date').val(formattedEndDate);
			}
		}

		$('#contract_start_date, #contract_duration').on('change', calculateContractEndDate);

		// File processing functions
		const requisitionType = $('input[name="requisition_type"]').val();

		// Process PAN Card
		$('#pan_card').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			const panNoField = $('#pan_no');
			panNoField.prop('disabled', true).val('Extracting...');

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
						panNoField.val(response.data.panNumber)
							.removeClass('is-invalid')
							.addClass(response.data.isVerified ? 'is-valid' : '');

						if (!response.data.isVerified) {
							showToast('PAN extracted but not verified. Please verify manually.', 'warning');
						} else {
							showToast('PAN extracted and verified successfully!', 'success');
						}

						$('#pan_filename').val(response.data.filename);
						$('#pan_filepath').val(response.data.filePath);
					}
				},
				error: function(xhr) {
					panNoField.val('').addClass('is-invalid');
					showToast('Failed to extract PAN. Please enter manually.', 'error');
				},
				complete: function() {
					panNoField.prop('disabled', false);
				}
			});
		});

		// Process Bank Document
		$('#bank_document').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			$('#account_holder_name, #bank_account_no, #bank_ifsc, #bank_name')
				.prop('disabled', true)
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

						if (data.accountNumber) {
							$('#bank_account_no').val(data.accountNumber)
								.removeClass('is-invalid')
								.addClass(data.isVerified ? 'is-valid' : '');
						}

						if (data.ifscCode) {
							$('#bank_ifsc').val(data.ifscCode)
								.removeClass('is-invalid')
								.addClass(data.isVerified ? 'is-valid' : '');
						}

						if (data.verificationData?.ifsc_details?.name) {
							$('#bank_name')
								.val(data.verificationData.ifsc_details.name)
								.removeClass('is-invalid')
								.addClass(data.isVerified ? 'is-valid' : '');
						}

						if (data.verificationData?.beneficiary_name) {
							$('#account_holder_name')
								.val(data.verificationData.beneficiary_name)
								.removeClass('is-invalid')
								.addClass(data.isVerified ? 'is-valid' : '');
						}

						if (!data.isVerified) {
							showToast('Bank details extracted but not verified. Please verify manually.', 'warning');
						} else {
							showToast('Bank details extracted and verified successfully!', 'success');
						}

						$('#bank_filename').val(data.filename);
						$('#bank_filepath').val(data.filePath);
					}
				},
				error: function(xhr) {
					$('#bank_account_no, #bank_ifsc, #bank_name')
						.val('').addClass('is-invalid');
					showToast('Failed to extract bank details. Please enter manually.', 'error');
				},
				complete: function() {
					$('#account_holder_name, #bank_account_no, #bank_ifsc, #bank_name').prop('disabled', false);
				}
			});
		});

		// Process Aadhaar Card
		$('#aadhaar_card').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			const aadhaarField = $('#aadhaar_no');
			aadhaarField.prop('disabled', true).val('Extracting...');

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
					if (response.status === 'SUCCESS') {
						aadhaarField.val(response.data.aadhaarNumber);

						if (response.data.isVerified) {
							showToast('Aadhaar extracted and verified successfully!', 'success');
						} else {
							showToast('Aadhaar extracted but not verified.', 'warning');
						}

						$('#aadhaar_filename').val(response.data.filename);
						$('#aadhaar_filepath').val(response.data.filePath);
					}
				},
				error: function(xhr) {
					aadhaarField.val('');
					showToast('Failed to extract Aadhaar. Please enter manually.', 'error');
				},
				complete: function() {
					aadhaarField.prop('disabled', false);
				}
			});
		});

		// Toast notification function
		function showToast(message, type = 'info') {
			const toast = `<div class="toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 show position-fixed" role="alert" style="bottom: 20px; right: 20px; z-index: 1050;">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;

			$('.toast').remove();
			$('body').append(toast);

			setTimeout(() => {
				$('.toast').remove();
			}, 5000);
		}

		// Form submission
		$('#requisition-form').on('submit', function(e) {
			e.preventDefault();

			const form = $(this);
			const url = form.attr('action');
			const formData = new FormData(form[0]);

			form.find('.is-invalid').removeClass('is-invalid');
			form.find('.invalid-feedback').text('').hide();

			const submitBtn = form.find('button[type="submit"]');
			const originalText = submitBtn.html();
			submitBtn.html('<i class="ri-loader-4-line ri-spin me-1"></i> Creating...').prop('disabled', true);

			$.ajax({
				url: url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						alert(response.message);
						window.location.href = response.redirect;
					}
				},
				error: function(xhr) {
					submitBtn.html(originalText).prop('disabled', false);

					if (xhr.status === 422) {
						const errors = xhr.responseJSON.errors;
						$.each(errors, function(field, messages) {
							const input = form.find(`[name="${field}"]`);
							input.addClass('is-invalid');
							input.siblings('.invalid-feedback').text(messages[0]).show();
						});
					} else {
						alert('An error occurred. Please try again.');
					}
				}
			});
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

         // Load reporting managers when department is selected
        $('#department_id').on('change', function() {
            const departmentId = $(this).val();
            resetDropdown('#sub_department_id');
            loadReportingManagers(departmentId);

            if (departmentId) {
                loadSubDepartments(departmentId);
            }
        });
        // Auto-fill reporting manager ID when reporting manager is selected
        $('#reporting_manager_name').on('change', function() {
            const empId = $(this).val();
            $('#reporting_manager_id').val(empId);
        });

	});
</script>
@endsection