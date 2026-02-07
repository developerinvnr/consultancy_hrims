@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Detailed Remuneration Report</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshPage()">
                        <i class="ri-refresh-line"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Filter Card -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">

            <!-- Month-Year -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label form-label-sm mb-1">Month</label>
                <input type="month" id="monthYear"
                       class="form-control form-control-sm"
                       value="{{ date('Y-m') }}" required>
            </div>

            <!-- Requisition Type -->
            <div class="col-md-3 col-lg-2">
                <label class="form-label form-label-sm mb-1">Type</label>
                <select id="requisitionType" class="form-select form-select-sm">
                    <option value="All">All</option>
                    <option value="Contractual">Contractual</option>
                    <option value="TFA">TFA</option>
                    <option value="CB">CB</option>
                </select>
            </div>

            <!-- Preview Button -->
            <div class="col-md-3 col-lg-2">
                <button class="btn btn-primary btn-sm"
                        onclick="loadReportPreview()">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <!-- Export Button -->
            <div class="col-md-3 col-lg-2 ms-auto">
                <button class="btn btn-success btn-sm"
                        onclick="exportReport()"
                        id="exportBtn" disabled>
                    <i class="ri-file-excel-2-line"></i> Excel
                </button>
            </div>

        </div>
    </div>
</div>


    <!-- Report Preview -->
    <div class="card shadow-sm" id="reportPreview" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Report Preview</h5>
                <div class="text-muted small" id="reportSummary"></div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="reportTable">
                    <thead class="table-light">
                        <tr>
                            <th>S no.</th>
                            <th>Party Code</th>
                            <th>Name of Party</th>
                            <th>Function</th>
                            <th>Vertical</th>
                            <th>Department</th>
                            <th>Sub-Department</th>
                            <th>State</th>
                            <th>BU</th>
                            <th>Zone</th>
                            <th>Region</th>
                            <th>Territory</th>
                            <th>Job-Location</th>
                            <th>Date of joining</th>
                            <th>Date of Separation</th>
                            <th>State (for address)</th>
                            <th>HQ</th>
                            <th>Paid days</th>
                            <th>Remuneration</th>
                            <th>Overtime</th>
                            <th>Arrear</th>
                            <th>Total Payable</th>
                        </tr>
                    </thead>
                    <tbody id="reportData">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 text-center" id="noDataMessage" style="display: none;">
                <div class="alert alert-info">
                    <i class="ri-information-line"></i>
                    No data found for the selected filters. Please process salaries for this period first.
                </div>
                <a href="{{ route('salary.index') }}" class="btn btn-sm btn-primary">
                    <i class="ri-calculator-line"></i> Go to Salary Processing
                </a>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="text-center py-5" id="loadingSpinner" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading report data...</p>
    </div>

</div>
@endsection

@section('script_section')
<script>
    let currentMonth = null;
    let currentYear = null;
    let currentRequisitionType = 'All';

    function refreshPage() {
        location.reload();
    }

    function loadReportPreview() {
        const monthVal = $('#monthYear').val();
        if (!monthVal) {
            toastr.error('Please select month-year');
            return;
        }

        [currentYear, currentMonth] = monthVal.split('-').map(Number);
        currentRequisitionType = $('#requisitionType').val();

        // Show loading
        $('#reportPreview').hide();
        $('#loadingSpinner').show();
        $('#exportBtn').prop('disabled', true);

        // Make AJAX call to get real data
        $.ajax({
            url: "{{ route('salary.detailed.report.data') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                month: currentMonth,
                year: currentYear,
                requisition_type: currentRequisitionType
            },
            success: function(response) {
                if (response.success) {
                    renderReportData(response.data);
                    $('#loadingSpinner').hide();
                    $('#reportPreview').show();
                    $('#exportBtn').prop('disabled', false);
                    
                    const monthName = new Date(currentYear, currentMonth - 1).toLocaleString('default', { month: 'long' });
                    $('#reportSummary').text(`Showing ${response.count} records for ${monthName} ${currentYear} (${currentRequisitionType} employees)`);
                    
                    if (response.count === 0) {
                        $('#reportTable').hide();
                        $('#noDataMessage').show();
                        $('#exportBtn').prop('disabled', true);
                    } else {
                        $('#reportTable').show();
                        $('#noDataMessage').hide();
                    }
                } else {
                    toastr.error('Failed to load report data');
                    $('#loadingSpinner').hide();
                }
            },
            error: function(xhr) {
                $('#loadingSpinner').hide();
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Failed to load report data');
                }
            }
        });
    }

    function renderReportData(data) {
        const tbody = $('#reportData');
        tbody.empty();

        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="23" class="text-center py-4 text-muted">
                        No data found for selected filters
                    </td>
                </tr>
            `);
            return;
        }

        data.forEach((item, index) => {
            // Format dates
            const joinDate = item.date_of_joining ? 
                new Date(item.date_of_joining).toLocaleDateString('en-IN') : '-';
            const separationDate = item.date_of_separation ? 
                new Date(item.date_of_separation).toLocaleDateString('en-IN') : '-';
            
            // Add status badge for processed/unprocessed
            const statusBadge = item.processed ? 
                '<span class="badge bg-success ms-2">Processed</span>' : 
                '<span class="badge bg-warning ms-2">Not Processed</span>';

            const row = `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${item.code}${statusBadge}</td>
                    <td>${item.name}</td>
                    <td>${item.function || '-'}</td>
                    <td>${item.vertical || '-'}</td>
                    <td>${item.department || '-'}</td>
                    <td>${item.sub_department || '-'}</td>
                    <td>${item.work_state  || '-'}</td>
                    <td>${item.bu || '-'}</td>
                    <td>${item.zone || '-'}</td>
                    <td>${item.region || '-'}</td>
                    <td>${item.territory || '-'}</td>
                    <td>${item.job_location || '-'}</td>
                    <td>${joinDate}</td>
                    <td>${separationDate}</td>
                    <td>${item.residence_state || '-'}</td>
                    <td>${item.hq || '-'}</td>
                    <td class="text-center">${item.paid_days ?? 0}</td>
                    <td class="text-end">₹ ${item.remuneration?.toLocaleString('en-IN') || '0'}</td>
                    <td class="text-end">₹ ${item.overtime?.toLocaleString('en-IN') || '0'}</td>
                    <td class="text-end ${item.arrear > 0 ? 'text-success fw-bold' : ''}">
                        ${item.arrear > 0 ? '+' : ''}₹ ${item.arrear?.toLocaleString('en-IN') || '0'}
                    </td>
                    <td class="text-end fw-bold">₹ ${item.total_payable?.toLocaleString('en-IN') || '0'}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function exportReport() {
        if (!currentMonth || !currentYear) {
            toastr.error('Please generate report preview first');
            return;
        }

        Swal.fire({
            title: 'Export Detailed Report',
            html: `Export detailed Remuneration report for <b>${currentMonth}/${currentYear}</b>${currentRequisitionType !== 'All' ? ` (${currentRequisitionType})` : ''}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Download Excel',
            confirmButtonColor: '#198754',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve, reject) => {
                    let url = `/hr/salary/export-detailed-report?month=${currentMonth}&year=${currentYear}`;
                    if (currentRequisitionType && currentRequisitionType !== 'All') {
                        url += `&requisition_type=${currentRequisitionType}`;
                    }
                    url += `&_token={{ csrf_token() }}`;

                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.download = `Detailed_Salary_Report_${currentMonth}_${currentYear}${currentRequisitionType !== 'All' ? '_' + currentRequisitionType : ''}.xlsx`;

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        resolve(true);
                    }, 1000);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                toastr.success('Report export started');
            }
        });
    }

    // Auto-load current month data on page load
    $(document).ready(function() {
        loadReportPreview();
    });
</script>
@endsection

@push('styles')
<style>
    .table th {
        white-space: nowrap;
        font-size: 0.85rem;
        background-color: #f8f9fa;
    }
    
    .table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    
    #reportTable {
        font-size: 0.8rem;
    }
    
    #reportTable th {
        background-color: #2c3e50;
        color: white;
        border-color: #2c3e50;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
</style>
@endpush