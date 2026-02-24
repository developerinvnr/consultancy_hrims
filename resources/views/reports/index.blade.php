@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0">Reports Dashboard</h4>
            <small class="text-muted">Generate and export HR reports</small>
        </div>
    </div>

    <div class="row g-3">

        <!-- Master Report -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Master Report</h6>
                    <p class="text-muted small">
                        Complete employee master data with contract & salary info.
                    </p>
                    <a href="{{ route('reports.master') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Management Report -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Remuneration Report</h6>
                    <p class="text-muted small">
                        Salary processing details by month & department.
                    </p>
                    <a href="{{ route('reports.remuneration') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>

         <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Management Report</h6>
                    <p class="text-muted small">
                        Management Remuneration Report.
                    </p>
                    <a href="{{ route('salary.management.report') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>

        



        <!-- Vendor Details -->
        {{--<div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Vendor Details Report</h6>
                    <p class="text-muted small">
                        Bank & compliance details of contractual employees.
                    </p>
                    <a href="{{ route('reports.vendor-details') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>--}}

    </div>

</div>
@endsection

<style>
.report-card {
    transition: all 0.2s ease-in-out;
}
.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
</style>