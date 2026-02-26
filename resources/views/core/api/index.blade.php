@extends('layouts.guest')
@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Core APIs</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pages</a></li>
                        <li class="breadcrumb-item active">API List</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search for..."
                                    id="customSearchBox">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-auto ms-auto">
                            <div class="hstack gap-2">
                                <button class="btn btn-soft-success" id="import-actions"><i class=" ri-download-cloud-2-line"></i> Import</button>
                                <button type="button" class="btn btn-success add-btn" id="syncAPI"><i
                                        class="ri-restart-line align-bottom me-1"></i> Sync APIs
                                </button>

                            </div>
                        </div>
                    </div>
                </div>

                <!--end card-body-->
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <div id="elmLoader" class="d-none loader-overlay">
                        <div class="loader-box text-center">

                            <div class="spinner-border text-primary mb-3"
                                style="width: 3rem; height: 3rem;">
                            </div>

                            <div class="fw-bold">
                                Importing API data, please wait...
                            </div>

                        </div>
                    </div>

                    <div class="table-responsive table-card mb-4">
                        <table class="table align-middle table-nowrap mb-0" id="data-table">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th></th>
                                    <th>S.No</th>
                                    <th>API Name</th>
                                    <th>API End Point</th>
                                    <th>Parameter</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($api_list as $api)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="apis" id="apis_{{$loop->iteration}}"
                                            value="{{$api->api_end_point}}">
                                    </td>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td>{{$api->api_name}}</td>
                                    <td>{{$api->api_end_point}}</td>
                                    <td>{{$api->parameters}}</td>
                                    <td>{{$api->description}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                    <!--end table-->

                    <!-- No Results Message -->
                    <div class="noresult d-none" id="noResult">
                        <div class="text-center">
                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                colors="primary:#121331,secondary:#08a88a"
                                style="width:75px;height:75px"></lord-icon>
                            <h5 class="mt-2">Sorry! No Result Found</h5>

                        </div>
                    </div>

                    <!-- Custom Pagination -->
                    <div class="d-flex justify-content-end mt-3">
                        <!-- Page Length Selector -->
                        <select id="pageLengthSelector" class="form-select form-select-sm me-3"
                            style="width: auto;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <div class="pagination-wrap hstack gap-2" style="display: flex;">
                            <ul class="pagination listjs-pagination mb-0"></ul>
                        </div>
                    </div>
                </div>
                <!--end card-body-->
            </div>
            <!--end card-->
        </div>
        <!--end col-->
    </div>

</div>

@endsection
@push('scripts')

<script>
    $(document).on('click', '#syncAPI', function() {
        $.ajax({
            url: "{{route('core_api_sync')}}",
            method: 'GET',
            processData: false,
            dataType: 'json',
            contentType: false,
            beforeSend: function() {
                $("#elmLoader").removeClass('d-none');
                // FORCE UI repaint
                $("body").css("cursor", "wait");
            },
            complete: function() {

                $("#elmLoader").addClass('d-none');
                $("body").css("cursor", "default");

            },
            success: function(data) {
                if (data.status == 200) {
                    $("#elmLoader").addClass('d-none');
                    toastr.success(data.msg);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    $("#elmLoader").addClass('d-none');
                    toastr.error(data.msg);
                }
            }
        });

    });
    // Event listener for checkboxes to show/hide import button
    $('#data-table').on('change', 'input[name="apis"]', function() {
        // Check if any checkbox is checked
        var anyChecked = $('#data-table input[name="apis"]:checked').length > 0;

        // Toggle import button based on checkbox selection
        $('#import-actions').toggle(anyChecked);
    });
    // Initially hide the import button
    $('#import-actions').hide();

    $(document).on('click', '#import-actions', function() {
        var api_end_points = [];
        $("input[name='apis']:checked").each(function() {
            api_end_points.push($(this).val());
        });
        if (api_end_points.length === 0) {
            alert('Select at least one API');
            return;
        }
        if (!confirm('Import selected API data?')) return;
        $.ajax({
            url: "{{ route('importAPISData') }}",
            type: 'POST',
            data: {
                api_end_points: api_end_points,
                _token: "{{ csrf_token() }}"
            },
            beforeSend: function() {
                $("#elmLoader").removeClass('d-none');
                $("body").css("cursor", "wait");
            },
            complete: function() {
                $("#elmLoader").addClass('d-none');
                $("body").css("cursor", "default");
            },
            success: function(data) {
                toastr.success(data.msg);
            },

            error: function() {
                toastr.error("Import failed");
            }
        });
    });

    $(document).ready(function() {
        function toggleNoResult() {
            var rowCount = $("#data-table tbody tr").length;
            if (rowCount === 0) {
                $("#noResult").removeClass("d-none");
            } else {
                $("#noResult").addClass("d-none");
            }
        }
        toggleNoResult();
    });
</script>
@endpush