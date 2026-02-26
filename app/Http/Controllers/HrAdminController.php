<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\RequisitionDocument;
use App\Models\CandidateMaster;
use App\Models\AgreementTemp;
use App\Models\AgreementDocument;
use App\Models\Employee;
use App\Models\LeaveBalance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\RequisitionApprovalRequest;
use App\Mail\RequisitionApproved;
use App\Mail\RequisitionRejected;
use App\Mail\CorrectionRequested;
use App\Services\AgriSamvidaService;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PartyEditHistory;
use App\Services\S3Service;

class HrAdminController extends Controller
{

	/**
	 * HR Admin Dashboard
	 */
	public function dashboard()
	{
		if (!auth()->check() || !auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized');
		}

		$stats = [
			'pending_verification' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),
			'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),
			'pending_approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),
			'approved' => ManpowerRequisition::where('status', 'Approved')->count(),
			'processed' => ManpowerRequisition::where('status', 'Processed')->count(),
			'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),
			'rejected' => ManpowerRequisition::where('status', 'Rejected')->count(),
			'agreement_pending' => CandidateMaster::where('candidate_status', 'Agreement Pending')->count(),
			'total_employees' => CandidateMaster::count(),
		];

		return view('hr-admin.dashboard', compact('stats'));
	}

	/**
	 * New Applications Tab - Pending Verification
	 */
	public function newApplications(Request $request)
	{
		$query = ManpowerRequisition::with(['function', 'department', 'vertical', 'submittedBy'])
			->whereIn('status', ['Pending HR Verification', 'Hr Verified'])
			->orderBy('submission_date', 'desc');

		// Filter by status
		if ($request->has('status')) {
			$status = $request->get('status');
			$query->where('status', $status);
		}

		// Search functionality
		if ($request->has('search')) {
			$search = $request->get('search');
			$query->where(function ($q) use ($search) {
				$q->where('requisition_id', 'like', "%{$search}%")
					->orWhere('candidate_name', 'like', "%{$search}%")
					->orWhere('candidate_email', 'like', "%{$search}%")
					->orWhereHas('submittedBy', function ($q) use ($search) {
						$q->where('name', 'like', "%{$search}%");
					});
			});
		}

		$requisitions = $query->paginate(15);
		$status = $request->get('status', 'all');

		return view('hr-admin.new-applications.index', compact('requisitions', 'status'));
	}

	/**
	 * View Requisition Details
	 */
	public function viewRequisition(ManpowerRequisition $requisition)
	{
		// Only HR Admin can view
		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized');
		}

		$requisition->load([
			'function',
			'department',
			'vertical',
			'submittedBy',
			'documents'  // Still load all documents
		]);

		// Get candidate from CandidateMaster using requisition_id
		$candidate = \App\Models\CandidateMaster::where('requisition_id', $requisition->id)
			->first();

		$agreementDocuments = [];

		if ($candidate) {
			try {
				// Get agreement documents using candidate_code instead of candidate_id
				$agreementDocs = \App\Models\AgreementDocument::where('candidate_code', $candidate->candidate_code)
					->orderBy('created_at', 'desc')
					->get();

				foreach ($agreementDocs as $doc) {

					$s3Url = null;
					$hasFile = false;

					if (!empty($doc->agreement_path) && $doc->agreement_path !== 'null') {
						try {
							$s3Url = $doc->file_url;
							$hasFile = true;
						} catch (\Exception $e) {
							Log::error("Error generating S3 URL for agreement document: " . $e->getMessage());
						}
					}

					$agreementDocuments[] = [
						'id' => $doc->id,
						'type' => 'Agreement',
						'stamp_type' => $this->formatDocumentType($doc->stamp_type),
						'sign_status' => $this->formatDocumentType($doc->sign_status),
						'agreement_number' => $doc->agreement_number,
						'file_name' => 'Agreement_' . ($doc->agreement_number ?? $doc->id) . '.pdf',
						'uploaded_at' => $doc->created_at->format('d-m-Y H:i'),
						's3_url' => $s3Url,
						'has_file' => $hasFile,
						'document_category' => 'agreement',
						'candidate_code' => $doc->candidate_code
					];
				}
			} catch (\Exception $e) {
				\Log::error("Error loading agreement documents: " . $e->getMessage());
			}
		} else {
			\Log::warning("Candidate not found for requisition ID: " . $requisition->id);
		}

		// Get approvers for HR to select
		$approvers = $this->getApproversHierarchy($requisition);
		$showSendApprovalButton = $requisition->status === 'Hr Verified';

		// Add this: Group documents by type and get only the latest one
		$latestDocuments = collect();
		if ($requisition->documents && $requisition->documents->count() > 0) {
			// Group by document_type and get the latest (by created_at) for each type
			$latestDocuments = $requisition->documents
				->groupBy('document_type')
				->map(function ($group) {
					return $group->sortByDesc('created_at')->first();
				})
				->values(); // Reset keys to maintain proper indexing
		}


		return view('hr-admin.new-applications.view', compact(
			'requisition',
			'approvers',
			'showSendApprovalButton',
			'agreementDocuments',
			'candidate',
			'latestDocuments' // Pass this to the view
		));
	}

	private function formatDocumentType($type)
	{
		return ucwords(str_replace('_', ' ', $type));
	}

	/**
	 * Verify Application
	 */
	public function verifyApplication(Request $request, ManpowerRequisition $requisition)
	{
		$isTfaOrCb = in_array($requisition->requisition_type, ['TFA', 'CB']);
		//dd($request->all());
		$request->validate([
			'team_id' => $isTfaOrCb
				? 'required|in:11'
				: 'required|integer',
			'hr_verification_remarks' => 'nullable|string|max:1000',
		]);

		DB::beginTransaction();
		try {
			// HR employee id
			$hrEmployeeId = Auth::user()->employee_id
				?? Auth::user()->emp_id
				?? 'HR-' . Auth::id();

			$requisition->update([
				'team_id' => $request->team_id, // ✅ STORE HERE
				'status' => 'Hr Verified',
				'hr_verification_date' => now(),
				'hr_verification_remarks' => $request->hr_verification_remarks,
				'hr_verified_id' => $hrEmployeeId,
			]);

			DB::commit();

			return redirect()->back()
				->with('success', 'Application verified successfully. You can now send it for approval.');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error verifying application: ' . $e->getMessage());

			return redirect()->back()
				->with('error', 'Failed to verify application. Please try again.');
		}
	}


	/**
	 * Get edit form for a section
	 */
	public function getEditForm(ManpowerRequisition $requisition, Request $request)
	{
		$section = $request->get('section');

		$view = 'hr-admin.new-applications.edit-forms.' . $section;

		// Check if view exists
		if (!view()->exists($view)) {
			return response()->json([
				'error' => 'Form not found'
			], 404);
		}

		// Load necessary data for different sections
		$data = ['requisition' => $requisition];

		switch ($section) {
			case 'work_info':
				$data['functions'] = \App\Models\FunctionModel::orderBy('function_name')->get();
				$data['departments'] = \App\Models\Department::orderBy('department_name')->get();
				$data['verticals'] = \App\Models\Vertical::orderBy('vertical_name')->get();
				break;

			case 'basic_info':
			case 'personal_info':
			case 'employment_details':
			case 'extracted_info':
				// No additional data needed for these sections
				break;

			default:
				return response()->json([
					'error' => 'Invalid section'
				], 400);
		}

		return view($view, $data)->render();
	}

	/**
	 * Update requisition section
	 */
	public function updateSection(Request $request, ManpowerRequisition $requisition)
	{
		$section = $request->get('section');

		$validations = [];
		$data = [];

		// Define validations and data mapping for each section
		switch ($section) {
			case 'basic_info':
				$validations = [
					'candidate_name' => 'required|string|max:255',
					'candidate_email' => 'required|email',
					'requisition_type' => 'required|string|in:Contractual,TFA,CB'
				];
				$data = $request->only(['candidate_name', 'candidate_email', 'requisition_type']);
				break;

			case 'personal_info':
				$validations = [
					'father_name' => 'nullable|string|max:255',
					'mobile_no' => 'required|string|max:20',
					'date_of_birth' => 'required|date|before:today',
					'address_line_1' => 'required|string|max:500',
					'city' => 'required|string|max:100',
					'state_residence' => 'required|string|max:100',
					'pin_code' => 'required|string|max:10'
				];
				$data = $request->only([
					'father_name',
					'mobile_no',
					'alternate_email',
					'date_of_birth',
					'gender',
					'highest_qualification',
					'college_name',
					'address_line_1',
					'city',
					'state_residence',
					'pin_code'
				]);
				break;

			case 'work_info':
				$validations = [
					'work_location_hq' => 'nullable|string|max:255',
					'state_work_location' => 'nullable|string|max:100',
					'function_id' => 'nullable|exists:core_org_function,id',
					'department_id' => 'nullable|exists:core_department,id',
					'vertical_id' => 'nullable|exists:core_vertical,id',
				];
				$data = $request->only([
					'work_location_hq',
					'district',
					'state_work_location',
					'function_id',
					'department_id',
					'vertical_id',
					'sub_department',
					'business_unit',
					'zone',
					'region',
					'territory'
				]);
				break;

			case 'employment_details':
				$validations = [
					'contract_start_date' => 'required|date|after:today',
					'contract_end_date' => 'required|date|after:contract_start_date',
					'remuneration_per_month' => 'required|numeric|min:0',
					'contract_duration' => 'nullable|integer|min:1'
				];
				$data = $request->only([
					'reporting_to',
					'reporting_manager_employee_id',
					'reporting_manager_address',
					'contract_start_date',
					'contract_duration',
					'contract_end_date',
					'remuneration_per_month',
					'other_reimbursement_required',
					'out_of_pocket_required',
				]);
				break;

			case 'extracted_info':
				$validations = [
					'pan_no' => 'nullable|regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/',
					'aadhaar_no' => 'nullable|digits:12',
					'bank_ifsc' => 'nullable|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'
				];
				$data = $request->only([
					'pan_no',
					'aadhaar_no',
					'bank_account_no',
					'account_holder_name',
					'bank_ifsc',
					'bank_name'
				]);
				break;

			default:
				return response()->json([
					'success' => false,
					'message' => 'Invalid section'
				], 400);
		}

		// Validate the request
		$validator = Validator::make($request->all(), $validations);
		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		DB::beginTransaction();
		try {
			// Format date fields before updating
			if (isset($data['date_of_birth']) && $data['date_of_birth']) {
				$data['date_of_birth'] = Carbon::parse($data['date_of_birth']);
			}
			if (isset($data['contract_start_date']) && $data['contract_start_date']) {
				$data['contract_start_date'] = Carbon::parse($data['contract_start_date']);
			}
			if (isset($data['contract_end_date']) && $data['contract_end_date']) {
				$data['contract_end_date'] = Carbon::parse($data['contract_end_date']);
			}

			// Update requisition
			$requisition->update($data);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => ucfirst(str_replace('_', ' ', $section)) . ' updated successfully'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error updating requisition: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to update information: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Verify Requisition - Show Verification Interface
	 */
	public function verifyRequisition(ManpowerRequisition $requisition)
	{
		$requisition->load([
			'function',
			'department',
			'vertical',
			'submittedBy',
			'documents'
		]);

		// Get possible approvers from hierarchy
		$approvers = $this->getApproversHierarchy($requisition);

		return view('hr-admin.new-applications.verify', compact('requisition', 'approvers'));
	}

	/**
	 * Get Approvers Hierarchy
	 */
	private function getApproversHierarchy($requisition)
	{
		$approvers = [];

		// Get the employee who submitted the requisition
		$selfEmployee = DB::table('core_employee')
			->where('employee_id', $requisition->submitted_by_employee_id)
			->first();

		// Get reporting manager
		$reportingManager = DB::table('core_employee')
			->where('employee_id', $requisition->reporting_manager_employee_id)
			->first();

		// If applicant == reporting manager, move one level up
		if (
			$selfEmployee && $reportingManager &&
			$selfEmployee->employee_id == $reportingManager->employee_id
		) {

			$reportingManager = DB::table('core_employee')
				->where('employee_id', $reportingManager->emp_reporting)
				->first();
		}

		// Traverse hierarchy upward
		if ($reportingManager) {

			$currentEmployee = $reportingManager;
			$currentLevel = 1;
			$maxLevels = 6;

			while ($currentEmployee && $currentLevel <= $maxLevels) {

				// Prevent self from appearing anywhere
				if (!$selfEmployee || $currentEmployee->employee_id != $selfEmployee->employee_id) {

					$designation = $currentEmployee->emp_designation ?? 'Manager';

					$isGM = stripos($designation, 'general manager') !== false ||
						stripos($designation, 'gm') !== false;

					$approvers[] = [
						'id' => $currentEmployee->employee_id,
						'name' => $currentEmployee->emp_name . ' (' . $designation . ')',
						'role' => $designation,
						'employee_record' => $currentEmployee,
						'is_gm' => $isGM,
					];

					// Stop traversal at GM
					if ($isGM) {
						break;
					}
				}

				// Move upward
				if (!$currentEmployee->emp_reporting || $currentEmployee->emp_reporting == 0) {
					break;
				}

				$currentEmployee = DB::table('core_employee')
					->where('employee_id', $currentEmployee->emp_reporting)
					->first();

				$currentLevel++;
			}
		}

		/*
    |--------------------------------------------------------------------------
    | Fallback: If no hierarchy found, use designation-based fallback
    |--------------------------------------------------------------------------
    */
		if (empty($approvers)) {

			$fallback = DB::table('core_employee')
				->where('emp_status', 'A')
				->where(function ($q) {
					$q->where('emp_designation', 'like', '%Manager%')
						->orWhere('emp_designation', 'like', '%GM%')
						->orWhere('emp_designation', 'like', '%Head%');
				})
				->orderByRaw("
                CASE 
                    WHEN emp_designation LIKE '%General Manager%' THEN 1
                    WHEN emp_designation LIKE '%GM%' THEN 2
                    WHEN emp_designation LIKE '%Senior Manager%' THEN 3
                    WHEN emp_designation LIKE '%Manager%' THEN 4
                    ELSE 5
                END
            ")
				->limit(10)
				->get();

			foreach ($fallback as $emp) {
				if ($selfEmployee && $emp->employee_id == $selfEmployee->employee_id) {
					continue; // still avoid self
				}

				$approvers[] = [
					'id' => $emp->employee_id,
					'name' => $emp->emp_name . ' (' . $emp->emp_designation . ')',
					'role' => $emp->emp_designation,
					'employee_record' => $emp,
					'is_gm' => stripos($emp->emp_designation, 'general manager') !== false,
				];
			}
		}

		/*
    |--------------------------------------------------------------------------
    | Remove duplicates
    |--------------------------------------------------------------------------
    */
		$unique = [];
		$seen = [];

		foreach ($approvers as $a) {
			if (!in_array($a['id'], $seen)) {
				$unique[] = $a;
				$seen[] = $a['id'];
			}
		}

		return $unique;
	}

	/**
	 * Send for Approval
	 */
	public function sendForApproval(Request $request, ManpowerRequisition $requisition)
	{
		// Validate that only Hr_verified applications can be sent for approval
		if ($requisition->status !== 'Hr Verified') {
			return redirect()->back()
				->with('error', 'Application must be verified by HR before sending for approval.');
		}

		$request->validate([
			'approver_id' => 'required|exists:users,emp_id',
		]);

		DB::beginTransaction();
		try {
			// Update requisition status to "Pending Approval"
			$requisition->status = 'Pending Approval';
			$requisition->approver_id = $request->approver_id;
			$requisition->save();

			// Find the approver
			$approver = Employee::where('employee_id', $request->approver_id)->firstOrFail();
			if (!$approver) {
				throw new \Exception('Approver not found');
			}

			// =================================================
			// ADD COMMUNICATION CONTROL CHECK HERE
			// =================================================
			$communicationService = app(\App\Services\CommunicationService::class);

			// Check if both master toggle and approval reminders are enabled
			$canSendEmail = $communicationService->isEnabled('approval_reminders');
			$emailStatus = "";
			// =================================================

			// Send email to approver ONLY if allowed by communication controls
			if ($canSendEmail) {
				try {
					Mail::to($approver->emp_email)->send(new RequisitionApprovalRequest($requisition, $approver));

					// Log email sent
					Log::info('Approval email sent', [
						'requisition_id' => $requisition->id,
						'approver_id' => $approver->id,
						'approver_email' => $approver->emp_email,
						'sent_at' => now()
					]);

					$emailStatus = "Email notification sent to " . $approver->emp_email;
				} catch (\Exception $emailException) {
					// Log email error but don't fail the transaction
					Log::error('Failed to send approval email: ' . $emailException->getMessage(), [
						'requisition_id' => $requisition->id,
						'approver_email' => $approver->emp_email
					]);

					$emailStatus = "Email notification failed to send.";
				}
			} else {
				// Log that email was skipped due to communication controls
				Log::info('Approval email skipped - communication control disabled', [
					'requisition_id' => $requisition->id,
					'email_enabled' => $communicationService->isEnabled('email_enabled'),
					'approval_reminders' => $communicationService->isEnabled('approval_reminders')
				]);

				$emailStatus = "Email notification not sent (communication control disabled).";
			}

			DB::commit();

			// Redirect back with success message
			return redirect()->route('hr-admin.applications.new')
				->with('success', 'Application sent for approval successfully. ' . $emailStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error sending for approval: ' . $e->getMessage(), [
				'requisition_id' => $requisition->id,
				'approver_id' => $request->approver_id,
				'user_id' => auth()->id()
			]);

			return redirect()->back()
				->with('error', 'Failed to send for approval: ' . $e->getMessage());
		}
	}

	/**
	 * Request Correction
	 */
	public function requestCorrection(Request $request, ManpowerRequisition $requisition)
	{
		$request->validate([
			'correction_remarks' => 'required|string|min:10'
		]);

		DB::beginTransaction();
		try {
			// Update requisition status
			$requisition->status = 'Correction Required';
			$requisition->hr_verification_remarks = $request->correction_remarks;
			$requisition->save();

			// Load relationships for email if needed
			$requisition->load(['function', 'department', 'vertical']);

			// Get communication service
			$communicationService = app(\App\Services\CommunicationService::class);

			// Check if both master toggle and correction notifications are enabled
			$canSendEmail = $communicationService->isEnabled('correction_notifications');
			$emailStatus = "";

			if ($canSendEmail) {
				try {
					$submitter = $requisition->submittedBy;

					if (!$submitter || !$submitter->email) {
						throw new \Exception('Submitter email missing');
					}

					Mail::to($submitter->email)
						->send(new CorrectionRequested($requisition, $request->correction_remarks));

					Log::info('Correction request email sent', [
						'requisition_id' => $requisition->id,
						'email' => $submitter->email
					]);

					$emailStatus = "Correction request email sent to {$submitter->email}";
				} catch (\Exception $e) {
					Log::error('Correction email failed: ' . $e->getMessage());
					$emailStatus = "Correction email failed.";
				}
			} else {
				Log::info('Correction email skipped by communication control', [
					'requisition_id' => $requisition->id
				]);

				$emailStatus = "Correction email skipped (disabled by admin).";
			}


			DB::commit();

			return redirect()->route('hr-admin.new-applications')
				->with('success', 'Correction request sent. ' . $emailStatus);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error requesting correction: ' . $e->getMessage());

			return redirect()->back()
				->with('error', 'Failed to send correction request. Please try again.');
		}
	}

	/**
	 * Approved Applications Tab
	 */
	public function approvedApplications(Request $request)
	{
		$query = ManpowerRequisition::with(['function', 'department', 'vertical', 'submittedBy'])
			->where('status', 'Approved')
			->orderBy('approval_date', 'desc');

		if ($request->has('search')) {
			$search = $request->get('search');
			$query->where(function ($q) use ($search) {
				$q->where('requisition_id', 'like', "%{$search}%")
					->orWhere('candidate_name', 'like', "%{$search}%")
					->orWhere('candidate_email', 'like', "%{$search}%");
			});
		}

		$requisitions = $query->paginate(15);

		return view('hr-admin.approved-applications.index', compact('requisitions'));
	}

	/**
	 * Show reporting manager selection modal data
	 */
	public function getReportingManagers(Request $request, ManpowerRequisition $requisition)
	{
		try {
			$requisition->load(['department']);

			$departmentManagers = collect();
			$departmentEmployees = collect();

			if ($requisition->department) {
				$deptName = $requisition->department->department_name;

				// Get managers
				$departmentManagers = DB::table('core_employee')
					->where('emp_department', 'like', '%' . $deptName . '%')
					->where('emp_status', 'A')
					->where(function ($query) {
						$query->where('emp_designation', 'like', '%Manager%')
							->orWhere('emp_designation', 'like', '%Head%')
							->orWhere('emp_designation', 'like', '%Lead%');
					})
					->orderBy('emp_name')
					->get(['employee_id', 'emp_name', 'emp_designation']);

				// Get other employees
				$departmentEmployees = DB::table('core_employee')
					->where('emp_department', 'like', '%' . $deptName . '%')
					->where('emp_status', 'A')
					->where('emp_designation', 'not like', '%Manager%')
					->where('emp_designation', 'not like', '%Head%')
					->where('emp_designation', 'not like', '%Lead%')
					->orderBy('emp_name')
					->get(['employee_id', 'emp_name', 'emp_designation']);
			}

			return response()->json([
				'success' => true,
				'data' => [
					'current' => [
						'reporting_to' => $requisition->reporting_to,
						'reporting_manager_employee_id' => $requisition->reporting_manager_employee_id,
						'reporting_manager_address' => $requisition->reporting_manager_address
					],
					'managers' => $departmentManagers,
					'employees' => $departmentEmployees
				]
			]);
		} catch (\Exception $e) {
			Log::error('Error fetching reporting managers: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to load reporting managers'
			], 500);
		}
	}

	public function processApplicationModal(Request $request)
	{
		//dd($request->all());
		$request->validate([
			'requisition_id' => 'required|exists:manpower_requisitions,id',
			'reporting_manager_employee_id' => 'required|string',
			'reporting_to' => 'required|string|max:255',
			'team_id' => 'required|integer'
		]);

		$requisition = ManpowerRequisition::findOrFail($request->requisition_id);
		$requisition->update([
			'team_id' => $request->team_id
		]);
		// if (config('services.agreement.test_mode')) {

		// 	$candidateCode = 'TEST-' . time();

		// 	$agreementResponse = $this->generateAgreementViaAPI($requisition, $candidateCode);
		//   //dd($agreementResponse);
		// 	return response()->json([
		// 		'success' => $agreementResponse['success'],
		// 		'message' => 'Agreement API test mode response',
		// 		'agreement_number' => $agreementResponse['agreement_number'] ?? null,
		// 		'agreement_path' => $agreementResponse['agreement_path'] ?? null,
		// 		'api_response' => $agreementResponse,
		// 	]);
		// }

		DB::beginTransaction();
		try {
			// Check if already processed
			if (CandidateMaster::where('requisition_id', $requisition->id)->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'Application already processed'
				], 400);
			}

			// Generate candidate code
			$candidateCode = $this->generateCandidateCode($requisition->requisition_type);

			// Calculate leave_credited for Contractual candidates
			$leaveCredited = 0;
			if ($requisition->requisition_type === 'Contractual') {
				$leaveCredited = $this->calculateLeaveCreditedFromAgreementDuration(
					$requisition->contract_start_date,
					$requisition->contract_end_date
				);
			}

			$baseAmount = (float) $requisition->remuneration_per_month;

			if (in_array($requisition->requisition_type, ['Contractual', 'TFA'])) {
				$tdsAmount      = round($baseAmount * 0.02, 2);
				$contractAmount = round($baseAmount + $tdsAmount, 2);
			} else {
				$contractAmount = $baseAmount;
			}

			// Create candidate master record WITH ALL REQUIRED FIELDS
			$candidate = CandidateMaster::create([
				'candidate_code' => $candidateCode,
				'requisition_id' => $requisition->id,
				'requisition_type' => $requisition->requisition_type,
				'candidate_email' => $requisition->candidate_email, // REQUIRED FIELD
				'candidate_name' => $requisition->candidate_name,
				'father_name' => $requisition->father_name,
				'mobile_no' => $requisition->mobile_no,
				'alternate_email' => $requisition->alternate_email,
				'address_line_1' => $requisition->address_line_1,
				'city' => $requisition->city,
				'state_residence' => $requisition->state_residence,
				'pin_code' => $requisition->pin_code,
				'date_of_birth' => $requisition->date_of_birth,
				'gender' => $requisition->gender,
				'highest_qualification' => $requisition->highest_qualification,
				'college_name' => $requisition->college_name,
				'work_location_hq' => $requisition->work_location_hq,
				'district' => $requisition->district,
				'state_work_location' => $requisition->state_work_location,
				'function_id' => $requisition->function_id,
				'department_id' => $requisition->department_id,
				'vertical_id' => $requisition->vertical_id,
				'sub_department' => $requisition->sub_department,
				'business_unit' => $requisition->business_unit,
				'zone' => $requisition->zone,
				'region' => $requisition->region,
				'territory' => $requisition->territory,
				'reporting_to' => $request->reporting_to,
				'reporting_manager_employee_id' => $request->reporting_manager_employee_id,
				'reporting_manager_address' => $requisition->reporting_manager_address,
				'contract_start_date' => $requisition->contract_start_date,
				'contract_duration' => $requisition->contract_duration,
				'leave_credited' => $leaveCredited,
				'contract_end_date' => $requisition->contract_end_date,
				'remuneration_per_month' => $requisition->remuneration_per_month,
				'contract_amount'        => $contractAmount,
				'other_reimbursement_required' => $requisition->other_reimbursement_required,
				'out_of_pocket_required' => $requisition->out_of_pocket_required,
				'account_holder_name' => $requisition->account_holder_name,
				'bank_account_no' => $requisition->bank_account_no,
				'bank_verification_status' => $requisition->bank_verification_status,
				'bank_branch_address' => $requisition->bank_branch_address,
				'bank_ifsc' => $requisition->bank_ifsc,
				'bank_name' => $requisition->bank_name,
				'pan_no' => $requisition->pan_no,
				'pan_verification_status' => $requisition->pan_verification_status,
				'pan_aadhaar_link_status' => $requisition->pan_aadhaar_link_status,
				'pan_status_2' => $requisition->pan_status_2,
				// Driving Licence
				'driving_licence_no' => $requisition->driving_licence_no,
				'dl_valid_from' => $requisition->dl_valid_from,
				'dl_valid_to' => $requisition->dl_valid_to,
				'dl_verification_status' => $requisition->dl_verification_status,

				'aadhaar_no' => $requisition->aadhaar_no,
				'aadhaar_verification_status' => $requisition->aadhaar_verification_status,
				'candidate_status' => 'Agreement Pending',
				'created_by_user_id' => auth()->id(),
				'updated_by_user_id' => auth()->id(),
			]);

			// Create initial LeaveBalance record for Contractual candidates
			if ($requisition->requisition_type === 'Contractual' && $leaveCredited > 0) {
				LeaveBalance::create([
					'CandidateID' => $candidate->id,
					'calendar_year' => Carbon::parse($requisition->contract_start_date)->year,
					'opening_cl_balance' => $leaveCredited,
					'cl_utilized' => 0,
					'lwp_days_accumulated' => 0,
					'contract_start_date' => $requisition->contract_start_date,
					'contract_end_date' => $requisition->contract_end_date,
				]);
			}

			// Call Agreement Generation API (CURL)
			// In processApplicationModal method, after the API call:
			// Call Agreement Generation API
			$agreementResponse = $this->generateAgreementViaAPI($requisition, $candidateCode);

			\Log::info('Agreement API Response in processApplicationModal:', $agreementResponse);

			if (!$agreementResponse['success']) {
				throw new \Exception('Failed to generate agreement: ' . $agreementResponse['message']);
			}

			$agreementId = $agreementResponse['agreement_id'] ?? null;

			// Collect all pdf paths
			// $pdfPaths = array_filter([
			// 	$agreementResponse['pdf_path_old_stamp'] ?? null,
			// 	$agreementResponse['pdf_path_estamp'] ?? null,
			// ]);

			$agreements = [];


			$agreements = [
				[
					'stamp_type' => 'NONE',
					'pdf_path'   => $agreementResponse['pdf_path_old_stamp'] ?? null,
				],
				[
					'stamp_type' => 'E_STAMP',
					'pdf_path'   => $agreementResponse['pdf_path_estamp'] ?? null,
				],
			];

			if (empty($agreementId) || count($agreements) === 0) {
				\Log::error('Agreement generated but PDFs missing', $agreementResponse);
				throw new \Exception('Agreement generated but PDF files not received from API');
			}

			foreach ($agreements as $agreement) {

				if (empty($agreement['pdf_path'])) {
					continue;
				}

				$pdfPath = $agreement['pdf_path'];
				$fileUrl = 'https://s3.ap-south-1.amazonaws.com/vnragri.bkt/' . ltrim($pdfPath, '/');

				AgreementDocument::create([
					'candidate_id'        => $candidate->id,
					'candidate_code'      => $candidateCode,

					// WHAT IT IS
					'document_type'       => 'agreement',

					// ATTRIBUTES
					'stamp_type'          => $agreement['stamp_type'], // NONE / E_STAMP
					'sign_status'         => 'UNSIGNED',

					'agreement_number'    => $agreementId,
					'agreement_path'      => $pdfPath,
					'file_url'            => $fileUrl,

					'uploaded_by_user_id' => auth()->id(),
					'uploaded_by_role'    => 'hr_admin',
				]);
			}





			// Update candidate (ONLY agreement_id)
			$candidate->update([
				'candidate_status' => 'Unsigned Agreement Uploaded',
				'final_status'     => 'A',
			]);

			// Update requisition
			$requisition->update([
				'status' => 'Unsigned Agreement Uploaded',
				'processing_date' => now(),
			]);

			// Update candidate status
			$candidate->update([
				'candidate_status' => 'Unsigned Agreement Uploaded',
			]);

			DB::commit();

			\Log::info('Process completed successfully for candidate: ' . $candidateCode);

			return response()->json([
				'success' => true,
				'message' => 'Candidate created successfully! Candidate Code: ' . $candidateCode .
					($leaveCredited > 0 ? " with {$leaveCredited} CL days" : "") .
					'. Agreement has been generated.',
				'candidate_code' => $candidateCode,
				'agreement_number' => $agreementId ?? null,
				'agreement_id' => $agreementId ?? null,
				'pdf_path' => $pdfPath ?? null,
				'view_url' => $s3Url ?? null,
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			\Log::error('Error processing application: ' . $e->getMessage());
			\Log::error('Stack trace: ' . $e->getTraceAsString());

			return response()->json([
				'success' => false,
				'message' => 'Failed to process application: ' . $e->getMessage()
			], 500);
		}
	}
	// Helper method to calculate age from DOB
	private function calculateAge($dob)
	{
		return $dob ? Carbon::parse($dob)->age : null;
	}

	private function generateMockResponse($apiData, $candidateCode)
	{
		// For testing purposes only
		$agreementNumber = 'MOCK-' . date('Ymd-His') . '-' . $candidateCode;

		// Create a mock file in storage
		$filePath = 'mock_agreements/' . $agreementNumber . '.pdf';

		// Create directory if not exists
		if (!Storage::disk('public')->exists('mock_agreements')) {
			Storage::disk('public')->makeDirectory('mock_agreements');
		}

		// Create mock PDF content (simple text for testing)
		$content = "MOCK AGREEMENT DOCUMENT\n\n";
		$content .= "Agreement Number: {$agreementNumber}\n";
		$content .= "Generated: " . now()->toDateTimeString() . "\n\n";
		$content .= "Candidate Details:\n";
		foreach ($apiData as $key => $value) {
			$content .= "  " . str_pad($key . ':', 15) . " {$value}\n";
		}

		Storage::disk('public')->put($filePath, $content);

		return [
			'success' => true,
			'agreement_number' => $agreementNumber,
			'file_path' => $filePath,
			'file_url' => Storage::disk('public')->url($filePath),
			'message' => 'Mock agreement generated successfully (DEBUG MODE)',
			'debug_data' => $apiData
		];
	}

	// New method to handle API call for agreement generation
	private function generateAgreementViaAPI($requisition, $candidateCode)
	{
		try {
			// Calculate age
			$age = $this->calculateAge($requisition->date_of_birth);

			$dobFormatted = $requisition->date_of_birth;
			if ($dobFormatted instanceof \Carbon\Carbon) {
				$dobFormatted = $dobFormatted->format('Y-m-d');
			} else {
				$dobFormatted = \Carbon\Carbon::parse($dobFormatted)->format('Y-m-d');
			}

			$district = $requisition->district ?? $requisition->city ?? 'Unknown';

			$date_of_agreement = now()->format('Y-m-d');

			$baseAmount = (float) $requisition->remuneration_per_month;
			$finalAmount = $baseAmount;

			$submittedByUser = User::find($requisition->submitted_by_user_id);

			$submittedByName  = $submittedByUser->name ?? '';
			$submittedByEmail = $submittedByUser->email ?? '';
			$submittedByEmpId = $submittedByUser->emp_id ?? '';


			// Apply 2% TDS ADDITION only for Contractual & TFA
			if (in_array($requisition->requisition_type, ['Contractual', 'TFA'])) {
				$tdsAmount   = round($baseAmount * 0.02, 2);
				$finalAmount = round($baseAmount + $tdsAmount, 2);
			} else {
				// For CB (Counter Boy) send original amount
				$tdsAmount   = 0;
				$finalAmount = $baseAmount;
			}

			$apiData = [
				'nature_type'       => '6',
				'TypeId'            => '3',
				'name'              => $requisition->candidate_name,
				'designation'       => $requisition->requisition_type,
				// Submitter Details (FROM manpower_requisitions)
				'submitted_by_name'  => $submittedByName,
				'submitted_by_email' => $submittedByEmail,
				'submitted_by_empid' => $submittedByEmpId,
				'father_name'       => $requisition->father_name,
				'address'           => $requisition->address_line_1,
				'country'           => 'India',
				'state'             => $requisition->state_residence,
				'hq'                => $requisition->work_location_hq,
				'distric'           => $district,
				'add1'              => $requisition->address_line_1,
				'add2'              => $requisition->address_line_2 ?? '',
				'village'           => $requisition->city,
				'pincode'           => $requisition->pin_code,
				'contact'           => $requisition->mobile_no,
				'dob'               => $dobFormatted,
				'age'               => (string) $age,
				'start_date' => optional($requisition->contract_start_date)->format('Y-m-d'),
				'end_date'   => optional($requisition->contract_end_date)->format('Y-m-d'),
				'amount'            => $finalAmount,
				'expenses'          => $requisition->out_of_pocket_required ?? 0,
				'agreement_type'    => 'CONSULTANCY AGREEMENT',
				'date_of_agreement' => $date_of_agreement,
				'CreatePlace'       => $requisition->work_location_hq,
				'serviceLocation'   => $requisition->work_location_hq,
				'team_id'           => $requisition->team_id,
			];

			\Log::info('Agreement API Request Payload:', $apiData);

			// CURL
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL            => 'https://vnragro.com/agrisamvida/generate_consultancy_agreement.php',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query($apiData),
				CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
			]);

			$response  = curl_exec($ch);
			$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlError = curl_error($ch);
			curl_close($ch);

			\Log::info('API Response: ' . $response);
			\Log::info('HTTP Code: ' . $httpCode);

			if ($curlError || !$response) {
				return [
					'success' => false,
					'message' => $curlError ?: 'No response from API',
				];
			}

			$apiResponse = json_decode($response, true);

			if (!$apiResponse || !$apiResponse['success']) {
				return [
					'success' => false,
					'message' => $apiResponse['message'] ?? 'Agreement API failed',
				];
			}

			\Log::info('Full API Response for debugging:', $apiResponse);

			// ✅ Extract CORRECT fields
			return [
				'success'             => true,
				'agreement_id'        => $apiResponse['agreement_id'] ?? null,
				'pdf_path_old_stamp'  => $apiResponse['pdf_path_old_stamp'] ?? null,
				'pdf_path_estamp'     => $apiResponse['pdf_path_estamp'] ?? null,
				'message'             => $apiResponse['message'] ?? 'Agreement generated',
				'full_response'       => $apiResponse,
			];
		} catch (\Exception $e) {
			\Log::error('Exception in generateAgreementViaAPI: ' . $e->getMessage());

			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	}



	// Helper method to calculate leave credited from agreement duration
	private function calculateLeaveCreditedFromAgreementDuration($startDate, $endDate)
	{
		if (!$startDate || !$endDate) {
			return 0;
		}

		$start = \Carbon\Carbon::parse($startDate);
		$end   = \Carbon\Carbon::parse($endDate);

		// Calculate full months difference
		$months = $start->diffInMonths($end);

		return $months;
	}
	/**
	 * Generate candidate code
	 */
	private function generateCandidateCode($requisitionType)
	{
		// Prefix mapping
		$prefix = match ($requisitionType) {
			'Contractual' => 'CRS',
			'TFA'         => 'TFA',
			'CB'          => 'CBS',
			default       => 'CAN',
		};

		return DB::transaction(function () use ($prefix, $requisitionType) {

			// Lock rows to avoid duplicate code generation (IMPORTANT)
			$lastCandidate = CandidateMaster::where('requisition_type', $requisitionType)
				->whereNotNull('candidate_code')
				->lockForUpdate()
				->orderBy('candidate_code', 'desc')
				->first();

			if ($lastCandidate && $lastCandidate->candidate_code) {

				// Extract numeric part from code (e.g. CRS0005 → 0005)
				preg_match('/(\d+)$/', $lastCandidate->candidate_code, $matches);

				$nextNumber = isset($matches[1])
					? ((int)$matches[1] + 1)
					: 1;
			} else {
				$nextNumber = 1;
			}

			// Format: CRS0001 / TFA0001 / CBS0001
			return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
		});
	}



	/**
	 * Store data in agreement_temp table with document paths
	 */
	// private function storeAgreementTempData($requisition, $candidateCode)
	// {
	// 	// Calculate age from date of birth
	// 	$dateOfBirth = new \DateTime($requisition->date_of_birth);
	// 	$today = new \DateTime();
	// 	$age = $today->diff($dateOfBirth)->y;

	// 	// Fetch document paths from requisition_documents table
	// 	$documentPaths = $this->getDocumentPaths($requisition->id);

	// 	// Store in agreement_temp table
	// 	AgreementTemp::create([
	// 		'candidate_code' => $candidateCode,
	// 		'requisition_id' => $requisition->id,
	// 		'candidate_name' => $requisition->candidate_name,
	// 		'contract_start_date' => $requisition->contract_start_date,
	// 		'emp_type' => $requisition->requisition_type, // Store the employee type (Contractual/TFA/CB)
	// 		'contact_number' => $requisition->mobile_no,
	// 		'father_name' => $requisition->father_name,
	// 		'address_line_1' => $requisition->address_line_1,
	// 		'country' => 'India', // Default country
	// 		'state' => $requisition->state_residence,
	// 		'district' => $requisition->district ?? $requisition->city,
	// 		'pin_code' => $requisition->pin_code,
	// 		'date_of_birth' => $requisition->date_of_birth,
	// 		'age' => $age,
	// 		'aadhaar_number' => $requisition->aadhaar_no,
	// 		'id_proof_path' => $documentPaths['id_proof'] ?? null,
	// 		'address_proof_path' => $documentPaths['address_proof'] ?? null,
	// 		'agreement_generated' => 'No',
	// 		'agreement_generated_at' => null,
	// 		'agreement_response' => null
	// 	]);
	// }

	/**
	 * Get document paths from requisition_documents table
	 */
	private function getDocumentPaths($requisitionId)
	{
		$documents = DB::table('requisition_documents')
			->where('requisition_id', $requisitionId)
			->get();

		$documentPaths = [];

		foreach ($documents as $document) {
			switch ($document->document_type) {
				case 'aadhaar_card':
					$documentPaths['id_proof'] = $document->file_path;
					break;
				case 'driving_licence':
					if (!isset($documentPaths['address_proof'])) {
						$documentPaths['address_proof'] = $document->file_path;
					}
					break;
				case 'pan_card':
					// Store PAN card path if needed
					break;
				case 'bank_document':
					// Store bank document path if needed
					break;
				case 'resume':
					// Store resume path if needed
					break;
			}
		}

		return $documentPaths;
	}

	/**
	 * Show verify signed agreement page
	 */
	public function showVerifySigned(CandidateMaster $candidate)
	{
		if ($candidate->candidate_status !== 'Unsigned Agreement Uploaded') {
			return redirect()->route('hr-admin.applications.approved')
				->with('error', 'Cannot verify agreement for this employee');
		}

		$signedDocument = $candidate->agreementDocuments
			->where('document_type', 'signed')
			->where('verification_status', 'pending')
			->first();

		if (!$signedDocument) {
			return redirect()->route('hr-admin.applications.approved')
				->with('error', 'No signed agreement pending verification');
		}

		return view('hr-admin.approved-applications.verify-signed', compact('employee', 'signedDocument'));
	}

	/**
	 * Master Tab - Agreement Management
	 */
	public function masterTab(Request $request, $type = null)
	{
		$validTypes = ['Contractual', 'TFA', 'CB'];

		if (!$type || !in_array($type, $validTypes)) {
			$type = 'Contractual'; // Default
		}

		$query = CandidateMaster::with(['department', 'function'])
			->where('requisition_type', $type)
			->orderBy('created_at', 'desc');

		if ($request->has('search')) {
			$search = $request->get('search');
			$query->where(function ($q) use ($search) {
				$q->where('employee_code', 'like', "%{$search}%")
					->orWhere('candidate_name', 'like', "%{$search}%")
					->orWhere('candidate_email', 'like', "%{$search}%");
			});
		}

		$employees = $query->paginate(15);

		return view('hr-admin.master.index', compact('employees', 'type'));
	}


	/**
	 * View Employee Details
	 */
	public function viewEmployee(CandidateMaster $candidate)
	{
		$candidate->load([
			'requisition',
			'department',
			'function',
			'vertical',
			'agreementDocuments'
		]);

		return view('hr-admin.master.view-employee', compact('candidate'));
	}


	/**
	 * List all candidates pending agreement actions
	 */
	public function agreementPendingList(Request $request)
	{
		// Check if user is HR Admin
		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized access.');
		}

		$query = CandidateMaster::with(['requisition', 'requisition.submittedBy'])
			->orderBy('created_at', 'desc');

		// Filters
		if ($request->has('candidate_status')) {
			$query->where('candidate_status', $request->candidate_status);
		}

		if ($request->has('requisition_type')) {
			$query->where('requisition_type', $request->requisition_type);
		}

		if ($request->has('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('candidate_code', 'like', "%{$search}%")
					->orWhere('candidate_name', 'like', "%{$search}%")
					->orWhere('candidate_email', 'like', "%{$search}%");
			});
		}

		$candidates = $query->paginate(20);
		$statusOptions = [
			'Agreement Pending' => 'Agreement Pending',
			'Unsigned Agreement Uploaded' => 'Unsigned Agreement Uploaded',
			'Agreement Completed' => 'Agreement Completed'
		];

		return view('hr-admin.agreement-management.list', compact('candidates', 'statusOptions'));
	}


	public function verifySignedAgreement(Request $request, CandidateMaster $candidate)
	{
		$request->validate([
			'document_id' => 'required|exists:agreement_documents,id'
		]);

		DB::beginTransaction();
		try {
			$document = AgreementDocument::find($request->document_id);

			// Update employee status to Active
			$candidate->candidate_status = 'Active';
			$candidate->final_status = 'A';
			$candidate->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Candidate activated successfully!'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error verifying signed agreement: ' . $e->getMessage());

			return redirect()->back()
				->with('error', 'Failed to activate employee. Please try again.');
		}
	}

	/**
	 * Upload Signed Agreement
	 */
	public function uploadSignedAgreement(Request $request, CandidateMaster $candidate)
	{
		/* ---------------- VALIDATION ---------------- */
		$request->validate([
			'agreement_file'   => 'required|file|mimes:pdf|max:10240',
			'agreement_number' => 'required|string|max:100',
		]);
		//dd($candidate);
		DB::beginTransaction();

		try {
			/* ---------------- REMOVE OLD SIGNED AGREEMENT ---------------- */
			$oldSigned = $candidate->agreementDocuments()
				->where('document_type', 'signed')
				->first();

			if ($oldSigned) {
				Storage::disk('s3')->delete($oldSigned->agreement_path);
				$oldSigned->delete();
			}

			/* ---------------- UPLOAD FILE TO S3 ---------------- */
			$file     = $request->file('agreement_file');
			$fileName = 'signed_' . $candidate->candidate_code . '_' . time() . '.pdf';
			$filePath = 'agreements/signed/' . $fileName;

			Storage::disk('s3')->put(
				$filePath,
				file_get_contents($file),
				'public'
			);

			$fileUrl = Storage::disk('s3')->url($filePath);

			/* ---------------- CREATE AGREEMENT RECORD ---------------- */
			AgreementDocument::create([
				'candidate_id'        => $candidate->id,
				'candidate_code'      => $candidate->candidate_code,
				'document_type'       => 'agreement',
				'sign_status'         => 'SIGNED',
				'stamp_type'          => 'NONE',
				'agreement_number'    => $request->agreement_number,
				'agreement_path'      => $filePath,
				'file_url'            => $fileUrl,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role'    => 'hr_admin',
			]);

			/* ---------------- UPDATE CANDIDATE ---------------- */
			$candidate->update([
				'candidate_status' => 'Active',
				'final_status'     => 'A',
			]);

			/* ---------------- UPDATE REQUISITION ---------------- */
			if ($candidate->requisition) {
				$candidate->requisition->update([
					'status' => 'Agreement Completed',
				]);
			}

			/* ---------------- COMMIT DATABASE ---------------- */
			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();

			Log::error('Signed agreement upload failed', [
				'candidate_id' => $candidate->id,
				'error'        => $e->getMessage(),
			]);

			return $request->ajax()
				? response()->json([
					'success' => false,
					'message' => 'Failed to upload signed agreement.',
				], 500)
				: redirect()->back()->with('error', 'Failed to upload signed agreement.');
		}

		/* ---------------- SYNC TO SUBMITTER PORTAL (POST-COMMIT) ---------------- */
		try {
			$apiPayload = [
				'agreement_id' => $request->agreement_number,
				'file_url'     => $fileUrl,
			];

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL            => 'https://vnragro.com/agrisamvida/generated_signed_agreement.php',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query($apiPayload),
				CURLOPT_HTTPHEADER     => [
					'Content-Type: application/x-www-form-urlencoded',
				],
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
			]);

			$response  = curl_exec($ch);
			$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlError = curl_error($ch);
			curl_close($ch);

			Log::info('Submitter portal sync response', [
				'http_code' => $httpCode,
				'response'  => $response,
				'error'     => $curlError,
				'payload'   => $apiPayload,
			]);
		} catch (\Exception $apiEx) {
			Log::warning('Submitter portal API exception', [
				'message' => $apiEx->getMessage(),
			]);
		}

		/* ---------------- FINAL RESPONSE ---------------- */
		return $request->ajax()
			? response()->json([
				'success' => true,
				'message' => 'Signed agreement uploaded successfully. Process completed.',
				'status'  => $candidate->candidate_status,
			])
			: redirect()->back()->with('success', 'Signed agreement uploaded successfully.');
	}



	/**
	 * Get agreement details (API)
	 */
	public function getAgreementDetails(AgreementDocument $agreement)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access'
			], 403);
		}

		try {
			// Get file name from path
			$fileName = basename($agreement->agreement_path);

			return response()->json([
				'success' => true,
				'data' => [
					'id' => $agreement->id,
					'agreement_number' => $agreement->agreement_number,
					'document_type' => $agreement->document_type,
					'file_name' => $fileName,
					'created_at' => $agreement->created_at,
					'uploaded_by' => $agreement->uploaded_by_user_id
				]
			]);
		} catch (\Exception $e) {
			Log::error('Error getting agreement details: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to get agreement details'
			], 500);
		}
	}


	/**
	 * Update agreement (if HR uploaded wrong)
	 */

	/**
	 * Update agreement (API endpoint for modal updates)
	 */
	public function updateAgreement(Request $request, AgreementDocument $agreement)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access'
			], 403);
		}

		$request->validate([
			'agreement_number' => 'nullable|string|max:100',
			'agreement_file' => 'nullable|file|mimes:pdf|max:10240',
		]);

		// Check if at least one field is being updated
		if (!$request->filled('agreement_number') && !$request->hasFile('agreement_file')) {
			return response()->json([
				'success' => false,
				'message' => 'Please provide either agreement number or file to update'
			], 422);
		}

		DB::beginTransaction();
		try {
			$candidate = $agreement->candidate;
			$updateData = [];
			$logChanges = [];

			// Track changes for logging
			if ($request->filled('agreement_number') && $request->agreement_number != $agreement->agreement_number) {
				$logChanges['old_number'] = $agreement->agreement_number;
				$logChanges['new_number'] = $request->agreement_number;
				$updateData['agreement_number'] = $request->agreement_number;
			}

			// If new file uploaded
			if ($request->hasFile('agreement_file')) {
				$file = $request->file('agreement_file');

				// Delete old file from S3 if exists
				if ($agreement->agreement_path && Storage::disk('s3')->exists($agreement->agreement_path)) {
					Storage::disk('s3')->delete($agreement->agreement_path);
				}

				// Upload new file to same S3 location
				$filePath = 'agreements/' . $agreement->document_type . '/' . basename($agreement->agreement_path);
				Storage::disk('s3')->put($filePath, file_get_contents($file));

				$logChanges['file_updated'] = true;
				$updateData['agreement_path'] = $filePath;
			}

			// Update agreement if there are changes
			if (!empty($updateData)) {
				$updateData['uploaded_by_user_id'] = Auth::id();
				$agreement->update($updateData);

				// Log the update
				Log::info('Agreement updated via modal', array_merge([
					'agreement_id' => $agreement->id,
					'candidate_id' => $candidate->id,
					'document_type' => $agreement->document_type,
					'updated_by' => Auth::id(),
					'updated_at' => now()
				], $logChanges));

				DB::commit();

				return response()->json([
					'success' => true,
					'message' => 'Agreement updated successfully.'
				]);
			} else {
				DB::rollBack();
				return response()->json([
					'success' => false,
					'message' => 'No changes detected.'
				], 400);
			}
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error updating agreement via modal: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to update agreement: ' . $e->getMessage()
			], 500);
		}
	}

	// In your controller
	public function getSignedDocuments(CandidateMaster $candidate)
	{
		try {
			$documents = $candidate->agreementDocuments()
				->where('document_type', 'signed')
				->where('uploaded_by_role', 'submitter') // Only show submitter-uploaded docs
				->orderBy('created_at', 'desc')
				->get()
				->map(function ($doc) {
					return [
						'id' => $doc->id,
						'agreement_number' => $doc->agreement_number,
						'created_at' => $doc->created_at->format('d-M-Y H:i'),
						'file_url' => Storage::disk('s3')->url($doc->agreement_path),
						'uploaded_by' => $doc->uploaded_by_role
					];
				});

			return response()->json([
				'success' => true,
				'documents' => $documents
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to load documents'
			], 500);
		}
	}
	// public function updateAgreement(Request $request, CandidateMaster $candidate, $type)
	// {
	// 	$request->validate([
	// 		'agreement_file' => 'nullable|file|mimes:pdf|max:10240',
	// 		'agreement_number' => 'required|string|max:100',
	// 		'reason' => 'required|string|max:500',
	// 	]);

	// 	if (!in_array($type, ['unsigned', 'signed'])) {
	// 		return redirect()->back()->with('error', 'Invalid type.');
	// 	}

	// 	DB::beginTransaction();
	// 	try {
	// 		// Get current agreement
	// 		$currentAgreement = $candidate->agreementDocuments()
	// 			->where('document_type', $type)
	// 			->first();

	// 		if (!$currentAgreement) {
	// 			return redirect()->back()->with('error', 'No agreement found.');
	// 		}

	// 		$filePath = $currentAgreement->agreement_path;

	// 		// If new file uploaded
	// 		if ($request->hasFile('agreement_file')) {
	// 			$file = $request->file('agreement_file');
	// 			$fileName = $type . '_updated_' . $candidate->candidate_code . '.pdf';
	// 			$filePath = 'agreements/' . $type . '/' . $fileName;

	// 			Storage::disk('s3')->put($filePath, file_get_contents($file));
	// 		}

	// 		// Update agreement
	// 		$currentAgreement->update([
	// 			'agreement_number' => $request->agreement_number,
	// 			'agreement_path' => $filePath,
	// 			'uploaded_by_user_id' => Auth::id(),
	// 			'uploaded_by_role' => 'hr_admin',
	// 		]);

	// 		Log::info('Agreement updated', [
	// 			'candidate_id' => $candidate->id,
	// 			'type' => $type,
	// 			'old_number' => $currentAgreement->agreement_number,
	// 			'new_number' => $request->agreement_number,
	// 			'reason' => $request->reason
	// 		]);

	// 		DB::commit();

	// 		return redirect()->back()
	// 			->with('success', 'Agreement updated successfully.');
	// 	} catch (\Exception $e) {
	// 		DB::rollBack();
	// 		Log::error('Error updating agreement: ' . $e->getMessage());
	// 		return redirect()->back()->with('error', 'Failed to update agreement.');
	// 	}
	// }



	/**
	 * Notify submitter
	 */
	// private function notifySubmitter($candidate, $agreementNumber)
	// {
	// 	try {
	// 		$submitter = $candidate->requisition->submittedBy;
	// 		if ($submitter && $submitter->email) {
	// 			Mail::to($submitter->email)->send(new \App\Mail\UnsignedAgreementUploaded(
	// 				$candidate,
	// 				$submitter,
	// 				$agreementNumber
	// 			));
	// 		}
	// 	} catch (\Exception $e) {
	// 		Log::error('Failed to send email: ' . $e->getMessage());
	// 	}
	// }
	/**
	 * Download Agreement
	 */
	public function downloadAgreement(AgreementDocument $agreement)
	{
		// Check permissions
		if (!Auth::user()->hasRole('hr_admin')) {
			abort(403);
		}

		// Generate download URL (for S3) or serve file
		$path = storage_path('app/' . $agreement->file_path);

		if (file_exists($path)) {
			return response()->download($path, $agreement->file_name);
		}

		return redirect()->back()->with('error', 'File not found.');
	}

	/**
	 * Download Document
	 */
	public function downloadDocument(RequisitionDocument $document)
	{
		// Check permissions
		if (!Auth::user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized access');
		}

		try {
			// Check if file exists in storage
			if (!Storage::exists($document->file_path)) {
				return redirect()->back()
					->with('error', 'File not found in storage.');
			}

			// Get file details
			$filePath = Storage::path($document->file_path);
			$fileName = $document->file_name;

			// For S3 storage
			if (config('filesystems.default') === 's3') {
				$headers = [
					'Content-Type' => $document->mime_type,
					'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
				];

				return Storage::download($document->file_path, $fileName, $headers);
			}

			// For local storage
			return response()->download($filePath, $fileName, [
				'Content-Type' => $document->mime_type,
			]);
		} catch (\Exception $e) {
			Log::error('Error downloading document: ' . $e->getMessage());

			return redirect()->back()
				->with('error', 'Failed to download document. Please try again.');
		}
	}

	/**
	 * Show agreement management page
	 */
	public function agreementManagement(CandidateMaster $candidate)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized access.');
		}

		$candidate->load([
			'requisition.submittedBy',
			'agreementDocuments'
		]);

		// Get current unsigned agreement
		$unsignedAgreement = $candidate->agreementDocuments()
			->where('document_type', 'unsigned')
			->first();

		// Get signed agreement
		$signedAgreement = $candidate->agreementDocuments()
			->where('document_type', 'signed')
			->first();

		return view('hr-admin.agreement-management.management', compact(
			'candidate',
			'unsignedAgreement',
			'signedAgreement'
		));
	}


	/**
	 * Download agreement document
	 */
	public function downloadAgreementDocument(AgreementDocument $agreement)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized access.');
		}

		try {
			$filePath = $agreement->agreement_path;

			if (!Storage::disk('s3')->exists($filePath)) {
				return redirect()->back()
					->with('error', 'Agreement file not found.');
			}

			$fileContent = Storage::disk('s3')->get($filePath);

			return response()->make($fileContent, 200, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
			]);
		} catch (\Exception $e) {
			Log::error('Error downloading agreement document: ' . $e->getMessage());
			return redirect()->back()
				->with('error', 'Failed to download agreement.');
		}
	}

	/**
	 * View agreement document (opens in new tab)
	 */
	public function viewAgreementDocument(AgreementDocument $agreement)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized access.');
		}

		try {
			$filePath = $agreement->agreement_path;

			if (!Storage::disk('s3')->exists($filePath)) {
				abort(404, 'Agreement file not found.');
			}

			$fileContent = Storage::disk('s3')->get($filePath);

			return response()->make($fileContent, 200, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
			]);
		} catch (\Exception $e) {
			Log::error('Error viewing agreement document: ' . $e->getMessage());
			abort(500, 'Failed to view agreement.');
		}
	}


	/**
	 * Get unsigned agreement (API endpoint)
	 */
	public function getUnsignedAgreement(CandidateMaster $candidate)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access'
			], 403);
		}

		$agreement = $candidate->agreementDocuments()
			->where('document_type', 'unsigned')
			->first();

		if (!$agreement) {
			return response()->json([
				'success' => false,
				'message' => 'No unsigned agreement found'
			]);
		}

		return response()->json([
			'success' => true,
			'agreement_number' => $agreement->agreement_number,
			'file_url' => 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/' . $agreement->agreement_path
		]);
	}

	/**
	 * Show agreement upload page (for employee parameter)
	 */
	public function showUploadAgreementByEmployee(Employee $employee)
	{
		// Convert employee to candidate if needed, or use directly
		// This is a placeholder - you need to adjust based on your data structure
		$candidate = CandidateMaster::where('candidate_code', $employee->employee_id)->first();

		if (!$candidate) {
			return redirect()->back()
				->with('error', 'Candidate not found for this employee.');
		}

		return $this->showUploadUnsignedAgreement($candidate);
	}

	/**
	 * Store agreement (for employee parameter)
	 */
	public function uploadAgreementStoreByEmployee(Request $request, Employee $employee)
	{
		$candidate = CandidateMaster::where('employee_code', $employee->employee_id)->first();

		if (!$candidate) {
			return redirect()->back()
				->with('error', 'Candidate not found for this employee.');
		}

		return $this->uploadUnsignedAgreement($request, $candidate);
	}

	/**
	 * Show verify signed agreement (for employee parameter)
	 */
	public function showVerifySignedByEmployee(Employee $employee)
	{
		$candidate = CandidateMaster::where('employee_code', $employee->employee_id)->first();

		if (!$candidate) {
			return redirect()->back()
				->with('error', 'Candidate not found for this employee.');
		}

		return $this->showVerifySigned($candidate);
	}

	/**
	 * Show edit form for party
	 */
	public function editParty(CandidateMaster $candidate)
	{

		try {

			if (!auth()->user()->hasRole('hr_admin')) {
				\Log::warning('Unauthorized access to editParty');
				abort(403, 'Unauthorized');
			}

			$candidate->load([
				'agreementDocuments',
				'requisition',
				'cityMaster',
				'residenceState',
				'qualification'
			]);

			$functions = \App\Models\CoreFunction::orderBy('function_name')->get();

			$departments = \App\Models\CoreDepartment::orderBy('department_name')->get();

			$verticals = \App\Models\CoreVertical::orderBy('vertical_name')->get();

			$selectedCity = \App\Models\CoreCityVillage::find($candidate->city);



			$states = \App\Models\CoreState::orderBy('state_name')->get();

			$qualifications = \App\Models\MasterEducation::orderBy('EducationName')->get();


			$departmentEmployees = DB::table('core_employee')
				->where('department', $candidate->department_id)
				->where('emp_status', 'A')
				->where('company_id', '1')
				->select('employee_id', 'emp_name', 'emp_code', 'emp_designation')
				->orderBy('emp_name')
				->get();

			$editHistory = PartyEditHistory::where('candidate_id', $candidate->id)
				->with('user')
				->orderBy('created_at', 'desc')
				->get();


			return view('hr-admin.master.edit-party', compact(
				'candidate',
				'functions',
				'departments',
				'verticals',
				'editHistory',
				'departmentEmployees',
				'selectedCity',
				'states',
				'qualifications'
			));
		} catch (\Throwable $e) {

			\Log::error('EditParty Error', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);

			abort(500, 'Something went wrong.');
		}
	}


	private function syncManpowerTable(CandidateMaster $candidate)
	{
		DB::table('manpower_requisitions')
			->where('id', $candidate->requisition_id)
			->update([
				'candidate_name' => $candidate->candidate_name,
				'father_name' => $candidate->father_name,
				'mobile_no' => $candidate->mobile_no,
				'address_line_1' => $candidate->address_line_1,
				'city' => $candidate->city,
				'state_residence' => $candidate->state_residence,
				'pin_code' => $candidate->pin_code,
				'reporting_to' => $candidate->reporting_to,
				'reporting_manager_employee_id' => $candidate->reporting_manager_employee_id,
				'contract_start_date' => $candidate->contract_start_date,
				'contract_end_date' => $candidate->contract_end_date,
				'remuneration_per_month' => $candidate->remuneration_per_month,
				'updated_at' => now()
			]);
	}


	/**
	 * Update party details
	 */
	public function updateParty(Request $request, CandidateMaster $candidate)
	{
		//dd($request->all());
		$request->merge([
			'pin_code' => $request->pin_code ?: '000000'
		]);



		if (!auth()->user()->hasRole('hr_admin')) {
			abort(403, 'Unauthorized');
		}

		// Determine which tab is being updated
		$activeTab = $request->input('active_tab', 'personal');

		$rules = [];

		// Personal Info Tab
		if ($activeTab == 'personal' || $activeTab == 'all') {
			$rules = array_merge($rules, [
				'candidate_name' => 'required|string|max:255',
				'father_name' => 'nullable|string|max:255',
				'candidate_email' => 'nullable|email|max:255',
				'mobile_no' => 'nullable|string|max:15',
				'date_of_birth' => 'required|date',
				'gender' => 'required|in:Male,Female,Other',
				'pan_no' => 'nullable|regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/',
				'aadhaar_no' => 'nullable|digits:12',
				'highest_qualification' => 'required|integer',
				'address_line_1' => 'required|string|max:500',
				'city' => 'required|integer',
				'state_residence' => 'required|integer',
				'pin_code' => 'required|string|max:6',
			]);
		}

		// Work Details Tab
		if ($activeTab == 'work' || $activeTab == 'all') {
			$rules = array_merge($rules, [
				'work_location_hq' => 'required|string|max:255',
				'state_work_location' => 'required|integer',
				'function_id' => 'required|exists:core_org_function,id',
				'department_id' => 'required|exists:core_department,id',
				'vertical_id' => 'required|exists:core_vertical,id',
				'contract_start_date' => 'nullable|date',
				'contract_end_date' => 'required|date|after_or_equal:contract_start_date',
				'remuneration_per_month' => 'required|numeric|min:0',
				'team_id' => 'nullable|string|max:50',
			]);
		}

		// Bank Details Tab
		if ($activeTab == 'bank' || $activeTab == 'all') {
			$rules = array_merge($rules, [
				'account_holder_name' => 'nullable|string|max:255',
				'bank_account_no' => 'nullable|string|max:50',
				'bank_name' => 'nullable|string|max:255',
				'bank_ifsc' => 'nullable|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
			]);
		}

		// Reporting Changes Tab
		if ($activeTab == 'reporting' || $activeTab == 'all') {
			$rules = array_merge($rules, [
				'reporting_department_id' => 'required|exists:core_department,id',
				'new_reporting_manager_employee_id' => 'required|string|max:50',
				'new_reporting_to' => 'required|string|max:255', // From hidden field
				'reporting_change_reason' => 'required|string|max:100',
				'reporting_change_remarks' => 'nullable|string|max:500',
			]);
		}
		//dd($request->all());
		// Documents Tab - ALL FIELDS OPTIONAL
		if ($activeTab == 'documents' || $activeTab == 'all') {
			$rules = array_merge($rules, [
				// Unsigned Agreement - both fields must be present together if uploading
				'unsigned_agreement_number' => 'required_with:unsigned_agreement_file|string|max:100|nullable',
				'unsigned_agreement_file' => 'required_with:unsigned_agreement_number|file|mimes:pdf|max:10240|nullable',

				// Signed Agreement - both fields must be present together if uploading
				'signed_agreement_number' => 'required_with:signed_agreement_file|string|max:100|nullable',
				'signed_agreement_file' => 'required_with:signed_agreement_number|file|mimes:pdf|max:10240|nullable',

				// PAN Document
				'pan_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
				'pan_document_number' => 'nullable|string|max:100',

				// Aadhaar Document
				'aadhaar_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
				'aadhaar_document_number' => 'nullable|string|max:100',

				// Bank Document
				'bank_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
				'bank_document_number' => 'nullable|string|max:100',

				// Other Document
				'other_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
				'other_document_number' => 'nullable|string|max:100',
				'other_document_type' => 'nullable|string|max:100',
			]);
		}



		// Validate only the rules for the active tab
		$validated = $request->validate($rules);
		//dd('validation passed');

		DB::beginTransaction();
		try {

			$changes = [];
			$reportingChanged = false;

			// ===============================
			// HANDLE REPORTING CHANGE FIRST
			// ===============================
			if ($activeTab == 'reporting' || $activeTab == 'all') {

				$oldReportingTo = $candidate->getOriginal('reporting_to');
				$oldReportingManagerId = $candidate->getOriginal('reporting_manager_employee_id');
				$oldDepartmentId = $candidate->getOriginal('department_id');
				if (
					$request->new_reporting_to != $oldReportingTo ||
					$request->new_reporting_manager_employee_id != $oldReportingManagerId ||
					$request->reporting_department_id != $oldDepartmentId
				) {

					$reportingChanged = true;

					// Update candidate master
					$candidate->reporting_to = $request->new_reporting_to;
					$candidate->reporting_manager_employee_id = $request->new_reporting_manager_employee_id;
					$candidate->department_id = $request->reporting_department_id; // IMPORTANT

					// Update manpower requisition also
					DB::table('manpower_requisitions')
						->where('id', $candidate->requisition_id)
						->update([
							'reporting_to' => $request->new_reporting_to,
							'reporting_manager_employee_id' => $request->new_reporting_manager_employee_id,
							'department_id' => $request->reporting_department_id, // IMPORTANT
							'updated_at' => now()
						]);

					$changes['reporting_manager'] = [
						'old' => $oldReportingTo . ' (' . $oldReportingManagerId . ')',
						'new' => $request->new_reporting_to . ' (' . $request->new_reporting_manager_employee_id . ')'
					];

					if ($oldDepartmentId != $request->reporting_department_id) {

						$changes['department'] = [
							'old' => $oldDepartmentId,
							'new' => $request->reporting_department_id
						];
					}
				}
			}




			/*
		|--------------------------------------------------------------------------
		| Fields allowed to update
		|--------------------------------------------------------------------------
		*/
			$fieldsToCheck = [];

			if ($activeTab == 'personal' || $activeTab == 'all') {
				$fieldsToCheck = array_merge($fieldsToCheck, [
					'candidate_name',
					'father_name',
					'candidate_email',
					'mobile_no',
					'date_of_birth',
					'gender',
					'pan_no',
					'aadhaar_no',
					'highest_qualification',
					'address_line_1',
					'city',
					'state_residence',
					'pin_code',
				]);
			}

			if ($activeTab == 'work' || $activeTab == 'all') {
				$fieldsToCheck = array_merge($fieldsToCheck, [
					'work_location_hq',
					'state_work_location',
					'function_id',
					'department_id',
					'vertical_id',
					'contract_start_date',
					'contract_end_date',
					'remuneration_per_month',
					'team_id',
				]);
			}

			if ($activeTab == 'bank' || $activeTab == 'all') {
				$fieldsToCheck = array_merge($fieldsToCheck, [
					'account_holder_name',
					'bank_account_no',
					'bank_name',
					'bank_ifsc',
				]);
			}

			/*
		|--------------------------------------------------------------------------
		| Fill model but don't save yet
		|--------------------------------------------------------------------------
		*/
			$candidate->fill($request->only($fieldsToCheck));

			/*
		|--------------------------------------------------------------------------
		| Get only changed fields
		|--------------------------------------------------------------------------
		*/
			\Log::info('Dirty fields:', $candidate->getDirty());

			$dirtyFields = $candidate->getDirty();

			// if (!empty($dirtyFields)) {

			// 	foreach ($dirtyFields as $field => $newValue) {

			// 		$oldValue = $candidate->getOriginal($field);

			// 		// Normalize date fields
			// 		if (in_array($field, ['date_of_birth', 'contract_start_date', 'contract_end_date'])) {
			// 			$oldValue = $oldValue ? \Carbon\Carbon::parse($oldValue)->format('Y-m-d') : null;
			// 			$newValue = $newValue ? \Carbon\Carbon::parse($newValue)->format('Y-m-d') : null;
			// 		}

			// 		$changes[$field] = [
			// 			'old' => $oldValue,
			// 			'new' => $newValue
			// 		];
			// 	}

			// 	// Save only changed fields
			// 	$candidate->save();
			// }

			if ($candidate->isDirty()) {
				$candidate->save();
				$this->syncManpowerTable($candidate);
			}

			// ===============================
// HANDLE DOCUMENT UPLOADS
// ===============================

			/** @var \App\Services\S3Service $s3Service */
			$s3Service = app(S3Service::class);

			// Handle UNSIGNED AGREEMENT - goes to agreement_documents
			if ($request->hasFile('unsigned_agreement_file') && $request->filled('unsigned_agreement_number')) {
				$file = $request->file('unsigned_agreement_file');

				// Upload to S3 in agreements folder
				$extension = $file->getClientOriginalExtension();
				$timestamp = time();
				$filename = 'unsigned_agreement_' . $candidate->candidate_code . '_' . $timestamp . '.' . $extension;
				$filePath = 'agreements/unsigned/' . $filename;

				Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
				$fileUrl = Storage::disk('s3')->url($filePath);

				// Delete old unsigned agreement if exists
				$oldUnsigned = $candidate->agreementDocuments()
					->where('document_type', 'agreement')
					->where('sign_status', 'UNSIGNED')
					->first();

				if ($oldUnsigned) {
					Storage::disk('s3')->delete($oldUnsigned->agreement_path);
					$oldUnsigned->delete();
				}

				// Create new agreement document
				AgreementDocument::create([
					'candidate_id' => $candidate->id,
					'candidate_code' => $candidate->candidate_code,
					'document_type' => 'agreement',
					'stamp_type' => 'NONE',
					'sign_status' => 'UNSIGNED',
					'agreement_number' => $request->unsigned_agreement_number,
					'agreement_path' => $filePath,
					'file_url' => $fileUrl,
					'uploaded_by_user_id' => Auth::id(),
					'uploaded_by_role' => 'hr_admin',
				]);

				$changes['unsigned_agreement_uploaded'] = [
					'old' => 'Previous/None',
					'new' => 'New unsigned agreement uploaded'
				];
			}

			// Handle SIGNED AGREEMENT - goes to agreement_documents
			if ($request->hasFile('signed_agreement_file') && $request->filled('signed_agreement_number')) {
				$file = $request->file('signed_agreement_file');

				// Upload to S3 in agreements folder
				$extension = $file->getClientOriginalExtension();
				$timestamp = time();
				$filename = 'signed_agreement_' . $candidate->candidate_code . '_' . $timestamp . '.' . $extension;
				$filePath = 'agreements/signed/' . $filename;

				Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
				$fileUrl = Storage::disk('s3')->url($filePath);

				// Delete old signed agreement if exists
				$oldSigned = $candidate->agreementDocuments()
					->where('document_type', 'agreement')
					->where('sign_status', 'SIGNED')
					->first();

				if ($oldSigned) {
					Storage::disk('s3')->delete($oldSigned->agreement_path);
					$oldSigned->delete();
				}

				// Create new agreement document
				AgreementDocument::create([
					'candidate_id' => $candidate->id,
					'candidate_code' => $candidate->candidate_code,
					'document_type' => 'agreement',
					'stamp_type' => 'NONE',
					'sign_status' => 'SIGNED',
					'agreement_number' => $request->signed_agreement_number,
					'agreement_path' => $filePath,
					'file_url' => $fileUrl,
					'uploaded_by_user_id' => Auth::id(),
					'uploaded_by_role' => 'hr_admin',
				]);

				$changes['signed_agreement_uploaded'] = [
					'old' => 'Previous/None',
					'new' => 'New signed agreement uploaded'
				];
			}

			// Handle PAN DOCUMENT - goes to requisition_documents
			if ($request->hasFile('pan_document')) {
				$file = $request->file('pan_document');
				$documentNumber = $request->pan_document_number;

				// Upload using S3Service
				$upload = $this->uploadDocumentToS3($file, $candidate->requisition_type, 'pan_card');

				if ($upload['success']) {
					// Store in requisition_documents
					DB::table('requisition_documents')->insert([
						'requisition_id' => $candidate->requisition_id,
						'document_type' => 'pan_card',
						'file_name' => $upload['filename'],
						'file_path' => $upload['key'],
						'uploaded_by_user_id' => Auth::id(),
						'created_at' => now(),
						'updated_at' => now(),
					]);

					$changes['pan_document_uploaded'] = [
						'old' => 'Missing',
						'new' => 'PAN document uploaded'
					];
				}
			}

			// Handle AADHAAR DOCUMENT - goes to requisition_documents
			if ($request->hasFile('aadhaar_document')) {
				$file = $request->file('aadhaar_document');
				$documentNumber = $request->aadhaar_document_number;

				// Upload using S3Service
				$upload = $this->uploadDocumentToS3($file, $candidate->requisition_type, 'aadhaar_card');

				if ($upload['success']) {
					// Store in requisition_documents
					DB::table('requisition_documents')->insert([
						'requisition_id' => $candidate->requisition_id,
						'document_type' => 'aadhaar_card',
						'file_name' => $upload['filename'],
						'file_path' => $upload['key'],
						'uploaded_by_user_id' => Auth::id(),
						'created_at' => now(),
						'updated_at' => now(),
					]);

					$changes['aadhaar_document_uploaded'] = [
						'old' => 'Missing',
						'new' => 'Aadhaar document uploaded'
					];
				}
			}

			// Handle BANK DOCUMENT - goes to requisition_documents
			if ($request->hasFile('bank_document')) {
				$file = $request->file('bank_document');
				$documentNumber = $request->bank_document_number;

				// Upload using S3Service
				$upload = $this->uploadDocumentToS3($file, $candidate->requisition_type, 'bank_document');

				if ($upload['success']) {
					// Store in requisition_documents
					DB::table('requisition_documents')->insert([
						'requisition_id' => $candidate->requisition_id,
						'document_type' => 'bank_document',
						'file_name' => $upload['filename'],
						'file_path' => $upload['key'],
						'uploaded_by_user_id' => Auth::id(),
						'created_at' => now(),
						'updated_at' => now(),
					]);

					$changes['bank_document_uploaded'] = [
						'old' => 'Missing',
						'new' => 'Bank document uploaded'
					];
				}
			}

			// Handle OTHER DOCUMENT - goes to requisition_documents
			if ($request->hasFile('other_document') && $request->filled('other_document_type')) {
				$file = $request->file('other_document');
				$documentType = $request->other_document_type;
				$documentNumber = $request->other_document_number;

				// Upload using S3Service
				$upload = $this->uploadDocumentToS3($file, $candidate->requisition_type, $documentType);

				if ($upload['success']) {
					// Store in requisition_documents
					DB::table('requisition_documents')->insert([
						'requisition_id' => $candidate->requisition_id,
						'document_type' => $documentType,
						'file_name' => $upload['filename'],
						'file_path' => $upload['key'],
						'uploaded_by_user_id' => Auth::id(),
						'created_at' => now(),
						'updated_at' => now(),
					]);

					$changes['other_document_uploaded'] = [
						'old' => 'Missing',
						'new' => 'Other document uploaded'
					];
				}
			}
			// Log all changes to history
			foreach ($changes as $field => $change) {
				PartyEditHistory::create([
					'candidate_id' => $candidate->id,
					'field_name' => $field,
					'old_value' => is_array($change['old']) ? json_encode($change['old']) : $change['old'],
					'new_value' => is_array($change['new']) ? json_encode($change['new']) : $change['new'],
					'changed_by_user_id' => Auth::id(),
					'reason' => $request->reporting_change_reason ?? $request->agreement_remarks ?? 'Manual update',
				]);
			}

			// If reporting changed, generate new agreement via API
			// if ($reportingChanged) {
			// 	try {
			// 		// Get the requisition associated with this candidate
			// 		$requisition = $candidate->requisition;

			// 		if (!$requisition) {
			// 			throw new \Exception('Associated requisition not found');
			// 		}

			// 		// Generate new agreement via API
			// 		$agreementResponse = $this->generateAgreementViaAPI($requisition, $candidate->candidate_code);

			// 		\Log::info('Agreement API Response for reporting change:', $agreementResponse);

			// 		if (!$agreementResponse['success']) {
			// 			throw new \Exception('Failed to generate agreement: ' . $agreementResponse['message']);
			// 		}

			// 		$agreementId = $agreementResponse['agreement_id'] ?? null;

			// 		// Delete old unsigned agreements
			// 		$oldUnsignedAgreements = $candidate->agreementDocuments()
			// 			->where('document_type', 'agreement')
			// 			->where('sign_status', 'UNSIGNED')
			// 			->get();

			// 		foreach ($oldUnsignedAgreements as $oldDoc) {
			// 			Storage::disk('s3')->delete($oldDoc->agreement_path);
			// 			$oldDoc->delete();
			// 		}

			// 		// Save new agreements
			// 		$agreements = [
			// 			[
			// 				'stamp_type' => 'NONE',
			// 				'pdf_path'   => $agreementResponse['pdf_path_old_stamp'] ?? null,
			// 			],
			// 			[
			// 				'stamp_type' => 'E_STAMP',
			// 				'pdf_path'   => $agreementResponse['pdf_path_estamp'] ?? null,
			// 			],
			// 		];

			// 		foreach ($agreements as $agreement) {
			// 			if (empty($agreement['pdf_path'])) {
			// 				continue;
			// 			}

			// 			$pdfPath = $agreement['pdf_path'];
			// 			$fileUrl = 'https://s3.ap-south-1.amazonaws.com/vnragri.bkt/' . ltrim($pdfPath, '/');

			// 			AgreementDocument::create([
			// 				'candidate_id'        => $candidate->id,
			// 				'candidate_code'      => $candidate->candidate_code,
			// 				'document_type'       => 'agreement',
			// 				'stamp_type'          => $agreement['stamp_type'],
			// 				'sign_status'         => 'UNSIGNED',
			// 				'agreement_number'    => $agreementId,
			// 				'agreement_path'      => $pdfPath,
			// 				'file_url'            => $fileUrl,
			// 				'uploaded_by_user_id' => Auth::id(),
			// 				'uploaded_by_role'    => 'hr_admin',
			// 				'remarks'              => 'Generated due to reporting manager change',
			// 			]);
			// 		}

			// 		// Update candidate with new agreement number
			// 		$candidate->update([
			// 			'agreement_number' => $agreementId,
			// 			'candidate_status' => 'Unsigned Agreement Uploaded',
			// 		]);

			// 		// Log agreement generation in history
			// 		PartyEditHistory::create([
			// 			'candidate_id' => $candidate->id,
			// 			'field_name' => 'agreement_generated',
			// 			'old_value' => 'Old agreement',
			// 			'new_value' => 'New agreement generated (ID: ' . $agreementId . ')',
			// 			'changed_by_user_id' => Auth::id(),
			// 			'reason' => 'Reporting manager changed',
			// 		]);

			// 		\Log::info('New agreement generated successfully for candidate: ' . $candidate->candidate_code . ' due to reporting change');
			// 	} catch (\Exception $e) {
			// 		\Log::error('Failed to generate agreement for reporting change: ' . $e->getMessage(), [
			// 			'candidate_id' => $candidate->id,
			// 			'reporting_from' => $oldReportingTo ?? 'unknown',
			// 			'reporting_to' => $request->new_reporting_to,
			// 		]);

			// 		// Don't rollback the entire transaction if agreement generation fails
			// 		// Just log it and continue - the reporting change is still saved
			// 		// PartyEditHistory::create([
			// 		// 	'candidate_id' => $candidate->id,
			// 		// 	'field_name' => 'agreement_generation_failed',
			// 		// 	'old_value' => '',
			// 		// 	'new_value' => 'Failed to generate agreement: ' . $e->getMessage(),
			// 		// 	'changed_by_user_id' => Auth::id(),
			// 		// 	'reason' => 'Reporting manager changed but agreement generation failed',
			// 		// ]);
			// 	}
			// }

			DB::commit();


			$message = 'Party details updated successfully.';
			if ($reportingChanged) {
				$message .= ' Reporting manager changed.';
			}

			// Count how many documents were uploaded
			$uploadedCount = 0;
			foreach ($changes as $key => $change) {
				if (str_contains($key, '_uploaded') || str_contains($key, '_updated')) {
					$uploadedCount++;
				}
			}

			if ($uploadedCount > 0) {
				$message .= " {$uploadedCount} document(s) have been uploaded/updated.";
			}

			return redirect()->back()->with('success', $message);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error updating party: ' . $e->getMessage(), [
				'candidate_id' => $candidate->id,
				'user_id' => Auth::id()
			]);

			return redirect()->back()
				->with('error', 'Failed to update party details: ' . $e->getMessage())
				->withInput();
		}
	}


	protected function uploadDocumentToS3($file, $requisitionType, $documentType)
	{
		$s3Service = app(S3Service::class);
		return $s3Service->uploadRequisitionDocument($file, $requisitionType, $documentType);
	}

	/**
	 * Add document to party
	 */
	public function addPartyDocument(Request $request, CandidateMaster $candidate)
	{
		if (!auth()->user()->hasRole('hr_admin')) {
			return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
		}

		$request->validate([
			'document_type' => 'required|string',
			'document_number' => 'required|string|max:100',
			'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
		]);

		try {
			$file = $request->file('document_file');
			$fileName = $request->document_type . '_' . $candidate->candidate_code . '_' . time() . '.' . $file->getClientOriginalExtension();
			$filePath = 'documents/' . $request->document_type . '/' . $fileName;

			Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
			$fileUrl = Storage::disk('s3')->url($filePath);

			$document = AgreementDocument::create([
				'candidate_id' => $candidate->id,
				'candidate_code' => $candidate->candidate_code,
				'document_type' => $request->document_type,
				'agreement_number' => $request->document_number,
				'agreement_path' => $filePath,
				'file_url' => $fileUrl,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role' => 'hr_admin',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Document uploaded successfully',
				'document' => $document
			]);
		} catch (\Exception $e) {
			Log::error('Error uploading document: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to upload document: ' . $e->getMessage()
			], 500);
		}
	}
}
