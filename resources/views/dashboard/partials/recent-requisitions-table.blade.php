	<table class="table table-sm table-hover mb-0">
		<thead class="sticky-top bg-white">
			<tr>
				<th class="fs-11">ID</th>
				<th>Priority</th>
				<th class="fs-11">Candidate</th>
				<th class="fs-11">Email</th>
				<th class="fs-11">Type</th>
				<th class="fs-11">Reporting Manager</th>
				<th class="fs-11">Approver</th>
				<th class="fs-11">Status</th>
				<th>Ageing</th>
				<th class="fs-11">Remark</th>
				<th class="fs-11">Courier Status</th>
				<th class="fs-11">Date</th>
				<th class="fs-11">Actions</th>
			</tr>
		</thead>
		<tbody>
			@if($recent_requisitions->count() > 0)

			@foreach($recent_requisitions as $req)
			@php
			$isProcessed = $req->candidate ? true : false;
			$candidate = $req->candidate;
			$empStatus = $candidate->candidate_status ?? null;

			// Use the data we attached in the controller
			$signedAgreement = $req->signed_agreement ?? null;
			$courierDetails = $req->courier_details ?? null;
			@endphp
			<tr>
				<td class="fs-11">
					<span class="badge bg-secondary fs-10">{{ $req->request_code }}</span>
				</td>
				<td>
					<span class="badge bg-{{ $req->priority_color }}">
						{{ $req->priority_label }}
					</span>
				</td>
				<td class="fs-11">
					{{ $req->candidate_name }}
				</td>
				<td class="fs-11">
					<small class="text-muted fs-9">{{ $req->candidate_email }}</small>
				</td>
				<td class="fs-11">
					<span class="badge bg-{{ $req->requisition_type == 'Contractual' ? 'primary' : ($req->requisition_type == 'TFA' ? 'success' : 'info') }} fs-10">
						{{ $req->requisition_type }}
					</span>
				</td>
				<td class="fs-11">
					@if($candidate && $candidate->reportingManager)
					{{ $candidate->reportingManager->emp_name ?? 'N/A' }}
					@else
					<span class="text-muted fs-9">Not Assigned</span>
					@endif
				</td>
				<td class="fs-11">
					@if($req->currentApprover)

					@php
					$days = \Carbon\Carbon::parse($req->created_at)->diffInDays(now());
					@endphp

					@if($req->status == 'Pending Approval')
					<span class="{{ $days > 2 ? 'text-danger' : 'text-warning' }}">
						⏳ {{ $req->currentApprover->name }}
						<small>({{ floor($days) }}d)</small>
					</span>

					@elseif($req->status == 'Approved')
					<span class="text-success">
						✔ {{ $req->currentApprover->name }}
					</span>

					@else
					<span class="text-muted">
						{{ $req->currentApprover->name }}
					</span>
					@endif

					@else
					<span class="text-muted">Not Assigned</span>
					@endif
				</td>
				<td class="fs-11">
					@if($candidate && $candidate->candidate_status)

					@php
					$status = trim($candidate->candidate_status);

					$displayStatus = ($status === 'Unsigned Agreement Created')
					? 'Unsigned Agreement Created'
					: $status;

					$statusColors = [
					'Agreement Pending' => 'warning',
					'Unsigned Agreement Created' => 'info',
					'Signed Agreement Uploaded' => 'primary',
					'Agreement Completed' => 'secondary',
					'Active' => 'success',
					'Inactive' => 'danger',
					'Rejected' => 'danger'
					];
					@endphp

					<span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} fs-10">
						{{ $displayStatus }}
					</span>

					@if($candidate->candidate_code)
					<br>
					<small class="text-muted fs-9">{{ $candidate->candidate_code }}</small>
					@endif

					@else

					@switch($req->status)

					@case('Pending HR Verification')
					<span class="badge bg-warning fs-10">Pending HR Verification</span>
					@break

					@case('Correction Required')
					<span class="badge bg-danger fs-10">Correction Required</span>
					@break

					@case('Pending Approval')
					<span class="badge bg-info fs-10">Pending Approval</span>
					@break

					@case('Approved')
					<span class="badge bg-primary fs-10">Ready to Process</span>
					@break

					@case('Rejected')
					<span class="badge bg-danger fs-10">Rejected</span>


					@if($req->rejectedBy)
					<br>
					<small class="text-muted fs-9">
						By {{ $req->rejectedBy->name }}

						@if($req->rejectedBy->hasRole('hr_admin'))
						(HR Admin)
						@elseif($req->rejectedBy->emp_id == $req->approver_id)
						(Approver)
						@endif

					</small>
					@endif

					@break

					@default
					<span class="badge bg-secondary fs-10">{{ $req->status }}</span>

					@endswitch

					@endif
				</td>

				<td>
					<span class="fw-bold text-{{ $req->priority_color }}">
						{{ $req->ageing_days }}d
					</span>
				</td>

				<!-- REMARK COLUMN -->
				<td class="fs-11">

					@if($req->status == 'Rejected' && $req->rejection_reason)

					<small class="text-danger"
						title="{{ $req->rejection_reason }}">
						{{ \Illuminate\Support\Str::limit($req->rejection_reason, 50) }}
					</small>

					@else

					<span class="text-muted fs-9">-</span>

					@endif

				</td>

				<!-- COURIER STATUS COLUMN -->
				<td class="fs-11">
					@if($courierDetails)
					@if($courierDetails->received_date)
					<span class="badge bg-success fs-10">
						<i class="ri-checkbox-circle-line me-1"></i> Received
					</span>
					<small class="d-block text-muted fs-9">
						{{ \Carbon\Carbon::parse($courierDetails->received_date)->format('d M Y') }}
					</small>
					@else
					<span class="badge bg-warning fs-10">
						<i class="ri-truck-line me-1"></i> Dispatched
					</span>
					<small class="d-block text-muted fs-9">
						{{ $courierDetails->courier_name }}<br>
						Docket: {{ $courierDetails->docket_number }}<br>
						Dispatch: {{ \Carbon\Carbon::parse($courierDetails->dispatch_date)->format('d M Y') }}
					</small>
					@endif
					@elseif($signedAgreement)
					<span class="badge bg-secondary fs-10">
						<i class="ri-time-line me-1"></i> Awaiting Dispatch
					</span>
					<small class="d-block text-muted fs-9">
						Signed on: {{ \Carbon\Carbon::parse($signedAgreement->created_at)->format('d M Y') }}
					</small>
					@else
					<span class="text-muted fs-9">N/A</span>
					@endif
				</td>

				<td class="fs-11">{{ $req->created_at->format('d-M') }}</td>
				<td>
					<div class="btn-group btn-group-sm" role="group">
						<a href="{{ route('hr-admin.applications.view', $req) }}"
							class="btn btn-outline-primary" title="View">
							<i class="ri-eye-line fs-10"></i>
						</a>

						@if($req->status === 'Approved' && !$isProcessed)
						<button type="button" class="btn btn-success process-btn"
							data-bs-toggle="modal" data-bs-target="#processModal"
							data-requisition-id="{{ $req->id }}"
							data-requisition-type="{{ $req->requisition_type }}"
							data-requisition-name="{{ $req->candidate_name }}"
							data-current-reporting="{{ $req->reporting_to }}"
							data-current-manager-id="{{ $req->reporting_manager_employee_id }}">
							<i class="ri-play-line fs-10"></i>
						</button>
						@elseif($req->status === 'Pending Approval')
						<span class="badge bg-info fs-9">Awaiting Approval</span>
						@endif
						@if($candidate && $empStatus !== 'Inactive')

						<a href="{{ route('submitter.agreement.view', $req) }}"
							class="btn btn-sm btn-outline-{{ $req->status == 'Agreement Completed' ? 'success' : 'primary' }}"
							title="{{ $req->status == 'Agreement Completed' ? 'View Completed Agreement' : 'View Agreement Details' }}">
							<i class="ri-file-text-line"></i>
							@if($req->status == 'Signed Agreement Uploaded')
							<span class="badge bg-warning text-dark ms-1">Courier</span>
							@endif
						</a>
						@endif
						<!-- COURIER RECEIVE BUTTON -->
						@if($courierDetails && !$courierDetails->received_date)
						<button type="button"
							class="btn btn-outline-success receive-courier-btn"
							data-bs-toggle="modal"
							data-bs-target="#receiveCourierModal"
							data-requisition-id="{{ $req->id }}"
							data-agreement-id="{{ $signedAgreement->id ?? '' }}"
							data-candidate-name="{{ $req->candidate_name }}"
							data-courier-name="{{ $courierDetails->courier_name }}"
							data-docket-number="{{ $courierDetails->docket_number }}"
							data-dispatch-date="{{ \Carbon\Carbon::parse($courierDetails->dispatch_date)->format('d M Y') }}"
							title="Mark as Received">
							<i class="ri-check-double-line fs-10"></i>
						</button>
						@endif

						@if($courierDetails && $courierDetails->received_date && !$candidate->file_created_date)

						<button
							class="btn btn-sm btn-success create-file-btn"
							data-candidate-id="{{ $candidate->id }}">
							<i class="ri-folder-add-line"></i>
						</button>

						@endif

						@if($candidate)
						@php
						$hasUnsigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
						->where('document_type', 'agreement')
						->where('sign_status', 'UNSIGNED')
						->exists();

						$hasSigned = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
						->where('document_type', 'agreement')
						->where('sign_status', 'SIGNED')
						->exists();

						$agreementNumber = \App\Models\AgreementDocument::where('candidate_id', $candidate->id)
						->where('document_type', 'agreement')
						->where('sign_status', 'UNSIGNED')
						->value('agreement_number');

						$hasEstamp = $candidate->agreementDocuments
						->where('document_type','estamp')
						->count() > 0;
						@endphp


						@if(!$hasEstamp)

						<button type="button"
							class="btn btn-sm btn-warning upload-estamp-btn"
							data-candidate-id="{{ $candidate->id }}"
							data-candidate-code="{{ $candidate->candidate_code }}"
							data-candidate-name="{{ $candidate->candidate_name }}">
							<i class="ri-file-upload-line"></i>
						</button>

						@endif


						@if($empStatus == "Active")
						<button
							type="button"
							class="btn btn-outline-danger end-contract-btn"
							data-bs-toggle="modal"
							data-bs-target="#endContractModal"
							data-candidate-id="{{ $candidate->id }}"
							data-candidate-name="{{ $candidate->candidate_name }}"
							title="End Contract">
							<i class="ri-user-unfollow-line"></i>
						</button>
						@endif

						@if($hasUnsigned && !$hasSigned)
						<button type="button"
							class="btn btn-outline-primary upload-signed-btn"
							data-candidate-id="{{ $candidate->id }}"
							data-candidate-code="{{ $candidate->candidate_code }}"
							data-candidate-name="{{ $candidate->candidate_name }}"
							data-agreement-number="{{ $agreementNumber }}">
							<i class="ri-upload-line fs-10"></i>
						</button>
						@endif

						@endif
					</div>
				</td>
			</tr>
			@endforeach

			@else

			<tr>
				<td colspan="9" class="text-center text-muted py-4">
					No requisitions found for the selected filter.
				</td>
			</tr>
			@endif


		</tbody>
	</table>
	@if($recent_requisitions instanceof \Illuminate\Pagination\LengthAwarePaginator)
	<div class="d-flex justify-content-end mt-3">
		{{ $recent_requisitions->links('pagination::bootstrap-5') }}
	</div>
	@endif