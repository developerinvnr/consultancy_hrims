<div class="modal fade" id="uploadEstampModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload eStamp Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="uploadEstampForm" enctype="multipart/form-data">

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Candidate</label>
                        <input type="text" class="form-control" id="estampCandidateInfo" readonly>
                    </div>

                    <div class="mb-3">
                        <label>Upload eStamp PDF (First Page)</label>
                        <input type="file"
                            name="estamp_file"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.pdf"
                            required>

                        <small class="text-muted">
                            Upload only the 1-page eStamp file.
                        </small>
                    </div>

                    <input type="hidden" name="candidate_id" id="estampCandidateId">

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-warning">
                        Upload eStamp
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>