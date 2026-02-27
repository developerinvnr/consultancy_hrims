<div class="table-responsive">
	<table class="table table-hover">

		<thead>
			<tr>
				<th>ID</th>
				<th>Candidate</th>
				<th>Email</th>
				<th>Type</th>
				<th>Status</th>
				<th>Date</th>
				<th>Action</th>
			</tr>
		</thead>

		<tbody>

			@forelse($requisitions as $req)

			<tr>

				<td>{{ $req->requisition_id }}</td>

				<td>{{ $req->candidate_name }}</td>

				<td>{{ $req->candidate_email }}</td>

				<td>{{ $req->requisition_type }}</td>

				<td>
					@php
					$statusColors = [
					'Active' => 'success',
					'Inactive' => 'danger',
					'Pending HR Verification' => 'warning',
					'Pending Approval' => 'info',
					'Approved' => 'success',
					'Rejected' => 'danger',
					'Processed' => 'primary'
					];

					$color = $statusColors[$req->candidate->candidate_status ?? $req->status] ?? 'secondary';
					@endphp

					<span class="badge bg-{{ $color }}">
						{{ $req->candidate->candidate_status ?? $req->status }}
					</span>
				</td>

				<td>{{ $req->created_at->format('d-M-Y') }}</td>

				<td>
					@php
					$viewRoute = auth()->user()->hasRole('hr_admin')
					? route('hr-admin.applications.show', $req->id)
					: route('requisitions.show', $req->id);
					@endphp

					<a href="{{ $viewRoute }}" class="btn btn-sm btn-outline-primary">
						View
					</a>
				</td>

			</tr>

			@empty

			<tr>
				<td colspan="7" class="text-center">
					No records found
				</td>
			</tr>

			@endforelse

		</tbody>

	</table>

	@php
	$pageName = $requisitions->getPageName();
	@endphp

	{{ $requisitions->appends([$pageName => request($pageName)])->links('pagination::bootstrap-5') }}

</div>