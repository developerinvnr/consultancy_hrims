@extends('layouts.guest')

@section('content')
<div class="container-fluid">

	<!-- Page Header -->
	<div class="row mb-3">
		<div class="col-12">
			<div class="page-title-box d-flex justify-content-between align-items-center">
				<h4 class="mb-0">Salary Processing</h4>
				<div>
					<button class="btn btn-sm btn-outline-secondary me-2" onclick="refreshList()">
						<i class="ri-refresh-line"></i> Refresh
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Filter & Actions Card -->
	<div class="card mb-4 shadow-sm">
		<div class="card-body">
			<div class="row g-3 align-items-end">

				<div class="col-md-3 col-sm-6">
					<label class="form-label form-label-sm">Select Month-Year</label>
					<input type="month" id="monthYear" class="form-control form-control-sm" required>
				</div>

				<div class="col-md-5 col-sm-6">
					<label class="form-label form-label-sm">Quick Filter</label>
					<input type="text" id="search" class="form-control form-control-sm" placeholder="Search name / code...">
				</div>

				<div class="col-md-auto ms-auto d-flex gap-2 flex-wrap">
					<button class="btn btn-sm btn-primary" onclick="processSelected()">
						<i class="ri-play-circle-line"></i> Process Selected
					</button>
					<button class="btn btn-sm btn-success" onclick="processAll()">
						<i class="ri-check-double-line"></i> Process All Active
					</button>
					{{--<button class="btn btn-sm btn-info" onclick="exportExcel()">
						<i class="ri-file-excel-2-line"></i> Export Excel
					</button>--}}
				</div>

			</div>
		</div>
	</div>

	<!-- Salary Table -->
	<div class="card shadow-sm">
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover table-bordered mb-0" id="salaryTable">
					<thead class="table-light">
						<tr>
							<th width="40"><input type="checkbox" id="selectAll"></th>
							<th>Code</th>
							<th>Name</th>
							<th>Monthly Base</th>
							<th>Extra</th>
							<th>Deduction</th>
							<th>Net Pay</th>
							<th>Status</th>
							<th width="140">Actions</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="9" class="text-center py-4 text-muted">
								Select month-year to load active candidates
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="card-footer text-muted small">
			Showing active candidates (final_status = 'A'). Processed = calculated & saved.
		</div>
	</div>

</div>
@endsection

@push('styles')
<style>
	.badge-pending {
		background-color: #ffc107;
		color: black;
	}

	.badge-processed {
		background-color: #198754;
	}

	.badge-paid {
		background-color: #0d6efd;
	}

	.btn-sm {
		font-size: 0.8rem;
		padding: 0.25rem 0.5rem;
	}
</style>
@endpush

@section('script_section')
<script>
    let currentMonth = null;
    let currentYear = null;

    // Load list when month changes or on refresh
    $('#monthYear').on('change', function() {
        const val = $(this).val();
        if (!val) return;

        [currentYear, currentMonth] = val.split('-').map(Number);
        loadSalaryList();
    });

    function loadSalaryList() {
        if (!currentMonth || !currentYear) return;

        $.post("{{ route('salary.list') }}", {
            _token: '{{ csrf_token() }}',
            month: currentMonth,
            year: currentYear
        }, function(data) {
            renderTable(data);
        }).fail(() => {
            toastr.error("Failed to load salary data");
        });
    }

    function renderTable(records) {
        if (!records || records.length === 0) {
            $('#salaryTable tbody').html(`
                <tr><td colspan="9" class="text-center py-4 text-muted">No active candidates or no records for this period</td></tr>
            `);
            return;
        }

        let html = '';
        records.forEach(r => {
            const statusClass = r.processed ? 'success' : 'warning';
            const statusText = r.processed ? 'Processed' : 'Pending';

            html += `
            <tr data-id="${r.id || ''}" data-candidate-id="${r.candidate_id}">
                <td><input type="checkbox" class="row-check"></td>
                <td>${r.candidate?.candidate_code ?? '-'}</td>
                <td>${r.candidate?.candidate_name ?? '-'}</td>
                <td>₹ ${Number(r.monthly_salary || 0).toLocaleString('en-IN')}</td>
                <td class="text-success">+ ₹ ${Number(r.extra_amount || 0).toLocaleString('en-IN')}</td>
                <td class="text-danger">- ₹ ${Number(r.deduction_amount || 0).toLocaleString('en-IN')}</td>
                <td><strong>₹ ${Number(r.net_pay || 0).toLocaleString('en-IN')}</strong></td>
                <td>
                    <span class="badge bg-${statusClass}">${statusText}</span>
                </td>
                <td>
                    ${r.processed ? `
                        <a href="/hr/salary/payslip/${r.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="ri-download-line"></i> Payslip
                        </a>
						<!-- Recalculate button disabled for now
                        <button class="btn btn-sm btn-outline-warning recalculate-btn" 
                                title="Recalculate & Overwrite" 
                                data-candidate-id="${r.candidate_id}">
                            <i class="ri-restart-line"></i> Recalc
                        </button>
						-->
                    ` : `
                        <span class="text-muted small">Not processed</span>
                    `}
                </td>
            </tr>`;
        });

        $('#salaryTable tbody').html(html);

        // Re-attach events
        $('.recalculate-btn').on('click', function() {
            const candidateId = $(this).data('candidate-id');
            const candidateName = $(this).closest('tr').find('td:nth-child(3)').text().trim();
            
            Swal.fire({
                title: 'Recalculate Salary?',
                text: `Recalculate salary for ${candidateName}? This will overwrite existing data.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Recalculate',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    processSingle(candidateId, true);
                }
            });
        });
    }

    // Select All checkbox
    $('#selectAll').on('change', function() {
        $('.row-check').prop('checked', this.checked);
    });

    // Process All with warning
    function processAll() {
        if (!currentMonth || !currentYear) return toastr.error("Select month-year first");

        // Check if any records exist for this month
        $.post("{{ route('salary.checkExists') }}", {
            _token: '{{ csrf_token() }}',
            month: currentMonth,
            year: currentYear
        }, function(response) {
            let exists = response.exists || false;
            let count = response.count || 0;

            let confirmMessage = exists ?
                `<div class="alert alert-danger">
                    <i class="ri-alert-line"></i> 
                    <strong>${count} salary record(s) already exist for ${currentMonth}/${currentYear}!</strong><br>
                    Processing will overwrite all existing data.
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="forceAll">
                    <label class="form-check-label" for="forceAll">
                        I understand this will overwrite existing records
                    </label>
                </div>` :
                `Process salary for ALL active employees for ${currentMonth}/${currentYear}?`;

            Swal.fire({
                title: exists ? 'Overwrite Existing Records?' : 'Process All Salaries?',
                html: confirmMessage,
                icon: exists ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: exists ? 'Overwrite All' : 'Process All',
                confirmButtonColor: exists ? '#dc3545' : '#198754',
                preConfirm: () => {
                    if (exists) {
                        return {
                            force: $('#forceAll').is(':checked')
                        }
                    }
                    return {};
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                if (exists && !result.value?.force) {
                    toastr.warning("Operation cancelled - confirmation not checked");
                    return;
                }

                // Show processing loader
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process salaries',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post("{{ route('salary.process') }}", {
                    _token: '{{ csrf_token() }}',
                    month: currentMonth,
                    year: currentYear,
                    force: exists ? '1' : '0'
                }, function(response) {
                    Swal.close();
                    if (response.success) {
                        toastr.success(response.message);
                        loadSalaryList();
                    } else {
                        toastr.error(response.message || "Processing failed");
                    }
                }).fail(function(xhr) {
                    Swal.close();
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error("Bulk processing failed");
                    }
                });
            });
        });
    }
    
    // Process Selected (checkboxes)
    function processSelected() {
        const selected = $('.row-check:checked').closest('tr');
        const selectedIds = selected.map(function() {
            return $(this).data('candidate-id');
        }).get();

        if (selectedIds.length === 0) return toastr.warning("No employees selected");

        // Check if any selected are already processed
        let alreadyProcessed = [];
        selected.each(function() {
            const row = $(this).closest('tr');
            const statusBadge = row.find('.badge');
            if (statusBadge.hasClass('bg-success') || statusBadge.text().includes('Processed')) {
                alreadyProcessed.push(row.find('td:nth-child(3)').text().trim()); // Name column
            }
        });

        let confirmMessage = `Process ${selectedIds.length} selected employee(s)?`;
        if (alreadyProcessed.length > 0) {
            confirmMessage = `
                <div class="alert alert-warning">
                    <i class="ri-alert-line"></i> ${alreadyProcessed.length} employee(s) are already processed:<br>
                    <small class="text-muted">${alreadyProcessed.slice(0, 3).join(', ')}${alreadyProcessed.length > 3 ? '...' : ''}</small><br>
                    <strong>Processing again will overwrite existing data!</strong>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="forceProcess">
                    <label class="form-check-label" for="forceProcess">
                        Force Recalculation (overwrite existing)
                    </label>
                </div>
            `;
        }

        Swal.fire({
            title: alreadyProcessed.length > 0 ? 'Overwrite Processed Salaries?' : 'Process Selected?',
            html: confirmMessage,
            icon: alreadyProcessed.length > 0 ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: alreadyProcessed.length > 0 ? 'Yes, Overwrite' : 'Yes, Process',
            confirmButtonColor: alreadyProcessed.length > 0 ? '#dc3545' : '#198754',
            showDenyButton: alreadyProcessed.length > 0,
            denyButtonText: 'Skip Processed & Only Process New',
            denyButtonColor: '#6c757d',
            preConfirm: () => {
                return {
                    force: $('#forceProcess').is(':checked')
                }
            }
        }).then((result) => {
            if (result.isDismissed) return;

            const force = result.value?.force || false;
            const skipProcessed = result.isDenied;

            // Prepare candidate IDs to process
            let candidateIdsToProcess = [];
            
            if (skipProcessed) {
                selected.each(function() {
                    const row = $(this).closest('tr');
                    const statusBadge = row.find('.badge');
                    if (!statusBadge.hasClass('bg-success') && !statusBadge.text().includes('Processed')) {
                        candidateIdsToProcess.push($(this).closest('tr').data('candidate-id'));
                    }
                });
                
                if (candidateIdsToProcess.length === 0) {
                    toastr.info("All selected are already processed");
                    return;
                }
            } else {
                candidateIdsToProcess = selectedIds;
            }

            // Show processing loader
            Swal.fire({
                title: 'Processing...',
                text: `Processing ${candidateIdsToProcess.length} employee(s)`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send as JSON array
            $.ajax({
                url: "{{ route('salary.process') }}",
                method: 'POST',
                data: JSON.stringify({
                    _token: '{{ csrf_token() }}',
                    month: currentMonth,
                    year: currentYear,
                    force: force ? '1' : '0',
                    candidate_ids: candidateIdsToProcess
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        toastr.success(response.message);
                        loadSalaryList();
                    } else {
                        toastr.error(response.message || "Processing failed");
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error("Processing failed");
                    }
                }
            });
        });
    }

    // Process single employee (from recalculate button)
    function processSingle(candidateId, force = false) {
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "{{ route('salary.process') }}",
            method: 'POST',
            data: JSON.stringify({
                _token: '{{ csrf_token() }}',
                month: currentMonth,
                year: currentYear,
                candidate_id: candidateId,
                force: force ? '1' : '0'
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.success) {
                    toastr.success(response.message || "Salary recalculated");
                    loadSalaryList();
                } else {
                    toastr.error(response.message || "Failed to recalculate");
                }
            },
            error: function(xhr) {
                Swal.close();
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error("Recalculation failed");
                }
            }
        });
    }

    function exportExcel() {
        if (!currentMonth || !currentYear) return toastr.error("Select month-year first");
        window.location = `/hr/salary/export?month=${currentMonth}&year=${currentYear}`;
    }

    function refreshList() {
        loadSalaryList();
    }

    // Auto-load if monthYear has value on page load
    if ($('#monthYear').val()) {
        $('#monthYear').trigger('change');
    }
</script> 
@endsection