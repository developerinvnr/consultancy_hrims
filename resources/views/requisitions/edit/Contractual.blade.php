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
							<h5 class="card-title mb-0">Edit Contractual Manpower Requisition Form</h5>
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
					<form id="requisition-form" method="POST" action="{{ route('requisitions.update', $requisition) }}" enctype="multipart/form-data">
						@csrf
						@method('PUT')
						@php
						$documents = $requisition->documents ?? collect();
						@endphp
						@php
						$bankDoc = $documents->firstWhere('document_type', 'bank_document');
						$panDoc = $documents->firstWhere('document_type', 'pan_card');
						$resumeDoc = $documents->firstWhere('document_type', 'resume');
						$drivingDoc = $documents->firstWhere('document_type', 'driving_licence');
						$aadhaarDoc = $documents->firstWhere('document_type', 'aadhaar_card');
						$otherDocs = $documents->where('document_type', 'other');

						@endphp
						<input type="hidden" name="requisition_type" value="Contractual">
						<input type="hidden" name="pan_filename" id="pan_filename" value="{{ $panDoc->file_name ?? '' }}">
						<input type="hidden" name="pan_filepath" id="pan_filepath" value="{{ $panDoc->file_path ?? '' }}">
						<input type="hidden" name="bank_filename" id="bank_filename" value="{{ $bankDoc->file_name ?? '' }}">
						<input type="hidden" name="bank_filepath" id="bank_filepath" value="{{ $bankDoc->file_path ?? '' }}">
						<input type="hidden" name="aadhaar_filename" id="aadhaar_filename" value="{{ $aadhaarDoc->file_name ?? '' }}">
						<input type="hidden" name="aadhaar_filepath" id="aadhaar_filepath" value="{{ $aadhaarDoc->file_path ?? '' }}">
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
													id="candidate_name" name="candidate_name"
													value="{{ old('candidate_name', $requisition->candidate_name) }}" required>

												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="father_name" class="form-label">Father's Name <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="father_name" name="father_name"
													value="{{ old('father_name', $requisition->father_name) }}" required>

												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
												<input type="date" class="form-control form-select-sm"
													id="date_of_birth" name="date_of_birth"
													value="{{ old('date_of_birth', $requisition->date_of_birth?->format('Y-m-d')) }}" required>
												<div class="invalid-feedback">Age must be 18 years or older</div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
												<!-- Gender -->
												<select class="form-select form-select-sm" id="gender" name="gender" required>
													<option value="">Select Gender</option>
													<option value="Male" {{ old('gender', $requisition->gender) == 'Male' ? 'selected' : '' }}>Male</option>
													<option value="Female" {{ old('gender', $requisition->gender) == 'Female' ? 'selected' : '' }}>Female</option>
													<option value="Other" {{ old('gender', $requisition->gender) == 'Other' ? 'selected' : '' }}>Other</option>
												</select>
												<div class="invalid-feedback"></div>
											</div>
										</div>

										<div class="row">

											<div class="col-md-3 mb-3">
												<label for="mobile_no" class="form-label">Mobile No. <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="mobile_no" name="mobile_no" maxlength="10"
													value="{{ old('mobile_no', $requisition->mobile_no) }}" required>

												<div class="invalid-feedback">10-digit number required</div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="candidate_email" class="form-label">Email <span class="text-danger">*</span></label>
												<input type="email" class="form-control form-select-sm"
													id="candidate_email" name="candidate_email"
													value="{{ old('candidate_email', $requisition->candidate_email) }}" required>
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="alternate_email" class="form-label">Alternate Email</label>
												<input type="email" class="form-control form-select-sm"
													id="alternate_email" name="alternate_email"
													value="{{ old('alternate_email', $requisition->alternate_email) }}">
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="highest_qualification" class="form-label">Highest Qualification <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="highest_qualification" name="highest_qualification"
													value="{{ old('highest_qualification', $requisition->highest_qualification) }}" required>
												<div class="invalid-feedback"></div>
											</div>
										</div>

										<div class="row">

											<div class="col-md-3 mb-3">
												<label for="college_name" class="form-label">College/University</label>
												<input type="text" class="form-control form-select-sm"
													id="college_name" name="college_name"
													value="{{ old('college_name', $requisition->college_name) }}">

												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-2 mb-3">
												<label for="state_residence" class="form-label">State <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="state_residence" name="state_residence" required>
													<option value="">Select State</option>
													@foreach($states as $state)
													<option value="{{ $state }}" {{ old('state_residence', $requisition->state_residence) == $state ? 'selected' : '' }}>
														{{ $state }}
													</option>
													@endforeach
												</select>
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-3 mb-3">
												<label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
												<textarea class="form-control form-select-sm"
													id="address_line_1" name="address_line_1" rows="2" required>
												{{ old('address_line_1', $requisition->address_line_1) }}</textarea>
												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-2 mb-3">
												<label for="city" class="form-label">City <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="city" name="city"
													value="{{ old('city', $requisition->city) }}" required>

												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-2 mb-3">
												<label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="pin_code" name="pin_code" maxlength="6"
													value="{{ old('pin_code', $requisition->pin_code) }}" required>
												<div class="invalid-feedback"></div>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>

						<!-- Section 2: Work Information -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 2: Work Information</h6>
									</div>
									<div class="card-body">
										<div class="row">
											<!-- Function -->
											<div class="col-md-3 mb-3">
												<label for="function_id" class="form-label">Function <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" disabled>
													@foreach($functions as $function)
													<option {{ $requisition->function_id == $function->id ? 'selected' : '' }}>
														{{ $function->function_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="function_id" value="{{ $requisition->function_id }}">
												<div class="invalid-feedback"></div>
											</div>

											<!-- Department -->
											<div class="col-md-3 mb-3">
												<label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" disabled>
													@foreach($departments as $department)
													<option {{ $requisition->department_id == $department->id ? 'selected' : '' }}>
														{{ $department->department_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="department_id" value="{{ $requisition->department_id }}">
												<div class="invalid-feedback"></div>
											</div>

											<!-- Sub-Department -->
											<div class="col-md-3 mb-3">
												<label for="sub_department_id" class="form-label">Department <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" disabled>
													@foreach($sub_departments as $subdepartment)
													<option {{ $requisition->sub_department == $subdepartment->id ? 'selected' : '' }}>
														{{ $subdepartment->sub_department_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="sub_department_id" value="{{ $requisition->sub_department_id }}">
												<div class="invalid-feedback"></div>
											</div>


											<!-- Vertical -->
											<div class="col-md-3 mb-3">
												<label for="vertical_id" class="form-label">Vertical <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" disabled>
													@foreach($verticals as $vertical)
													<option {{ $requisition->vertical_id == $vertical->id ? 'selected' : '' }}>
														{{ $vertical->vertical_name }}
													</option>
													@endforeach
												</select>
												<input type="hidden" name="vertical_id" value="{{ $requisition->vertical_id }}">
												<div class="invalid-feedback"></div>
											</div>
										</div>

										<div class="row">
											<div class="col-md-6 mb-3">
												<label for="work_location_hq" class="form-label">Work Location/HQ Details <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													id="work_location_hq" name="work_location_hq"
													value="{{ old('work_location_hq', $requisition->work_location_hq) }}" required>

												<div class="invalid-feedback"></div>
											</div>
											<div class="col-md-6 mb-3">
												<label for="state_work_location" class="form-label">State (Work Location) <span class="text-danger">*</span></label>
												<select class="form-select form-select-sm" id="state_work_location" name="state_work_location" required>
													<option value="">Select State</option>
													@foreach($states as $state)
													<option value="{{ $state }}" {{ old('state_work_location', $requisition->state_work_location) == $state ? 'selected' : '' }}>
														{{ $state }}
													</option>
													@endforeach
												</select>
												<div class="invalid-feedback"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Section 3: Employment Details -->
						<div class="row mb-4">
							<div class="col-12">
								<div class="card border">
									<div class="card-header bg-light py-2">
										<h6 class="mb-0">Section 3: Employment Details</h6>
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-md-4 mb-3">
												<label class="form-label">Reporting To <span class="text-danger">*</span></label>
												<input type="text" class="form-control form-select-sm"
													value="{{ $requisition->reporting_to }}" readonly>
												<input type="hidden" name="reporting_to" value="{{ $requisition->reporting_to }}">
												<input type="hidden" name="reporting_manager_employee_id" value="{{ $requisition->reporting_manager_employee_id }}">
											</div>
											{{--<div class="col-md-3 mb-3">
                                                <label class="form-label">Reporting Manager ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-select-sm" 
                                                       value="{{ $autoFillData['reporting_manager_employee_id'] }}" readonly>
											<input type="hidden" name="reporting_manager_employee_id" value="{{ $autoFillData['reporting_manager_employee_id'] }}">
										</div>--}}
										<div class="col-md-4 mb-3">
											<label for="contract_start_date" class="form-label">Contract Start Date<span class="text-danger">*</span></label>
											<input type="date" class="form-control form-select-sm"
												id="contract_start_date" name="contract_start_date"
												value="{{ old('contract_start_date', $requisition->contract_start_date?->format('Y-m-d')) }}" required>
										</div>
										<div class="col-md-4 mb-3">
											<label for="contract_duration" class="form-label">
												Contract Duration <span class="text-danger">*</span>
											</label>

											<select class="form-select form-select-sm" id="contract_duration" name="contract_duration" required>
												<option value="">Select Duration</option>

												@php
												$durations = [
												30 => '1 Month',
												60 => '2 Months',
												90 => '3 Months',
												120 => '4 Months',
												150 => '5 Months',
												180 => '6 Months',
												210 => '7 Months',
												240 => '8 Months',
												270 => '9 Months',
												];
												@endphp

												@foreach($durations as $value => $label)
												<option value="{{ $value }}"
													{{ old('contract_duration', $requisition->contract_duration) == $value ? 'selected' : '' }}>
													{{ $label }}
												</option>
												@endforeach
											</select>

											<div class="invalid-feedback"></div>
										</div>
									</div>

									<div class="row">
										<div class="col-md-4 mb-3">
											<label for="contract_end_date" class="form-label">Contract End Date<span class="text-danger">*</span></label>
											<input type="date" class="form-control form-select-sm"
												id="contract_end_date" name="contract_end_date"
												value="{{ old('contract_end_date', $requisition->contract_end_date?->format('Y-m-d')) }}" readonly required>

											<div class="invalid-feedback"></div>
										</div>
										<div class="col-md-4 mb-3">
											<label for="remuneration_per_month" class="form-label">Remuneration/Month <span class="text-danger">*</span></label>
											<div class="input-group input-group-sm">
												<span class="input-group-text">₹</span>
												<input type="number" class="form-control"
													id="remuneration_per_month" name="remuneration_per_month"
													step="0.01" min="0"
													value="{{ old('remuneration_per_month', $requisition->remuneration_per_month) }}" required>
											</div>
											<div class="invalid-feedback"></div>
										</div>
										<div class="col-md-4 mb-3">
											<label for="fuel_reimbursement_per_month" class="form-label">Fuel Reimbursement</label>
											<div class="input-group input-group-sm">
												<span class="input-group-text">₹</span>
												<input type="number" class="form-control"
													id="fuel_reimbursement_per_month" name="fuel_reimbursement_per_month"
													step="0.01" min="0"
													value="{{ old('fuel_reimbursement_per_month', $requisition->fuel_reimbursement_per_month) }}">
											</div>
											<div class="invalid-feedback"></div>
										</div>
									</div>

									<div class="row">
										<div class="col-12 mb-3">
											<label for="reporting_manager_address" class="form-label">Address for Agreement Dispatch <span class="text-danger">*</span></label>
											<textarea class="form-control form-select-sm"
												id="reporting_manager_address" name="reporting_manager_address"
												rows="3" required>{{ old('reporting_manager_address', $requisition->reporting_manager_address) }}</textarea>
											<div class="invalid-feedback"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
				</div>

				<!-- Section 4: Document Uploads -->
				<!-- Section 4: Document Uploads -->
				<div class="row mb-4">
					<div class="col-12">
						<div class="card border">
							<div class="card-header bg-light py-2">
								<h6 class="mb-0">Section 4: Document Uploads with Data Extraction</h6>
							</div>
							<div class="card-body">

								<!-- First Row: Resume, Driving License, PAN Card -->
								<div class="row">
									<!-- Resume -->
									<div class="col-md-3 mb-3">
										<label for="resume" class="form-label">Resume <span class="text-danger">*</span></label>

										@if($resumeDoc)
										<div class="mb-2">
											<div class="d-flex justify-content-between align-items-center">
												<small class="text-success">
													<i class="ri-checkbox-circle-fill me-1"></i> File uploaded
												</small>
												<a href="{{ route('document.download', $resumeDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line me-1"></i> View
												</a>
											</div>
											<small class="text-muted d-block mt-1">{{ $resumeDoc->file_name }}</small>
											<small class="text-muted">Upload new file to replace</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="resume" name="resume" accept=".pdf,.doc,.docx" {{ !$resumeDoc ? 'required' : '' }}>
										<small class="text-muted">PDF, DOC, DOCX (Max 5MB)</small>
										<div class="invalid-feedback"></div>
									</div>

									<!-- Driving License -->
									<div class="col-md-3 mb-3">
										<label for="driving_licence" class="form-label">Driving Licence <span class="text-danger">*</span></label>

										@if($drivingDoc)
										<div class="mb-2">
											<div class="d-flex justify-content-between align-items-center">
												<small class="text-success">
													<i class="ri-checkbox-circle-fill me-1"></i> File uploaded
												</small>
												<a href="{{ route('document.download', $drivingDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line me-1"></i> View
												</a>
											</div>
											<small class="text-muted d-block mt-1">{{ $drivingDoc->file_name }}</small>
											<small class="text-muted">Upload new file to replace</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="driving_licence" name="driving_licence" accept=".pdf,.jpg,.jpeg,.png" {{ !$drivingDoc ? 'required' : '' }}>
										<small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
										<div class="invalid-feedback"></div>
									</div>

									<!-- PAN Card -->
									<div class="col-md-3 mb-3">
										<label for="pan_card" class="form-label">PAN Card <span class="text-danger">*</span></label>

										@if($panDoc)
										<div class="mb-2">
											<div class="d-flex justify-content-between align-items-center">
												<small class="text-success">
													<i class="ri-checkbox-circle-fill me-1"></i> File uploaded
												</small>
												<a href="{{ route('document.download', $panDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line me-1"></i> View
												</a>
											</div>
											<small class="text-muted d-block mt-1">{{ $panDoc->file_name }}</small>
											<small class="text-muted">Upload new file to replace</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="pan_card" name="pan_card" accept=".pdf,.jpg,.jpeg,.png" {{ !$panDoc ? 'required' : '' }}>
										<small class="text-muted">Clear image/PDF for auto-extraction</small>
										<div class="invalid-feedback"></div>
									</div>

									<!-- PAN Number -->
									<div class="col-md-3 mb-3">
										<label for="pan_no" class="form-label">PAN Number <span class="text-danger">*</span></label>
										<div class="input-group input-group-sm">
											<input type="text" class="form-control"
												id="pan_no" name="pan_no" maxlength="10"
												value="{{ old('pan_no', $requisition->pan_no) }}" required>
											<span class="input-group-text">
												<i class="ri-checkbox-circle-fill text-success d-none" id="pan-verified-icon"></i>
												<i class="ri-alert-fill text-warning d-none" id="pan-warning-icon"></i>
											</span>
										</div>
										<small class="text-muted" id="pan-status-text">
											@if($panDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@else
											Upload PAN to auto-extract
											@endif
										</small>
										<div class="invalid-feedback">Valid PAN required</div>
									</div>
								</div>

								<!-- Second Row: Aadhaar Card, Aadhaar Number, Bank Document -->
								<div class="row">
									<!-- Aadhaar Card -->
									<div class="col-md-3 mb-3">
										<label for="aadhaar_card" class="form-label">Aadhaar Card <span class="text-danger">*</span></label>

										@if($aadhaarDoc)
										<div class="mb-2">
											<div class="d-flex justify-content-between align-items-center">
												<small class="text-success">
													<i class="ri-checkbox-circle-fill me-1"></i> File uploaded
												</small>
												<a href="{{ route('document.download', $aadhaarDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line me-1"></i> View
												</a>
											</div>
											<small class="text-muted d-block mt-1">{{ $aadhaarDoc->file_name }}</small>
											<small class="text-muted">Upload new file to replace</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="aadhaar_card" name="aadhaar_card" accept=".pdf,.jpg,.jpeg,.png" {{ !$aadhaarDoc ? 'required' : '' }}>
										<small class="text-muted">Clear image/PDF of Aadhaar</small>
										<div class="invalid-feedback"></div>
									</div>

									<!-- Aadhaar Number -->
									<div class="col-md-3 mb-3">
										<label for="aadhaar_no" class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
										<div class="input-group input-group-sm">
											<input type="text" class="form-control"
												id="aadhaar_no" name="aadhaar_no" maxlength="12"
												value="{{ old('aadhaar_no', $requisition->aadhaar_no) }}" required>
											<span class="input-group-text">
												<i class="ri-checkbox-circle-fill text-success d-none" id="aadhaar-verified-icon"></i>
												<i class="ri-alert-fill text-warning d-none" id="aadhaar-warning-icon"></i>
											</span>
										</div>
										<small class="text-muted" id="aadhaar-status-text">
											@if($aadhaarDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@else
											Upload Aadhaar to auto-extract
											@endif
										</small>
										<div class="invalid-feedback">Valid Aadhaar required</div>
									</div>

									<!-- Bank Document -->
									<div class="col-md-3 mb-3">
										<label for="bank_document" class="form-label">Bank Document <span class="text-danger">*</span></label>

										@if($bankDoc)
										<div class="mb-2">
											<div class="d-flex justify-content-between align-items-center">
												<small class="text-success">
													<i class="ri-checkbox-circle-fill me-1"></i> File uploaded
												</small>
												<a href="{{ route('document.download', $bankDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line me-1"></i> View
												</a>
											</div>
											<small class="text-muted d-block mt-1">{{ $bankDoc->file_name }}</small>
											<small class="text-muted">Upload new file to replace</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="bank_document" name="bank_document" accept=".pdf,.jpg,.jpeg,.png" {{ !$bankDoc ? 'required' : '' }}>
										<small class="text-muted">Passbook/Cancelled Cheque</small>
										<div class="invalid-feedback"></div>
									</div>

									<!-- Other Document -->
									<div class="col-md-3 mb-3">
										<label for="other_document" class="form-label">Other Document (Optional)</label>

										@if($otherDocs->count() > 0)
										<div class="mb-2">
											<small class="text-success">
												<i class="ri-checkbox-circle-fill me-1"></i> {{ $otherDocs->count() }} file(s) uploaded
											</small>
											@foreach($otherDocs as $otherDoc)
											<div class="d-flex justify-content-between align-items-center mt-1">
												<small class="text-muted">{{ $otherDoc->file_name }}</small>
												<a href="{{ route('document.download', $otherDoc) }}"
													class="btn btn-xs btn-outline-primary" target="_blank">
													<i class="ri-eye-line"></i>
												</a>
											</div>
											@endforeach
											<small class="text-muted d-block mt-1">Upload new file to add more</small>
										</div>
										@endif
										<input type="file" class="form-control form-select-sm"
											id="other_document" name="other_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
										<small class="text-muted">Additional documents</small>
									</div>
								</div>

								<!-- Bank Details Row -->
								<div class="row">
									<!-- Account Holder Name -->
									<div class="col-md-3 mb-3">
										<label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
										<input type="text" class="form-control form-select-sm"
											id="account_holder_name" name="account_holder_name"
											value="{{ old('account_holder_name', $requisition->account_holder_name) }}" required>
										<small class="text-muted">
											@if($bankDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@endif
										</small>
										<div class="invalid-feedback">Account holder name required</div>
									</div>

									<!-- Account Number -->
									<div class="col-md-3 mb-3">
										<label for="bank_account_no" class="form-label">Account Number <span class="text-danger">*</span></label>
										<div class="input-group input-group-sm">
											<input type="text" class="form-control"
												id="bank_account_no" name="bank_account_no" maxlength="50"
												value="{{ old('bank_account_no', $requisition->bank_account_no) }}" required>
											<span class="input-group-text">
												<i class="ri-checkbox-circle-fill text-success d-none" id="account-verified-icon"></i>
												<i class="ri-alert-fill text-warning d-none" id="account-warning-icon"></i>
											</span>
										</div>
										<small class="text-muted">
											@if($bankDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@endif
										</small>
										<div class="invalid-feedback">Valid account number required</div>
									</div>

									<!-- IFSC Code -->
									<div class="col-md-3 mb-3">
										<label for="bank_ifsc" class="form-label">IFSC Code <span class="text-danger">*</span></label>
										<div class="input-group input-group-sm">
											<input type="text" class="form-control"
												id="bank_ifsc" name="bank_ifsc" maxlength="11"
												value="{{ old('bank_ifsc', $requisition->bank_ifsc) }}" required>
											<span class="input-group-text">
												<i class="ri-checkbox-circle-fill text-success d-none" id="ifsc-verified-icon"></i>
												<i class="ri-alert-fill text-warning d-none" id="ifsc-warning-icon"></i>
											</span>
										</div>
										<small class="text-muted">
											@if($bankDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@endif
										</small>
										<div class="invalid-feedback">Valid IFSC code required</div>
									</div>

									<!-- Bank Name -->
									<div class="col-md-3 mb-3">
										<label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
										<input type="text" class="form-control form-select-sm"
											id="bank_name" name="bank_name"
											value="{{ old('bank_name', $requisition->bank_name) }}" required>
										<small class="text-muted">
											@if($bankDoc)
											<i class="ri-checkbox-circle-fill text-success me-1"></i>Extracted from uploaded file
											@endif
										</small>
										<div class="invalid-feedback">Bank name required</div>
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
								<i class="ri-save-line me-1"></i> Update Requisition
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

@section('script_section')
<script src="{{ asset('assets/js/contract-rules.js') }}"></script>

<script>
	$(document).ready(function() {
		initContractDateValidation("#contract_start_date");
		// Get requisition type from hidden input
		const requisitionType = $('input[name="requisition_type"]').val();

		// Process PAN Card when file is selected
		$('#pan_card').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			// Show loading indicator
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

						// Store filename for submission
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

		// Process Bank Document when file is selected
		$('#bank_document').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			// Show loading indicators
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

						// Update bank fields
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

						// Store filename for submission
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

		// Add this to your JavaScript section
		$('#aadhaar_card').on('change', function() {
			const file = this.files[0];
			if (!file) return;

			const aadhaarField = $('#aadhaar_no');
			updateAadhaarStatus('loading', 'Extracting Aadhaar number...');
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
							updateAadhaarStatus('success', 'Aadhaar verified successfully!', true);
							showToast('Aadhaar extracted and verified successfully!', 'success');
						} else {
							updateAadhaarStatus('success', 'Aadhaar extracted but not verified', false);
							showToast('Aadhaar extracted but not verified.', 'warning');
						}

						// Store filename for submission
						$('#aadhaar_filename').val(response.data.filename);
						$('#aadhaar_filepath').val(response.data.filePath);
					}
				},
				error: function(xhr) {
					aadhaarField.val('');
					updateAadhaarStatus('error', 'Failed to extract Aadhaar. Please enter manually.');
					showToast('Failed to extract Aadhaar. Please enter manually.', 'error');
				},
				complete: function() {
					aadhaarField.prop('disabled', false);
				}
			});
		});
		// Helper function for Aadhaar status updates
		function updateAadhaarStatus(status, message, isVerified = false) {
			const aadhaarField = $('#aadhaar_no');
			const verifiedIcon = $('#aadhaar-verified-icon');
			const warningIcon = $('#aadhaar-warning-icon');
			const statusText = $('#aadhaar-status-text');

			aadhaarField.removeClass('is-invalid is-valid');
			verifiedIcon.addClass('d-none');
			warningIcon.addClass('d-none');

			if (status === 'success') {
				aadhaarField.addClass(isVerified ? 'is-valid' : '');
				if (isVerified) {
					verifiedIcon.removeClass('d-none');
				} else {
					warningIcon.removeClass('d-none');
				}
			} else if (status === 'error') {
				aadhaarField.addClass('is-invalid');
			} else if (status === 'loading') {
				// Loading state - keep icons hidden
			}

			statusText.text(message);
		}

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

		// Calculate separation date when date of joining or duration changes
		function calculateSeparationDate() {
			const doj = $('#contract_start_date').val();
			const duration = parseInt($('#contract_duration').val());

			if (doj && duration) {
				const dojDate = new Date(doj + "T00:00:00");
				const separationDate = new Date(dojDate);

				// Add number of days
				separationDate.setDate(separationDate.getDate() + duration - 1);

				const yyyy = separationDate.getFullYear();
				const mm = String(separationDate.getMonth() + 1).padStart(2, '0');
				const dd = String(separationDate.getDate()).padStart(2, '0');

				$('#contract_end_date').val(`${yyyy}-${mm}-${dd}`);
			}
		}


        	// Event listeners for date calculation
		$('#contract_start_date').on('change', function() {
			if ($('#contract_duration').val()) {
				calculateSeparationDate();
			}
		});

		$('#contract_duration').on('change', function() {
			if ($('#contract_start_date').val()) {
				calculateSeparationDate();
			}
		});


		// Validate Date of Birth
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

		// Form submission
		// Form submission
		$('#requisition-form').on('submit', function(e) {
			e.preventDefault();

			const form = $(this);
			const url = form.attr('action');
			const formData = new FormData(form[0]);

			// Add _method for Laravel to recognize as PUT
			formData.append('_method', 'PUT');

			// Get CSRF token from meta tag
			const csrfToken = $('meta[name="csrf-token"]').attr('content');

			form.find('.is-invalid').removeClass('is-invalid');
			form.find('.invalid-feedback').text('').hide();

			const submitBtn = form.find('button[type="submit"]');
			const originalText = submitBtn.html();
			submitBtn.html('<i class="ri-loader-4-line ri-spin me-1"></i> Updating...').prop('disabled', true);

			$.ajax({
				url: url,
				type: 'POST', // Must be POST when using FormData with _method
				data: formData,
				processData: false,
				contentType: false,
				headers: {
					'X-CSRF-TOKEN': csrfToken // Add CSRF token header
				},
				success: function(response) {
					if (response.success) {
						alert('Requisition updated successfully!');
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
	});
</script>
@endsection