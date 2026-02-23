@extends('layouts.guest')

@section('page-title', 'View Agreement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box py-2 d-sm-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ $isCompleted ? 'Completed' : 'Unsigned' }} Agreement</h5>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ $isCompleted ? 'Completed' : 'Unsigned' }} Agreement</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Agreement Details</h5>
                </div>

                <div class="card-body">
                    <!-- Status Alert -->
                    <div class="alert alert-{{ $isCompleted ? 'success' : ($signedAgreement ? 'warning' : 'info') }} py-2">
                        <i class="ri-{{ $isCompleted ? 'check-double' : ($signedAgreement ? 'truck' : 'information') }}-line me-2"></i>
                        @if($isCompleted)
                            <strong>Completed!</strong> Signed agreement uploaded and processed.
                        @elseif($signedAgreement && !$signedAgreement->courierDetails)
                            <strong>Action Required:</strong> Add courier details after dispatching.
                        @elseif($signedAgreement && $signedAgreement->courierDetails && !$signedAgreement->courierDetails->isReceived())
                            <strong>In Transit:</strong> Agreement dispatched via {{ $signedAgreement->courierDetails->courier_name }} ({{ $signedAgreement->courierDetails->docket_number }})
                        @elseif($signedAgreement)
                            <strong>Signed:</strong> Agreement uploaded successfully.
                        @else
                            <strong>Action Required:</strong> Upload signed agreement.
                        @endif
                    </div>

                    <!-- Candidate Info -->
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="row small">
                            <div class="col-md-3"><strong>Req ID:</strong> {{ $requisition->requisition_id }}</div>
                            <div class="col-md-3"><strong>Code:</strong> {{ $candidate->candidate_code }}</div>
                            <div class="col-md-3"><strong>Name:</strong> {{ $candidate->candidate_name }}</div>
                            <div class="col-md-3"><strong>Status:</strong> 
                                <span class="badge bg-{{ $isCompleted ? 'success' : ($signedAgreement ? 'warning' : 'info') }}">
                                    {{ $candidate->candidate_status ?? $requisition->status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Unsigned Agreements -->
                    @if($unsignedAgreements->count())
                    <div class="mb-4">
                        <h6 class="mb-2">Unsigned Agreements</h6>
                        @foreach($unsignedAgreements as $unsigned)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $unsigned->agreement_number }}</strong>
                                    <span class="badge bg-{{ $unsigned->stamp_type === 'E_STAMP' ? 'warning' : 'secondary' }} ms-1">
                                        {{ $unsigned->stamp_type === 'E_STAMP' ? 'E-Stamp' : 'No Stamp' }}
                                    </span>
                                    <div class="small text-muted">Uploaded: {{ $unsigned->created_at->format('d M Y') }}</div>
                                </div>
                                <div>
                                    <a href="{{ $unsigned->file_url }}" target="_blank" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('submitter.agreement.download', [$requisition, 'doc' => $unsigned->id]) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Download">
                                        <i class="ri-download-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Signed Agreement -->
                    @if($signedAgreement)
                    <div class="mb-4">
                        <h6 class="mb-2">Signed Agreement</h6>
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <strong>{{ $signedAgreement->agreement_number }}</strong>
                                    <span class="badge bg-success ms-1">Final</span>
                                    @if($signedAgreement->courierDetails)
                                        @if($signedAgreement->courierDetails->isReceived())
                                            <span class="badge bg-success ms-1">✓ Received</span>
                                        @else
                                            <span class="badge bg-warning ms-1">📦 Dispatched</span>
                                        @endif
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ $signedAgreement->file_url }}" target="_blank" class="btn btn-sm btn-outline-success" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('submitter.agreement.download', [$requisition, 'doc' => $signedAgreement->id]) }}" 
                                       class="btn btn-sm btn-outline-success" title="Download">
                                        <i class="ri-download-line"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Courier Details Section -->
                            @if($signedAgreement->courierDetails)
                                @php $c = $signedAgreement->courierDetails; @endphp
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2"><i class="ri-truck-line me-1"></i> Courier Tracking Information</h6>
                                    <div class="row small">
                                        <div class="col-md-3"><strong>Courier:</strong> {{ $c->courier_name }}</div>
                                        <div class="col-md-3"><strong>Docket No:</strong> {{ $c->docket_number }}
                                            @if($c->docket_number)
                                                <a href="#" onclick="window.open('https://www.google.com/search?q={{ urlencode($c->courier_name . ' ' . $c->docket_number . ' tracking') }}', '_blank')" 
                                                   class="ms-1 text-primary" title="Track">
                                                    <i class="ri-external-link-line"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="col-md-3"><strong>Dispatch:</strong> {{ $c->formatted_dispatch_date }}</div>
                                        @if($c->isReceived())
                                            <div class="col-md-3"><strong>Received:</strong> {{ $c->formatted_received_date }}</div>
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-2">
                                        <i class="ri-time-line me-1"></i> Added by {{ $c->sentBy->name ?? 'Unknown' }} on {{ $c->created_at->format('d M Y, h:i A') }}
                                        @if($c->isReceived() && $c->receivedBy)
                                            <br><i class="ri-checkbox-circle-line me-1 text-success"></i> Received by {{ $c->receivedBy->name ?? 'Candidate' }}
                                        @endif
                                    </div>
                                </div>
                            @elseif(!$isCompleted)
                                <div class="bg-light p-3 rounded text-center">
                                    <i class="ri-truck-line fs-2 text-muted mb-2"></i>
                                    <p class="small text-muted mb-2">No courier details added yet. Please add after dispatching.</p>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#courierModal">
                                        <i class="ri-truck-line me-1"></i> Add Courier Details
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Upload Button -->
                    @if(!$signedAgreement && !$isCompleted && $unsignedAgreements->count())
                    <div class="text-center p-4 bg-light rounded">
                        <i class="ri-file-copy-line fs-2 text-muted mb-2"></i>
                        <p class="mb-2">Upload the signed agreement to proceed</p>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="ri-upload-2-line me-1"></i> Upload Signed Agreement
                        </button>
                    </div>
                    @endif

                    <!-- Edit Courier Link -->
                    @if($signedAgreement && $signedAgreement->courierDetails && !$signedAgreement->courierDetails->isReceived() && !$isCompleted)
                    <div class="mt-2 text-end">
                        <a href="{{ route('submitter.agreement.courier-details', [$requisition, $signedAgreement]) }}" class="small">
                            <i class="ri-edit-line me-1"></i> Edit Courier Details
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
@if(!$signedAgreement && !$isCompleted && $unsignedAgreements->count())
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Upload Signed Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Agreement Number *</label>
                        <input type="text" class="form-control" name="agreement_number" required 
                               value="{{ $unsignedAgreements->last()?->agreement_number }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Signed Agreement (PDF) *</label>
                        <input type="file" class="form-control" name="agreement_file" accept=".pdf" required>
                        <small class="text-muted">Max: 10MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="uploadBtn">
                        <i class="ri-file-upload-line me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Courier Modal -->
@if($signedAgreement && !$signedAgreement->courierDetails && !$isCompleted)
<div class="modal fade" id="courierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="courierForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Courier Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Courier Name *</label>
                        <input type="text" class="form-control" name="courier_name" required 
                               placeholder="e.g., Blue Dart, DTDC, FedEx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Docket / Tracking Number *</label>
                        <input type="text" class="form-control" name="docket_number" required 
                               placeholder="Enter tracking number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dispatch Date *</label>
                        <input type="date" class="form-control" name="dispatch_date" required 
                               value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="courierBtn">
                        <i class="ri-save-line me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const token = $('meta[name="csrf-token"]').attr('content');

    // Upload Form
    @if(!$signedAgreement && !$isCompleted && $unsignedAgreements->count())
    $('#uploadForm').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        let data = new FormData(form[0]);
        let btn = $('#uploadBtn');
        
        $.ajax({
            url: "{{ route('submitter.agreement.upload-signed', $requisition) }}",
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            headers: {'X-CSRF-TOKEN': token},
            beforeSend: () => btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Uploading...'),
            success: (res) => {
                if(res.success) {
                    alert('Signed agreement uploaded successfully!');
                    window.location.reload();
                }
            },
            error: (xhr) => {
                let msg = xhr.responseJSON?.message || 'Upload failed';
                alert(msg);
                btn.prop('disabled', false).html('<i class="ri-file-upload-line me-1"></i> Upload');
            }
        });
    });
    @endif

    // Courier Form
    @if($signedAgreement && !$signedAgreement->courierDetails && !$isCompleted)
    $('#courierForm').submit(function(e) {
        e.preventDefault();
        let data = $(this).serialize();
        let btn = $('#courierBtn');
        
        $.ajax({
            url: "{{ route('submitter.agreement.save-courier-details', [$requisition, $signedAgreement]) }}",
            type: 'POST',
            data: data,
            headers: {'X-CSRF-TOKEN': token},
            beforeSend: () => btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Saving...'),
            success: (res) => {
                if(res.success) {
                    alert('Courier details saved successfully!');
                    window.location.reload();
                }
            },
            error: (xhr) => {
                let msg = xhr.responseJSON?.message || 'Save failed';
                alert(msg);
                btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i> Save');
            }
        });
    });
    @endif
});
</script>
@endpush