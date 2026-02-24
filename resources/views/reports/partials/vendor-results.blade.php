{{-- resources/views/reports/partials/vendor-results.blade.php --}}
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover" style="font-size: 12px;">
        <thead class="">
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
                <th>E Mail</th>
                <th>Tel No</th>
                <th>Bank Account Name</th>
                <th>Bank Account Number</th>
                <th>IFSC Code</th>
                <th>Mobile</th>
                <th>City Name</th>
                <th>MSME Numb</th>
                <th>MSME</th>
                <th>State Name</th>
                <th>Country</th>
                <th>Business Unit</th>
                <th>Pan No</th>
                <th>Department</th>
                <th>Emp Designation</th>
                <th>Emp Grade</th>
                <th>Emp Reporting To</th>
                <th>AADHAR No</th>
                <th>Function Name</th>
                <th>Sub Department</th>
                <th>Zone Name</th>
                <th>DOJ</th>
                <th>Reporting Designation</th>
                <th>Reporting Email</th>
                <th>Reporting Contact No</th>
                <th>Emp Bank Acc No</th>
                <th>Emp Bank IFSC Code</th>
                <th>Emp Bank Name</th>
                <th>Location/HQ</th>
                <th>Crop</th>
                <th>Transaction Type</th>
            </tr>
        </thead>
        <tbody>
            @forelse($candidates as $candidate)
            <tr>
                <td>{{ $candidate->candidate_name }}</td>
                <td>{{ $candidate->candidate_code }}</td>
                <td>Vendor</td>
                <td>FALSE</td>
                <td>{{ $candidate->requisition_type == 'TFA' ? 'TEMPORARY FIELD ASSISTANT' : ($candidate->requisition_type == 'CB' ? 'Counter Boy' : 'CONTRACTUAL STAFF') }}</td>
                <td>{{ $candidate->requisition_type == 'TFA' ? 'Temporary Field Assistant' : ($candidate->requisition_type == 'CB' ? 'Counter Boy' : 'Contractual Staff') }}</td>
                <td>120</td>
                <td>{{ $candidate->crop_vertical ?? 'FC' }}</td>
                <td>{{ $candidate->region ?? 'N/A' }}</td>
                <td>{{ $candidate->address ?? 'N/A' }}</td>
                <td>{{ $candidate->city ?? 'N/A' }}</td>
                <td>{{ $candidate->pin_code ?? 'N/A' }}</td>
                <td>{{ $candidate->email ?? 'N/A' }}</td>
                <td>{{ $candidate->mobile_no ?? 'N/A' }}</td>
                <td>{{ $candidate->bank_account_name ?? $candidate->candidate_name }}</td>
                <td>{{ $candidate->bank_account_no ?? 'N/A' }}</td>
                <td>{{ $candidate->ifsc_code ?? 'N/A' }}</td>
                <td>{{ $candidate->mobile_no ?? 'N/A' }}</td>
                <td>{{ $candidate->city ?? 'N/A' }}</td>
                <td>N/A</td>
                <td>NO</td>
                <td>{{ $candidate->state_name ?? 'N/A' }}</td>
                <td>IND</td>
                <td>BU501FC</td>
                <td>{{ $candidate->pan_no ?? 'N/A' }}</td>
                <td>{{ $candidate->department->department_name ?? 'N/A' }}</td>
                <td>{{ $candidate->designation ?? 'N/A' }}</td>
                <td>{{ $candidate->grade ?? 'N/A' }}</td>
                <td>{{ $candidate->reporting_to ?? 'N/A' }}</td>
                <td>{{ $candidate->aadhaar_no ?? 'N/A' }}</td>
                <td>{{ $candidate->function_name ?? 'N/A' }}</td>
                <td>{{ $candidate->sub_department ?? 'N/A' }}</td>
                <td>{{ $candidate->zone_name ?? 'N/A' }}</td>
                <td>{{ $candidate->date_of_joining ? date('d/m/Y', strtotime($candidate->date_of_joining)) : 'N/A' }}</td>
                <td>{{ $candidate->reporting_designation ?? 'N/A' }}</td>
                <td>{{ $candidate->rm_email ?? 'N/A' }}</td>
                <td>{{ $candidate->reporting_contact_no ?? 'N/A' }}</td>
                <td>{{ $candidate->bank_account_no ?? 'N/A' }}</td>
                <td>{{ $candidate->ifsc_code ?? 'N/A' }}</td>
                <td>{{ $candidate->bank_name ?? 'N/A' }}</td>
                <td>{{ $candidate->work_location_hq ?? 'N/A' }}</td>
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

@if(isset($candidates))
<div class="row mt-3">
    <div class="col-md-6">
        Showing {{ $candidates->firstItem() }} to {{ $candidates->lastItem() }} of {{ $candidates->total() }} entries
    </div>
    <div class="col-md-6">
        <div class="float-right">
            {{ $candidates->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endif