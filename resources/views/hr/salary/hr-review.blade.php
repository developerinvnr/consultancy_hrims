@extends('layouts.guest')

@section('content')

<div class="container-fluid">

	<div class="card mb-3">
		<div class="card-body">

			<div class="row">

				<div class="col-md-3">
					<label>Select Month</label>
					<input type="month" id="monthYear" class="form-control">
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
					<a class="nav-link" data-type="hold">Held</a>
				</li>

				<li class="nav-item">
					<a class="nav-link" data-type="release">Released</a>
				</li>

			</ul>

		</div>

		<div class="card-body">

			<div class="table-responsive">

				<table class="table table-bordered">

					<thead>

						<tr>
							<th>Code</th>
							<th>Name</th>
							<th>Net Pay</th>
							<th>Status</th>
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

		loadReview();

	});


	function loadReview() {

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

		if (records.length == 0) {

			$('#reviewTable').html(`<tr>
<td colspan="5" class="text-center">No records</td>
</tr>`);

			return;

		}

		let html = '';

		records.forEach(r => {

			let action = '';

			if (currentTab == 'pending') {

				action = `
<button class="btn btn-success btn-sm"
onclick="updatePayment(${r.id},'release')">
Release
</button>

<button class="btn btn-danger btn-sm"
onclick="updatePayment(${r.id},'hold')">
Hold
</button>
`;

			}

			if (currentTab == 'hold') {

				action = `
<button class="btn btn-success btn-sm"
onclick="updatePayment(${r.id},'release')">
Release
</button>
`;

			}

			if (currentTab == 'release') {

				action = `
<a class="btn btn-primary btn-sm"
href="/hr/salary/payslip/${r.id}">
Payslip
</a>
`;

			}

			html += `

<tr>

<td>${r.candidate.candidate_code}</td>

<td>${r.candidate.candidate_name}</td>

<td>₹ ${Number(r.net_pay).toLocaleString('en-IN')}</td>

<td>
<span class="badge bg-info">
${r.payment_instruction}
</span>
</td>

<td>${action}</td>

</tr>

`;

		});

		$('#reviewTable').html(html);

	}



	function updatePayment(id, action) {

		let remark = prompt("Enter remark");

		if (!remark) return;

		$.post("{{route('salary.toggle.payment')}}", {

			_token: '{{csrf_token()}}',
			salary_id: id,
			action: action,
			remark: remark

		}, function(res) {

			toastr.success(res.message);

			loadReview();

		});

	}
</script>
@endpush