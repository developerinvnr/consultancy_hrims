@extends('layouts.guest')

@section('content')

<div class="container-fluid">

	<div class="card mb-3">

		<div class="card-body">

			<div class="row align-items-end">

				<div class="col-md-3">

					<label class="form-label form-label-sm">Select Month</label>

					<input type="month"
						id="monthYear"
						class="form-control form-control-sm">

				</div>

				<div class="col-md-3">
					<label class="form-label form-label-sm">Requisition Type</label>
					<select id="requisitionFilter" class="form-control form-control-sm">
						<option value="">All</option>
						<option value="Contractual">Contractual</option>
						<option value="CB">CB</option>
						<option value="TFA">TFA</option>
					</select>
				</div>

				<div class="col-md-3" id="exportFilterBlock" style="display:none">

					<label class="form-label form-label-sm">Export Status</label>

					<select id="exportFilter"
						class="form-select form-select-sm">

						<option value="">All</option>
						<option value="exported">Exported</option>
						<option value="not_exported">Not Exported</option>

					</select>

				</div>

				<div class="col-md-3">

					<button id="approveBtn"
						class="btn btn-success btn-sm">

						Approve Selected

					</button>

					<button id="exportBtn"
						class="btn btn-primary btn-sm"
						style="display:none">

						Export Selected

					</button>

					<button id="syncBtn"
						class="btn btn-warning btn-sm"
						style="display:none">
						Check Payment Status
					</button>

				</div>

			</div>

		</div>

	</div>



	<div class="card">

		<div class="card-header">

			<ul class="nav nav-tabs">

				<li class="nav-item">
					<a class="nav-link active" data-tab="pending">
						Pending
					</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-tab="instruction">
						Payment Instruction
					</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-tab="confirmed">
						Confirmed Payment
					</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-tab="unsettled">
						Unsettled Payment
					</a>
				</li>

			</ul>

		</div>


		<div class="card-body">
			<div id="tableLoader" class="text-center py-4" style="display:none">
				<div class="spinner-border text-primary"></div>
				<div>Loading records...</div>
			</div>

			<table class="table table-bordered">

				<thead>

					<tr>

						<th>

							<input type="checkbox"
								id="selectAll">

						</th>

						<th>Code</th>

						<th>Name</th>

						<th>Type</th>

						<th>Month</th>

						<th>Net Pay</th>

						<th>Status</th>

						<th>UTR No</th>
						<th>Payment Date</th>
						<th>Remark</th>

					</tr>

				</thead>

				<tbody id="workflowTable">

					<tr>

						<td colspan="10"
							class="text-center">

							Select month

						</td>

					</tr>

				</tbody>

			</table>

		</div>

	</div>

</div>

@endsection

@push('scripts')

<script>
	let currentTab = 'pending';

	let currentMonth;
	let currentYear;

	let today = new Date();

	currentMonth = String(today.getMonth() + 1).padStart(2, '0');

	currentYear = today.getFullYear();

	$('#monthYear').val(currentYear + '-' + currentMonth);

	loadWorkflow();



	$('#monthYear').change(function() {

		let val = $(this).val();

		[currentYear, currentMonth] = val.split('-');

		loadWorkflow();

	});

	$('#exportFilter').change(function() {

		loadWorkflow();

	});



	$('.nav-link').click(function() {

		$('.nav-link').removeClass('active');

		$(this).addClass('active');

		currentTab = $(this).data('tab');


		if (currentTab == 'pending') {

			$('#approveBtn').show();
			$('#exportBtn').hide();
			$('#syncBtn').hide();
			$('#exportFilterBlock').hide();

		} else if (currentTab == 'instruction') {

			$('#approveBtn').hide();
			$('#exportBtn').show();
			$('#syncBtn').show();
			$('#exportFilterBlock').show();

		} else {

			$('#approveBtn').hide();
			$('#exportBtn').hide();
			$('#syncBtn').hide();
			$('#exportFilterBlock').hide();
			$('#exportFilter').val('');
		}

		loadWorkflow();

	});

	$('#syncBtn').click(function() {

		$('#tableLoader').show();

		$.post(
			"{{ route('payment.workflow.sync') }}", {
				_token: '{{ csrf_token() }}'
			},

			function(response) {

				$('#tableLoader').hide();

				alert(response.message);

				loadWorkflow();
			}

		).fail(function(xhr) {

			$('#tableLoader').hide();

			alert('Sync failed. Please check logs.');

			console.log(xhr.responseText);
		});

	});



	$('#selectAll').change(function() {

		$('.rowCheck').prop('checked', $(this).prop('checked'));

	});

	$('#requisitionFilter').change(function() {

		loadWorkflow();

	});



	function loadWorkflow() {

		if (!currentMonth) return;

		$('#selectAll').prop('checked', false);

		$('#tableLoader').show();
		$('#workflowTable').html('');

		$.get("{{ route('payment.workflow.list') }}", {

			tab: currentTab,
			month: currentMonth,
			year: currentYear,
			export_status: $('#exportFilter').val(),
			requisition_type: $('#requisitionFilter').val()

		}, function(data) {

			renderTable(data);

		}).always(function() {

			$('#tableLoader').hide();

		});
	}



	function renderTable(records) {

		if (records.length == 0) {

			$('#workflowTable').html(
				`<tr>
                <td colspan="10" class="text-center">
                    No records
                </td>
            </tr>`
			);

			return;
		}

		let html = '';

		records.forEach(r => {

			let statusBadge = '';

			if (r.payment_status == 'paid') {
				statusBadge = 'bg-success';
			} else if (r.payment_status == 'failed') {
				statusBadge = 'bg-danger';
			} else if (r.payment_status == 'exported') {
				statusBadge = 'bg-info';
			} else if (r.payment_status == 'approved') {
				statusBadge = 'bg-primary';
			} else {
				statusBadge = 'bg-warning';
			}

			html += `
        <tr>

            <td>
                ${
                    (currentTab=='pending' && r.payment_status=='pending') ||
                    (currentTab=='instruction' && r.payment_status=='approved')
                    ? `<input type="checkbox" class="rowCheck" value="${r.id}">`
                    : ''
                }
            </td>

            <td>${r.candidate_code}</td>

            <td>${r.candidate_name}</td>

            <td>${r.requisition_type ?? '-'}</td>

            <td>${r.month}</td>

            <td>₹ ${Number(r.net_pay).toLocaleString('en-IN')}</td>

            <td>
                <span class="badge ${statusBadge}">
                    ${r.payment_status}
                </span>
            </td>

            <td>${r.utr_number ?? '-'}</td>

            <td>${r.payment_date ?? '-'}</td>

            <td>${r.verification_remark ?? '-'}</td>

        </tr>
        `;
		});

		$('#workflowTable').html(html);
	}


	$('#approveBtn').click(function() {
		$('#tableLoader').show();
		let ids = [];

		$('.rowCheck:checked').each(function() {

			ids.push($(this).val());

		});


		$.post("{{ route('payment.workflow.approve') }}", {

			ids: ids,

			_token: '{{csrf_token()}}'

		}, loadWorkflow);

	});




	$('#exportBtn').click(function() {

		let ids = [];

		$('.rowCheck:checked').each(function() {

			ids.push($(this).val());

		});

		if (ids.length === 0) {
			alert('Please select records first');
			return;
		}

		let form = $('<form>', {
			method: 'POST',
			action: "{{ route('payment.workflow.export') }}"
		});

		form.append(
			$('<input>', {
				type: 'hidden',
				name: '_token',
				value: "{{ csrf_token() }}"
			})
		);

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'requisition_type',
				value: $('#requisitionFilter').val()
			})
		);

		ids.forEach(id => {

			form.append(
				$('<input>', {
					type: 'hidden',
					name: 'ids[]',
					value: id
				})
			);

		});

		$('body').append(form);

		form.submit();

		setTimeout(loadWorkflow, 1000);

	});
</script>

@endpush