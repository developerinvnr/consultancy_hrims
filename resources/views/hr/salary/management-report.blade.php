@extends('layouts.guest')

@section('content')
<div class="container-fluid">

	<!-- Page Header -->
	<div class="row mb-3">
		<div class="col-12">
			<div class="page-title-box d-flex justify-content-between align-items-center">
				<h4 class="mb-0">Management Remuneration Report</h4>
				<div class="d-flex gap-2">
					<button class="btn btn-sm btn-outline-secondary" onclick="refreshPage()">
						<i class="ri-refresh-line"></i> Refresh
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Filter Card -->
	<div class="card mb-3 shadow-sm">
		<div class="card-body py-2">
			<div class="row g-2 align-items-end">

				<!-- Year -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Year</label>
					<select id="year" class="form-select form-select-sm">
						@for($i = date('Y'); $i >= 2026; $i--)
						<option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
						@endfor
					</select>
				</div>

				<!-- Department -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Department</label>
					<select id="department" class="form-select form-select-sm">
						<option value="All">All Departments</option>
						@foreach($departments as $dept)
						<option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- BU -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">BU</label>
					<select id="bu" class="form-select form-select-sm">
						<option value="All">All BUs</option>
						@foreach($businessUnits as $bu)
						<option value="{{ $bu->id }}">{{ $bu->business_unit_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Zone -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Zone</label>
					<select id="zone" class="form-select form-select-sm">
						<option value="All">All Zones</option>
						@foreach($zones as $zone)
						<option value="{{ $zone->id }}">{{ $zone->zone_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Region -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Region</label>
					<select id="region" class="form-select form-select-sm">
						<option value="All">All Regions</option>
						@foreach($regions as $region)
						<option value="{{ $region->id }}">{{ $region->region_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Territory -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Territory</label>
					<select id="territory" class="form-select form-select-sm">
						<option value="All">All Territories</option>
						@foreach($territories as $territory)
						<option value="{{ $territory->id }}">{{ $territory->territory_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Party Type -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Party Type</label>
					<select id="requisition_type" class="form-select form-select-sm">
						<option value="All">All Types</option>
						<option value="Contractual">Contractual</option>
						<option value="TFA">TFA</option>
						<option value="CB">CB</option>
					</select>
				</div>

				<!-- Preview Button -->
				<div class="col-md-3 col-lg-2">
					<button class="btn btn-primary btn-sm" onclick="loadReportPreview()">
						<i class="ri-eye-line"></i> Generate Report
					</button>
				</div>

				<!-- Export Button -->
				<div class="col-md-3 col-lg-2 ms-auto">
					<button class="btn btn-success btn-sm" onclick="exportReport()" id="exportBtn" disabled>
						<i class="ri-file-excel-2-line"></i> Export Excel
					</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Report Preview -->
	<div class="card shadow-sm" id="reportPreview" style="display: none;">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h5 class="mb-0">Management Report Preview</h5>
				<div class="text-muted small" id="reportSummary"></div>
			</div>

			<div class="table-responsive">
				<table class="table table-bordered table-hover mb-0" id="reportTable">
					<thead class="table-light">
						<tr>
							<th rowspan="2" class="align-middle">S No.</th>
							<th rowspan="2" class="align-middle">PC</th>
							<th rowspan="2" class="align-middle">Name</th>
							<th colspan="12" class="text-center">Monthly Remuneration</th>
							<th rowspan="2" class="align-middle">Grand Total</th>
						</tr>
						<tr>
							<th class="text-center">January</th>
							<th class="text-center">February</th>
							<th class="text-center">March</th>
							<th class="text-center">April</th>
							<th class="text-center">May</th>
							<th class="text-center">June</th>
							<th class="text-center">July</th>
							<th class="text-center">August</th>
							<th class="text-center">September</th>
							<th class="text-center">October</th>
							<th class="text-center">November</th>
							<th class="text-center">December</th>
						</tr>
					</thead>
					<tbody id="reportData">
						<!-- Data will be loaded here -->
					</tbody>
					<tfoot id="reportFooter" style="display: none;">
						<!-- Grand totals will be calculated here -->
					</tfoot>
				</table>
			</div>

			<div class="mt-3 text-center" id="noDataMessage" style="display: none;">
				<div class="alert alert-info">
					<i class="ri-information-line"></i>
					No data found for the selected filters.
				</div>
			</div>
		</div>
	</div>

	<!-- Loading Spinner -->
	<div class="text-center py-5" id="loadingSpinner" style="display: none;">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
		<p class="mt-2">Loading report data...</p>
	</div>

</div>
@endsection

@section('script_section')
<script>
	let currentYear = null;
	let currentFilters = {};

	function refreshPage() {
		location.reload();
	}

	function loadReportPreview() {
		// Collect all filter values
		currentFilters = {
			year: $('#year').val(),
			department: $('#department').val(),
			bu: $('#bu').val(),
			zone: $('#zone').val(),
			region: $('#region').val(),
			territory: $('#territory').val(),
			requisition_type: $('#requisition_type').val()
		};

		if (!currentFilters.year) {
			toastr.error('Please select year');
			return;
		}

		// Show loading
		$('#reportPreview').hide();
		$('#loadingSpinner').show();
		$('#exportBtn').prop('disabled', true);

		// Make AJAX call to get report data
		$.ajax({
			url: "{{ route('salary.management.report.data') }}",
			method: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				...currentFilters
			},
			success: function(response) {
				if (response.success) {
					renderReportData(response.data, response.monthly_totals);
					$('#loadingSpinner').hide();
					$('#reportPreview').show();
					$('#exportBtn').prop('disabled', false);

					// Update summary
					const filterSummary = [];
					if (currentFilters.requisition_type !== 'All') {
						filterSummary.push(currentFilters.requisition_type);
					}
					if (currentFilters.department !== 'All') {
						const deptName = $('#department option:selected').text();
						filterSummary.push(deptName);
					}

					const summary = filterSummary.length > 0 ?
						`Showing ${response.count} employees for ${currentFilters.year} (${filterSummary.join(', ')})` :
						`Showing ${response.count} employees for ${currentFilters.year}`;

					$('#reportSummary').text(summary);

					if (response.count === 0) {
						$('#reportTable').hide();
						$('#noDataMessage').show();
						$('#exportBtn').prop('disabled', true);
					} else {
						$('#reportTable').show();
						$('#noDataMessage').hide();
					}
				} else {
					toastr.error('Failed to load report data');
					$('#loadingSpinner').hide();
				}
			},
			error: function(xhr) {
				$('#loadingSpinner').hide();
				if (xhr.responseJSON && xhr.responseJSON.message) {
					toastr.error(xhr.responseJSON.message);
				} else {
					toastr.error('Failed to load report data');
				}
			}
		});
	}

	function renderReportData(data, monthlyTotals) {
		const tbody = $('#reportData');
		const tfoot = $('#reportFooter');
		tbody.empty();
		tfoot.empty();

		if (data.length === 0) {
			tbody.html(`
                <tr>
                    <td colspan="16" class="text-center py-4 text-muted">
                        No data found for selected filters
                    </td>
                </tr>
            `);
			tfoot.hide();
			return;
		}

		// Render employee rows
		data.forEach((employee, index) => {
			const row = `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${employee.code}</td>
                    <td>${employee.name}</td>
                    <td class="text-end">${formatCurrency(employee.january)}</td>
                    <td class="text-end">${formatCurrency(employee.february)}</td>
                    <td class="text-end">${formatCurrency(employee.march)}</td>
                    <td class="text-end">${formatCurrency(employee.april)}</td>
                    <td class="text-end">${formatCurrency(employee.may)}</td>
                    <td class="text-end">${formatCurrency(employee.june)}</td>
                    <td class="text-end">${formatCurrency(employee.july)}</td>
                    <td class="text-end">${formatCurrency(employee.august)}</td>
                    <td class="text-end">${formatCurrency(employee.september)}</td>
                    <td class="text-end">${formatCurrency(employee.october)}</td>
                    <td class="text-end">${formatCurrency(employee.november)}</td>
                    <td class="text-end">${formatCurrency(employee.december)}</td>
                    <td class="text-end fw-bold">${formatCurrency(employee.grand_total)}</td>
                </tr>
            `;
			tbody.append(row);
		});

		// Render footer with totals
		if (monthlyTotals) {
			const footerRow = `
                <tr class="table-light fw-bold">
                    <td colspan="3" class="text-center">Grand Total</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.january)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.february)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.march)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.april)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.may)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.june)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.july)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.august)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.september)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.october)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.november)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.december)}</td>
                    <td class="text-end">${formatCurrency(monthlyTotals.grand_total)}</td>
                </tr>
            `;
			tfoot.html(footerRow);
			tfoot.show();
		} else {
			tfoot.hide();
		}
	}

	function formatCurrency(amount) {
		if (amount === 0 || amount === '0.00') return '-';
		return '₹ ' + parseFloat(amount).toLocaleString('en-IN', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		});
	}

	function exportReport() {
		if (!currentFilters.year) {
			toastr.error('Please generate report first');
			return;
		}

		Swal.fire({
			title: 'Export Management Report',
			html: `Export management report for <b>${currentFilters.year}</b>?`,
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Download Excel',
			confirmButtonColor: '#198754',
		}).then((result) => {
			if (!result.isConfirmed) return;

			let url = "{{ route('salary.export.management.report') }}";
			const params = new URLSearchParams();

			Object.keys(currentFilters).forEach(key => {
				if (currentFilters[key] && currentFilters[key] !== 'All') {
					params.append(key, currentFilters[key]);
				}
			});

			// SAME TAB download → session preserved
			window.location.href = url + '?' + params.toString();

			toastr.success('Report export started');
		});
	}


	// Auto-load current year data on page load
	$(document).ready(function() {
		loadReportPreview();
	});
</script>
@endsection

@push('styles')
<style>
	.table th {
		white-space: nowrap;
		font-size: 0.85rem;
	}

	.table td {
		font-size: 0.85rem;
		vertical-align: middle;
	}

	#reportTable {
		font-size: 0.8rem;
	}

	#reportTable thead th {
		background-color: #2c3e50;
		color: white;
		border-color: #2c3e50;
	}

	.table-dark {
		background-color: #343a40 !important;
	}

	.form-select-sm {
		font-size: 0.85rem;
	}
</style>
@endpush