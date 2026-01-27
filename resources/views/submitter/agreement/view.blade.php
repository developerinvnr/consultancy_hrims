@extends('layouts.guest')

@section('page-title', 'View Agreement')

@section('content')
<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="page-title-box py-2 d-sm-flex align-items-center justify-content-between">
				<h5 class="mb-0">
					@if($isCompleted)
					Completed Agreement
					@else
					Unsigned Agreement
					@endif
					</h4>
					<div class="page-title-right">
						<ol class="breadcrumb m-0">
							<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
							<li class="breadcrumb-item active">
								@if($isCompleted)
								Completed Agreement
								@else
								Unsigned Agreement
								@endif
							</li>
						</ol>
					</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="card-title mb-0">
						@if($isCompleted)
						Completed Agreement Details
						@else
						Agreement Details
						@endif
					</h5>

					{{--<a href="{{ url()->previous() ?? route('dashboard') }}" class="btn btn-sm btn-light">
						<i class="ri-arrow-left-line me-1"></i> Back
					</a>--}}
				</div>

				<div class="card-body">
					<!-- Status Alert -->
					@if($isCompleted)
					<div class="alert alert-success py-2 small">
						<i class="ri-check-double-line me-2"></i>
						<strong>Agreement Process Completed!</strong> The signed agreement has been uploaded and processed.
					</div>
					@else
					<div class="alert alert-info py-2 small">
						<i class="ri-information-line me-2"></i>
						<strong>Action Required:</strong> Please download the unsigned agreement, get it signed by the candidate, and upload the signed version.
					</div>
					@endif

					<div class="row g-2 mb-3 small">

						<div class="col-md-6">
							<div class="border rounded p-2 h-100">
								<strong class="d-block mb-1 text-muted">Candidate</strong>
								<div>Req ID: <strong>{{ $requisition->requisition_id }}</strong></div>
								<div>Code: {{ $candidate->candidate_code }}</div>
								<div>Name: {{ $candidate->candidate_name }}</div>
								<div>Email: {{ $candidate->candidate_email }}</div>
								<div>Status:
									<span class="badge bg-{{ $isCompleted ? 'success' : 'info' }}">
										{{ $requisition->status }}
									</span>
								</div>
							</div>
						</div>

						<div class="col-md-6">
							<div class="border rounded p-2 h-100">
								<strong class="d-block mb-1 text-muted">Agreement</strong>

								@if($unsignedAgreement)
								<div>No: {{ $unsignedAgreement->agreement_number }}</div>
								<div>Uploaded: {{ $unsignedAgreement->created_at->format('d M Y H:i') }}</div>
								@endif

								@if($isCompleted && $signedAgreement)
								<div>Signed On: {{ $signedAgreement->created_at->format('d M Y H:i') }}</div>
								<div>By: {{ $signedAgreement->uploadedBy->name ?? 'N/A' }}</div>
								@endif
							</div>
						</div>

					</div>


					<!-- Agreement Actions -->
					<div class="row">
						<div class="col-12">
							<h6>Agreement Actions</h6>
							<div class="d-flex gap-2 flex-wrap">
								@if($unsignedAgreement)
								<a href="{{ route('submitter.agreement.download', $requisition) }}?type=unsigned"
									class="btn btn-sm btn-outline-primary">
									<i class="ri-download-line"></i> Unsigned
								</a>
								@endif

								@if($isCompleted && $signedAgreement)
								<a href="{{ route('submitter.agreement.download', $requisition) }}?type=signed"
									class="btn btn-sm btn-outline-success">
									<i class="ri-download-line"></i> Signed
								</a>
								@endif

								@if(!$isCompleted)
								<button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#uploadSignedModal">
									<i class="ri-upload-2-line"></i> Upload
								</button>
								@endif
							</div>

						</div>
					</div>

					<!-- Compact Timeline -->
					<div class="row mt-2">
						<div class="col-12">
							<h6 class="mb-2">Agreement Timeline</h6>

							<ul class="list-group list-group-flush small">

								<li class="list-group-item px-0 py-1 d-flex justify-content-between">
									<span>
										<i class="ri-checkbox-circle-fill text-success me-1"></i>
										Unsigned Agreement Uploaded
									</span>
									<span class="text-muted">
										{{ $unsignedAgreement?->created_at?->format('d M Y, h:i A') ?? 'Pending' }}
									</span>
								</li>

								<li class="list-group-item px-0 py-1 d-flex justify-content-between">
									<span>
										<i class="ri-time-line text-warning me-1"></i>
										Signed Agreement Uploaded
									</span>
									<span class="text-muted">
										{{ $signedAgreement?->created_at?->format('d M Y, h:i A') ?? 'Pending' }}
									</span>
								</li>

								<li class="list-group-item px-0 py-1 d-flex justify-content-between">
									<span>
										<i class="ri-check-double-line text-success me-1"></i>
										Process Completed
									</span>
									<span class="text-muted">
										{{ $isCompleted ? $requisition->updated_at->format('d M Y, h:i A') : 'Not yet completed' }}
									</span>
								</li>

							</ul>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>

</div>

<!-- Upload Signed Agreement Modal (only show if not completed) -->
@if(!$isCompleted)
<div class="modal fade" id="uploadSignedModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Upload Signed Agreement</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<form id="uploadSignedForm" enctype="multipart/form-data">
				@csrf
				<div class="modal-body">
					<div class="alert alert-info">
						<i class="ri-information-line me-2"></i>
						Please upload the signed agreement PDF after getting it signed from the candidate.
					</div>

					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control"
							value="{{ $candidate->candidate_code }} - {{ $candidate->candidate_name }}"
							readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">Agreement Number *</label>
						<input type="text" class="form-control" name="agreement_number" required
							placeholder="Enter agreement number" maxlength="100"
							value="{{ $unsignedAgreement->agreement_number ?? '' }}">
						<small class="text-muted">Same agreement number as provided by HR</small>
					</div>

					<div class="mb-3">
						<label class="form-label">Signed Agreement File (PDF) *</label>
						<input type="file" class="form-control" name="agreement_file"
							accept=".pdf" required>
						<small class="text-muted">Maximum file size: 10MB. Only PDF files accepted.</small>
						<div class="form-text">
							<i class="ri-information-line me-1"></i>
							Ensure the agreement is signed by both candidate and company representative.
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success">
						<i class="ri-file-upload-line me-1"></i> Upload Signed Agreement
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endif
@endsection



@section('script_section')
<script>
	$(document).ready(function() {
		const csrfToken = $('meta[name="csrf-token"]').attr('content');

		// Only initialize upload form if modal exists
		@if(!$isCompleted)
		$('#uploadSignedForm').on('submit', function(e) {
			e.preventDefault();
			const form = $(this);
			const formData = new FormData(form[0]);
			const url = "{{ route('submitter.agreement.upload-signed', $requisition) }}";

			$.ajax({
				url: url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				headers: {
					'X-CSRF-TOKEN': csrfToken
				},
				beforeSend: function() {
					form.find('button[type="submit"]').prop('disabled', true).html(
						'<i class="ri-loader-4-line ri-spin me-1"></i> Uploading...'
					);
				},
				success: function(response) {
					if (response.success) {
						showToast('success', response.message);
						setTimeout(() => {
							window.location.reload();
						}, 2000);
					} else {
						showToast('error', response.message || 'An error occurred');
						form.find('button[type="submit"]').prop('disabled', false).html(
							'<i class="ri-file-upload-line me-1"></i> Upload Signed Agreement'
						);
					}
				},
				error: function(xhr) {
					let errorMessage = 'Failed to upload. Please try again.';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					} else if (xhr.responseJSON && xhr.responseJSON.errors) {
						errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
					}
					showToast('error', errorMessage);
					form.find('button[type="submit"]').prop('disabled', false).html(
						'<i class="ri-file-upload-line me-1"></i> Upload Signed Agreement'
					);
				}
			});
		});
		@endif

		function showToast(type, message) {
			const toast = `<div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

			if ($('.toast-container').length === 0) {
				$('body').append('<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"><div class="toast-container"></div></div>');
			}

			$('.toast-container').append(toast);
			$('.toast').last().toast('show');

			setTimeout(() => {
				$('.toast').last().remove();
			}, 5000);
		}
	});
</script>
@endsection