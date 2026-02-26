<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Current Reporting Manager</label>
            <input type="text" class="form-control" 
                   value="{{ $candidate->reporting_to }} (ID: {{ $candidate->reporting_manager_employee_id }})" 
                   readonly disabled>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Department</label>
            <input type="text" class="form-control" 
                   value="{{ $candidate->department->department_name ?? 'N/A' }}" 
                   readonly disabled>
            <input type="hidden" name="department_id" value="{{ $candidate->department_id }}">
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Select Department <span class="text-danger">*</span></label>

            <select name="reporting_department_id" 
                    id="reporting_department_id" 
                    class="form-control select2">

                <option value="">Select Department</option>

                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}"
                        {{ $dept->id == $candidate->department_id ? 'selected' : '' }}>
                        {{ $dept->department_name }}
                    </option>
                @endforeach

            </select>

        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Select New Reporting Manager <span class="text-danger">*</span></label>

            <select name="new_reporting_manager_employee_id" 
                    id="employee_select" 
                    class="form-control select2">

                <option value="">Select Reporting Manager</option>

            </select>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Reason for Change <span class="text-danger">*</span></label>
            <select name="reporting_change_reason" class="form-control">

                <option value="">Select Reason</option>
                <option value="Resigned">Reporting Manager Resigned</option>
                <option value="Transferred">Reporting Manager Transferred</option>
                <option value="Reorganized">Team Reorganization</option>
                <option value="Promoted">Reporting Manager Promoted</option>
                <option value="Other">Other</option>
            </select>
            @error('reporting_change_reason')
<div class="text-danger">{{ $message }}</div>
@enderror

        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Additional Remarks</label>
            <textarea name="reporting_change_remarks" class="form-control" rows="2"></textarea>
        </div>
    </div>
</div>

<div class="alert alert-warning">
    <i class="fas fa-clock"></i> Reporting changes will be effective from next working day.
</div>

@push('scripts')
<script>
$(document).ready(function() {

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Current reporting manager ID (for auto select)
    var currentReportingManagerId = '{{ $candidate->reporting_manager_employee_id }}';

    // Disable employee select initially
    $('#employee_select').prop('disabled', true);

    // Load employees when department changes
    $('#reporting_department_id').on('change', function() {

        var departmentId = $(this).val();

        $('#employee_select').html('<option value="">Loading...</option>');
        $('#employee_select').prop('disabled', true);

        if (!departmentId) {
            $('#employee_select').html('<option value="">Select Reporting Manager</option>');
            return;
        }

        $.ajax({
            url: "{{ route('hr.get.employees.by.department') }}",
            type: "GET",
            data: {
                department_id: departmentId
            },
            success: function(response) {

                var options = '<option value="">Select Reporting Manager</option>';

                $.each(response, function(index, emp) {

                    var selected = (emp.employee_id == currentReportingManagerId) ? 'selected' : '';

                    options += `<option value="${emp.employee_id}" 
                                data-name="${emp.emp_name}"
                                ${selected}>
                                ${emp.emp_name} (${emp.emp_code ?? ''})
                                </option>`;
                });

                $('#employee_select')
                    .html(options)
                    .prop('disabled', false)
                    .trigger('change');

            },
            error: function() {

                $('#employee_select')
                    .html('<option value="">Error loading employees</option>')
                    .prop('disabled', false);

            }
        });

    });

    // When employee selected, add hidden field for name
    $('#employee_select').on('change', function() {

        var empName = $(this).find(':selected').data('name') || '';

        $('#editPartyForm').find('input[name="new_reporting_to"]').remove();

        if(empName){
            $('<input>').attr({
                type: 'hidden',
                name: 'new_reporting_to',
                value: empName
            }).appendTo('#editPartyForm');
        }

    });

    // Trigger department change on page load
    $('#reporting_department_id').trigger('change');

});
</script>
@endpush