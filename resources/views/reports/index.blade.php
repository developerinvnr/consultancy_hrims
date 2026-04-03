@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0">Reports Dashboard</h4>
            <small class="text-muted">Generate and export reports</small>
        </div>
    </div>

    <div class="row g-3">

        <!-- Master Report -->

        @can('master_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Master Report</h6>
                    <p class="text-muted small">
                        Complete employee master data with contract info.
                    </p>
                    <a href="{{ route('reports.master') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('payout_report')
        <!-- Management Report -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Payout Report</h6>
                    <p class="text-muted small">
                        Payout processing details by month & department.
                    </p>
                    <a href="{{ route('reports.remuneration') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('focus_maste_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Focus Master Report</h6>
                    <p class="text-muted small">
                        Focus master compliance, bank & reporting details.
                    </p>
                    <a href="{{ route('reports.focus-master') }}"
                        class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('jv_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">JV Report</h6>
                    <p class="text-muted small">
                        Journal entry export for expenses.
                    </p>
                    <a href="{{ route('reports.jv') }}"
                        class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('tds_jv_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">TDS JV Report</h6>
                    <p class="text-muted small">
                        TDS deduction journal entry for expenses.
                    </p>
                    <a href="{{ route('reports.tds-jv') }}"
                        class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('payment_jv_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Payment JV</h6>
                    <p class="text-muted small">
                        Bank payment entry for expenses.
                    </p>
                    <a href="{{ route('reports.payment-jv') }}"
                        class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        @can('tat_report')
        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">TAT Report</h6>
                    <p class="text-muted small">
                        Turnaround time report action wise.
                    </p>
                    <a href="{{ route('reports.tat') }}" class="btn btn-sm btn-primary">
                        Open Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
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

        <div class="col-md-4">
            <div class="card shadow-sm h-100 report-card">
                <div class="card-body">
                    <h6 class="fw-bold">Hierarchy-wise Consultant Remuneration Report</h6>
                    <p class="text-muted small">
                        Remuneration Report action wise.
                    </p>
                    <a href="{{ route('reports.paymentReport') }}" class="btn btn-sm btn-primary">
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
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
</style>