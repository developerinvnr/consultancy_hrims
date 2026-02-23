{{-- resources/views/submitter/agreement/courier-details.blade.php --}}
@extends('layouts.guest')

@section('page-title', 'Courier Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box py-2 d-sm-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    @if($courierDetails)
                        Edit Courier Details
                    @else
                        Add Courier Details
                    @endif
                </h5>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('submitter.agreement.view', $requisition) }}">Agreement Details</a>
                        </li>
                        <li class="breadcrumb-item active">Courier Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-truck-line me-2"></i>
                        Agreement Dispatch Information
                    </h5>
                </div>

                <div class="card-body">
                    @if($courierDetails && $courierDetails->isReceived())
                        <div class="alert alert-success">
                            <i class="ri-checkbox-circle-line me-2"></i>
                            <strong>Courier Received!</strong> This courier was marked as received on 
                            {{ $courierDetails->formatted_received_date }}.
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            Please provide the courier details for the physical agreement sent to the candidate.
                            @if($courierDetails)
                                <br>You can update the details below if needed.
                            @endif
                        </div>
                    @endif

                    <!-- Agreement Summary -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="mb-3">Agreement Details</h6>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <strong>Candidate:</strong><br>
                                {{ $candidate->candidate_name }} ({{ $candidate->candidate_code }})
                            </div>
                            <div class="col-sm-6">
                                <strong>Agreement Number:</strong><br>
                                {{ $agreement->agreement_number }}
                            </div>
                            <div class="col-sm-6">
                                <strong>Requisition ID:</strong><br>
                                {{ $requisition->requisition_id }}
                            </div>
                            <div class="col-sm-6">
                                <strong>Stamp Type:</strong><br>
                                @if($agreement->stamp_type === 'E_STAMP')
                                    <span class="badge bg-warning">E-Stamp</span>
                                @else
                                    <span class="badge bg-secondary">No Stamp</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Courier Details Form -->
                    <form id="courierDetailsForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Courier Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="courier_name" 
                                   required 
                                   placeholder="e.g., Blue Dart, DTDC, FedEx"
                                   maxlength="150"
                                   value="{{ old('courier_name', $courierDetails->courier_name ?? '') }}">
                            <small class="text-muted">Enter the name of the courier service provider</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Docket / Tracking Number <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   name="docket_number" 
                                   required 
                                   placeholder="Enter tracking number"
                                   maxlength="150"
                                   value="{{ old('docket_number', $courierDetails->docket_number ?? '') }}">
                            <small class="text-muted">The unique tracking number provided by the courier</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date of Dispatch <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   name="dispatch_date" 
                                   required 
                                   max="{{ date('Y-m-d') }}"
                                   value="{{ old('dispatch_date', $courierDetails->dispatch_date ?? date('Y-m-d')) }}">
                            <small class="text-muted">The date when the agreement was dispatched</small>
                        </div>

                        @if($courierDetails && $courierDetails->sent_by_user_id)
                            <div class="mb-3 p-2 bg-light rounded small">
                                <strong>Previously added by:</strong> {{ $courierDetails->sentBy->name ?? 'Unknown' }} 
                                on {{ $courierDetails->created_at->format('d M Y, h:i A') }}
                            </div>
                        @endif

                        @if($courierDetails && $courierDetails->isReceived())
                            <div class="mb-3 p-2 bg-success bg-opacity-10 rounded small">
                                <strong class="text-success">Received Information:</strong><br>
                                Received on: {{ $courierDetails->formatted_received_date }}<br>
                                Received by: {{ $courierDetails->receivedBy->name ?? 'Unknown' }}
                            </div>
                        @endif

                        <div class="alert alert-warning small">
                            <i class="ri-error-warning-line me-2"></i>
                            Please ensure all details are correct before submitting. These details will be used for tracking the agreement dispatch.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('submitter.agreement.view', $requisition) }}" 
                               class="btn btn-light">
                                Cancel
                            </a>
                            @if(!$courierDetails || !$courierDetails->isReceived())
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="ri-save-line me-1"></i> 
                                    {{ $courierDetails ? 'Update Courier Details' : 'Save Courier Details' }}
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#courierDetailsForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitBtn');
        const url = "{{ route('submitter.agreement.save-courier-details', [$requisition, $agreement]) }}";

        $.ajax({
            url: url,
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                submitBtn.prop('disabled', true).html(
                    '<i class="ri-loader-4-line ri-spin me-1"></i> Saving...'
                );
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(() => {
                        window.location.href = "{{ route('submitter.agreement.view', $requisition) }}";
                    }, 2000);
                } else {
                    showToast('error', response.message);
                    submitBtn.prop('disabled', false).html(
                        '<i class="ri-save-line me-1"></i> {{ $courierDetails ? "Update Courier Details" : "Save Courier Details" }}'
                    );
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to save courier details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                showToast('error', errorMessage);
                submitBtn.prop('disabled', false).html(
                    '<i class="ri-save-line me-1"></i> {{ $courierDetails ? "Update Courier Details" : "Save Courier Details" }}'
                );
            }
        });
    });

    function showToast(type, message) {
        const toast = `<div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

        if ($('.toast-container').length === 0) {
            $('body').append('<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"><div class="toast-container"></div></div>');
        }

        $('.toast-container').append(toast);
        $('.toast').last().toast('show');

        setTimeout(() => {
            $('.toast').last().remove();
        }, 5000);
    }
});
</script>
@endpush