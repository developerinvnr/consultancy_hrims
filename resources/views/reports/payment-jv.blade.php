@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <div class="row mb-2">
        <div class="col-12">
            <h4>Payment JV Report</h4>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form method="GET"
                action="{{ route('reports.payment-jv') }}"
                id="paymentForm"
                class="row g-3 align-items-end">

                {{-- Financial Year --}}
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Financial Year</label>
                    <select name="financial_year" class="form-select form-select-sm">
                        @for($y=date('Y')-2;$y<=date('Y');$y++)
                            @php $fy=$y.'-'.($y+1); @endphp
                            <option value="{{ $fy }}"
                            {{ $financialYear==$fy?'selected':'' }}>
                            {{ $fy }}
                            </option>
                            @endfor
                    </select>
                </div>

                {{-- Month --}}
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Month</label>
                    @php $fyMonths=[4,5,6,7,8,9,10,11,12,1,2,3]; @endphp
                    <select name="month" class="form-select form-select-sm">
                        @foreach($fyMonths as $m)
                        <option value="{{ $m }}"
                            {{ $month==$m?'selected':'' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="All" {{ $status=='All'?'selected':'' }}>All</option>
                        <option value="A" {{ $status=='A'?'selected':'' }}>Active</option>
                        <option value="D" {{ $status=='D'?'selected':'' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Requisition Type</label>
                    <select name="requisition_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                        <option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
                        <option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-50">
                        Generate
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-success w-50"
                        onclick="exportPayment()">
                        Export
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="vendor-scroll">
        <table class="table table-sm table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>CashBankAC</th>
                    <th>sNarration</th>
                    <th>Department</th>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>TDS</th>
                </tr>
            </thead>

            <tbody>
                @forelse($records as $rec)

                @php
                // ✅ Calculate using the same logic as JV report
                $finalPayable = $rec->total_payable ?? ($rec->net_pay + ($rec->arrear_amount ?? 0));

                // TDS @ 2% (calculated on gross)
                $tds = $finalPayable > 0 ? ($finalPayable / 98) * 2 : 0;

                // Gross Up Amount
                $grossUp = $finalPayable + $tds;

                // Payment Amount = Gross - TDS (which should equal finalPayable)
                $paymentAmount = $grossUp - $tds; // This equals $finalPayable

                // For verification, you can also do:
                // $paymentAmount = $finalPayable;

                $monthYear = \Carbon\Carbon::create()->month($month)->format('M-y');
                @endphp

                <tr>
                    <td>{{ now()->format('d-m-Y') }}</td>
                    <td>BANK-26</td>
                    <td>
                        Payment against expenses for {{ $monthYear }}
                    </td>
                    <td>{{ $rec->candidate->department->department_name ?? '' }}</td>
                    <td>{{ $rec->candidate->candidate_code }}</td>
                    <td>{{ number_format($paymentAmount, 0) }}</td>
                    <td>{{ number_format($tds, 0) }}</td>
                </tr>

                @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $records->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection

<script>
    function exportPayment() {
        let form = $('#paymentForm');
        let params = form.serialize();
        window.location.href =
            "{{ route('reports.payment-jv.export') }}?" + params;
    }
</script>