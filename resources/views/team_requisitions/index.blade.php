@extends('layouts.guest')

@section('title', 'Team Requisitions')

@section('content')

<div class="container-fluid">

	<div class="card">

		<div class="card-header">
			<h5 class="mb-0">
				Team Requisitions
			</h5>
		</div>

		<div class="card-body">

			@if($teamRequisitions->count())

			<div class="table-responsive">

				<table class="table table-bordered table-striped">

					<thead>

						<tr>
							<th>#</th>
							<th>Request Code</th>
							<th>Type</th>
							<th>Submitted By</th>
							<th>Submission Date</th>
							<th>Contract Start</th>
							<th>Contract End</th>
							<th>Status</th>
							<th>Action</th>
						</tr>

					</thead>

					<tbody>

						@foreach($teamRequisitions as $index => $req)

						<tr>

							<td>{{ $teamRequisitions->firstItem() + $index }}</td>

							<td>{{ $req->request_code }}</td>

							<td>{{ $req->requisition_type }}</td>

							<td>{{ $req->submitted_by_name }}</td>

							<td>
								{{ \Carbon\Carbon::parse($req->submission_date)->format('d-m-Y') }}
							</td>

							<td>
								{{ optional($req->contract_start_date)
    ? \Carbon\Carbon::parse($req->contract_start_date)->format('d-m-Y')
    : '-' }}
							</td>

							<td>
								{{ optional($req->contract_end_date)
    ? \Carbon\Carbon::parse($req->contract_end_date)->format('d-m-Y')
    : '-' }}
							</td>

							<td>
								<span class="badge bg-info">
									{{ $req->status }}
								</span>
							</td>

							<td>
								<a href="{{ route('requisitions.show', $req->id) }}"
									class="btn btn-sm btn-primary">
									View
								</a>
							</td>

						</tr>
						@endforeach
					</tbody>
				</table>
				<div>
					{{ $teamRequisitions->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
				</div>
			</div>
			@else
			<div class="alert alert-warning">
				No requisitions found for your team.
			</div>
			@endif

		</div>
	</div>
</div>

@endsection