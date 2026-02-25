<div class="remuneration-scroll">
    <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead>
            <tr>
                <th>S.N.</th>
                <th>Code</th>
                <th>Name of Employees</th>
                <th>Business Unit</th>
                <th>Vertical</th>
                <th>Zone</th>
                <th>Region</th>
                <th>Territory</th>
                <th>Department</th>
                <th>Sub Department</th>
                <th>Paid Days</th>
                <th>Working Days</th>
                <th>Sunday Working</th>
                <th>Total Paid Days</th>
                
                <th>As Per Approval</th>
				<th>As Per Contract</th>
                <th>Arrear</th>
                <th>Deduction</th>
                <th>Final Payable</th>
                <th>Payment Instruction</th>
                <th>HR Remarks</th>
                <th>TDS 2%</th>
                <th>Gross Up 102%</th>
            </tr>
        </thead>

        <tbody>
        @php
            $totalBased = 0;
        @endphp

        @forelse($salaryRecords as $index => $record)

            @php
                $rpm = $record->candidate->remuneration_per_month ?? 0;
                $contractAmount = $record->candidate->contract_amount ?? 0;

                $paidDays = $record->paid_days ?? 0;
                $extra = $record->extra_amount ?? 0;
                $deduction = $record->deduction_amount ?? 0;

                // Final Payable Calculation
                $basedOnPaidDays = (($rpm / 26) * $paidDays) + $extra - $deduction;

                // TDS 2%
                $tds = ($basedOnPaidDays / 98) * 2;

                // Gross Up
                $grossUp = $basedOnPaidDays + $tds;

                $totalBased += $basedOnPaidDays;
            @endphp

            <tr>
                <td>{{ $salaryRecords->firstItem() + $index }}</td>
                <td>{{ $record->candidate->candidate_code ?? '-' }}</td>
                <td>{{ $record->candidate->candidate_name ?? '-' }}</td>

                <td>{{ $record->candidate->businessUnit->business_unit_name ?? '-' }}</td>
                <td>{{ $record->candidate->vertical->vertical_name ?? '-' }}</td>
                <td>{{ $record->candidate->zoneRef->zone_name ?? '-' }}</td>
                <td>{{ $record->candidate->regionRef->region_name ?? '-' }}</td>
                <td>{{ $record->candidate->territoryRef->territory_name ?? '-' }}</td>
                <td>{{ $record->candidate->department->department_name ?? '-' }}</td>
                <td>{{ $record->candidate->subDepartmentRef->sub_department_name ?? '-' }}</td>

                <td>{{ $paidDays }}</td>
                <td>{{ $record->total_days ?? 0 }}</td>
                <td>{{ $record->approved_sundays ?? 0 }}</td>
                <td>{{ $paidDays + ($record->approved_sundays ?? 0) }}</td>
                 <td>{{ number_format(round($rpm)) }}</td>
                <td>{{ number_format(round($contractAmount)) }}</td>                
                <td>{{ number_format(round($extra)) }}</td>
                <td>{{ number_format(round($deduction)) }}</td>
                <td>{{ number_format(round($basedOnPaidDays)) }}</td>

                <td>
                    @php
                        $instruction = strtolower($record->payment_instruction ?? '');
                    @endphp

                    @if($instruction === 'hold')
                        <span class="badge bg-warning text-dark">Hold</span>
                    @elseif($instruction === 'release')
                        <span class="badge bg-success">Release</span>
                    @else
                        <span class="badge bg-secondary">Pending</span>
                    @endif
                </td>

                <td>{{ $record->hr_remarks ?? '-' }}</td>

                <td>{{ number_format(round($tds)) }}</td>
                <td>{{ number_format(round($grossUp)) }}</td>
            </tr>

        @empty
            <tr>
                <td colspan="24" class="text-center">No records found</td>
            </tr>
        @endforelse
        </tbody>

        @if($salaryRecords->count() > 0)
        @php
            $totalTds = ($totalBased / 98) * 2;
            $totalGross = $totalBased + $totalTds;
        @endphp

        <tfoot class="bg-light fw-bold">
            <tr>
                <td colspan="19" class="text-end">Totals:</td>
                <td>{{ number_format(round($totalBased), 2) }}</td>
                <td colspan="2"></td>
                <td>{{ number_format(round($totalTds), 2) }}</td>
                <td>{{ number_format(round($totalGross), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@if(isset($salaryRecords))
<div class="row mt-3">
    <div class="col-md-6">
        Showing {{ $salaryRecords->firstItem() }} to {{ $salaryRecords->lastItem() }} of {{ $salaryRecords->total() }} entries
    </div>
    <div class="col-md-6 text-end">
        {{ $salaryRecords->links('pagination::bootstrap-5') }}
    </div>
</div>
@endif