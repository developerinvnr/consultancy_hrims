<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\S3Service;

class DocumentController extends Controller
{
	/**
	 * Process PAN card document
	 */
	public function processPANCard(Request $request, S3Service $s3Service)
	{
		$filePath = null;
		$fileUrl = null;
		$filename = null;
	

		$request->validate([
			'pan_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
			'requisition_type' => 'required|in:Contractual,TFA,CB',
		]);

		$requisitionType = $request->input('requisition_type', 'Contractual');
		$filePath = null;

		try {
			$file = $request->file('pan_file');
			if (!$file->isValid()) {
				throw new \Exception('Invalid file uploaded');
			}

			// Upload to S3
			$upload = $s3Service->uploadRequisitionDocument($file, $requisitionType, 'pan');

			if (!$upload['success']) {
				throw new \Exception('S3 Upload failed: ' . $upload['error']);
			}

			$fileUrl = $upload['url'];
			$filePath = $upload['key'];
			$filename = $upload['filename'];

			// Extract PAN details
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-pan-card', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);

			if (!$extractData['success'] || empty($extractData['data']['panNumber'])) {
				throw new \Exception('PAN number extraction failed');
			}

			$panNumber = $extractData['data']['panNumber'];
			$fatherName = $extractData['data']['fatherName'] ?? null;
            $dateOfBirth = $extractData['data']['dateOfBirth'] ?? null;

			// Verify PAN with external API
			$verifyResponse = $client->post('https://api.rpacpc.com/services/get-pan-nsdl-details', [
				'headers' => [
					'Content-Type' => 'application/json',
					'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
					'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
				],
				'json' => ['pan_number' => $panNumber],
				'timeout' => 30,
			]);

			$verifyData = json_decode($verifyResponse->getBody(), true);

			// Determine if PAN is verified
			$isVerified = ($verifyData['status'] === 'SUCCESS' &&
				!empty($verifyData['data']['is_valid']) &&
				$verifyData['data']['is_valid']);

			$message = $isVerified
				? 'PAN extracted and verified successfully'
				: 'PAN extracted but verification failed';

			return response()->json([
				'status' => 'SUCCESS',
				'data' => [
					'panNumber' => $panNumber,
					'fatherName' => $fatherName,
                    'dateOfBirth' => $dateOfBirth,
					'isVerified' => $isVerified,
					'verificationData' => $verifyData['data'] ?? null,
					'filename' => $filename,
					'originalName' => $file->getClientOriginalName(),
					'filePath' => $filePath,
					'fileUrl' => $fileUrl,
				],
				'message' => $message,
			]);
		} catch (\Exception $e) {
			// Clean up S3 file if upload was successful but processing failed
			// if ($filePath && $s3Service->fileExists($filePath)) {
			// 	$s3Service->deleteFile($filePath);
			// 	Log::info('Cleaned up PAN file from S3 due to processing error', ['filePath' => $filePath]);
			// }

			return response()->json([
				'status' => 'PARTIAL_SUCCESS',
				'data' => [
					'panNumber' => null,
					'isVerified' => false,
					'filename' => $filename,
					'filePath' => $filePath,
					'fileUrl' => $fileUrl,
				],
				'message' => 'PAN uploaded but extraction failed. Please enter manually.',
			]);
		}
	}

	/**
	 * Process bank document (passbook/cheque)
	 */
	public function processBankDocument(Request $request, S3Service $s3Service)
	{
		$filePath = null;
		$fileUrl = null;
		$filename = null;

		$request->validate([
			'bank_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
			'requisition_type' => 'required|in:Contractual,TFA,CB',
		]);

		$requisitionType = $request->input('requisition_type', 'Contractual');
		$filePath = null;

		try {
			$file = $request->file('bank_file');
			if (!$file->isValid()) {
				throw new \Exception('Invalid file uploaded');
			}

			// Upload to S3
			$upload = $s3Service->uploadRequisitionDocument($file, $requisitionType, 'bank');

			if (!$upload['success']) {
				throw new \Exception('S3 Upload failed: ' . $upload['error']);
			}

			$fileUrl = $upload['url'];
			$filePath = $upload['key'];
			$filename = $upload['filename'];

			// Extract bank details
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-cheque-details', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);

			if (!$extractData['success']) {
				throw new \Exception('Bank details extraction failed');
			}

			$accountNumber = $extractData['data']['accountNumber'] ?? null;
			$accountHolderName = $extractData['data']['accountHolderName'] ?? null;
			$ifscCode = $extractData['data']['ifscCode'] ?? null;
			$bankName = $extractData['data']['bankName'] ?? null;

			$isVerified = false;
			$verificationData = null;

			// Verify bank details if we have both account number and IFSC
			if ($accountNumber && $ifscCode) {

				$verifyResponse = $client->post('https://api.rpacpc.com/services/account-verification-pl', [
					'headers' => [
						'Content-Type' => 'application/json',
						'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
						'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
					],
					'json' => [
						'acc_number' => $accountNumber,
						'ifsc_number' => $ifscCode,
					],
					'timeout' => 30,
				]);

				$verifyData = json_decode($verifyResponse->getBody(), true);

				$isVerified = ($verifyData['status'] === 'SUCCESS' &&
					!empty($verifyData['data']['verification_status']) &&
					$verifyData['data']['verification_status'] === 'VERIFIED');
				$verificationData = $verifyData['data'] ?? null;
			}

			$message = $isVerified
				? 'Bank details extracted and verified successfully'
				: ($accountNumber ? 'Bank details extracted but not verified' : 'Partial bank details extracted');

			return response()->json([
				'status' => 'SUCCESS',
				'data' => [
					'accountNumber' => $accountNumber,
					'ifscCode' => $ifscCode,
					'bankName' => $bankName,
					'isVerified' => $isVerified,
					'verificationData' => $verificationData,
					'filename' => $filename,
					'originalName' => $file->getClientOriginalName(),
					'filePath' => $filePath,
					'fileUrl' => $fileUrl,
				],
				'message' => $message,
			]);
		} catch (\Exception $e) {
			// Clean up S3 file if upload was successful but processing failed
			// if ($filePath && $s3Service->fileExists($filePath)) {
			// 	$s3Service->deleteFile($filePath);
			// 	Log::info('Cleaned up bank file from S3 due to processing error', ['filePath' => $filePath]);
			// }

			return response()->json([
				'status' => 'PARTIAL_SUCCESS',
				'data' => [
					'accountNumber' => null,
					'ifscCode' => null,
					'bankName' => null,
					'isVerified' => false,
					'filename' => $filename ?? null,
					'filePath' => $filePath ?? null,
					'fileUrl' => $fileUrl ?? null,
				],
				'message' => 'Bank document uploaded but extraction failed. Please enter details manually.',
			]);
		}
	}

	/**
	 * Process Aadhaar card document (optional)
	 */
	public function processAadhaarCard(Request $request, S3Service $s3Service)
	{
		$filePath = null;
		$fileUrl = null;
		$filename = null;

		$request->validate([
			'aadhaar_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
			'requisition_type' => 'required|in:Contractual,TFA,CB',
		]);

		$requisitionType = $request->input('requisition_type', 'Contractual');
		$filePath = null;

		try {
			$file = $request->file('aadhaar_file');
			if (!$file->isValid()) {
				throw new \Exception('Invalid file uploaded');
			}

			// ✅ FIX: Use the same approach as PAN and Bank - don't pass custom filename
			// Let the S3Service generate the filename
			$upload = $s3Service->uploadRequisitionDocument($file, $requisitionType, 'aadhaar_card');

			if (!$upload['success']) {
				throw new \Exception('S3 Upload failed: ' . $upload['error']);
			}

			$fileUrl = $upload['url'];
			$filePath = $upload['key'];
			$filename = $upload['filename'];
			// Extract Aadhaar number
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-aadhaar-number', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);

			if (!$extractData['success'] || empty($extractData['data']['aadhaarNumber'])) {
				// Clean up uploaded file on failure
				$s3Service->deleteFile($filePath);
				throw new \Exception('Aadhaar number extraction failed: ' . ($extractData['message'] ?? 'No Aadhaar number found'));
			}

			$aadhaarNumber = $extractData['data']['aadhaarNumber'];

			// Initialize personal details array
			$personalDetails = [
				'name' => null,
				'fatherName' => null,
				'dob' => null,
				'gender' => null,
				'address' => null,
			];

			// Verify Aadhaar with external API to get personal details
			$isVerified = false;
			$verificationData = null;

			try {
				

				$verifyResponse = $client->post('https://api.rpacpc.com/services/aadhaar-verification', [
					'headers' => [
						'Content-Type' => 'application/json',
						'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
						'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
					],
					'json' => ['aadhaar_number' => $aadhaarNumber],
					'timeout' => 30,
				]);

				$verifyData = json_decode($verifyResponse->getBody(), true);

				// Check if verification was successful
				if ($verifyData['status'] === 'SUCCESS' && isset($verifyData['data'])) {
					$isVerified = !empty($verifyData['data']['is_valid']) && $verifyData['data']['is_valid'];
					$verificationData = $verifyData['data'] ?? null;

					// Extract personal details from verification response
					if (isset($verifyData['data']['name'])) {
						$personalDetails['name'] = $verifyData['data']['name'];
					}
					if (isset($verifyData['data']['father_name'])) {
						$personalDetails['fatherName'] = $verifyData['data']['father_name'];
					}
					if (isset($verifyData['data']['fatherName'])) {
						$personalDetails['fatherName'] = $verifyData['data']['fatherName'];
					}
					if (isset($verifyData['data']['dob'])) {
						// Convert date format if needed (assuming format is dd/mm/yyyy)
						$dob = $verifyData['data']['dob'];
						if (strpos($dob, '/') !== false) {
							$parts = explode('/', $dob);
							if (count($parts) === 3) {
								$personalDetails['dob'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
							}
						} else {
							$personalDetails['dob'] = $dob;
						}
					}
					if (isset($verifyData['data']['gender'])) {
						$personalDetails['gender'] = ucfirst(strtolower($verifyData['data']['gender']));
					}
					if (isset($verifyData['data']['address'])) {
						$personalDetails['address'] = $verifyData['data']['address'];
					}
					if (isset($verifyData['data']['full_address'])) {
						$personalDetails['address'] = $verifyData['data']['full_address'];
					}
				}
			} catch (\Exception $e) {
				// Log::warning('Aadhaar verification failed, but extraction succeeded', [
				// 	'error' => $e->getMessage(),
				// 	'aadhaar_number' => $aadhaarNumber
				// ]);
				// Continue with extraction only - verification failed but we still have the number
			}

			$message = $isVerified
				? 'Aadhaar extracted and verified successfully'
				: 'Aadhaar extracted successfully';

			return response()->json([
				'status' => 'SUCCESS',
				'data' => [
					'aadhaarNumber' => $aadhaarNumber,
					'personalDetails' => $personalDetails,
					'isVerified' => $isVerified,
					'verificationData' => $verificationData,
					'filename' => $filename,
					'originalName' => $file->getClientOriginalName(),
					'filePath' => $filePath,
					'fileUrl' => $fileUrl,
				],
				'message' => $message,
				'note' => $isVerified ? '' : 'Aadhaar extracted but not verified',
			]);
		} catch (\Exception $e) {
			// Clean up S3 file if upload was successful but processing failed
			if ($filePath && $s3Service->fileExists($filePath)) {
				$s3Service->deleteFile($filePath);
				// Log::info('Cleaned up Aadhaar file from S3 due to processing error', ['filePath' => $filePath]);
			}

			return response()->json([
				'status' => 'PARTIAL_SUCCESS',
				'data' => [
					'aadhaarNumber' => null,
					'personalDetails' => [],
					'isVerified' => false,
					'filename' => $filename ?? null,
					'filePath' => $filePath ?? null,
					'fileUrl' => $fileUrl ?? null,
				],
				'message' => 'Aadhaar uploaded but extraction failed. Please enter details manually.',
			]);
		}
	}

	/**
	 * Process Driving License document
	 */
	public function processDrivingLicense(Request $request, S3Service $s3Service)
	{
		$filePath = null;
		$fileUrl = null;
		$filename = null;

		

		$request->validate([
			'dl_file'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
			'requisition_type' => 'required|in:Contractual,TFA,CB',
		]);

		$requisitionType = $request->input('requisition_type', 'Contractual');
		$filePath = null;

		try {
			$file = $request->file('dl_file');
			if (!$file->isValid()) {
				throw new \Exception('Invalid file uploaded');
			}

			// Upload to S3
			$upload = $s3Service->uploadRequisitionDocument($file, $requisitionType, 'dl');

			if (!$upload['success']) {
				throw new \Exception('S3 Upload failed: ' . ($upload['error'] ?? 'Unknown error'));
			}

			$fileUrl  = $upload['url'];
			$filePath = $upload['key'];
			$filename = $upload['filename'];

			// Call extraction API
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-driving-license', [
				'headers' => ['Content-Type' => 'application/json'],
				'json'    => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);


			// ✅ FIXED PATHS
			$apiSuccess = data_get($extractData, 'success', false);
			$apiMessage = data_get($extractData, 'message', 'No message provided');
			$innerData  = data_get($extractData, 'data', []);

			// Extract values
			$dlNumberRaw = data_get($innerData, 'drivingLicenseNumber');
			$dateOfIssue = data_get($innerData, 'dateOfIssue');
			$validTill   = data_get($innerData, 'validTill');

			// Convert dates
			$validFrom = $dateOfIssue
				? date('Y-m-d', strtotime(str_replace('/', '-', $dateOfIssue)))
				: null;

			$validTo = $validTill
				? date('Y-m-d', strtotime(str_replace('/', '-', $validTill)))
				: null;

			// Validation
			if (!$apiSuccess || empty($dlNumberRaw)) {
				throw new \Exception("DL extraction failed: {$apiMessage}");
			}

			// Success - file stays in S3
			return response()->json([
				'status' => 'SUCCESS',
				'data'   => [
					'dlNumber'     => trim($dlNumberRaw),
					'validFrom'    => $validFrom,     // e.g. "2023-07-19"
					'validTo'      => $validTo,       // e.g. "2044-03-14"
					'filename'     => $filename,
					'originalName' => $file->getClientOriginalName(),
					'filePath'     => $filePath,
					'fileUrl'      => $fileUrl,
				],
				'message' => 'Driving License extracted successfully',
			]);
		} catch (\Exception $e) {
			// Cleanup only on real failure
			// if ($filePath && $s3Service->fileExists($filePath)) {
			// 	$s3Service->deleteFile($filePath);
			// 	Log::info('Cleaned up DL file from S3 due to processing error', ['filePath' => $filePath]);
			// }

			return response()->json([
				'status' => 'PARTIAL_SUCCESS',
				'data' => [
					'dlNumber' => null,
					'status' => 'FAILURE',
					'filename' => $filename,
					'filePath' => $filePath,
					'fileUrl' => $fileUrl,
				],
				'message' => 'Driving License uploaded but extraction failed. Please enter manually.',
			]);
		}
	}

	/**
	 * Verify PAN manually (when user enters PAN number)
	 */
	public function verifyPANManually(Request $request)
	{

		$request->validate([
			'pan_number' => 'required|string|size:10',
		]);

		try {
			$panNumber = strtoupper($request->pan_number);

			$client = new Client();
			$verifyResponse = $client->post('https://api.rpacpc.com/services/get-pan-nsdl-details', [
				'headers' => [
					'Content-Type' => 'application/json',
					'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
					'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
				],
				'json' => ['pan_number' => $panNumber],
				'timeout' => 30,
			]);

			$verifyData = json_decode($verifyResponse->getBody(), true);

			if ($verifyData['status'] === 'SUCCESS' && !empty($verifyData['data'])) {
				$data = $verifyData['data'];

				// Return the full verification data
				return response()->json([
					'status' => 'SUCCESS',
					'data' => [
						'pan_number' => $panNumber,
						'is_valid' => $data['is_valid'] ?? false,
						'owner_name' => $data['name'] ?? $data['owner_name'] ?? null,
						'verificationData' => $data, // Include full verification data
					],
					'message' => 'PAN verified successfully',
				]);
			} else {
				return response()->json([
					'status' => 'FAILURE',
					'message' => $verifyData['message'] ?? 'Invalid PAN number',
				], 400);
			}
		} catch (\Exception $e) {
			

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to verify PAN: ' . $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Verify Bank Account manually
	 */
	public function verifyBankAccount(Request $request)
	{

		$request->validate([
			'account_number' => 'required|string',
			'ifsc_code'      => 'required|string|size:11',
		]);

		try {
			$client = new Client();
			$verifyResponse = $client->post('https://api.rpacpc.com/services/account-verification-pl', [
				'headers' => [
					'Content-Type' => 'application/json',
					'token'        => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
					'secretkey'    => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
				],
				'json' => [
					'acc_number'  => $request->account_number,
					'ifsc_number' => strtoupper($request->ifsc_code),
				],
				'timeout' => 30,
			]);

			$verifyData = json_decode($verifyResponse->getBody(), true);

			// ────────────────────────────────────────────────
			// FIXED PATHS
			$status     = data_get($verifyData, 'status');           // ← top level
			$bankData   = data_get($verifyData, 'data');             // ← the nested object we want

			$verificationStatus = strtoupper(trim(data_get($bankData, 'verification_status', '')));

			if ($status === 'SUCCESS' && $verificationStatus === 'VERIFIED') {

				$ifscDetails = data_get($bankData, 'ifsc_details', []);

				$branchAddress = '';
				if ($ifscDetails) {
					$parts = array_filter([
						data_get($ifscDetails, 'branch'),
						data_get($ifscDetails, 'district'),
						data_get($ifscDetails, 'state'),
					]);
					$branchAddress = implode(', ', $parts);
				}

				return response()->json([
					'status' => 'SUCCESS',
					'data'   => [
						'account_holder_name' => data_get($bankData, 'beneficiary_name'),
						'verification_status' => data_get($bankData, 'verification_status'),
						'bank_name'           => data_get($ifscDetails, 'name'),
						'branch_address'      => $branchAddress,
						'ifsc'                => data_get($ifscDetails, 'ifsc'),
					],
					'message' => 'Bank account verified successfully',
				]);
			}

			return response()->json([
				'status'  => 'FAILURE',
				'message' => 'Bank account verification failed. ' .
					($status ? "API status: $status" : 'No status returned') . '. ' .
					($verificationStatus ? "Verification: $verificationStatus" : 'Unknown verification status'),
			], 400);
		} catch (\Exception $e) {

			return response()->json([
				'status'  => 'FAILURE',
				'message' => 'Unable to verify bank account: ' . $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Verify Driving License manually
	 */
	public function verifyDLManually(Request $request)
	{
		
		$request->validate([
			'dl_number' => 'required|string',
		]);

		try {
			$client = new Client();
			$verifyResponse = $client->post('https://api.rpacpc.com/services/vv005', [
				'headers' => [
					'Content-Type' => 'application/json',
					'token' => 'HZqJwTTU+6SnoILGiwfD2h6Lgpp977mCfFJ4+XrnVvUDKENPJ0WjgRGO0uv9NODrf7KjCl6d34LQJOvn8w/aih79BZHUU6zKzfcoQDLBHkC8SoCaffiBcFvjagMjwnDrQmL6qb6+dmWi8rqFBWV3Sy/utyhxsFxC6N8FdIkvnBjKKlugKVCSssdECP07PB3sCJfU+I6pCWm8uF+4cCROXSXZvNRqaOqap9B/bSIUzSQ89j+Z8CdAhjF6MoKleyj5EsgLvfkybuovyiUscldmbgL6xKDnOwGOB5a3cZgk+/An0SZ92UMRAubEidLDw9lqf+8mmjVdIsfVzu9M5rTYh6ztfDksYcvYQ3kMJpvpUwcinGFCyRg+nW/bJPSv8TGFVs9E+tEgIzr92xryXc2WeEHAinwzVol0gkwfYMvcVJah0qn6gfKXkW/53zCDx4Yd0UWIipAHPPWyKKX2O9RI9g==',
					'secretkey' => 'f0e07252-46b4-4d31-9f76-54f92d3b7d60',
				],
				'json' => [
					'request_id' => uniqid(),
					'consent' => 'Y',
					'consent_text' => 'I hear by declare my consent agreement for fetching my information via RPACPC API',
					'dl_number' => $request->dl_number,
				],
				'timeout' => 30,
			]);

			$verifyData = json_decode($verifyResponse->getBody(), true);

			if ($verifyData['status'] === 'SUCCESS' && $verifyData['status_code'] === '200') {
				$data = $verifyData['data'];

				// Extract validity dates from transport or non-transport
				$validFrom = null;
				$validTo = null;

				if (!empty($data['dl_validity']['transport']['from'])) {
					$validFrom = $this->convertDateFormat($data['dl_validity']['transport']['from']);
				}
				if (!empty($data['dl_validity']['transport']['to'])) {
					$validTo = $this->convertDateFormat($data['dl_validity']['transport']['to']);
				}

				return response()->json([
					'status' => 'SUCCESS',
					'data' => [
						'owner_name' => $data['owner_name'] ?? null,
						'date_of_birth' => $this->convertDateFormat($data['date_of_birth'] ?? null),
						'dl_number' => $data['dl_number'] ?? null,
						'dl_status' => $data['dl_status'] ?? null,
						'issue_date' => $this->convertDateFormat($data['issue_date'] ?? null),
						'valid_from' => $validFrom,
						'valid_to' => $validTo,
						'rto_authority' => $data['rto_authority'] ?? null,
						'cov_details' => $data['cov_details'] ?? null,
					],
					'message' => 'DL verified successfully',
				]);
			} else {
				return response()->json([
					'status' => 'FAILURE',
					'message' => 'Invalid DL number or verification failed',
				], 400);
			}
		} catch (\Exception $e) {
			

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to verify DL: ' . $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Delete uploaded document from S3
	 */
	public function deleteDocument(Request $request, S3Service $s3Service)
	{
		$request->validate([
			'file_path' => 'required|string',
		]);

		try {
			$result = $s3Service->deleteFile($request->file_path);

			if ($result['success']) {
				return response()->json([
					'status' => 'SUCCESS',
					'message' => 'Document deleted successfully',
				]);
			} else {
				return response()->json([
					'status' => 'FAILURE',
					'message' => $result['error'],
				], 500);
			}
		} catch (\Exception $e) {
		

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to delete document: ' . $e->getMessage(),
			], 500);
		}
	}
}
