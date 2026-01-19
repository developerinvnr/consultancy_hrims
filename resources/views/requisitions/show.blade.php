@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <!-- Start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Requisition Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('requisitions.index') }}">Requisitions</a></li>
                        <li class="breadcrumb-item active">View</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- End page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                Requisition: <span class="text-primary">{{ $requisition->requisition_id }}</span>
                                <span class="badge bg-primary ms-2">{{ $requisition->requisition_type }}</span>
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">

                                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-arrow-left-line me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @include('requisitions.show-content')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    .table-sm td {
        padding: 0.3rem 0;
    }

    .card-header h6 {
        font-size: 0.95rem;
        font-weight: 600;
    }

    .card.border {
        border: 1px solid rgba(0, 0, 0, .125) !important;
    }
</style>