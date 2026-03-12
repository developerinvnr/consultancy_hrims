$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr("content");

    /* ---------------- Toast ---------------- */

    window.showToast = function (type, message) {
        const toast = `
        <div class="toast align-items-center text-bg-${type} border-0">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
            </div>
        </div>`;

        $(".toast-container").append(toast);
        $(".toast").last().toast("show");

        setTimeout(() => {
            $(".toast").last().remove();
        }, 5000);
    };

    /* ---------------- Agreement Upload ---------------- */

    window.submitAgreementForm = function (form, baseUrl) {
        const candidateId = form.find('input[name="candidate_id"]').val();
        const url = baseUrl.replace("{candidate}", candidateId);
        const formData = new FormData(form[0]);

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },

            beforeSend: function () {
                form.find('button[type="submit"]')
                    .prop("disabled", true)
                    .html(
                        '<i class="ri-loader-4-line ri-spin"></i> Processing...',
                    );
            },

            success: function (response) {
                if (response.success) {
                    showToast("success", response.message);

                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast("error", response.message);
                }
            },

            error: function (xhr) {
                let errorMessage = "Upload failed";

                if (xhr.responseJSON?.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join("<br>");
                }

                showToast("error", errorMessage);
            },
        });
    };

    /* ---------------- Upload Signed Agreement Button ---------------- */

    // Upload Signed Agreement Modal (from email)
    $(".upload-signed-btn").on("click", function () {
        const candidateId = $(this).data("candidate-id");
        const candidateCode = $(this).data("candidate-code");
        const candidateName = $(this).data("candidate-name");
        const agreementNumber = $(this).data("agreement-number");

        $("#signedCandidateInfo").val(`${candidateCode} - ${candidateName}`);
        $("#signedCandidateId").val(candidateId);
        $("#signedAgreementNumber").val(agreementNumber);

        $("#uploadSignedModal").modal("show");
    });

	 /* Signed Agreement Submit */
    $("#uploadSignedEmailForm").on("submit", function(e){
        e.preventDefault();
        submitAgreementForm($(this), "/hr-admin/agreement/{candidate}/upload-signed");
    });


    /* ---------------- Upload Estamp Button ---------------- */

    $(".upload-estamp-btn").on("click", function () {
        const candidateId = $(this).data("candidate-id");
        const candidateCode = $(this).data("candidate-code");
        const candidateName = $(this).data("candidate-name");

        $("#estampCandidateId").val(candidateId);
        $("#estampCandidateInfo").val(candidateCode + " - " + candidateName);

        $("#uploadEstampModal").modal("show");
    });

    $("#uploadEstampForm").on("submit", function (e) {
        e.preventDefault();

        let candidateId = $("#estampCandidateId").val();
        let formData = new FormData(this);

        $.ajax({
            url: window.routes.uploadEstamp.replace(
                "CANDIDATE_ID",
                candidateId,
            ),

            type: "POST",
            data: formData,
            processData: false,
            contentType: false,

            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },

            success: function (response) {
                if (response.success) {
                    Swal.fire("Success", response.message, "success").then(
                        () => {
                            location.reload();
                        },
                    );
                } else {
                    Swal.fire("Error", response.message, "error");
                }
            },

            error: function () {
                Swal.fire("Error", "Upload failed", "error");
            },
        });
    });


		// Receive Courier Modal - Populate data when opened
		$('#receiveCourierModal').on('show.bs.modal', function(event) {
			var button = $(event.relatedTarget); // Button that triggered the modal

			// Extract data from button attributes
			var requisitionId = button.data('requisition-id');
			var agreementId = button.data('agreement-id');
			var candidateName = button.data('candidate-name');
			var courierName = button.data('courier-name');
			var docketNumber = button.data('docket-number');
			var dispatchDate = button.data('dispatch-date');

			// Update the modal fields
			var modal = $(this);
			modal.find('#receiveRequisitionId').val(requisitionId);
			modal.find('#receiveAgreementId').val(agreementId);
			modal.find('#receiveCandidateName').val(candidateName);
			modal.find('#receiveCourierName').text(courierName);
			modal.find('#receiveDocketNumber').text(docketNumber);
			modal.find('#receiveDispatchDate').text(dispatchDate);

			// Set default received date to today
			var today = new Date().toISOString().split('T')[0];
			modal.find('input[name="received_date"]').val(today);
		});

		// Handle form submission
		$('#receiveCourierForm').on('submit', function(e) {
			e.preventDefault();

			var form = $(this);
			var formData = form.serialize();
			var requisitionId = $('#receiveRequisitionId').val();
			var agreementId = $('#receiveAgreementId').val();
			var submitBtn = $('#receiveCourierBtn');

			Swal.fire({
				title: 'Confirm Receipt',
				text: 'Are you sure you want to mark this courier as received?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#28a745',
				confirmButtonText: 'Yes, mark as received',
				cancelButtonText: 'Cancel',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					submitBtn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Processing...');

					return $.ajax({
						url: '/hr-admin/agreement/' + requisitionId + '/courier-received/' + agreementId,
						type: 'POST',
						data: formData,
						headers: {
							'X-CSRF-TOKEN': csrfToken
						}
					});
				}
			}).then((result) => {
				if (result.isConfirmed && result.value && result.value.success) {
					Swal.fire('Success!', result.value.message, 'success');
					setTimeout(() => {
						window.location.reload();
					}, 1500);
				} else if (result.isConfirmed) {
					Swal.fire('Error!', result.value?.message || 'Something went wrong', 'error');
					submitBtn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Confirm Received');
				}
			}).catch((error) => {
				Swal.fire('Error!', 'Failed to process request', 'error');
				submitBtn.prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Confirm Received');
			});
		});

			// Open End Contract Modal
		$(document).on('click', '.end-contract-btn', function() {

			let candidateId = $(this).data('candidate-id');
			let candidateName = $(this).data('candidate-name');

			$('#endCandidateId').val(candidateId);
			$('#endCandidateName').val(candidateName);

			// Set today's date automatically
			let today = new Date().toISOString().split('T')[0];
			$('input[name="last_working_date"]').val(today);

		});

		$('#endContractForm').on('submit', function(e) {

			e.preventDefault();

			let formData = $(this).serialize();
			let candidateId = $('#endCandidateId').val();

			Swal.fire({
				title: 'End Contract?',
				text: 'Candidate will be marked inactive.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Yes, End Contract'
			}).then((result) => {

				if (result.isConfirmed) {

					$.post('/candidate/' + candidateId + '/deactivate', formData, function(response) {

						Swal.fire('Success', 'Contract ended successfully', 'success')
							.then(() => location.reload());

					});

				}

			});

		});

		// Process Modal Show Event
		$('#processModal').on('shown.bs.modal', function(event) {
			let button = $(event.relatedTarget);

			let requisitionType = button.data('requisition-type');
			let teamSelect = $('#team_id');

			if (requisitionType === 'TFA' || requisitionType === 'CB') {
				teamSelect.val('11');
				teamSelect.prop('disabled', true);

				if (!$('#hiddenTeamInput').length) {
					$('<input>').attr({
						type: 'hidden',
						id: 'hiddenTeamInput',
						name: 'team_id',
						value: '11'
					}).appendTo('#processForm');
				}
			} else {
				teamSelect.val('');
				teamSelect.prop('disabled', false);
				$('#hiddenTeamInput').remove();
			}

			let requisitionId = button.data('requisition-id');
			let candidateName = button.data('requisition-name');
			let currentReporting = button.data('current-reporting');
			let currentManagerId = button.data('current-manager-id');

			let modal = $(this);
			modal.find('#modalRequisitionId').val(requisitionId);
			modal.find('#modalCandidateName').val(candidateName);
			modal.find('#currentReporting').val(currentReporting);
			modal.find('#currentManagerId').val(currentManagerId);

			let select = $('#reporting_manager_employee_id');
			select.html('<option value="">Loading...</option>');
			select.trigger('change');

			// AJAX call to load managers
			$.ajax({
				url: '{{ url("hr-admin/applications/get-reporting-managers") }}/' + requisitionId,
				type: 'GET',
				success: function(response) {
					if (!response.success) {
						select.html('<option value="">No data found</option>');
						select.trigger('change');
						return;
					}

					let data = response.data;
					select.empty();
					select.append('<option value="">-- Select Reporting Manager --</option>');

					// Current manager
					if (data.current) {
						select.append(`
                        <option value="${data.current.reporting_manager_employee_id}" selected>
                            ${data.current.reporting_to} (${data.current.reporting_manager_employee_id}) - Current
                        </option>
                    `);

						$('#reporting_to').val(data.current.reporting_to);
						$('#reporting_manager_id').val(data.current.reporting_manager_employee_id);
					}

					// Managers
					if (data.managers?.length) {
						select.append('<optgroup label="Department Managers">');
						data.managers.forEach(m => {
							if (!data.current || m.employee_id != data.current.reporting_manager_employee_id) {
								select.append(`
                                <option value="${m.employee_id}">
                                    ${m.emp_name} (${m.employee_id}) - ${m.emp_designation}
                                </option>
                            `);
							}
						});
					}

					// Employees
					if (data.employees?.length) {
						select.append('<optgroup label="Department Employees">');
						data.employees.forEach(e => {
							if (!data.current || e.employee_id != data.current.reporting_manager_employee_id) {
								select.append(`
                                <option value="${e.employee_id}">
                                    ${e.emp_name} (${e.employee_id}) - ${e.emp_designation}
                                </option>
                            `);
							}
						});
					}

					select.trigger('change.select2');
				},
				error: function() {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'Failed to load reporting managers'
					});
				}
			});
		});

		/* ================================
   Process Modal Manager Change
================================ */

$(document).on('change','#reporting_manager_employee_id',function(){

    let selectedText = $(this).find('option:selected').text();
    let selectedValue = $(this).val();

    if(selectedValue){

        let name = selectedText.split('(')[0].trim();

        $('#reporting_to').val(name);
        $('#reporting_manager_id').val(selectedValue);
    }

});

	// Submit process form
		$('#processForm').on('submit', function(e) {
			e.preventDefault();
			let formData = $(this).serialize();

			Swal.fire({
				title: 'Process Employee?',
				html: '<p>This will generate party code.</p>',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#198754',
				confirmButtonText: 'Yes, process it',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					return $.ajax({
						url: '{{ route("hr-admin.applications.process-modal") }}',
						type: 'POST',
						data: formData
					});
				}
			}).then((result) => {
				if (result.isConfirmed && result.value?.success) {
					Swal.fire('Success', result.value.message, 'success')
						.then(() => location.reload());
				} else if (result.isConfirmed) {
					Swal.fire('Error', result.value?.message || 'Something went wrong', 'error');
				}
			});
		});


});
