@extends('layouts.guest')

@section('content')

<div class="container-fluid">

    {{-- Page Title --}}
    <div class="row mb-2">
        <div class="col-12">
            <h5 class="mb-0">Focus Code Master</h5>
            <small class="text-muted">Update focus codes for city / village</small>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">

            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label small">State</label>
                    <select id="state" class="form-select form-select-sm">
                        <option value="">Select</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}">
                                {{ $state->state_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">District</label>
                    <select id="district" class="form-select form-select-sm">
                        <option value="">Select</option>
                    </select>
                </div>

            </div>

        </div>
    </div>

    {{-- Data Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
				<div id="loader" class="text-center py-3 d-none">
					<div class="spinner-border spinner-border-sm text-primary"></div>
					<span class="ms-2 small text-muted">Loading cities...</span>
				</div>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light text-center small">
                        <tr>
                            <th width="60">ID</th>
                            <th>Division</th>
                            <th>City / Village</th>
                            <th>City Code</th>
                            <th width="150">Focus Code</th>
                            <th width="80">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cityTableBody" class="small text-center">
                        <tr>
                            <td colspan="6" class="text-muted py-3">
                                Select district to load data
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@endsection
{{-- AJAX SCRIPT --}}
@push('scripts')


<script>
$(document).ready(function(){

    // STATE CHANGE
    $('#state').change(function(){

        let stateId = $(this).val();

        $('#district').html('<option value="">Select</option>');
        $('#cityTableBody').html('<tr><td colspan="6" class="text-center small">Select district to load data</td></tr>');

        if(!stateId) return;

        $.get("{{ url('/get-districts-by-state') }}",
            { state_id: stateId },
            function(data){

                data.forEach(function(item){
                    $('#district').append(
                        `<option value="${item.id}">${item.district_name}</option>`
                    );
                });

        });

    });


    // DISTRICT CHANGE
    $('#district').change(function(){

        let districtId = $(this).val();

        if(!districtId){
            $('#cityTableBody').html('<tr><td colspan="6" class="text-center small">No Data</td></tr>');
            return;
        }

        // SHOW LOADER
        $('#loader').removeClass('d-none');
        $('#cityTableBody').html('');

        $.get("{{ route('focus.cities') }}",
            { district_id: districtId },
            function(data){

                let rows = '';

                if(data.length === 0){
                    rows = '<tr><td colspan="6" class="text-center small">No Records Found</td></tr>';
                } else {

                    data.forEach(function(city){

                        rows += `
                            <tr class="text-center">
                                <td>${city.id}</td>
                                <td>${city.division_name ?? '-'}</td>
                                <td class="text-start">${city.city_village_name}</td>
                                <td>${city.city_village_code ?? '-'}</td>
                                <td>
                                    <input type="text"
                                        class="form-control form-control-sm"
                                        value="${city.focus_code ?? ''}"
                                        data-id="${city.id}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success save-btn"
                                        data-id="${city.id}">
                                        <i class="ri-save-line"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#cityTableBody').html(rows);

                // HIDE LOADER
                $('#loader').addClass('d-none');

        }).fail(function(){
            $('#loader').addClass('d-none');
            $('#cityTableBody').html('<tr><td colspan="6" class="text-danger text-center small">Failed to load data</td></tr>');
        });

    });


    // SAVE FOCUS CODE
    $(document).on('click','.save-btn',function(){

        let id = $(this).data('id');
        let focusCode = $(`input[data-id="${id}"]`).val();

        let btn = $(this);
        btn.prop('disabled', true);

        $.post("{{ route('focus.update') }}",{
            _token: "{{ csrf_token() }}",
            id: id,
            focus_code: focusCode
        },function(response){

            btn.prop('disabled', false);

            if(response.success){
                btn.removeClass('btn-success')
                   .addClass('btn-primary');

                setTimeout(function(){
                    btn.removeClass('btn-primary')
                       .addClass('btn-success');
                },800);
            }

        }).fail(function(){
            btn.prop('disabled', false);
            alert('Update failed');
        });

    });

});
</script>

@endpush
