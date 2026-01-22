@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0">Team Attendance</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Team Attendance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Filters -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 small text-muted">Month</label>
                        </div>
                        <div class="col-md-2 col-sm-3">
                            <select name="month" id="monthFilter" class="form-select form-select-sm">
                                @php
                                $currentMonth = date('n');
                                $currentYear = date('Y');
                                $months = [];
                                for ($i = 0; $i < 6; $i++) {
                                    $date=date_create(date('Y-m-01'));
                                    date_modify($date, "-{$i} months" );
                                    $months[]=[ 'value'=> date_format($date, 'Y-m'),
                                    'label' => date_format($date, 'F Y')
                                    ];
                                    }
                                    @endphp
                                    @foreach($months as $month)
                                    <option value="{{ $month['value'] }}" {{ $loop->first ? 'selected' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                    @endforeach
                            </select>
                        </div>

                        <div class="col-auto">
                            <label class="form-label mb-0 small text-muted">Type</label>
                        </div>
                        <div class="col-md-2 col-sm-3">
                            <select name="employee_type" id="employeeTypeFilter" class="form-select form-select-sm">
                                <option value="all">All Candidates</option>
                                <option value="Contractual">Contractual</option>
                                <option value="TFA">TFA</option>
                                <option value="CB">CB</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="button" onclick="loadAttendance()" class="btn btn-sm btn-primary">
                                <i class="ri-refresh-line align-middle"></i> Load
                            </button>
                        </div>

                        <div class="col-auto ms-auto">
                            <button type="button" onclick="openSundayWorkModal()" class="btn btn-sm btn-success">
                                <i class="ri-calendar-2-line align-middle"></i> Add Sunday Work
                            </button>
                            <button type="button" onclick="exportAttendance()" class="btn btn-sm btn-success me-2">
                                <i class="ri-download-line align-middle"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="text-center" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading attendance data...</p>
    </div>

    <!-- Attendance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0">Attendance Sheet</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive" id="tableContainer" style="display: none;">
                        <table class="table table-bordered table-hover table-sm mb-0" id="attendanceTable">
                            <thead class="table-light">
                                <tr id="tableHeader">
                                    <!-- Dynamic headers will be loaded here -->
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Dynamic rows will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sunday Work Modal -->
<div class="modal fade" id="sundayWorkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-calendar-2-line align-middle"></i> Sunday Working Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sundayWorkForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Month *</label>
                            <select name="month" id="swMonth" class="form-select" required>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == $currentMonth ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                    @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Select Year *</label>
                            <input type="text" name="year" id="swYear" class="form-control"
                                value="{{ $currentYear }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Sunday Date(s) *</label>
                            <select name="sunday_dates[]" id="swSundayDate" class="form-select" multiple
                                style="height: 200px;" required>
                                <!-- Sundays will be loaded dynamically -->
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple Sundays</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Candidate(s) *</label>
                            <select name="candidate_ids[]" id="swCandidates" class="form-select" multiple
                                style="height: 200px;" required>
                                <!-- Candidates will be loaded dynamically -->
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple candidates</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Work Details / Remark *</label>
                            <textarea name="remark" id="swRemark" class="form-control"
                                rows="3" placeholder="Enter work details..." required></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" name="attachment" id="swAttachment" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script_section')
<script>
    // Global variables
    let currentMonth, currentYear, currentEmployeeType, currentDaysInMonth;

    // Initialize page
    $(document).ready(function() {
        loadAttendance();
    });

    // Load attendance data
    function loadAttendance() {
        const monthYear = $('#monthFilter').val().split('-');
        if (!monthYear[0] || !monthYear[1]) {
            toastr.error('Please select month and year');
            return;
        }

        currentYear = parseInt(monthYear[0], 10);
        currentMonth = parseInt(monthYear[1], 10);
        currentEmployeeType = $('#employeeTypeFilter').val();

        // Show loading
        $('#loadingSpinner').show();
        $('#tableContainer').hide();

        $.ajax({
            url: '{{ route("attendance.get") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                month: currentMonth,
                year: currentYear,
                employee_type: currentEmployeeType
            },
            success: function(response) {
                $('#loadingSpinner').hide();
                if (response.success) {
                    renderAttendanceTable(response.data);
                } else {
                    toastr.error(response.message || 'Error loading attendance data');
                }
            },
            error: function() {
                $('#loadingSpinner').hide();
                toastr.error('Error loading attendance data');
            }
        });
    }

    // Render attendance table
    function renderAttendanceTable(data) {
        currentDaysInMonth = data.days_in_month;
        const candidates = data.candidates;

        // Build header
        let headerHtml = `
        <th style="width: 40px;" class="text-center">#</th>
        <th style="min-width: 90px;">Code</th>
        <th style="min-width: 120px;">Name</th>
        <th style="width: 60px;" class="text-center">Type</th>`;

        // Add day columns
        for (let day = 1; day <= currentDaysInMonth; day++) {
            const date = new Date(currentYear, currentMonth - 1, day);
            const dayOfWeek = date.getDay();
            const isSunday = dayOfWeek === 0;
            const dayName = date.toLocaleDateString('en-US', {
                weekday: 'short'
            });

            headerHtml += `<th class="text-center ${isSunday ? 'sunday-cell' : ''}" 
    style="width: 46px; padding: 4px 2px;">
    <div style="font-size: 11px; font-weight: 600;">${day}</div>
    <div style="font-size: 9px; color: #666;">${dayName}</div>
    </th>`;
        }

        // Add summary columns
        headerHtml += `
        <th style="width: 35px;" class="text-center">P</th>
        <th style="width: 35px;" class="text-center">A</th>
        <th style="width: 35px;" class="text-center">CL</th>
        <th style="width: 50px;" class="text-center">Bal</th>
        <th style="width: 70px;" class="text-center">Action</th>`;

        $('#tableHeader').html(headerHtml);

        // Build rows
        let bodyHtml = '';
        candidates.forEach((candidate, index) => {
            const isContractual = candidate.requisition_type === 'Contractual';

            let rowHtml = `
            <tr id="row-${candidate.candidate_id}" data-candidate-id="${candidate.candidate_id}">
                <td class="text-center">${index + 1}</td>
                <td><small><strong>${candidate.candidate_code}</strong></small></td>
                <td><small>${candidate.candidate_name}</small></td>
                <td class="text-center"><span class="badge bg-secondary" style="font-size: 9px; padding: 2px 4px;">${candidate.requisition_type}</span></td>`;

            // Add day cells
            for (let day = 1; day <= currentDaysInMonth; day++) {
                const date = new Date(currentYear, currentMonth - 1, day);
                const isSunday = date.getDay() === 0;
                const status = candidate.attendance[day] || '';

                const sundayStatus = status === 'P' ? 'P' : 'W';

                let optionsHtml = '';

                if (isSunday) {
                    optionsHtml = `
                    <option value="W" ${sundayStatus === 'W' ? 'selected' : ''}>W</option>
                    <option value="P" ${sundayStatus === 'P' ? 'selected' : ''}>P</option>`;
                } else {
                    if (isContractual) {
                        optionsHtml = `
                        <option value=""></option>
                        <option value="P" ${status === 'P' ? 'selected' : ''}>P</option>
                        <option value="A" ${status === 'A' ? 'selected' : ''}>A</option>
                        <option value="CL" ${status === 'CL' ? 'selected' : ''}>CL</option>
                        <option value="H" ${status === 'H' ? 'selected' : ''}>H</option>`;
                    } else {
                        optionsHtml = `
                        <option value=""></option>
                        <option value="P" ${status === 'P' ? 'selected' : ''}>P</option>
                        <option value="A" ${status === 'A' ? 'selected' : ''}>A</option>
                        <option value="H" ${status === 'H' ? 'selected' : ''}>H</option>`;
                    }
                }

                rowHtml += `<td class="text-center ${isSunday ? 'sunday-cell' : ''}" data-day="${day}">
    ${isSunday ? 
        `<span class="badge ${sundayStatus === 'P' ? 'bg-success' : 'bg-light text-muted'}" 
               style="cursor: default; font-size: 10px; padding: 2px 4px;">
            ${sundayStatus}
        </span>` : 
        `<select class="form-select form-select-sm edit-select compact-select" data-day="${day}" disabled>
            ${optionsHtml}
        </select>`
    }
    </td>`;
            }

            // Add summary columns
            rowHtml += `
            <td class="text-center"><span class="badge bg-success" style="font-size: 10px; padding: 2px 4px;" id="present-${candidate.candidate_id}">${candidate.total_present || 0}</span></td>
            <td class="text-center"><span class="badge bg-danger" style="font-size: 10px; padding: 2px 4px;" id="absent-${candidate.candidate_id}">${candidate.total_absent || 0}</span></td>
            <td class="text-center"><span class="badge bg-info" style="font-size: 10px; padding: 2px 4px;" id="cl-${candidate.candidate_id}">${candidate.cl_used || 0}</span></td>
            <td class="text-center">
                ${isContractual ? 
                    `<span class="badge ${candidate.cl_remaining > 5 ? 'bg-success' : 
                                          candidate.cl_remaining > 2 ? 'bg-warning' : 
                                          'bg-danger'}" 
                           style="font-size: 10px; padding: 2px 4px;"
                           id="cl-balance-${candidate.candidate_id}"
                           title="Total: ${candidate.leave_credited || 0}">
                        ${candidate.cl_remaining || 0}
                    </span>` : 
                    '<small class="text-muted">N/A</small>'}
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary edit-toggle-btn" 
                        onclick="toggleEdit(${candidate.candidate_id})"
                        id="btn-${candidate.candidate_id}"
                        style="padding: 1px 6px; font-size: 11px;">
                    <i class="ri-edit-line"></i>
                </button>
                <button class="btn btn-sm btn-light ms-1 cancel-btn" 
                        onclick="cancelEdit(${candidate.candidate_id})"
                        id="cancel-${candidate.candidate_id}"
                        style="display:none; padding: 1px 6px; font-size: 11px;">
                    <i class="ri-close-line"></i>
                </button>
            </td>
            </tr>`;

            bodyHtml += rowHtml;
        });

        $('#tableBody').html(bodyHtml);
        $('#tableContainer').show();
    }

    // Recalculate totals for editing row
    function recalculateTotals(candidateId) {
        const row = $(`#row-${candidateId}`);
        let present = 0,
            absent = 0,
            cl = 0;

        row.find('.edit-select').each(function() {
            const status = $(this).val();
            switch (status) {
                case 'P':
                    present++;
                    break;
                case 'A':
                    absent++;
                    break;
                case 'CL':
                    cl++;
                    break;
            }
        });

        $(`#present-${candidateId}`).text(present);
        $(`#absent-${candidateId}`).text(absent);
        $(`#cl-${candidateId}`).text(cl);
    }

    // Save attendance for candidate
    function saveAttendance(candidateId) {
        const row = $(`#row-${candidateId}`);
        const attendanceData = {};

        row.find('.edit-select').each(function() {
            const day = $(this).data('day');
            const date = new Date(currentYear, currentMonth - 1, parseInt(day));
            const isSunday = date.getDay() === 0;

            if (!isSunday) {
                const status = $(this).val() || '';
                attendanceData[day] = status;
            }
        });

        if (currentDaysInMonth) {
            for (let day = 1; day <= currentDaysInMonth; day++) {
                const date = new Date(currentYear, currentMonth - 1, day);
                if (date.getDay() === 0) {
                    const sundayCell = row.find(`td[data-day="${day}"]`);
                    const sundayStatus = sundayCell.find('.badge').text();
                    attendanceData[day] = sundayStatus || 'W';
                }
            }
        }

        const btn = $(`#btn-${candidateId}`);
        const cancelBtn = $(`#cancel-${candidateId}`);
        const selects = row.find('.edit-select');

        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');

        $.ajax({
            url: '{{ route("attendance.update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                candidate_id: candidateId,
                month: currentMonth,
                year: currentYear,
                attendance: JSON.stringify(attendanceData)
            },
            success: function(response) {
                btn.prop('disabled', false);

                if (response.success) {
                    toastr.success('Attendance saved successfully!');

                    const clBalanceBadge = $(`#cl-balance-${candidateId}`);
                    const clUsedBadge = $(`#cl-${candidateId}`);
                    const isContractual = row.find('.badge.bg-secondary').text() === 'Contractual';

                    if (isContractual && response.cl_remaining !== undefined) {
                        const newBalance = response.cl_remaining;
                        const newUsed = response.cl_used;

                        clUsedBadge.text(newUsed); // ✅ FIXED
                        clBalanceBadge.text(newBalance);

                        clBalanceBadge.removeClass('bg-success bg-warning bg-danger');
                        if (newBalance > 5) {
                            clBalanceBadge.addClass('bg-success');
                        } else if (newBalance > 2) {
                            clBalanceBadge.addClass('bg-warning');
                        } else {
                            clBalanceBadge.addClass('bg-danger');
                        }
                    }


                    if (response.warning) {
                        toastr.warning(response.warning);
                    }

                    selects.prop('disabled', true);
                    row.removeClass('editing-row');
                    btn.html('<i class="ri-edit-line"></i>');
                    btn.removeClass('btn-success').addClass('btn-outline-primary');
                    cancelBtn.hide();

                } else {
                    toastr.error(response.message || 'Error saving attendance');
                    btn.html('<i class="ri-save-line"></i>');
                    btn.removeClass('btn-outline-primary').addClass('btn-success');
                    selects.prop('disabled', false);
                    row.addClass('editing-row');
                    cancelBtn.show();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Error saving attendance');
                }

                btn.html('<i class="ri-save-line"></i>');
                btn.removeClass('btn-outline-primary').addClass('btn-success');
            }
        });
    }

    // Sunday Work Modal functions
    function openSundayWorkModal() {
        $('#swMonth').val(currentMonth);
        $('#swYear').val(currentYear);
        loadSWCandidates();
        loadSundays();
        $('#sundayWorkModal').modal('show');
    }

    function loadSundays() {
        const month = $('#swMonth').val();
        const year = $('#swYear').val();

        if (!month || !year) return;

        $.ajax({
            url: '{{ route("attendance.get-sundays") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                month: month,
                year: year
            },
            success: function(response) {
                if (response.success) {
                    const sundaySelect = $('#swSundayDate');
                    sundaySelect.empty();

                    response.data.forEach(sunday => {
                        sundaySelect.append(new Option(
                            `${sunday.date} (${sunday.day_name})`,
                            sunday.date
                        ));
                    });
                }
            }
        });
    }

    function loadSWCandidates() {
        $.ajax({
            url: '{{ route("attendance.get-active-candidates") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    const candidateSelect = $('#swCandidates');
                    candidateSelect.empty();

                    response.data.forEach(candidate => {
                        candidateSelect.append(new Option(
                            candidate.candidate_name,
                            candidate.id
                        ));
                    });
                }
            }
        });
    }

    $('#sundayWorkForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("attendance.submit-sunday-work") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Sunday work request submitted successfully!');
                    $('#sundayWorkModal').modal('hide');
                    $('#sundayWorkForm')[0].reset();
                    loadAttendance();
                } else {
                    toastr.error(response.message || 'Error submitting request');
                }
            }
        });
    });

    function cancelEdit(candidateId) {
        const row = $(`#row-${candidateId}`);
        const btn = $(`#btn-${candidateId}`);
        const cancelBtn = $(`#cancel-${candidateId}`);
        const selects = row.find('.edit-select');

        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');

        $.ajax({
            url: '{{ route("attendance.get-candidate") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                candidate_id: candidateId,
                month: currentMonth,
                year: currentYear
            },
            success: function(response) {
                if (response.success) {
                    updateCandidateRow(candidateId, response.data);

                    selects.prop('disabled', true);
                    row.removeClass('editing-row');
                    btn.html('<i class="ri-edit-line"></i>');
                    btn.removeClass('btn-success').addClass('btn-outline-primary');
                    btn.prop('disabled', false);
                    cancelBtn.hide();

                    toastr.info('Changes cancelled');
                } else {
                    toastr.error(response.message || 'Error loading attendance data');
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('Error loading attendance data');
                btn.html(originalHtml);
                btn.prop('disabled', false);
            }
        });
    }

    function updateCandidateRow(candidateId, data) {
        const row = $(`#row-${candidateId}`);

        for (let day = 1; day <= currentDaysInMonth; day++) {
            const status = data.attendance[day] || '';
            const cell = row.find(`td[data-day="${day}"]`);

            if (cell.length) {
                const select = cell.find('select');
                if (select.length) {
                    select.val(status);
                }
            }
        }

        recalculateTotals(candidateId);
    }

    function toggleEdit(candidateId) {
        const row = $(`#row-${candidateId}`);
        const btn = $(`#btn-${candidateId}`);
        const cancelBtn = $(`#cancel-${candidateId}`);
        const selects = row.find('.edit-select');

        const currentIcon = btn.find('i').attr('class');
        const isEditMode = currentIcon.includes('ri-edit-line');

        if (isEditMode) {
            selects.each(function() {
                const day = $(this).data('day');
                const date = new Date(currentYear, currentMonth - 1, parseInt(day));
                const isSunday = date.getDay() === 0;

                if (!isSunday) {
                    $(this).prop('disabled', false);
                }
            });
            row.addClass('editing-row');
            btn.html('<i class="ri-save-line"></i>');
            btn.removeClass('btn-outline-primary').addClass('btn-success');
            cancelBtn.show();
        } else {
            saveAttendance(candidateId);
        }
    }

    // Export attendance data
    // Alternative export function using fetch API
    function exportAttendance() {
        const monthYear = $('#monthFilter').val().split('-');
        if (!monthYear[0] || !monthYear[1]) {
            toastr.error('Please select month and year');
            return;
        }

        const year = parseInt(monthYear[0], 10);
        const month = parseInt(monthYear[1], 10);
        const employeeType = $('#employeeTypeFilter').val();

        // Show loading indicator
        const exportBtn = $(event.target).closest('button');
        const originalHtml = exportBtn.html();
        exportBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Exporting...');

        // Build the URL
        const url = '{{ route("attendance.export") }}' +
            '?month=' + month +
            '&year=' + year +
            '&employee_type=' + encodeURIComponent(employeeType) +
            '&_token=' + encodeURIComponent('{{ csrf_token() }}');

        // Use fetch API to download the file
        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.blob();
            })
            .then(blob => {
                // Create a download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;

                // Set filename
                const monthName = new Date(year, month - 1).toLocaleString('default', {
                    month: 'long'
                });
                a.download = `attendance_${monthName}_${year}.xlsx`;

                // Append to body and click
                document.body.appendChild(a);
                a.click();

                // Cleanup
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                toastr.success('Export completed successfully!');
            })
            .catch(error => {
                console.error('Export error:', error);
                toastr.error('Error exporting data: ' + error.message);
            })
            .finally(() => {
                exportBtn.prop('disabled', false).html(originalHtml);
            });
    }

    $('#swMonth').on('change', function() {
        loadSundays();
    });
</script>

<style>
    /* Sunday column color */
    .sunday-cell {
        background-color: #f3f6ff !important;
    }

    /* Row being edited */
    .editing-row {
        background-color: #fff8e1 !important;
    }

    /* Compact table styling */
    #attendanceTable {
        font-size: 11px;
    }

    #attendanceTable th,
    #attendanceTable td {
        padding: 3px 2px !important;
        vertical-align: middle;
    }

    #attendanceTable td[data-day] {
        padding: 2px !important;
    }

    #tableHeader th {
        font-size: 11px;
        line-height: 1.2;
    }

    /* Compact dropdown style */
    .compact-select {
        width: 42px !important;
        height: 24px !important;
        padding: 1px 16px 1px 4px !important;
        font-size: 10px !important;
        text-align: left;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        background-color: #ffffff;
        font-weight: 500;
        transition: all 0.2s ease;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3E%3Cpath fill='%23333' d='M0 2l4 4 4-4z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 3px center;
        background-size: 7px 7px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .compact-select:not(:disabled):hover {
        border-color: #86b7fe;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.08);
    }

    .compact-select:disabled {
        background-color: transparent;
        border-color: transparent;
        color: #495057;
        font-weight: 600;
        background-image: none;
        padding-right: 4px !important;
    }

    .compact-select:focus {
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
        border-color: #0d6efd !important;
        outline: none;
    }

    /* Status color coding */
    .compact-select option[value="P"] {
        color: #198754;
    }

    .compact-select option[value="A"] {
        color: #dc3545;
    }

    .compact-select option[value="CL"] {
        color: #0dcaf0;
    }

    .compact-select option[value="H"] {
        color: #6c757d;
    }

    /* Sunday modal */
    #sundayWorkModal .modal-body {
        padding: 1.25rem;
    }

    #sundayWorkModal .form-label {
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    #sundayWorkModal select[multiple] {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    #sundayWorkModal .modal-xl {
        max-width: 1000px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #attendanceTable {
            font-size: 10px;
        }

        .compact-select {
            width: 38px !important;
            height: 22px !important;
            font-size: 9px !important;
            background-size: 6px 6px;
            background-position: right 2px center;
        }

        #tableHeader th {
            font-size: 10px;
        }

        #attendanceTable th[style*="width: 46px"] {
            width: 38px !important;
        }
    }
</style>
@endsection