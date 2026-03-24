<!-- Current Documents Display -->
<div class="mb-4">
    <h5 class="border-bottom pb-2">Current Documents</h5>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Number</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                // Get requisition documents if they exist
                $requisitionDocs = $candidate->requisition ? $candidate->requisition->documents : collect();
                @endphp

                {{-- Show Agreement Documents --}}
                @foreach($candidate->agreementDocuments as $doc)
                <tr>
                    <td>
                        @if($doc->document_type == 'agreement')
                        Agreement
                        @if($doc->stamp_type)
                        ({{ $doc->stamp_type }})
                        @endif
                        @else
                        {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                        @endif
                    </td>
                    <td>{{ $doc->agreement_number ?? 'N/A' }}</td>
                    <td>
                        @if($doc->sign_status == 'SIGNED')
                        <span class="badge badge-success">Signed</span>
                        @elseif($doc->sign_status == 'UNSIGNED')
                        <span class="badge badge-warning">Unsigned</span>
                        @else
                        <span class="badge badge-secondary">{{ $doc->sign_status ?? 'Uploaded' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($doc->file_url)
                        <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach

                {{-- Show Requisition Documents (PAN, Aadhaar, Bank, etc.) --}}
                @foreach($requisitionDocs as $doc)
                <tr>
                    <td>
                        @if($doc->document_type == 'pan_card')
                        PAN Card
                        @elseif($doc->document_type == 'aadhaar_card')
                        Aadhaar Card
                        @elseif($doc->document_type == 'bank_document')
                        Bank Document
                        @elseif($doc->document_type == 'resume')
                        Resume
                        @elseif($doc->document_type == 'driving_licence')
                        Driving Licence
                        @else
                        {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                        @endif
                    </td>
                    <td>{{ $doc->document_number ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-info">Supporting Document</span>
                    </td>
                    <td>
                        @if($doc->file_path)
                        <a href="{{ Storage::disk('s3')->url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach

                @if($candidate->agreementDocuments->isEmpty() && $requisitionDocs->isEmpty())
                <tr>
                    <td colspan="4" class="text-center">No documents found</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Document Upload Sections -->
<div class="row">
    <div class="col-md-12">
        <h5 class="border-bottom pb-2 mb-3">Upload Missing Documents</h5>
        <p class="text-muted"><i class="fas fa-info-circle"></i> You can upload any missing documents. All fields are optional.</p>
    </div>

    @php
    $requisitionDocs = $candidate->requisition ? $candidate->requisition->documents : collect();
    @endphp

    <!-- Unsigned Agreement Upload -->
    <div class="col-md-6 mb-3">
        @php
        $hasUnsignedAgreement = $candidate->agreementDocuments
        ->where('document_type', 'agreement')
        ->where('sign_status', 'UNSIGNED')
        ->count() > 0;
        @endphp
        <div class="card {{ $hasUnsignedAgreement ? 'border-success' : 'border-warning' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Unsigned Agreement</h6>
                @if($hasUnsignedAgreement)
                <span class="badge badge-success">Uploaded</span>
                @else
                <span class="badge badge-warning">Missing</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Agreement Number</label>
                    <input type="text" name="unsigned_agreement_number" class="form-control" placeholder="Enter agreement number (optional)">
                </div>
                <div class="form-group">
                    <label>Upload File (PDF only)</label>
                    <div class="custom-file">
                        <input type="file" name="unsigned_agreement_file" class="custom-file-input" accept=".pdf">
                        <label class="custom-file-label">Choose PDF file</label>
                    </div>
                    <small class="text-muted">Max size: 2MB, PDF only</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Signed Agreement Upload -->
    <div class="col-md-6 mb-3">
        @php
        $hasSignedAgreement = $candidate->agreementDocuments
        ->where('document_type', 'agreement')
        ->where('sign_status', 'SIGNED')
        ->count() > 0;
        @endphp
        <div class="card {{ $hasSignedAgreement ? 'border-success' : 'border-warning' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Signed Agreement</h6>
                @if($hasSignedAgreement)
                <span class="badge badge-success">Uploaded</span>
                @else
                <span class="badge badge-warning">Missing</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Agreement Number</label>
                    <input type="text" name="signed_agreement_number" class="form-control" placeholder="Enter agreement number (optional)">
                </div>
                <div class="form-group">
                    <label>Upload File (PDF only)</label>
                    <div class="custom-file">
                        <input type="file" name="signed_agreement_file" class="custom-file-input" accept=".pdf">
                        <label class="custom-file-label">Choose PDF file</label>
                    </div>
                    <small class="text-muted">Max size: 2MB, PDF only</small>
                </div>
            </div>
        </div>
    </div>

    <!-- PAN Card Upload -->
    <div class="col-md-4 mb-3">
        @php
        $hasPanDoc = $requisitionDocs->where('document_type', 'pan_card')->isNotEmpty();
        @endphp
        <div class="card {{ $hasPanDoc ? 'border-success' : 'border-warning' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    PAN Card

                    @if($candidate->pan_status_2 == 'Operative')
                    <span class="badge badge-success ml-2">✔ Operative</span>
                    @elseif($candidate->pan_status_2 == 'Inoperative')
                    <span class="badge badge-danger ml-2">✖ Inoperative</span>
                    @elseif($candidate->pan_status_2)
                    <span class="badge badge-warning ml-2">
                        {{ $candidate->pan_status_2 }}
                    </span>
                    @else
                    <span class="badge badge-secondary ml-2">
                        Not Verified
                    </span>
                    @endif

                </h6>
                @if($hasPanDoc)
                <span class="badge badge-success">Uploaded</span>
                @else
                <span class="badge badge-warning">Missing</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>PAN Number</label>
                    <input type="text" name="pan_document_number" class="form-control" placeholder="Enter PAN number (optional)">
                </div>
                <div class="form-group">
                    <label>Upload File</label>
                    <div class="custom-file">
                        <input type="file" name="pan_document" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png">
                        <label class="custom-file-label">Choose file</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aadhaar Card Upload -->
    <div class="col-md-4 mb-3">
        @php
        $hasAadhaarDoc = $requisitionDocs->where('document_type', 'aadhaar_card')->isNotEmpty();
        @endphp
        <div class="card {{ $hasAadhaarDoc ? 'border-success' : 'border-warning' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Aadhaar Card</h6>
                @if($hasAadhaarDoc)
                <span class="badge badge-success">Uploaded</span>
                @else
                <span class="badge badge-warning">Missing</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Aadhaar Number</label>
                    <input type="text" name="aadhaar_document_number" class="form-control" placeholder="Enter Aadhaar number (optional)">
                </div>
                <div class="form-group">
                    <label>Upload File</label>
                    <div class="custom-file">
                        <input type="file" name="aadhaar_document" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png">
                        <label class="custom-file-label">Choose file</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Document Upload -->
    <div class="col-md-4 mb-3">
        @php
        $hasBankDoc = $requisitionDocs->where('document_type', 'bank_document')->isNotEmpty();
        @endphp
        <div class="card {{ $hasBankDoc ? 'border-success' : 'border-warning' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Bank Document</h6>
                @if($hasBankDoc)
                <span class="badge badge-success">Uploaded</span>
                @else
                <span class="badge badge-warning">Missing</span>
                @endif
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="bank_document_number" class="form-control" placeholder="Enter account number (optional)">
                </div>
                <div class="form-group">
                    <label>Upload File</label>
                    <div class="custom-file">
                        <input type="file" name="bank_document" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png">
                        <label class="custom-file-label">Choose file</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Other Document Upload -->
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Other Document</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="other_document_type" class="form-control">
                                <option value="">Select Type (optional)</option>
                                <option value="id_proof">ID Proof</option>
                                <option value="address_proof">Address Proof</option>
                                <option value="qualification">Qualification Certificate</option>
                                <option value="experience">Experience Certificate</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Number</label>
                            <input type="text" name="other_document_number" class="form-control" placeholder="Enter document number (optional)">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Upload File</label>
                            <div class="custom-file">
                                <input type="file" name="other_document" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png">
                                <label class="custom-file-label">Choose file</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Update file input labels
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
</script>
@endpush