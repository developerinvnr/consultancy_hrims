<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\CoreFunction;
use App\Models\CoreDepartment;
use App\Models\CoreVertical;
use App\Models\CoreState;
use App\Models\RequisitionDocument;
use Illuminate\Support\Facades\Log;
use App\Services\S3Service;

class ManpowerRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'all');
        $status = $request->get('status', '');
        $search = $request->get('search', '');

        // Get user's department details
        $employeeDetails = DB::table('core_employee')
            ->where('employee_id', $user->emp_id)
            ->orWhere('emp_code', $user->emp_code)
            ->first();

        $isSalesDepartment = $employeeDetails && $employeeDetails->department == '15';

        //dd($isSalesDepartment);
        $query = ManpowerRequisition::with(['function', 'department', 'vertical'])
            ->where('submitted_by_user_id', $user->id);

        if ($type !== 'all') {
            $query->where('requisition_type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('requisition_id', 'like', "%{$search}%")
                    ->orWhere('candidate_name', 'like', "%{$search}%")
                    ->orWhere('candidate_email', 'like', "%{$search}%");
            });
        }

        $requisitions = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('requisitions.index', compact('requisitions', 'type', 'isSalesDepartment'));
    }

    public function create($type)
    {
        if (!in_array($type, ['Contractual', 'TFA', 'CB'])) {
            abort(404);
        }

        $user = Auth::user();

        // Get user's employee details from core_employee table
        $employeeDetails = DB::table('core_employee')
            ->where('employee_id', $user->emp_id)
            ->orWhere('emp_code', $user->emp_code)
            ->first();

        // Check department-based access control
        if (in_array($type, ['TFA', 'CB'])) {
            // Only allow if department is Sales (15)
            $isSalesDepartment = $employeeDetails && $employeeDetails->department == '15';

            if (!$isSalesDepartment) {
                abort(403, 'Access denied. TFA and CB requisitions can only be created by Sales department users.');
            }
        }

        // Get dropdown data
        $functions = CoreFunction::where('is_active', '1')->orderBy('function_name')->get();
        $departments = CoreDepartment::where('is_active', '1')->orderBy('department_name')->get();
        $verticals = CoreVertical::where('is_active', '1')->orderBy('vertical_name')->get();
        $sub_departments = DB::table('core_sub_department')->where('is_active', 1)->orderBy('id')->get();

        // Get business units, zones, regions, territories from database
        $businessUnits = DB::table('core_business_unit')->where('is_active', '1')->orderBy('business_unit_name')->get();
        $zones = DB::table('core_zone')->where('is_active', '1')->orderBy('zone_name')->get();
        $regions = DB::table('core_region')->where('is_active', '1')->orderBy('region_name')->get();
        $territories = DB::table('core_territory')->where('is_active', '1')->orderBy('territory_name')->get();

        // Get states list from core_state table
        $states = CoreState::where('is_active', '1')
            ->orderBy('state_name')
            ->pluck('state_name')
            ->toArray();
        //dd($employeeDetails);
        // Auto-fill data based on employee details
        $autoFillData = [
            // Reporting information
            'reporting_to' => $employeeDetails->emp_name ?? $user->name,
            'reporting_manager_employee_id' => $employeeDetails->employee_id ?? $user->emp_id,

            // IDs for database storage
            'function_id' => $this->getFunctionIdFromName($employeeDetails->emp_function ?? null),
            'department_id' => $employeeDetails->department ?? null,
            'vertical_id' => $this->getVerticalIdFromName($employeeDetails->emp_vertical ?? null),
            'sub_department_id' => $employeeDetails->sub_department ?? null,

            // Names for display
            'function_name' => $employeeDetails->emp_function ?? null,
            'department_name' => $employeeDetails->emp_department ?? null,
            'vertical_name' => $employeeDetails->emp_vertical ?? null,
            'sub_department_name' => $employeeDetails->emp_sub_department ?? null,

            // Other fields
            'business_unit_id' => $employeeDetails->bu ?? null,
            'zone_id' => $employeeDetails->zone ?? null,
            'region_id' => $employeeDetails->region ?? null,
            'territory_id' => $employeeDetails->territory ?? null,

            // Names for display
            'business_unit_name' => $employeeDetails->emp_bu ?? null,
            'zone_name' => $employeeDetails->emp_zone ?? null,
            'region_name' => $employeeDetails->emp_region ?? null,
            'territory_name' => $employeeDetails->emp_territory ?? null,
        ];
        //dd($autoFillData);
        return view("requisitions.create.{$type}", compact(
            'type',
            'functions',
            'departments',
            'sub_departments',
            'verticals',
            'states',
            'businessUnits',
            'zones',
            'regions',
            'territories',
            'autoFillData'
        ));
    }

    /**
     * Helper method to get function ID from function name
     */
    private function getFunctionIdFromName($functionName)
    {
        if (!$functionName) {
            return null;
        }

        $function = CoreFunction::where('function_name', $functionName)->first();
        return $function ? $function->id : null;
    }

    /**
     * Helper method to get vertical ID from vertical name
     */
    private function getVerticalIdFromName($verticalName)
    {
        if (!$verticalName) {
            return null;
        }

        $vertical = CoreVertical::where('vertical_name', $verticalName)->first();
        return $vertical ? $vertical->id : null;
    }



    public function store(Request $request)
    {
        //dd($request->all());
        try {

            $validatedData = $this->validateRequisition($request);
            //dd($validatedData);

            // Generate requisition ID
            $requisitionId = ManpowerRequisition::generateRequisitionId($request->requisition_type);

            // Get user details for reporting information
            $user = Auth::user();
            $employeeDetails = DB::table('core_employee')
                ->where('employee_id', $user->emp_id)
                ->orWhere('emp_code', $user->emp_id)
                ->first();

            // Create requisition
            $requisition = ManpowerRequisition::create([
                'requisition_id' => $requisitionId,
                'requisition_type' => $request->requisition_type,
                'submitted_by_user_id' => Auth::id(),
                'submitted_by_name' => $employeeDetails->emp_name ?? $user->name,
                'submitted_by_employee_id' => $employeeDetails->employee_id ?? $user->emp_id,
                'submission_date' => now(),
                'candidate_email' => $validatedData['candidate_email'],
                'candidate_name' => $validatedData['candidate_name'],
                'father_name' => $validatedData['father_name'],
                'mobile_no' => $validatedData['mobile_no'],
                'alternate_email' => $validatedData['alternate_email'] ?? null,
                'address_line_1' => $validatedData['address_line_1'],
                'city' => $validatedData['city'],
                'state_residence' => $validatedData['state_residence'],
                'pin_code' => $validatedData['pin_code'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'gender' => $validatedData['gender'],
                'highest_qualification' => $validatedData['highest_qualification'],
                'college_name' => $validatedData['college_name'] ?? null,
                'work_location_hq' => $validatedData['work_location_hq'],
                'district' => $validatedData['district'] ?? null,
                'state_work_location' => $validatedData['state_work_location'],
                'function_id' => $validatedData['function_id'],
                'department_id' => $validatedData['department_id'],
                'vertical_id' => $validatedData['vertical_id'],
                'sub_department' => $validatedData['sub_department_id'] ?? null,
                'business_unit' => $validatedData['business_unit'] ?? null,
                'zone' => $validatedData['zone'] ?? null,
                'region' => $validatedData['region'] ?? null,
                'territory' => $validatedData['territory'] ?? null,
                'reporting_to' => $employeeDetails->emp_name ?? $user->name,
                'reporting_manager_employee_id' => $employeeDetails->employee_id ?? $user->emp_id,
                'contract_start_date' => $validatedData['contract_start_date'],
                'contract_duration' => $validatedData['contract_duration'] ?? null,
                'contract_end_date' => $validatedData['contract_end_date'],
                'remuneration_per_month' => $validatedData['remuneration_per_month'],
                'fuel_reimbursement_per_month' => $validatedData['fuel_reimbursement_per_month'] ?? null,
                'reporting_manager_address' => $validatedData['reporting_manager_address'],
                'bank_account_no' => $validatedData['bank_account_no'] ?? null,
                'account_holder_name' => $validatedData['account_holder_name'] ?? null,
                'bank_ifsc' => $validatedData['bank_ifsc'] ?? null,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'pan_no' => $validatedData['pan_no'] ?? null,
                'aadhaar_no' => $validatedData['aadhaar_no'] ?? null,
                'status' => 'Pending HR Verification',
            ]);

            // Save uploaded documents to requisition_documents table
            $this->saveRequisitionDocuments($request, $requisition->id);

            return response()->json([
                'success' => true,
                'message' => 'Requisition submitted successfully!',
                'redirect' => route('requisitions.index')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating requisition: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error submitting requisition.',
            ], 500);
        }
    }

    protected function saveRequisitionDocuments(Request $request, $requisitionId)
    {
        $userId = Auth::id();

        // Only save pre-extracted documents (already uploaded via AJAX)
        $this->savePreExtractedDocuments($request, $requisitionId, $userId);

        // Handle direct file uploads for documents that weren't processed via AJAX
        $this->saveDirectFileUploads($request, $requisitionId, $userId);
    }

    protected function saveDirectFileUploads(Request $request, $requisitionId, $userId)
    {
        // Only upload files that weren't already processed via AJAX
        // Document mapping: form field name => document_type
        $documentMap = [
            'resume' => 'resume',
            'driving_licence' => 'driving_licence',
            'other_document' => 'other',
        ];

        foreach ($documentMap as $field => $type) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $upload = $this->uploadDocumentToS3($file, $request->requisition_type, $type);

                if ($upload['success']) {
                    RequisitionDocument::create([
                        'requisition_id' => $requisitionId,
                        'document_type' => $type,
                        'file_name' => $upload['filename'],
                        'file_path' => $upload['key'],
                        'uploaded_by_user_id' => $userId,
                    ]);
                }
            }
        }
    }

    protected function savePreExtractedDocuments(Request $request, $requisitionId, $userId)
    {
        $s3Service = app(S3Service::class);

        // Save PAN document (extracted via AJAX)
        if ($request->filled('pan_filename') && $request->filled('pan_filepath')) {
            // Check if path already includes Consultancy folder
            $filePath = $request->pan_filepath;
            if (!str_contains($filePath, 'Consultancy/')) {
                // Generate new path with Consultancy folder
                $filename = basename($filePath);
                $filePath = $s3Service->generateRequisitionPath(
                    $request->requisition_type,
                    'pan_card',
                    $filename
                );
            }

            RequisitionDocument::create([
                'requisition_id' => $requisitionId,
                'document_type' => 'pan_card',
                'file_name' => $request->pan_filename,
                'file_path' => $filePath,
                'uploaded_by_user_id' => $userId,
            ]);
        }

        // Save Bank document (extracted via AJAX)
        if ($request->filled('bank_filename') && $request->filled('bank_filepath')) {
            // Check if path already includes Consultancy folder
            $filePath = $request->bank_filepath;
            if (!str_contains($filePath, 'Consultancy/')) {
                // Generate new path with Consultancy folder
                $filename = basename($filePath);
                $filePath = $s3Service->generateRequisitionPath(
                    $request->requisition_type,
                    'bank_document',
                    $filename
                );
            }

            RequisitionDocument::create([
                'requisition_id' => $requisitionId,
                'document_type' => 'bank_document',
                'file_name' => $request->bank_filename,
                'file_path' => $filePath,
                'uploaded_by_user_id' => $userId,
            ]);
        }

        // Save Aadhaar document (extracted via AJAX)
        if ($request->filled('aadhaar_filename') && $request->filled('aadhaar_filepath')) {
            // Check if path already includes Consultancy folder
            $filePath = $request->aadhaar_filepath;
            if (!str_contains($filePath, 'Consultancy/')) {
                // Generate new path with Consultancy folder
                $filename = basename($filePath);
                $filePath = $s3Service->generateRequisitionPath(
                    $request->requisition_type,
                    'aadhaar_card',
                    $filename
                );
            }

            RequisitionDocument::create([
                'requisition_id' => $requisitionId,
                'document_type' => 'aadhaar_card',
                'file_name' => $request->aadhaar_filename,
                'file_path' => $filePath,
                'uploaded_by_user_id' => $userId,
            ]);
        }
    }

    protected function uploadDocumentToS3($file, $requisitionType, $documentType)
    {
        $s3Service = app(S3Service::class);
        return $s3Service->uploadRequisitionDocument($file, $requisitionType, $documentType);
    }

    protected function validateRequisition(Request $request)
    {
        return $request->validate([
            'requisition_type' => 'required|in:Contractual,TFA,CB',
            'candidate_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:-18 years',
            'gender' => 'required|in:Male,Female,Other',
            'mobile_no' => 'required|digits:10',
            'candidate_email' => 'required|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'highest_qualification' => 'required|string|max:255',
            'college_name' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state_residence' => 'required|string|max:100',
            'pin_code' => 'required|digits:6',
            'sub_department_id' => 'nullable|integer',
            'business_unit'     => 'nullable|integer',
            'zone'              => 'nullable|integer',
            'region'            => 'nullable|integer',
            'territory'         => 'nullable|integer',
            'district'          => 'nullable|string|max:100',
            'function_id' => 'required|exists:core_org_function,id',
            'department_id' => 'required|exists:core_department,id',
            'vertical_id' => 'required|exists:core_vertical,id',
            'work_location_hq' => 'required|string|max:255',
            'state_work_location' => 'required|string|max:100',
            'contract_start_date' => 'required|date',
            'contract_duration' => 'required|integer|min:15|max:270',
            'contract_end_date' => 'required|date',
            'remuneration_per_month' => 'required|numeric|min:0',
            'fuel_reimbursement_per_month' => 'nullable|numeric|min:0',
            'reporting_manager_address' => 'required|string|max:500',

            // Document number fields
            'pan_no' => 'nullable|string|max:10|regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/',
            'aadhaar_no' => 'nullable|digits:12',
            'bank_account_no' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name' => 'nullable|string|max:255',

            // File upload validations
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'aadhaar_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driving_licence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'other_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);
    }

    public function show(ManpowerRequisition $requisition)
    {
        $user = Auth::user();

        $isSubmitter = $user->id === $requisition->submitted_by_user_id;

        $isApprover = (
            $requisition->approver_id &&
            $user->emp_id === $requisition->approver_id
        );

        // Optional: HR role access
        $isHr = $user->hasRole('hr_admin'); // only if using roles

        if (! $isSubmitter && ! $isApprover && ! $isHr) {
            abort(403, 'You are not authorized to view this requisition.');
        }

        $requisition->load([
            'function',
            'department',
            'vertical',
            'submittedBy',
            'candidate'
        ]);

        return view('requisitions.show', compact('requisition'));
    }

    public function edit(ManpowerRequisition $requisition)
    {
        if (Auth::id() !== $requisition->submitted_by_user_id) {
            abort(403, 'You are not authorized to edit this requisition.');
        }

        if ($requisition->status !== 'Correction Required') {
            abort(403, 'Cannot edit requisition in current status.');
        }

        $type = $requisition->requisition_type;
        $functions = CoreFunction::where('is_active', '1')->orderBy('function_name')->get();
        $departments = CoreDepartment::where('is_active', '1')->orderBy('department_name')->get();
        $verticals = CoreVertical::where('is_active', '1')->orderBy('vertical_name')->get();

        // ADD ALL THESE ADDITIONAL DATA LIKE IN CREATE METHOD:
        $sub_departments = DB::table('core_sub_department')->where('is_active', 1)->orderBy('id')->get();
        $businessUnits = DB::table('core_business_unit')->where('is_active', '1')->orderBy('business_unit_name')->get();
        $zones = DB::table('core_zone')->where('is_active', '1')->orderBy('zone_name')->get();
        $regions = DB::table('core_region')->where('is_active', '1')->orderBy('region_name')->get();
        $territories = DB::table('core_territory')->where('is_active', '1')->orderBy('territory_name')->get();

        // FIX: Use the same states source as create()
        $states = CoreState::where('is_active', '1')
            ->orderBy('state_name')
            ->pluck('state_name')
            ->toArray();

        // ADD THIS: Get autoFillData like in create method
        $user = Auth::user();
        $employeeDetails = DB::table('core_employee')
            ->where('employee_id', $user->emp_id)
            ->orWhere('emp_code', $user->emp_id)
            ->first();

        $autoFillData = [
            // Reporting information
            'reporting_to' => $employeeDetails->emp_name ?? $user->name,
            'reporting_manager_employee_id' => $employeeDetails->employee_id ?? $user->emp_id,

            // IDs for database storage
            'function_id' => $this->getFunctionIdFromName($employeeDetails->emp_function ?? null),
            'department_id' => $employeeDetails->department ?? null,
            'vertical_id' => $this->getVerticalIdFromName($employeeDetails->emp_vertical ?? null),
            'sub_department_id' => $employeeDetails->sub_department ?? null,

            // Names for display
            'function_name' => $employeeDetails->emp_function ?? null,
            'department_name' => $employeeDetails->emp_department ?? null,
            'vertical_name' => $employeeDetails->emp_vertical ?? null,
            'sub_department_name' => $employeeDetails->emp_sub_department ?? null,

            // Other fields
            'business_unit_id' => $employeeDetails->bu ?? null,
            'zone_id' => $employeeDetails->zone ?? null,
            'region_id' => $employeeDetails->region ?? null,
            'territory_id' => $employeeDetails->territory ?? null,

            // Names for display
            'business_unit_name' => $employeeDetails->emp_bu ?? null,
            'zone_name' => $employeeDetails->emp_zone ?? null,
            'region_name' => $employeeDetails->emp_region ?? null,
            'territory_name' => $employeeDetails->emp_territory ?? null,
        ];
        $requisition->load('documents');
        //dd($requisition->district);
        return view("requisitions.edit.{$type}", compact(
            'requisition',
            'functions',
            'departments',
            'verticals',
            'states',
            'autoFillData',
            'sub_departments',
            'businessUnits',
            'zones',
            'regions',
            'territories'
        ));
    }

    protected function validateUpdateRequisition(Request $request)
    {
        return $request->validate([
            'requisition_type' => 'required|in:Contractual,TFA,CB',
            'candidate_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:-18 years',
            'gender' => 'required|in:Male,Female,Other',
            'mobile_no' => 'required|digits:10',
            'candidate_email' => 'required|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'highest_qualification' => 'required|string|max:255',
            'college_name' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state_residence' => 'required|string|max:100',
            'pin_code' => 'required|digits:6',

            'function_id' => 'required|exists:core_org_function,id',
            'department_id' => 'required|exists:core_department,id',
            'vertical_id' => 'required|exists:core_vertical,id',

            'work_location_hq' => 'required|string|max:255',
            'state_work_location' => 'required|string|max:100',
            'contract_start_date' => 'required|date',
            'contract_duration' => 'required|integer|min:15|max:270',
            'contract_end_date' => 'required|date',
            'remuneration_per_month' => 'required|numeric|min:0',
            'reporting_manager_address' => 'required|string|max:500',

            // Optional but validated if present
            'pan_no' => 'nullable|string|max:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'aadhaar_no' => 'nullable|digits:12',
            'bank_account_no' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name' => 'nullable|string|max:255',

            // Files optional on update
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'aadhaar_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driving_licence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'other_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);
    }
    public function update(Request $request, ManpowerRequisition $requisition)
    {
        // Authorization (same as edit)
        if (Auth::id() !== $requisition->submitted_by_user_id) {
            abort(403, 'You are not authorized to update this requisition.');
        }

        try {
            // Validation (update mode)
            $validatedData = $this->validateUpdateRequisition($request);
            //dd($validatedData);
            // Update requisition safely (preserve old values if empty)
            $requisition->update([
                'candidate_email' => $validatedData['candidate_email'],
                'candidate_name' => $validatedData['candidate_name'],
                'father_name' => $validatedData['father_name'],
                'mobile_no' => $validatedData['mobile_no'],
                'alternate_email' => $validatedData['alternate_email'] ?? $requisition->alternate_email,

                'address_line_1' => $validatedData['address_line_1'],
                'city' => $validatedData['city'],
                'state_residence' => $validatedData['state_residence'],
                'pin_code' => $validatedData['pin_code'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'gender' => $validatedData['gender'],
                'highest_qualification' => $validatedData['highest_qualification'],
                'college_name' => $validatedData['college_name'] ?? $requisition->college_name,

                'work_location_hq' => $validatedData['work_location_hq'],
                'district' => $validatedData['district'] ?? $requisition->district,
                'state_work_location' => $validatedData['state_work_location'],

                'function_id' => $validatedData['function_id'],
                'department_id' => $validatedData['department_id'],
                'vertical_id' => $validatedData['vertical_id'],

                'sub_department' => $validatedData['sub_department_id'] ?? $requisition->sub_department,
                'business_unit' => $validatedData['business_unit'] ?? $requisition->business_unit,
                'zone' => $validatedData['zone'] ?? $requisition->zone,
                'region' => $validatedData['region'] ?? $requisition->region,
                'territory' => $validatedData['territory'] ?? $requisition->territory,

                'contract_start_date' => $validatedData['contract_start_date'],
                'contract_duration' => $validatedData['contract_duration'],
                'contract_end_date' => $validatedData['contract_end_date'],
                'remuneration_per_month' => $validatedData['remuneration_per_month'],
                'fuel_reimbursement_per_month' => $validatedData['fuel_reimbursement_per_month'] ?? $requisition->fuel_reimbursement_per_month,

                'reporting_manager_address' => $validatedData['reporting_manager_address'],

                // 🔒 Critical identity/bank fields (never overwrite with null)
                'pan_no' => $validatedData['pan_no'] ?? $requisition->pan_no,
                'aadhaar_no' => $validatedData['aadhaar_no'] ?? $requisition->aadhaar_no,
                'bank_account_no' => $validatedData['bank_account_no'] ?? $requisition->bank_account_no,
                'account_holder_name' => $validatedData['account_holder_name'] ?? $requisition->account_holder_name,
                'bank_ifsc' => $validatedData['bank_ifsc'] ?? $requisition->bank_ifsc,
                'bank_name' => $validatedData['bank_name'] ?? $requisition->bank_name,

                // Workflow fields
                'status' => 'Pending HR Verification',
                'hr_verification_remarks' => null,
                'last_updated_by_user_id' => Auth::id(),
                'last_updated_date' => now(),
            ]);

            // Documents handled separately
            $this->updateRequisitionDocuments($request, $requisition->id);

            return response()->json([
                'success' => true,
                'message' => 'Requisition updated successfully!',
                'redirect' => route('requisitions.show', $requisition)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating requisition: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating requisition.',
            ], 500);
        }
    }

    // In your ManpowerRequisitionController or a separate controller
    public function downloadDocument(RequisitionDocument $document)
    {
        // Check authorization - user should own the requisition
        $requisition = $document->requisition;
        if (Auth::id() !== $requisition->submitted_by_user_id) {
            abort(403, 'Unauthorized');
        }

        // Use your S3 service to download the file
        $s3Service = app(S3Service::class);
        return $s3Service->downloadFile($document->file_path, $document->file_name);
    }
    protected function updateRequisitionDocuments(Request $request, $requisitionId)
    {
        $userId = Auth::id();

        // Document mapping: form field name => document_type
        $documentMap = [
            'resume' => 'resume',
            'pan_card' => 'pan_card',
            'aadhaar_card' => 'aadhaar_card',
            'driving_licence' => 'driving_licence',
            'bank_document' => 'bank_document',
            'other_document' => 'other',
        ];

        foreach ($documentMap as $field => $type) {
            if ($request->hasFile($field)) {
                // New file uploaded - replace existing
                $file = $request->file($field);
                $upload = $this->uploadDocumentToS3($file, $request->requisition_type, $type);

                if ($upload['success']) {
                    // Delete existing document of this type if exists
                    if ($type !== 'other') {
                        RequisitionDocument::where('requisition_id', $requisitionId)
                            ->where('document_type', $type)
                            ->delete();
                    }

                    // Create new document
                    RequisitionDocument::create([
                        'requisition_id' => $requisitionId,
                        'document_type' => $type,
                        'file_name' => $upload['filename'],
                        'file_path' => $upload['key'],
                        'uploaded_by_user_id' => $userId,
                    ]);
                }
            }
        }

        // Handle pre-extracted documents (from AJAX updates)
        $this->updatePreExtractedDocuments($request, $requisitionId, $userId);
    }

    protected function updatePreExtractedDocuments(Request $request, $requisitionId, $userId)
    {
        $map = [
            'pan_card'      => ['pan_filename', 'pan_filepath'],
            'bank_document' => ['bank_filename', 'bank_filepath'],
            'aadhaar_card'  => ['aadhaar_filename', 'aadhaar_filepath'],
        ];

        foreach ($map as $type => [$filenameField, $pathField]) {

            // Only update if user really changed it this request
            if ($request->filled($filenameField) && $request->filled($pathField)) {

                $existing = RequisitionDocument::where('requisition_id', $requisitionId)
                    ->where('document_type', $type)
                    ->first();

                // Avoid unnecessary overwrite
                if (!$existing || $existing->file_path !== $request->$pathField) {

                    if ($existing) {
                        $existing->delete();
                    }

                    RequisitionDocument::create([
                        'requisition_id' => $requisitionId,
                        'document_type' => $type,
                        'file_name' => $request->$filenameField,
                        'file_path' => $request->$pathField,
                        'uploaded_by_user_id' => $userId,
                    ]);
                }
            }
        }
    }

    /**
     * Helper method to get function ID from function code
     */
    private function getFunctionIdFromCode($functionCode)
    {
        if (!$functionCode) {
            return null;
        }

        $function = CoreFunction::where('function_code', $functionCode)->first();
        return $function ? $function->id : null;
    }

    /**
     * Helper method to get department ID from department code
     */
    private function getDepartmentIdFromCode($departmentCode)
    {
        if (!$departmentCode) {
            return null;
        }

        $department = CoreDepartment::where('department_code', $departmentCode)->first();
        return $department ? $department->id : null;
    }

    /**
     * Helper method to get vertical ID from vertical code
     */
    private function getVerticalIdFromCode($verticalCode)
    {
        if (!$verticalCode) {
            return null;
        }

        $vertical = CoreVertical::where('vertical_code', $verticalCode)->first();
        return $vertical ? $vertical->id : null;
    }
}
