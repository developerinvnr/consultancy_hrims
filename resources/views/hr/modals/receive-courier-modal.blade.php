<div class="modal fade" id="receiveCourierModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header">
				<h5 class="modal-title">Mark Courier as Received</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>

			<form id="receiveCourierForm">
				@csrf

				<div class="modal-body">

					<div class="alert alert-info">
						<i class="ri-information-line me-2"></i>
						Confirm that the courier has been received by the candidate.
					</div>

					<div class="mb-3">
						<label>Candidate</label>
						<input type="text" class="form-control" id="receiveCandidateName" readonly>
					</div>

					<div class="mb-3 bg-light p-2 rounded">

						<label>Courier Details</label>

						<div class="row small">
							<div class="col-6">
								<strong>Courier:</strong> <span id="receiveCourierName"></span>
							</div>

							<div class="col-6">
								<strong>Docket:</strong> <span id="receiveDocketNumber"></span>
							</div>

							<div class="col-6 mt-1">
								<strong>Dispatch Date:</strong> <span id="receiveDispatchDate"></span>
							</div>
						</div>

					</div>

					<div class="mb-3">

						<label>Received Date</label>

						<input type="date"
							class="form-control"
							name="received_date"
							id="receivedDate"
							value="{{ date('Y-m-d') }}"
							readonly>

					</div>

					<input type="hidden" name="requisition_id" id="receiveRequisitionId">
					<input type="hidden" name="agreement_id" id="receiveAgreementId">

				</div>

				<div class="modal-footer">
					<button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>

					<button type="submit" class="btn btn-success" id="receiveCourierBtn">
						Confirm Received
					</button>
				</div>

			</form>

		</div>
	</div>
</div>