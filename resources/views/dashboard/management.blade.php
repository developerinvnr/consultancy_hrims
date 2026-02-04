@extends('layouts.guest')

@section('page-title', 'Management Dashboard')


@section('content')
<div class="container-fluid px-3 py-3">
	
	<!-- Filters Card -->
	<div class="card filter-card border-0 shadow-sm mb-2">
		<div class="card-body p-3">
			<div class="row g-2 align-items-end">
				<!-- Year -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Year</label>
					<select id="filterYear" class="form-select form-select-sm">
						@for($i = date('Y'); $i >= 2026; $i--)
						<option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
						@endfor
					</select>
				</div>

				<!-- Month -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Month</label>
					<select id="filterMonth" class="form-select form-select-sm">
						<option value="">All Months</option>
						@foreach(range(1, 12) as $month)
						<option value="{{ $month }}" {{ $month == date('m') ? 'selected' : '' }}>
							{{ DateTime::createFromFormat('!m', $month)->format('F') }}
						</option>
						@endforeach
					</select>
				</div>

				<!-- Department -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Department</label>
					<select id="filterDepartment" class="form-select form-select-sm select2">
						<option value="All">All Departments</option>
						@foreach($departments as $dept)
						<option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Business Unit -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Business Unit</label>
					<select id="filterBU" class="form-select form-select-sm select2">
						<option value="All">All BUs</option>
						@foreach($businessUnits as $bu)
						<option value="{{ $bu->id }}">{{ $bu->business_unit_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Zone -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Zone</label>
					<select id="filterZone" class="form-select form-select-sm select2">
						<option value="All">All Zones</option>
						@foreach($zones as $zone)
						<option value="{{ $zone->id }}">{{ $zone->zone_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Region -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Region</label>
					<select id="filterRegion" class="form-select form-select-sm select2">
						<option value="All">All Regions</option>
						@foreach($regions as $region)
						<option value="{{ $region->id }}">{{ $region->region_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Territory -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Territory</label>
					<select id="filterTerritory" class="form-select form-select-sm select2">
						<option value="All">All Territories</option>
						@foreach($territories as $territory)
						<option value="{{ $territory->id }}">{{ $territory->territory_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Vertical -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Vertical</label>
					<select id="filterVertical" class="form-select form-select-sm select2">
						<option value="All">All Verticals</option>
						@foreach($verticals as $vertical)
						<option value="{{ $vertical->id }}">{{ $vertical->vertical_name }}</option>
						@endforeach
					</select>
				</div>

				<!-- Requisition Type -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<label class="form-label">Party Type</label>
					<select id="filterType" class="form-select form-select-sm">
						<option value="All">All Types</option>
						<option value="Contractual">Contractual</option>
						<option value="TFA">TFA</option>
						<option value="CB">CB</option>
					</select>
				</div>

				<!-- Apply Filters Button -->
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					<button class="btn btn-primary btn-sm w-100" onclick="loadDashboardData()">
						<i class="ri-filter-line me-1"></i> Apply Filters
					</button>
					
				</div>
				<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
					
					<button class="btn btn-sm btn-outline-secondary" onclick="exportDashboardData()">
						<i class="ri-download-line me-1"></i> Export
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Overview Statistics -->
	<div class="row mb-1" id="overviewStats">
		<!-- Will be loaded via AJAX -->
	</div>

	<!-- Charts Section -->
	<div class="row g-3">
		<!-- Monthly Trend Chart - Full width -->
		<div class="col-xl-12 col-lg-12 mb-3">
			<div class="card dashboard-card">
				<div class="card-header">
					<h6><i class="ri-line-chart-line me-2"></i>Monthly Trend Analysis</h6>
				</div>
				<div class="card-body p-3">
					<div id="monthlyTrendChart"></div>
				</div>
			</div>
		</div>

		<!-- Type Distribution -->
		<div class="col-xl-4 col-lg-4">
			<div class="card dashboard-card h-100">
				<div class="card-header">
					<h6><i class="ri-pie-chart-line me-2"></i>Party Type Distribution</h6>
				</div>
				<div class="card-body p-3">
					<div id="typeChart"></div>
				</div>
			</div>
		</div>

		<!-- Department Distribution -->
		<div class="col-xl-4 col-lg-4">
			<div class="card dashboard-card h-100">
				<div class="card-header">
					<h6><i class="ri-bar-chart-line me-2"></i>Top Departments</h6>
				</div>
				<div class="card-body p-3">
					<div id="departmentChart"></div>
				</div>
			</div>
		</div>

		<!-- Status Distribution -->
		<div class="col-xl-4 col-lg-4">
			<div class="card dashboard-card h-100">
				<div class="card-header">
					<h6><i class="ri-donut-chart-line me-2"></i>Status Distribution</h6>
				</div>
				<div class="card-body p-3">
					<div id="statusChart"></div>
				</div>
			</div>
		</div>

		<!-- Geographic Distribution -->
		<div class="col-xl-6 col-lg-6">
			<div class="card dashboard-card h-100">
				<div class="card-header">
					<h6><i class="ri-map-pin-line me-2"></i>Top Locations</h6>
				</div>
				<div class="card-body p-3">
					<div id="geoChart"></div>
				</div>
			</div>
		</div>

		<!-- Salary Expenditure -->
		<div class="col-xl-6 col-lg-6">
			<div class="card dashboard-card h-100">
				<div class="card-header">
					<h6><i class="ri-money-rupee-circle-line me-2"></i>Salary Expenditure Analysis</h6>
				</div>
				<div class="card-body p-3">
					<div class="row g-2 mb-4">
						<div class="col-md-4">
							<div class="salary-summary-card">
								<div class="value text-primary" id="totalSalary">₹0</div>
								<div class="label">Total Salary</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="salary-summary-card">
								<div class="value text-success" id="avgSalary">₹0</div>
								<div class="label">Average Salary</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="salary-summary-card">
								<div class="value text-warning" id="employeeCount">0</div>
								<div class="label">Total Employees</div>
							</div>
						</div>
					</div>
					<!-- Radial Gauge Container -->
					<div class="radial-gauge-container">
						<div id="salaryChart"></div>
						<div class="radial-gauge-center" id="radialGaugeCenter">
							<div class="radial-gauge-value" id="radialGaugeValue">₹0</div>
							<div class="radial-gauge-label">Current Month</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Floating Refresh Button -->
<button class="btn refresh-btn" onclick="loadDashboardData()" title="Refresh Dashboard">
	<i class="ri-refresh-line"></i>
</button>
<div id="loadingOverlay"
	class="position-fixed top-0 start-0 w-100 h-100 d-none"
	style="
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.95);
     ">
	<div class="text-center">
		<div class="spinner-border text-primary"
			style="width: 2.5rem; height: 2.5rem;"
			role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
		<h6 class="mt-3 text-muted">Loading Dashboard...</h6>
	</div>
</div>

@endsection

@section('script_section')
<script>
	// ApexCharts color scheme
	const chartColors = {
		primary: '#6366F1',
		success: '#10B981',
		danger: '#EF4444',
		warning: '#F59E0B',
		info: '#3B82F6',
		secondary: '#6B7280',
		primaryLight: '#818CF8',
		successLight: '#34D399',
		dangerLight: '#F87171',
		warningLight: '#FBBF24',
		infoLight: '#60A5FA'
	};

	// Global chart instances
	let monthlyTrendChart = null;
	let departmentChart = null;
	let typeChart = null;
	let statusChart = null;
	let geoChart = null;
	let salaryChart = null;

	// ApexCharts default options
	const chartOptions = {
		chart: {
			fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
			toolbar: {
				show: true,
				tools: {
					download: true,
					selection: false,
					zoom: false,
					zoomin: false,
					zoomout: false,
					pan: false,
					reset: false
				}
			}
		},
		colors: [
			chartColors.primary,
			chartColors.success,
			chartColors.danger,
			chartColors.warning,
			chartColors.info,
			chartColors.secondary
		],
		dataLabels: {
			enabled: false
		},
		grid: {
			borderColor: '#F3F4F6',
			strokeDashArray: 3
		},
		stroke: {
			curve: 'smooth',
			width: 2
		},
		xaxis: {
			labels: {
				style: {
					colors: '#6B7280',
					fontSize: '11px'
				}
			},
			axisBorder: {
				show: false
			}
		},
		yaxis: {
			labels: {
				style: {
					colors: '#6B7280',
					fontSize: '11px'
				}
			}
		},
		legend: {
			fontSize: '12px',
			labels: {
				colors: '#6B7280'
			}
		},
		tooltip: {
			theme: 'light',
			style: {
				fontSize: '12px'
			}
		}
	};

	$(document).ready(function() {
		// Initialize Select2
		$('.select2').select2({
			theme: 'bootstrap-5',
			width: '100%',
			minimumResultsForSearch: 5
		});

		// Configure Toastr
		toastr.options = {
			"closeButton": true,
			"progressBar": true,
			"positionClass": "toast-top-right",
			"timeOut": "3000"
		};

		// Load initial data
		loadDashboardData();
	});

	function loadDashboardData() {
		showLoading();

		const filters = {
			year: $('#filterYear').val(),
			month: $('#filterMonth').val(),
			department: $('#filterDepartment').val(),
			bu: $('#filterBU').val(),
			zone: $('#filterZone').val(),
			region: $('#filterRegion').val(),
			territory: $('#filterTerritory').val(),
			vertical: $('#filterVertical').val(),
			requisition_type: $('#filterType').val()
		};

		$.ajax({
			url: '{{ route("dashboard.management.data") }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				...filters
			},
			dataType: 'json',
			timeout: 30000,

			success: function(response) {
				if (response.success) {
					updateDashboard(response.data);
					toastr.success('Dashboard updated successfully');
				} else {
					toastr.error('Failed to load dashboard data');
				}
			},

			error: function(xhr, status, error) {
				console.error('Dashboard AJAX error:', xhr.responseText);

				if (xhr.status === 0) {
					toastr.error('Network error. Please check your connection.');
				} else if (xhr.status === 500) {
					toastr.error('Server error. Please try again later.');
				} else {
					toastr.error('Failed to load dashboard data');
				}
			},

			complete: function() {
				hideLoading();
			}
		});
	}

	function showLoading() {
		console.log('Showing loading overlay');
		const overlay = $('#loadingOverlay');
		overlay.removeClass('d-none hide').addClass('d-flex show');
		overlay.css({
			'display': 'flex',
			'opacity': '1'
		});
	}

	function hideLoading() {
		console.log('Hiding loading overlay');
		const overlay = $('#loadingOverlay');
		overlay.removeClass('show d-flex').addClass('hide d-none');
		overlay.css({
			'display': 'none',
			'opacity': '0'
		});

		// Force hide after delay
		setTimeout(() => {
			if (overlay.is(':visible')) {
				overlay.hide();
				overlay.css('display', 'none');
			}
		}, 100);
	}

	function updateDashboard(data) {
		try {
			// Update period text
			const monthText = data.period.month && data.period.month !== 'Whole Year' ?
				data.period.month :
				'';
			const periodText = monthText ?
				`${monthText} ${data.period.year}` :
				`Year ${data.period.year}`;

			$('#dashboardPeriod').html(`
                <i class="ri-calendar-line me-1"></i>
                Showing data for <strong>${periodText}</strong>
            `);

			// Update overview statistics
			updateOverviewStats(data.overview);

			// Update charts
			if (data.monthly_trend?.length > 0) {
				updateMonthlyTrendChart(data.monthly_trend);
			} else {
				showNoDataMessage('monthlyTrendChart', 'Monthly Trend');
			}

			if (data.department_distribution?.length > 0) {
				updateDepartmentChart(data.department_distribution);
			} else {
				showNoDataMessage('departmentChart', 'Department Distribution');
			}

			if (data.type_distribution?.length > 0) {
				updateTypeChart(data.type_distribution);
			} else {
				showNoDataMessage('typeChart', 'Type Distribution');
			}

			if (data.status_distribution?.length > 0) {
				updateStatusChart(data.status_distribution);
			} else {
				showNoDataMessage('statusChart', 'Status Distribution');
			}

			if (data.geographic_distribution?.length > 0) {
				updateGeoChart(data.geographic_distribution);
			} else {
				showNoDataMessage('geoChart', 'Geographic Distribution');
			}

			if (data.salary_expenditure) {
				updateSalaryChart(data.salary_expenditure);
			} else {
				showNoDataMessage('salaryChart', 'Salary Data');
			}

		} catch (error) {
			console.error('Error updating dashboard:', error);
			toastr.error('Error updating dashboard display');
		}
	}

	function showNoDataMessage(chartId, title) {
		const element = document.getElementById(chartId);
		if (!element) return;

		// Destroy existing chart
		switch (chartId) {
			case 'monthlyTrendChart':
				if (monthlyTrendChart) monthlyTrendChart.destroy();
				break;
			case 'departmentChart':
				if (departmentChart) departmentChart.destroy();
				break;
			case 'typeChart':
				if (typeChart) typeChart.destroy();
				break;
			case 'statusChart':
				if (statusChart) statusChart.destroy();
				break;
			case 'geoChart':
				if (geoChart) geoChart.destroy();
				break;
			case 'salaryChart':
				if (salaryChart) salaryChart.destroy();
				break;
		}

		// Show no data message
		element.innerHTML = `
            <div class="no-data-message">
                <i class="ri-bar-chart-box-line"></i>
                <h6>No ${title} Available</h6>
                <p>No data found for the selected filters</p>
            </div>
        `;
	}

	function updateOverviewStats(stats) {
		const html = `
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card stat-card created">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value text-primary">${stats.total_created}</div>
                                <div class="stat-label">Total Created</div>
                            </div>
                            <div class="stat-icon" style="background-color: var(--primary-light); color: var(--primary);">
                                <i class="ri-user-add-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card stat-card active">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value text-success">${stats.total_active}</div>
                                <div class="stat-label">Active Parties</div>
                            </div>
                            <div class="stat-icon" style="background-color: var(--success-light); color: var(--success);">
                                <i class="ri-user-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card stat-card deactivated">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value text-danger">${stats.total_deactivated}</div>
                                <div class="stat-label">Deactivated</div>
                            </div>
                            <div class="stat-icon" style="background-color: var(--danger-light); color: var(--danger);">
                                <i class="ri-user-unfollow-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card stat-card remaining">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value text-warning">${stats.total_remaining}</div>
                                <div class="stat-label">Remaining</div>
                            </div>
                            <div class="stat-icon" style="background-color: var(--warning-light); color: var(--warning);">
                                <i class="ri-time-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

		$('#overviewStats').html(html);
	}

	function updateMonthlyTrendChart(data) {
		const options = {
			...chartOptions,
			chart: {
				...chartOptions.chart,
				type: 'line',
				height: 300,
				toolbar: {
					...chartOptions.chart.toolbar,
					show: true
				}
			},
			series: [{
					name: 'Created',
					data: data.map(item => item.created)
				},
				{
					name: 'Activated',
					data: data.map(item => item.activated)
				},
				{
					name: 'Deactivated',
					data: data.map(item => item.deactivated)
				}
			],
			colors: [chartColors.primary, chartColors.success, chartColors.danger],
			stroke: {
				curve: 'smooth',
				width: 2
			},
			xaxis: {
				categories: data.map(item => item.month),
				labels: {
					style: {
						colors: '#6B7280',
						fontSize: '11px'
					}
				}
			},
			yaxis: {
				labels: {
					formatter: function(val) {
						return Math.round(val);
					}
				}
			},
			tooltip: {
				theme: 'light',
				y: {
					formatter: function(val) {
						return val;
					}
				}
			},
			legend: {
				position: 'top',
				horizontalAlign: 'right',
				fontSize: '12px'
			}
		};

		if (monthlyTrendChart) {
			monthlyTrendChart.updateOptions(options);
		} else {
			monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), options);
			monthlyTrendChart.render();
		}
	}

	function updateDepartmentChart(data) {
		// Sort and take top 10 departments
		const sortedData = [...data].sort((a, b) => b.count - a.count).slice(0, 10);

		const options = {
			...chartOptions,
			chart: {
				...chartOptions.chart,
				type: 'bar',
				height: 280
			},
			series: [{
				name: 'Active Parties',
				data: sortedData.map(item => item.count)
			}],
			colors: [chartColors.info],
			plotOptions: {
				bar: {
					borderRadius: 4,
					horizontal: false,
					columnWidth: '60%'
				}
			},
			xaxis: {
				categories: sortedData.map(item => item.department_name),
				labels: {
					style: {
						colors: '#6B7280',
						fontSize: '10px'
					},
					rotate: -45,
					rotateAlways: true
				}
			},
			yaxis: {
				labels: {
					formatter: function(val) {
						return Math.round(val);
					}
				}
			},
			tooltip: {
				y: {
					formatter: function(val) {
						return val + ' parties';
					}
				}
			}
		};

		if (departmentChart) {
			departmentChart.updateOptions(options);
		} else {
			departmentChart = new ApexCharts(document.querySelector("#departmentChart"), options);
			departmentChart.render();
		}
	}

	function updateTypeChart(data) {
		const options = {
			...chartOptions,
			chart: {
				...chartOptions.chart,
				type: 'donut',
				height: 280
			},
			series: data.map(item => item.count),
			labels: data.map(item => item.requisition_type),
			colors: [
				chartColors.primary,
				chartColors.success,
				chartColors.warning,
				chartColors.info,
				chartColors.danger
			],
			legend: {
				position: 'bottom',
				fontSize: '11px'
			},
			plotOptions: {
				pie: {
					donut: {
						size: '65%',
						labels: {
							show: true,
							name: {
								show: true,
								fontSize: '12px',
								color: '#6B7280'
							},
							value: {
								show: true,
								fontSize: '16px',
								fontWeight: '600',
								color: '#1F2937'
							}
						}
					}
				}
			},
			responsive: [{
				breakpoint: 480,
				options: {
					chart: {
						height: 240
					},
					legend: {
						position: 'bottom'
					}
				}
			}]
		};

		if (typeChart) {
			typeChart.updateOptions(options);
		} else {
			typeChart = new ApexCharts(document.querySelector("#typeChart"), options);
			typeChart.render();
		}
	}

	function updateStatusChart(data) {
		const options = {
			...chartOptions,
			chart: {
				...chartOptions.chart,
				type: 'pie',
				height: 280
			},
			series: data.map(item => item.count),
			labels: data.map(item => item.candidate_status),
			colors: [
				chartColors.success,
				chartColors.danger,
				chartColors.warning,
				chartColors.info,
				chartColors.primary
			],
			legend: {
				position: 'bottom',
				fontSize: '11px'
			},
			responsive: [{
				breakpoint: 480,
				options: {
					chart: {
						height: 240
					},
					legend: {
						position: 'bottom'
					}
				}
			}]
		};

		if (statusChart) {
			statusChart.updateOptions(options);
		} else {
			statusChart = new ApexCharts(document.querySelector("#statusChart"), options);
			statusChart.render();
		}
	}

	function updateGeoChart(data) {
		// Sort by count and take top 8
		const sortedData = [...data].sort((a, b) => b.count - a.count).slice(0, 8);

		const options = {
			...chartOptions,
			chart: {
				...chartOptions.chart,
				type: 'bar',
				height: 280
			},
			series: [{
				name: 'Active Parties',
				data: sortedData.map(item => item.count)
			}],
			colors: [chartColors.primaryLight],
			plotOptions: {
				bar: {
					borderRadius: 4,
					horizontal: true
				}
			},
			xaxis: {
				labels: {
					formatter: function(val) {
						return Math.round(val);
					}
				}
			},
			yaxis: {
				categories: sortedData.map(item => {
					const location = item.location || 'Unknown';
					return location.length > 25 ? location.substring(0, 25) + '...' : location;
				}),
				labels: {
					style: {
						fontSize: '10px'
					}
				}
			},
			tooltip: {
				y: {
					formatter: function(val) {
						return val + ' parties';
					}
				}
			}
		};

		if (geoChart) {
			geoChart.updateOptions(options);
		} else {
			geoChart = new ApexCharts(document.querySelector("#geoChart"), options);
			geoChart.render();
		}
	}

	function updateSalaryChart(data) {

    const totalSalary   = Number(data?.total_salary || 0);
    const avgSalary     = Number(data?.avg_monthly_salary || data?.avg_salary || 0);
    const employeeCount = Number(data?.employee_count || 0);

    // Update summary boxes ALWAYS
    $('#totalSalary').text(formatCurrency(totalSalary));
    $('#avgSalary').text(formatCurrency(avgSalary));
    $('#employeeCount').text(employeeCount);

    // Clean container before rendering
    $('#salaryChart').empty();

    // 🔴 CASE 1: Absolutely no salary data
    if (totalSalary === 0 && employeeCount === 0) {
        $('#radialGaugeCenter').hide();
        $('#salaryChart').html(`
            <div class="no-data-message">
                <i class="ri-money-rupee-circle-line"></i>
                <h6>No Salary Data Available</h6>
                <p>No data found for the selected filters</p>
            </div>
        `);
        return;
    }

    // 🟢 CASE 2: Monthly trend exists → Line chart
    if (Array.isArray(data.monthly_data) && data.monthly_data.length > 0) {

        $('#radialGaugeCenter').hide();

        const options = {
            ...chartOptions,
            chart: {
                ...chartOptions.chart,
                type: 'line',
                height: 200,
                toolbar: { show: false }
            },
            series: [{
                name: 'Monthly Salary',
                data: data.monthly_data.map(m => m.total_salary || 0)
            }],
            xaxis: {
                categories: data.monthly_data.map(m => m.month)
            },
            colors: [chartColors.danger]
        };

        salaryChart = new ApexCharts(
            document.querySelector('#salaryChart'),
            options
        );
        salaryChart.render();
        return;
    }

    // 🟡 CASE 3: Only total salary → Radial gauge
    $('#radialGaugeCenter').show();
    $('#radialGaugeValue').text(formatCurrencyShort(totalSalary));

    const options = {
        ...chartOptions,
        chart: {
            ...chartOptions.chart,
            type: 'radialBar',
            height: 200,
            toolbar: { show: false }
        },
        series: [100],
        colors: [chartColors.danger],
        plotOptions: {
            radialBar: {
                hollow: { size: '70%' },
                dataLabels: { show: false }
            }
        }
    };

    salaryChart = new ApexCharts(
        document.querySelector('#salaryChart'),
        options
    );
    salaryChart.render();
}


	function formatCurrency(amount) {
		if (!amount) return '₹0';

		if (amount >= 10000000) {
			return '₹' + (amount / 10000000).toFixed(2) + ' Cr';
		} else if (amount >= 100000) {
			return '₹' + (amount / 100000).toFixed(2) + ' L';
		} else if (amount >= 1000) {
			return '₹' + (amount / 1000).toFixed(2) + ' K';
		}
		return '₹' + amount.toFixed(0);
	}

	function formatCurrencyShort(amount) {
		if (!amount) return '₹0';

		if (amount >= 10000000) {
			return '₹' + (amount / 10000000).toFixed(1) + 'Cr';
		} else if (amount >= 100000) {
			return '₹' + (amount / 100000).toFixed(1) + 'L';
		} else if (amount >= 1000) {
			return '₹' + (amount / 1000).toFixed(0) + 'K';
		}
		return '₹' + amount.toFixed(0);
	}

	function exportDashboardData() {
		const filters = {
			year: $('#filterYear').val(),
			month: $('#filterMonth').val(),
			department: $('#filterDepartment').val(),
			bu: $('#filterBU').val(),
			zone: $('#filterZone').val(),
			region: $('#filterRegion').val(),
			territory: $('#filterTerritory').val(),
			vertical: $('#filterVertical').val(),
			requisition_type: $('#filterType').val()
		};

		const params = new URLSearchParams();
		Object.keys(filters).forEach(key => {
			if (filters[key] && filters[key] !== 'All') {
				params.append(key, filters[key]);
			}
		});
		window.location.href = '/hr/salary/export-management-report?' + params.toString();
	}

	// Auto-refresh every 5 minutes
	setInterval(loadDashboardData, 300000);
</script>
@endsection