<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Process Approved Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="processForm" action="" method="POST">
				@csrf
				<div class="modal-body">
					<input type="hidden" name="requisition_id" id="modalRequisitionId">

					<div class="row">
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Party</label>
								<input type="text" class="form-control form-control-sm" id="modalCandidateName" readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Current Reporting Manager</label>
								<input type="text" class="form-control form-control-sm" id="currentReporting" readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label class="form-label-sm">Current Reporting ID</label>
								<input type="text" class="form-control form-control-sm" id="currentManagerId" readonly>
							</div>
						</div>
					</div>
					<h6>Change Reporting Manager</h6>
					<div class="mb-3">
						<label for="reporting_manager_employee_id" class="form-label-sm">Reporting Manager *</label>
						<select class="form-select form-select-sm select2-modal" id="reporting_manager_employee_id"
							name="reporting_manager_employee_id" required>
							<option value="">-- Select Reporting Manager --</option>
							<!-- Options will be populated via AJAX -->
						</select>
						<small class="text-muted">Select the reporting manager from the department hierarchy</small>
					</div>

					<div class="row">
						<div class="col-md-4">
							<div class="mb-3">
								<label for="reporting_to" class="form-label">Reporting To Name *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_to"
									name="reporting_to" required readonly>
							</div>
						</div>
						<div class="col-md-4">
							<div class="mb-3">
								<label for="reporting_manager_id" class="form-label-sm">Reporting Manager ID *</label>
								<input type="text" class="form-control form-control-sm" id="reporting_manager_id"
									name="reporting_manager_id" required readonly>
							</div>
						</div>

						<div class="col-md-4">
							<label for="team_id" class="form-label small">
								Team <span class="text-danger">*</span>
							</label>

							<select name="team_id"
								id="team_id"
								class="form-select form-select-sm"
								required>

								<option value="">Select Team</option>
								<option value="1">BTS-RnD FCzzz</option>
								<option value="2">Contractual</option>
								<option value="3">Marketing</option>
								<option value="4">PD VC+FC</option>
								<option value="5">Production VC KushDutt Sir</option>
								<option value="6">Production VC</option>
								<option value="7">QA VC+FC</option>
								<option value="8">RnD VC</option>
								<option value="9">Sales (P Srinivas Sir) 2</option>
								<option value="10">Sales (P Srinivas Sir)</option>
								<option value="11">TFA-CB</option>
							</select>

						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success">
						<i class="ri-save-line me-1"></i> Generate Party Code & Process
					</button>
				</div>
			</form>
		</div>
	</div>
</div>