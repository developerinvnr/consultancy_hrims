                    <!-- Status Badge -->
                    <div class="row mb-4">
                    	<div class="col-12">
                    		@php
                    		$statusColors = [
                    		'Pending HR Verification' => 'warning',
                    		'Correction Required' => 'danger',
                    		'Pending Approval' => 'info',
                    		'Approved' => 'success',
                    		'Rejected' => 'dark',
                    		'Processed' => 'primary',
                    		'Agreement Pending' => 'secondary',
                    		'Completed' => 'success'
                    		];
                    		$color = $statusColors[$requisition->status] ?? 'secondary';
                    		@endphp
                    		<div class="alert alert-{{ $color }} mb-0">
                    			<h6 class="alert-heading mb-0">Status: {{ $requisition->status }}</h6>
                    			@if($requisition->hr_verification_remarks)
                    			<hr>
                    			<p class="mb-0"><strong>HR Remarks:</strong> {{ $requisition->hr_verification_remarks }}</p>
                    			@endif
                    			@if($requisition->rejection_reason)
                    			<hr>
                    			<p class="mb-0"><strong>Rejection Reason:</strong> {{ $requisition->rejection_reason }}</p>
                    			@endif
                    		</div>
                    	</div>
                    </div>

                    <!-- Basic Information -->
                    <div class="row mb-4">
                    	<div class="col-md-6">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<h6 class="mb-0">Basic Information</h6>
                    			</div>
                    			<div class="card-body">
                    				<table class="table table-sm table-borderless mb-0">
                    					<tr>
                    						<td width="40%"><strong>Requisition ID:</strong></td>
                    						<td>{{ $requisition->requisition_id }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Type:</strong></td>
                    						<td>{{ $requisition->requisition_type }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Submitted By:</strong></td>
                    						<td>{{ $requisition->submitted_by_name }} ({{ $requisition->submitted_by_employee_id }})</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Submission Date:</strong></td>
                    						<td>{{ $requisition->submission_date->format('d-m-Y H:i') }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Candidate Name:</strong></td>
                    						<td>{{ $requisition->candidate_name }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Candidate Email:</strong></td>
                    						<td>{{ $requisition->candidate_email }}</td>
                    					</tr>
                    				</table>
                    			</div>
                    		</div>
                    	</div>
                    	<div class="col-md-6">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<h6 class="mb-0">Employment Details</h6>
                    			</div>
                    			<div class="card-body">
                    				<table class="table table-sm table-borderless mb-0">
                    					<tr>
                    						<td width="40%"><strong>Reporting To:</strong></td>
                    						<td>{{ $requisition->reporting_to }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Reporting Manager ID:</strong></td>
                    						<td>{{ $requisition->reporting_manager_employee_id }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Date of Joining:</strong></td>
                    						<td>{{ $requisition->contract_start_date->format('d-m-Y') }}</td>
                    					</tr>
                    					<tr>
                    						<td><strong>Contract End Date:</strong></td>
                    						<td>{{ $requisition->contract_end_date->format('d-m-Y') }}</td>
                    					</tr>
                    					@if($requisition->contract_duration)
                    					<tr>
                    						<td><strong>Agreement Duration:</strong></td>
                    						<td>{{ $requisition->contract_duration }} months</td>
                    					</tr>
                    					@endif
                    					<tr>
                    						<td><strong>Remuneration:</strong></td>
                    						<td>₹ {{ number_format($requisition->remuneration_per_month, 2) }}/month</td>
                    					</tr>
                    				</table>
                    			</div>
                    		</div>
                    	</div>
                    </div>

                    <!-- Personal Information -->
                    <div class="row mb-4">
                    	<div class="col-12">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<h6 class="mb-0">Personal Information</h6>
                    			</div>
                    			<div class="card-body">
                    				<div class="row">
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							<tr>
                    								<td width="40%"><strong>Father's Name:</strong></td>
                    								<td>{{ $requisition->father_name }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>Mobile No:</strong></td>
                    								<td>{{ $requisition->mobile_no }}</td>
                    							</tr>
                    							@if($requisition->alternate_email)
                    							<tr>
                    								<td><strong>Alternate Email:</strong></td>
                    								<td>{{ $requisition->alternate_email }}</td>
                    							</tr>
                    							@endif
                    							<tr>
                    								<td><strong>Date of Birth:</strong></td>
                    								<td>{{ $requisition->date_of_birth->format('d-m-Y') }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>Gender:</strong></td>
                    								<td>{{ $requisition->gender }}</td>
                    							</tr>
                    						</table>
                    					</div>
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							<tr>
                    								<td width="40%"><strong>Address:</strong></td>
                    								<td>{{ $requisition->address_line_1 }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>City:</strong></td>
                    								<td>{{ $requisition->city }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>State (Residence):</strong></td>
                    								<td>{{ $requisition->state_residence }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>PIN Code:</strong></td>
                    								<td>{{ $requisition->pin_code }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>Highest Qualification:</strong></td>
                    								<td>{{ $requisition->highest_qualification }}</td>
                    							</tr>
                    							@if($requisition->college_name)
                    							<tr>
                    								<td><strong>College Name:</strong></td>
                    								<td>{{ $requisition->college_name }}</td>
                    							</tr>
                    							@endif
                    						</table>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>

                    <!-- Work Information -->
                    <div class="row mb-4">
                    	<div class="col-12">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<h6 class="mb-0">Work Information</h6>
                    			</div>
                    			<div class="card-body">
                    				<div class="row">
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							<tr>
                    								<td width="40%"><strong>Work Location/HQ:</strong></td>
                    								<td>{{ $requisition->work_location_hq }}</td>
                    							</tr>
                    							@if($requisition->district)
                    							<tr>
                    								<td><strong>District:</strong></td>
                    								<td>{{ $requisition->district }}</td>
                    							</tr>
                    							@endif
                    							<tr>
                    								<td><strong>State (Work Location):</strong></td>
                    								<td>{{ $requisition->state_work_location }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>Function:</strong></td>
                    								<td>{{ $requisition->function->function_name ?? 'N/A' }}</td>
                    							</tr>
                    							<tr>
                    								<td><strong>Department:</strong></td>
                    								<td>{{ $requisition->department->department_name ?? 'N/A' }}</td>
                    							</tr>
                    						</table>
                    					</div>
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							<tr>
                    								<td width="40%"><strong>Vertical:</strong></td>
                    								<td>{{ $requisition->vertical->vertical_name ?? 'N/A' }}</td>
                    							</tr>
                    							@if($requisition->sub_department)
                    							<tr>
                    								<td><strong>Sub Department:</strong></td>
                    								<td>{{ $requisition->sub_department }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->business_unit)
                    							<tr>
                    								<td><strong>Business Unit:</strong></td>
                    								<td>{{ $requisition->business_unit }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->zone)
                    							<tr>
                    								<td><strong>Zone:</strong></td>
                    								<td>{{ $requisition->zone }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->region)
                    							<tr>
                    								<td><strong>Region:</strong></td>
                    								<td>{{ $requisition->region }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->territory)
                    							<tr>
                    								<td><strong>Territory:</strong></td>
                    								<td>{{ $requisition->territory }}</td>
                    							</tr>
                    							@endif
                    						</table>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>

                    <!-- Extracted Information -->
                    <div class="row mb-4">
                    	<div class="col-12">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<h6 class="mb-0">Kyc Information</h6>
                    			</div>
                    			<div class="card-body">
                    				<div class="row">
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							@if($requisition->pan_no)
                    							<tr>
                    								<td width="40%"><strong>PAN No:</strong></td>
                    								<td>{{ $requisition->pan_no }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->aadhaar_no)
                    							<tr>
                    								<td><strong>Aadhaar No:</strong></td>
                    								<td>{{ $requisition->aadhaar_no }}</td>
                    							</tr>
                    							@endif
                    						</table>
                    					</div>
                    					<div class="col-md-6">
                    						<table class="table table-sm table-borderless mb-0">
                    							@if($requisition->bank_account_no)
                    							<tr>
                    								<td width="40%"><strong>Bank Account No:</strong></td>
                    								<td>{{ $requisition->bank_account_no }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->bank_ifsc)
                    							<tr>
                    								<td><strong>Bank IFSC:</strong></td>
                    								<td>{{ $requisition->bank_ifsc }}</td>
                    							</tr>
                    							@endif
                    							@if($requisition->bank_name)
                    							<tr>
                    								<td><strong>Bank Name:</strong></td>
                    								<td>{{ $requisition->bank_name }}</td>
                    							</tr>
                    							@endif
                    						</table>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    	</div>
                    </div>

                    <!-- Documents -->
                    <div class="row mb-4">
                    	<div class="col-12">
                    		<div class="card border">
                    			<div class="card-header bg-light py-2">
                    				<div class="d-flex justify-content-between align-items-center">
                    					<h6 class="mb-0">Documents</h6>
                    					<span class="badge bg-primary">{{ $requisition->documents->count() }} documents</span>
                    				</div>
                    			</div>
                    			<div class="card-body">
                    				@if($requisition->documents && $requisition->documents->count() > 0)
                    				<div class="row">
                    					@foreach($requisition->documents as $document)
                    					<div class="col-md-4 mb-3">
                    						<div class="card border">
                    							<div class="card-body">
                    								<h6 class="card-title">
                    									@switch($document->document_type)
                    									@case('pan_card')
                    									<i class="ri-bank-card-line me-1"></i> PAN Card
                    									@break
                    									@case('aadhaar_card')
                    									<i class="ri-id-card-line me-1"></i> Aadhaar Card
                    									@break
                    									@case('bank_document')
                    									<i class="ri-bank-line me-1"></i> Bank Document
                    									@break
                    									@case('resume')
                    									<i class="ri-file-text-line me-1"></i> Resume
                    									@break
                    									@case('driving_licence')
                    									<i class="ri-car-line me-1"></i> Driving Licence
                    									@break
                    									@case('zbm_gm_approval')
                    									<i class="ri-approval-line me-1"></i> ZBM/GM Approval
                    									@break
                    									@default
                    									<i class="ri-file-line me-1"></i> {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                    									@endswitch
                    								</h6>
                    								<p class="card-text text-muted mb-1">
                    									<small>File: {{ $document->file_name }}</small>
                    								</p>
                    								<p class="card-text text-muted mb-2">
                    									<small>Uploaded: {{ $document->created_at->format('d-m-Y H:i') }}</small>
                    								</p>
                    								<div class="d-flex gap-2">
                    									@php
                    									// Get S3 URL - adjust based on your S3 configuration
                    									$s3Url = Storage::disk('s3')->url($document->file_path);
                    									// OR if you're using S3 public URLs directly:
                    									// $s3Url = config('filesystems.disks.s3.url') . '/' . $document->file_path;
                    									@endphp
                    									<a href="{{ $s3Url }}"
                    										target="_blank"
                    										class="btn btn-sm btn-primary">
                    										<i class="ri-eye-line me-1"></i> View
                    									</a>
                    									<a href="{{ $s3Url }}"
                    										download="{{ $document->file_name }}"
                    										class="btn btn-sm btn-secondary">
                    										<i class="ri-download-line me-1"></i> Download
                    									</a>
                    								</div>
                    							</div>
                    						</div>
                    					</div>
                    					@endforeach
                    				</div>
                    				@else
                    				<div class="text-center py-4">
                    					<i class="ri-folder-open-line display-1 text-muted"></i>
                    					<p class="text-muted mt-3">No documents uploaded.</p>
                    				</div>
                    				@endif
                    			</div>
                    		</div>
                    	</div>
                    </div>