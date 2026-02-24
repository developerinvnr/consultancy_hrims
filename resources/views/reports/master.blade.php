@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-1">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Master Report (Finance Related Data)</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="exportReport()">
                        <i class="ri-file-excel-2-line"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-2 shadow-sm">
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('reports.master') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Financial Year</label>
                    <select name="financial_year" id="financialYear" class="form-select form-select-sm" required>
                        @php
                        $currentMonth = date('n');
                        $currentYear = date('Y');

                        if ($currentMonth >= 4) {
                        $fyStart = $currentYear;
                        } else {
                        $fyStart = $currentYear - 1;
                        }

                        $startYear = $fyStart - 2; // Show last 2 FY
                        $endYear = $fyStart; // Show current FY only
                        @endphp

                        @for($y = $startYear; $y <= $endYear; $y++)
                            @php $fy=$y . '-' . ($y + 1); @endphp
                            <option value="{{ $fy }}"
                            {{ request('financial_year') == $fy ? 'selected' : '' }}>
                            {{ $fy }}
                            </option>
                            @endfor
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label form-label-sm">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="All" {{ request('status', 'All') == 'All' ? 'selected' : '' }}>All</option>
                        <option value="A" {{ request('status') == 'A' ? 'selected' : '' }}>Active</option>
                        <option value="D" {{ request('status') == 'D' ? 'selected' : '' }}>Deactive</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Requisition Type</label>
                    <select name="requisition_type" class="form-select form-select-sm">
                        <option value="All" {{ $requisitionType == 'All' ? 'selected' : '' }}>All Types</option>
                        <option value="Contractual" {{ $requisitionType == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                        <option value="TFA" {{ $requisitionType == 'TFA' ? 'selected' : '' }}>TFA</option>
                        <option value="CB" {{ $requisitionType == 'CB' ? 'selected' : '' }}>CB</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Work Location</label>
                    <select name="work_location" class="form-select form-select-sm">
                        <option value="">All Locations</option>
                        @foreach($workLocations as $location)
                        <option value="{{ $location }}" {{ request('work_location') == $location ? 'selected' : '' }}>
                            {{ $location }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->department_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Quick Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name, code, PAN, Aadhaar..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="ri-filter-line"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(isset($stats))
    <div class="row mb-2 g-2">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Total Employees</div>
                    <div class="fw-bold fs-6">{{ $stats['total_employees'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card shadow-sm border">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Salary Processed</div>
                    <div class="fw-bold fs-6">{{ $stats['salary_processed_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card shadow-sm border">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Total Salary</div>
                    <div class="fw-bold fs-6">₹ {{ number_format($stats['total_salary_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card shadow-sm border">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Average Salary</div>
                    <div class="fw-bold fs-6">₹ {{ number_format($stats['average_salary'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Report Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover mb-0" id="masterReportTable">
                    <thead class="">
                        <tr>
                            <th>S.No</th>
                            <th>Name</th>
                            <th>Agreement ID</th>
                            <th>Code</th>
                            <th>Function</th>
                            <th>Department</th>
                            <th>Sub-Dept</th>
                            <th>Crop Vertical</th>
                            <th>Region</th>
                            <th>Business Unit</th>
                            <th>Location/HQ</th>
                            <th>City</th>
                            <th>State Name</th>
                            <th>Address</th>
                            <th>Pin</th>
                            <th>E Mail</th>
                            <th>Tel No</th>
                            <th>Bank Account Name</th>
                            <th>Bank Account Number</th>
                            <th>IFSC Code</th>
                            <th>Pan No</th>
                            <th>Emp Designation</th>
                            <th>Emp Grade</th>
                            <th>Emp Reporting To</th>
                            <th>RM Email</th>
                            <th>Aadhaar No</th>
                            <th>DOJ</th>
                            <th>DOS</th>
                            <th>Active/Deactive</th>
                            <th>Remuneration</th>
                            <th>Remarks</th>
                            <th>Contract generate date</th>
                            <th>Contract dispatch date</th>
                            <th>Signed Contract Upload date</th>
                            <th>Signed Contract dispatch date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($candidates as $index => $candidate)
                        @php
                        $salary = $candidate->salaryProcessings->first();
                        $function = $candidate->function;
                        $department = $candidate->department;
                        $vertical = $candidate->vertical;
                        $region = $candidate->regionRef;
                        $businessUnit = $candidate->businessUnit;
                        $zone = $candidate->zoneRef;
                        $territory = $candidate->territoryRef;

                        // Get agreement documents
                        $unsignedAgreement = $candidate->unsignedAgreements->first();
                        $signedAgreement = $candidate->signedAgreements->first();
                        @endphp
                        <tr>
                            <td>{{ $candidates->firstItem() + $index }}</td>
                            <td>{{ $candidate->candidate_name }}</td>
                            <td>
                                @if($signedAgreement && $signedAgreement->agreement_number)
                                {{ $signedAgreement->agreement_number }}
                                @elseif($unsignedAgreement && $unsignedAgreement->agreement_number)
                                {{ $unsignedAgreement->agreement_number }}
                                @else
                                -
                                @endif
                            </td>
                            <td>{{ $candidate->candidate_code }}</td>
                            <td>{{ $function->function_name ?? ($function->function_name ?? '-') }}</td>
                            <td>{{ $department->department_name ?? '-' }}</td>
                            <td>{{ $candidate->subDepartmentRef?->sub_department_name ?? '-' }}</td>
                            <td>{{ $candidate->vertical?->vertical_name ?? '-' }}</td>
                            <td>{{ $candidate->regionRef?->region_name ?? '-' }}</td>
                            <td>{{ $candidate->businessUnit?->business_unit_name ?? '-' }}</td>
                            <td>{{ $candidate->work_location_hq ?? '-' }}</td>
                            <td>{{ $candidate->cityMaster?->city_village_name ?? '-' }}</td>
                            <td>{{ $candidate->workState?->state_name ?? '-' }}</td>
                            <td>{{ $candidate->address_line_1 ?? '-' }}</td>
                            <td>{{ $candidate->pin_code ?? '-' }}</td>
                            <td>{{ $candidate->candidate_email ?? $candidate->alternate_email ?? '-' }}</td>
                            <td>{{ $candidate->mobile_no ?? '-' }}</td>
                            <td>{{ $candidate->account_holder_name ?? $candidate->candidate_name }}</td>
                            <td>{{ $candidate->bank_account_no ?? '-' }}</td>
                            <td>{{ $candidate->bank_ifsc ?? '-' }}</td>
                            <td>{{ $candidate->pan_no ?? '-' }}</td>
                            <td>{{ $candidate->requisition_type ?? '-' }}</td>
                            <td>{{ $candidate->requisition_type ?? '-' }}</td>
                            <td>{{ $candidate->reporting_to ?? '-' }}</td>
                            <td>
                                @if($candidate->reportingManager)
                                {{ $candidate->reportingManager->emp_email ?? '-' }}
                                @else
                                -
                                @endif
                            </td>
                            <td>{{ $candidate->aadhaar_no ?? '-' }}</td>
                            <td>{{ $candidate->contract_start_date ? \Carbon\Carbon::parse($candidate->contract_start_date)->format('d-M-Y') : '-' }}</td>
                            <td>{{ $candidate->contract_end_date ? \Carbon\Carbon::parse($candidate->contract_end_date)->format('d-M-Y') : '-' }}</td>
                            <td>
                                @if($candidate->final_status == 'A')
                                <span class="badge bg-success">Active</span>
                                @elseif($candidate->final_status == 'D')
                                <span class="badge bg-danger">Deactive</span>
                                @else
                                <span class="badge bg-secondary">{{ $candidate->final_status }}</span>
                                @endif
                            </td>
                            <td class="text-end">₹ {{ number_format($candidate->remuneration_per_month ?? 0, 2) }}</td>
                            <td>{{ $candidate->remarks ?? '-' }}</td>
                            <td>
                                @if($unsignedAgreement && $unsignedAgreement->created_at)
                                {{ \Carbon\Carbon::parse($unsignedAgreement->created_at)->format('d-M-Y') }}
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($unsignedAgreement && $unsignedAgreement->courierDetails && $unsignedAgreement->courierDetails->dispatch_date)
                                {{ \Carbon\Carbon::parse($unsignedAgreement->courierDetails->dispatch_date)->format('d-M-Y') }}
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($signedAgreement && $signedAgreement->created_at)
                                {{ \Carbon\Carbon::parse($signedAgreement->created_at)->format('d-M-Y') }}
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                @if($signedAgreement && $signedAgreement->courierDetails && $signedAgreement->courierDetails->dispatch_date)
                                {{ \Carbon\Carbon::parse($signedAgreement->courierDetails->dispatch_date)->format('d-M-Y') }}
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="35" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ri-inbox-line display-4"></i>
                                    <h5 class="mt-2">No records found</h5>
                                    <p>Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $candidates->firstItem() ?? 0 }} to {{ $candidates->lastItem() ?? 0 }} of {{ $candidates->total() }} entries
                </div>
                <div>
                    {{ $candidates->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
        overflow-x: auto;
    }
    
    #masterReportTable {
    font-size: 11px;
}

#masterReportTable thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #e9ecef;
    font-weight: 600;
    padding: 6px 8px;
    white-space: nowrap;
}

#masterReportTable tbody td {
    padding: 5px 8px;
    white-space: nowrap;
    vertical-align: middle;
}
    .report-table thead th {
        background-color: #212529;
        color: #fff;
    }

    .card.bg-primary {
        background-color: #0d6efd !important;
    }

    .card.bg-success {
        background-color: #198754 !important;
    }

    .card.bg-info {
        background-color: #0dcaf0 !important;
    }

    .card.bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
</style>e

@push('scripts')
<script>
    // Export function
    function exportReport() {
    const financialYear = document.getElementById('financialYear').value;
    const status = document.querySelector('[name="status"]').value;
    const requisitionType = document.querySelector('[name="requisition_type"]').value;
    const workLocation = document.querySelector('[name="work_location"]').value;
    const departmentId = document.querySelector('[name="department_id"]').value;
    const search = document.querySelector('[name="search"]').value;

    if (!financialYear) {
        toastr.error("Please select financial year");
        return;
    }

    let url = `{{ route('reports.master.export') }}?financial_year=${financialYear}`;

    if (status && status !== 'All') {
        url += `&status=${status}`;
    }

    if (requisitionType && requisitionType !== 'All') {
        url += `&requisition_type=${requisitionType}`;
    }

    if (workLocation) {
        url += `&work_location=${encodeURIComponent(workLocation)}`;
    }

    if (departmentId) {
        url += `&department_id=${departmentId}`;
    }

    if (search) {
        url += `&search=${encodeURIComponent(search)}`;
    }

    window.location.href = url;
}

    // Auto-submit form when main filters change
   

    document.querySelector('[name="requisition_type"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    document.querySelector('[name="work_location"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    document.querySelector('[name="department_id"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    // Debounced search
    let searchTimeout;
    document.querySelector('[name="search"]').addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length === 0 || this.value.length >= 2) {
                document.getElementById('filterForm').submit();
            }
        }, 500);
    });
</script>
@endpush