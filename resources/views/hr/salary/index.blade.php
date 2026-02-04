@extends('layouts.guest')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-3">
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
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-sm-6">
                    <label class="form-label form-label-sm">Select Month-Year</label>
                    <input type="month" id="monthYear" class="form-control form-control-sm" required>
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

                <div class="col-md-5 ms-auto d-flex gap-2 flex-wrap justify-content-end">
                    <button class="btn btn-sm btn-primary" onclick="processSelected()">
                        <i class="ri-play-circle-line"></i> Process Selected
                    </button>
                    <button class="btn btn-sm btn-success" onclick="processAll()">
                        <i class="ri-check-double-line"></i> Process All Filtered
                    </button>
                    <button class="btn btn-sm btn-info" onclick="exportExcel()">
                        <i class="ri-file-excel-2-line"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0" id="salaryTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Requisition Type</th>
                            <th>Monthly Base</th>
                            <th>Extra</th>
                            <th>Deduction</th>
                            <th>Arrear</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
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
                <h5 class="modal-title" id="arrearModalLabel">Manage Arrear</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="arrearForm">
                    <input type="hidden" id="salary_id" name="salary_id">
                    <input type="hidden" id="candidate_id" name="candidate_id">
                    <input type="hidden" id="month" name="month">
                    <input type="hidden" id="year" name="year">

                    <div class="row mb-3">
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
                            <label class="form-label">Calculated Arrear Amount</label>
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
                        <strong>Calculation:</strong> Arrear Amount = Per Day Salary × Number of Days
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveArrear()">Save Arrear</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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

@section('script_section')
<script>
    let currentMonth = null;
    let currentYear = null;
    let currentRequisitionType = 'All';
    let dailyRate = 0;
    let localArrearData = {}; // Store arrear data locally before processing

    // Load list when month or requisition type changes
    $('#monthYear, #requisitionType').on('change', function() {
        const monthVal = $('#monthYear').val();
        if (!monthVal) return;

        [currentYear, currentMonth] = monthVal.split('-').map(Number);
        currentRequisitionType = $('#requisitionType').val();
        loadSalaryList();
    });

    function loadSalaryList() {
        if (!currentMonth || !currentYear) return;

        if (!currentRequisitionType) {
            toastr.warning('Please select requisition type');
            return;
        }

        $.post("{{ route('salary.list') }}", {
            _token: '{{ csrf_token() }}',
            month: currentMonth,
            year: currentYear,
            requisition_type: currentRequisitionType
        }, function(data) {
            renderTable(data);
        }).fail(() => {
            toastr.error("Failed to load salary data");
        });
    }


    function renderTable(records) {
        if (!records || records.length === 0) {
            $('#salaryTable tbody').html(`
                <tr><td colspan="11" class="text-center py-4 text-muted">
                    No active candidates found for ${currentRequisitionType !== 'All' ? currentRequisitionType + ' ' : ''}this period
                </td></tr>
            `);
            return;
        }

        let html = '';
        records.forEach(r => {
            const statusClass = r.processed ? 'success' : 'warning';
            const statusText = r.processed ? 'Processed' : 'Pending';
            const requisitionType = r.candidate?.requisition_type || r.requisition_type || '-';
            const monthlySalary = Number(r.monthly_salary || 0);
            const perDaySalary = Number(r.per_day_salary || (monthlySalary / 30));

            // Check for locally stored arrear data (not yet saved to DB)
            const candidateKey = `${r.candidate_id}_${currentMonth}_${currentYear}`;
            let localArrear = localArrearData[candidateKey] || {
                days: 0,
                amount: 0
            };

            // Use saved arrear if processed, otherwise use local arrear
            const arrearAmount = r.processed ?
                Number(r.arrear_amount || 0) :
                Number(localArrear.amount || 0);
            const arrearDays = r.processed ?
                Number(r.arrear_days || 0) :
                Number(localArrear.days || 0);

            // Calculate net pay
            const baseNetPay = Number(r.net_pay || 0);
            const netPay = baseNetPay + arrearAmount;

            // Calculate arrear text
            let arrearText = '₹ 0';
            let arrearLinkText = 'Add Arrear';
            let arrearLinkClass = 'arrear-link';

            if (arrearAmount > 0) {
                arrearText = `<span class="text-success fw-bold">+ ₹ ${arrearAmount.toLocaleString('en-IN')}</span>`;
                arrearLinkText = 'Edit Arrear';
            }

            // Show arrear link for ALL rows (processed or pending)
            const arrearAction = r.processed ?
                // For processed: save to DB
                `<a href="javascript:void(0)" 
                   onclick="openArrearModal(${r.id || 'null'}, ${r.candidate_id}, ${monthlySalary}, ${perDaySalary}, '${r.candidate?.candidate_code || ''}', '${r.candidate?.candidate_name || ''}', '${requisitionType}', ${arrearAmount}, ${arrearDays})" 
                   class="${arrearLinkClass}">
                    ${arrearLinkText}
                </a>` :
                // For pending: local calculation only
                `<a href="javascript:void(0)" 
                   onclick="openArrearModal(null, ${r.candidate_id}, ${monthlySalary}, ${perDaySalary}, '${r.candidate?.candidate_code || ''}', '${r.candidate?.candidate_name || ''}', '${requisitionType}', ${arrearAmount}, ${arrearDays})" 
                   class="${arrearLinkClass}">
                    ${arrearLinkText}
                </a>`;

            html += `
            <tr data-id="${r.id || ''}" data-candidate-id="${r.candidate_id}">
                <td><input type="checkbox" class="row-check"></td>
                <td>${r.candidate?.candidate_code ?? '-'}</td>
                <td>${r.candidate?.candidate_name ?? '-'}</td>
                <td><span class="badge bg-secondary">${requisitionType}</span></td>
                <td>₹ ${monthlySalary.toLocaleString('en-IN')}</td>
                <td class="text-success">+ ₹ ${Number(r.extra_amount || 0).toLocaleString('en-IN')}</td>
                <td class="text-danger">- ₹ ${Number(r.deduction_amount || 0).toLocaleString('en-IN')}</td>
                <td>
                    ${arrearText}<br>
                    <small>${arrearAction}</small>
                </td>
                <td><strong>₹ ${netPay.toLocaleString('en-IN')}</strong></td>
                <td>
                    <span class="badge bg-${statusClass}">${statusText}</span>
                </td>
                <td>
                    ${r.processed ? `
                        <a href="/hr/salary/payslip/${r.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="ri-download-line"></i> Payslip
                        </a>
                    ` : `
                        <span class="text-muted small">Not processed</span>
                    `}
                </td>
            </tr>`;
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
        const modalTitle = salaryId ? 'Manage Arrear' : 'Add Arrear (Local Calculation)';
        $('#arrearModalLabel').text(modalTitle);

        // Add info message for pending salaries
        $('.alert-warning').remove(); // Remove any existing warning

        if (!salaryId) {
            const infoHtml = `
                <div class="alert alert-warning mt-2">
                    <i class="ri-information-line"></i>
                    <strong>Note:</strong> Arrear amount will be calculated locally and included in net pay preview. 
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
        const arrearAmount = (days * dailyRate).toFixed(2);
        $('#calculated_arrear').val(parseFloat(arrearAmount).toLocaleString('en-IN'));
    });

    // Save arrear LOCALLY (not to database)
    function saveArrear() {
        const salaryId = $('#salary_id').val();
        const candidateId = $('#candidate_id').val();
        const month = $('#month').val();
        const year = $('#year').val();
        const arrearDays = parseFloat($('#arrear_days').val()) || 0;
        const arrearAmount = (arrearDays * dailyRate).toFixed(2);
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
                    Swal.close();
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Failed to save arrear');
                    }
                }
            });
        }
        // For PENDING salaries: Store locally
        else {
            // Store arrear data locally
            const candidateKey = `${candidateId}_${month}_${year}`;
            localArrearData[candidateKey] = {
                days: arrearDays,
                amount: parseFloat(arrearAmount),
                remarks: remarks,
                monthlySalary: parseFloat($('#monthly_salary').val().replace(/,/g, '')),
                perDaySalary: dailyRate
            };

            // Close modal and update table
            $('#arrearModal').modal('hide');
            toastr.success('Arrear calculated locally. It will be included when salary is processed.');

            // Re-render the table to show updated arrear
            loadSalaryList(); // This will refresh from server but we need to preserve local data
            // Instead, we can directly update the row
            updateRowWithLocalArrear(candidateId, arrearDays, arrearAmount);
        }
    }

    // Function to update specific row with local arrear data
    function updateRowWithLocalArrear(candidateId, days, amount) {
        const row = $(`tr[data-candidate-id="${candidateId}"]`);
        if (row.length) {
            const arrearCell = row.find('td:nth-child(8)');
            const netPayCell = row.find('td:nth-child(9)');

            // Update arrear display
            let arrearText = '₹ 0';
            if (amount > 0) {
                arrearText = `<span class="text-success fw-bold">+ ₹ ${parseFloat(amount).toLocaleString('en-IN')}</span>`;
            }

            // Update arrear cell
            arrearCell.html(`
                ${arrearText}<br>
                <small>
                    <a href="javascript:void(0)" 
                       onclick="openArrearModal(null, ${candidateId}, ${localArrearData[`${candidateId}_${currentMonth}_${currentYear}`]?.monthlySalary || 0}, ${localArrearData[`${candidateId}_${currentMonth}_${currentYear}`]?.perDaySalary || 0}, '${row.find('td:nth-child(2)').text()}', '${row.find('td:nth-child(3)').text()}', '${row.find('td:nth-child(4) .badge').text()}', ${amount}, ${days})" 
                       class="arrear-link">
                        Edit Arrear
                    </a>
                </small>
            `);

            // Update net pay
            const monthlySalary = parseFloat(row.find('td:nth-child(5)').text().replace(/[^0-9.-]+/g, ''));
            const extra = parseFloat(row.find('td:nth-child(6)').text().match(/\d+/g)?.join('') || 0);
            const deduction = parseFloat(row.find('td:nth-child(7)').text().match(/\d+/g)?.join('') || 0);
            const baseNetPay = monthlySalary + extra - deduction;
            const netPay = baseNetPay + parseFloat(amount);

            netPayCell.html(`<strong>₹ ${netPay.toLocaleString('en-IN')}</strong>`);
        }
    }

    // Process All with warning - Modified to include local arrear data
    function processAll() {
        if (!currentMonth || !currentYear) return toastr.error("Select month-year first");

        const filterText = currentRequisitionType !== 'All' ? ` for ${currentRequisitionType}` : '';

        // Check if any records exist for this month
        $.post("{{ route('salary.checkExists') }}", {
            _token: '{{ csrf_token() }}',
            month: currentMonth,
            year: currentYear,
            requisition_type: currentRequisitionType
        }, function(response) {
            let exists = response.exists || false;
            let count = response.count || 0;

            // Check if we have local arrear data that needs to be saved
            let localArrearCount = 0;
            Object.keys(localArrearData).forEach(key => {
                if (key.includes(`_${currentMonth}_${currentYear}`)) {
                    localArrearCount++;
                }
            });

            let confirmMessage = exists ?
                `<div class="alert alert-danger">
                    <i class="ri-alert-line"></i> 
                    <strong>${count} salary record(s) already exist${filterText} for ${currentMonth}/${currentYear}!</strong><br>
                    Processing will overwrite all existing data.
                </div>` :
                `Process salary${filterText} for ${currentMonth}/${currentYear}?`;

            // Add local arrear info if any
            if (localArrearCount > 0) {
                confirmMessage += `
                    <div class="alert alert-info mt-2">
                        <i class="ri-information-line"></i>
                        <strong>Note:</strong> ${localArrearCount} employee(s) have local arrear calculations that will be saved during processing.
                    </div>
                `;
            }

            if (exists) {
                confirmMessage += `
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="forceAll">
                    <label class="form-check-label" for="forceAll">
                        I understand this will overwrite existing records
                    </label>
                </div>`;
            }

            Swal.fire({
                title: exists ? `Overwrite Existing${filterText} Records?` : `Process All${filterText} Salaries?`,
                html: confirmMessage,
                icon: exists ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: exists ? 'Overwrite All' : 'Process All',
                confirmButtonColor: exists ? '#dc3545' : '#198754',
                preConfirm: () => {
                    if (exists) {
                        return {
                            force: $('#forceAll').is(':checked')
                        }
                    }
                    return {};
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                if (exists && !result.value?.force) {
                    toastr.warning("Operation cancelled - confirmation not checked");
                    return;
                }

                // Prepare data with local arrear
                const processData = {
                    _token: '{{ csrf_token() }}',
                    month: currentMonth,
                    year: currentYear,
                    force: exists ? '1' : '0',
                    requisition_type: currentRequisitionType,
                    local_arrears: localArrearCount > 0 ? localArrearData : null
                };

                // Show processing loader
                Swal.fire({
                    title: 'Processing...',
                    text: `Processing${filterText} salaries`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post("{{ route('salary.process') }}", processData, function(response) {
                    Swal.close();
                    if (response.success) {
                        toastr.success(response.message);
                        // Clear local arrear data after successful processing
                        localArrearData = {};
                        loadSalaryList();
                    } else {
                        toastr.error(response.message || "Processing failed");
                    }
                }).fail(function(xhr) {
                    Swal.close();
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error("Bulk processing failed");
                    }
                });
            });
        });
    }

    // Process Selected (checkboxes) - Modified to include local arrear data
    function processSelected() {
        const selected = $('.row-check:checked').closest('tr');
        const selectedIds = selected.map(function() {
            return $(this).data('candidate-id');
        }).get();

        if (selectedIds.length === 0) return toastr.warning("No employees selected");

        // Check if any selected are already processed
        let alreadyProcessed = [];
        selected.each(function() {
            const row = $(this).closest('tr');
            const statusBadge = row.find('.badge');
            if (statusBadge.hasClass('bg-success') || statusBadge.text().includes('Processed')) {
                alreadyProcessed.push(row.find('td:nth-child(3)').text().trim()); // Name column
            }
        });

        // Check for local arrear data in selected rows
        let localArrearSelected = [];
        selected.each(function() {
            const candidateId = $(this).data('candidate-id');
            const candidateKey = `${candidateId}_${currentMonth}_${currentYear}`;
            if (localArrearData[candidateKey]) {
                localArrearSelected.push({
                    id: candidateId,
                    name: $(this).find('td:nth-child(3)').text().trim(),
                    days: localArrearData[candidateKey].days,
                    amount: localArrearData[candidateKey].amount
                });
            }
        });

        let confirmMessage = `Process ${selectedIds.length} selected employee(s)?`;
        if (alreadyProcessed.length > 0) {
            confirmMessage = `
                <div class="alert alert-warning">
                    <i class="ri-alert-line"></i> ${alreadyProcessed.length} employee(s) are already processed:<br>
                    <small class="text-muted">${alreadyProcessed.slice(0, 3).join(', ')}${alreadyProcessed.length > 3 ? '...' : ''}</small><br>
                    <strong>Processing again will overwrite existing data!</strong>
                </div>`;
        }

        // Add local arrear info if any
        if (localArrearSelected.length > 0) {
            confirmMessage += `
                <div class="alert alert-info mt-2">
                    <i class="ri-information-line"></i>
                    <strong>Note:</strong> ${localArrearSelected.length} selected employee(s) have local arrear calculations that will be saved.
                </div>
            `;
        }

        if (alreadyProcessed.length > 0) {
            confirmMessage += `
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="forceProcess">
                    <label class="form-check-label" for="forceProcess">
                        Force Recalculation (overwrite existing)
                    </label>
                </div>
            `;
        }

        Swal.fire({
            title: alreadyProcessed.length > 0 ? 'Overwrite Processed Salaries?' : 'Process Selected?',
            html: confirmMessage,
            icon: alreadyProcessed.length > 0 ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: alreadyProcessed.length > 0 ? 'Yes, Overwrite' : 'Yes, Process',
            confirmButtonColor: alreadyProcessed.length > 0 ? '#dc3545' : '#198754',
            showDenyButton: alreadyProcessed.length > 0,
            denyButtonText: 'Skip Processed & Only Process New',
            denyButtonColor: '#6c757d',
            preConfirm: () => {
                return {
                    force: $('#forceProcess').is(':checked')
                }
            }
        }).then((result) => {
            if (result.isDismissed) return;

            const force = result.value?.force || false;
            const skipProcessed = result.isDenied;

            // Prepare candidate IDs to process
            let candidateIdsToProcess = [];
            let candidateArrearsToProcess = {};

            if (skipProcessed) {
                selected.each(function() {
                    const row = $(this).closest('tr');
                    const statusBadge = row.find('.badge');
                    if (!statusBadge.hasClass('bg-success') && !statusBadge.text().includes('Processed')) {
                        const candidateId = $(this).closest('tr').data('candidate-id');
                        candidateIdsToProcess.push(candidateId);

                        // Include local arrear if exists
                        const candidateKey = `${candidateId}_${currentMonth}_${currentYear}`;
                        if (localArrearData[candidateKey]) {
                            candidateArrearsToProcess[candidateId] = localArrearData[candidateKey];
                        }
                    }
                });

                if (candidateIdsToProcess.length === 0) {
                    toastr.info("All selected are already processed");
                    return;
                }
            } else {
                candidateIdsToProcess = selectedIds;
                // Include all local arrears for selected candidates
                selectedIds.forEach(candidateId => {
                    const candidateKey = `${candidateId}_${currentMonth}_${currentYear}`;
                    if (localArrearData[candidateKey]) {
                        candidateArrearsToProcess[candidateId] = localArrearData[candidateKey];
                    }
                });
            }

            // Show processing loader
            Swal.fire({
                title: 'Processing...',
                text: `Processing ${candidateIdsToProcess.length} employee(s)`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send as JSON array with local arrear data
            $.ajax({
                url: "{{ route('salary.process') }}",
                method: 'POST',
                data: JSON.stringify({
                    _token: '{{ csrf_token() }}',
                    month: currentMonth,
                    year: currentYear,
                    force: force ? '1' : '0',
                    candidate_ids: candidateIdsToProcess,
                    candidate_arrears: candidateArrearsToProcess,
                    requisition_type: currentRequisitionType
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        toastr.success(response.message);
                        // Clear processed local arrear data
                        candidateIdsToProcess.forEach(candidateId => {
                            const candidateKey = `${candidateId}_${currentMonth}_${currentYear}`;
                            delete localArrearData[candidateKey];
                        });
                        loadSalaryList();
                    } else {
                        toastr.error(response.message || "Processing failed");
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error("Processing failed");
                    }
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
                    let url = `/hr/salary/export?month=${currentMonth}&year=${currentYear}`;
                    if (currentRequisitionType && currentRequisitionType !== 'All') {
                        url += `&requisition_type=${currentRequisitionType}`;
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
</script>
@endsection