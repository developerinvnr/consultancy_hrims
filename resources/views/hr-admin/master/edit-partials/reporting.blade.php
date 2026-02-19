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
    <div class="col-md-12">
        <div class="form-group">
            <label>Select New Reporting Manager <span class="text-danger">*</span></label>
            <select name="new_reporting_manager_employee_id" id="employee_select" class="form-control select2">

                <option value="">Select Reporting Manager</option>
                @foreach($departmentEmployees as $emp)
                    <option value="{{ $emp->employee_id }}" 
                        data-name="{{ $emp->emp_name }}"
                        {{ $emp->employee_id == $candidate->reporting_manager_employee_id ? 'selected' : '' }}>
                        {{ $emp->emp_name }} ({{ $emp->emp_code ?? '' }}) - {{ $emp->emp_designation ?? '' }}
                    </option>
                @endforeach
            </select>
         @error('new_reporting_manager_employee_id')
            <div class="text-danger">{{ $message }}</div>
            @enderror
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
        theme: 'bootstrap-5'
    });

    // Store current reporting manager ID for comparison
    var currentReportingManagerId = '{{ $candidate->reporting_manager_employee_id }}';
    
    // When employee selected, add hidden field for name
    $('#employee_select').on('change', function() {
        var selectedOption = $(this).find(':selected');
        var empName = selectedOption.data('name');
        
        // Remove existing hidden inputs if any
        $('#editPartyForm').find('input[name="new_reporting_to"]').remove();
        
        // Add hidden input for reporting manager name
        $('<input>').attr({
            type: 'hidden',
            name: 'new_reporting_to',
            value: empName
        }).appendTo('#editPartyForm');
    });
    
    // Trigger change on page load to set initial hidden field
    $('#employee_select').trigger('change');
});
</script>
@endpush