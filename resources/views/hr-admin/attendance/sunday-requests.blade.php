@extends('layouts.guest')

@section('content')

<div class="container-fluid">

    <h4>Sunday Work Requests</h4>

    <div class="card">
        <div class="card-body">

            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Candidate</th>
                        <th>Code</th>
                        <th>Sunday Date</th>
                        <th>Remark</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($requests as $req)

                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>{{ $req->candidate_name }}</td>

                        <td>{{ $req->candidate_code }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($req->sunday_date)->format('d M Y') }}
                        </td>

                        <td>{{ $req->remark }}</td>

                        <td>{{ $req->requested_by_name }}</td>

                        <td>
                            <span class="badge bg-{{ 
                                $req->status == 'approved' ? 'success' :
                                ($req->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>

                        <td>

                            @if($req->status == 'pending')

                                <button onclick="updateSunday({{ $req->id }}, 'approved')"
                                    class="btn btn-success btn-sm">
                                    Approve
                                </button>

                                <button onclick="updateSunday({{ $req->id }}, 'rejected')"
                                    class="btn btn-danger btn-sm">
                                    Reject
                                </button>

                            @else
                                -
                            @endif

                        </td>
                    </tr>

                    @empty

                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No Sunday work requests found
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>

        </div>
    </div>

</div>

@endsection


@push('scripts')
<script>

function updateSunday(id, status)
{
    if(!confirm("Are you sure to " + status + " this request?")) return;

    $.post("{{ route('attendance.sunday.update') }}", {

        _token: "{{ csrf_token() }}",
        id: id,
        status: status

    }, function(res){

        if(res.success)
        {
            toastr.success("Request " + status + " successfully");
            location.reload();
        }
        else
        {
            toastr.error("Failed to update");
        }

    }).fail(function(){

        toastr.error("Server error");

    });
}

</script>
@endpush