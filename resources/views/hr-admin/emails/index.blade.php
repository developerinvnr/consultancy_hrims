@extends('layouts.guest')

@section('content')

<div class="container-fluid">

	<div class="card">

		<div class="card-header">
			<h5>Email Logs</h5>
		</div>

		<div class="card-body">

			<div class="table-responsive">

				<table class="table table-bordered table-sm">

					<thead>

						<tr>
							<th>Date</th>
							<th>From</th>
							<th>To</th>
							<th>CC</th>
							<th>Subject</th>
							<th>Action</th>
						</tr>

					</thead>

					<tbody>

						@forelse($emails as $email)

						<tr>

							<td>{{ $email->date }}</td>

							<td>{{ $email->from }}</td>

							<td>{{ $email->to }}</td>

							<td>{{ $email->cc }}</td>

							<td>{{ $email->subject }}</td>

							<td>

								<button
									class="btn btn-sm btn-primary viewEmailBtn"
									data-body='@json($email->body)'>
									View
								</button>

							</td>

						</tr>

						@empty

						<tr>
							<td colspan="6" class="text-center">
								No emails found
							</td>
						</tr>

						@endforelse

					</tbody>

				</table>

			</div>

			{{ $emails->links('pagination::bootstrap-5') }}

		</div>

	</div>

</div>


<!-- Modal -->

<div class="modal fade" id="emailModal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5>Email Body</h5>
			</div>
			<div class="modal-body p-0">
				<iframe id="emailFrame"
						style="width:100%; height:70vh; border:none;">
				</iframe>
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script>
$(document).on('click', '.viewEmailBtn', function () {

    let body = $(this).data('body');

    // ✅ Decode JSON-encoded HTML
    body = JSON.parse('"' + body.replace(/"/g, '\\"') + '"');

    document.getElementById('emailFrame').srcdoc = body;

    $('#emailModal').modal('show');

});
</script>
@endpush