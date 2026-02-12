<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\CandidateMaster;
use App\Models\LeaveBalance;
use App\Models\RequisitionDocument;
use App\Models\CoreFunction;
use App\Models\CoreDepartment;
use App\Models\CoreState;
use App\Models\CoreBusinessUnit;
use App\Models\CoreZone;
use App\Models\CoreRegion;
use App\Models\CoreTerritory;
use App\Models\CoreCityVillage;
use App\Models\MasterEducation;
use App\Models\CoreVertical;
use App\Models\CoreSubDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use DateTime;

class ImportController extends Controller
{
	private $lookupCache = [];
	private $importedRows = [];
	private $importErrors = [];

	public function showImportPage()
	{
		return view('import.candidates');
	}

	public function previewExcel(Request $request)
	{
		ini_set('memory_limit', '1024M');
		set_time_limit(300);

		// DISABLE MOST LOGGING TO PREVENT MEMORY EXHAUSTION
		\Log::debug('Memory limit increased for import');

		\Log::info('=== PREVIEW EXCEL STARTED ===');
		\Log::info('User ID: ' . auth()->id());
		\Log::info('Has file: ' . ($request->hasFile('excel_file') ? 'YES' : 'NO'));
		$requisitionType = $request->requisition_type;
		$request->validate([
			'excel_file' => 'required|mimes:xlsx,xls,csv|max:2048',
			'requisition_type' => 'required|in:Contractual,TFA,CB'
		]);

		try {
			\Log::info('Starting Excel processing...');

			$file = $request->file('excel_file');
			\Log::info('File details:', [
				'name' => $file->getClientOriginalName(),
				'extension' => $file->getClientOriginalExtension(),
				'size' => $file->getSize(),
				'mime' => $file->getMimeType()
			]);

			// TRY 1: First attempt with error handling
			try {
				\Log::info('Attempting to read Excel file...');
				$data = Excel::toArray([], $file);

				\Log::info('Excel read successfully');
				\Log::info('Number of sheets: ' . count($data));

				if (!empty($data) && isset($data[0])) {
					\Log::info('First sheet has ' . count($data[0]) . ' rows');

					// Log first few rows to see structure
					for ($i = 0; $i < min(3, count($data[0])); $i++) {
						\Log::info("Row $i: " . json_encode($data[0][$i]));
					}
				}
			} catch (\Maatwebsite\Excel\Exceptions\NoTypeDetectedException $e) {
				\Log::error('Excel type detection failed: ' . $e->getMessage());
				return response()->json([
					'success' => false,
					'message' => 'Invalid Excel file format'
				], 400);
			} catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
				\Log::error('PhpSpreadsheet reader error: ' . $e->getMessage());
				return response()->json([
					'success' => false,
					'message' => 'Error reading Excel file: ' . $e->getMessage()
				], 400);
			} catch (\Exception $e) {
				\Log::error('General Excel reading error: ' . $e->getMessage());
				return response()->json([
					'success' => false,
					'message' => 'Error processing Excel file'
				], 400);
			}

			if (empty($data) || empty($data[0])) {
				\Log::error('Excel file is empty or has no data');
				return response()->json([
					'success' => false,
					'message' => 'Excel file is empty or has no data'
				], 400);
			}

			// TRY 2: Check if we have headers
			$headers = $data[0][0] ?? [];
			\Log::info('Headers found: ' . json_encode($headers));
			\Log::info('Number of columns: ' . count($headers));

			$this->importedRows = [];
			$this->importErrors = [];

			// TRY 3: Attempt to preload lookup data
			try {
				\Log::info('Preloading lookup data...');
				$this->preloadLookupData();
				\Log::info('Lookup data preloaded successfully');
			} catch (\Exception $e) {
				\Log::error('Failed to preload lookup data: ' . $e->getMessage());
				return response()->json([
					'success' => false,
					'message' => 'Database connection issue'
				], 500);
			}

			$previewData = [];

			// TRY 4: Process rows with detailed error handling
			foreach ($data[0] as $index => $row) {
				try {
					if ($index == 0) {
						\Log::info('Skipping header row');
						continue;
					}

					\Log::info("Processing row $index");

					// Check if row has enough data
					if (count($row) < 10) {
						\Log::warning("Row $index has insufficient columns: " . count($row));
					}

					$cleanData = $this->cleanRowData($row, $headers);
					\Log::debug("Row $index cleaned data keys: " . implode(', ', array_keys($cleanData)));

					// Skip empty rows
					if (empty($cleanData["Candidate's Name"]) && empty($cleanData['Mobile No.'])) {
						\Log::info("Row $index skipped - empty name and mobile");
						continue;
					}

					// Validate row
					\Log::info("Validating row $index");
					$validation = $this->validateRowData($cleanData);

					if ($validation['valid']) {
						\Log::info("Row $index is valid");
						$lookupIds = $this->getLookupIds($cleanData);

						$previewData[] = [
							'row_index' => $index,
							'data' => $cleanData,
							'lookup_ids' => $lookupIds,
							'valid' => true,
							'status' => 'Approved',
							'can_import' => true
						];

						$this->importedRows[] = [
							'data' => $cleanData,
							'lookup_ids' => $lookupIds
						];
					} else {
						\Log::info("Row $index validation failed: " . implode(', ', $validation['errors']));
						$previewData[] = [
							'row_index' => $index,
							'data' => $cleanData,
							'valid' => false,
							'errors' => $validation['errors'],
							'status' => 'Validation Failed',
							'can_import' => false
						];

						$this->importErrors[] = [
							'row' => $index,
							'errors' => $validation['errors']
						];
					}
				} catch (\Exception $e) {
					\Log::error("Error processing row $index: " . $e->getMessage());
					\Log::error("Row data: " . json_encode($row));

					$previewData[] = [
						'row_index' => $index,
						'valid' => false,
						'errors' => ['Processing error: ' . $e->getMessage()],
						'status' => 'Processing Error',
						'can_import' => false
					];
				}
			}

			// Store preview data in session for import
			session(['import_preview_data' => $this->importedRows]);
			session(['import_errors' => $this->importErrors]);

			\Log::info('Preview processing completed');
			\Log::info('Total rows processed: ' . count($previewData));
			\Log::info('Valid rows: ' . count($this->importedRows));
			\Log::info('Invalid rows: ' . count($this->importErrors));
			session(['import_requisition_type' => $requisitionType]);
			$responseData = [
				'success' => true,
				'preview' => array_values($previewData), // Re-index to ensure it's a proper array
				'total_rows' => count($previewData),
				'valid_rows' => count($this->importedRows),
				'invalid_rows' => count($this->importErrors)
			];
			\Log::info('Preview response prepared', [
				'preview_count' => count($previewData),
				'sample_row' => isset($previewData[0]) ? array_keys($previewData[0]) : 'none'
			]);

			return response()->json($responseData);
		} catch (\Exception $e) {
			\Log::error('Candidate Import Preview Failed - Full Trace:', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
				'user_id' => auth()->id(),
			]);
			\Log::error('ERROR DETAILS: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());


			return response()->json([
				'success' => false,
				'message' => 'File processing failed: ' . $e->getMessage()
			], 500);
		}
	}

	public function importData(Request $request)
	{
		\Log::info('=== IMPORT DATA STARTED ===');
		\Log::info('User ID: ' . auth()->id());
		$user = Auth::user();
		$requisitionType = session('import_requisition_type');

		$importData = session('import_preview_data', []);

		\Log::info('Import data count from session: ' . count($importData));

		if (empty($importData)) {
			\Log::error('No data to import');
			return response()->json([
				'success' => false,
				'message' => 'No data to import. Please upload and preview the file first.'
			]);
		}

		// Initialize results array
		$results = [
			'success' => 0,
			'failed' => 0,
			'imported' => [],
			'errors' => []
		];

		foreach ($importData as $index => $rowData) {
			DB::beginTransaction();
			try {
				$result = $this->importSingleRow($rowData, $user, $requisitionType);
				$results['success']++;
				$results['imported'][] = $result;
				DB::commit();
			} catch (\Exception $e) {
				DB::rollBack();
				$results['failed']++;
				$results['errors'][] = "Row " . ($index + 1) . ": " . $e->getMessage();
				\Log::error("Row import failed", ['row' => $index + 1, 'error' => $e->getMessage()]);
			}
		}

		// Store results in session for display
		session(['import_results' => $results]);

		return response()->json([
			'success' => true,
			'message' => 'Import completed',
			'data' => [
				'success' => $results['success'],
				'failed' => $results['failed'],
				'imported' => $results['imported'],
				'errors' => $results['errors']
			]
		]);
	}
	public function showImportResults()
	{
		$results = session('import_results', []);
		$importedCandidates = $results['imported'] ?? [];

		// Clear session data after displaying
		session()->forget(['import_preview_data', 'import_errors', 'import_results']);

		return view('import.results', compact('importedCandidates', 'results'));
	}

	public function getImportedCandidates(Request $request)
	{
		$user = Auth::user();

		// ✅ FIX: Show ONLY Excel imports - SIMPLE AND CLEAN
		$query = ManpowerRequisition::with('candidate')
			->where('submitted_by_employee_id', 'SYS-IMPORT') // Only SYS-IMPORT = Excel imports
			->whereDate('created_at', '>=', Carbon::today()->subDays(30))
			->orderBy('created_at', 'desc');

		// Search functionality
		if ($request->has('search') && !empty($request->search)) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('candidate_name', 'LIKE', "%{$search}%")
					->orWhere('candidate_email', 'LIKE', "%{$search}%")
					->orWhere('requisition_id', 'LIKE', "%{$search}%")
					->orWhere('mobile_no', 'LIKE', "%{$search}%");
			});
		}

		$requisitions = $query->paginate(20);

		\Log::info('Found ' . $requisitions->total() . ' imported candidates');

		// Get document counts for each requisition
		foreach ($requisitions as $requisition) {
			$requisition->document_count = RequisitionDocument::where('requisition_id', $requisition->id)
				->count();
			$requisition->has_all_documents = $this->checkAllDocumentsUploaded($requisition->id);

			// Get candidate code if exists
			if ($requisition->candidate) {
				$requisition->candidate_code = $requisition->candidate->candidate_code;
				$requisition->candidate_name = $requisition->candidate->candidate_name;
				$requisition->candidate_email = $requisition->candidate->candidate_email;
				$requisition->mobile_no = $requisition->candidate->mobile_no;
				$requisition->contract_start_date = $requisition->candidate->contract_start_date;
			}
		}

		return response()->json([
			'success' => true,
			'candidates' => $requisitions,
			'pagination' => [
				'current_page' => $requisitions->currentPage(),
				'last_page' => $requisitions->lastPage(),
				'total' => $requisitions->total(),
				'per_page' => $requisitions->perPage()
			]
		]);
	}
	public function getCandidateDocuments($requisitionId)
	{
		$requisition = ManpowerRequisition::with('candidate')->findOrFail($requisitionId);
		$documents = RequisitionDocument::where('requisition_id', $requisitionId)->get();

		$documentTypes = [
			'pan_card' => [
				'name' => 'PAN Card',
				'uploaded' => false,
				'field_label' => 'PAN Number',
				'field_name' => 'pan_no',
				'field_value' => $requisition->candidate->pan_no ?? $requisition->pan_no ?? '',
				'icon' => 'ri-id-card-line'
			],
			'aadhaar_card' => [
				'name' => 'Aadhaar Card',
				'uploaded' => false,
				'field_label' => 'Aadhaar Number',
				'field_name' => 'aadhaar_no',
				'field_value' => $requisition->candidate->aadhaar_no ?? $requisition->aadhaar_no ?? '',
				'icon' => 'ri-fingerprint-line'
			],
			'bank_document' => [
				'name' => 'Bank Document',
				'uploaded' => false,
				'field_label' => 'Bank Details',
				'field_name' => 'bank_details',
				'sub_fields' => [
					'account_holder_name' => [
						'label' => 'Account Holder Name',
						'value' => $requisition->candidate->account_holder_name ?? $requisition->account_holder_name ?? $requisition->candidate_name ?? ''
					],
					'bank_account_no' => [
						'label' => 'Account Number',
						'value' => $requisition->candidate->bank_account_no ?? $requisition->bank_account_no ?? ''
					],
					'bank_ifsc' => [
						'label' => 'IFSC Code',
						'value' => $requisition->candidate->bank_ifsc ?? $requisition->bank_ifsc ?? ''
					],
					'bank_name' => [
						'label' => 'Bank Name',
						'value' => $requisition->candidate->bank_name ?? $requisition->bank_name ?? ''
					]
				],
				'icon' => 'ri-bank-line'
			],
			'resume' => [
				'name' => 'Resume',
				'uploaded' => false,
				'icon' => 'ri-file-text-line'
			],
			'driving_licence' => [
				'name' => 'Driving Licence',
				'uploaded' => false,
				'field_label' => 'Driving Licence Number',
				'field_name' => 'driving_licence_no',
				'field_value' => $requisition->candidate->driving_licence_no ?? '',
				'icon' => 'ri-car-line'
			],
			'other' => [
				'name' => 'Other Document',
				'uploaded' => false,
				'icon' => 'ri-file-copy-line'
			]
		];

		foreach ($documents as $doc) {
			if (isset($documentTypes[$doc->document_type])) {
				$documentTypes[$doc->document_type]['uploaded'] = true;
				$documentTypes[$doc->document_type]['document'] = $doc;
			}
		}

		return response()->json([
			'success' => true,
			'documents' => $documentTypes,
			'requisition' => [
				'id' => $requisition->id,
				'requisition_id' => $requisition->requisition_id,
				'requisition_type' => $requisition->requisition_type,
				'candidate_name' => $requisition->candidate_name ?? $requisition->candidate->candidate_name ?? '',
				'candidate_id' => $requisition->candidate->id ?? null,
				'candidate_code' => $requisition->candidate->candidate_code ?? ''
			]
		]);
	}

	public function uploadDocument(Request $request)
	{
		$request->validate([
			'requisition_id' => 'required|exists:manpower_requisitions,id',
			'document_type' => 'required|in:pan_card,aadhaar_card,bank_document,resume,driving_licence,other',
			'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'
		]);

		try {
			$user = Auth::user();
			$requisition = ManpowerRequisition::findOrFail($request->requisition_id);

			// Check if user has permission to upload for this requisition
			if ($requisition->submitted_by_user_id != $user->id) {
				return response()->json([
					'success' => false,
					'message' => 'You are not authorized to upload documents for this requisition'
				], 403);
			}

			// Check if document already exists
			$existingDoc = RequisitionDocument::where('requisition_id', $request->requisition_id)
				->where('document_type', $request->document_type)
				->first();

			if ($existingDoc) {
				// Delete old file from S3
				Storage::disk('s3')->delete($existingDoc->file_path);
				$existingDoc->delete();
			}

			// Upload to S3
			$file = $request->file('document_file');
			$originalName = $file->getClientOriginalName();
			$extension = $file->getClientOriginalExtension();

			// Generate unique filename
			$filename = pathinfo($originalName, PATHINFO_FILENAME);
			$uniqueFilename = $filename . '_' . time() . '.' . $extension;

			// Generate S3 path
			$s3Path = $this->generateS3Path($requisition->requisition_type, $request->document_type, $uniqueFilename);

			// Upload to S3
			Storage::disk('s3')->put($s3Path, file_get_contents($file));

			// Create document record
			$document = RequisitionDocument::create([
				'requisition_id' => $requisition->id,
				'document_type' => $request->document_type,
				'file_name' => $originalName,
				'file_path' => $s3Path,
				'uploaded_by_user_id' => $user->id
			]);

			return response()->json([
				'success' => true,
				'message' => 'Document uploaded successfully',
				'document' => $document
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Upload failed: ' . $e->getMessage()
			], 500);
		}
	}

	public function deleteDocument($documentId)
	{
		try {
			$user = Auth::user();
			$document = RequisitionDocument::findOrFail($documentId);

			// Check if user has permission
			$requisition = ManpowerRequisition::find($document->requisition_id);

			if (!$requisition || $requisition->submitted_by_user_id != $user->id) {
				return response()->json([
					'success' => false,
					'message' => 'You are not authorized to delete this document'
				], 403);
			}

			// Delete from S3
			Storage::disk('s3')->delete($document->file_path);

			// Delete record
			$document->delete();

			return response()->json([
				'success' => true,
				'message' => 'Document deleted successfully'
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Delete failed: ' . $e->getMessage()
			], 500);
		}
	}

	public function downloadDocument($documentId)
	{
		try {
			$user = Auth::user();
			$document = RequisitionDocument::findOrFail($documentId);

			// Check if user has permission
			$requisition = ManpowerRequisition::find($document->requisition_id);

			if (!$requisition || $requisition->submitted_by_user_id != $user->id) {
				abort(403, 'Unauthorized access');
			}

			// Get file from S3
			$file = Storage::disk('s3')->get($document->file_path);

			// Return file download
			return response($file)
				->header('Content-Type', Storage::disk('s3')->mimeType($document->file_path))
				->header('Content-Disposition', 'attachment; filename="' . $document->file_name . '"');
		} catch (\Exception $e) {
			abort(404, 'Document not found');
		}
	}

	private function importSingleRow($rowData, $user, $requisitionType)
	{
		\Log::info('=== IMPORT SINGLE ROW STARTED ===');

		$data = $rowData['data'];
		$lookupIds = $rowData['lookup_ids'];

		// Get email from either field
		$email = null;
		if (!empty($data['E Mail'])) {
			$email = $data['E Mail'];
		} elseif (!empty($data['Email Address'])) {
			$email = $data['Email Address'];
		}

		\Log::info('Candidate name: ' . ($data["Candidate's Name"] ?? 'N/A'));
		\Log::info('Email: ' . ($email ?? 'NULL - will be inserted as null'));

		// Check for duplicates - only if email exists
		\Log::info('Checking for duplicates...');
		if ($this->candidateExists($data)) {
			\Log::error('Candidate already exists');
			throw new \Exception('Candidate already exists (duplicate email or mobile)');
		}

		// Generate the requisition ID
		$requisitionId = ManpowerRequisition::generateRequisitionId(
			$requisitionType,
			'IMPORT'
		);
		\Log::info('Generated requisition ID: ' . $requisitionId);

		// Create Manpower Requisition
		\Log::info('Creating manpower requisition...');
		$requisitionData = $this->prepareManpowerRequisitionData($data, $lookupIds, $user, $requisitionType);
		$requisitionData['requisition_id'] = $requisitionId;

		try {
			$manpowerRequisition = ManpowerRequisition::create($requisitionData);
			\Log::info('Manpower requisition created with ID: ' . $manpowerRequisition->id);
		} catch (\Exception $e) {
			\Log::error('Failed to create manpower requisition: ' . $e->getMessage());
			throw $e;
		}

		// Generate candidate code
		$candidateCode = $this->generateCandidateCode($requisitionType, $lookupIds);
		\Log::info('Generated candidate code: ' . $candidateCode);

		// Create Candidate Master
		\Log::info('Creating candidate master...');
		$candidateData = $this->prepareCandidateMasterData(
			$data,
			$lookupIds,
			$manpowerRequisition->id,
			$requisitionType,
			$candidateCode
		);

		try {
			$candidate = CandidateMaster::create($candidateData);
			\Log::info('Candidate master created with ID: ' . $candidate->id . ' and candidate_code: ' . $candidate->candidate_code);
		} catch (\Exception $e) {
			\Log::error('Failed to create candidate master: ' . $e->getMessage());
			throw $e;
		}

		// Create Leave Balance only for Contractual
		if ($requisitionType === 'Contractual') {
			$this->createLeaveBalance($candidate->id, $data['Date of Joining Required']);
		}

		\Log::info('=== ROW IMPORT COMPLETED SUCCESSFULLY ===');

		return [
			'success' => true,
			'candidate_code' => $candidate->candidate_code,
			'candidate_name' => $candidate->candidate_name,
			'candidate_email' => $candidate->candidate_email, // This will be NULL
			'requisition_id' => $manpowerRequisition->requisition_id,
			'candidate_id' => $candidate->id
		];
	}
	private function validateRowData($data)
	{
		$errors = [];

		// CRITICAL FIX: Get email from E Mail field (which should have been mapped from Email Address in cleanRowData)
		$email = $data['E Mail'] ?? null;

		// If still no email, check if there's an Email Address field directly (fallback)
		if (empty($email) && !empty($data['Email Address'])) {
			$email = $data['Email Address'];
			\Log::info("Using Email Address fallback: " . $email);
		}

		// Required fields validation
		$requiredFields = [
			"Candidate's Name" => $data["Candidate's Name"] ?? null,
			'Mobile No.' => $data['Mobile No.'] ?? null,
			'Date of Joining Required' => $data['Date of Joining Required'] ?? null,
			'Reporting To' => $data['Reporting To'] ?? null,
		];

		foreach ($requiredFields as $field => $value) {
			if (empty($value) && $value !== '0') {
				$errors[] = "Required field '{$field}' is empty";
			}
		}

		// Email validation - ONLY check if email exists, don't make it required
		if (!empty($email)) {
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$errors[] = "Invalid email format: {$email}";
			}
		} else {
			// Just log that email is missing, don't fail validation
			\Log::info("Email is empty for row - will be inserted as NULL");
		}

		// Validate mobile number
		if (!empty($data['Mobile No.'])) {
			$mobile = preg_replace('/[^0-9]/', '', (string)$data['Mobile No.']);
			if (strlen($mobile) < 10) {
				$errors[] = 'Invalid mobile number format (must be at least 10 digits)';
			}
		}

		// Validate dates
		$joiningDate = $this->parseExcelDate($data['Date of Joining Required'] ?? null);
		$separationDate = $this->parseExcelDate($data['Date of Separation'] ?? null);

		if (!$joiningDate) {
			$errors[] = 'Invalid Date of Joining format';
		}

		if ($separationDate && $joiningDate && $separationDate <= $joiningDate) {
			$errors[] = 'Date of Separation must be after Date of Joining';
		}

		return [
			'valid' => empty($errors),
			'errors' => $errors
		];
	}
	private function checkAllDocumentsUploaded($requisitionId)
	{
		$requiredDocs = ['pan_card', 'aadhaar_card', 'bank_document'];
		$uploadedDocs = RequisitionDocument::where('requisition_id', $requisitionId)
			->whereIn('document_type', $requiredDocs)
			->pluck('document_type')
			->toArray();

		return count(array_intersect($requiredDocs, $uploadedDocs)) === count($requiredDocs);
	}

	private function generateS3Path($requisitionType, $documentType, $filename)
	{
		$basePath = "Consultancy/Requisitions/";
		$typePath = strtoupper($requisitionType);
		$docPath = str_replace('_', '-', $documentType);

		return $basePath . $typePath . '/' . $docPath . '/' . $filename;
	}


	private function preloadLookupData()
	{
		try {
			\Log::info('Preloading states...');
			$this->lookupCache['states'] = CoreState::where('is_active', 1)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->state_name));
				});
			\Log::info('States loaded: ' . $this->lookupCache['states']->count());

			\Log::info('Preloading qualifications...');
			$this->lookupCache['qualifications'] = MasterEducation::where('Status', 'A')
				->where('IsDeleted', 0)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->EducationName));
				});
			\Log::info('Qualifications loaded: ' . $this->lookupCache['qualifications']->count());

			\Log::info('Preloading functions...');
			$this->lookupCache['functions'] = CoreFunction::where('is_active', 1)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->function_name));
				});
			\Log::info('Functions loaded: ' . $this->lookupCache['functions']->count());

			\Log::info('Preloading departments...');
			$this->lookupCache['departments'] = CoreDepartment::where('is_active', 1)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->department_name));
				});
			\Log::info('Departments loaded: ' . $this->lookupCache['departments']->count());

			\Log::info('Preloading verticals...');
			$this->lookupCache['verticals'] = CoreVertical::where('is_active', 1)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->vertical_name));
				});
			\Log::info('Verticals loaded: ' . $this->lookupCache['verticals']->count());

			\Log::info('Preloading business units...');
			$this->lookupCache['business_units'] = CoreBusinessUnit::where('is_active', 1)
				->get()
				->keyBy(function ($item) {
					return strtolower(trim($item->business_unit_name));
				});
			\Log::info('Business units loaded: ' . $this->lookupCache['business_units']->count());
		} catch (\Exception $e) {
			\Log::error('Error in preloadLookupData: ' . $e->getMessage());
			throw new \Exception('Failed to load lookup data: ' . $e->getMessage());
		}
	}

	private function cleanRowData($row, $headers = null)
	{
		$cleanData = [];

		// If headers are provided (first row of Excel), use them for mapping
		if ($headers) {
			foreach ($headers as $index => $header) {
				// Skip if header is empty
				if (empty($header) && $header !== 0) {
					continue;
				}

				$value = isset($row[$index]) ? $row[$index] : null;

				if (is_string($value)) {
					$value = trim(preg_replace('/\s+/', ' ', $value));
					if ($value === '' || in_array(strtolower($value), ['null', 'na', 'n/a', '-', 'nil'])) {
						$value = null;
					}
				}

				// Normalize the header - remove extra spaces
				$normalizedHeader = trim(preg_replace('/\s+/', ' ', $header));

				// CRITICAL FIX: YOUR EXCEL USES "Email Address" NOT "E Mail"
				// Map "Email Address" to "E Mail" for consistency
				if ($normalizedHeader === 'Email Address') {
					$normalizedHeader = 'E Mail';
				}

				// FIX: Handle Reporting Manager Employee ID with extra spaces
				if (strpos($normalizedHeader, 'Reporting Manager Employee') === 0) {
					$normalizedHeader = 'Reporting Manager Employee ID';
				}

				// Handle duplicate State headers explicitly
				if ($normalizedHeader === 'State') {
					if (!isset($cleanData['State_Work'])) {
						$cleanData['State_Work'] = $value;        // first State
					} else {
						$cleanData['State_Residence'] = $value;   // second State
					}
				} else {
					$cleanData[$normalizedHeader] = $value;
				}
			}

			unset($cleanData['State']);
		} else {
			// Fallback mapping for when headers are not provided
			$mapping = [
				0 => 'Timestamp',
				1 => 'Email Address',
				2 => 'Candidate\'s Name',
				3 => 'Fathers Name',
				4 => 'Function',
				5 => 'Vertical',
				6 => 'Department',
				7 => 'Sub-department',
				8 => 'Work Location/HQ',
				9 => 'District',
				10 => 'State',  // Work state
				11 => 'Business Unit',
				12 => 'Zone',
				13 => 'Region',
				14 => 'Territory',
				15 => 'Mobile No.',
				16 => 'E Mail',
				17 => 'Address Line 1',
				18 => 'City',
				19 => 'State', // Residence state
				20 => 'Pin Code',
				21 => 'Date of Birth',
				22 => 'Gender',
				23 => 'Highest Qualification',
				24 => 'Reporting To',
				25 => 'Date of Joining Required',
				26 => 'Date of Separation',
				27 => 'Remuneration (per month)',
				28 => 'Please specify the address of Reporting Manager for dispatching the Agreement with complete detail (PIN and Phone No)',
				29 => 'Approval from ZBM/GM',
				30 => 'Resume of the Candidate',
				31 => 'Aadhaar Card',
				32 => 'Driving Licence',
				33 => 'Bank Passbook or Cancelled Cheque',
				34 => 'Bank Account No.',
				35 => 'Bank IFSC',
				36 => 'Bank Name',
				37 => 'Other Document',
				38 => 'Reporting Manager Employee ID',
				39 => 'Approval',
				40 => 'Document verification'
			];

			foreach ($mapping as $index => $columnName) {
				$value = isset($row[$index]) ? $row[$index] : null;

				if (is_string($value)) {
					$value = trim($value);
					$value = preg_replace('/\s+/', ' ', $value);

					if ($value === '' || in_array(strtolower($value), ['null', 'na', 'n/a', '-', 'nil'])) {
						$value = null;
					}
				}

				$cleanData[$columnName] = $value;
			}

			// Resolve duplicate State columns only if those indices exist
			$cleanData['State_Work'] = isset($row[10]) ? $row[10] : null;
			$cleanData['State_Residence'] = isset($row[19]) ? $row[19] : null;

			// Remove ambiguous key
			unset($cleanData['State']);

			// Map Email Address to E Mail for consistency
			if (!empty($cleanData['Email Address']) && empty($cleanData['E Mail'])) {
				$cleanData['E Mail'] = $cleanData['Email Address'];
			}
			unset($cleanData['Email Address']);
		}

		return $cleanData;
	}
	private function candidateExists($data)
{
    // Get email from E Mail (mapped) or direct Email Address
    $email = null;
    if (!empty($data['E Mail'])) {
        $email = $data['E Mail'];
    } elseif (!empty($data['Email Address'])) {
        $email = $data['Email Address'];
    }
    
    $mobile = $data['Mobile No.'] ?? null;

    // Only check duplicate if email exists
    if ($email) {
        if (CandidateMaster::where('candidate_email', $email)->exists()) {
            \Log::debug('Candidate exists by email in CandidateMaster');
            return true;
        }
        if (ManpowerRequisition::where('candidate_email', $email)->exists()) {
            \Log::debug('Candidate exists by email in ManpowerRequisition');
            return true;
        }
    }

    // Mobile check (always required)
    if ($mobile) {
        $cleanMobile = preg_replace('/[^0-9]/', '', (string)$mobile);
        $cleanMobile = substr($cleanMobile, -10);

        if (CandidateMaster::where('mobile_no', $cleanMobile)->exists()) {
            \Log::debug('Candidate exists by mobile in CandidateMaster');
            return true;
        }
        if (ManpowerRequisition::where('mobile_no', $cleanMobile)->exists()) {
            \Log::debug('Candidate exists by mobile in ManpowerRequisition');
            return true;
        }
    }

    \Log::debug('Candidate does not exist');
    return false;
}
	private function getLookupIds($data)
	{
		return [
			'function_id' => $this->getFunctionId($data['Function'] ?? null),
			'department_id' => $this->getDepartmentId($data['Department'] ?? null),
			'state_residence_id' => $this->getStateId($data['State_Residence'] ?? null),
			'state_work_id'      => $this->getStateId($data['State_Work'] ?? null),
			'business_unit_id' => $this->getBusinessUnitId($data['Business Unit'] ?? null),
			'zone_id' => $this->getZoneId($data['Zone'] ?? null),
			'region_id' => $this->getRegionId($data['Region'] ?? null),
			'territory_id' => $this->getTerritoryId($data['Territory'] ?? null),
			'city_id' => $this->getCityId(
				$data['City'] ?? null,
				$data['State_Residence'] ?? null,
				null
			),
			'qualification_id' => $this->getQualificationId($data['Highest Qualification'] ?? null),
			'vertical_id' => $this->getVerticalId($data['Vertical'] ?? null),
			'sub_department_id' => $this->getSubDepartmentId($data['Sub-department'] ?? null),
		];
	}
	// ==================== LOOKUP FUNCTIONS ====================

	private function getFunctionId($functionName)
	{
		if (empty($functionName)) return null;

		$searchTerm = strtolower(trim($functionName));

		// Check cache first
		if (isset($this->lookupCache['functions'][$searchTerm])) {
			return $this->lookupCache['functions'][$searchTerm]->id;
		}

		// Try fuzzy matching
		$function = CoreFunction::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(function_name) LIKE ?', ["%{$searchTerm}%"])
					->orWhereRaw('LOWER(function_code) LIKE ?', ["%{$searchTerm}%"]);
			})
			->first();

		if ($function) {
			// Cache for future use
			$this->lookupCache['functions'][$searchTerm] = $function;
			return $function->id;
		}

		return null;
	}

	private function getDepartmentId($departmentName)
	{
		if (empty($departmentName)) return null;

		$searchTerm = strtolower(trim($departmentName));

		// Check cache first
		if (isset($this->lookupCache['departments'][$searchTerm])) {
			return $this->lookupCache['departments'][$searchTerm]->id;
		}

		// Common mappings
		$departmentMappings = [
			'sales' => 'Sales',
			'marketing' => 'Marketing',
			'hr' => 'Human Resources',
			'human resource' => 'Human Resources',
			'finance' => 'Finance',
			'accounts' => 'Finance',
			'it' => 'Information Technology',
			'information technology' => 'Information Technology',
			'administration' => 'Administration',
			'production' => 'Production',
			'quality' => 'Quality',
			'qa' => 'Quality',
			'research' => 'Research & Development',
			'r&d' => 'Research & Development',
			'r and d' => 'Research & Development',
		];

		if (isset($departmentMappings[$searchTerm])) {
			$searchTerm = strtolower($departmentMappings[$searchTerm]);
			if (isset($this->lookupCache['departments'][$searchTerm])) {
				return $this->lookupCache['departments'][$searchTerm]->id;
			}
		}

		// Try exact match first
		$department = CoreDepartment::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(department_name) = ?', [$searchTerm])
					->orWhereRaw('LOWER(department_code) = ?', [$searchTerm]);
			})
			->first();

		if (!$department) {
			// Try fuzzy matching
			$department = CoreDepartment::where('is_active', 1)
				->whereRaw('LOWER(department_name) LIKE ?', ["%{$searchTerm}%"])
				->first();
		}

		if ($department) {
			$this->lookupCache['departments'][$searchTerm] = $department;
			return $department->id;
		}

		return null;
	}

	private function getStateId($stateName, $columnIndex = null)
	{
		if (empty($stateName)) return null;

		$searchTerm = strtolower(trim($stateName));

		// Clean up the search term
		$searchTerm = rtrim($searchTerm, " -,."); // Remove trailing dashes, commas, spaces, dots

		\Log::debug('State lookup attempt', [
			'original' => $stateName,
			'cleaned' => $searchTerm,
			'column_index' => $columnIndex
		]);

		// Check cache first
		if (isset($this->lookupCache['states'][$searchTerm])) {
			return $this->lookupCache['states'][$searchTerm]->id;
		}

		// Enhanced abbreviations mapping
		$stateMappings = [
			'dnh' => 'dadra and nagar haveli',
			'dadra & nagar haveli' => 'dadra and nagar haveli',
			'dadra and nagar haveli' => 'dadra and nagar haveli',
			'utterpradesh' => 'uttar pradesh',
			'up' => 'uttar pradesh',
			'u.p.' => 'uttar pradesh',
			'u p' => 'uttar pradesh',
			'mp' => 'madhya pradesh',
			'm.p.' => 'madhya pradesh',
			'm p' => 'madhya pradesh',
			'cg' => 'chhattisgarh',
			'chhatisgarh' => 'chhattisgarh',
			'chattisgarh' => 'chhattisgarh',
			'mh' => 'maharashtra',
			'm.h.' => 'maharashtra',
			'm h' => 'maharashtra',
			'gj' => 'gujarat',
			'g.j.' => 'gujarat',
			'g j' => 'gujarat',
			'hr' => 'haryana',
			'h.r.' => 'haryana',
			'h r' => 'haryana',
			'rj' => 'rajasthan',
			'r.j.' => 'rajasthan',
			'r j' => 'rajasthan',
			'tn' => 'tamil nadu',
			't.n.' => 'tamil nadu',
			't n' => 'tamil nadu',
		];

		if (isset($stateMappings[$searchTerm])) {
			$searchTerm = $stateMappings[$searchTerm];
			if (isset($this->lookupCache['states'][$searchTerm])) {
				return $this->lookupCache['states'][$searchTerm]->id;
			}
		}

		// Try exact match
		$state = CoreState::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(state_name) = ?', [$searchTerm])
					->orWhereRaw('LOWER(state_code) = ?', [$searchTerm])
					->orWhereRaw('LOWER(short_code) = ?', [$searchTerm]);
			})
			->first();

		if (!$state) {
			// Try contains
			$state = CoreState::where('is_active', 1)
				->whereRaw('LOWER(state_name) LIKE ?', ["%{$searchTerm}%"])
				->first();
		}

		if ($state) {
			$this->lookupCache['states'][$searchTerm] = $state;
			return $state->id;
		}

		\Log::warning('State not found', [
			'search_term' => $searchTerm,
			'original' => $stateName
		]);

		return null;
	}

	private function getBusinessUnitId($businessUnitName)
	{
		if (empty($businessUnitName)) return null;

		$searchTerm = trim($businessUnitName);

		// Check cache
		$cacheKey = strtolower($searchTerm);
		if (isset($this->lookupCache['business_units'][$cacheKey])) {
			return $this->lookupCache['business_units'][$cacheKey]->id;
		}

		// Extract BU number from patterns like "BU 1 - SOUTH WEST - VC"
		if (preg_match('/BU\s*(\d+)/i', $searchTerm, $matches)) {
			$buNumber = $matches[1];

			$businessUnit = CoreBusinessUnit::where('is_active', 1)
				->where(function ($query) use ($buNumber, $searchTerm) {
					$query->where('numeric_code', $buNumber)
						->orWhere('business_unit_code', 'LIKE', "%{$buNumber}%")
						->orWhereRaw('LOWER(business_unit_name) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
				})
				->first();

			if ($businessUnit) {
				$this->lookupCache['business_units'][$cacheKey] = $businessUnit;
				return $businessUnit->id;
			}
		}

		// Try direct match
		$businessUnit = CoreBusinessUnit::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(business_unit_name) LIKE ?', ["%" . strtolower($searchTerm) . "%"])
					->orWhereRaw('LOWER(business_unit_code) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
			})
			->first();

		if ($businessUnit) {
			$this->lookupCache['business_units'][$cacheKey] = $businessUnit;
			return $businessUnit->id;
		}

		return null;
	}

	private function getZoneId($zoneName)
	{
		if (empty($zoneName)) return null;

		$searchTerm = trim($zoneName);

		// Extract zone from patterns like "FC - Zone 1"
		if (preg_match('/Zone\s*(\d+)/i', $searchTerm, $matches)) {
			$zoneNumber = $matches[1];

			$zone = CoreZone::where('is_active', 1)
				->where(function ($query) use ($zoneNumber, $searchTerm) {
					$query->where('zone_code', 'LIKE', "%Z{$zoneNumber}%")
						->orWhereRaw('LOWER(zone_name) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
				})
				->first();

			return $zone ? $zone->id : null;
		}

		// Try direct match
		$zone = CoreZone::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(zone_name) LIKE ?', ["%" . strtolower($searchTerm) . "%"])
					->orWhereRaw('LOWER(zone_code) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
			})
			->first();

		return $zone ? $zone->id : null;
	}

	private function normalizeRegionName($region)
	{
		if (empty($region)) return null;

		$region = strtolower(trim($region));

		// normalize hyphens
		$region = str_replace([' – ', ' - ', '–', '_'], '-', $region);

		// normalize ampersand spacing
		$region = str_replace([' & ', '&'], '&', $region);

		// remove extra spaces
		$region = preg_replace('/\s+/', ' ', $region);

		return trim($region);
	}


	private function getRegionId($regionName)
	{
		if (empty($regionName)) return null;

		$normalized = $this->normalizeRegionName($regionName);

		\Log::debug('Region lookup', [
			'excel' => $regionName,
			'normalized' => $normalized
		]);

		// 1️⃣ Exact normalized match
		$region = CoreRegion::where('is_active', 1)
			->whereRaw('LOWER(REPLACE(region_name, " ", "")) = ?', [
				str_replace(' ', '', $normalized)
			])
			->first();

		if ($region) return $region->id;

		// 2️⃣ LIKE match (safe fallback)
		$region = CoreRegion::where('is_active', 1)
			->whereRaw('LOWER(region_name) LIKE ?', ["%{$normalized}%"])
			->first();

		if ($region) return $region->id;

		// 3️⃣ Try removing FC / VC prefix from Excel
		$stripped = preg_replace('/^(fc|vc|rs)-?/i', '', $normalized);

		$region = CoreRegion::where('is_active', 1)
			->whereRaw('LOWER(region_name) LIKE ?', ["%{$stripped}%"])
			->first();

		return $region ? $region->id : null;
	}


	private function getTerritoryId($territoryName)
	{
		if (empty($territoryName)) return null;

		$searchTerm = trim($territoryName);

		$territory = CoreTerritory::where('is_active', 1)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(territory_name) LIKE ?', ["%" . strtolower($searchTerm) . "%"])
					->orWhereRaw('LOWER(territory_code) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
			})
			->first();

		return $territory ? $territory->id : null;
	}

	private function normalizeCityName($city)
	{
		if (empty($city)) return null;

		$city = strtolower(trim($city));

		// Remove pin codes (6 digits)
		$city = preg_replace('/\d{6}/', '', $city);

		// Remove any numbers
		$city = preg_replace('/[0-9]/', '', $city);

		// Remove special characters and extra text
		$city = preg_replace('/[-,\.\(\)]/', '', $city);

		// Remove state/district words
		$removeWords = [
			'dadra & nagar haveli',
			'dadra and nagar haveli',
			'dnh',
			'district',
			'dist',
			'dt',
			'tal',
			'taluka',
			'tahsil',
			'state',
			'pin',
			'pincode',
			'code',
		];

		foreach ($removeWords as $word) {
			$city = str_replace($word, '', $city);
		}

		// Clean up extra spaces
		$city = preg_replace('/\s+/', ' ', $city);
		$city = trim($city);

		\Log::debug('City normalization', [
			'original' => $city,
			'normalized' => $city
		]);

		return $city;
	}

	private function getCityId($cityName, $stateName, $stateColumnIndex)
	{
		if (empty($cityName)) return null;

		$normalizedCity = $this->normalizeCityName($cityName);
		$stateId = $this->getStateId($stateName, $stateColumnIndex);

		\Log::debug('City lookup', [
			'original' => $cityName,
			'normalized' => $normalizedCity,
			'state_name' => $stateName,
			'state_id' => $stateId
		]);

		if (!$normalizedCity) return null;

		// ✅ 1. Try with state if available
		if ($stateId) {
			$city = CoreCityVillage::where('is_active', 1)
				->where('state_id', $stateId)
				->whereRaw('LOWER(city_village_name) = ?', [$normalizedCity])
				->first();

			if ($city) return $city->id;
		}

		// ✅ 2. Try LIKE with state
		if ($stateId) {
			$city = CoreCityVillage::where('is_active', 1)
				->where('state_id', $stateId)
				->whereRaw('LOWER(city_village_name) LIKE ?', ["%{$normalizedCity}%"])
				->first();

			if ($city) return $city->id;
		}

		// ✅ 3. GLOBAL city fallback (NO state filter) - EXACT
		$city = CoreCityVillage::where('is_active', 1)
			->whereRaw('LOWER(city_village_name) = ?', [$normalizedCity])
			->first();

		if ($city) {
			\Log::warning('City matched without state', [
				'city' => $cityName,
				'normalized' => $normalizedCity,
				'derived_state_id' => $city->state_id
			]);
			return $city->id;
		}

		// ✅ 4. GLOBAL city fallback - LIKE
		$city = CoreCityVillage::where('is_active', 1)
			->whereRaw('LOWER(city_village_name) LIKE ?', ["%{$normalizedCity}%"])
			->first();

		if ($city) {
			\Log::warning('City matched (LIKE) without state', [
				'city' => $cityName,
				'normalized' => $normalizedCity,
				'derived_state_id' => $city->state_id
			]);
			return $city->id;
		}

		\Log::warning('City not found', [
			'city' => $cityName,
			'normalized' => $normalizedCity,
			'state_id' => $stateId
		]);

		return null;
	}


	private function normalizeQualification($value)
	{
		if (!$value) return null;

		$value = strtolower(trim($value));

		// normalize dots, multiple spaces
		$value = str_replace(['.', '  '], ['', ' '], $value);

		return $value;
	}

	private function mapQualificationKeyword($normalized)
	{
		$map = [

			// ===== BELOW 10th =====
			'iti' => 'below 10th',
			'iti copa' => 'below 10th',

			// ===== 10th / SSC =====
			'10' => 'secondary',
			'10th' => 'secondary',
			'ssc' => 'secondary',
			's sc' => 'secondary',

			// ===== 12th / HSC =====
			'12' => 'higher secondary',
			'12th' => 'higher secondary',
			'hsc' => 'higher secondary',
			'h sc' => 'higher secondary',

			// ===== Graduation =====
			'graduation' => 'bachelor of arts',
			'graduate' => 'bachelor of arts',
			'ba' => 'bachelor of arts',
			'b a' => 'bachelor of arts',
			'bachelor of rural studies' => 'bachelor of arts',

			// ===== Post Graduation =====
			'ma' => 'master of arts',
			'm a' => 'master of arts',
			'master of arts' => 'master of arts',
		];

		return $map[$normalized] ?? $normalized;
	}


	private function getQualificationId($qualificationName)
	{
		// 1. Normalize
		$normalized = $this->normalizeQualification($qualificationName);

		if (!$normalized) {
			return 5; // fallback SSC
		}

		// 2. Convert Excel wording → canonical wording
		$searchTerm = $this->mapQualificationKeyword($normalized);

		// 3. Cache check
		if (isset($this->lookupCache['qualifications'][$searchTerm])) {
			return $this->lookupCache['qualifications'][$searchTerm]->EducationId;
		}

		// 4. Exact match (name or code)
		$qualification = MasterEducation::where('Status', 'A')
			->where('IsDeleted', 0)
			->where(function ($query) use ($searchTerm) {
				$query->whereRaw('LOWER(EducationName) = ?', [$searchTerm])
					->orWhereRaw('LOWER(EducationCode) = ?', [$searchTerm]);
			})
			->first();

		// 5. Partial match fallback
		if (!$qualification) {
			$qualification = MasterEducation::where('Status', 'A')
				->where('IsDeleted', 0)
				->whereRaw('LOWER(EducationName) LIKE ?', ["%{$searchTerm}%"])
				->first();
		}

		// 6. Cache + return
		if ($qualification) {
			$this->lookupCache['qualifications'][$searchTerm] = $qualification;
			return $qualification->EducationId;
		}

		// 7. FINAL SAFETY FALLBACK (VERY IMPORTANT)
		return 5; // SSC
	}




	// private function getQualificationId($qualificationName)
	// {
	// 	if (empty($qualificationName)) return null;

	// 	$searchTerm = strtolower(trim($qualificationName));

	// 	// Check cache first
	// 	if (isset($this->lookupCache['qualifications'][$searchTerm])) {
	// 		return $this->lookupCache['qualifications'][$searchTerm]->EducationId;
	// 	}

	// 	// Common qualification mappings
	// 	$qualificationMappings = [
	// 		'bsc ag' => 'bsc agriculture',
	// 		'bsc' => 'bachelor of science',
	// 		'b.sc' => 'bachelor of science',
	// 		'b sc' => 'bachelor of science',
	// 		'12th' => 'higher secondary',
	// 		'12 th' => 'higher secondary',
	// 		'h.s.c' => 'higher secondary',
	// 		'hsc' => 'higher secondary',
	// 		'10th' => 'secondary',
	// 		'10 th' => 'secondary',
	// 		'ssc' => 'secondary',
	// 		'ba' => 'bachelor of arts',
	// 		'b.a' => 'bachelor of arts',
	// 		'b a' => 'bachelor of arts',
	// 		'b.a. 1st year' => 'bachelor of arts',
	// 		'iti copa' => 'iti',
	// 		'iti' => 'industrial training institute',
	// 		'graduate' => 'graduation',
	// 		'post graduate' => 'post graduation',
	// 		'masters of art' => 'master of arts',
	// 		'ma' => 'master of arts',
	// 		'm.a' => 'master of arts',
	// 		'm a' => 'master of arts',
	// 	];

	// 	if (isset($qualificationMappings[$searchTerm])) {
	// 		$searchTerm = $qualificationMappings[$searchTerm];
	// 		if (isset($this->lookupCache['qualifications'][$searchTerm])) {
	// 			return $this->lookupCache['qualifications'][$searchTerm]->EducationId;
	// 		}
	// 	}

	// 	// Try exact match
	// 	$qualification = MasterEducation::where('Status', 'A')
	// 		->where('IsDeleted', 0)
	// 		->where(function ($query) use ($searchTerm) {
	// 			$query->whereRaw('LOWER(EducationName) = ?', [$searchTerm])
	// 				->orWhereRaw('LOWER(EducationCode) = ?', [$searchTerm]);
	// 		})
	// 		->first();

	// 	if (!$qualification) {
	// 		// Try contains
	// 		$qualification = MasterEducation::where('Status', 'A')
	// 			->where('IsDeleted', 0)
	// 			->whereRaw('LOWER(EducationName) LIKE ?', ["%{$searchTerm}%"])
	// 			->first();
	// 	}

	// 	if ($qualification) {
	// 		$this->lookupCache['qualifications'][$searchTerm] = $qualification;
	// 		return $qualification->EducationId;
	// 	}

	// 	return null;
	// }

	private function getVerticalId($verticalName)
	{
		if (empty($verticalName)) return null;

		$searchTerm = strtolower(trim($verticalName));

		// Check cache first
		if (isset($this->lookupCache['verticals'][$searchTerm])) {
			return $this->lookupCache['verticals'][$searchTerm]->id;
		}

		// Determine vertical from text
		$verticalMappings = [
			'field crop' => 'field crop',
			'fc' => 'field crop',
			'vegetable' => 'vegetable',
			'vc' => 'vegetable',
			'tfa' => 'tfa',
			'cb' => 'cb',
		];

		foreach ($verticalMappings as $key => $value) {
			if (str_contains($searchTerm, $key)) {
				$searchTerm = $value;
				break;
			}
		}

		// Try exact match
		$vertical = CoreVertical::where('is_active', 1)
			->whereRaw('LOWER(vertical_name) = ?', [$searchTerm])
			->first();

		if (!$vertical) {
			// Try contains
			$vertical = CoreVertical::where('is_active', 1)
				->whereRaw('LOWER(vertical_name) LIKE ?', ["%{$searchTerm}%"])
				->first();
		}

		if ($vertical) {
			$this->lookupCache['verticals'][$searchTerm] = $vertical;
			return $vertical->id;
		}

		return null;
	}

	private function getSubDepartmentId($subDepartmentName)
	{
		if (empty($subDepartmentName)) return null;

		$searchTerm = strtolower(trim($subDepartmentName));

		// Normalize spaces
		$searchTerm = preg_replace('/\s+/', ' ', $searchTerm);

		// 1️⃣ Exact match from cache
		if (isset($this->lookupCache['sub_departments'][$searchTerm])) {
			return $this->lookupCache['sub_departments'][$searchTerm]->id;
		}

		// 2️⃣ Try DB exact match
		$subDept = CoreSubDepartment::where('is_active', 1)
			->whereRaw('LOWER(sub_department_name) = ?', [$searchTerm])
			->first();

		if ($subDept) {
			$this->lookupCache['sub_departments'][$searchTerm] = $subDept;
			return $subDept->id;
		}

		// 3️⃣ LIKE fallback (safe)
		$subDept = CoreSubDepartment::where('is_active', 1)
			->whereRaw('LOWER(sub_department_name) LIKE ?', ["%{$searchTerm}%"])
			->first();

		if ($subDept) {
			$this->lookupCache['sub_departments'][$searchTerm] = $subDept;
			return $subDept->id;
		}

		// 4️⃣ Log missing sub-department
		\Log::warning('Sub-department not mapped', [
			'excel_value' => $subDepartmentName
		]);

		return null;
	}


	// ==================== DATA PREPARATION ====================

	private function prepareManpowerRequisitionData($data, $lookupIds, $user, $requisitionType)
	{
		$timestamp = $this->parseExcelDate($data['Timestamp'] ?? null) ?? now();
		$contractStart = $this->parseExcelDate($data['Date of Joining Required'] ?? null);
		$contractEnd = $this->parseExcelDate($data['Date of Separation'] ?? null);
		$lastWorkingDate = $contractEnd;

		if ($contractEnd) {
			$lastWorkingDate = date('Y-m-d', strtotime($contractEnd . ' +1 day'));
		}

		// Get email from E Mail (mapped) or direct Email Address
		$email = null;
		if (!empty($data['E Mail'])) {
			$email = $data['E Mail'];
		} elseif (!empty($data['Email Address'])) {
			$email = $data['Email Address'];
		}

		// Get Reporting Manager Employee ID
		$reportingManagerEmployeeId = null;
		if (!empty($data['Reporting Manager Employee ID'])) {
			$reportingManagerEmployeeId = $data['Reporting Manager Employee ID'];
		} elseif (!empty($data['Reporting Manager Employee  ID'])) {
			$reportingManagerEmployeeId = $data['Reporting Manager Employee  ID'];
		}

		$requisitionData = [
			'requisition_type' => $requisitionType,
			'submitted_by_user_id' => $user->id ?? 1,
			'submitted_by_name' => $user->name ?? ($data['Email Address'] ?? 'System Import'),
			'submitted_by_employee_id' => $user->employee_id ?? 'SYS-IMPORT',
			'submission_date' => $timestamp,
			'candidate_email' => $email,  // Will be NULL if no email
			'candidate_name' => $data["Candidate's Name"] ?? '',
			'father_name' => $data['Fathers Name'] ?? '',
			'mobile_no' => $this->formatMobileNumber($data['Mobile No.'] ?? ''),
			'alternate_email' => null,
			'address_line_1' => $data['Address Line 1'] ?? '',
			'city' => $lookupIds['city_id'] ?? null,
			'state_residence' => $lookupIds['state_residence_id'] ?? null,
			'pin_code' => $this->formatPinCode($data['Pin Code'] ?? ''),
			'date_of_birth' => $this->parseExcelDate($data['Date of Birth'] ?? null),
			'gender' => $this->formatGender($data['Gender'] ?? ''),
			'highest_qualification' => $lookupIds['qualification_id'] ?? null,
			'work_location_hq' => $data['Work Location/HQ'] ?? '',
			'district' => $data['District'] ?? '',
			'state_work_location' => $lookupIds['state_work_id'] ?? null,
			'function_id' => $lookupIds['function_id'] ?? null,
			'department_id' => $lookupIds['department_id'] ?? null,
			'vertical_id' => $lookupIds['vertical_id'] ?? null,
			'sub_department' => $lookupIds['sub_department_id'] ?? null,
			'business_unit' => $lookupIds['business_unit_id'] ?? null,
			'zone' => $lookupIds['zone_id'] ?? null,
			'region' => $lookupIds['region_id'] ?? null,
			'territory' => $lookupIds['territory_id'] ?? null,
			'reporting_to' => $data['Reporting To'] ?? '',
			'reporting_manager_employee_id' => $reportingManagerEmployeeId,
			'contract_start_date' => $contractStart,
			'contract_end_date' => $contractEnd,
			'contract_duration' => $this->calculateDuration($contractStart, $contractEnd),
			'last_working_date' => $lastWorkingDate,
			'remuneration_per_month' => $this->formatSalary($data['Remuneration (per month)'] ?? 0),
			'other_reimbursement_required' => 'N',
			'out_of_pocket_required' => 'N',
			'reporting_manager_address' => $data['Please specify the address of Reporting Manager for dispatching the Agreement with complete detail (PIN and Phone No)'] ?? '',
			'bank_account_no' => $data['Bank Account No.'] ?? null,
			'bank_ifsc' => $data['Bank IFSC'] ?? null,
			'bank_name' => $data['Bank Name'] ?? null,
			//'status' => $this->determineInitialStatus($data),
			'status' => 'Inactive',
			'created_at' => now(),
			'updated_at' => now(),
		];

		if (isset($data['Approval']) && strtolower($data['Approval']) === 'approved') {
			$requisitionData['approval_date'] = $timestamp;
			$requisitionData['hr_verification_date'] = $timestamp;
			$requisitionData['processing_date'] = $timestamp;
		}

		return $requisitionData;
	}

	private function prepareCandidateMasterData($data, $lookupIds, $manpowerRequisitionId, $requisitionType, $candidateCode)
	{
		$contractStart = $this->parseExcelDate($data['Date of Joining Required'] ?? null);
		$contractEnd = $this->parseExcelDate($data['Date of Separation'] ?? null);
		$lastWorkingDate = $contractEnd;

		if ($contractEnd) {
			$lastWorkingDate = date('Y-m-d', strtotime($contractEnd . ' +1 day'));
		}

		// Get email from E Mail (mapped) or direct Email Address
		$email = null;
		if (!empty($data['E Mail'])) {
			$email = $data['E Mail'];
		} elseif (!empty($data['Email Address'])) {
			$email = $data['Email Address'];
		}

		// Get Reporting Manager Employee ID
		$reportingManagerEmployeeId = null;
		if (!empty($data['Reporting Manager Employee ID'])) {
			$reportingManagerEmployeeId = $data['Reporting Manager Employee ID'];
		} elseif (!empty($data['Reporting Manager Employee  ID'])) {
			$reportingManagerEmployeeId = $data['Reporting Manager Employee  ID'];
		}

		return [
			'requisition_id' => $manpowerRequisitionId,
			'candidate_code' => $candidateCode,
			'requisition_type' => $requisitionType,
			'candidate_email' => $email,  // Will be NULL if no email
			'candidate_name' => $data["Candidate's Name"] ?? '',
			'father_name' => $data['Fathers Name'] ?? '',
			'mobile_no' => $this->formatMobileNumber($data['Mobile No.'] ?? ''),
			'alternate_email' => null,
			'address_line_1' => $data['Address Line 1'] ?? '',
			'city' => $lookupIds['city_id'] ?? null,
			'state_residence' => $lookupIds['state_residence_id'] ?? null,
			'pin_code' => $this->formatPinCode($data['Pin Code'] ?? ''),
			'date_of_birth' => $this->parseExcelDate($data['Date of Birth'] ?? null),
			'gender' => $this->formatGender($data['Gender'] ?? ''),
			'highest_qualification' => $lookupIds['qualification_id'] ?? null,
			'work_location_hq' => $data['Work Location/HQ'] ?? '',
			'district' => $data['District'] ?? '',
			'state_work_location' => $lookupIds['state_work_id'] ?? null,
			'function_id' => $lookupIds['function_id'] ?? 0,
			'department_id' => $lookupIds['department_id'] ?? 0,
			'vertical_id' => $lookupIds['vertical_id'] ?? 0,
			'sub_department' => $lookupIds['sub_department_id'] ?? null,
			'business_unit' => $lookupIds['business_unit_id'] ?? null,
			'zone' => $lookupIds['zone_id'] ?? null,
			'region' => $lookupIds['region_id'] ?? null,
			'territory' => $lookupIds['territory_id'] ?? null,
			'reporting_to' => $data['Reporting To'] ?? '',
			'reporting_manager_employee_id' => $reportingManagerEmployeeId,
			'reporting_manager_address' => $data['Please specify the address of Reporting Manager for dispatching the Agreement with complete detail (PIN and Phone No)'] ?? '',
			'contract_start_date' => $contractStart,
			'contract_end_date' => $contractEnd,
			'contract_duration' => $this->calculateDuration($contractStart, $contractEnd),
			'last_working_date' => $lastWorkingDate,
			'remuneration_per_month' => $this->formatSalary($data['Remuneration (per month)'] ?? 0),
			'account_holder_name' => $data["Candidate's Name"] ?? '',
			'bank_account_no' => $data['Bank Account No.'] ?? null,
			'bank_ifsc' => $data['Bank IFSC'] ?? null,
			'bank_name' => $data['Bank Name'] ?? null,
			'pan_no' => null,
			'candidate_status' => 'Inactive',
			'final_status' => 'D',
			'leave_credited' => 0,
			'other_reimbursement_required' => 'N',
			'out_of_pocket_required' => 'N',
			'external_reference_id' => 'EXCEL-IMPORT-' . date('YmdHis'),
			'external_created_at' => now(),
			'created_by_user_id' => auth()->id() ?? 1,
			'created_at' => now(),
			'updated_at' => now(),
		];
	}
	// ==================== HELPER FUNCTIONS ====================

	private function determineRequisitionType($data)
	{
		$vertical = strtoupper($data['Vertical'] ?? '');

		if (str_contains($vertical, 'TFA')) {
			return 'TFA';
		} elseif (str_contains($vertical, 'CB')) {
			return 'CB';
		} else {
			return 'Contractual';
		}
	}

	private function determineInitialStatus($data)
	{
		$approval = strtolower($data['Approval'] ?? '');
		$docVerification = strtolower($data['Document verification'] ?? '');

		if ($approval === 'approved' && $docVerification === 'documents verified') {
			return 'Approved';
		} elseif ($approval === 'approved') {
			return 'Hr Verified';
		} else {
			return 'Pending HR Verification';
		}
	}

	private function generateCandidateCode($requisitionType, $lookupIds = [])
	{
		// Prefix mapping based on requisition type
		$prefixes = [
			'TFA' => 'TFA',
			'CB' => 'CB',
			'Contractual' => 'CON',
		];

		$prefix = $prefixes[$requisitionType] ?? 'EMP';
		$year = date('y'); // 26 for 2026

		// Get last candidate code for this prefix + year
		// Format: TFA-26-0001, TFA-26-0002, etc.
		$lastCandidate = CandidateMaster::where('candidate_code', 'like', $prefix . '-' . $year . '-%')
			->orderByRaw('CAST(SUBSTRING_INDEX(candidate_code, "-", -1) AS UNSIGNED) DESC')
			->first();

		if ($lastCandidate) {
			// Extract the number after the last dash
			$parts = explode('-', $lastCandidate->candidate_code);
			$lastNumber = intval(end($parts));
			$newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
		} else {
			$newNumber = '0001';
		}

		// Format: TFA-26-0001
		return $prefix . '-' . $year . '-' . $newNumber;
	}
	private function parseExcelDate($dateValue)
	{
		if (empty($dateValue)) return null;

		try {
			// If it's already a DateTime object (from Excel import)
			if ($dateValue instanceof \DateTime) {
				return $dateValue->format('Y-m-d');
			}

			// Handle Excel serial date numbers
			if (is_numeric($dateValue)) {
				$unixTimestamp = ($dateValue - 25569) * 86400;
				return date('Y-m-d', $unixTimestamp);
			}

			// Remove any whitespace
			$dateValue = trim($dateValue);

			// Try Carbon first
			try {
				return Carbon::parse($dateValue)->format('Y-m-d');
			} catch (\Exception $e) {
				// Continue with other methods
			}

			// Handle common date formats
			$formats = [
				'd-M-Y',
				'd/M/Y',
				'd M Y',
				'd F Y',
				'Y-m-d',
				'Y/m/d',
				'm/d/Y',
				'M d Y',
				'd-m-Y',
				'd/m/Y',
				'm-d-Y'
			];

			foreach ($formats as $format) {
				$date = DateTime::createFromFormat($format, $dateValue);
				if ($date !== false) {
					return $date->format('Y-m-d');
				}
			}

			// Try strtotime as fallback
			$timestamp = strtotime($dateValue);
			if ($timestamp !== false) {
				return date('Y-m-d', $timestamp);
			}

			return null;
		} catch (\Exception $e) {
			return null;
		}
	}

	private function formatMobileNumber($number)
	{
		$number = preg_replace('/[^0-9]/', '', $number);
		return substr($number, -10); // Take last 10 digits
	}

	private function formatPinCode($pincode)
	{
		$pincode = preg_replace('/[^0-9]/', '', $pincode);
		return substr($pincode, 0, 6);
	}

	private function formatGender($gender)
	{
		if (empty($gender)) return 'Male'; // Default

		$gender = strtolower(trim($gender));

		if (in_array($gender, ['male', 'm', 'boy', 'male '])) {
			return 'Male';
		} elseif (in_array($gender, ['female', 'f', 'girl', 'woman', 'female '])) {
			return 'Female';
		} else {
			return 'Other';
		}
	}

	private function formatSalary($salary)
	{
		if (empty($salary)) return 0;

		if (is_numeric($salary)) {
			return floatval($salary);
		}

		$salary = preg_replace('/[^0-9.]/', '', $salary);
		$value = floatval($salary);

		return $value > 0 ? $value : 0;
	}

	private function calculateDuration($startDate, $endDate)
	{
		if (!$startDate || !$endDate) return 0;

		try {
			$start = new DateTime($startDate);
			$end = new DateTime($endDate);

			// Calculate difference in months
			$interval = $start->diff($end);
			$months = ($interval->y * 12) + $interval->m;

			// Add partial month if days > 15
			if ($interval->d > 15) {
				$months++;
			}

			return $months;
		} catch (\Exception $e) {
			return 0;
		}
	}

	private function createLeaveBalance($candidateId, $startDate)
	{
		if (!$startDate) return;

		$startDate = $this->parseExcelDate($startDate);
		if (!$startDate) return;

		LeaveBalance::create([
			'CandidateID' => $candidateId,
			'calendar_year' => date('Y'),
			'opening_cl_balance' => 12,
			'cl_utilized' => 0,
			'lwp_days_accumulated' => 0,
			'contract_start_date' => $startDate,
			'contract_end_date' => null,
			'created_at' => now(),
			'updated_at' => now()
		]);
	}

	public function updateCandidateData(Request $request)
	{
		try {
			DB::beginTransaction();

			$requisitionId = $request->requisition_id;
			$candidateId = $request->candidate_id;

			$requisition = ManpowerRequisition::findOrFail($requisitionId);
			$candidate = $candidateId ? CandidateMaster::find($candidateId) : null;

			// Update or create candidate record
			if (!$candidate && $requisition->candidate) {
				$candidate = $requisition->candidate;
			}

			$updateData = [];

			// PAN Card data
			if ($request->filled('pan_no')) {
				$updateData['pan_no'] = $request->pan_no;
			}

			// Aadhaar Card data
			if ($request->filled('aadhaar_no')) {
				$updateData['aadhaar_no'] = $request->aadhaar_no;
			}

			// Bank details
			if ($request->filled('account_holder_name')) {
				$updateData['account_holder_name'] = $request->account_holder_name;
			}
			if ($request->filled('bank_account_no')) {
				$updateData['bank_account_no'] = $request->bank_account_no;
			}
			if ($request->filled('bank_ifsc')) {
				$updateData['bank_ifsc'] = $request->bank_ifsc;
			}
			if ($request->filled('bank_name')) {
				$updateData['bank_name'] = $request->bank_name;
			}

			// Driving Licence
			if ($request->filled('driving_licence_no')) {
				$updateData['driving_licence_no'] = $request->driving_licence_no;
			}

			// Update candidate master
			if ($candidate && !empty($updateData)) {
				$candidate->update($updateData);
			}

			// Also update requisition with these details
			$requisitionUpdateData = [];

			if ($request->filled('pan_no')) {
				$requisitionUpdateData['pan_no'] = $request->pan_no;
			}
			if ($request->filled('aadhaar_no')) {
				$requisitionUpdateData['aadhaar_no'] = $request->aadhaar_no;
			}
			if ($request->filled('bank_account_no')) {
				$requisitionUpdateData['bank_account_no'] = $request->bank_account_no;
			}
			if ($request->filled('bank_ifsc')) {
				$requisitionUpdateData['bank_ifsc'] = $request->bank_ifsc;
			}
			if ($request->filled('bank_name')) {
				$requisitionUpdateData['bank_name'] = $request->bank_name;
			}
			if ($request->filled('account_holder_name')) {
				$requisitionUpdateData['account_holder_name'] = $request->account_holder_name;
			}

			if (!empty($requisitionUpdateData)) {
				$requisition->update($requisitionUpdateData);
			}

			// Save any pre-extracted documents
			$this->savePreExtractedDocuments($request, $requisitionId, Auth::id());

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Candidate data updated successfully'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			\Log::error('Failed to update candidate data: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to update candidate data: ' . $e->getMessage()
			], 500);
		}
	}

	private function handleDocumentStatus($candidate, $docStatus)
	{
		// You can implement document status handling here
		// For example, create document records if needed
		if (strtolower($docStatus) === 'documents verified') {
			// Mark documents as verified
			// $candidate->update(['document_verification_status' => 'Verified']);
		}
	}

	private function handleApprovalStatus($requisition, $approvalStatus)
	{
		if (strtolower($approvalStatus) === 'approved') {
			$requisition->update([
				'status' => 'Approved',
				'approval_date' => now(),
				'hr_verification_date' => now()
			]);
		}
	}

	// ==================== TEMPLATE DOWNLOAD ====================

	// public function downloadTemplate()
	// {
	// 	$headers = [
	// 		'Timestamp',
	// 		'Email Address',
	// 		'Candidate\'s Name',
	// 		'Fathers Name',
	// 		'Function',
	// 		'Vertical',
	// 		'Department',
	// 		'Sub-department',
	// 		'Work Location/HQ',
	// 		'District',
	// 		'State',
	// 		'Business Unit',
	// 		'Zone',
	// 		'Region',
	// 		'Territory',
	// 		'Mobile No.',
	// 		'E Mail',
	// 		'Address Line 1',
	// 		'City',
	// 		'State',
	// 		'Pin Code',
	// 		'Date of Birth',
	// 		'Gender',
	// 		'Highest Qualification',
	// 		'Reporting To',
	// 		'Date of Joining Required',
	// 		'Date of Separation',
	// 		'Remuneration (per month)',
	// 		'Please specify the address of Reporting Manager for dispatching the Agreement with complete detail (PIN and Phone No)',
	// 		'Approval from ZBM/GM',
	// 		'Resume of the Candidate',
	// 		'Aadhaar Card',
	// 		'Driving Licence',
	// 		'Bank Passbook or Cancelled Cheque',
	// 		'Bank Account No.',
	// 		'Bank IFSC',
	// 		'Bank Name',
	// 		'Other Document',
	// 		'Reporting Manager Employee ID',
	// 		'Approval',
	// 		'Document verification'
	// 	];

	// 	// Create example data row
	// 	$exampleRow = [
	// 		'5/14/2025 12:12:19',
	// 		'shivamsingh.vspl@gmail.com',
	// 		'JaiSingh',
	// 		'Yogendra',
	// 		'Sales',
	// 		'Field Crop (FC)',
	// 		'Sales',
	// 		'',
	// 		'Gorakhpur',
	// 		'Gorakhpur',
	// 		'UP',
	// 		'BU 1 - SOUTH WEST - VC',
	// 		'FC - Zone 1',
	// 		'VC - Chhattisgarh',
	// 		'FC-JAGDALPUR',
	// 		'9839243510',
	// 		'jaisingh@example.com',
	// 		'Vill Post - Rajdhani',
	// 		'Maharajganj',
	// 		'UP',
	// 		'273162',
	// 		'11-Jul-1993',
	// 		'Male',
	// 		'BSc Ag',
	// 		'Shivam Singh',
	// 		'15-May-2025',
	// 		'29-Jun-2025',
	// 		'13000',
	// 		'Somani trade link Sanjay Market Jagdalpur',
	// 		'Approved',
	// 		'', // Resume
	// 		'', // Aadhaar
	// 		'', // DL
	// 		'', // Bank Passbook
	// 		'43898663557',
	// 		'SBIN0005467',
	// 		'STATE BANK OF INDIA',
	// 		'', // Other Doc
	// 		'EMP12345',
	// 		'Approved',
	// 		'Documents verified'
	// 	];

	// 	$data = [$headers, $exampleRow];

	// 	return Excel::download(
	// 		new GenericExport($data),
	// 		'candidate_import_template_' . date('Ymd_His') . '.xlsx'
	// 	);
	// }
}
