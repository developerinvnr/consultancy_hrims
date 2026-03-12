<!-- Upload Signed Agreement Modal (from Email) -->
<div class="modal fade" id="uploadSignedModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Signed Agreement (from Email)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadSignedEmailForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Candidate</label>
                        <input type="text" class="form-control" id="signedCandidateInfo" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Agreement Number *</label>
                        <input type="text" class="form-control" id="signedAgreementNumber" name="agreement_number" required
                            placeholder="Enter agreement number" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Signed Agreement File (PDF) *</label>
                        <input type="file" class="form-control" name="agreement_file"
                            accept=".pdf" required>
                        <small class="text-muted">Maximum file size: 10MB</small>
                    </div>

                    <input type="hidden" name="candidate_id" id="signedCandidateId">
                    <input type="hidden" name="source" value="email">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Upload Signed Agreement</button>
                </div>
            </form>
        </div>
    </div>
</div>