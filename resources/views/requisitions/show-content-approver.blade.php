<!-- resources/views/requisitions/show-content.blade.php -->
<!-- Status Badge -->
<div class="row mb-3">
	<div class="col-12">
		@php
		$statusColors = [
		'Pending HR Verification' => 'warning',
		'Correction Required' => 'danger',
		'Pending Approval' => 'info',
		'Approved' => 'success',
		'Rejected' => 'danger',
		'Processed' => 'primary',
		'Agreement Pending' => 'secondary',
		'Completed' => 'success'
		];
		$color = $statusColors[$requisition->status] ?? 'secondary';
		@endphp
		<div class="alert alert-{{ $color }} py-2 mb-0">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<strong class="fs-6">Status:</strong> <span class="badge bg-{{ $color }} text-dark">{{ $requisition->status }}</span>
					@if($requisition->hr_verification_remarks)
					<span class="ms-3"><small><strong>HR Remarks:</strong> {{ $requisition->hr_verification_remarks }}</small></span>
					@endif
					@if($requisition->rejection_reason)
					<span class="ms-3"><small><strong>Rejection:</strong> {{ $requisition->rejection_reason }}</small></span>
					@endif
				</div>
				<div class="text-muted small">
					Submitted: {{ $requisition->submission_date->format('d-m-Y H:i') }}
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Compact Information Grid -->
<div class="row g-2 mb-3">
	<!-- Basic Information -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">Basic Information</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					<tr>
						<td width="40%" class="text-muted small">Requisition ID:</td>
						<td class="small fw-medium">{{ $requisition->request_code }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Type:</td>
						<td class="small">{{ $requisition->requisition_type }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Submitted By:</td>
						<td class="small">{{ $requisition->submitted_by_name }} ({{ $requisition->submitted_by_employee_id }})</td>
					</tr>
					<tr>
						<td class="text-muted small">Candidate Name:</td>
						<td class="small">{{ $requisition->candidate_name }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Candidate Email:</td>
						<td class="small">{{ $requisition->candidate_email }}</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<!-- Employment Details -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">Employment Details</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					<tr>
						<td width="40%" class="text-muted small">Reporting To:</td>
						<td class="small">{{ $requisition->reporting_to }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Manager ID:</td>
						<td class="small">{{ $requisition->reporting_manager_employee_id }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Contract Start Date:</td>
						<td>
							<input type="date"
								name="contract_start_date"
								class="form-control form-control-sm"
								value="{{ $requisition->contract_start_date->format('Y-m-d') }}">
						</td>
					</tr>
					<tr>
						<td class="text-muted small">Contract End Date:</td>
						<td>
							<input type="date"
								name="contract_end_date"
								class="form-control form-control-sm"
								value="{{ $requisition->contract_end_date->format('Y-m-d') }}">
						</td>
					</tr>
					<tr>
						<td class="text-muted small">Remuneration:</td>
						<td>
							<input type="number"
								name="remuneration_per_month"
								class="form-control form-control-sm"
								value="{{ $requisition->remuneration_per_month }}">
						</td>
					</tr>
					<tr>
						<td class="text-muted small">Contract Duration:</td>
						<td id="contract-duration-display">
							{{ intval($requisition->contract_duration / 30) }} months
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<!-- Personal Information -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">Personal Information</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					<tr>
						<td width="40%" class="text-muted small">Father's Name:</td>
						<td class="small">{{ $requisition->father_name }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Mobile:</td>
						<td class="small">{{ $requisition->mobile_no }}</td>
					</tr>
					<tr>
						<td class="text-muted small">DOB:</td>
						<td class="small">{{ $requisition->date_of_birth->format('d-m-Y') }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Gender:</td>
						<td class="small">{{ $requisition->gender }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Qualification:</td>
						<td class="small">
							{{ $requisition->qualification->EducationName ?? 'N/A' }}
							@if($requisition->qualification?->EducationCode)
							({{ $requisition->qualification->EducationCode }})
							@endif
						</td>
					</tr>

					@if($requisition->alternate_email)
					<tr>
						<td class="text-muted small">Alt. Email:</td>
						<td class="small">{{ $requisition->alternate_email }}</td>
					</tr>
					@endif
					@if($requisition->college_name)
					<tr>
						<td class="text-muted small">College:</td>
						<td class="small">{{ $requisition->college_name }}</td>
					</tr>
					@endif
				</table>
			</div>
		</div>
	</div>

	<!-- Address Information -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">Address Information</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					<tr>
						<td width="40%" class="text-muted small">Address:</td>
						<td class="small">{{ $requisition->address_line_1 }}</td>
					</tr>
					<tr>
						<td class="text-muted small">City:</td>
						<td class="small"> {{ $requisition->cityMaster->city_village_name ?? 'N/A' }}</td>
					</tr>
					<tr>
						<td class="text-muted small">State:</td>
						<td class="small"> {{ $requisition->residenceState->state_name ?? 'N/A' }}</td>
					</tr>
					<tr>
						<td class="text-muted small">PIN Code:</td>
						<td class="small">{{ $requisition->pin_code }}</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<!-- Work Information -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">Work Information</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					<tr>
						<td width="40%" class="text-muted small">Location:</td>
						<td class="small">{{ $requisition->work_location_hq }}</td>
					</tr>
					@if($requisition->district)
					<tr>
						<td class="text-muted small">District:</td>
						<td class="small">{{ $requisition->district }}</td>
					</tr>
					@endif
					<tr>
						<td class="text-muted small">Work State:</td>
						<td class="small">
							{{ $requisition->workState->state_name ?? 'N/A' }}
						</td>
					</tr>
					<tr>
						<td class="text-muted small">Function:</td>
						<td class="small">{{ $requisition->function->function_name ?? 'N/A' }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Department:</td>
						<td class="small">{{ $requisition->department->department_name ?? 'N/A' }}</td>
					</tr>
					<tr>
						<td class="text-muted small">Vertical:</td>
						<td class="small">{{ $requisition->vertical->vertical_name ?? 'N/A' }}</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<!-- KYC Information -->
	<div class="col-md-6">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">KYC Information</h6>
			</div>
			<div class="card-body p-2">
				<table class="table table-sm table-borderless mb-0">
					@if($requisition->pan_no)
					<tr>
						<td width="40%" class="text-muted small">PAN No:</td>
						<td class="small">{{ $requisition->pan_no }}</td>
					</tr>
					@endif
					@if($requisition->aadhaar_no)
					<tr>
						<td class="text-muted small">Aadhaar No:</td>
						<td class="small">{{ $requisition->aadhaar_no }}</td>
					</tr>
					@endif
					@if($requisition->account_holder_name)
					<tr>
						<td class="text-muted small">Account Holder Name:</td>
						<td class="small">{{ $requisition->account_holder_name }}</td>
					</tr>
					@endif
					@if($requisition->bank_account_no)
					<tr>
						<td class="text-muted small">Bank Account:</td>
						<td class="small">{{ $requisition->bank_account_no }}</td>
					</tr>
					@endif
					@if($requisition->bank_ifsc)
					<tr>
						<td class="text-muted small">IFSC Code:</td>
						<td class="small">{{ $requisition->bank_ifsc }}</td>
					</tr>
					@endif
					@if($requisition->bank_name)
					<tr>
						<td class="text-muted small">Bank Name:</td>
						<td class="small">{{ $requisition->bank_name }}</td>
					</tr>
					@endif
				</table>
			</div>
		</div>
	</div>

	<!-- Documents -->
	<div class="col-md-12">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<div class="d-flex justify-content-between align-items-center">
					<h6 class="mb-0 fs-6">Documents</h6>
					<span class="badge bg-primary fs-6">{{ $requisition->documents->count() }}</span>
				</div>
			</div>

			<div class="card-body p-2">

				@if($requisition->documents && $requisition->documents->count() > 0)

				<div class="row g-1">

					@foreach($requisition->documents as $document)

					@php
					$s3Url = Storage::disk('s3')->url($document->file_path);

					// Icon mapping
					$iconClass = [
					'pan_card' => 'ri-bank-card-line',
					'aadhaar_card' => 'ri-id-card-line',
					'bank_document' => 'ri-bank-line',
					'resume' => 'ri-file-text-line',
					'driving_licence' => 'ri-car-line',
					'zbm_gm_approval' => 'ri-approval-line'
					][$document->document_type] ?? 'ri-file-line';

					// Verification status (ONLY for verified document types)
					$status = null;
					$statusClass = null;

					if($document->document_type === 'pan_card'){
					$status = $requisition->pan_status_2;
					}
					elseif($document->document_type === 'bank_document'){
					$status = $requisition->bank_verification_status;
					}
					elseif($document->document_type === 'driving_licence'){
					$status = $requisition->dl_verification_status;
					}

					// Assign color only if status exists
					if(!empty($status)){
					$statusClass = match(strtolower($status)){
					'verified','valid','success','successful' => 'success',
					'pending' => 'warning',
					'failed','invalid','rejected','inoperative' => 'danger',
					default => 'secondary'
					};
					}
					@endphp


					<div class="col-12">

						<div class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">

							<!-- Left section -->
							<div class="d-flex align-items-center">

								<i class="{{ $iconClass }} me-2 text-primary fs-6"></i>

								<div>

									<!-- Document name -->
									<small class="d-block text-muted">
										@switch($document->document_type)
										@case('pan_card') PAN Card @break
										@case('aadhaar_card') Aadhaar Card @break
										@case('bank_document') Bank Document @break
										@case('resume') Resume @break
										@case('driving_licence') Driving Licence @break
										@case('zbm_gm_approval') ZBM/GM Approval @break
										@default {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
										@endswitch
									</small>

									<!-- File name -->
									<small class="text-muted d-block">
										{{ $document->file_name }}
									</small>

									<!-- Verification badge ONLY if exists -->
									@if(!empty($status))
									<span class="badge bg-{{ $statusClass }} mt-1">
										{{ ucfirst($status) }}
									</span>
									@endif

								</div>

							</div>


							<!-- Right buttons -->
							<div class="btn-group btn-group-xs">

								<a href="{{ $s3Url }}"
									target="_blank"
									class="btn btn-outline-primary btn-xs"
									title="View">
									<i class="ri-eye-line fs-6"></i>
								</a>

								<a href="{{ $s3Url }}"
									download="{{ $document->file_name }}"
									class="btn btn-outline-secondary btn-xs"
									title="Download">
									<i class="ri-download-line fs-6"></i>
								</a>

							</div>

						</div>

					</div>

					@endforeach

				</div>

				@else

				<div class="text-center py-2">
					<i class="ri-folder-open-line text-muted fs-3"></i>
					<p class="text-muted mt-1 small">No documents uploaded</p>
				</div>

				@endif

			</div>
		</div>
	</div>

	<!-- Agreements Section -->
	@if(($agreements['unsigned'] ?? collect())->count() > 0 || ($agreements['signed'] ?? null))
	<div class="col-md-12 mt-3">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<div class="d-flex justify-content-between align-items-center">
					<h6 class="mb-0 fs-6">
						<i class="ri-file-copy-line me-1"></i>Agreements
					</h6>
					@if($agreements['signed'] ?? null)
					<span class="badge bg-success">Signed</span>
					@elseif(($agreements['unsigned'] ?? collect())->count() > 0)
					<span class="badge bg-warning">Pending Signature</span>
					@endif
				</div>
			</div>
			<div class="card-body p-2">
				<!-- Signed Agreement -->
				@if($agreements['signed'])
				<div class="mb-3">
					<h6 class="text-success mb-2">
						<i class="ri-check-double-line me-1"></i>Signed Agreement
					</h6>
					<div class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
						<div class="d-flex align-items-center">
							<i class="ri-file-pdf-line text-danger me-2 fs-5"></i>
							<div>
								<small class="d-block fw-medium">{{ $agreements['signed']->agreement_number }}</small>
								<small class="text-muted">
									Uploaded: {{ $agreements['signed']->created_at->format('d M Y') }}
									@if($agreements['signed']->courierDetails)
									@if($agreements['signed']->courierDetails->isReceived())
									<span class="badge bg-success ms-2">✓ Received</span>
									@else
									<span class="badge bg-warning ms-2">📦 Dispatched</span>
									@endif
									@endif
								</small>
							</div>
						</div>
						<div class="btn-group btn-group-xs">
							<a href="{{ $agreements['signed']->file_url }}"
								target="_blank"
								class="btn btn-outline-success btn-xs"
								title="View">
								<i class="ri-eye-line fs-6"></i>
							</a>

						</div>
					</div>

					<!-- Courier Details for Signed Agreement -->
					@if($agreements['signed']->courierDetails)
					@php $c = $agreements['signed']->courierDetails; @endphp
					<div class="mt-2 p-2 bg-light rounded small">
						<div class="d-flex align-items-center text-muted">
							<i class="ri-truck-line me-1"></i>
							<span class="me-3"><strong>Courier:</strong> {{ $c->courier_name }}</span>
							<span class="me-3"><strong>Docket:</strong> {{ $c->docket_number }}</span>
							<span><strong>Dispatch:</strong> {{ $c->formatted_dispatch_date }}</span>
							@if($c->isReceived())
							<span class="ms-3 text-success"><i class="ri-checkbox-circle-line me-1"></i>Received: {{ $c->formatted_received_date }}</span>
							@endif
						</div>
					</div>
					@endif
				</div>
				@endif

				<!-- Unsigned Agreements -->
				@if(($agreements['unsigned'] ?? collect())->count() > 0)
				<div>
					<h6 class="text-warning mb-2">
						<i class="ri-time-line me-1"></i>Unsigned Agreements
					</h6>
					@foreach($agreements['unsigned'] as $unsigned)
					<div class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
						<div class="d-flex align-items-center">
							<i class="ri-file-pdf-line text-danger me-2 fs-5"></i>
							<div>
								<small class="d-block fw-medium">{{ $unsigned->agreement_number }}</small>
								<small class="text-muted">
									Uploaded: {{ $unsigned->created_at->format('d M Y') }}
									<span class="badge bg-{{ $unsigned->stamp_type === 'E_STAMP' ? 'warning' : 'secondary' }} ms-1">
										{{ $unsigned->stamp_type === 'E_STAMP' ? 'E-Stamp' : 'No Stamp' }}
									</span>
								</small>
							</div>
						</div>
						<div class="btn-group btn-group-xs">
							<a href="{{ $unsigned->file_url }}"
								target="_blank"
								class="btn btn-outline-warning btn-xs"
								title="View">
								<i class="ri-eye-line fs-6"></i>
							</a>
						</div>
					</div>
					@endforeach
				</div>
				@endif

				<!-- No Agreements Message -->
				@if((!isset($agreements['unsigned']) || $agreements['unsigned']->count() === 0) && !isset($agreements['signed']))
				<div class="text-center py-3">
					<i class="ri-file-copy-line text-muted fs-3"></i>
					<p class="text-muted mt-1 small mb-0">No agreements available</p>
				</div>
				@endif
			</div>
		</div>
	</div>
	@endif


	<!-- Timeline Section -->
	<div class="col-md-12">
		<div class="card border">
			<div class="card-header bg-light py-1 px-2">
				<h6 class="mb-0 fs-6">
					<i class="ri-timeline-line me-1"></i>Requisition Timeline
				</h6>
			</div>
			<div class="card-body p-2">
				<div class="d-flex flex-wrap align-items-center gap-2 small">
					{{-- Submitted --}}
					@if($requisition->submission_date)
					<span class="badge bg-primary">
						Submitted
						<br>
						{{ $requisition->submission_date->format('d M Y') }}
					</span>
					@endif
					{{-- HR Verified --}}
					@if($requisition->hr_verification_date)
					<span class="badge bg-info">
						HR Verified
						<br>
						{{ $requisition->hr_verification_date->format('d M Y') }}
					</span>
					@endif
					{{-- Approved --}}
					@if($requisition->approval_date)
					<span class="badge bg-success">
						Approved
						<br>
						{{ $requisition->approval_date->format('d M Y') }}
					</span>
					@endif
					{{-- Agreement Created --}}

					@isset($agreements)
					@if(($agreements['unsigned'] ?? collect())->first())
					<span class="badge bg-secondary">
						Agreement Created
						<br>
						{{ $agreements['unsigned']->first()->created_at->format('d M Y') }}
					</span>
					@endif
					{{-- Agreement Signed --}}
					@if($agreements['signed'])
					<span class="badge bg-success">
						Agreement Signed
						<br>
						{{ $agreements['signed']->updated_at->format('d M Y') }}
					</span>
					@endif
					{{-- Courier Dispatched --}}
					@if($agreements['signed']?->courierDetails?->dispatch_date)
					<span class="badge bg-warning text-dark">
						Courier Sent
						<br>
						{{ $agreements['signed']->courierDetails->dispatch_date->format('d M Y') }}
					</span>
					@endif
					{{-- Courier Received --}}
					@if($agreements['signed']?->courierDetails?->received_date)
					<span class="badge bg-success">
						Courier Received
						<br>
						{{ $agreements['signed']->courierDetails->received_date->format('d M Y') }}
					</span>
					@endif
					@endisset
					{{-- File Created --}}
					@if($requisition->candidate?->file_created_date)
					<span class="badge bg-primary">
						File Created
						<br>
						{{ \Carbon\Carbon::parse($requisition->candidate->file_created_date)->format('d M Y') }}
					</span>
					@endif
					{{-- Ledger Created --}}
					@if($requisition->candidate?->ledger_created_at)
					<span class="badge bg-info">
						Ledger Created
						<br>
						{{ \Carbon\Carbon::parse($requisition->candidate->ledger_created_at)->format('d M Y') }}
					</span>
					@endif
					@if($requisition->candidate?->contract_cancelled_at)
					<span class="badge bg-danger">
						Contract Cancelled
						<br>
						{{ \Carbon\Carbon::parse($requisition->candidate->contract_cancelled_at)->format('d M Y') }}
					</span>
					@endif
				</div>
			</div>
		</div>
	</div>

	@php
	$loggedEmpId = auth()->user()->emp_id ?? null;
	@endphp
	@if($requisition->reporting_manager_employee_id == $loggedEmpId
	&& empty($requisition->candidate?->contract_cancelled_at) && !$requisition->candidate?->file_created_date)
	<div class="col-md-12 mt-2">
		<div class="card border-danger">
			<div class="card-body p-2 d-flex justify-content-between align-items-center">
				<div class="small text-danger fw-medium">
					Contract cancellation available for reporting manager
				</div>
				<button class="btn btn-danger btn-sm"
					data-bs-toggle="modal"
					data-bs-target="#cancelContractModal">
					<i class="ri-close-circle-line me-1"></i>
					Cancel Contract
				</button>
			</div>
		</div>
	</div>
	@endif

</div>

{{-- MODAL --}}
@if(
$requisition->reporting_manager_employee_id == $loggedEmpId
&& !$requisition->candidate?->contract_cancelled_at
&& !$requisition->candidate?->file_created_date
)
<div class="modal fade"
	id="cancelContractModal"
	tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="POST"
				action="{{ route('requisitions.cancel-contract', $requisition->id) }}">
				@csrf
				<div class="modal-header">
					<h6 class="modal-title text-danger">
						Cancel Contract
					</h6>
					<button type="button"
						class="btn-close"
						data-bs-dismiss="modal"></button>
				</div>


				<div class="modal-body">
					<label class="form-label small">
						Reason for cancellation
					</label>
					<textarea name="cancel_reason"
						class="form-control @error('cancel_reason') is-invalid @enderror"
						rows="3"
						required
						placeholder="Enter reason why contract is being cancelled minimum 10 characters">{{ old('cancel_reason') }}</textarea>

					@error('cancel_reason')
					<div class="invalid-feedback d-block">
						{{ $message }}
					</div>
					@enderror

				</div>
				<div class="modal-footer">
					<button type="button"
						class="btn btn-secondary btn-sm"
						data-bs-dismiss="modal">
						Close
					</button>
					<button type="submit"
						class="btn btn-danger btn-sm">
						Confirm Cancel Contract
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endif

<!-- Add compact CSS styles -->
<style>
	.compact-view .card {
		font-size: 0.8125rem;
	}

	.compact-view .card-header {
		padding: 0.25rem 0.5rem;
	}

	.compact-view .card-body {
		padding: 0.5rem;
	}

	.compact-view .table-sm td {
		padding: 0.15rem 0;
		font-size: 0.8125rem;
	}

	.compact-view .btn-xs {
		padding: 0.125rem 0.375rem;
		font-size: 0.75rem;
		line-height: 1.2;
	}

	.compact-view .btn-group-xs>.btn {
		padding: 0.125rem 0.375rem;
		font-size: 0.75rem;
		line-height: 1.2;
	}

	.compact-view .badge {
		font-size: 0.7em;
		padding: 0.25em 0.5em;
	}

	.compact-view .fs-6 {
		font-size: 0.875rem !important;
	}

	.compact-view .small {
		font-size: 0.8125rem;
	}

	.compact-view .row.g-2 {
		margin-top: -0.5rem;
	}

	.compact-view .alert {
		padding: 0.5rem 1rem;
	}

	.compact-view .alert .fs-6 {
		font-size: 0.875rem;
	}

	.timeline {
		position: relative;
		padding-left: 30px;
	}

	.timeline:before {
		content: '';
		position: absolute;
		left: 10px;
		top: 0;
		bottom: 0;
		width: 2px;
		background: #e9ecef;
	}

	.timeline-item {
		position: relative;
		margin-bottom: 20px;
	}

	.timeline-icon {
		position: absolute;
		left: -30px;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;
		z-index: 1;
	}

	.timeline-icon i {
		font-size: 18px;
	}

	.timeline-content {
		padding-left: 20px;
	}

	.timeline-content h6 {
		margin-bottom: 5px;
		font-size: 14px;
		font-weight: 600;
	}

	/* Background colors */
	.bg-primary {
		background-color: #0d6efd;
	}

	.bg-info {
		background-color: #0dcaf0;
	}

	.bg-success {
		background-color: #198754;
	}

	.bg-danger {
		background-color: #dc3545;
	}
</style>
@if ($errors->has('cancel_reason'))
<script>
	document.addEventListener("DOMContentLoaded", function() {
		var cancelModal = new bootstrap.Modal(
			document.getElementById('cancelContractModal')
		);
		cancelModal.show();
	});
</script>
@endif
<script>
	document.addEventListener('DOMContentLoaded', function() {
		
		const startInput = document.querySelector('input[name="contract_start_date"]');
		const endInput =  document.querySelector('input[name="contract_end_date"]');
		const durationRow = document.querySelector('#contract-duration-display');

		function updateDuration() {
			if (startInput.value && endInput.value) {
				const start = new Date(startInput.value);
				const end = new Date(endInput.value);
				const diff = Math.ceil(
					(end - start) / (1000 * 60 * 60 * 24)
				);
				durationRow.innerText =
					Math.floor(diff / 30) + ' months';

			}
		}
		startInput.addEventListener('change', updateDuration);
		endInput.addEventListener('change', updateDuration);

	});
</script>