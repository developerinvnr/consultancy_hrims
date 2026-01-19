<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\RequisitionDocument;
use App\Models\CandidateMaster;
use App\Models\AgreementTemp;
use App\Models\AgreementDocument;
use App\Models\Employee;
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
			'documents'
		]);

		// Get approvers for HR to select
		$approvers = $this->getApproversHierarchy($requisition);
		$showSendApprovalButton = $requisition->status === 'Hr Verified';

		return view('hr-admin.new-applications.view', compact(
			'requisition',
			'approvers',
			'showSendApprovalButton'
		));
	}

	/**
	 * Verify Application
	 */
	public function verifyApplication(Request $request, ManpowerRequisition $requisition)
	{
		$request->validate([
			'hr_verification_remarks' => 'nullable|string|max:1000'
		]);

		DB::beginTransaction();
		try {
			// Get current HR user's employee ID
			$hrEmployeeId = Auth::user()->employee_id ?? Auth::user()->emp_id ?? 'HR-' . Auth::id();

			// Update requisition status to "Verified"
			$requisition->status = 'Hr Verified';
			$requisition->hr_verification_date = now();
			$requisition->hr_verification_remarks = $request->hr_verification_remarks;
			$requisition->hr_verified_id = $hrEmployeeId;
			$requisition->save();

			DB::commit();

			// Redirect back with success message
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
					'father_name' => 'required|string|max:255',
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
					'function_id' => 'nullable|exists:functions,id',
					'department_id' => 'nullable|exists:departments,id',
					'vertical_id' => 'nullable|exists:verticals,id'
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
					'date_of_joining_required' => 'required|date|after:today',
					'date_of_separation' => 'required|date|after:date_of_joining_required',
					'remuneration_per_month' => 'required|numeric|min:0',
					'agreement_duration' => 'nullable|integer|min:1'
				];
				$data = $request->only([
					'reporting_to',
					'reporting_manager_employee_id',
					'reporting_manager_address',
					'date_of_joining_required',
					'agreement_duration',
					'date_of_separation',
					'remuneration_per_month',
					'fuel_reimbursement_per_month'
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
			if (isset($data['date_of_joining_required']) && $data['date_of_joining_required']) {
				$data['date_of_joining_required'] = Carbon::parse($data['date_of_joining_required']);
			}
			if (isset($data['date_of_separation']) && $data['date_of_separation']) {
				$data['date_of_separation'] = Carbon::parse($data['date_of_separation']);
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
		//dd($request->all());

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

			// Send email to approver
			try {
				Mail::to($approver->emp_email)->send(new RequisitionApprovalRequest($requisition, $approver));

				// Log email sent
				Log::info('Approval email sent', [
					'requisition_id' => $requisition->id,
					'approver_id' => $approver->id,
					'approver_email' => $approver->emp_email,
					'sent_at' => now()
				]);
			} catch (\Exception $emailException) {
				// Log email error but don't fail the transaction
				Log::error('Failed to send approval email: ' . $emailException->getMessage(), [
					'requisition_id' => $requisition->id,
					'approver_email' => $approver->emp_email
				]);

				// You might want to notify admin about email failure
				// Mail::to('admin@example.com')->send(new EmailFailureNotification($emailException));
			}

			DB::commit();

			// Redirect back with success message
			return redirect()->route('hr-admin.applications.new')
				->with('success', 'Application sent for approval successfully. Email notification sent to ' . $approver->emp_email);
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

			// Send email to submitter
			$submitter = $requisition->submittedBy;
			Mail::to($submitter->email)->send(new CorrectionRequested($requisition, $request->correction_remarks));

			DB::commit();

			return redirect()->route('hr-admin.new-applications')
				->with('success', 'Correction request sent to submitter.');
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
		$request->validate([
			'requisition_id' => 'required|exists:manpower_requisitions,id',
			'reporting_manager_employee_id' => 'required|string',
			'reporting_to' => 'required|string|max:255',
		]);

		DB::beginTransaction();
		try {
			$requisition = ManpowerRequisition::findOrFail($request->requisition_id);
			//dd($request->all());
			// Check if already processed (in candidate_master)
			if (CandidateMaster::where('requisition_id', $requisition->id)->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'Application already processed'
				], 400);
			}

			// Check if already exists in agreement_temp
			if (AgreementTemp::where('requisition_id', $requisition->id)->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'Application already exists in agreement processing queue'
				], 400);
			}

			// Get reporting manager details
			$reportingManager = DB::table('core_employee')
				->where('employee_id', $request->reporting_manager_employee_id)
				->first();

			// Generate candidate code
			$candidateCode = $this->generateCandidateCode($requisition->requisition_type);

			// Create candidate master record
			$candidate = CandidateMaster::create([
				'candidate_code' => $candidateCode,
				'requisition_id' => $requisition->id,
				'requisition_type' => $requisition->requisition_type,
				'candidate_email' => $requisition->candidate_email,
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
				'date_of_joining' => $requisition->date_of_joining_required,
				'agreement_duration' => $requisition->agreement_duration,
				'date_of_separation' => $requisition->date_of_separation,
				'remuneration_per_month' => $requisition->remuneration_per_month,
				'fuel_reimbursement_per_month' => $requisition->fuel_reimbursement_per_month,
				'account_holder_name' => $requisition->account_holder_name,
				'bank_account_no' => $requisition->bank_account_no,
				'bank_ifsc' => $requisition->bank_ifsc,
				'bank_name' => $requisition->bank_name,
				'pan_no' => $requisition->pan_no,
				'aadhaar_no' => $requisition->aadhaar_no,
				'candidate_status' => 'Agreement Pending',
				'created_by_user_id' => auth()->id(),
				'updated_by_user_id' => auth()->id(),
			]);

			// Store data in agreement_temp table for agreement generation
			$this->storeAgreementTempData($requisition, $candidateCode);

			// Update requisition status
			$requisition->status = 'Agreement Pending';
			$requisition->processing_date = now();
			$requisition->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Candidate created successfully! Candidate Code: ' . $candidateCode . '. Agreement generation is pending.',
				'candidate_code' => $candidateCode
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error processing application: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to process application: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Generate candidate code
	 */
	private function generateCandidateCode($requisitionType)
	{
		// Map requisition type to prefix
		$prefixMap = [
			'Contractual' => 'CRS',
			'TFA' => 'TFA',
			'CB' => 'CB'
		];

		$prefix = $prefixMap[$requisitionType] ?? 'CAN';

		// Get current year and month
		$year = date('y');

		// Get the last candidate code for this type and month
		$lastCandidate = CandidateMaster::where('candidate_code', 'like', $prefix . '-' . $year . '-%')
			->orderBy('candidate_code', 'desc')
			->first();

		if ($lastCandidate) {
			$lastNumber = intval(substr($lastCandidate->candidate_code, -4));
			$newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
		} else {
			$newNumber = '0001';
		}

		return $prefix . '-' . $year . '-' . $newNumber;
	}


	/**
	 * Store data in agreement_temp table with document paths
	 */
	private function storeAgreementTempData($requisition, $candidateCode)
	{
		// Calculate age from date of birth
		$dateOfBirth = new \DateTime($requisition->date_of_birth);
		$today = new \DateTime();
		$age = $today->diff($dateOfBirth)->y;

		// Fetch document paths from requisition_documents table
		$documentPaths = $this->getDocumentPaths($requisition->id);

		// Store in agreement_temp table
		AgreementTemp::create([
			'candidate_code' => $candidateCode,
			'requisition_id' => $requisition->id,
			'candidate_name' => $requisition->candidate_name,
			'date_of_joining' => $requisition->date_of_joining_required,
			'emp_type' => $requisition->requisition_type, // Store the employee type (Contractual/TFA/CB)
			'contact_number' => $requisition->mobile_no,
			'father_name' => $requisition->father_name,
			'address_line_1' => $requisition->address_line_1,
			'country' => 'India', // Default country
			'state' => $requisition->state_residence,
			'district' => $requisition->district ?? $requisition->city,
			'pin_code' => $requisition->pin_code,
			'date_of_birth' => $requisition->date_of_birth,
			'age' => $age,
			'aadhaar_number' => $requisition->aadhaar_no,
			'id_proof_path' => $documentPaths['id_proof'] ?? null,
			'address_proof_path' => $documentPaths['address_proof'] ?? null,
			'agreement_generated' => 'No',
			'agreement_generated_at' => null,
			'agreement_response' => null
		]);
	}

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
	 * Show agreement upload page
	 */
	// public function showUploadAgreement(CandidateMaster $candidate)
	// {
	// 	if ($candidate->candidate_status !== 'Agreement Pending') {
	// 		return redirect()->route('hr-admin.applications.approved')
	// 			->with('error', 'Agreement cannot be uploaded for this employee');
	// 	}

	// 	$candidate->load(['department', 'function', 'agreementDocuments']);

	// 	return view('hr-admin.approved-applications.upload-agreement', compact('employee'));
	// }

	/**
	 * Upload agreement
	 */
	// public function uploadAgreementStore(Request $request, CandidateMaster $candidate)
	// {
	// 	$request->validate([
	// 		'agreement_file' => 'required|file|mimes:pdf|max:10240'
	// 	]);

	// 	DB::beginTransaction();
	// 	try {
	// 		// Upload file
	// 		$file = $request->file('agreement_file');
	// 		$fileName = time() . '_unsigned_' . $candidate->candidate_code . '_' . $file->getClientOriginalName();
	// 		$filePath = $file->storeAs('agreements/unsigned', $fileName, 's3');

	// 		// Create document record
	// 		AgreementDocument::create([
	// 			'employee_id' => $candidate->id,
	// 			'employee_code' => $candidate->candidate_code,
	// 			'document_type' => 'unsigned',
	// 			'file_name' => $fileName,
	// 			'file_path' => $filePath,
	// 			'file_size' => $file->getSize(),
	// 			'mime_type' => $file->getMimeType(),
	// 			'uploaded_by_user_id' => Auth::id(),
	// 			'uploaded_by_role' => 'hr_admin',
	// 			'upload_date' => now(),
	// 			'verification_status' => 'verified',
	// 			'verified_by_user_id' => Auth::id(),
	// 			'verification_date' => now()
	// 		]);

	// 		// Update employee status
	// 		$candidate->candidate_status = 'Unsigned Agreement Uploaded';
	// 		$candidate->save();

	// 		// Notify submitter
	// 		// $submitter = $candidate->requisition->submittedBy;
	// 		// if ($submitter) {
	// 		// 	Mail::to($submitter->email)->send(new \App\Mail\UnsignedAgreementUploaded($employee, $submitter));
	// 		// }

	// 		DB::commit();

	// 		return redirect()->route('hr-admin.applications.approved')
	// 			->with('success', 'Unsigned agreement uploaded successfully. Submitter has been notified.');
	// 	} catch (\Exception $e) {
	// 		DB::rollBack();
	// 		Log::error('Error uploading agreement: ' . $e->getMessage());

	// 		return redirect()->back()
	// 			->with('error', 'Failed to upload agreement. Please try again.');
	// 	}
	// }

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

	/**
	 * Upload Unsigned Agreement
	 */
	public function uploadUnsignedAgreement(Request $request, CandidateMaster $candidate)
	{
		$request->validate([
			'agreement_file' => 'required|file|mimes:pdf|max:10240',
			'agreement_number' => 'required|string|max:100',
		]);

		DB::beginTransaction();
		try {
			// Delete old unsigned agreement if exists
			$candidate->agreementDocuments()
				->where('document_type', 'unsigned')
				->delete();

			// Upload file to S3
			$file = $request->file('agreement_file');
			$fileName = 'unsigned_' . $candidate->candidate_code . '_' . time() . '.pdf';
			$filePath = 'agreements/unsigned/' . $fileName;

			Storage::disk('s3')->put($filePath, file_get_contents($file));

			// Create new unsigned agreement
			AgreementDocument::create([
				'candidate_id' => $candidate->id,
				'candidate_code' => $candidate->candidate_code,
				'document_type' => 'unsigned',
				'agreement_number' => $request->agreement_number,
				'agreement_path' => $filePath,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role' => 'hr_admin',
			]);

			// Update status
			$candidate->candidate_status = 'Unsigned Agreement Uploaded';
			$candidate->save();

			if ($candidate->requisition) {
				$candidate->requisition->status = 'Unsigned Agreement Uploaded';
				$candidate->requisition->save();
			}

			DB::commit();

			if ($request->ajax()) {
				return response()->json([
					'success' => true,
					'message' => 'Unsigned agreement uploaded successfully.',
					'status' => $candidate->candidate_status
				]);
			}

			return redirect()->back()
				->with('success', 'Unsigned agreement uploaded. Submitter notified.');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error uploading unsigned agreement: ' . $e->getMessage());

			if ($request->ajax()) {
				return response()->json([
					'success' => false,
					'message' => 'Failed to upload agreement: ' . $e->getMessage()
				], 500);
			}

			return redirect()->back()->with('error', 'Failed to upload agreement.');
		}
	}

	public function verifySignedAgreement(Request $request, CandidateMaster $candidate)
	{
		$request->validate([
			'document_id' => 'required|exists:agreement_documents,id'
		]);

		DB::beginTransaction();
		try {
			$document = AgreementDocument::find($request->document_id);

			// Verify the document
			$document->verification_status = 'verified';
			$document->verified_by_user_id = Auth::id();
			$document->verification_date = now();
			$document->save();

			// Update employee status to Active
			$candidate->candidate_status = 'Active';
			$candidate->save();

			DB::commit();

			return redirect()->route('hr-admin.applications.process', $candidate->requisition)
				->with('success', 'Employee activated successfully!');
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
		$request->validate([
			'agreement_file' => 'required|file|mimes:pdf|max:10240',
			'agreement_number' => 'required|string|max:100',
		]);

		DB::beginTransaction();
		try {
			// Delete old signed agreement if exists
			$candidate->agreementDocuments()
				->where('document_type', 'signed')
				->delete();

			// Upload file to S3
			$file = $request->file('agreement_file');
			$fileName = 'signed_' . $candidate->candidate_code . '.pdf';
			$filePath = 'agreements/signed/' . $fileName;

			Storage::disk('s3')->put($filePath, file_get_contents($file));

			// Create new signed agreement
			AgreementDocument::create([
				'candidate_id' => $candidate->id,
				'candidate_code' => $candidate->candidate_code,
				'document_type' => 'signed',
				'agreement_number' => $request->agreement_number,
				'agreement_path' => $filePath,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role' => 'hr_admin',
			]);

			// Update status to completed
			$candidate->candidate_status = 'Agreement Completed';
			$candidate->save();

			$requisition = $candidate->requisition;
			if ($requisition) {
				$requisition->status = 'Agreement Completed';
				$requisition->save();
			}

			DB::commit();
			if ($request->ajax()) {
				return response()->json([
					'success' => true,
					'message' => 'Signed agreement uploaded successfully.. Process completed.',
					'status' => $candidate->candidate_status
				]);
			}


			return redirect()->back()
				->with('success', 'Signed agreement uploaded. Process completed.');
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error uploading signed agreement: ' . $e->getMessage());
			return redirect()->back()->with('error', 'Failed to upload signed agreement.');
		}
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
		$candidate = CandidateMaster::where('employee_code', $employee->employee_id)->first();

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
}
