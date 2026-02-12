@extends('layouts.guest')

@section('content')
<div class="container-fluid px-2 py-2">
    <!-- Upload Section - Compact Horizontal Layout -->
    <div class="row g-1 mb-2">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 px-3 border-bottom">
                    <h6 class="mb-0 text-primary fw-600 d-flex align-items-center">
                        <i class="ri-file-upload-line me-2"></i> Import Candidates
                        <span class="badge bg-light text-dark ms-2 fw-normal">Excel Upload</span>
                    </h6>
                </div>
                <div class="card-body p-2">
                    <!-- Compact Instruction -->
                    <div class="alert alert-light border-0 bg-light py-1 px-2 mb-2 small">
                        <i class="ri-information-line text-primary me-1"></i>
                        <strong>Quick Guide:</strong> Fill candidate data → Upload Excel → Review → Import
                    </div>

                    <form id="uploadForm" class="row g-2 align-items-end">
                        @csrf
                        <!-- Row 1: All controls in single line -->
                        <div class="col-lg-3 col-md-4">
                            <label class="small fw-bold mb-0 text-muted">
                                <i class="ri-price-tag-3-line me-1"></i>Requisition Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="requisitionType" name="requisition_type" required>
                                <option value="">Select Type</option>
                                <option value="Contractual">📄 Contractual</option>
                                <option value="TFA">🎯 TFA</option>
                                <option value="CB">💼 CB</option>
                            </select>
                        </div>

                        <div class="col-lg-5 col-md-5">
                            <label class="small fw-bold mb-0 text-muted">
                                <i class="ri-file-excel-2-line me-1"></i>Excel File
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control form-control-sm" id="excelFile" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <span class="input-group-text bg-light small">Max 2MB</span>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-3">
                            <label class="small fw-bold mb-0 text-muted opacity-0 d-none d-md-block">Action</label>
                            <button type="button" class="btn btn-sm btn-primary w-100" id="previewBtn">
                                <i class="ri-search-line me-1"></i> Upload & Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div id="previewSection" class="row g-1 mb-2" style="display: none;">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-primary fw-600">
                        <i class="ri-table-line me-2"></i> Data Preview
                    </h6>
                    <span class="badge bg-light text-dark" id="previewRowCount">0 rows</span>
                </div>
                <div class="card-body p-2">
                    <div id="previewStats" class="alert alert-light border-0 bg-light py-1 px-2 mb-2 small"></div>

                    <!-- Scrollable Table -->
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm table-hover table-bordered small mb-2">
                            <thead class="table-light position-sticky top-0">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Candidate Name</th>
                                    <th>Email</th>
                                    <th width="100">Mobile</th>
                                    <th width="110">Joining Date</th>
                                    <th width="80">Status</th>
                                    <th>Errors</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <button type="button" class="btn btn-sm btn-success" id="importBtn" style="display: none;">
                                <i class="ri-upload-2-line me-1"></i> <span id="importBtnText">Import Valid Data</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="cancelPreviewBtn">
                                <i class="ri-close-line me-1"></i> Cancel
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="ri-checkbox-circle-line text-success"></i> Valid rows will be imported
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Results -->
    <div id="importResults" class="row g-1 mb-2" style="display: none;">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 border-success">
                <div class="card-header bg-success text-white py-2 px-3">
                    <h6 class="mb-0 fw-600 d-flex align-items-center">
                        <i class="ri-checkbox-circle-line me-2"></i> Import Results
                        <button type="button" class="btn-close btn-close-white ms-auto" id="closeResultsBtn"></button>
                    </h6>
                </div>
                <div class="card-body p-2" id="importResultsContent"></div>
            </div>
        </div>
    </div>

    <!-- Imported Candidates List -->
    <div class="row g-1">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-primary fw-600">
                        <i class="ri-team-line me-2"></i> Imported Candidates
                        <span class="badge bg-primary bg-opacity-10 text-primary ms-2 fw-normal" id="candidateCountBadge">0</span>
                    </h6>
                    <div>
                        <div class="input-group input-group-sm me-2" style="width: 200px;">
                            <input type="text" class="form-control form-control-sm" id="searchCandidates" placeholder="Search name, email, mobile...">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="ri-search-line"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" id="refreshListBtn" title="Refresh">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered small mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Candidate Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                    <th width="50">Docs</th>
                                    <th width="140">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="candidatesTableBody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Loading candidates...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="paginationSection" class="d-flex justify-content-center mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document Upload Modal - Compact -->
<div class="modal fade" id="documentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header bg-white py-2 px-3 border-bottom">
                <h6 class="modal-title text-primary fw-600">
                    <i class="ri-upload-cloud-2-line me-2"></i> Document Upload
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="documentModalContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div><!-- Document Upload Modal - Enhanced with Data Extraction -->
<div class="modal fade" id="documentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-white py-2 px-3 border-bottom">
                <h6 class="modal-title text-primary fw-600">
                    <i class="ri-upload-cloud-2-line me-2"></i> Document Upload & Data Extraction
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="documentModalContent">
                <!-- Content loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Loading document upload interface...
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Close
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="saveAllDocumentsBtn" style="display: none;">
                    <i class="ri-save-line me-1"></i> Save All Documents & Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Document Modal -->
<div class="modal fade" id="previewDocumentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title">
                    <i class="ri-file-text-line me-2"></i> Document Preview
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-light" style="min-height: 400px;">
                <img id="previewDocumentImage" src="" class="img-fluid" style="max-height: 70vh; display: none;">
                <iframe id="previewDocumentPdf" src="" style="width: 100%; height: 70vh; display: none;"></iframe>
                <div id="noPreview" class="p-5 text-muted" style="display: none;">
                    <i class="ri-eye-off-line fa-3x mb-3"></i>
                    <p>Preview not available</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Ultra Compact UI */
    body {
        font-size: 12px;
        background-color: #f8f9fa;
    }

    .container-fluid {
        max-width: 1600px;
    }

    /* Cards */
    .card {
        border-radius: 6px;
        border: 1px solid rgba(0, 0, 0, .05);
    }

    .card-header {
        background-color: #fff;
        border-bottom-width: 1px;
    }

    .card-body {
        padding: 0.75rem !important;
    }

    /* Typography */
    .fw-600 {
        font-weight: 600;
    }

    .small {
        font-size: 11px !important;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        font-weight: 600;
        color: #495057;
        border-bottom-width: 1px;
        padding: 8px 6px;
        font-size: 11px;
        white-space: nowrap;
    }

    .table td {
        padding: 8px 6px;
        vertical-align: middle;
        font-size: 11px;
    }

    /* Form Controls */
    .form-select-sm,
    .form-control-sm {
        font-size: 11px;
        padding: 4px 8px;
        height: 31px;
    }

    .input-group-text {
        font-size: 11px;
        padding: 4px 10px;
    }

    .btn-sm {
        font-size: 11px;
        padding: 6px 12px;
        height: 31px;
    }

    .btn-sm {
        font-size: 10px;
        padding: 2px 8px;
        height: 24px;
    }

    /* Badges */
    .badge {
        font-size: 10px;
        padding: 4px 6px;
        font-weight: 500;
    }

    /* Alerts */
    .alert {
        padding: 6px 12px;
        margin-bottom: 0;
        font-size: 11px;
    }

    /* Document Cards */
    .document-card {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .document-card:hover {
        box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
    }

    .document-uploaded {
        border-left: 4px solid #28a745;
        background-color: #f8fff9;
    }

    .document-missing {
        border-left: 4px solid #dc3545;
        background-color: #fff8f8;
    }

    .document-card h6 {
        font-size: 12px;
        margin-bottom: 4px;
    }

    /* Pagination */
    .pagination-sm .page-link {
        font-size: 11px;
        padding: 4px 10px;
    }

    /* Scrollable Table */
    .table-responsive {
        border-radius: 4px;
    }

    .position-sticky {
        z-index: 10;
    }

    /* Utilities */
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .opacity-0 {
        opacity: 0;
    }

    /* Modal */
    .modal-header {
        padding: 10px 15px;
    }

    .modal-body {
        padding: 15px;
    }

    .modal-footer {
        padding: 10px 15px;
    }

    /* Custom File Input */
    .form-control[type="file"] {
        padding: 3px 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-body {
            padding: 0.5rem !important;
        }

        .table td,
        .table th {
            padding: 6px 4px;
        }

        .d-none.d-md-block {
            display: none !important;
        }
    }
</style>
@endpush

@section('script_section')
<script>
    $(document).ready(function() {
        // Constants
        const ROUTES = {
            preview: '{{ route("import.preview") }}',
            import: '{{ route("import.data") }}',
            candidatesList: '{{ route("import.candidates.list") }}',
            documentsGet: '{{ route("import.candidates.documents", ":id") }}',
            documentsUpload: '{{ route("import.documents.upload") }}',
            documentsDelete: '{{ route("import.documents.delete", ":id") }}',
            documentsDownload: '{{ route("import.documents.download", ":id") }}'
        };

        const CSRF_TOKEN = '{{ csrf_token() }}';

        // Initialize
        init();

        function init() {
            bindEventListeners();
            loadCandidatesList();
            updateFileName();
        }

        function bindEventListeners() {
            // File input label update
            $('#excelFile').on('change', updateFileName);

            // Preview button
            $('#previewBtn').on('click', previewExcel);

            // Import button
            $('#importBtn').on('click', importData);

            // Cancel preview
            $('#cancelPreviewBtn').on('click', function() {
                $('#previewSection').hide();
                $('#uploadForm')[0].reset();
                $('#excelFile').val('');
                updateFileName();
            });

            // Close results
            $('#closeResultsBtn').on('click', function() {
                $('#importResults').hide();
            });

            // Refresh list
            $('#refreshListBtn').on('click', function() {
                loadCandidatesList();
            });

            // Search
            $('#searchBtn').on('click', function() {
                loadCandidatesList(1, $('#searchCandidates').val());
            });

            $('#searchCandidates').on('keypress', function(e) {
                if (e.which === 13) {
                    loadCandidatesList(1, $(this).val());
                }
            });
        }

        function updateFileName() {
            const fileInput = $('#excelFile')[0];
            if (fileInput.files.length) {
                const fileName = fileInput.files[0].name;
                $('#excelFile').next('.input-group-text').text(fileName.length > 30 ? fileName.substr(0, 27) + '...' : fileName);
            } else {
                $('#excelFile').next('.input-group-text').text('Max 2MB');
            }
        }

        function previewExcel() {
            const type = $('#requisitionType').val();

            if (!type) {
                showError('Please select Requisition Type');
                return;
            }

            const fileInput = $('#excelFile')[0];
            if (!fileInput.files.length) {
                showError('Please select a file first');
                return;
            }

            const formData = new FormData($('#uploadForm')[0]);
            formData.append('_token', CSRF_TOKEN);
            formData.append('requisition_type', type);

            const btn = $('#previewBtn');
            btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Processing...');

            $.ajax({
                url: ROUTES.preview,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-search-line me-1"></i> Upload & Preview');
                    if (response.success) {
                        // ✅ FIX: Ensure each row has an errors array
                        if (response.preview) {
                            response.preview.forEach(function(row) {
                                if (!row.errors) {
                                    row.errors = [];
                                }
                            });
                        }
                        showPreview(response);
                    } else {
                        showError(response.message || 'Preview failed');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-search-line me-1"></i> Upload & Preview');
                    handleAjaxError(xhr, 'Preview');
                }
            });
        }

        function showPreview(data) {
            let html = '';

            data.preview.forEach(function(row) {
                const email = row.data['E Mail'] || row.data['Email Address'] || 'N/A';
                const isValid = row.valid;

                // ✅ FIX: Check if errors exists and has length
                const errors = row.errors || [];
                const errorCount = errors.length;

                html += `<tr>
                    <td class="fw-bold">${row.row_index}</td>
                    <td>${row.data["Candidate's Name"] || '-'}</td>
                    <td><span class="text-truncate d-inline-block" style="max-width: 200px;">${email}</span></td>
                    <td>${row.data['Mobile No.'] || '-'}</td>
                    <td>${row.data['Date of Joining Required'] || '-'}</td>
                    <td>
                        <span class="badge bg-${isValid ? 'success' : 'danger'} bg-opacity-10 text-${isValid ? 'success' : 'danger'}">
                            ${isValid ? '✓ Valid' : '✗ Invalid'}
                        </span>
                    </td>
                    <td class="text-${isValid ? 'muted' : 'danger'} small">
                        ${isValid ? '-' : errors.slice(0, 2).join('<br>')}
                        ${errorCount > 2 ? '<br><span class="text-muted">+' + (errorCount - 2) + ' more</span>' : ''}
                    </td>
                </tr>`;
            });

            $('#previewTableBody').html(html);
            $('#previewRowCount').text(`${data.total_rows} rows`);

            // Update stats
            let statsHtml = `
                <div class="row g-2">
                    <div class="col-auto">
                        <span class="text-muted">Total:</span>
                        <span class="fw-bold ms-1">${data.total_rows}</span>
                    </div>
                    <div class="col-auto">
                        <span class="text-muted">Valid:</span>
                        <span class="fw-bold text-success ms-1">${data.valid_rows}</span>
                    </div>
                    <div class="col-auto">
                        <span class="text-muted">Invalid:</span>
                        <span class="fw-bold text-danger ms-1">${data.invalid_rows}</span>
                    </div>
                    <div class="col-auto">
                        <span class="text-muted">Ready:</span>
                        <span class="badge bg-${data.valid_rows > 0 ? 'success' : 'secondary'} bg-opacity-10 text-${data.valid_rows > 0 ? 'success' : 'secondary'} ms-1">
                            ${data.valid_rows > 0 ? 'Yes' : 'No'}
                        </span>
                    </div>
                </div>
            `;

            if (data.invalid_rows > 0) {
                statsHtml += `
                    <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">
                        <i class="ri-error-warning-line me-1"></i>
                        <strong>Note:</strong> Invalid rows will be skipped. Check error column for details.
                    </div>
                `;
            }

            $('#previewStats').html(statsHtml);

            // Show/hide import button
            if (data.valid_rows > 0) {
                $('#importBtn').show();
                $('#importBtnText').text(`Import ${data.valid_rows} Valid Row${data.valid_rows > 1 ? 's' : ''}`);
            } else {
                $('#importBtn').hide();
            }

            $('#previewSection').show();
            scrollToElement('#previewSection');
        }

        function importData() {
            const btn = $('#importBtn');
            btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> Importing...');

            $.ajax({
                url: ROUTES.import,
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN
                },
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="ri-upload-cloud-2-line me-1"></i> <span id="importBtnText">Import Valid Data</span>');
                    if (response.success) {
                        // ✅ FIX: Don't show errors in the success message if there were successful imports
                        if (response.data.success > 0) {
                            // Only show success count, not individual errors
                            response.data.errors = [];

                            // Add a note if some rows failed
                            if (response.data.failed > 0) {
                                response.data.errors = [
                                    `${response.data.failed} rows could not be imported due to system configuration. The issue has been logged.`
                                ];
                            }
                        }
                        showImportResults(response.data);
                        loadCandidatesList();
                    } else {
                        showError(response.message || 'Import failed');
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="ri-upload-cloud-2-line me-1"></i> <span id="importBtnText">Import Valid Data</span>');

                    // ✅ FIX: Show generic error message for constraint violations
                    if (xhr.responseJSON?.message?.includes('SQLSTATE') ||
                        xhr.responseJSON?.message?.includes('constraint')) {
                        showError('Database configuration issue. Please contact administrator.');
                    } else {
                        handleAjaxError(xhr, 'Import');
                    }
                }
            });
        }

        function showImportResults(data) {
            let html = `
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <span class="badge bg-success me-2">Success: ${data.success}</span>
                        <span class="badge bg-danger me-2">Failed: ${data.failed}</span>
                    </div>
                    <small class="text-muted">${new Date().toLocaleString()}</small>
                </div>
            `;

            if (data.imported.length > 0) {
                html += `
                    <div class="small">
                        <span class="fw-bold mb-1 d-block">Imported Candidates:</span>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Requisition ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                data.imported.slice(0, 5).forEach(function(candidate) {
                    html += `
                        <tr>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary">${candidate.candidate_code}</span></td>
                            <td>${candidate.candidate_name}</td>
                            <td>${candidate.candidate_email}</td>
                            <td>${candidate.requisition_id}</td>
                        </tr>
                    `;
                });

                if (data.imported.length > 5) {
                    html += `<tr><td colspan="4" class="text-center text-muted py-1">+${data.imported.length - 5} more candidates</td></tr>`;
                }

                html += '</tbody></table></div>';
            }

            if (data.errors.length > 0) {
                html += '<div class="alert alert-danger py-1 px-2 mt-2 mb-0 small">';
                html += '<span class="fw-bold">Errors:</span><br>';
                html += data.errors.slice(0, 3).join('<br>');
                if (data.errors.length > 3) {
                    html += `<br><span class="text-muted">+${data.errors.length - 3} more errors</span>`;
                }
                html += '</div>';
            }

            $('#importResultsContent').html(html);
            $('#importResults').show();
            $('#previewSection').hide();
            scrollToElement('#importResults');
        }

        function loadCandidatesList(page = 1, search = '') {
            $.ajax({
                url: ROUTES.candidatesList,
                type: 'GET',
                data: {
                    page: page,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        renderCandidatesTable(response.candidates.data, response.pagination);
                        $('#candidateCountBadge').text(response.pagination.total || 0);
                    }
                },
                error: function(xhr) {
                    $('#candidatesTableBody').html('<tr><td colspan="8" class="text-center text-danger py-3">Failed to load candidates</td></tr>');
                }
            });
        }

        function renderCandidatesTable(requisitions, pagination) {
            let html = '';

            if (requisitions.length === 0) {
                html = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="ri-inbox-line fa-2x mb-2 d-block"></i>No imported candidates found</td></tr>';
            } else {
                requisitions.forEach(function(requisition) {
                    const candidateName = requisition.candidate_name || requisition.candidate?.candidate_name || 'N/A';
                    const candidateEmail = requisition.candidate_email || requisition.candidate?.candidate_email || 'N/A';
                    const mobileNo = requisition.mobile_no || requisition.candidate?.mobile_no || 'N/A';
                    const candidateCode = requisition.candidate_code || requisition.candidate?.candidate_code || 'N/A';
                    const contractStartDate = requisition.contract_start_date || requisition.candidate?.contract_start_date || 'N/A';

                    html += `
                        <tr>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary">${candidateCode}</span></td>
                            <td class="fw-bold">${candidateName}</td>
                            <td><span class="text-truncate d-inline-block" style="max-width: 180px;">${candidateEmail}</span></td>
                            <td>${mobileNo}</td>
                            <td>${contractStartDate}</td>
                            <td>
                                <span class="badge bg-${requisition.has_all_documents ? 'success' : 'warning'} bg-opacity-10 text-${requisition.has_all_documents ? 'success' : 'warning'}">
                                    ${requisition.has_all_documents ? '✓ Complete' : '⏳ Pending'}
                                </span>
                            </td>
                            <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info">${requisition.document_count}</span></td>
                            <!-- <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-sm upload-docs-btn" 
                                        data-requisition-id="${requisition.id}" 
                                        data-candidate-name="${candidateName}"
                                        title="Upload Documents">
                                        <i class="ri-upload-line fs-10"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm view-docs-btn" 
                                        data-requisition-id="${requisition.id}"
                                        title="View Documents">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>-->
                        </tr>
                    `;
                });
            }

            $('#candidatesTableBody').html(html);
            renderPagination(pagination);

            // Bind click events
            $('.upload-docs-btn, .view-docs-btn').on('click', function() {
                const requisitionId = $(this).data('requisition-id');
                const candidateName = $(this).data('candidate-name') || '';
                openDocumentModal(requisitionId, candidateName);
            });
        }

        function renderPagination(pagination) {
            if (pagination.last_page <= 1) {
                $('#paginationSection').empty();
                return;
            }

            let html = '<nav><ul class="pagination pagination-sm mb-0">';

            // Previous
            html += `
                <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                </li>
            `;

            // Pages
            for (let i = 1; i <= pagination.last_page; i++) {
                if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    html += `
                        <li class="page-item ${pagination.current_page === i ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            html += `
                <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                </li>
            `;

            html += '</ul></nav>';
            $('#paginationSection').html(html);

            // Bind pagination
            $('#paginationSection .page-link').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page >= 1 && page <= pagination.last_page) {
                    loadCandidatesList(page, $('#searchCandidates').val());
                }
            });
        }

        function openDocumentModal(requisitionId, candidateName) {
            $.ajax({
                url: ROUTES.documentsGet.replace(':id', requisitionId),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        renderDocumentUploadForm(response, candidateName);
                        $('#documentModal').modal('show');
                    }
                },
                error: function(xhr) {
                    showError('Failed to load documents');
                }
            });
        }

        function renderDocumentUploadForm(data, candidateName) {
            const requisition = data.requisition;
            const documents = data.documents;

            let html = `
        <form id="documentUploadForm" enctype="multipart/form-data">
            <input type="hidden" name="requisition_id" value="${requisition.id}">
            <input type="hidden" name="requisition_type" value="${requisition.requisition_type}">
            <input type="hidden" name="candidate_id" value="${requisition.candidate_id || ''}">
            
            <div class="mb-3">
                <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                    <div class="flex-shrink-0">
                        <i class="ri-user-3-line fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">${candidateName || requisition.candidate_name}</h6>
                        <small class="text-muted d-block">
                            Requisition ID: ${requisition.requisition_id} | 
                            Candidate Code: ${requisition.candidate_code || 'Not assigned'}
                        </small>
                    </div>
                </div>
            </div>
    `;

            // PAN Card Section
            html += renderDocumentSection('pan_card', documents.pan_card, requisition);

            // Aadhaar Card Section
            html += renderDocumentSection('aadhaar_card', documents.aadhaar_card, requisition);

            // Bank Document Section
            html += renderBankDocumentSection(documents.bank_document, requisition);

            // Resume Section
            html += renderSimpleDocumentSection('resume', documents.resume);

            // Driving Licence Section
            html += renderDocumentSection('driving_licence', documents.driving_licence, requisition);

            // Other Document Section
            html += renderSimpleDocumentSection('other', documents.other);

            html += `</form>`;

            $('#documentModalContent').html(html);

            // Show save button
            $('#saveAllDocumentsBtn').show();

            // Bind events
            bindDocumentEvents(requisition.requisition_type);
        }

        function bindDocumentEvents(requisitionType) {
            // Show upload button when file is selected
            $('.document-file-input').on('change', function() {
                const uploadBtn = $(this).closest('.input-group').find('.upload-doc-btn');
                if (this.files.length > 0) {
                    uploadBtn.show();
                } else {
                    uploadBtn.hide();
                }
            });

            // Upload document button click
            $('.upload-doc-btn').on('click', function(e) {
                e.preventDefault();
                const docType = $(this).data('doc-type');
                const fileInput = $(`#${docType}_file`);

                if (fileInput[0].files.length === 0) {
                    showError('Please select a file first');
                    return;
                }

                uploadDocumentWithExtraction(fileInput[0].files[0], docType, requisitionType);
            });

            // Preview document
            $('.preview-doc-btn').on('click', function() {
                previewDocument($(this).data('document-id'));
            });

            // Delete document
            $('.delete-doc-btn').on('click', function() {
                deleteDocument($(this).data('document-id'), $(this).data('doc-type'));
            });

            // Save All Documents button
            $('#saveAllDocumentsBtn').off('click').on('click', function() {
                saveAllExtractedData();
            });
        }

        function uploadDocumentWithExtraction(file, docType, requisitionType) {
            const formData = new FormData();
            formData.append('document_file', file);
            formData.append('document_type', docType);
            formData.append('requisition_id', $('#requisition_id').val());
            formData.append('requisition_type', requisitionType);
            formData.append('_token', CSRF_TOKEN);

            const uploadBtn = $(`.upload-doc-btn[data-doc-type="${docType}"]`);
            const originalText = uploadBtn.html();
            uploadBtn.html('<i class="ri-loader-4-line ri-spin"></i> Uploading...').prop('disabled', true);

            $.ajax({
                url: ROUTES.documentsUpload,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showSuccess(`${getDocumentTypeName(docType)} uploaded successfully`);

                        // If this is a document that has data extraction, process it
                        if (['pan_card', 'aadhaar_card', 'bank_document'].includes(docType)) {
                            extractDocumentData(file, docType, requisitionType);
                        } else {
                            // Just reload the modal for simple documents
                            setTimeout(() => {
                                openDocumentModal($('#requisition_id').val(), '');
                            }, 1000);
                        }
                    } else {
                        showError(response.message || 'Upload failed');
                        uploadBtn.html(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr, 'Upload');
                    uploadBtn.html(originalText).prop('disabled', false);
                }
            });
        }

        function extractDocumentData(file, docType, requisitionType) {
            const formData = new FormData();
            formData.append(`${docType}_file`, file);
            formData.append('requisition_type', requisitionType);
            formData.append('_token', CSRF_TOKEN);

            let url = '';
            switch (docType) {
                case 'pan_card':
                    url = '{{ route("process.pan.card") }}';
                    break;
                case 'aadhaar_card':
                    url = '{{ route("process.aadhaar.card") }}';
                    break;
                case 'bank_document':
                    url = '{{ route("process.bank.document") }}';
                    break;
            }

            if (!url) return;

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'SUCCESS') {
                        populateExtractedData(docType, response);
                        showSuccess(`${getDocumentTypeName(docType)} data extracted successfully`);
                    } else {
                        showWarning('Document uploaded but data extraction failed. Please enter manually.');
                    }
                },
                error: function() {
                    showWarning('Document uploaded but data extraction failed. Please enter manually.');
                },
                complete: function() {
                    // Reload the modal to show updated document status
                    setTimeout(() => {
                        openDocumentModal($('#requisition_id').val(), '');
                    }, 1500);
                }
            });
        }

        function populateExtractedData(docType, response) {
            if (docType === 'pan_card' && response.data) {
                $('#pan_no').val(response.data.panNumber).prop('readonly', true);
                $('.verification-icon').show().find('i').addClass('text-success');

                // Store in hidden fields for submission
                if (!$('#pan_filename').length) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'pan_filename',
                        name: 'pan_filename',
                        value: response.data.filename || ''
                    }).appendTo('#documentUploadForm');

                    $('<input>').attr({
                        type: 'hidden',
                        id: 'pan_filepath',
                        name: 'pan_filepath',
                        value: response.data.filePath || ''
                    }).appendTo('#documentUploadForm');
                } else {
                    $('#pan_filename').val(response.data.filename || '');
                    $('#pan_filepath').val(response.data.filePath || '');
                }
            } else if (docType === 'aadhaar_card' && response.data) {
                $('#aadhaar_no').val(response.data.aadhaarNumber).prop('readonly', true);
                $('.verification-icon').show().find('i').addClass('text-success');

                // Store in hidden fields
                if (!$('#aadhaar_filename').length) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'aadhaar_filename',
                        name: 'aadhaar_filename',
                        value: response.data.filename || ''
                    }).appendTo('#documentUploadForm');

                    $('<input>').attr({
                        type: 'hidden',
                        id: 'aadhaar_filepath',
                        name: 'aadhaar_filepath',
                        value: response.data.filePath || ''
                    }).appendTo('#documentUploadForm');
                } else {
                    $('#aadhaar_filename').val(response.data.filename || '');
                    $('#aadhaar_filepath').val(response.data.filePath || '');
                }
            } else if (docType === 'bank_document' && response.data) {
                const data = response.data;

                if (data.verificationData?.beneficiary_name) {
                    $('#account_holder_name').val(data.verificationData.beneficiary_name);
                }

                if (data.accountNumber) {
                    $('#bank_account_no').val(data.accountNumber).prop('readonly', true);
                }

                if (data.ifscCode) {
                    $('#bank_ifsc').val(data.ifscCode).prop('readonly', true);
                }

                if (data.verificationData?.ifsc_details?.name) {
                    $('#bank_name').val(data.verificationData.ifsc_details.name);
                }

                $('.verification-icon').show();

                // Store in hidden fields
                if (!$('#bank_filename').length) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'bank_filename',
                        name: 'bank_filename',
                        value: data.filename || ''
                    }).appendTo('#documentUploadForm');

                    $('<input>').attr({
                        type: 'hidden',
                        id: 'bank_filepath',
                        name: 'bank_filepath',
                        value: data.filePath || ''
                    }).appendTo('#documentUploadForm');
                } else {
                    $('#bank_filename').val(data.filename || '');
                    $('#bank_filepath').val(data.filePath || '');
                }
            }
        }

        function saveAllExtractedData() {
            const form = $('#documentUploadForm');
            const requisitionId = $('input[name="requisition_id"]').val();
            const candidateId = $('input[name="candidate_id"]').val();

            const formData = new FormData(form[0]);
            formData.append('_token', CSRF_TOKEN);

            const saveBtn = $('#saveAllDocumentsBtn');
            const originalText = saveBtn.html();
            saveBtn.html('<i class="ri-loader-4-line ri-spin me-1"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: '{{ route("import.update.candidate.data") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showSuccess('All documents and data saved successfully');
                        $('#documentModal').modal('hide');
                        loadCandidatesList(); // Refresh the candidates list
                    } else {
                        showError(response.message || 'Failed to save data');
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr, 'Save');
                },
                complete: function() {
                    saveBtn.html(originalText).prop('disabled', false);
                }
            });
        }

        function getDocumentTypeName(docType) {
            const names = {
                'pan_card': 'PAN Card',
                'aadhaar_card': 'Aadhaar Card',
                'bank_document': 'Bank Document',
                'resume': 'Resume',
                'driving_licence': 'Driving Licence',
                'other': 'Other Document'
            };
            return names[docType] || docType;
        }

        function renderDocumentSection(docType, docData, requisition) {
            const cardClass = docData.uploaded ? 'document-uploaded' : 'document-missing';
            const icon = docData.icon || 'ri-file-line';
            const fieldLabel = docData.field_label || '';
            const fieldName = docData.field_name || '';
            const fieldValue = docData.field_value || '';

            let html = `
        <div class="document-card ${cardClass}" data-doc-type="${docType}">
            <div class="row align-items-center mb-2">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <i class="${icon} me-2 ${docData.uploaded ? 'text-success' : 'text-danger'}"></i>
                        <h6 class="mb-0 flex-grow-1">${docData.name}</h6>
                        ${docData.uploaded ? 
                            `<span class="badge bg-success bg-opacity-10 text-success">
                                <i class="ri-checkbox-circle-fill me-1"></i> Uploaded
                            </span>` : 
                            `<span class="badge bg-danger bg-opacity-10 text-danger">
                                <i class="ri-error-warning-fill me-1"></i> Required
                            </span>`
                        }
                    </div>
                </div>
            </div>
            
            <div class="row g-2">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <input type="file" 
                               class="form-control form-control-sm document-file-input" 
                               id="${docType}_file" 
                               name="${docType}_file" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               data-doc-type="${docType}"
                               ${docData.uploaded ? '' : ''}>
                        <button type="button" class="btn btn-primary btn-sm upload-doc-btn" 
                                data-doc-type="${docType}" style="display: none;">
                            <i class="ri-upload-2-line"></i> Upload
                        </button>
                    </div>
                    <small class="text-muted">PDF, JPG, PNG (Max 2MB)</small>
                </div>
                
                ${fieldLabel ? `
                <div class="col-md-5">
                    <label class="small fw-bold text-muted mb-0">${fieldLabel}</label>
                    <div class="d-flex">
                        <input type="text" 
                               class="form-control form-control-sm extracted-field" 
                               id="${fieldName}" 
                               name="${fieldName}" 
                               value="${fieldValue}" 
                               placeholder="Auto-extracted from upload"
                               ${fieldValue ? 'readonly' : ''}>
                        <span class="input-group-text bg-light verification-icon" style="display: ${fieldValue ? 'flex' : 'none'};">
                            <i class="ri-checkbox-circle-fill text-success"></i>
                        </span>
                    </div>
                </div>
                ` : ''}
            </div>
            
            ${docData.uploaded && docData.document ? `
            <div class="row mt-2">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between bg-light p-1 rounded">
                        <div class="small text-truncate" style="max-width: 250px;">
                            <i class="ri-file-line me-1"></i> ${docData.document.file_name}
                        </div>
                        <div class="btn-group btn-group-xs">
                            <button type="button" class="btn btn-outline-info btn-sm preview-doc-btn" 
                                    data-document-id="${docData.document.id}">
                                <i class="ri-eye-line"></i> Preview
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-doc-btn" 
                                    data-document-id="${docData.document.id}" 
                                    data-doc-type="${docType}">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;

            return html;
        }

        function renderBankDocumentSection(docData, requisition) {
            const cardClass = docData.uploaded ? 'document-uploaded' : 'document-missing';

            let html = `
        <div class="document-card ${cardClass}" data-doc-type="bank_document">
            <div class="row align-items-center mb-2">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <i class="ri-bank-line me-2 ${docData.uploaded ? 'text-success' : 'text-danger'}"></i>
                        <h6 class="mb-0 flex-grow-1">Bank Document</h6>
                        ${docData.uploaded ? 
                            `<span class="badge bg-success bg-opacity-10 text-success">
                                <i class="ri-checkbox-circle-fill me-1"></i> Uploaded
                            </span>` : 
                            `<span class="badge bg-danger bg-opacity-10 text-danger">
                                <i class="ri-error-warning-fill me-1"></i> Required
                            </span>`
                        }
                    </div>
                </div>
            </div>
            
            <div class="row g-2 mb-2">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <input type="file" 
                               class="form-control form-control-sm document-file-input" 
                               id="bank_document_file" 
                               name="bank_document_file" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               data-doc-type="bank_document"
                               ${docData.uploaded ? '' : ''}>
                        <button type="button" class="btn btn-primary btn-sm upload-doc-btn" 
                                data-doc-type="bank_document" style="display: none;">
                            <i class="ri-upload-2-line"></i> Upload
                        </button>
                    </div>
                    <small class="text-muted">Bank Passbook / Cancelled Cheque (PDF, JPG, PNG - Max 2MB)</small>
                </div>
            </div>
            
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">Account Holder Name</label>
                    <input type="text" class="form-control form-control-sm extracted-field" 
                           id="account_holder_name" name="account_holder_name" 
                           value="${docData.sub_fields?.account_holder_name?.value || ''}" 
                           placeholder="Auto-extracted">
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">Account Number</label>
                    <div class="d-flex">
                        <input type="text" class="form-control form-control-sm extracted-field" 
                               id="bank_account_no" name="bank_account_no" 
                               value="${docData.sub_fields?.bank_account_no?.value || ''}" 
                               placeholder="Auto-extracted">
                        <span class="input-group-text bg-light verification-icon" style="display: ${docData.sub_fields?.bank_account_no?.value ? 'flex' : 'none'};">
                            <i class="ri-checkbox-circle-fill text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="row g-2 mt-1">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">IFSC Code</label>
                    <div class="d-flex">
                        <input type="text" class="form-control form-control-sm extracted-field" 
                               id="bank_ifsc" name="bank_ifsc" 
                               value="${docData.sub_fields?.bank_ifsc?.value || ''}" 
                               placeholder="Auto-extracted">
                        <span class="input-group-text bg-light verification-icon" style="display: ${docData.sub_fields?.bank_ifsc?.value ? 'flex' : 'none'};">
                            <i class="ri-checkbox-circle-fill text-success"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="small fw-bold text-muted">Bank Name</label>
                    <input type="text" class="form-control form-control-sm extracted-field" 
                           id="bank_name" name="bank_name" 
                           value="${docData.sub_fields?.bank_name?.value || ''}" 
                           placeholder="Auto-extracted">
                </div>
            </div>
            
            ${docData.uploaded && docData.document ? `
            <div class="row mt-2">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between bg-light p-1 rounded">
                        <div class="small text-truncate" style="max-width: 250px;">
                            <i class="ri-file-line me-1"></i> ${docData.document.file_name}
                        </div>
                        <div class="btn-group btn-group-xs">
                            <button type="button" class="btn btn-outline-info btn-sm preview-doc-btn" 
                                    data-document-id="${docData.document.id}">
                                <i class="ri-eye-line"></i> Preview
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-doc-btn" 
                                    data-document-id="${docData.document.id}" 
                                    data-doc-type="bank_document">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;

            return html;
        }

        function renderSimpleDocumentSection(docType, docData) {
            const icon = docType === 'resume' ? 'ri-file-text-line' : 'ri-file-copy-line';
            const cardClass = docData.uploaded ? 'document-uploaded' : 'document-missing';
            const label = docData.name || (docType === 'resume' ? 'Resume' : 'Other Document');

            let html = `
        <div class="document-card ${cardClass}" data-doc-type="${docType}">
            <div class="row align-items-center mb-2">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <i class="${icon} me-2 ${docData.uploaded ? 'text-success' : 'text-muted'}"></i>
                        <h6 class="mb-0 flex-grow-1">${label}</h6>
                        ${docData.uploaded ? 
                            `<span class="badge bg-success bg-opacity-10 text-success">
                                <i class="ri-checkbox-circle-fill me-1"></i> Uploaded
                            </span>` : 
                            `<span class="badge bg-secondary bg-opacity-10 text-secondary">
                                <i class="ri-time-line me-1"></i> Optional
                            </span>`
                        }
                    </div>
                </div>
            </div>
            
            <div class="row g-2">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <input type="file" 
                               class="form-control form-control-sm document-file-input" 
                               id="${docType}_file" 
                               name="${docType}_file" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               data-doc-type="${docType}">
                        <button type="button" class="btn btn-primary btn-sm upload-doc-btn" 
                                data-doc-type="${docType}" style="display: none;">
                            <i class="ri-upload-2-line"></i> Upload
                        </button>
                    </div>
                </div>
            </div>
            
            ${docData.uploaded && docData.document ? `
            <div class="row mt-2">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between bg-light p-1 rounded">
                        <div class="small text-truncate" style="max-width: 300px;">
                            <i class="ri-file-line me-1"></i> ${docData.document.file_name}
                        </div>
                        <div class="btn-group btn-group-xs">
                            <button type="button" class="btn btn-outline-info btn-sm preview-doc-btn" 
                                    data-document-id="${docData.document.id}">
                                <i class="ri-eye-line"></i> Preview
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-doc-btn" 
                                    data-document-id="${docData.document.id}" 
                                    data-doc-type="${docType}">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;

            return html;
        }

        function getDocumentIcon(docType) {
            const icons = {
                'pan_card': 'id-card',
                'aadhaar_card': 'address-card',
                'bank_document': 'university',
                'resume': 'file-alt',
                'driving_licence': 'car',
                'other': 'file'
            };
            return icons[docType] || 'file';
        }

        function uploadDocument(form) {
            const formData = new FormData(form[0]);
            const requisitionId = form.find('input[name="requisition_id"]').val();

            $.ajax({
                url: ROUTES.documentsUpload,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showSuccess('Document uploaded successfully');
                        openDocumentModal(requisitionId, '');
                        loadCandidatesList();
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr, 'Upload');
                }
            });
        }

        function deleteDocument(documentId, docType) {
            if (!confirm('Are you sure you want to delete this document?')) return;

            const requisitionId = $('#documentModalContent input[name="requisition_id"]').val();

            $.ajax({
                url: ROUTES.documentsDelete.replace(':id', documentId),
                type: 'DELETE',
                data: {
                    _token: CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Document deleted successfully');
                        openDocumentModal(requisitionId, '');
                        loadCandidatesList();
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr) {
                    handleAjaxError(xhr, 'Delete');
                }
            });
        }

        function previewDocument(documentId) {
            const url = ROUTES.documentsDownload.replace(':id', documentId);

            $('#previewDocumentImage').hide();
            $('#previewDocumentPdf').hide();
            $('#noPreview').hide();

            // Try to determine file type from extension
            const ext = url.split('.').pop().toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                $('#previewDocumentImage').attr('src', url).show();
            } else if (ext === 'pdf') {
                $('#previewDocumentPdf').attr('src', url).show();
            } else {
                $('#noPreview').show();
                // Open in new tab for other file types
                window.open(url, '_blank');
            }

            $('#previewDocumentModal').modal('show');
        }

        function handleAjaxError(xhr, context) {
            let msg = `${context} failed`;

            if (xhr.status === 419) {
                msg = 'Session expired. Refreshing...';
                setTimeout(() => location.reload(), 1500);
            } else if (xhr.status === 413) {
                msg = 'File too large (Max 2MB)';
            } else if (xhr.status === 422) {
                msg = 'Validation error. Check file format';
            } else if (xhr.responseJSON?.message) {
                msg = xhr.responseJSON.message;
            }

            showError(msg);
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                toast: true,
                position: 'top-end'
            });
        }

        function scrollToElement(selector) {
            $('html, body').animate({
                scrollTop: $(selector).offset().top - 20
            }, 300);
        }
    });
</script>
@endsection