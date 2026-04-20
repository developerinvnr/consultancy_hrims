@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <div class="row mb-2">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Focus Master Report</h4>
            </div>
        </div>
    </div>

    {{-- FILTER CARD --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET"
                  action="{{ route('reports.focus-master') }}"
                  id="vendorMasterForm"
                  class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="Name / Code / PAN">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-50">
                        Generate
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-success w-50"
                            onclick="exportVendorMaster()">
                        Export
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="vendor-scroll">
        <table class="table table-sm table-bordered table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Account Type</th>
                    <th>Group</th>
                    <th>Parent Code</th>
                    <th>Parent Name</th>
                    <th>Business Entity</th>
                    <th>Crop Vertical</th>
                    <th>Region</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Pin</th>
                    <th>Email</th>
                    <th>Tel No</th>
                    <th>Bank Account Name</th>
                    <th>Bank Account No</th>
                    <th>IFSC</th>
                    <th>Mobile</th>
                    <th>City Name</th>
                    <th>MSME No</th>
                    <th>MSME</th>
                    <th>State</th>
                    <th>Country</th>
                    <th>Business Unit</th>
                    <th>PAN</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Grade</th>
                    <th>Reporting To</th>
                    <th>AADHAR</th>
                    <th>Function</th>
                    <th>Sub Department</th>
                    <th>Zone</th>
                    <th>DOJ</th>
                    <th>Reporting Designation</th>
                    <th>Reporting Email</th>
                    <th>Reporting Contact</th>
                    <th>Emp Bank Acc No</th>
                    <th>Emp IFSC</th>
                    <th>Emp Bank Name</th>
                    <th>Location/HQ</th>
                    <th>Crop</th>
                    <th>Transaction Type</th>
                </tr>
            </thead>

            <tbody>
            @forelse($records as $rec)
                <tr>
                    <td>{{ $rec->candidate_name }}</td>
                    <td>{{ $rec->candidate_code }}</td>
                    <td>Vendor</td>
                    <td>FALSE</td>
                    <td>{{ $rec->requisition_type ?? '-' }}</td>
                    <td>{{ $rec->requisition_type ?? '-' }}</td>
                    <td>120</td>
                    <td>{{ $rec->vertical->vertical_code ?? '-' }}</td>
                    <td>{{ $rec->regionRef->focus_code ?? '-' }}</td>
                    <td>{{ $rec->formatted_address }}</td>
                    <td>{{ $rec->focus_code ?? '-' }}</td>
                    <td>{{ $rec->pin_code ?? '-' }}</td>
                    <td>{{ $rec->candidate_email ?? '-' }}</td>
                    <td>{{ $rec->mobile_no ?? '-' }}</td>
                    <td>{{ $rec->account_holder_name }} {{ $rec->candidate_code }}</td>
                    <td>{{ $rec->bank_account_no ?? '-' }}</td>
                    <td>{{ $rec->bank_ifsc ?? '-' }}</td>
                    <td>{{ $rec->mobile_no ?? '-' }}</td>
                    <td>{{ $rec->city_village_name ?? '-' }}</td>
                    <td>N/A</td>
                    <td>NO</td>
                    <td>{{ $rec->workState->state_name ?? '-' }}</td>
                    <td>IND</td>
                    <td>{{ $rec->businessUnit->business_unit_code ?? '-' }}</td>
                    <td>{{ $rec->pan_no ?? '-' }}</td>
                    <td>{{ $rec->department->department_code ?? '-' }}</td>
                    <td>{{ $rec->requisition_type ?? '-' }}</td>
                    <td>{{ $rec->requisition_type ?? '-' }}</td>
                    <td>{{ $rec->reportingManager?->emp_name ?? '-' }}</td>
                    <td>{{ $rec->aadhaar_no ?? '-' }}</td>
                    <td>{{ $rec->function->function_code ?? '-' }}</td>
                    <td>{{ $rec->subDepartmentRef->focus_code ?? '-' }}</td>
                    <td>{{ $rec->zoneRef->zone_code ?? '-' }}</td>
                    <td>{{ optional($rec->contract_start_date)->format('d/m/Y') }}</td>
                    <td>{{ $rec->reportingManager?->emp_designation ?? '-' }}</td>
                    <td>{{ $rec->reportingManager?->emp_email ?? '-' }}</td>
                    <td>{{ $rec->reportingManager?->emp_contact ?? '-' }}</td>
                    <td>{{ $rec->bank_account_no ?? '-' }}</td>
                    <td>{{ $rec->bank_ifsc ?? '-' }}</td>
                    <td>{{ $rec->account_holder_name }} {{ $rec->candidate_code }}</td>
                    <td>{{ $rec->workLocation->focus_code ?? '-' }}</td>
                    <td>All Crop</td>
                    <td>NEFT</td>
                </tr>
            @empty
                <tr>
                    <td colspan="43" class="text-center">No records found</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $records->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection


<script>
function exportVendorMaster() {
    let form = $('#vendorMasterForm');
    let params = form.serialize();
    window.location.href = "{{ route('reports.focus-master.export') }}?" + params;
}
</script>


<style>
.vendor-scroll {
    width: 100%;
    max-height: 70vh;
    overflow-x: auto;
    overflow-y: auto;
    display: block;
}
.vendor-scroll thead th {
    position: sticky;
    top: 0;
    background: #e9ecef;
    z-index: 2;
}
.vendor-scroll table {
    font-size: 11px;
}
</style>