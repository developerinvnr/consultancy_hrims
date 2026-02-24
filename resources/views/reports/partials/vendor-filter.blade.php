{{-- resources/views/reports/partials/vendor-filter.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <form method="GET" action="{{ route('reports.vendor-details') }}" class="mb-4" id="vendorReportForm">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Requisition Type</label>
                        <select name="requisition_type" class="form-control">
                            <option value="All">All Types</option>
                            <option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                            <option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
                            <option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">All Departments</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Work Location</label>
                        <select name="work_location" class="form-control">
                            <option value="">All Locations</option>
                            @foreach($workLocations ?? [] as $location)
                                <option value="{{ $location }}" {{ request('work_location') == $location ? 'selected' : '' }}>
                                    {{ $location }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Generate Report
                            </button>
                            <button type="button" class="btn btn-success" onclick="exportVendorReport()">
                                <i class="fas fa-file-excel"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Results Table --}}
        @if(isset($candidates))
            @include('reports.partials.vendor-results')
        @endif
    </div>
</div>

<script>
function exportVendorReport() {
    let form = $('#vendorReportForm');
    let params = form.serialize();
    window.location.href = "{{ route('reports.vendor-details.export') }}?" + params;
}
</script>