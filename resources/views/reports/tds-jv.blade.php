@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <div class="row mb-2">
        <div class="col-12">
            <h4>TDS JV Report</h4>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form method="GET"
                  action="{{ route('reports.tds-jv') }}"
                  id="tdsForm"
                  class="row g-3 align-items-end">

                {{-- Financial Year --}}
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Financial Year</label>
                    <select name="financial_year" class="form-select form-select-sm">
                        @php
                            $currentYear = date('Y');
                            $startYear = $currentYear - 2;
                            $endYear = $currentYear;
                        @endphp

                        @for($y=$startYear;$y<=$endYear;$y++)
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

                <div class="col-md-2">
                    <label class="form-label form-label-sm">Requisition Type</label>
                    <select name="requisition_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                        <option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
                        <option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                    </select>
			   </div>

               <div class="col-md-2">
                    <label class="form-label form-label-sm">Export Status</label>
                    <select name="export_status" class="form-select form-select-sm">
                        <option value="All">All</option>

                        <option value="exported"
                            {{ request('export_status')=='exported'?'selected':'' }}>
                            Exported
                        </option>

                        <option value="not_exported"
                            {{ request('export_status')=='not_exported'?'selected':'' }}>
                            Not Exported
                        </option>
                    </select>
               </div>

                {{-- Buttons --}}
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-50">
                        Generate
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-success w-50"
                            onclick="exportTDS()">
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
                    <th>DocNo</th>
                    <th>Date</th>
                    <th>Business Entity</th>
                    <th>sNarration</th>
                    <th>TDSVoucherNo</th>
                    <th>DrAccount</th>
                    <th>CrAccount</th>
                    <th>Amount</th>
                    <th>Reference</th>
                </tr>
            </thead>

            <tbody>
            @forelse($records as $rec)

                @php
                    $tds = round($rec->net_pay * 0.02,0);

                    $narration = "TDS deducted on Rs. "
                        . round($rec->net_pay,0)
                        . " @2%, Being Contractual Expenses for the Month of "
                        . \Carbon\Carbon::create()->month($month)->format('F')
                        . " $year";
                @endphp

                <tr>
                    <td></td>
                    <td>{{ now()->format('d-m-Y') }}</td>
                    <td>120</td>
                    <td>{{ $narration }}</td>
                    <td></td>
                    <td>{{ $rec->candidate->candidate_code }}</td>
                    <td>STAT-DUES-TDS-15</td>
                    <td>{{ $tds }}</td>
                    <td></td>
                </tr>

            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
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
function exportTDS(){
    let form = $('#tdsForm');
    let params = form.serialize();
    window.location.href =
        "{{ route('reports.tds-jv.export') }}?" + params;
}
</script>