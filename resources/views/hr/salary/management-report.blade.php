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
					<label>Financial Year</label>
					<select id="financial_year" class="form-select form-select-sm">
						@php
						$currentYear = date('Y');
						$currentMonth = date('n');

						if ($currentMonth >= 4) {
						$defaultStart = $currentYear;
						} else {
						$defaultStart = $currentYear - 1;
						}

						for ($i = $defaultStart; $i >= 2025; $i--) {
						$fy = $i . '-' . ($i + 1);
						@endphp
						<option value="{{ $fy }}" {{ $i == $defaultStart ? 'selected' : '' }}>
							{{ $fy }}
						</option>
						@php } @endphp
					</select>
				</div>

				<!-- Employee -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Manager / Employee</label>
					<select id="employee" class="form-select form-select-sm"
						{{ count($employee_list) == 1 ? 'disabled' : '' }} onchange="loadDepartmentsByEmployee()">

						@if(count($employee_list) > 1)
						<option value="All">All Managers</option>
						@endif

						@foreach($employee_list as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>

				<!-- Department (will be populated dynamically) -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Department</label>
					<select id="department" class="form-select form-select-sm" onchange="loadSubDepartmentsByDepartment()">
						<option value="All">All Departments</option>
						@foreach($departments as $key => $value)
						@if($key !== 'All')
						<option value="{{ $key }}">{{ $value }}</option>
						@endif
						@endforeach
					</select>
				</div>

				<!-- Vertical -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Vertical</label>
					<select id="vertical" class="form-select form-select-sm">
						<option value="All">All Verticals</option>
						@foreach($verticals as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>

				<!-- Sub Department (will be populated based on department) -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Sub Department</label>
					<select id="sub_department" class="form-select form-select-sm">
						<option value="All">All Sub Departments</option>
					</select>
				</div>

				<!-- BU -->
				@if($access_level == 'bu' || $access_level == 'all')
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">BU</label>
					<select id="bu" class="form-select form-select-sm">
						<option value="All">All Business Units</option>
						@foreach($bu_list as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>
				@endif

				<!-- Zone -->
				@if($access_level == 'zone' || $access_level == 'bu' || $access_level == 'all')
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Zone</label>
					<select id="zone" class="form-select form-select-sm">
						<option value="All">All Zones</option>
						@foreach($zone_list as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>
				@endif

				<!-- Region -->
				@if($access_level == 'region' || $access_level == 'zone' || $access_level == 'bu' || $access_level == 'all')
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Region</label>
					<select id="region" class="form-select form-select-sm">
						<option value="All">All Regions</option>
						@foreach($region_list as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>
				@endif

				<!-- Territory -->
				@if($access_level == 'territory' || $access_level == 'region' || $access_level == 'zone' || $access_level == 'bu' || $access_level == 'all')
				<div class="col-md-3 col-lg-2">
					<label class="form-label form-label-sm mb-1">Territory</label>
					<select id="territory" class="form-select form-select-sm">
						<option value="All">All Territories</option>
						@foreach($territory_list as $key => $value)
						<option value="{{ $key }}">{{ $value }}</option>
						@endforeach
					</select>
				</div>
				@endif

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

			<div class="table-responsive management-table-wrapper">
				<table class="table table-bordered table-hover mb-0" id="reportTable">
						<thead class="table-light">
							<tr>
								<th rowspan="2" class="align-middle">S No.</th>
								<th rowspan="2" class="align-middle">PC</th>
								<th rowspan="2" class="align-middle">Name</th>
								<th rowspan="2" class="align-middle">Contract Start Date</th>
								<th rowspan="2" class="align-middle">Contract End Date</th>
								<th rowspan="2" class="align-middle">Termination Date</th>
								<th rowspan="2" class="align-middle">Grand Total</th>
								<th colspan="12" class="text-center">Monthly Remuneration (Financial Year Order)</th>
							</tr>
							<tr>
								<th class="text-center">April</th>
								<th class="text-center">May</th>
								<th class="text-center">June</th>
								<th class="text-center">July</th>
								<th class="text-center">August</th>
								<th class="text-center">September</th>
								<th class="text-center">October</th>
								<th class="text-center">November</th>
								<th class="text-center">December</th>
								<th class="text-center">January</th>
								<th class="text-center">February</th>
								<th class="text-center">March</th>
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

@push('scripts')

<script>
	let currentFilters = {};

	function refreshPage() {
		location.reload();
	}

	function loadReportPreview() {
		// Collect all filter values
		currentFilters = {
			financial_year: $('#financial_year').val(),
			requisition_type: $('#requisition_type').val()
		};

		let department = $('#department').val();
		if (department && department !== 'All') {
			currentFilters.department = department;
		}

		let employee = $('#employee').val();
		if (employee && employee !== 'All') {
			currentFilters.employee = employee;
		}

		let vertical = $('#vertical').val();
		if (vertical && vertical !== 'All') {
			currentFilters.vertical = vertical;
		}

		let subDepartment = $('#sub_department').val();
		if (subDepartment && subDepartment !== 'All') {
			currentFilters.sub_department = subDepartment;
		}

		if ($('#bu').length) {
			let bu = $('#bu').val();
			if (bu && bu !== 'All') currentFilters.bu = bu;
		}

		if ($('#zone').length) {
			let zone = $('#zone').val();
			if (zone && zone !== 'All') currentFilters.zone = zone;
		}

		if ($('#region').length) {
			let region = $('#region').val();
			if (region && region !== 'All') currentFilters.region = region;
		}

		if ($('#territory').length) {
			let territory = $('#territory').val();
			if (territory && territory !== 'All') currentFilters.territory = territory;
		}

		if (!currentFilters.financial_year) {
			toastr.error('Please select financial year');
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
					if (currentFilters.department && currentFilters.department !== 'All') {
						const deptName = $('#department option:selected').text();
						filterSummary.push(deptName);
					}

					const summary = filterSummary.length > 0 ?
						`Showing ${response.count} parties for FY ${currentFilters.financial_year} (${filterSummary.join(', ')})` :
						`Showing ${response.count} parties for FY ${currentFilters.financial_year}`;

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
    tfoot.hide();

    if (data.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="19" class="text-center py-4 text-muted">
                    No data found for selected filters
                </td>
            </tr>
        `);
        return;
    }

    // Add Grand Total Row as FIRST DATA ROW
    if (monthlyTotals) {
        const grandTotalRow = `
            <tr class="table-primary fw-bold" style="background-color: #e8f4fd;">
                <td class="text-center">—</td>
                <td>—</td>
                <td><strong>GRAND TOTAL</strong></td>
                <td>—</td>
                <td>—</td>
                <td>—</td>
                <td class="text-end fw-bold">${formatCurrency(monthlyTotals.grand_total)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.april)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.may)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.june)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.july)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.august)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.september)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.october)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.november)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.december)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.january)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.february)}</td>
                <td class="text-end">${formatCurrency(monthlyTotals.march)}</td>
            </tr>
        `;
        tbody.append(grandTotalRow);
    }

    // Render individual employee rows
    data.forEach((employee, index) => {
        const row = `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>${employee.code}</td>
                <td>${employee.name}</td>
                <td>${employee.contract_start_date ?? '-'}</td>
                <td>${employee.contract_end_date ?? '-'}</td>
                <td>${employee.termination_date ?? '-'}</td>
                <td class="text-end fw-bold">${formatCurrency(employee.grand_total)}</td>
                <td class="text-end">${formatCurrency(employee.april)}</td>
                <td class="text-end">${formatCurrency(employee.may)}</td>
                <td class="text-end">${formatCurrency(employee.june)}</td>
                <td class="text-end">${formatCurrency(employee.july)}</td>
                <td class="text-end">${formatCurrency(employee.august)}</td>
                <td class="text-end">${formatCurrency(employee.september)}</td>
                <td class="text-end">${formatCurrency(employee.october)}</td>
                <td class="text-end">${formatCurrency(employee.november)}</td>
                <td class="text-end">${formatCurrency(employee.december)}</td>
                <td class="text-end">${formatCurrency(employee.january)}</td>
                <td class="text-end">${formatCurrency(employee.february)}</td>
                <td class="text-end">${formatCurrency(employee.march)}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

	function formatCurrency(amount) {
		if (amount === 0 || amount === '0.00' || amount === null || amount === undefined) return '-';
		return '₹ ' + parseFloat(amount).toLocaleString('en-IN', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		});
	}

	function exportReport() {
		if (!currentFilters.financial_year) {
			toastr.error('Please generate report first');
			return;
		}

		Swal.fire({
			title: 'Export Management Report',
			html: `Export management report for <b>${currentFilters.financial_year}</b>?`,
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

			window.location.href = url + '?' + params.toString();
			toastr.success('Report export started');
		});
	}

	// Hierarchy cascade functions
	$(document).on("change", "#bu", function() {
		let bu = $(this).val();
		$.ajax({
			url: "{{ route('hierarchy.zone.by.bu') }}",
			type: "POST",
			data: {
				_token: "{{ csrf_token() }}",
				bu: bu
			},
			success: function(response) {
				let zoneSelect = $('#zone');
				zoneSelect.empty();
				zoneSelect.append('<option value="All">All Zones</option>');
				$.each(response.zoneList, function(index, zone) {
					zoneSelect.append(`<option value="${zone.id}">${zone.zone_name}</option>`);
				});
				$('#region').empty().append('<option value="All">All Regions</option>');
				$('#territory').empty().append('<option value="All">All Territories</option>');
			}
		});
	});

	$(document).on("change", "#zone", function() {
		let zone = $(this).val();
		$.ajax({
			url: "{{ route('hierarchy.region.by.zone') }}",
			type: "POST",
			data: {
				_token: "{{ csrf_token() }}",
				zone: zone
			},
			success: function(response) {
				let regionSelect = $('#region');
				regionSelect.empty();
				regionSelect.append('<option value="All">All Regions</option>');
				$.each(response.regionList, function(index, region) {
					regionSelect.append(`<option value="${region.id}">${region.region_name}</option>`);
				});
				$('#territory').empty().append('<option value="All">All Territories</option>');
			}
		});
	});

	$(document).on("change", "#region", function() {
		let region = $(this).val();
		$.ajax({
			url: "{{ route('hierarchy.territory.by.region') }}",
			type: "POST",
			data: {
				_token: "{{ csrf_token() }}",
				region: region
			},
			success: function(response) {
				let territorySelect = $('#territory');
				territorySelect.empty();
				territorySelect.append('<option value="All">All Territories</option>');
				$.each(response.territoryList, function(index, territory) {
					territorySelect.append(`<option value="${territory.id}">${territory.territory_name}</option>`);
				});
			}
		});
	});

	function loadSubDepartmentsByDepartment() {
		let departmentId = $('#department').val();

		if (!departmentId || departmentId === 'All') {
			let subDeptSelect = $('#sub_department');
			subDeptSelect.empty();
			subDeptSelect.append('<option value="All">All Sub Departments</option>');
			return;
		}

		let subDeptSelect = $('#sub_department');
		subDeptSelect.prop('disabled', true);
		subDeptSelect.html('<option value="All">Loading sub-departments...</option>');

		$.ajax({
			url: "{{ route('salary.subdepartments.by.department') }}",
			method: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				department_id: departmentId
			},
			success: function(response) {
				if (response.success) {
					subDeptSelect.empty();
					subDeptSelect.append('<option value="All">All Sub Departments</option>');
					$.each(response.sub_departments, function(value, label) {
						if (value !== 'All') {
							subDeptSelect.append($('<option></option>').val(value).html(label));
						}
					});
					subDeptSelect.prop('disabled', false);
				} else {
					toastr.error('Failed to load sub-departments');
					resetSubDepartmentDropdown();
				}
			},
			error: function() {
				toastr.error('Failed to load sub-departments');
				resetSubDepartmentDropdown();
			}
		});
	}

	function resetSubDepartmentDropdown() {
		let subDeptSelect = $('#sub_department');
		subDeptSelect.prop('disabled', false);
		subDeptSelect.empty();
		subDeptSelect.append('<option value="All">All Sub Departments</option>');
	}

	function loadDepartmentsByEmployee() {
		let employeeId = $('#employee').val();
		let financialYear = $('#financial_year').val();

		if (!employeeId || employeeId === 'All') {
			resetDepartmentDropdown();
			return;
		}

		let departmentSelect = $('#department');
		departmentSelect.prop('disabled', true);
		departmentSelect.html('<option value="All">Loading departments...</option>');

		$.ajax({
			url: "{{ route('salary.departments.by.employee') }}",
			method: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				employee_id: employeeId,
				financial_year: financialYear
			},
			success: function(response) {
				if (response.success) {
					departmentSelect.empty();
					departmentSelect.append('<option value="All">All Departments</option>');
					$.each(response.departments, function(value, label) {
						if (value !== 'All') {
							departmentSelect.append($('<option></option>').val(value).html(label));
						}
					});
					departmentSelect.prop('disabled', false);
				} else {
					toastr.error('Failed to load departments');
					resetDepartmentDropdown();
				}
			},
			error: function() {
				toastr.error('Failed to load departments');
				resetDepartmentDropdown();
			}
		});
	}

	function resetDepartmentDropdown() {
		let departmentSelect = $('#department');
		departmentSelect.prop('disabled', false);
		departmentSelect.empty();
		departmentSelect.append('<option value="All">All Departments</option>');
		@foreach($departments as $key => $value)
		@if($key !== 'All')
		departmentSelect.append('<option value="{{ $key }}">{{ $value }}</option>');
		@endif
		@endforeach
	}

	$(document).ready(function() {
		loadReportPreview();

		$('#employee').on('change', function() {
			loadDepartmentsByEmployee();
		});

		$('#financial_year').on('change', function() {
			let employeeId = $('#employee').val();
			if (employeeId && employeeId !== 'All') {
				loadDepartmentsByEmployee();
			}
		});
	});
</script>
@endpush

<style>
	.management-table-wrapper {
		max-height: 70vh;
		overflow-y: auto;
	}

	.management-table-wrapper thead th {
		position: sticky;
		background: #f8f9fa;
		color: #000;
		z-index: 10;
	}

	.management-table-wrapper thead tr:first-child th {
		top: 0;
		height: 45px;
	}

	.management-table-wrapper thead tr:nth-child(2) th {
		top: 45px;
	}

	.table td.text-end,
	.table th.text-end {
		text-align: right;
	}
</style>