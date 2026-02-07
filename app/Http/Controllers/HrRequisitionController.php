<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequisition;
use App\Models\CandidateMaster;
use App\Models\CoreFunction;
use App\Models\CoreDepartment;
use App\Models\CoreVertical;
use App\Models\CoreState;
use App\Models\CoreCityVillage;
use App\Models\MasterEducation;
use App\Models\RequisitionDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\S3Service;

class HrRequisitionController extends Controller
{

    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', '');
        $search = $request->get('search', '');
        $query = ManpowerRequisition::with(['function', 'department', 'vertical', 'submittedBy'])
            ->orderBy('created_at', 'desc');

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
                    ->orWhere('candidate_email', 'like', "%{$search}%")
                    ->orWhere('submitted_by_name', 'like', "%{$search}%");
            });
        }

        $requisitions = $query->paginate(20);

        return view('hr.requisitions.index', compact('requisitions', 'type', 'status'));
    }



    public function create($type)
    {
        if (!in_array($type, ['Contractual', 'TFA', 'CB'])) {
            abort(404);
        }

        // Get only the first level dropdown - Functions
        $functions = CoreFunction::where('is_active', '1')->orderBy('function_name')->get();

        // For other dropdowns, pass empty arrays - they'll be loaded via AJAX
        $departments = collect(); // Empty collection
        $verticals = collect();    // Empty collection
        $sub_departments = collect(); // Empty collection
        $businessUnits = collect(); // Empty collection
        $zones = collect(); // Empty collection
        $regions = collect(); // Empty collection
        $territories = collect(); // Empty collection

        // Only states and educations remain as they're independent
        $states = CoreState::where('is_active', '1')->orderBy('state_name')->get();
        $educations = MasterEducation::where('Status', 'A')->orderBy('EducationName')->get();

        // For HR, we don't auto-fill from employee details
        $autoFillData = [
            'function_id' => null,
            'department_id' => null,
            'vertical_id' => null,
            'sub_department_id' => null,
            'business_unit_id' => null,
            'zone_id' => null,
            'region_id' => null,
            'territory_id' => null,
            'reporting_to' => null,
            'reporting_manager_employee_id' => null,
        ];

        return view("hr.requisitions.{$type}", compact(
            'type',
            'functions',
            'departments',
            'verticals',
            'states',
            'educations',
            'sub_departments',
            'businessUnits',
            'zones',
            'regions',
            'territories',
            'autoFillData'
        ));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        try {
            $validatedData = $this->validateHrRequisition($request);

            // Generate requisition ID
            $requisitionId = ManpowerRequisition::generateRequisitionId($request->requisition_type);

            // HR user details
            $user = Auth::user();

            // Create requisition with Approved status
            $data = [
                'requisition_id' => $requisitionId,
                'requisition_type' => $request->requisition_type,
                'submitted_by_user_id' => $user->id,
                'submitted_by_name' => $user->name,
                'submitted_by_employee_id' => $user->emp_id,
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
                'reporting_to' => $validatedData['reporting_manager_name'],
                'reporting_manager_employee_id' => $validatedData['reporting_manager_id'],
                'contract_start_date' => $validatedData['contract_start_date'],
                'contract_duration' => $validatedData['contract_duration'],
                'contract_end_date' => $validatedData['contract_end_date'],
                'remuneration_per_month' => $validatedData['remuneration_per_month'],
                'reporting_manager_address' => $validatedData['reporting_manager_address'],
                'bank_account_no' => $validatedData['bank_account_no'] ?? null,
                'account_holder_name' => $validatedData['account_holder_name'] ?? null,
                'bank_ifsc' => $validatedData['bank_ifsc'] ?? null,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'pan_no' => $validatedData['pan_no'] ?? null,
                'aadhaar_no' => $validatedData['aadhaar_no'] ?? null,

                'status' => 'Approved',
                'approver_id' => $user->emp_id,
                'hr_verification_date' => now(),
                'hr_verified_id' => $user->emp_id,
            ];

            /**
             * 🔑 ONLY FOR CONTRACTUAL
             */
            if ($request->requisition_type === 'Contractual') {
                $data['other_reimbursement_required'] = $validatedData['other_reimbursement_required'];
                $data['out_of_pocket_required'] = $validatedData['out_of_pocket_required'];
            }

            $requisition = ManpowerRequisition::create($data);


            // Save documents
            $this->saveHrDocuments($request, $requisition->id);

            // Create candidate master if checkbox is checked
            // if ($request->filled('create_candidate_master')) {
            //     $candidate = $this->createCandidateMaster($requisition);

            //     // Update requisition with candidate ID
            //     $requisition->update([
            //         'candidate_master_id' => $candidate->id,
            //         'candidate_employee_id' => $candidate->employee_id
            //     ]);
            // }

            return response()->json([
                'success' => true,
                'message' => 'Requisition created and approved successfully!',
                'requisition_id' => $requisition->requisition_id,
                'redirect' => route('hr_requisitions.index')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('HR Requisition Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating requisition: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function validateHrRequisition(Request $request)
    {
        // Use the same validation as regular requisitions but with HR specific fields
        $rules = [
            'requisition_type' => 'required|in:Contractual,TFA,CB',
            'candidate_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:-18 years',
            'gender' => 'required|in:Male,Female,Other',
            'mobile_no' => 'required|digits:10',
            'candidate_email' => 'required|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'highest_qualification' => 'required|exists:master_education,EducationId',
            'college_name' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:500',
            'city' => 'required|exists:core_city_village,id',
            'state_residence' => 'required|exists:core_state,id',
            'pin_code' => 'required|digits:6',
            'sub_department_id' => 'nullable|integer',
            'business_unit' => 'nullable|integer',
            'zone' => 'nullable|integer',
            'region' => 'nullable|integer',
            'territory' => 'nullable|integer',
            'district' => 'nullable|string|max:100',
            'function_id' => 'required|exists:core_org_function,id',
            'department_id' => 'required|exists:core_department,id',
            'vertical_id' => 'required|exists:core_vertical,id',
            'work_location_hq' => 'required|string|max:255',
            'state_work_location' => 'required|exists:core_state,id',
            'contract_start_date' => 'required|date',
            'contract_duration' => 'required|integer|min:15|max:270',
            'contract_end_date' => 'required|date|after_or_equal:contract_start_date',
            'remuneration_per_month' => 'required|numeric|min:0',
            'reporting_manager_name' => 'required|string|max:255',
            'reporting_manager_id' => 'required|string|max:50',
            'reporting_manager_address' => 'required|string|max:500',
            'pan_no' => 'nullable|string|max:10|regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/',
            'aadhaar_no' => 'nullable|digits:12',
            'bank_account_no' => 'nullable|string|max:50',
            'account_holder_name' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name' => 'nullable|string|max:255',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'pan_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'aadhaar_card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driving_licence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'other_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'create_candidate_master' => 'nullable|boolean',
        ];

        // Additional rules for Contractual
        if ($request->requisition_type === 'Contractual') {
            $rules['other_reimbursement_required'] = 'required|in:Y,N';
            $rules['out_of_pocket_required'] = 'required|in:Y,N';
        } else {
            $rules['other_reimbursement_required'] = 'nullable|in:Y,N';
            $rules['out_of_pocket_required'] = 'nullable|in:Y,N';
        }

        return $request->validate($rules);
    }

    protected function saveHrDocuments(Request $request, $requisitionId)
    {
        $userId = Auth::id();
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

    protected function uploadDocumentToS3($file, $requisitionType, $documentType)
    {
        $s3Service = app(S3Service::class);
        return $s3Service->uploadRequisitionDocument($file, $requisitionType, $documentType);
    }

    protected function createCandidateMaster($requisition)
    {
        // Generate unique employee ID
        $employeeId = $this->generateEmployeeId($requisition);

        // Get current date for joining
        $joiningDate = now()->format('Y-m-d');

        // Calculate separation date based on contract duration
        $separationDate = date('Y-m-d', strtotime($joiningDate . ' + ' . $requisition->contract_duration . ' days'));

        // Get education name
        $education = MasterEducation::find($requisition->highest_qualification);

        // Create candidate master record
        $candidate = CandidateMaster::create([
            'employee_id' => $employeeId,
            'emp_code' => $employeeId,
            'emp_name' => $requisition->candidate_name,
            'father_name' => $requisition->father_name,
            'gender' => $requisition->gender,
            'date_of_birth' => $requisition->date_of_birth,
            'mobile' => $requisition->mobile_no,
            'email' => $requisition->candidate_email,
            'alternate_email' => $requisition->alternate_email,
            'education' => $education->EducationName ?? null,
            'education_id' => $requisition->highest_qualification,
            'college' => $requisition->college_name,
            'address' => $requisition->address_line_1,
            'city_village_id' => $requisition->city,
            'state_id' => $requisition->state_residence,
            'pincode' => $requisition->pin_code,
            'function_id' => $requisition->function_id,
            'department_id' => $requisition->department_id,
            'vertical_id' => $requisition->vertical_id,
            'sub_department' => $requisition->sub_department,
            'business_unit' => $requisition->business_unit,
            'zone_id' => $requisition->zone,
            'region_id' => $requisition->region,
            'territory_id' => $requisition->territory,
            'work_location' => $requisition->work_location_hq,
            'district' => $requisition->district,
            'state_work_location' => $requisition->state_work_location,
            'reporting_to' => $requisition->reporting_to,
            'reporting_manager_id' => $requisition->reporting_manager_employee_id,
            'joining_date' => $joiningDate,
            'separation_date' => $separationDate,
            'contract_duration' => $requisition->contract_duration,
            'remuneration_per_month' => $requisition->remuneration_per_month,
            'reporting_manager_address' => $requisition->reporting_manager_address,
            'bank_account_no' => $requisition->bank_account_no,
            'account_holder_name' => $requisition->account_holder_name,
            'bank_ifsc' => $requisition->bank_ifsc,
            'bank_name' => $requisition->bank_name,
            'pan_no' => $requisition->pan_no,
            'aadhaar_no' => $requisition->aadhaar_no,
            'employee_status' => 'Active',
            'contract_type' => $requisition->requisition_type,
            'requisition_id' => $requisition->id,
            'created_by_user_id' => Auth::id(),
            'created_by_name' => Auth::user()->name,
        ]);

        return $candidate;
    }

    protected function generateEmployeeId($requisition)
    {
        // Generate employee ID based on requisition type and date
        $prefix = '';

        switch ($requisition->requisition_type) {
            case 'TFA':
                $prefix = 'TFA';
                break;
            case 'CB':
                $prefix = 'CB';
                break;
            case 'Contractual':
                $prefix = 'CON';
                break;
        }

        $year = date('y');
        $month = date('m');

        // Get last employee ID for this type
        $lastEmployee = CandidateMaster::where('employee_id', 'like', $prefix . $year . $month . '%')
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = intval(substr($lastEmployee->employee_id, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $year . $month . $newNumber;
    }

    public function getCitiesByState(Request $request)
    {
        $stateId = $request->input('state_id');

        $cities = CoreCityVillage::where('state_id', $stateId)
            ->where('is_active', 1)
            ->select('id', 'city_village_name as name')
            ->orderBy('city_village_name')
            ->get();

        return response()->json($cities);
    }

    public function getEmployeesByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');

        if (!$departmentId) {
            return response()->json([]);
        }

        // Get employees from core_employee table based on department
        $employees = DB::table('core_employee')
            ->where('department', $departmentId)
            ->where('emp_status', 'A') // Active employees only
            ->where('company_id', '1')
            ->select('employee_id', 'emp_name')
            ->distinct()
            ->orderBy('id')
            ->get();

        return response()->json($employees);
    }

    public function getEmployeeDetails(Request $request)
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json([]);
        }

        // Get employee details from core_employee table
        $employee = DB::table('core_employee')
            ->where('employee_id', $employeeId)
            ->orWhere('emp_code', $employeeId)
            ->first(['employee_id', 'emp_name', 'emp_code', 'emp_email']);

        return response()->json($employee);
    }


    public function getVerticalByFunction(Request $request)
    {
        $functionId = $request->function_id;

        $verticals = DB::table('core_function_vertical_mapping')
            ->leftJoin('core_vertical as v', 'core_function_vertical_mapping.vertical_id', '=', 'v.id')
            ->select('v.id', 'v.vertical_name')
            ->where('org_function_id', $functionId)
            ->where('v.is_active', 1)
            ->orderBy('v.vertical_name')
            ->distinct()
            ->get();

        return response()->json($verticals);
    }

    public function getDepartmentByFunction(Request $request)
    {
        $functionId = $request->function_id;

        $departments = DB::table('core_function_vertical_mapping as fvm')
            ->join('core_fun_vertical_dept_mapping as fvdm', 'fvdm.function_vertical_id', '=', 'fvm.id')
            ->join('core_department as d', 'd.id', '=', 'fvdm.department_id')
            ->select('d.id', 'd.department_name')
            ->distinct()
            ->where('fvm.org_function_id', $functionId)
            ->where('d.id', '!=', 18) // Hide Management Department
            ->where('d.is_active', 1)
            ->orderBy('d.department_name')
            ->get();

        return response()->json($departments);
    }

    public function getSubDepartmentByDepartment(Request $request)
    {
        $departmentId = $request->department_id;

        $subDepartments = DB::table('core_fun_vertical_dept_mapping as fvdm')
            ->leftJoin('core_department_subdepartment_mapping as dsm', 'dsm.fun_vertical_dept_id', '=', 'fvdm.id')
            ->join('core_sub_department as sdpt', 'sdpt.id', '=', 'dsm.sub_department_id')
            ->where('fvdm.department_id', $departmentId)
            ->where('sdpt.is_active', 1)
            ->groupBy('sdpt.id', 'sdpt.sub_department_name')
            ->select('sdpt.id', 'sdpt.sub_department_name')
            ->get();

        return response()->json($subDepartments);
    }

    public function getBusinessUnitByVertical(Request $request)
    {
        $verticalId = $request->vertical_id;

        $businessUnits = DB::table('core_business_unit')
            ->select('id', 'business_unit_name')
            ->where('vertical_id', $verticalId)
            ->where('is_active', 1)
            ->orderBy('business_unit_name')
            ->get();

        return response()->json($businessUnits);
    }


    public function getZoneByBu(Request $request)
    {
        $businessUnitId = $request->business_unit_id;

        $zones = DB::table('core_bu_zone_mapping')
            ->leftJoin('core_zone as z', 'z.id', '=', 'core_bu_zone_mapping.zone_id')
            ->select('z.id', 'z.zone_name')
            ->where('business_unit_id', $businessUnitId)
            ->where('core_bu_zone_mapping.effective_to', null)
            ->where('z.is_active', 1)
            ->orderBy('z.zone_name')
            ->get();

        return response()->json($zones);
    }

    public function getRegionByZone(Request $request)
    {
        $zoneId = $request->zone_id;

        $regions = DB::table('core_zone_region_mapping')
            ->leftJoin('core_region as r', 'r.id', '=', 'core_zone_region_mapping.region_id')
            ->select('r.id', 'r.region_name')
            ->where('zone_id', $zoneId)
            ->where('r.is_active', 1)
            ->orderBy('r.region_name')
            ->get();

        return response()->json($regions);
    }

    public function getTerritoryByRegion(Request $request)
    {
        $regionId = $request->region_id;

        $territories = DB::table('core_region_territory_mapping')
            ->leftJoin('core_territory as t', 't.id', '=', 'core_region_territory_mapping.territory_id')
            ->select('t.id', 't.territory_name')
            ->where('region_id', $regionId)
            ->where('core_region_territory_mapping.effective_to', null)
            ->where('t.is_active', 1)
            ->orderBy('t.territory_name')
            ->get();

        return response()->json($territories);
    }
}
