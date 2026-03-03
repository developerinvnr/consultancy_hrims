@extends('layouts.guest')

@section('content')
<div class="container-fluid">

<div class="row mb-1">
        <div class="col-12">
           <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Payout Report</h4>
            </div>
        </div>
    </div>

    <h4 class="card mb-1 shadow-sm"></h4>
    {{-- Filter --}}
    @include('reports.partials.remuneration-filter', [
        'departments' => $departments,
        'route' => route('reports.remuneration')
    ])

    {{-- Results --}}
    @if(isset($salaryRecords))
        @include('reports.partials.remuneration-results')
    @endif

</div>
@endsection
<style>
.remuneration-scroll {
    width: 100%;
    max-height: 70vh;
    overflow-x: auto;
    overflow-y: auto;
    display: block;
}
.remuneration-scroll thead th {
    position: sticky;
    top: 0;
    background: #e9ecef;
    z-index: 2;
}
.remuneration-scroll tbody td {
    padding: 5px 8px;
    white-space: nowrap;
    vertical-align: middle;
}

.remuneration-scroll table {
    font-size: 11px;
}
</style>
