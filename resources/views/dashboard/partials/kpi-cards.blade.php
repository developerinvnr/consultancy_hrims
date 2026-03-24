<div class="row g-3 mb-3">

	<div class="col-md-6">
		<div class="card shadow-sm border-0">
			<div class="card-body text-center py-2 bg-light">
				Active
				<h5 class="fw-bold mb-0">
					{{ $attention['active'] }}
				</h5>
			</div>
		</div>
	</div>


	<div class="col-md-6">
		<div class="card shadow-sm border-0">
			<div class="card-body text-center py-2 bg-light">
				In Process
				<h3>
					{{ $attention['in_process'] }}
					@if($attention['delayed_cases'])
					<span class="text-danger">
						(🔴 {{ $attention['delayed_cases'] }} Delayed)
					</span>
					@endif
				</h3>
			</div>
		</div>
	</div>
</div>

<div class="row g-2 mb-2">
	<div class="col-md-4">
		<div class="card border-0 shadow-sm text-center py-2 bg-light">
			Contractual
			<h6>
				{{ $stats['active_by_type']['Contractual'] ?? 0 }}
			</h7>
		</div>
	</div>

	<div class="col-md-4">
		<div class="card border-0 shadow-sm text-center py-2 bg-light">
			TFA
			<h6>
				{{ $stats['active_by_type']['TFA'] ?? 0 }}
			</h6>
		</div>
	</div>


	<div class="col-md-4">
		<div class="card border-0 shadow-sm text-center py-2 bg-light">
			CB
			<h6>
				{{ $stats['active_by_type']['CB'] ?? 0 }}
			</h6>
		</div>
	</div>
</div>
<div class="row g-2 mb-2">
	<div class="col-md-6">
		<div class="card border-0 shadow-sm text-center py-3 py-3 bg-light">
			Avg Time (Req → Active)
			<h6>
				{{ $attention['avg_req_to_active'] }} Days
			</h6>
		</div>
	</div>

	<div class="col-md-6">
		<div class="card border-0 shadow-sm text-center py-3 py-3 bg-light">
			Bottleneck Stage
			<span class="badge bg-danger">
				{{ $attention['bottleneck_stage'] }}
			</span>
		</div>
	</div>
</div>