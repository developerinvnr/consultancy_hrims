<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Party Type</label>
            <input type="text" class="form-control" value="{{ $candidate->requisition_type }}" readonly disabled>
            <small class="text-muted">Party type cannot be changed</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Party Code</label>
            <input type="text" class="form-control" value="{{ $candidate->candidate_code }}" readonly disabled>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Work Location HQ</label>
            <input type="text" name="work_location_hq" class="form-control"
                value="{{ old('work_location_hq', $candidate->work_location_hq) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Work State</label>
            <select name="state_work_location" class="form-select" required>
                <option value="">-- Select Work State --</option>
                @foreach($states as $state)
                <option value="{{ $state->id }}"
                    {{ $candidate->state_work_location == $state->id ? 'selected' : '' }}>
                    {{ $state->state_name }}
                </option>
                @endforeach
            </select>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Function</label>
            <select name="function_id" class="form-control">
                <option value="">Select</option>
                @foreach($functions ?? [] as $function)
                <option value="{{ $function->id }}" {{ $candidate->function_id == $function->id ? 'selected' : '' }}>
                    {{ $function->function_name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Department</label>
            <select name="department_id" class="form-control">
                <option value="">Select</option>
                @foreach($departments ?? [] as $department)
                <option value="{{ $department->id }}" {{ $candidate->department_id == $department->id ? 'selected' : '' }}>
                    {{ $department->department_name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Vertical</label>
            <select name="vertical_id" class="form-control">
                <option value="">Select</option>
                @foreach($verticals ?? [] as $vertical)
                <option value="{{ $vertical->id }}" {{ $candidate->vertical_id == $vertical->id ? 'selected' : '' }}>
                    {{ $vertical->vertical_name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Contract Start Date</label>
            <input type="date" name="contract_start_date" class="form-control"
                value="{{ old('contract_start_date', $candidate->contract_start_date ? $candidate->contract_start_date->format('Y-m-d') : '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Contract End Date</label>
            <input type="date" name="contract_end_date" class="form-control"
                value="{{ old('contract_end_date', $candidate->contract_end_date ? $candidate->contract_end_date->format('Y-m-d') : '') }}">
        </div>
    </div>
</div>

<div class="row">
@if($candidate->sub_department)
    {{-- Sub Department --}}
    <div class="col-md-4">
            <div class="form-group">
                <label>Sub Department</label>
                <select name="sub_department" class="form-control">
                    <option value="">Select</option>
                    @foreach($subDepartments ?? [] as $subDept)
                        <option value="{{ $subDept->id }}"
                            {{ $candidate->sub_department == $subDept->id ? 'selected' : '' }}>
                            {{ $subDept->sub_department_name  }}
                        </option>
                    @endforeach
                </select>
            </div>
    </div>
@endif

    {{-- Business Unit --}}
    @if($candidate->business_unit)
    <div class="col-md-4">
        <div class="form-group">
            <label>Business Unit</label>
            <select name="business_unit" class="form-control">
                <option value="">Select</option>
                @foreach($businessUnits ?? [] as $bu)
                    <option value="{{ $bu->id }}"
                        {{ $candidate->business_unit == $bu->id ? 'selected' : '' }}>
                        {{ $bu->business_unit_name  }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif


    {{-- Zone --}}
    @if($candidate->zone)
    <div class="col-md-4">
        <div class="form-group">
            <label>Zone</label>
            <select name="zone" class="form-control">
                <option value="">Select</option>
                @foreach($zones ?? [] as $zone)
                    <option value="{{ $zone->id }}"
                        {{ $candidate->zone == $zone->id ? 'selected' : '' }}>
                        {{ $zone->zone_name  }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif


    {{-- Region --}}
    @if($candidate->region)
    <div class="col-md-4">
        <div class="form-group">
            <label>Region</label>
            <select name="region" class="form-control">
                <option value="">Select</option>
                @foreach($regions ?? [] as $region)
                    <option value="{{ $region->id }}"
                        {{ $candidate->region == $region->id ? 'selected' : '' }}>
                        {{ $region->region_name  }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif


    {{-- Territory --}}
    @if($candidate->territory)
    <div class="col-md-4">
        <div class="form-group">
            <label>Territory</label>
            <select name="territory" class="form-control">
                <option value="">Select</option>
                @foreach($territories ?? [] as $territory)
                    <option value="{{ $territory->id }}"
                        {{ $candidate->territory == $territory->id ? 'selected' : '' }}>
                        {{ $territory->territory_name  }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Monthly Remuneration (₹)</label>
            <input type="number" name="remuneration_per_month" class="form-control" step="0.01"
                value="{{ old('remuneration_per_month', $candidate->remuneration_per_month) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Team ID</label>
            <input type="text" name="team_id" class="form-control"
                value="{{ old('team_id', $candidate->team_id) }}">
        </div>
    </div>
</div>