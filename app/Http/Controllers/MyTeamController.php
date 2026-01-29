<?php

namespace App\Http\Controllers;

use App\Models\CandidateMaster;
use App\Models\RequisitionDocument;
use App\Models\AgreementDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MyTeamExport;

class MyTeamController extends Controller
{
    /**
     * Display My Team page
     */
    public function index()
    {
        return view('my-team.index');
    }

    /**
     * Get candidates for the logged-in user's team
     */
    public function getCandidates(Request $request)
    {
        try {
            $user = Auth::user();
            $empId = $user->emp_id; // Use emp_id from users table

            // Get search parameters
            $search = $request->get('search', '');
            $type = $request->get('type', 'all');
            $status = $request->get('status', 'all');

            // Base query - candidates reporting to current user's emp_id
            $query = CandidateMaster::select([
                'id',
                'candidate_code',
                'candidate_name',
                'candidate_email',
                'mobile_no',
                'requisition_type',
                'requisition_id',
                'work_location_hq',
                'contract_start_date',
                'remuneration_per_month',
                'candidate_status',
                'final_status',
                'reporting_manager_employee_id',
                'reporting_to',
                'created_by_user_id',
                'created_at',
            ])
                ->where('final_status', 'A') // Only active candidates
                ->where('reporting_manager_employee_id', $empId); // Only candidates reporting to this manager

            // Apply search filters
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('candidate_code', 'like', "%{$search}%")
                        ->orWhere('candidate_name', 'like', "%{$search}%")
                        ->orWhere('candidate_email', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%");
                });
            }

            // Apply type filter
            if ($type !== 'all') {
                $query->where('requisition_type', $type);
            }

            // Apply status filter
            if ($status !== 'all') {
                $query->where('final_status', $status);
            }

            $candidates = $query->orderBy('candidate_name')->get();

            return response()->json([
                'success' => true,
                'data' => $candidates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading candidates: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show candidate details
     */
    public function showCandidate($id)
    {
        try {
            $candidate = CandidateMaster::findOrFail($id);

            // Get submitted by user name
            $submittedByName = 'Unknown';
            if ($candidate->created_by_user_id) {
                $submittedByUser = User::find($candidate->created_by_user_id);
                if ($submittedByUser) {
                    $submittedByName = $submittedByUser->name . ' (' . ($submittedByUser->emp_id ?? 'N/A') . ')';
                }
            }

            // Get reporting manager name
            $reportingManagerName = 'Unknown';
            if ($candidate->reporting_manager_employee_id) {
                $reportingManager = User::where('emp_id', $candidate->reporting_manager_employee_id)->first();
                if ($reportingManager) {
                    $reportingManagerName = $reportingManager->name . ' (' . $reportingManager->emp_id . ')';
                }
            }

            // Get function, department, vertical names (you need to implement these based on your tables)
            $functionName = $this->getFunctionName($candidate->function_id);
            $departmentName = $this->getDepartmentName($candidate->department_id);
            $verticalName = $this->getVerticalName($candidate->vertical_id);

            // Format candidate data
            $formattedCandidate = [
                'basic_info' => [
                    'requisition_id' => $candidate->candidate_code,
                    'type' => $candidate->requisition_type,
                    'submitted_by' => $submittedByName,
                    'submission_date' => Carbon::parse($candidate->created_at)->format('d-m-Y H:i'),
                    'candidate_name' => $candidate->candidate_name,
                    'candidate_email' => $candidate->candidate_email,
                ],
                'employment_details' => [
                    'reporting_to' => $candidate->reporting_to,
                    'reporting_manager_id' => $candidate->reporting_manager_employee_id,
                    'reporting_manager_name' => $reportingManagerName,
                    'contract_start_date' => $candidate->contract_start_date ? Carbon::parse($candidate->contract_start_date)->format('d-m-Y') : 'N/A',
                    'contract_end_date' => $candidate->contract_end_date ? Carbon::parse($candidate->contract_end_date)->format('d-m-Y') : 'N/A',
                    'contract_duration' => $candidate->contract_duration ? $candidate->contract_duration . ' months' : 'N/A',
                    'remuneration' => $candidate->remuneration_per_month ? '₹ ' . number_format($candidate->remuneration_per_month, 2) . '/month' : 'N/A',
                    'fuel_reimbursement' => $candidate->fuel_reimbursement_per_month ? '₹ ' . number_format($candidate->fuel_reimbursement_per_month, 2) . '/month' : 'N/A',
                ],
                'personal_info' => [
                    'father_name' => $candidate->father_name,
                    'mobile_no' => $candidate->mobile_no,
                    'date_of_birth' => $candidate->date_of_birth ? Carbon::parse($candidate->date_of_birth)->format('d-m-Y') : 'N/A',
                    'gender' => $candidate->gender,
                    'address' => $candidate->address_line_1,
                    'city' => $candidate->city,
                    'state_residence' => $candidate->state_residence,
                    'pin_code' => $candidate->pin_code,
                    'highest_qualification' => $candidate->highest_qualification,
                ],
                'work_info' => [
                    'work_location_hq' => $candidate->work_location_hq,
                    'district' => $candidate->district,
                    'state_work_location' => $candidate->state_work_location,
                    'function' => $functionName,
                    'department' => $departmentName,
                    'vertical' => $verticalName,
                    'sub_department' => $candidate->sub_department,
                    'business_unit' => $candidate->business_unit,
                    'zone' => $candidate->zone,
                    'region' => $candidate->region,
                    'territory' => $candidate->territory,
                ],
                'kyc_info' => [
                    'pan_no' => $candidate->pan_no,
                    'aadhaar_no' => $candidate->aadhaar_no,
                    'bank_account_no' => $candidate->bank_account_no,
                    'bank_ifsc' => $candidate->bank_ifsc,
                    'bank_name' => $candidate->bank_name,
                    'account_holder_name' => $candidate->account_holder_name,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedCandidate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading candidate details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get candidate documents with S3 URLs
     */
    public function getCandidateDocuments($id)
    {
        try {
            // Get candidate to get requisition_id
            $candidate = CandidateMaster::findOrFail($id);
            $requisitionId = $candidate->requisition_id;

            // Get requisition documents using requisition_id
            $requisitionDocuments = [];
            if ($requisitionId) {
                $requisitionDocuments = RequisitionDocument::where('requisition_id', $requisitionId)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($doc) {
                        // Check if file_path exists and is not null
                        if (empty($doc->file_path) || $doc->file_path === 'null' || $doc->file_path === null) {
                            return [
                                'document_type' => $this->formatDocumentType($doc->document_type),
                                'file_name' => $doc->file_name,
                                'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                                'file_path' => null,
                                's3_url' => null,
                                'storage_disk' => 's3',
                                'has_file' => false,
                            ];
                        }

                        // Generate S3 URL only if file_path exists
                        try {
                            $s3Url = Storage::disk('s3')->url($doc->file_path);
                        } catch (\Exception $e) {
                            \Log::error("Error generating S3 URL for document {$doc->id}: " . $e->getMessage());
                            $s3Url = null;
                        }

                        return [
                            'document_type' => $this->formatDocumentType($doc->document_type),
                            'file_name' => $doc->file_name,
                            'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                            'file_path' => $doc->file_path,
                            's3_url' => $s3Url,
                            'storage_disk' => 's3',
                            'has_file' => true,
                        ];
                    });
            }

            // Get agreement documents using candidate_id
            $agreementDocuments = AgreementDocument::where('candidate_id', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($doc) {
                    // Check if file_path exists and is not null
                    if (empty($doc->file_path) || $doc->file_path === 'null' || $doc->file_path === null) {
                        return [
                            'document_type' => $this->formatDocumentType($doc->document_type),
                            'file_name' => $doc->file_name,
                            'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                            'file_path' => null,
                            's3_url' => null,
                            'storage_disk' => 's3',
                            'has_file' => false,
                        ];
                    }

                    // Generate S3 URL only if file_path exists
                    try {
                        $s3Url = Storage::disk('s3')->url($doc->file_path);
                    } catch (\Exception $e) {
                        \Log::error("Error generating S3 URL for agreement document {$doc->id}: " . $e->getMessage());
                        $s3Url = null;
                    }

                    return [
                        'document_type' => $this->formatDocumentType($doc->document_type),
                        'file_name' => $doc->file_name,
                        'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                        'file_path' => $doc->file_path,
                        's3_url' => $s3Url,
                        'storage_disk' => 's3',
                        'has_file' => true,
                    ];
                });

            // Merge all documents
            $documents = $requisitionDocuments->merge($agreementDocuments);

            // Log for debugging
            \Log::info("Loaded documents for candidate {$id}: " . $documents->count() . " documents found");

            return response()->json([
                'success' => true,
                'data' => $documents,
                'total_documents' => $documents->count(),
                'documents_with_files' => $documents->where('has_file', true)->count(),
                'documents_without_files' => $documents->where('has_file', false)->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Error loading documents for candidate {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading documents: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display candidate details page
     */
    // In MyTeamController.php - update the showCandidatePage method:

    public function showCandidatePage($id)
    {
        try {
            $candidate = CandidateMaster::findOrFail($id);

            // Check if the candidate reports to the current user
            $user = Auth::user();
            if ($candidate->reporting_manager_employee_id != $user->emp_id) {
                abort(403, 'You are not authorized to view this candidate.');
            }

            // Get submitted by user name
            $submittedByName = 'Unknown';
            if ($candidate->created_by_user_id) {
                $submittedByUser = User::find($candidate->created_by_user_id);
                if ($submittedByUser) {
                    $submittedByName = $submittedByUser->name . ' (' . ($submittedByUser->emp_id ?? 'N/A') . ')';
                }
            }

            // Get reporting manager name
            $reportingManagerName = 'Unknown';
            if ($candidate->reporting_manager_employee_id) {
                $reportingManager = User::where('emp_id', $candidate->reporting_manager_employee_id)->first();
                if ($reportingManager) {
                    $reportingManagerName = $reportingManager->name . ' (' . $reportingManager->emp_id . ')';
                }
            }

            // Get function, department, vertical names
            $functionName = $this->getFunctionName($candidate->function_id);
            $departmentName = $this->getDepartmentName($candidate->department_id);
            $verticalName = $this->getVerticalName($candidate->vertical_id);

            // Get documents
            $documents = $this->getCandidateDocumentsData($id);
            //dd($candidate);
            return view('my-team.candidate-details', compact(
                'candidate',
                'submittedByName',
                'reportingManagerName',
                'functionName',
                'departmentName',
                'verticalName',
                'documents'
            ));
        } catch (\Exception $e) {
            abort(404, 'Candidate not found or unauthorized.');
        }
    }

    /**
     * Get candidate documents data
     */
    private function getCandidateDocumentsData($candidateId)
    {
        $candidate = CandidateMaster::find($candidateId);
        $requisitionId = $candidate->requisition_id;

        $documents = [];

        // Get requisition documents
        if ($requisitionId) {
            $requisitionDocs = RequisitionDocument::where('requisition_id', $requisitionId)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($requisitionDocs as $doc) {
                $s3Url = null;
                if (!empty($doc->file_path) && $doc->file_path !== 'null') {
                    try {
                        $s3Url = Storage::disk('s3')->url($doc->file_path);
                    } catch (\Exception $e) {
                        \Log::error("Error generating S3 URL for requisition document: " . $e->getMessage());
                    }
                }

                $documents[] = [
                    'type' => 'Requisition',
                    'document_type' => $this->formatDocumentType($doc->document_type),
                    'file_name' => $doc->file_name,
                    'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                    's3_url' => $s3Url,
                    'has_file' => !empty($doc->file_path) && $doc->file_path !== 'null' && !empty($s3Url),
                ];
            }
        }

        // Get agreement documents - FIXED: Use agreement_path instead of file_path
        $agreementDocs = AgreementDocument::where('candidate_id', $candidateId)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($agreementDocs as $doc) {
            $s3Url = null;
            if (!empty($doc->agreement_path) && $doc->agreement_path !== 'null') { // Changed to agreement_path
                try {
                    $s3Url = Storage::disk('s3')->url($doc->agreement_path); // Changed to agreement_path
                } catch (\Exception $e) {
                    \Log::error("Error generating S3 URL for agreement document: " . $e->getMessage());
                }
            }

            $documents[] = [
                'type' => 'Agreement',
                'document_type' => $this->formatDocumentType($doc->document_type),
                'file_name' => 'Agreement_' . $doc->agreement_number . '.pdf', // Add meaningful file name
                'uploaded_at' => Carbon::parse($doc->created_at)->format('d-m-Y H:i'),
                's3_url' => $s3Url,
                'has_file' => !empty($doc->agreement_path) && $doc->agreement_path !== 'null' && !empty($s3Url), // Changed to agreement_path
            ];
        }

        return $documents;
    }

    /**
     * Format document type for display
     */
    private function formatDocumentType($type)
    {
        $formatted = str_replace('_', ' ', $type);
        return ucwords($formatted);
    }

    /**
     * Get function name (implement based on your functions table)
     */
    private function getFunctionName($functionId)
    {
        // Example implementation - adjust based on your tables
        $functions = [
            1 => 'Sales and Marketing',
            2 => 'Operations',
            3 => 'Human Resources',
            4 => 'Finance',
            5 => 'IT',
        ];

        return $functions[$functionId] ?? 'N/A';
    }

    /**
     * Get department name (implement based on your departments table)
     */
    private function getDepartmentName($departmentId)
    {
        // Example implementation - adjust based on your tables
        $departments = [
            15 => 'Sales',
            16 => 'Marketing',
            17 => 'Operations',
            18 => 'HR',
            19 => 'Finance',
        ];

        return $departments[$departmentId] ?? 'N/A';
    }

    /**
     * Get vertical name (implement based on your verticals table)
     */
    private function getVerticalName($verticalId)
    {
        // Example implementation - adjust based on your tables
        $verticals = [
            1 => 'Corporate',
            2 => 'Veg Crop',
            3 => 'Non Veg Crop',
            4 => 'Specialty',
        ];

        return $verticals[$verticalId] ?? 'N/A';
    }

     public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $empId = $user->emp_id;
            
            // Get filter parameters from request
            $search = $request->get('search', '');
            $type = $request->get('type', 'all');
            $status = $request->get('status', 'all');
            
            // Get filtered data
            $query = $this->getFilteredQuery($empId, $search, $type, $status);
            $candidates = $query->get();
            
            // Generate filename with timestamp
            $filename = 'my_team_' . $user->emp_id . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Export using Excel
            return Excel::download(new MyTeamExport($candidates), $filename);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }
    
    /**
     * Get filtered query for export
     */
    private function getFilteredQuery($empId, $search = '', $type = 'all', $status = 'all')
    {
        $query = CandidateMaster::select([
                'id',
                'candidate_code',
                'candidate_name',
                'candidate_email',
                'mobile_no',
                'requisition_type',
                'requisition_id',
                'work_location_hq',
                'contract_start_date',
                'remuneration_per_month',
                'candidate_status',
                'final_status',
                'reporting_manager_employee_id',
                'reporting_to',
                'created_by_user_id',
                'created_at',
                'contract_end_date',
                'contract_duration',
                'fuel_reimbursement_per_month',
                'father_name',
                'date_of_birth',
                'gender',
                'address_line_1',
                'city',
                'state_residence',
                'pin_code',
                'highest_qualification',
                'district',
                'state_work_location',
                'function_id',
                'department_id',
                'vertical_id',
                'sub_department',
                'business_unit',
                'zone',
                'region',
                'territory',
                'pan_no',
                'aadhaar_no',
                'bank_account_no',
                'bank_ifsc',
                'bank_name',
                'account_holder_name',
            ])
            ->where('final_status', 'A')
            ->where('reporting_manager_employee_id', $empId);

        // Apply search filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_code', 'like', "%{$search}%")
                    ->orWhere('candidate_name', 'like', "%{$search}%")
                    ->orWhere('candidate_email', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if ($type !== 'all') {
            $query->where('requisition_type', $type);
        }

        // Apply status filter
        if ($status !== 'all') {
            $query->where('final_status', $status);
        }

        return $query->orderBy('candidate_name');
    }
}
