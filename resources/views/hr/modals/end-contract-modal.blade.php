<!-- End Contract Modal -->
<div class="modal fade" id="endContractModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header">
				<h5 class="modal-title">
					End Contract
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>

			<form id="endContractForm">
				@csrf

				<div class="modal-body">

					<div class="mb-3">
						<label class="form-label">Candidate</label>
						<input type="text" class="form-control" id="endCandidateName" readonly>
					</div>

					<div class="mb-3">
						<label class="form-label">Last Working Date *</label>
						<input type="date"
							name="last_working_date"
							class="form-control"
							max="{{ date('Y-m-d') }}"
							required>
					</div>

					<input type="hidden" name="candidate_id" id="endCandidateId">

				</div>

				<div class="modal-footer">
					<button class="btn btn-light" data-bs-dismiss="modal">
						Cancel
					</button>

					<button type="submit" class="btn btn-danger">
						<i class="ri-user-unfollow-line me-1"></i>
						End Contract
					</button>
				</div>

			</form>

		</div>
	</div>
</div>