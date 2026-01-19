<div class="row">
    <div class="col-md-6 mb-3">
        <label for="work_location_hq" class="form-label">Work Location/HQ</label>
        <input type="text" class="form-control" id="work_location_hq" name="work_location_hq" 
               value="{{ $requisition->work_location_hq }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="district" class="form-label">District</label>
        <input type="text" class="form-control" id="district" name="district" 
               value="{{ $requisition->district }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="state_work_location" class="form-label">State (Work Location)</label>
        <input type="text" class="form-control" id="state_work_location" name="state_work_location" 
               value="{{ $requisition->state_work_location }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="function_id" class="form-label">Function</label>
        <select class="form-select" id="function_id" name="function_id">
            <option value="">-- Select Function --</option>
            @foreach($functions as $function)
                <option value="{{ $function->id }}" {{ $requisition->function_id == $function->id ? 'selected' : '' }}>
                    {{ $function->function_name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="department_id" class="form-label">Department</label>
        <select class="form-select" id="department_id" name="department_id">
            <option value="">-- Select Department --</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ $requisition->department_id == $department->id ? 'selected' : '' }}>
                    {{ $department->department_name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="vertical_id" class="form-label">Vertical</label>
        <select class="form-select" id="vertical_id" name="vertical_id">
            <option value="">-- Select Vertical --</option>
            @foreach($verticals as $vertical)
                <option value="{{ $vertical->id }}" {{ $requisition->vertical_id == $vertical->id ? 'selected' : '' }}>
                    {{ $vertical->vertical_name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="sub_department" class="form-label">Sub-department</label>
        <input type="text" class="form-control" id="sub_department" name="sub_department" 
               value="{{ $requisition->sub_department }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="business_unit" class="form-label">Business Unit</label>
        <input type="text" class="form-control" id="business_unit" name="business_unit" 
               value="{{ $requisition->business_unit }}">
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="zone" class="form-label">Zone</label>
        <input type="text" class="form-control" id="zone" name="zone" 
               value="{{ $requisition->zone }}">
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="region" class="form-label">Region</label>
        <input type="text" class="form-control" id="region" name="region" 
               value="{{ $requisition->region }}">
    </div>
    
    <div class="col-md-4 mb-3">
        <label for="territory" class="form-label">Territory</label>
        <input type="text" class="form-control" id="territory" name="territory" 
               value="{{ $requisition->territory }}">
    </div>
</div>