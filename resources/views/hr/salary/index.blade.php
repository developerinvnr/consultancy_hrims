@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-1">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="mb-0"></h4>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="refreshList()">
                        <i class="ri-refresh-line"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Actions Card -->
    <div class="card mb-2 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-sm-6">
                    <label class="form-label form-label-sm">Select Month-Year</label>
                    {{--<input type="month" id="monthYear" class="form-control form-control-sm" required>--}}
                    <input type="text" id="monthYear" class="form-control form-control-sm" placeholder="Select Month-Year" required>


                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label form-label-sm">Requisition Type</label>
                    <select id="requisitionType" class="form-select form-select-sm" required>
                        <option value="" selected disabled>Select Type</option>
                        <option value="Contractual">Contractual</option>
                        <option value="TFA">TFA</option>
                        <option value="CB">CB</option>
                    </select>

                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label form-label-sm">Department</label>
                    <select id="departmentFilter" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 ms-auto d-flex gap-2 flex-wrap justify-content-end">
                    <button class="btn btn-sm btn-primary process-btn" onclick="processSelected()">
                        <i class="ri-play-circle-line"></i> Process Selected
                    </button>
                    <button class="btn btn-sm btn-success process-btn" onclick="processAll()">
                        <i class="ri-check-double-line"></i> Process All Filtered
                    </button>
                    {{--<button class="btn btn-sm btn-info" onclick="exportExcel()">
                        <i class="ri-file-excel-2-line"></i> Export Excel
                    </button>--}}
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-2" id="salaryTabs">
        <li class="nav-item">
            <button class="nav-link active" data-tab="pending" onclick="switchTab('pending')">
                Pending Processing
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-tab="processed" onclick="switchTab('processed')">
                Processed
            </button>
        </li>
    </ul>

    <!-- Salary Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-bordered mb-0" id="salaryTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>PAN Status</th>
                            <th>Requisition Type</th>
                            <th>Paid Days</th>
                            <th>Monthly Base</th>
                            <th>Extra</th>
                            <th>Deduction</th>
                            <th>Additional Contract Charges</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">
                                Select month-year to load active candidates
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            Showing active candidates (final_status = 'A'). Processed = calculated & saved.
        </div>
    </div>

</div>

<!-- Arrear Modal -->
<div class="modal fade" id="arrearModal" tabindex="-1" aria-labelledby="arrearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="arrearModalLabel">Additional Contract Charges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="arrearForm">
                    <input type="hidden" id="salary_id" name="salary_id">
                    <input type="hidden" id="candidate_id" name="candidate_id">
                    <input type="hidden" id="month" name="month">
                    <input type="hidden" id="year" name="year">

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Party Code</label>
                            <input type="text" id="employee_code" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Party Name</label>
                            <input type="text" id="employee_name" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Monthly Base Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" id="monthly_salary" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Daily Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" id="daily_rate" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requisition Type</label>
                            <input type="text" id="requisition_type" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Number of Days</label>
                            <input type="number" id="arrear_days" name="arrear_days" class="form-control"
                                min="0" max="31" step="0.5" placeholder="Enter number of days">
                            <div class="form-text">Enter number of days for arrear calculation</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Calculated Additional Contract Charges Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" id="calculated_arrear" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Remarks (Optional)</label>
                            <textarea id="arrear_remarks" name="arrear_remarks" class="form-control"
                                rows="2" placeholder="Enter any remarks for arrear"></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="ri-information-line"></i>
                        <strong>Calculation:</strong> Additional Contract Charges Amount = Per Day Salary × Number of Days
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveArrear()">Save Additional Contract Charges</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    #salaryTable thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 10;
    }

    .badge-pending {
        background-color: #ffc107;
        color: black;
    }

    .badge-processed {
        background-color: #198754;
    }

    .badge-paid {
        background-color: #0d6efd;
    }

    .btn-sm {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }

    .arrear-link {
        cursor: pointer;
        color: #0d6efd;
        text-decoration: underline;
        font-weight: 500;
    }

    .arrear-link:hover {
        color: #0a58ca;
        text-decoration: underline;
    }
</style>
@endpush

@push('scripts')

<script>
    function switchTab(tab) {

        currentTab = tab;

        if (tab === 'processed') {
            $('#selectAll').hide();
            $('.process-btn').hide();
        } else {
            $('#selectAll').show();
            $('.process-btn').show();
        }

        $('#salaryTabs .nav-link').removeClass('active');
        $('#salaryTabs .nav-link[data-tab="' + tab + '"]').addClass('active');

        loadSalaryList();
    }

    let currentTab = 'pending';
    let currentMonth = null;
    let currentYear = null;
    let currentRequisitionType = 'All';
    let dailyRate = 0;

    flatpickr("#monthYear", {
        plugins: [
            new monthSelectPlugin({
                shorthand: true, // Jan, Feb, Mar
                dateFormat: "Y-m", // 2026-02
                altFormat: "F Y" // February 2026
            })
        ]
    });


    // Load list when month or requisition type changes
    $('#monthYear, #requisitionType, #departmentFilter').on('change', function() {
        const monthVal = $('#monthYear').val();
        if (!monthVal) return;

        [currentYear, currentMonth] = monthVal.split('-').map(Number);
        currentRequisitionType = $('#requisitionType').val();
        loadSalaryList();
    });

    function loadSalaryList() {
        if (!currentMonth || !currentYear) return;
        let department = $('#departmentFilter').val();
        if (!currentRequisitionType) {
            toastr.warning('Please select requisition type');
            return;
        }

        let data = {
            _token: '{{ csrf_token() }}',
            month: currentMonth,
            year: currentYear,
            requisition_type: currentRequisitionType,
            type: currentTab
        };

        if (department) {
            data.department_id = department;
        }

        $.post("{{ route('salary.list') }}", data, function(data) {
            renderTable(data);
        });
    }


    function renderTable(records) {

        const isProcessedTab = currentTab === 'processed';


        if (!records || records.length === 0) {
            $('#salaryTable tbody').html(`
            <tr>
                <td colspan="12" class="text-center py-4 text-muted">
                    No records found
                </td>
            </tr>
        `);
            return;
        }

        let html = '';

        records.forEach(r => {

            const panBadge = r.pan_status_2 === 'Operative' ?
                `<span class="badge bg-success">Operative</span>` :
                `<span class="badge bg-danger">Inoperative</span>`;

            let statusClass = 'warning';
            let statusText = 'Pending';

            if (!r.can_process) {
                statusClass = 'danger';
                statusText = 'PAN Inoperative';
            } else if (Number(r.paid_days || 0) === 0) {
                statusClass = 'warning';
                statusText = 'Attendance Missing';
            } else if (r.processed) {
                statusClass = 'success';
                statusText = 'Processed';
            }

            let payoutStatus = 'Pending Payout';

            if (r.payment_instruction === 'hold') {
                payoutStatus = 'Hold';
            }

            if (r.payment_instruction === 'release') {
                payoutStatus = 'Released';
            }

            const requisitionType = r.candidate?.requisition_type || '-';

            const monthlySalary = Number(r.monthly_salary || 0);
            const perDaySalary = Number(r.per_day_salary || (monthlySalary / 30));

            const arrearAmount = Math.round(Number(r.arrear_amount || 0));
            const arrearDays = Number(r.arrear_days || 0);

            const baseNetPay = Number(r.net_pay || 0);
            const netPay = baseNetPay;
            const totalPayable = baseNetPay + arrearAmount;
            let arrearText = `<span class="text-muted">₹ 0</span>`;
            let arrearLinkText = 'Add Additional Contract Charges';

            if (arrearAmount > 0) {
                arrearText = `<span class="text-success fw-bold">+ ₹ ${arrearAmount.toLocaleString('en-IN')}</span>`;
                arrearLinkText = 'Edit Additional Contract Charges';
            }

            const arrearAction = `
            <a href="javascript:void(0)" 
            onclick="openArrearModal(${r.id || 'null'}, ${r.candidate_id}, ${monthlySalary}, ${perDaySalary}, '${r.candidate?.candidate_code || ''}', '${r.candidate?.candidate_name || ''}', '${requisitionType}', ${arrearAmount}, ${arrearDays})" 
            class="arrear-link">
            ${arrearLinkText}
            </a>
            `;

            if (!isProcessedTab) {

                html += `
                            <tr data-candidate-id="${r.candidate_id}">

                            <td>
                            <input type="checkbox" class="row-check">
                            </td>

                            <td>${r.candidate?.candidate_code ?? '-'}</td>
                            <td>${r.candidate?.candidate_name ?? '-'}</td>
                            <td>${panBadge}</td>
                            <td><span class="badge bg-secondary">${requisitionType}</span></td>

                            <td>${Number(r.paid_days || 0)}</td>

                            <td>₹ ${monthlySalary.toLocaleString('en-IN')}</td>

                            <td class="text-success">
                            + ₹ ${Number(r.extra_amount || 0).toLocaleString('en-IN')}
                            </td>

                            <td class="text-danger">
                            - ₹ ${Number(r.deduction_amount || 0).toLocaleString('en-IN')}
                            </td>

                            <td>
                            ${arrearText}
                            <span class="ms-1">${arrearAction}</span>
                            </td>

                            <td><strong>₹ ${totalPayable.toLocaleString('en-IN')}</strong></td>

                            <td>
                            <span class="badge bg-${statusClass}">
                            ${statusText}
                            </span>
                            </td>

                            </tr>`;
            } else {

                html += `
                    <tr data-id="${r.id}" data-candidate-id="${r.candidate_id}">

                    <td></td>

                    <td>${r.candidate?.candidate_code ?? '-'}</td>
                    <td>${r.candidate?.candidate_name ?? '-'}</td>
                    <td>${panBadge}</td>
                    <td><span class="badge bg-secondary">${requisitionType}</span></td>

                    <td>${Number(r.paid_days || 0)}</td>

                    <td>₹ ${monthlySalary.toLocaleString('en-IN')}</td>

                    <td class="text-success">
                    + ₹ ${Number(r.extra_amount || 0).toLocaleString('en-IN')}
                    </td>

                    <td class="text-danger">
                    - ₹ ${Number(r.deduction_amount || 0).toLocaleString('en-IN')}
                    </td>

                    <td>
                    ${arrearText}
                    <span class="ms-1">${arrearAction}</span>
                    </td>

                    <td><strong>₹ ${totalPayable.toLocaleString('en-IN')}</strong></td>

                    <td>
                    <span class="badge bg-success">Processed</span>
                    <span class="badge bg-info ms-1">${payoutStatus}</span>
                    </td>

                    </tr>`;

            }
        });

        $('#salaryTable tbody').html(html);
    }

    // Function to open arrear modal
    function openArrearModal(salaryId, candidateId, monthlySalary, perDaySalary, employeeCode, employeeName, requisitionType, currentArrear = 0, currentDays = 0) {
        // Set daily rate from per day salary
        dailyRate = parseFloat(perDaySalary) || 0;

        // Populate form
        $('#salary_id').val(salaryId);
        $('#candidate_id').val(candidateId);
        $('#month').val(currentMonth);
        $('#year').val(currentYear);
        $('#employee_code').val(employeeCode);
        $('#employee_name').val(employeeName);
        $('#monthly_salary').val(monthlySalary.toLocaleString('en-IN'));
        $('#daily_rate').val(dailyRate.toFixed(2));
        $('#requisition_type').val(requisitionType);

        // If there's existing arrear, populate fields
        if (currentArrear > 0) {
            $('#arrear_days').val(currentDays);
            $('#calculated_arrear').val(currentArrear.toLocaleString('en-IN'));
        } else {
            $('#arrear_days').val('');
            $('#calculated_arrear').val('0');
        }

        // Clear remarks
        $('#arrear_remarks').val('');

        // Update modal title
        const modalTitle = 'Additional Contract Charges';
        $('#arrearModalLabel').text(modalTitle);

        // Add info message for pending salaries
        $('.alert-warning').remove(); // Remove any existing warning

        if (!salaryId) {
            const infoHtml = `
                <div class="alert alert-warning mt-2">
                    <i class="ri-information-line"></i>
                    <strong>Note:</strong> Additional Contract Charges amount will be calculated locally and included in net pay preview. 
                    It will be saved to database only when salary is processed.
                </div>
            `;
            $('.alert-info').after(infoHtml);
        }

        // Show modal
        $('#arrearModal').modal('show');
    }

    // Calculate arrear on days change
    $('#arrear_days').on('input', function() {
        const days = parseFloat($(this).val()) || 0;
        const arrearAmount = Math.round(days * dailyRate);
        $('#calculated_arrear').val(parseFloat(arrearAmount).toLocaleString('en-IN'));
    });

    function saveArrear() {
        const salaryId = $('#salary_id').val();
        const candidateId = $('#candidate_id').val();
        const month = $('#month').val();
        const year = $('#year').val();
        const arrearDays = parseFloat($('#arrear_days').val()) || 0;
        const arrearAmount = Math.round(arrearDays * dailyRate);
        const remarks = $('#arrear_remarks').val();

        if (arrearDays < 0) {
            toastr.error('Number of days cannot be negative');
            return;
        }

        if (arrearDays > 31) {
            toastr.error('Number of days cannot exceed 31');
            return;
        }

        // For PROCESSED salaries: Save to database
        if (salaryId) {
            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Saving arrear to database',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to save to database
            $.ajax({
                url: "{{ route('salary.update.arrear') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    salary_id: salaryId,
                    candidate_id: candidateId,
                    month: month,
                    year: year,
                    arrear_days: arrearDays,
                    arrear_amount: arrearAmount,
                    arrear_remarks: remarks
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        toastr.success(response.message);
                        $('#arrearModal').modal('hide');
                        loadSalaryList(); // Refresh the table
                    } else {
                        toastr.error(response.message || 'Failed to save arrear');
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON);
                    toastr.error(xhr.responseJSON?.message || "Failed to save arrear");
                }
            });
        } else {

            Swal.fire({
                title: 'Saving...',
                text: 'Saving arrear',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route('salary.save.arrear') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    candidate_id: candidateId,
                    month: month,
                    year: year,
                    arrear_days: arrearDays,
                    arrear_amount: arrearAmount,
                    arrear_remarks: remarks
                },
                success: function(response) {

                    Swal.close();

                    if (response.success) {

                        toastr.success('Additional Contract Charges saved');

                        $('#arrearModal').modal('hide');

                        // update UI immediately
                        loadSalaryList();

                    } else {
                        toastr.error(response.message || 'Failed to save arrear');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    toastr.error(xhr.responseJSON?.message || "Failed to save arrear");
                }
            });

        }
    }

    // Process All with warning - Modified to include local arrear data
    function processAll() {

        if (!currentMonth || !currentYear) {
            toastr.error("Select month-year first");
            return;
        }

        Swal.fire({
            title: 'Process All Remuneration?',
            text: `Process salary for ${currentMonth}/${currentYear}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Process All',
            confirmButtonColor: '#198754'
        }).then((result) => {

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.post("{{ route('salary.process') }}", {
                _token: '{{ csrf_token() }}',
                month: currentMonth,
                year: currentYear,
                requisition_type: currentRequisitionType
            }, function(response) {

                Swal.close();

                if (response.success) {
                    toastr.success(response.message);
                    loadSalaryList();
                } else {
                    toastr.warning(response.message);
                }

            }).fail(function() {
                Swal.close();
                toastr.error("Processing failed");
            });

        });

    }

    // Process Selected (checkboxes) - Modified to include local arrear data
    function processSelected() {

        const selected = $('.row-check:checked').closest('tr');

        const candidateIds = selected.map(function() {
            return $(this).data('candidate-id');
        }).get();

        if (candidateIds.length === 0) {
            toastr.warning("No Party selected");
            return;
        }

        Swal.fire({
            title: 'Process Selected?',
            text: `Process ${candidateIds.length} employee(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Process',
            confirmButtonColor: '#198754'
        }).then((result) => {

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route('salary.process') }}",
                method: 'POST',
                data: JSON.stringify({
                    _token: '{{ csrf_token() }}',
                    month: currentMonth,
                    year: currentYear,
                    candidate_ids: candidateIds,
                    requisition_type: currentRequisitionType
                }),
                contentType: 'application/json',

                success: function(response) {

                    Swal.close();

                    if (response.success) {
                        toastr.success(response.message);
                        loadSalaryList();
                    } else {
                        toastr.error(response.message);
                    }

                },
                error: function() {
                    Swal.close();
                    toastr.error("Processing failed");
                }
            });

        });

    }

    // Export Excel with current filters
    function exportExcel() {
        if (!currentMonth || !currentYear) {
            toastr.error("Please select month-year first");
            return;
        }

        Swal.fire({
            title: 'Export to Excel',
            html: `Export salary data for <b>${currentMonth}/${currentYear}</b>${currentRequisitionType !== 'All' ? ` (${currentRequisitionType})` : ''}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Download Excel',
            confirmButtonColor: '#198754',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return new Promise((resolve, reject) => {
                    let department = $('#departmentFilter').val();

                    let url = `/hr/salary/export?month=${currentMonth}&year=${currentYear}`;

                    if (currentRequisitionType && currentRequisitionType !== 'All') {
                        url += `&requisition_type=${currentRequisitionType}`;
                    }

                    if (department) {
                        url += `&department_id=${department}`;
                    }
                    url += `&_token={{ csrf_token() }}`;

                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.download = `Salary_Report_${currentMonth}_${currentYear}${currentRequisitionType !== 'All' ? '_' + currentRequisitionType : ''}.xlsx`;

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
                toastr.success("Excel file download started");
            }
        });
    }

    function refreshList() {
        loadSalaryList();
    }

    // Auto-load if monthYear has value on page load
    if ($('#monthYear').val()) {
        $('#monthYear').trigger('change');
    }

    // Select / Unselect all rows
    $(document).on('change', '#selectAll', function() {
        const isChecked = $(this).is(':checked');
        $('.row-check').prop('checked', isChecked);
    });

    // Auto update "Select All" when individual checkbox changes
    $(document).on('change', '.row-check', function() {
        const total = $('.row-check').length;
        const checked = $('.row-check:checked').length;

        $('#selectAll').prop('checked', total > 0 && total === checked);
    });
</script>
@endpush