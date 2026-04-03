@extends('layouts.guest')
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Remuneration Report</h4>
            <a href="{{ route('reports.payment.export', request()->query()) }}" class="btn btn-sm btn-success">
                <i class="ri-file-excel-2-line"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <!-- Main Filters Card -->
            <div class="card mb-2 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.paymentReport') }}" class="row g-2 align-items-end" id="mainFilterForm">
                        {{--<div class="col-md-2">
                            <label class="form-label form-label-sm">Financial Year</label>
                            <select name="financial_year" class="form-select form-select-sm" id="financial_year">
                                <option value="">Select Financial Year</option>
                                <option value="2024-2025" {{ $financialYear=='2024-2025' ? 'selected' : '' }}>2024-2025</option>
                                <option value="2025-2026" {{ $financialYear=='2025-2026' ? 'selected' : '' }}>2025-2026</option>
                                <option value="2026-2027" {{ $financialYear=='2026-2027' ? 'selected' : '' }}>2026-2027</option>
                            </select>
                        </div>--}}

                        <div class="col-md-1">
                            <label class="form-label form-label-sm">Year</label>
                            <select name="year" class="form-select form-select-sm" id="year_select">
                                <option value="">All</option>
                                @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label form-label-sm">Month</label>
                            <select name="month" class="form-select form-select-sm" id="month_select">
                                <option value="">All</option>
                                <option value="1" {{ $month==1 ? 'selected' : '' }}>Jan</option>
                                <option value="2" {{ $month==2 ? 'selected' : '' }}>Feb</option>
                                <option value="3" {{ $month==3 ? 'selected' : '' }}>Mar</option>
                                <option value="4" {{ $month==4 ? 'selected' : '' }}>Apr</option>
                                <option value="5" {{ $month==5 ? 'selected' : '' }}>May</option>
                                <option value="6" {{ $month==6 ? 'selected' : '' }}>Jun</option>
                                <option value="7" {{ $month==7 ? 'selected' : '' }}>Jul</option>
                                <option value="8" {{ $month==8 ? 'selected' : '' }}>Aug</option>
                                <option value="9" {{ $month==9 ? 'selected' : '' }}>Sep</option>
                                <option value="10" {{ $month==10 ? 'selected' : '' }}>Oct</option>
                                <option value="11" {{ $month==11 ? 'selected' : '' }}>Nov</option>
                                <option value="12" {{ $month==12 ? 'selected' : '' }}>Dec</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Payment Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="pending" {{ $status=='pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processed" {{ $status=='processed' ? 'selected' : '' }}>Processed</option>
                                <option value="paid" {{ $status=='paid' ? 'selected' : '' }}>Paid</option>
                                <option value="held" {{ $status=='held' ? 'selected' : '' }}>Held</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Payment Mode</label>
                            <select name="payment_mode" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="bank_transfer" {{ $paymentMode=='bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cheque" {{ $paymentMode=='cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="cash" {{ $paymentMode=='cash' ? 'selected' : '' }}>Cash</option>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <button class="btn btn-sm btn-primary w-100" type="submit">
                                <i class="ri-filter-line"></i> Filter
                            </button>
                        </div>
                    </form>

                    <!-- Hierarchy Filters -->
                    <hr class="my-3">
                    <form method="GET" action="{{ route('reports.paymentReport') }}" class="row g-2 align-items-end mt-2" id="hierarchyFilterForm">
                        <!-- Preserve existing filters -->
                        <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="payment_mode" value="{{ $paymentMode }}">

                        <!-- Location Filters -->
                        @if($showLocationFilters)
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Business Unit</label>
                            <select name="bu" id="bu_filter" class="form-select form-select-sm">
                                <option value="All">All Business Units</option>
                                @foreach($businessUnits as $bid => $bname)
                                <option value="{{ $bid }}" {{ $bid == $buId ? 'selected' : '' }}>
                                    {{ $bname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Zone</label>
                            <select name="zone" id="zone_filter" class="form-select form-select-sm">
                                <option value="All">All Zones</option>
                                @foreach($zones as $zid => $zname)
                                <option value="{{ $zid }}" {{ $zid == $zoneId ? 'selected' : '' }}>
                                    {{ $zname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Region</label>
                            <select name="region" id="region_filter" class="form-select form-select-sm">
                                <option value="All">All Regions</option>
                                @foreach($regions as $rid => $rname)
                                <option value="{{ $rid }}" {{ $rid == $regionId ? 'selected' : '' }}>
                                    {{ $rname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Territory</label>
                            <select name="territory" id="territory_filter" class="form-select form-select-sm">
                                <option value="All">All Territories</option>
                                @foreach($territories as $tid => $tname)
                                <option value="{{ $tid }}" {{ $tid == $territoryId ? 'selected' : '' }}>
                                    {{ $tname }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Department Filter -->
                        <div class="col-md-2" id="department_filter_container" style="{{ $employeeId != 'All' ? '' : 'display: none;' }}">
                            <label class="form-label form-label-sm">Department</label>
                            <select name="department_id" id="department_filter" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                @if(!empty($departments) && count($departments) > 0)
                                @foreach($departments as $deptId => $deptName)
                                <option value="{{ $deptId }}" {{ $deptId == $departmentId ? 'selected' : '' }}>
                                    {{ $deptName }}
                                </option>
                                @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Sub Department Filter -->
                        <div class="col-md-2" id="sub_department_filter_container" style="{{ $employeeId != 'All' ? '' : 'display: none;' }}">
                            <label class="form-label form-label-sm">Sub Department</label>
                            <select name="sub_department" id="sub_department_filter" class="form-select form-select-sm">
                                @foreach($subDepartments as $sid => $sname)
                                <option value="{{ $sid }}" {{ $sid == $subDepartmentId ? 'selected' : '' }}>
                                    {{ $sname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Vertical</label>
                            <select name="vertical" id="vertical_filter" class="form-select form-select-sm">
                                <option value="All">All Verticals</option>
                                @foreach($verticals as $vid => $vname)
                                <option value="{{ $vid }}" {{ $vid == $verticalId ? 'selected' : '' }}>
                                    {{ $vname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Reporting Manager</label>
                            <select name="employee" id="employee_filter" class="form-select form-select-sm">
                                @foreach($employees as $eid => $ename)
                                <option value="{{ $eid }}" {{ $eid == $employeeId ? 'selected' : '' }}>
                                    {{ $ename }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-sm btn-secondary w-100" type="submit" id="applyFiltersBtn">
                                <i class="ri-filter-2-line"></i> Apply Filters
                            </button>
                        </div>

                        @if(($buId ?? 'All') != 'All' || ($zoneId ?? 'All') != 'All' || ($regionId ?? 'All') != 'All' || ($territoryId ?? 'All') != 'All' || ($verticalId ?? 'All') != 'All' || ($employeeId ?? 'All') != 'All' || ($subDepartmentId ?? 'All') != 'All' || $departmentId)
                        <div class="col-md-2">
                            <a href="{{ route('reports.paymentReport', array_merge(request()->except(['bu', 'zone', 'region', 'territory', 'vertical', 'employee', 'sub_department', 'department_id']), ['bu' => 'All', 'zone' => 'All', 'region' => 'All', 'territory' => 'All', 'vertical' => 'All', 'employee' => 'All', 'sub_department' => 'All', 'department_id' => ''])) }}"
                                class="btn btn-sm btn-outline-danger w-100" id="clearFiltersBtn">
                                <i class="ri-close-line"></i> Clear All Filters
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Active Hierarchy Filters Display -->
            @php
            $activeFilters = [];
            if($showLocationFilters && ($buId ?? 'All') != 'All') $activeFilters[] = 'Business Unit: ' . ($businessUnits[$buId] ?? $buId);
            if($showLocationFilters && ($zoneId ?? 'All') != 'All') $activeFilters[] = 'Zone: ' . ($zones[$zoneId] ?? $zoneId);
            if($showLocationFilters && ($regionId ?? 'All') != 'All') $activeFilters[] = 'Region: ' . ($regions[$regionId] ?? $regionId);
            if($showLocationFilters && ($territoryId ?? 'All') != 'All') $activeFilters[] = 'Territory: ' . ($territories[$territoryId] ?? $territoryId);
            if(($verticalId ?? 'All') != 'All') $activeFilters[] = 'Vertical: ' . ($verticals[$verticalId] ?? $verticalId);
            if(($subDepartmentId ?? 'All') != 'All') $activeFilters[] = 'Sub Department: ' . ($subDepartments[$subDepartmentId] ?? $subDepartmentId);
            if(($employeeId ?? 'All') != 'All') $activeFilters[] = 'Reporting Manager: ' . ($employees[$employeeId] ?? $employeeId);
            if($departmentId) $activeFilters[] = 'Department: ' . ($departments[$departmentId] ?? $departmentId);
            @endphp

            @if(!empty($activeFilters))
            <div class="alert alert-info alert-sm mb-3 mx-3">
                <i class="ri-filter-line me-2"></i>
                <strong>Active Filters:</strong> {{ implode(' | ', $activeFilters) }}
            </div>
            @endif

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="text-center py-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading filters...</p>
            </div>

            <!-- Summary KPI Cards -->
            <div class="kpi-container mb-3 px-3">
                <!-- Total Candidates Card -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-title">Total Candidates</div>
                        <div class="kpi-icon">👥</div>
                    </div>
                    <div class="kpi-body d-flex justify-content-between align-items-center">
                        <div class="kpi-number">{{ number_format($summary['total_candidates']) }}</div>
                        <div class="kpi-avg">Avg Net: ₹{{ number_format($summary['avg_net_pay'], 2) }}</div>
                    </div>
                    <div class="kpi-footer">
                        <span class="text-success">Paid: {{ $summary['paid_count'] }}</span>
                        <span class="text-warning">Processed: {{ $summary['processed_count'] }}</span>
                        <span class="text-danger">Pending: {{ $summary['pending_count'] }}</span>
                    </div>
                </div>

                <!-- Total Payable Card -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-title">Total Payable</div>
                        <div class="kpi-icon">💰</div>
                    </div>
                    <div class="kpi-body d-flex justify-content-between align-items-center">
                        <div class="kpi-number">₹{{ number_format($summary['total_payable'], 2) }}</div>
                        <div class="kpi-avg">Gross</div>
                    </div>
                    <div class="kpi-footer">
                        <span class="text-success">Net: ₹{{ number_format($summary['total_net_pay'], 2) }}</span>
                    </div>
                </div>

                <!-- Deductions Card -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-title">Total Deductions</div>
                        <div class="kpi-icon">📉</div>
                    </div>
                    <div class="kpi-body d-flex justify-content-between align-items-center">
                        <div class="kpi-number">₹{{ number_format($summary['total_deductions'], 2) }}</div>
                        <div class="kpi-avg">+ Arrear</div>
                    </div>
                    <div class="kpi-footer">
                        <span>Arrear: ₹{{ number_format($summary['total_arrear'], 2) }}</span>
                    </div>
                </div>

                <!-- Processing Status Card -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-title">Processing Status</div>
                        <div class="kpi-icon">⚙️</div>
                    </div>
                    <div class="kpi-body d-flex justify-content-between align-items-center">
                        <div class="kpi-number">{{ $summary['processing_release'] }}</div>
                        <div class="kpi-avg">Released</div>
                    </div>
                    <div class="kpi-footer">
                        <span>Pending: {{ $summary['processing_pending'] }}</span>
                        <span>Hold: {{ $summary['processing_hold'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-scroll">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Candidate Name</th>
                            <th>Req ID</th>
                            <th>Month/Year</th>
                            <th>Department</th>
                            <th>Reporting Manager</th>
                            <th>Monthly Salary</th>
                            <th>Paid Days</th>
                            <th>Net Pay</th>
                            <th>Processing Status</th>
                            <th>Payment Status</th>
                            <th>UTR Number</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $index => $row)
                        <tr>
                            <td>{{ $records->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $row->candidate_name }}</strong><br>
                                <small class="text-muted">{{ $row->candidate_code }}</small>
                            </td>
                            <td>{{ $row->requisition_id ?? '-' }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $row->month ? date('F', mktime(0, 0, 0, $row->month, 1)) : '' }} {{ $row->year }}
                                </span>
                            </td>
                            <td>
                                {{ $row->department_name ?? '-' }}
                                @if($row->sub_department_name)
                                <br><small class="text-muted">{{ $row->sub_department_name }}</small>
                                @endif
                            </td>
                            <td>{{ $row->reporting_manager_name ?? '-' }}</td>
                            <td>₹{{ number_format($row->monthly_salary, 2) }}</td>
                            <td>{{ $row->paid_days ?? 0 }} / {{ $row->total_days ?? 0 }}</td>
                            <td>
                                <strong class="text-success">₹{{ number_format($row->net_pay, 2) }}</strong>
                                @if($row->arrear_amount > 0)
                                <br><small class="text-info">+ Arrear: ₹{{ number_format($row->arrear_amount, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = match($row->status) {
                                        'processed' => 'info',
                                        'release' => 'success',
                                        'hold' => 'danger',
                                        default => 'warning'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $paymentClass = match($row->payment_status) {
                                        'paid' => 'success',
                                        'processed' => 'info',
                                        'held' => 'danger',
                                        default => 'warning'
                                    };
                                @endphp
                                <span class="badge bg-{{ $paymentClass }}">
                                    {{ ucfirst($row->payment_status) }}
                                </span>
                            </td>
                            <td>
                                @if($row->utr_number)
                                <code>{{ $row->utr_number }}</code>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($row->payment_date)
                                {{ \Carbon\Carbon::parse($row->payment_date)->format('d-M-Y') }}
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div class="alert alert-info mb-0">
                                    <i class="ri-information-line fs-4 d-block mb-2"></i>
                                    <strong>No payment data found</strong><br>
                                    No salary processing records match the selected filters.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($records->total() > 0)
        <div class="card-footer d-flex justify-content-end">
            {{ $records->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<style>
    .kpi-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .kpi-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 12px 15px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        transition: 0.2s ease;
        border-left: 3px solid #3b82f6;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .kpi-title {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-icon {
        font-size: 18px;
    }

    .kpi-number {
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        color: #1f2937;
    }

    .kpi-avg {
        font-size: 11px;
        color: #6b7280;
    }

    .kpi-footer {
        font-size: 11px;
        margin-top: 8px;
        display: flex;
        gap: 12px;
    }

    .table-scroll {
        width: 100%;
        overflow-x: auto;
        border-radius: 8px;
    }

    .table-scroll table {
        min-width: 1200px;
    }

    .table-scroll thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 2;
        border-bottom: 2px solid #e5e7eb;
    }

    .table-scroll th:first-child,
    .table-scroll td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
    }

    .table-scroll thead th:first-child {
        background: #f8fafc;
        z-index: 3;
    }

    .table-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .table-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .form-label-sm {
        font-size: 12px;
        margin-bottom: 4px;
        font-weight: 500;
        color: #374151;
    }

    hr {
        margin: 16px 0;
    }

    .alert-sm {
        padding: 8px 12px;
        font-size: 13px;
    }

    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    .table td,
    .table th {
        vertical-align: middle;
        padding: 10px 8px;
        font-size: 13px;
    }

    .btn-sm {
        font-size: 12px;
        padding: 4px 10px;
    }
</style>

@push('scripts')
<script>
$(document).ready(function() {
    // Store original filter values
    let originalFilters = {
        vertical: $('#vertical_filter').html(),
        sub_department: $('#sub_department_filter').html(),
        department: $('#department_filter').html()
    };

    @if($showLocationFilters)
    originalFilters.bu = $('#bu_filter').html();
    originalFilters.zone = $('#zone_filter').html();
    originalFilters.region = $('#region_filter').html();
    originalFilters.territory = $('#territory_filter').html();
    @endif

    // When Reporting Manager changes
    $('#employee_filter').on('change', function() {
        let employeeId = $(this).val();

        if (employeeId && employeeId !== 'All') {
            $('#loadingSpinner').show();

            // Disable all filter selects while loading
            $('#vertical_filter, #sub_department_filter, #department_filter, #applyFiltersBtn').prop('disabled', true);
            @if($showLocationFilters)
            $('#bu_filter, #zone_filter, #region_filter, #territory_filter').prop('disabled', true);
            @endif

            $.ajax({
                url: '/reports/payment/filters/' + employeeId,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        @if($showLocationFilters)
                        // Update Business Unit dropdown
                        let buSelect = $('#bu_filter');
                        buSelect.empty();
                        buSelect.append('<option value="All">All Business Units</option>');
                        $.each(response.data.business_units, function(id, name) {
                            buSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        buSelect.val('All');

                        // Update Zone dropdown
                        let zoneSelect = $('#zone_filter');
                        zoneSelect.empty();
                        zoneSelect.append('<option value="All">All Zones</option>');
                        $.each(response.data.zones, function(id, name) {
                            zoneSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        zoneSelect.val('All');

                        // Update Region dropdown
                        let regionSelect = $('#region_filter');
                        regionSelect.empty();
                        regionSelect.append('<option value="All">All Regions</option>');
                        $.each(response.data.regions, function(id, name) {
                            regionSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        regionSelect.val('All');

                        // Update Territory dropdown
                        let territorySelect = $('#territory_filter');
                        territorySelect.empty();
                        territorySelect.append('<option value="All">All Territories</option>');
                        $.each(response.data.territories, function(id, name) {
                            territorySelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        territorySelect.val('All');
                        @endif

                        // Update Vertical dropdown
                        let verticalSelect = $('#vertical_filter');
                        verticalSelect.empty();
                        verticalSelect.append('<option value="All">All Verticals</option>');
                        $.each(response.data.verticals, function(id, name) {
                            verticalSelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        verticalSelect.val('All');

                        // Update Department dropdown
                        let deptSelect = $('#department_filter');
                        deptSelect.empty();
                        deptSelect.append('<option value="">All Departments</option>');
                        $.each(response.data.departments, function(id, name) {
                            deptSelect.append('<option value="' + id + '">' + name + '</option>');
                        });

                        // Auto-select manager's department
                        if (response.data.manager_department && response.data.departments[response.data.manager_department]) {
                            deptSelect.val(response.data.manager_department);
                        } else {
                            deptSelect.val('');
                        }

                        // Update Sub Department dropdown
                        let subDeptSelect = $('#sub_department_filter');
                        subDeptSelect.empty();
                        subDeptSelect.append('<option value="All">All Sub Departments</option>');
                        $.each(response.data.sub_departments, function(id, name) {
                            subDeptSelect.append('<option value="' + id + '">' + name + '</option>');
                        });

                        // Auto-select manager's sub-department
                        if (response.data.manager_sub_department && response.data.sub_departments[response.data.manager_sub_department]) {
                            subDeptSelect.val(response.data.manager_sub_department);
                        } else {
                            subDeptSelect.val('All');
                        }

                        // Show department and sub-department containers
                        $('#department_filter_container').show();
                        $('#sub_department_filter_container').show();

                        // Build the new URL with auto-selected filters
                        let currentUrl = new URL(window.location.href);
                        let params = new URLSearchParams();

                        // Preserve main filters
                        if ($('select[name="financial_year"]').val()) {
                            params.set('financial_year', $('select[name="financial_year"]').val());
                        }
                        if ($('select[name="year"]').val()) {
                            params.set('year', $('select[name="year"]').val());
                        }
                        if ($('select[name="month"]').val()) {
                            params.set('month', $('select[name="month"]').val());
                        }
                        if ($('select[name="status"]').val()) {
                            params.set('status', $('select[name="status"]').val());
                        }
                        if ($('select[name="payment_mode"]').val()) {
                            params.set('payment_mode', $('select[name="payment_mode"]').val());
                        }

                        // Add the employee filter
                        params.set('employee', employeeId);

                        // Add department if auto-selected
                        if (response.data.manager_department && response.data.departments[response.data.manager_department]) {
                            params.set('department_id', response.data.manager_department);
                        }

                        // Add sub-department if auto-selected
                        if (response.data.manager_sub_department && response.data.sub_departments[response.data.manager_sub_department]) {
                          	  if ($('#sub_department_filter').val() !== 'All') {
									params.set('sub_department', $('#sub_department_filter').val());
								}
                        }

                        let newUrl = currentUrl.pathname + '?' + params.toString();
                        toastr.success('Loading data for ' + $('#employee_filter option:selected').text());
                        window.location.href = newUrl;
                    }
                },
                error: function(xhr) {
                    console.error('Error loading filters:', xhr);
                    toastr.error('Error loading filters');
                    resetFiltersToOriginal();
                    $('#loadingSpinner').hide();
                    enableFilters();
                }
            });
        } else {
            // If "All Employees" selected, redirect to clean URL
            $('#department_filter_container').hide();
            $('#sub_department_filter_container').hide();

            let currentUrl = new URL(window.location.href);
            let params = new URLSearchParams();

            // Preserve main filters
            if ($('select[name="financial_year"]').val()) {
                params.set('financial_year', $('select[name="financial_year"]').val());
            }
            if ($('select[name="year"]').val()) {
                params.set('year', $('select[name="year"]').val());
            }
            if ($('select[name="month"]').val()) {
                params.set('month', $('select[name="month"]').val());
            }
            if ($('select[name="status"]').val()) {
                params.set('status', $('select[name="status"]').val());
            }
            if ($('select[name="payment_mode"]').val()) {
                params.set('payment_mode', $('select[name="payment_mode"]').val());
            }

            let newUrl = currentUrl.pathname + '?' + params.toString();
            window.location.href = newUrl;
        }
    });

    function enableFilters() {
        $('#vertical_filter, #sub_department_filter, #department_filter, #applyFiltersBtn').prop('disabled', false);
        @if($showLocationFilters)
        $('#bu_filter, #zone_filter, #region_filter, #territory_filter').prop('disabled', false);
        @endif
    }

    function resetFiltersToOriginal() {
        $('#vertical_filter').html(originalFilters.vertical);
        $('#sub_department_filter').html(originalFilters.sub_department);
        $('#department_filter').html(originalFilters.department);
        @if($showLocationFilters)
        if (originalFilters.bu) $('#bu_filter').html(originalFilters.bu);
        if (originalFilters.zone) $('#zone_filter').html(originalFilters.zone);
        if (originalFilters.region) $('#region_filter').html(originalFilters.region);
        if (originalFilters.territory) $('#territory_filter').html(originalFilters.territory);
        @endif

        $('#vertical_filter').val('All');
        $('#sub_department_filter').val('All');
        $('#department_filter').val('');
        @if($showLocationFilters)
        $('#bu_filter').val('All');
        $('#zone_filter').val('All');
        $('#region_filter').val('All');
        $('#territory_filter').val('All');
        @endif
        enableFilters();
    }

    $('#clearFiltersBtn').on('click', function(e) {
        if (!confirm('Are you sure you want to clear all hierarchy filters?')) {
            e.preventDefault();
        }
    });

    $('#applyFiltersBtn').on('click', function() {
        $(this).prop('disabled', true).html('<i class="ri-loader-4-line spin"></i> Applying...');
        $('#hierarchyFilterForm').submit();
    });

    // Initial load - show/hide containers
    let currentEmployee = '{{ $employeeId }}';
    if (currentEmployee && currentEmployee !== 'All') {
        $('#department_filter_container').show();
        $('#sub_department_filter_container').show();
    } else {
        $('#department_filter_container').hide();
        $('#sub_department_filter_container').hide();
    }
});
</script>
<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }
</style>
@endpush

@endsection