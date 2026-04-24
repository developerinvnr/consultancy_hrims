@extends('layouts.guest')

@section('title', 'Pending Approvals')

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">
                    <i class="ri-checkbox-circle-line me-2"></i>
                    Pending Approvals
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pending Approvals</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Reuse the existing pending approvals partial -->
    @include('dashboard.partials.pending-approvals')
</div>
@endsection