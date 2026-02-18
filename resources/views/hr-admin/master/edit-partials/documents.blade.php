<!-- Existing Documents -->
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
                @forelse($candidate->agreementDocuments as $doc)
                <tr>
                    <td>
                        @if($doc->document_type == 'agreement')
                            Agreement 
                            @if($doc->stamp_type)
                                ({{ $doc->stamp_type }})
                            @endif
                        @else
                            {{ ucfirst($doc->document_type) }}
                        @endif
                    </td>
                    <td>{{ $doc->agreement_number ?? 'N/A' }}</td>
                    <td>
                        @if($doc->sign_status == 'SIGNED')
                            <span class="badge badge-success">Signed</span>
                        @elseif($doc->sign_status == 'UNSIGNED')
                            <span class="badge badge-warning">Unsigned</span>
                        @else
                            <span class="badge badge-secondary">{{ $doc->sign_status ?? 'N/A' }}</span>
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
                @empty
                <tr>
                    <td colspan="4" class="text-center">No documents found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Upload New Agreement -->
<div class="mb-4">
    <h5 class="border-bottom pb-2">Upload New Agreement</h5>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Agreement Type</label>
                <select name="agreement_type" class="form-control">
                    <option value="">Select Type</option>
                    <option value="unsigned">Unsigned Agreement</option>
                    <option value="signed">Signed Agreement</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Agreement Number</label>
                <input type="text" name="new_agreement_number" class="form-control" 
                       placeholder="Enter agreement number">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Upload File (PDF only)</label>
                <div class="custom-file">
                    <input type="file" name="new_agreement_file" class="custom-file-input" accept=".pdf">
                    <label class="custom-file-label">Choose file</label>
                </div>
                <small class="text-muted">Max size: 10MB, PDF only</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="agreement_remarks" class="form-control" 
                       placeholder="Reason for upload">
            </div>
        </div>
    </div>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Uploading a new agreement will replace the existing unsigned agreement.
    </div>
</div>

<!-- Upload Other Documents -->
<div>
    <h5 class="border-bottom pb-2">Upload Other Documents</h5>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Document Type</label>
                <select name="other_doc_type" class="form-control">
                    <option value="">Select</option>
                    <option value="id_proof">ID Proof</option>
                    <option value="address_proof">Address Proof</option>
                    <option value="bank_document">Bank Document</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Document Number</label>
                <input type="text" name="other_doc_number" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Upload File</label>
                <div class="custom-file">
                    <input type="file" name="other_doc_file" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="custom-file-label">Choose file</label>
                </div>
            </div>
        </div>
    </div>
</div>
