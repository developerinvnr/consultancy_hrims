@extends('layouts.guest')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Team Attendance</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Team Attendance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter Attendance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Month</label>
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
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Candidate Type</label>
                                <select name="employee_type" id="employeeTypeFilter" class="form-select form-select-sm">
                                    <option value="all">All Candidates</option>
                                    <option value="Contractual">Contractual</option>
                                    <option value="TFA">TFA</option>
                                    <option value="CB">CB</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="mb-3 w-100">
                                <button type="button" onclick="loadAttendance()" class="btn btn-sm btn-primary w-100">
                                    <i class="ri-refresh-line align-middle"></i> Load Attendance
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-end">
                            <div class="mb-3">
                                <button type="button" onclick="openSundayWorkModal()" class="btn btn-sm btn-success">
                                    <i class="ri-calendar-2-line align-middle"></i> Add Sunday Work
                                </button>
                            </div>
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
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance Sheet</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="tableContainer" style="display: none;">
                        <table class="table table-bordered table-hover" id="attendanceTable">
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
    let currentMonth, currentYear, currentEmployeeType;

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
        const daysInMonth = data.days_in_month;
        const candidates = data.candidates;

        // Build header
        let headerHtml = `
        <th style="width: 50px;" class="text-center">S.No</th>
        <th style="min-width: 120px;">Candidate Code</th>
        <th style="min-width: 180px;">Candidate Name</th>
        <th style="width: 100px;" class="text-center">Type</th>`;

        // Add day columns - Compact 35px width
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(currentYear, currentMonth - 1, day);
            const dayOfWeek = date.getDay();
            const isSunday = dayOfWeek === 0;
            const dayName = date.toLocaleDateString('en-US', {
                weekday: 'short'
            });

            headerHtml += `<th class="text-center ${isSunday ? 'sunday-cell' : ''}" 
style="width: 35px; padding: 2px;">
<div style="font-size: 11px; font-weight: 600;">${day}</div>
<div style="font-size: 9px; color: #666;">${dayName}</div>
</th>`;
        }

        // Add summary columns
        headerHtml += `
        <th style="width: 40px;" class="text-center">P</th>
        <th style="width: 40px;" class="text-center">A</th>
        <th style="width: 40px;" class="text-center">CL</th>
        <th style="width: 60px;" class="text-center">CL Bal</th>
        <th style="width: 80px;" class="text-center">Action</th>`;

        $('#tableHeader').html(headerHtml);

        // Build rows
        let bodyHtml = '';
        candidates.forEach((candidate, index) => {
            const isContractual = candidate.requisition_type === 'Contractual';

            let rowHtml = `
            <tr id="row-${candidate.candidate_id}" data-candidate-id="${candidate.candidate_id}">
                <td class="text-center">${index + 1}</td>
                <td><strong>${candidate.candidate_code}</strong></td>
                <td>${candidate.candidate_name}</td>
                <td class="text-center"><span class="badge bg-secondary">${candidate.requisition_type}</span></td>`;

            // Add day cells - Compact 35px width
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(currentYear, currentMonth - 1, day);
                const isSunday = date.getDay() === 0;
                const status = candidate.attendance[day] || '';

                // For Sundays, only show if it's 'P' (Sunday work) or 'W' (Weekend)
                // Auto-set to 'W' if empty
                const sundayStatus = status === 'P' ? 'P' : 'W';

                // Create options based on whether it's Sunday or not
                let optionsHtml = '';

                if (isSunday) {
                    // Sunday cells: only 'W' or 'P' if Sunday work was requested
                    optionsHtml = `
                    <option value="W" ${sundayStatus === 'W' ? 'selected' : ''}>W</option>
                    <option value="P" ${sundayStatus === 'P' ? 'selected' : ''}>P</option>`;
                } else {
                    // Regular days: all options
                    optionsHtml = `
                    <option value=""></option>
                    <option value="P" ${status === 'P' ? 'selected' : ''}>P</option>
                    <option value="A" ${status === 'A' ? 'selected' : ''}>A</option>
                    <option value="CL" ${status === 'CL' ? 'selected' : ''}>CL</option>
                    <option value="H" ${status === 'H' ? 'selected' : ''}>H</option>`;
                }

                rowHtml += `<td class="text-center ${isSunday ? 'sunday-cell' : ''}" data-day="${day}">
<div class="day-cell-content">
    ${isSunday ? 
        `<span class="badge ${sundayStatus === 'P' ? 'bg-success' : 'bg-light text-muted'}" 
               style="cursor: default; font-size: 11px; padding: 3px 5px;">
            ${sundayStatus}
        </span>` : 
        `<select class="form-select form-select-sm edit-select compact-select" data-day="${day}" disabled>
            ${optionsHtml}
        </select>`
    }
</div>
</td>`;
            }

            // Add summary columns
            rowHtml += `
            <td class="text-center"><span class="badge bg-success" id="present-${candidate.candidate_id}">${candidate.total_present || 0}</span></td>
            <td class="text-center"><span class="badge bg-danger" id="absent-${candidate.candidate_id}">${candidate.total_absent || 0}</span></td>
            <td class="text-center"><span class="badge bg-info" id="cl-${candidate.candidate_id}">${candidate.cl_used || 0}</span></td>
            <td class="text-center">
                ${isContractual ? 
                    `<span class="badge ${candidate.cl_remaining > 5 ? 'bg-success' : candidate.cl_remaining > 2 ? 'bg-warning' : 'bg-danger'}" id="cl-balance-${candidate.candidate_id}">
                        ${candidate.cl_remaining || 12}
                    </span>` : 
                    '<span class="text-muted">N/A</span>'}
            </td>
           <td class="text-center">
    <button class="btn btn-sm btn-outline-primary edit-toggle-btn" 
            onclick="toggleEdit(${candidate.candidate_id})"
            id="btn-${candidate.candidate_id}"
            style="padding: 2px 8px; font-size: 12px;">
        <i class="ri-edit-line"></i>
    </button>

    <button class="btn btn-sm btn-light ms-1 cancel-btn" 
            onclick="cancelEdit(${candidate.candidate_id})"
            id="cancel-${candidate.candidate_id}"
            style="display:none; padding: 2px 8px; font-size: 12px;">
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

        // Update display
        $(`#present-${candidateId}`).text(present);
        $(`#absent-${candidateId}`).text(absent);
        $(`#cl-${candidateId}`).text(cl);
    }

    // Save attendance for candidate
    function saveAttendance(candidateId) {
        const row = $(`#row-${candidateId}`);
        const attendanceData = {};

        // Collect data from dropdowns (only for non-Sunday days)
        row.find('.edit-select').each(function() {
            const day = $(this).data('day');
            const date = new Date(currentYear, currentMonth - 1, parseInt(day));
            const isSunday = date.getDay() === 0;

            if (!isSunday) {
                const status = $(this).val() || '';
                attendanceData[day] = status;
            }
        });

        // Add Sundays (all Sundays should be 'W' or 'P' if Sunday work)
        for (let day = 1; day <= data.days_in_month; day++) {
            const date = new Date(currentYear, currentMonth - 1, day);
            if (date.getDay() === 0) {
                // Check if this Sunday has been marked as 'P' via Sunday work
                const sundayCell = row.find(`td[data-day="${day}"]`);
                const sundayStatus = sundayCell.find('.badge').text();
                attendanceData[day] = sundayStatus || 'W';
            }
        }

        // Send to server
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
                if (response.success) {
                    toastr.success('Attendance saved successfully!');

                    // Update CL balance if available
                    if (response.cl_remaining !== undefined) {
                        const clBalanceBadge = $(`#cl-balance-${candidateId}`);
                        const newBalance = response.cl_remaining;
                        clBalanceBadge.text(newBalance);

                        // Update badge color based on balance
                        clBalanceBadge.removeClass('bg-success bg-warning bg-danger');
                        if (newBalance > 5) {
                            clBalanceBadge.addClass('bg-success');
                        } else if (newBalance > 2) {
                            clBalanceBadge.addClass('bg-warning');
                        } else {
                            clBalanceBadge.addClass('bg-danger');
                        }
                    }

                    // Show warning if any
                    if (response.warning) {
                        toastr.warning(response.warning);
                    }

                    // Switch back to display mode
                    cancelEdit(candidateId);

                } else {
                    toastr.error(response.message || 'Error saving attendance');
                }
            },
            error: function() {
                toastr.error('Error saving attendance');
            }
        });
    }


    // Sunday Work Modal functions
    function openSundayWorkModal() {
        // Set current month and year
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

    // Handle Sunday Work form submission
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
        const selects = row.find('.edit-select');
        const btn = $(`#btn-${candidateId}`);
        const cancelBtn = $(`#cancel-${candidateId}`);

        // Reload original data by reloading attendance
        loadAttendance();

        // Restore UI
        selects.prop('disabled', true);
        row.removeClass('editing-row');

        btn.html('<i class="ri-edit-line"></i>');
        btn.removeClass('btn-success').addClass('btn-outline-primary');

        cancelBtn.hide();
    }

    // Toggle edit mode
    function toggleEdit(candidateId) {
        const row = $(`#row-${candidateId}`);
        const btn = $(`#btn-${candidateId}`);
        const cancelBtn = $(`#cancel-${candidateId}`);
        const selects = row.find('.edit-select');

        if (selects.prop('disabled')) {
            // Enable edit mode ONLY for non-Sunday selects
            selects.each(function() {
                const day = $(this).data('day');
                const date = new Date(currentYear, currentMonth - 1, parseInt(day));
                const isSunday = date.getDay() === 0;

                // Only enable non-Sunday selects
                if (!isSunday) {
                    $(this).prop('disabled', false);
                }
            });
            row.addClass('editing-row');

            btn.html('<i class="ri-save-line"></i>');
            btn.removeClass('btn-outline-primary').addClass('btn-success');

            cancelBtn.show();
        } else {
            // Save and lock again
            saveAttendance(candidateId);

            selects.prop('disabled', true);
            row.removeClass('editing-row');

            btn.html('<i class="ri-edit-line"></i>');
            btn.removeClass('btn-success').addClass('btn-outline-primary');

            cancelBtn.hide();
        }
    }

    // Initialize when month changes in Sunday modal
    $('#swMonth').on('change', function() {
        loadSundays();
    });
</script>

<style>
    /* Sunday column color */
    .sunday-cell {
        background-color: #f3f6ff !important;
        /* soft blue */
    }

    /* Row being edited */
    .editing-row {
        background-color: #fff8e1 !important;
        /* soft yellow */
    }

    /* Disabled dropdown better look */
    .edit-select:disabled {
        background-color: #f8f9fa;
        cursor: not-allowed;
        border-color: #dee2e6;
    }

    /* Compact dropdown style */
    .compact-select {
        width: 35px !important;
        height: 28px !important;
        padding: 0 2px !important;
        font-size: 11px !important;
        text-align: center;
    }

    /* Make the table more compact */
    #attendanceTable th,
    #attendanceTable td {
        padding: 4px 2px !important;
    }

    /* Compact badges */
    #attendanceTable .badge {
        padding: 3px 6px;
        font-size: 11px;
    }

    /* Adjust header text */
    #tableHeader th {
        font-size: 11px;
        line-height: 1.2;
    }

    /* Sunday modal improved layout */
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

    /* Better select focus */
    .edit-select:focus {
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
        border-color: #86b7fe !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #attendanceTable {
            font-size: 12px;
        }

        .compact-select {
            width: 30px !important;
            height: 26px !important;
            font-size: 10px !important;
        }

        #tableHeader th {
            font-size: 10px;
        }
    }
</style>
@endsection