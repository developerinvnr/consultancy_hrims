{{-- resources/views/reports/partials/master-results.blade.php --}}
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
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
            @endphp
            <tr>
                <td>{{ $candidates->firstItem() + $index }}</td>
                <td>{{ $candidate->candidate_name }}</td>
                <td>{{ $candidate->agreement_id ?? 'N/A' }}</td>
                <td>{{ $candidate->candidate_code }}</td>
                <td>{{ $candidate->function ?? 'N/A' }}</td>
                <td>{{ $candidate->department->department_name ?? 'N/A' }}</td>
                <td>{{ $candidate->sub_department ?? 'N/A' }}</td>
                <td>{{ $candidate->crop_vertical ?? 'N/A' }}</td>
                <td>{{ $candidate->region ?? 'N/A' }}</td>
                <td>{{ $candidate->business_unit ?? 'N/A' }}</td>
                <td>{{ $candidate->work_location_hq ?? 'N/A' }}</td>
                <td>{{ $candidate->city ?? 'N/A' }}</td>
                <td>{{ $candidate->state_name ?? 'N/A' }}</td>
                <td>{{ $candidate->address ?? 'N/A' }}</td>
                <td>{{ $candidate->pin_code ?? 'N/A' }}</td>
                <td>{{ $candidate->email ?? 'N/A' }}</td>
                <td>{{ $candidate->mobile_no ?? 'N/A' }}</td>
                <td>{{ $candidate->bank_account_name ?? $candidate->candidate_name }}</td>
                <td>{{ $candidate->bank_account_no ?? 'N/A' }}</td>
                <td>{{ $candidate->ifsc_code ?? 'N/A' }}</td>
                <td>{{ $candidate->pan_no ?? 'N/A' }}</td>
                <td>{{ $candidate->designation ?? 'N/A' }}</td>
                <td>{{ $candidate->grade ?? 'N/A' }}</td>
                <td>{{ $candidate->reporting_to ?? 'N/A' }}</td>
                <td>{{ $candidate->rm_email ?? 'N/A' }}</td>
                <td>{{ $candidate->aadhaar_no ?? 'N/A' }}</td>
                <td>{{ $candidate->date_of_joining ? date('d-M-Y', strtotime($candidate->date_of_joining)) : 'N/A' }}</td>
                <td>{{ $candidate->contract_end_date ? date('d-M-Y', strtotime($candidate->contract_end_date)) : 'N/A' }}</td>
                <td>
                    @if($candidate->final_status == 'A')
                        <span class="badge badge-success">Active</span>
                    @elseif($candidate->final_status == 'D')
                        <span class="badge badge-danger">Deactive</span>
                    @else
                        <span class="badge badge-secondary">{{ $candidate->final_status }}</span>
                    @endif
                </td>
                <td>{{ $salary ? number_format($salary->net_pay, 2) : 'N/A' }}</td>
                <td>{{ $candidate->remarks ?? 'N/A' }}</td>
                <td>{{ $candidate->contract_generate_date ? date('d-M-Y', strtotime($candidate->contract_generate_date)) : 'N/A' }}</td>
                <td>{{ $candidate->contract_dispatch_date ? date('d-M-Y', strtotime($candidate->contract_dispatch_date)) : 'N/A' }}</td>
                <td>{{ $candidate->signed_contract_upload_date ? date('d-M-Y', strtotime($candidate->signed_contract_upload_date)) : 'N/A' }}</td>
                <td>{{ $candidate->signed_contract_dispatch_date ? date('d-M-Y', strtotime($candidate->signed_contract_dispatch_date)) : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="35" class="text-center">No records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(isset($candidates))
<div class="row">
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