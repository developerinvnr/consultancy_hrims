@extends('layouts.guest')
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">TAT Report (Action Wise)</h4>
            <a href="{{ route('reports.tat.export', request()->query()) }}"
                class="btn btn-sm btn-success">
                Export Excel
            </a>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="card mb-2 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.tat') }}" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Financial Year</label>
                            <select name="financial_year" class="form-select form-select-sm">
                                <option value="2024-2025" {{ $financialYear=='2024-2025'?'selected':'' }}>2024-2025</option>
                                <option value="2025-2026" {{ $financialYear=='2025-2026'?'selected':'' }}>2025-2026</option>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label form-label-sm">Month</label>
                            <select name="month" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="4" {{ $month==4?'selected':'' }}>Apr</option>
                                <option value="5" {{ $month==5?'selected':'' }}>May</option>
                                <option value="6" {{ $month==6?'selected':'' }}>Jun</option>
                                <option value="7" {{ $month==7?'selected':'' }}>Jul</option>
                                <option value="8" {{ $month==8?'selected':'' }}>Aug</option>
                                <option value="9" {{ $month==9?'selected':'' }}>Sep</option>
                                <option value="10" {{ $month==10?'selected':'' }}>Oct</option>
                                <option value="11" {{ $month==11?'selected':'' }}>Nov</option>
                                <option value="12" {{ $month==12?'selected':'' }}>Dec</option>
                                <option value="1" {{ $month==1?'selected':'' }}>Jan</option>
                                <option value="2" {{ $month==2?'selected':'' }}>Feb</option>
                                <option value="3" {{ $month==3?'selected':'' }}>Mar</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">All</option>
                                @foreach($departments as $deptId => $deptName)
                                <option value="{{ $deptId }}"
                                    {{ $departmentId==$deptId?'selected':'' }}>
                                    {{ $deptName }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Requisition Type</label>
                            <select name="requisition_type" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="Contractual" {{ $requisitionType=='Contractual'?'selected':'' }}>Contractual</option>
                                <option value="TFA" {{ $requisitionType=='TFA'?'selected':'' }}>TFA</option>
                                <option value="CB" {{ $requisitionType=='CB'?'selected':'' }}>CB</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="Pending HR Verification">Pending HR Verification</option>
                                <option value="Pending Approval">Pending Approval</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <button class="btn btn-sm btn-primary w-100">
                                Filter
                            </button>
                        </div>
                    </form>
                    
                    <!-- Hierarchy Filters -->
                    <!-- Hierarchy Filters -->
<hr class="my-3">
<form method="GET" action="{{ route('reports.tat') }}" class="row g-2 align-items-end mt-2">
    <!-- Preserve existing filters -->
    <input type="hidden" name="financial_year" value="{{ $financialYear }}">
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="department_id" value="{{ $departmentId }}">
    <input type="hidden" name="requisition_type" value="{{ $requisitionType }}">
    <input type="hidden" name="status" value="{{ $status }}">
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Business Unit</label>
        <select name="bu" class="form-select form-select-sm">
            @foreach($businessUnits as $buId => $buName)
            <option value="{{ $buId }}" {{ ($buId ?? '') == $buId ? 'selected' : '' }}>
                {{ $buName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Zone</label>
        <select name="zone" class="form-select form-select-sm">
            @foreach($zones as $zoneId => $zoneName)
            <option value="{{ $zoneId }}" {{ ($zoneId ?? '') == $zoneId ? 'selected' : '' }}>
                {{ $zoneName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Region</label>
        <select name="region" class="form-select form-select-sm">
            @foreach($regions as $regionId => $regionName)
            <option value="{{ $regionId }}" {{ ($regionId ?? '') == $regionId ? 'selected' : '' }}>
                {{ $regionName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Territory</label>
        <select name="territory" class="form-select form-select-sm">
            @foreach($territories as $territoryId => $territoryName)
            <option value="{{ $territoryId }}" {{ ($territoryId ?? '') == $territoryId ? 'selected' : '' }}>
                {{ $territoryName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Vertical</label>
        <select name="vertical" class="form-select form-select-sm">
            @foreach($verticals as $verticalId => $verticalName)
            <option value="{{ $verticalId }}" {{ ($verticalId ?? '') == $verticalId ? 'selected' : '' }}>
                {{ $verticalName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <label class="form-label form-label-sm">Reporting Manager</label>
        <select name="employee" class="form-select form-select-sm">
            @foreach($employees as $empId => $empName)
            <option value="{{ $empId }}" {{ ($employeeId ?? '') == $empId ? 'selected' : '' }}>
                {{ $empName }}
            </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-2">
        <button class="btn btn-sm btn-secondary w-100">
            Apply Hierarchy Filters
        </button>
    </div>
    
    @if(($buId ?? 'All') != 'All' || ($zoneId ?? 'All') != 'All' || ($regionId ?? 'All') != 'All' || ($territoryId ?? 'All') != 'All' || ($verticalId ?? 'All') != 'All' || ($employeeId ?? 'All') != 'All')
    <div class="col-md-2">
        <a href="{{ route('reports.tat', array_merge(request()->except(['bu', 'zone', 'region', 'territory', 'vertical', 'employee']), ['bu' => 'All', 'zone' => 'All', 'region' => 'All', 'territory' => 'All', 'vertical' => 'All', 'employee' => 'All'])) }}" 
           class="btn btn-sm btn-outline-danger w-100">
            Clear All Filters
        </a>
    </div>
    @endif
</form>
                </div>
            </div>

            <!-- Display active hierarchy filters -->
            @if(($buId ?? 'All') != 'All' || ($zoneId ?? 'All') != 'All' || ($regionId ?? 'All') != 'All' || ($territoryId ?? 'All') != 'All' || ($verticalId ?? 'All') != 'All' || ($employeeId ?? 'All') != 'All')
            <div class="alert alert-info alert-sm mb-3">
                <i class="ri-filter-line me-2"></i>
                <strong>Active Filters:</strong>
                @if(($buId ?? 'All') != 'All') Business Unit: {{ $businessUnits[$buId] ?? $buId }} @endif
                @if(($zoneId ?? 'All') != 'All') | Zone: {{ $zones[$zoneId] ?? $zoneId }} @endif
                @if(($regionId ?? 'All') != 'All') | Region: {{ $regions[$regionId] ?? $regionId }} @endif
                @if(($territoryId ?? 'All') != 'All') | Territory: {{ $territories[$territoryId] ?? $territoryId }} @endif
                @if(($verticalId ?? 'All') != 'All') | Vertical: {{ $verticals[$verticalId] ?? $verticalId }} @endif
                @if(($employeeId ?? 'All') != 'All') | Reporting Manager: {{ $employees[$employeeId] ?? $employeeId }} @endif
            </div>
            @endif

            <!-- KPI Cards remain the same -->
            <div class="kpi-container mb-3">
                @foreach($summaries as $key => $s)
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-title">
                            {{ ucwords(str_replace('_',' ', $key)) }}
                        </div>
                        <div class="kpi-icon">
                            ⏱️
                        </div>
                    </div>

                    <div class="kpi-body d-flex justify-content-between align-items-center">
                        <div class="kpi-number">{{ $s['total'] }}</div>
                        <div class="kpi-avg">{{ $s['avg'] }}d</div>
                    </div>

                    <div class="kpi-footer">
                        <span class="badge-success">≤1 day: {{ $s['within_1'] }}</span>
                        <span class="badge-warning">1–3 days: {{ $s['within_3'] }}</span>
                        <span class="badge-danger">>3 days: {{ $s['above_3'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Table remains the same -->
            <div class="table-scroll">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Req ID</th>
                            <th>Candidate</th>
                            <th>Submission</th>
                            <th>Contract Start Date</th>

                            {{-- Stage Dates --}}
                            @foreach($stages as $key => $s)
                            <th>{{ ucfirst(str_replace('_',' ', $key)) }}</th>
                            @endforeach

                            {{-- Stage TAT --}}
                            @foreach($stages as $key => $s)
                            <th>{{ ucfirst(str_replace('_',' ', $key)) }} TAT</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $index => $row)
                        <tr>
                            <td>{{ $records->firstItem() + $index }}</td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $row->requisition_id }}
                                </span>
                            </td>

                            <td>{{ $row->candidate_name }}</td>

                            <td>
                                {{ $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('d-M-Y') : '-' }}
                            </td>

                            <td>
                                @if($row->contract_start_date)
                                <span class="badge bg-info">
                                    {{ \Carbon\Carbon::parse($row->contract_start_date)->format('d-M-Y') }}
                                </span>
                                @else
                                -
                                @endif
                            </td>

                            {{-- Stage Dates --}}
                            @foreach($stages as $key => $s)
                            <td>
                                {{ $row->{$s['to']} ? \Carbon\Carbon::parse($row->{$s['to']})->format('d-M-Y') : '-' }}
                            </td>
                            @endforeach

                            {{-- Stage TAT --}}
                            @foreach($stages as $key => $s)
                            @php
                                if($key === 'file_creation'){
                                    $fromDate = $row->received_date
                                        ?? $row->agreement_uploaded_date
                                        ?? $row->agreement_created_date
                                        ?? $row->approval_date;
                                    $toDate = $row->file_created_date;
                                } else {
                                    $fromDate = $row->{$s['from']} ?? null;
                                    $toDate = $row->{$s['to']} ?? null;
                                }

                                $tat = ($fromDate && $toDate)
                                    ? \Carbon\Carbon::parse($fromDate)->diffInDays($toDate)
                                    : null;
                            @endphp

                            <td class="text-center">
                                @if($tat !== null)
                                <span class="badge 
                                    @if($tat <= 1) bg-success
                                    @elseif($tat <= 3) bg-warning
                                    @else bg-danger
                                    @endif
                                ">
                                    {{ $tat < 1 ? 'Within 1 Day' : round($tat).' Days' }}
                                </span>
                                @else
                                -
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            {{ $records->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<style>
    .kpi-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
    }

    .kpi-card {
        background: #ffffff;
        border-radius: 8px;
        padding: 8px 10px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        transition: 0.2s ease;
        border-left: 3px solid #3b82f6;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
    }

    .kpi-title {
        font-size: 11px;
        font-weight: 600;
        color: #374151;
    }

    .kpi-icon {
        font-size: 13px;
    }

    .kpi-number {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.2;
    }

    .kpi-avg {
        font-size: 10px;
        color: #6b7280;
    }

    .kpi-footer {
        font-size: 10px;
        margin-top: 4px;
    }

    .kpi-body {
        margin-top: 4px;
    }

    .badge-success {
        color: #059669;
        font-weight: 600;
    }

    .badge-warning {
        color: #d97706;
        font-weight: 600;
    }

    .badge-danger {
        color: #dc2626;
        font-weight: 600;
    }

    .table-scroll {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
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
    }

    .table-scroll th:first-child,
    .table-scroll td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
    }

    .table-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>
@endsection