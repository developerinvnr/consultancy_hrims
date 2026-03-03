<div class="card mb-4 shadow-sm">
	<div class="card-body">
		<form method="GET" action="{{ $route }}"
			id="remunerationReportForm"
			class="row g-3 align-items-end">

			{{-- Year FIRST --}}
			@php
			$currentMonth = date('n');
			$currentYear = date('Y');

			if ($currentMonth >= 4) {
			$fyStart = $currentYear;
			} else {
			$fyStart = $currentYear - 1;
			}

			$startYear = $fyStart - 2;
			$endYear = $fyStart;
			@endphp

			<div class="col-md-2">
				<label class="form-label form-label-sm">Financial Year</label>
				<select name="financial_year" class="form-select form-select-sm">
					@for($y = $startYear; $y <= $endYear; $y++)
						@php $fy=$y . '-' . ($y + 1); @endphp
						<option value="{{ $fy }}"
						{{ ($financialYear ?? '') == $fy ? 'selected' : '' }}>
						{{ $fy }}
						</option>
						@endfor
				</select>
			</div>

			{{-- Month AFTER --}}
			<div class="col-md-2">
				<label class="form-label form-label-sm">
					Month <small class="text-muted">(FY Based)</small>
				</label>

				@php
				$fyMonths = [4,5,6,7,8,9,10,11,12,1,2,3];
				@endphp

				<select name="month" class="form-select form-select-sm">
					@foreach($fyMonths as $m)
					<option value="{{ $m }}"
						{{ request('month', date('n')) == $m ? 'selected' : '' }}>
						{{ \Carbon\Carbon::create()->month($m)->format('F') }}
					</option>
					@endforeach
				</select>
			</div>

			<div class="col-md-3">
				<label class="form-label form-label-sm">Department</label>
				<select name="department_id" class="form-select form-select-sm">
					<option value="">All Departments</option>
					@foreach($departments as $dept)
					<option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
						{{ $dept->department_name }}
					</option>
					@endforeach
				</select>
			</div>

			<div class="col-md-2">
				<label class="form-label form-label-sm">Requisition Type</label>
				<select name="requisition_type" class="form-select form-select-sm">
					<option value="">All Types</option>
					<option value="TFA" {{ request('requisition_type') == 'TFA' ? 'selected' : '' }}>TFA</option>
					<option value="CB" {{ request('requisition_type') == 'CB' ? 'selected' : '' }}>CB</option>
					<option value="Contractual" {{ request('requisition_type') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
				</select>
			</div>

			<div class="col-md-3 d-flex gap-2">
				<button type="submit" class="btn btn-sm btn-primary w-50">
					<i class="ri-filter-line"></i> Generate
				</button>

				<button type="button" class="btn btn-sm btn-success w-50"
					onclick="exportRemunerationReport()">
					<i class="ri-file-excel-2-line"></i> Export
				</button>
			</div>

		</form>
	</div>
</div>

<script>
	function exportRemunerationReport() {
		let form = $('#remunerationReportForm');
		let params = form.serialize();
		window.location.href = "{{ route('reports.remuneration.export') }}?" + params;
	}
</script>