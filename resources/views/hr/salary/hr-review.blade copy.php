@extends('layouts.guest')

@section('content')

<div class="container-fluid">

	<div class="card mb-3">
		<div class="card-body">

			<div class="row align-items-end">

				<div class="col-md-3">
					<label class="form-label">Select Month</label>
					<input type="month" id="monthYear" class="form-control">
				</div>

				<div class="col-md-3">
					<button id="releaseBtn" class="btn btn-success btn-sm" onclick="releaseSelected()">
						Release Selected
					</button>
					<button id="verifyBtn" class="btn btn-primary btn-sm" onclick="verifyPayments()">
						Verify Payment
					</button>
				</div>

			</div>

		</div>
	</div>

	<div class="card">

		<div class="card-header">

			<ul class="nav nav-tabs" id="salaryTabs">

				<li class="nav-item">
					<a class="nav-link active" data-type="pending">Pending</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-type="hold">Hold</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-type="release">Released</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-type="payment">Verify Payment</a>
				</li>
			</ul>

		</div>

		<div class="card-body">

			<div class="table-responsive">

				<table class="table table-bordered">

					<thead>

						<tr>
							<th><input type="checkbox" id="selectAll"></th>
							<th>Code</th>
							<th>Name</th>
							<th>Paid Days</th>
							<th>Extra</th>
							<th>Deduction</th>
							<th>Arrear</th>
							<th>Net Pay</th>
							<th>Agreement</th>
							<th>Courier</th>
							<th>File</th>
							<th>Status</th>
							<th>Remark</th>
							<th>Action</th>
						</tr>

					</thead>

					<tbody id="reviewTable">

						<tr>
							<td colspan="5" class="text-center">Select month</td>
						</tr>

					</tbody>

				</table>

			</div>

		</div>

	</div>

</div>

<div class="modal fade" id="remarkModal">

	<div class="modal-dialog">

		<div class="modal-content">

			<div class="modal-header">
				<h5 class="modal-title">Enter Remark</h5>
			</div>

			<div class="modal-body">

				<input type="hidden" id="modalSalaryId">
				<input type="hidden" id="modalAction">

				<div class="mb-2">
					<label class="form-label">Reason</label>
					<select id="modalReason" class="form-control">
						<option value="">Select reason</option>
					</select>
				</div>

				<div class="mt-2">
					<label class="form-label">Comment (optional)</label>
					<textarea id="modalRemark"
						class="form-control"
						placeholder="Additional remark"></textarea>
				</div>

			</div>

			<div class="modal-footer">

				<button class="btn btn-secondary"
					data-bs-dismiss="modal">
					Cancel
				</button>

				<button class="btn btn-primary"
					onclick="submitRemark()">
					Submit
				</button>

			</div>

		</div>

	</div>

</div>

@endsection
@push('scripts')

<script>
	let currentTab = 'pending';
	let currentMonth = null;
	let currentYear = null;

	$('#monthYear').change(function() {

		let val = $(this).val();

		[currentYear, currentMonth] = val.split('-');

		loadReview();

	});


	$('.nav-link').click(function() {

		$('.nav-link').removeClass('active');
		$(this).addClass('active');
		currentTab = $(this).data('type');
		$('.salaryCheck').prop('checked', false);
		$('#selectAll').prop('checked', false);
		loadReview();
	});


	// ADD HERE
	$('#selectAll').on('change', function() {
		$('.salaryCheck').prop('checked', $(this).prop('checked'));
	});

	$(document).on('change', '.salaryCheck', function() {
		$('#selectAll').prop(
			'checked',
			$('.salaryCheck:checked').length === $('.salaryCheck').length
		);
	});


	function loadReview() {
		if (currentTab === 'pending') {
			$('#releaseBtn').show();
			$('#selectAll').show();
		} else {
			$('#releaseBtn').hide();
			$('#selectAll').hide();
		}

		if (currentTab === 'payment') {
			$('#releaseBtn').hide();
			$('#selectAll').show();
		}
		if (!currentMonth) return;
		$.post("{{route('salary.hr.review.list')}}", {
			_token: '{{csrf_token()}}',
			month: currentMonth,
			year: currentYear,
			type: currentTab
		}, function(data) {
			renderTable(data);
		});
	}


	function renderTable(records) {

		$('#selectAll').prop('checked', false);

		if (records.length == 0) {
			$('#reviewTable').html(`<tr><td colspan="14" class="text-center">No records</td></tr>`);
			return;
		}

		let html = '';

		records.forEach(r => {
			let checkbox = '';

			if (currentTab == 'pending' || currentTab == 'payment') {
				checkbox = `
				<input type="checkbox" 
					class="salaryCheck" 
					value="${r.id}"
					data-account="${r.candidate.bank_account_no ?? ''}"
					data-amount="${r.total_payable}">
				`;
			}

			let paidDays = r.paid_days ?? 0;
			let extra = r.extra_amount ?? 0;
			let deduction = r.deduction_amount ?? 0;
			let arrear = r.arrear_amount ?? 0;

			let action = '';

			if (currentTab == 'pending') {
				if (r.agreement_signed && r.courier_received && r.file_created) {

					action = `
									<button class="btn btn-warning btn-sm"
										onclick="updatePayment(${r.id},'hold')">
										Hold
									</button>
									<span class="badge bg-success ms-1">Ready</span>
								`;

				} else {

					action = `<button class="btn btn-danger btn-sm"
									onclick="updatePayment(${r.id},'hold')">
									Put On Hold
								</button>`;

				}

			}

			if (currentTab == 'hold') {
				action = `<button class="btn btn-success btn-sm" onclick="updatePayment(${r.id},'release')">Release</button>`;
			}

			if (currentTab == 'release') {
				action = `<a class="btn btn-primary btn-sm" href="/hr/salary/payslip/${r.id}">Payslip</a>`;
			}

			let agreement = r.agreement_signed ?
				'<span class="badge bg-success">✔</span>' :
				'<span class="badge bg-danger">✖</span>';

			let courier = r.courier_received ?
				'<span class="badge bg-success">✔</span>' :
				'<span class="badge bg-danger">✖</span>';

			let file = r.file_created ?
				'<span class="badge bg-success">✔</span>' :
				'<span class="badge bg-danger">✖</span>';

			let remark = '';

			if (currentTab == 'hold') {
				remark = r.hr_hold_remark ?? '';
			}

			if (currentTab == 'release') {
				remark = r.hr_release_remark ?? '';
			}

			let badge = 'bg-secondary';

			if (r.payment_instruction === 'pending') badge = 'bg-warning';
			if (r.payment_instruction === 'hold') badge = 'bg-danger';
			if (r.payment_instruction === 'release') badge = 'bg-success';
			let finalPay = Number(r.total_payable ?? r.net_pay ?? 0);

			let extraClass = extra > 0 ? 'text-success fw-bold' : '';
			let deductionClass = deduction > 0 ? 'text-danger fw-bold' : '';
			let arrearClass = arrear > 0 ? 'text-primary fw-bold' : '';
			html += `
				<tr>
				<td>${checkbox}</td>
				<td>${r.candidate.candidate_code}</td>
				<td>${r.candidate.candidate_name}</td>

				<td>${paidDays}</td>
				<td class="${extraClass}">₹ ${Number(extra).toLocaleString('en-IN')}</td>
<td class="${deductionClass}">₹ ${Number(deduction).toLocaleString('en-IN')}</td>
<td class="${arrearClass}">₹ ${Number(arrear).toLocaleString('en-IN')}</td>

				<td>₹ ${finalPay.toLocaleString('en-IN')}</td>
				<td>${agreement}</td>
				<td>${courier}</td>
				<td>${file}</td>
				<td>
				<span class="badge ${badge}">
				${r.payment_instruction}
				</span>
				</td>
				<td>${remark}</td>
				<td>${action}</td>

				</tr>

				`;

		});

		$('#reviewTable').html(html);

	}


	function releaseSelected() {

		let ids = [];

		$('.salaryCheck:checked').each(function() {
			ids.push($(this).val());
		});

		if (ids.length == 0) {
			toastr.error("Select parties");
			return;
		}

		if (!confirm("Release selected salaries?")) {
			return;
		}

		// 🔥 DISABLE BUTTON HERE
		$('#releaseBtn').prop('disabled', true).text('Processing...');

		$.ajax({
			url: "{{route('salary.release.batch')}}",
			method: "POST",
			data: {
				_token: '{{csrf_token()}}',
				salary_ids: ids,
				month: currentMonth,
				year: currentYear
			},
			success: function(res) {
				toastr.success(res.message);
				loadReview();
			},
			error: function(xhr) {
				let msg = "Something went wrong";

				if (xhr.responseJSON && xhr.responseJSON.message) {
					msg = xhr.responseJSON.message;
				}

				toastr.error(msg);
			},
			complete: function() {
				// 🔥 RE-ENABLE BUTTON ALWAYS (success OR error)
				$('#releaseBtn').prop('disabled', false).text('Release Selected');
			}
		});
	}

	function verifyPayments() {

		let payments = [];

		$('.salaryCheck:checked').each(function() {

			let account = $(this).data('account');
			let amount = parseFloat($(this).data('amount'));
			let salaryId = $(this).val();

			if (!account || !amount) return;

			payments.push({
				beneficiary_account_number: account,
				amount: amount,
				date: currentYear + '-' + currentMonth + '-01',
				source: "erp_system",
				source_reference: salaryId
			});
		});

		if (payments.length === 0) {
			toastr.error("Select valid records");
			return;
		}

		$('#verifyBtn').prop('disabled', true).text('Verifying...');

		$.ajax({
			url: "{{ route('salary.verify.payment') }}",
			method: "POST",
			data: {
				_token: '{{csrf_token()}}',
				payments: payments
			},
			success: function(res) {
				toastr.success("Matched: " + res.summary.matched);
				loadReview();
			},
			error: function(xhr) {
				toastr.error(xhr.responseJSON?.message || "Error");
			},
			complete: function() {
				$('#verifyBtn').prop('disabled', false).text('Verify Payment');
			}
		});
	}

	function updatePayment(id, action) {

		$('#modalSalaryId').val(id);
		$('#modalAction').val(action);
		$('#modalRemark').val('');

		let options = '';

		if (action === 'hold') {
			options = `
		<option value="">Select reason</option>
		<option value="Agreement not signed">Agreement not signed</option>
		<option value="Courier not received">Courier not received</option>
		<option value="File not created">File not created</option>
		`;
		}

		if (action === 'release') {
			options = `
		<option value="">Select reason</option>
		<option value="Agreement uploaded">Agreement uploaded</option>
		<option value="Courier dispatched">Courier dispatched</option>
		<option value="Courier received">Courier received</option>
		<option value="File created">File created</option>
		<option value="Verified by HR">Verified by HR</option>
		`;
		}

		$('#modalReason').html(options);

		$('#remarkModal').modal('show');
	}

	function submitRemark() {

		let id = $('#modalSalaryId').val();
		let action = $('#modalAction').val();
		let reason = $('#modalReason').val();
		let comment = $('#modalRemark').val();

		if (!reason) {
			toastr.error("Please select reason");
			return;
		}

		let remark = reason;

		if (comment) {
			remark = reason + " - " + comment;
		}

		$.ajax({
			url: "{{route('salary.toggle.payment')}}",
			method: "POST",
			data: {
				_token: '{{csrf_token()}}',
				salary_id: id,
				action: action,
				remark: remark
			},
			success: function(res) {
				toastr.success(res.message);
				$('#remarkModal').modal('hide');
				loadReview();
			},
			error: function(xhr) {
				let msg = "Something went wrong";

				if (xhr.responseJSON && xhr.responseJSON.message) {
					msg = xhr.responseJSON.message;
				}

				toastr.error(msg);
			}
		});
	}
</script>
@endpush


    Route::post('verify-payment', [SalaryController::class, 'verifyPayment'])
    ->name('salary.verify.payment');


	  if ($request->type == 'payment') {
            $query->where('payment_instruction', 'release')
                ->where('payment_status', 'pending');
        }