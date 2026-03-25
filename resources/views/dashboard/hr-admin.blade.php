@extends('layouts.guest')

@section('page-title', 'HR Admin Dashboard')

@section('content')
<div class="container-fluid">
	<!-- Page Header -->
	<div class="row mb-1">
		<div class="col-12">
			<div class="page-title-box d-sm-flex align-items-center justify-content-between">
				<h4 class="mb-sm-0 fs-5">
					<i class="ri-dashboard-3-line me-2"></i>HR Dashboard
				</h4>
			</div>
		</div>
	</div>

	<div class="row mb-1">

		<!-- LEFT SIDE ATTENTION PANEL (1/3 WIDTH) -->
		<div class="col-lg-4">

			<div class="card border-danger shadow-sm">

				<div class="card-body">

					<h6 class="text-danger mb-2">
						⚠️ Attention Panel
					</h6>

					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						🔴 Delayed Cases

						<small class="text-{{ $attention['delay_color'] }}">
							({{ $attention['avg_delay_days'] }}d {{ $attention['delay_severity'] }})
						</small>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						🟡 About to be delayed

						<span class="float-end fw-bold text-warning">
							{{ $attention['about_to_delay'] }}
						</span>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						📅 Expiring in 3 days

						<span class="float-end fw-bold">
							{{ $attention['expiring_3_days'] }}
						</span>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						📅 Expiring in 5 days

						<span class="float-end fw-bold">
							{{ $attention['expiring_5_days'] }}
						</span>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						📅 Expiring in 7 days

						<span class="float-end fw-bold">
							{{ $attention['expiring_7_days'] }}
						</span>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						📄 Agreement Not Signed

						<span class="float-end fw-bold">
							{{ $attention['agreement_not_signed'] }}
						</span>

					</div>


					<div class="border rounded px-2 py-1 mb-2 d-flex justify-content-between align-items-center bg-light">

						🚚 Courier Pending

						<span class="float-end fw-bold">
							{{ $attention['courier_pending'] }}
						</span>

					</div>

				</div>

			</div>

		</div>


		<!-- RIGHT SIDE KPI GRID (2/3 WIDTH) -->
		<div class="col-lg-8">
			<!-- KPI CARDS -->
			<div class="card border-0 shadow-sm mb-2">
				<div class="card-body px-2 py-1">
					@include('dashboard.partials.kpi-cards')
				</div>
			</div>
		</div>

	</div>

	<div class="row">
		<!-- JOININGS CHART -->
		<div class="card border-0 shadow-sm mt-1">
			<div class="card-body">
				<h6 class="mb-2">
					📊 Month-wise Joinings (Financial Year)
				</h6>
				<canvas id="joiningChart" height="30"></canvas>
			</div>

		</div>
	</div>

	<!-- Recent Requisitions Table -->
	@if(isset($recent_requisitions))
	<div class="row">
		<div class="col-12">
			<div class="card border-0 shadow-sm">
				<div class="card-body p-2">
					<h6 class="mb-2 fs-6">Recent Requisitions</h6>
					<div class="tabs-scroll">
						<ul class="nav nav-tabs mb-2 sticky-tabs">

							<li class="nav-item">
								<a class="nav-link requisition-tab"
									data-tab="submission"
									href="javascript:void(0)">
									Pending HR Verification
									<span class="badge bg-warning ms-1">
										{{ $tabCounts['submission'] ?? 0 }}
									</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='correction_required'?'active':'' }}" data-tab="correction_required" href="javascript:void(0)">
									Correction Required
									<span class="badge bg-danger">{{ $tabCounts['correction_required'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='hr_verified'?'active':'' }}" data-tab="hr_verified" href="javascript:void(0)">
									HR Verified
									<span class="badge bg-info">{{ $tabCounts['hr_verified'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='approval'?'active':'' }}" data-tab="approval" href="javascript:void(0)">
									Pending Approval
									<span class="badge bg-info">{{ $tabCounts['approval'] }}</span>

								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='approved'?'active':'' }}" data-tab="approved" href="javascript:void(0)">
									Approved Requisitions
									<span class="badge bg-primary">{{ $tabCounts['approved'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='unsigned'?'active':'' }}" data-tab="unsigned" href="javascript:void(0)">
									Agreement Upload Pending
									<span class="badge bg-success">{{ $tabCounts['unsigned'] }}</span>

								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='dispatch_pending'?'active':'' }}" data-tab="dispatch_pending" href="javascript:void(0)">
									Pending Dispatch
									<span class="badge bg-success">{{ $tabCounts['dispatch_pending'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='courier_pending'?'active':'' }}" data-tab="courier_pending" href="javascript:void(0)">
									Pending Courier Receipt
									<span class="badge bg-success">{{ $tabCounts['courier_pending'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='file_pending'?'active':'' }}" data-tab="file_pending" href="javascript:void(0)">
									File Creation Pending
									<span class="badge bg-success">{{ $tabCounts['file_pending'] }}</span>
								</a>
							</li>

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='active'?'active':'' }}" data-tab="active" href="javascript:void(0)">
									Active
									<span class="badge bg-success">{{ $tabCounts['active'] }}</span>
								</a>
							</li>

							{{--<li class="nav-item">
									<a class="nav-link requisition-tab {{ $req_tab=='inactive'?'active':'' }}" data-tab="inactive" href="javascript:void(0)">
							Inactive
							<span class="badge bg-success">{{ $tabCounts['inactive'] }}</span>
							</a>
							</li>--}}

							<li class="nav-item">
								<a class="nav-link requisition-tab {{ $req_tab=='rejected'?'active':'' }}" data-tab="rejected" href="javascript:void(0)">
									Rejected
									<span class="badge bg-success">{{ $tabCounts['rejected'] }}</span>
								</a>
							</li>

						</ul>
					</div>

					<div id="recent-requisitions-table">
						@include('dashboard.partials.recent-requisitions-table')
					</div>

				</div>
			</div>
		</div>
	</div>
	@endif



	@if(isset($expiry))
	<div class="row mb-1">
		<div class="col-12">
			<div class="card border-0 shadow-sm">
				<div class="card-body p-2">

					<h6 class="mb-2 fs-6">
						<i class="ri-calendar-event-line me-1"></i>
						Contract Expiry
					</h6>

					<ul class="nav nav-tabs mb-2">

						<li class="nav-item">
							<a class="nav-link {{ request('exp_tab','exp30')=='exp30'?'active':'' }}"
								href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp30']) }}">
								< 30 Days
									</a>
						</li>

						<li class="nav-item">
							<a class="nav-link {{ request('exp_tab')=='exp60'?'active':'' }}"
								href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp60']) }}">
								30 - 60 Days
							</a>
						</li>

						<li class="nav-item">
							<a class="nav-link {{ request('exp_tab')=='exp90'?'active':'' }}"
								href="{{ request()->fullUrlWithQuery(['exp_tab'=>'exp90']) }}">
								60 - 90 Days
							</a>
						</li>
					</ul>

					<div class="tab-content">

						<div class="tab-pane fade {{ request('exp_tab','exp30')=='exp30' ? 'show active' : '' }}">
							@include('dashboard.partials.expiry-table', ['list'=>$expiry['lt_30_days']])
						</div>

						<div class="tab-pane fade {{ request('exp_tab')=='exp60' ? 'show active' : '' }}">
							@include('dashboard.partials.expiry-table', ['list'=>$expiry['days_30_60']])
						</div>

						<div class="tab-pane fade {{ request('exp_tab')=='exp90' ? 'show active' : '' }}">
							@include('dashboard.partials.expiry-table', ['list'=>$expiry['days_60_90']])
						</div>

					</div>

				</div>
			</div>
		</div>
	</div>
	@endif


	<!-- Top Submitters & Departments (Side by Side) -->
	<div class="row g-2 mb-2">
		<div class="col-md-6 col-lg-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-2">
					<h6 class="mb-2 fs-6">Top Submitters (30d)</h6>
					@forelse($stats['top_submitters'] as $submitter)
					<div class="d-flex justify-content-between align-items-center mb-1 pb-1 {{ !$loop->last ? 'border-bottom' : '' }}">
						<span class="text-truncate fs-12">{{ $submitter->submitted_by_name }}</span>
						<span class="badge bg-primary fs-10">{{ $submitter->count }}</span>
					</div>
					@empty
					<p class="text-muted text-center mb-0 fs-12">No data</p>
					@endforelse
				</div>
			</div>
		</div>

		<div class="col-md-6 col-lg-6">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-body p-2">
					<h6 class="mb-2 fs-6">Top Departments</h6>
					@forelse($stats['by_department'] as $dept)
					<div class="d-flex justify-content-between align-items-center mb-1 pb-1 {{ !$loop->last ? 'border-bottom' : '' }}">
						<span class="text-truncate fs-12">{{ $dept->department->department_name ?? 'N/A' }}</span>
						<span class="badge bg-info fs-10">{{ $dept->count }}</span>
					</div>
					@empty
					<p class="text-muted text-center mb-0 fs-12">No data</p>
					@endforelse
				</div>
			</div>
		</div>
	</div>


</div>

<!-- Process Modal -->
@include('hr.modals.process-modal')

<!-- Upload Signed Agreement Modal -->
@include('hr.modals.upload-signed-modal')

<!-- Receive Courier Modal -->
@include('hr.modals.receive-courier-modal')

<!-- End Contract Modal -->
@include('hr.modals.end-contract-modal')

<!-- upload estamp Modal -->
@include('hr.modals.upload-estamp-modal')
<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
	<div class="toast-container"></div>
</div>
@endsection

@push('scripts')

<script>
	window.routes = {
		uploadEstamp: "{{ route('hr-admin.master.upload-estamp', ['candidate'=>'CANDIDATE_ID']) }}",
		fileCreated: "{{ route('hr-admin.candidate.file-created') }}",
		getManagers: "{{ route('hr-admin.applications.get-reporting-managers','__ID__') }}",
		processModal: "{{ route('hr-admin.applications.process-modal') }}"
	};
</script>

<script src="{{ asset('assets/js/hr-common.js') }}?v={{ time() }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
	$(document).ready(function() {
		// Get CSRF token from meta tag
		const csrfToken = $('meta[name="csrf-token"]').attr('content');

		// Initialize Select2 for process modal
		$('#reporting_manager_employee_id').select2({
			theme: 'bootstrap-5',
			placeholder: '-- Select Reporting Manager --',
			allowClear: true,
			dropdownParent: $('#processModal'),
			width: '100%'
		});



		function loadSignedDocuments(candidateId) {
			$('#signedDocumentsList').html(`
					<div class="text-center">
						<div class="spinner-border spinner-border-sm" role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
						Loading documents...
					</div>
				`);

			$.ajax({
				url: `/hr-admin/candidate/${candidateId}/signed-documents`,
				type: 'GET',
				headers: {
					'X-CSRF-TOKEN': csrfToken
				},
				success: function(response) {
					if (response.success && response.documents.length > 0) {
						let html = '<div class="list-group">';
						response.documents.forEach(doc => {
							html += `
                    <label class="list-group-item">
                        <input class="form-check-input me-1 document-radio" 
                               type="radio" name="document_id" 
                               value="${doc.id}" data-file-url="${doc.file_url}">
                        <div>
                            <strong>${doc.agreement_number}</strong>
                            <small class="text-muted d-block">
                                Uploaded by: ${doc.uploaded_by === 'submitter' ? 'Candidate' : 'HR'} 
                                on ${doc.created_at}
                            </small>
                            <a href="${doc.file_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="ri-eye-line"></i> View
                            </a>
                        </div>
                    </label>`;
						});
						html += '</div>';
						$('#signedDocumentsList').html(html);

						// Enable radio selection
						$('.document-radio').on('change', function() {
							$('#verifySubmitBtn').prop('disabled', false);
						});
					} else {
						$('#signedDocumentsList').html(`
                    <div class="alert alert-warning">
                        <i class="ri-alert-line"></i> No signed agreements available for verification.
                        <br>
                        <small>The candidate hasn't uploaded any signed agreements yet.</small>
                    </div>
                `);
					}
				},
				error: function() {
					$('#signedDocumentsList').html(`
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line"></i> Failed to load documents.
                </div>
            `);
				}
			});
		}



		$('#applyFilter').on('click', function() {

			let status = $('#statusFilter').val();
			let action = $('#actionFilter').val();

			let url = new URL(window.location.href);

			// Always keep tab=status
			url.searchParams.set('req_tab', 'status');

			if (status) {
				url.searchParams.set('status_filter', status);
			} else {
				url.searchParams.delete('status_filter');
			}

			if (action) {
				url.searchParams.set('action_filter', action);
			} else {
				url.searchParams.delete('action_filter');
			}

			window.location.href = url.toString();
		});


		$(document).on('click', '.requisition-tab', function() {

			let tab = $(this).data('tab');

			$('.requisition-tab').removeClass('active');
			$(this).addClass('active');

			$('#recent-requisitions-table').html(
				'<div class="text-center py-3">Loading...</div>'
			);

			$.ajax({
				url: "{{ route('dashboard') }}",
				type: "GET",
				data: {
					req_tab: tab
				},
				success: function(response) {

					$('#recent-requisitions-table').html(response);

				}
			});

		});

	});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
	const joiningData = @json($joiningsChart);

	const fyLabels = [
		'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
		'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'
	];

	const formattedData = fyLabels.map((_, index) => {

		let monthNumber = index + 4;

		if (monthNumber > 12)
			monthNumber -= 12;

		return joiningData[monthNumber] ?? 0;

	});


	new Chart(document.getElementById('joiningChart'), {

		type: 'bar',

		data: {

			labels: fyLabels,

			datasets: [{

				label: 'Joinings',

				data: formattedData,

				borderWidth: 1

			}]

		},

		options: {

			responsive: true,

			plugins: {

				legend: {

					display: false

				}

			},

			scales: {

				y: {

					beginAtZero: true,

					ticks: {

						precision: 0

					}

				}

			}

		}

	});
</script>
<style>
	.select2-container {
		z-index: 1065 !important;
		/* Higher than Bootstrap modal */
	}

	.bg-soft-success {
		background-color: #e6f7f0 !important;
	}

	.nav-tabs {
		border-bottom: 1px solid #dee2e6;
	}

	.nav-tabs .nav-link {
		font-size: 13px;
		padding: 6px 14px;
		color: #495057;
		border: 1px solid transparent;
		border-top-left-radius: 6px;
		border-top-right-radius: 6px;
	}

	/* hover */
	.nav-tabs .nav-link:hover {
		border-color: #dee2e6 #dee2e6 #dee2e6;
		background: #f8f9fa;
	}

	/* ACTIVE TAB */
	.nav-tabs .nav-link.active {
		color: #5e999d;
		background: #ffffff;
		border-color: #dee2e6 #dee2e6 #ffffff;
		font-weight: 600;
	}

	.nav-tabs {
		flex-wrap: wrap;
		gap: 6px;
	}

	.tabs-scroll {
		overflow-x: auto;
	}

	.tabs-scroll .nav-tabs {
		flex-wrap: nowrap;
	}

	.tabs-scroll .nav-item {
		white-space: nowrap;
	}
</style>
@endpush