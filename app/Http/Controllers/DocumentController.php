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
		Log::info('PAN card processing request received', [
			'has_file' => $request->hasFile('pan_file'),
			'requisition_type' => $request->input('requisition_type')
		]);

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

			Log::info('PAN file uploaded to S3', [
				'filePath' => $filePath,
				'fileUrl' => $fileUrl,
				'filename' => $filename
			]);

			// Extract PAN details
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-pan-card', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);
			Log::info('PAN extraction response', ['data' => $extractData]);

			if (!$extractData['success'] || empty($extractData['data']['panNumber'])) {
				throw new \Exception('PAN number extraction failed');
			}

			$panNumber = $extractData['data']['panNumber'];

			Log::info('Sending PAN verification request', ['pan_number' => $panNumber]);

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
			Log::info('PAN verification response', ['data' => $verifyData]);

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
			if ($filePath && $s3Service->fileExists($filePath)) {
				$s3Service->deleteFile($filePath);
				Log::info('Cleaned up PAN file from S3 due to processing error', ['filePath' => $filePath]);
			}

			Log::error('PAN processing error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to process PAN card: ' . $e->getMessage(),
				'suggestion' => 'Please upload a clear image/PDF of your PAN card or enter the PAN number manually.',
			], 500);
		}
	}

	/**
	 * Process bank document (passbook/cheque)
	 */
	public function processBankDocument(Request $request, S3Service $s3Service)
	{
		Log::info('Bank document processing request received', [
			'has_file' => $request->hasFile('bank_file'),
			'requisition_type' => $request->input('requisition_type')
		]);

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

			Log::info('Bank file uploaded to S3', [
				'filePath' => $filePath,
				'fileUrl' => $fileUrl,
				'filename' => $filename
			]);

			// Extract bank details
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-cheque-details', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);
			Log::info('Bank extraction response', ['data' => $extractData]);

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
				Log::info('Sending bank verification request', [
					'account_number' => $accountNumber,
					'ifsc_code' => $ifscCode,
				]);

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
				Log::info('Bank verification response', ['data' => $verifyData]);

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
			if ($filePath && $s3Service->fileExists($filePath)) {
				$s3Service->deleteFile($filePath);
				Log::info('Cleaned up bank file from S3 due to processing error', ['filePath' => $filePath]);
			}

			Log::error('Bank processing error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to process bank document: ' . $e->getMessage(),
				'suggestion' => 'Please upload a clear image/PDF of your bank passbook or cancelled cheque, or enter the details manually.',
			], 500);
		}
	}

	/**
	 * Process Aadhaar card document (optional)
	 */
	public function processAadhaarCard(Request $request, S3Service $s3Service)
	{
		Log::info('Aadhaar card processing request received', [
			'has_file' => $request->hasFile('aadhaar_file'),
			'requisition_type' => $request->input('requisition_type')
		]);

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

			// Upload to S3
			$upload = $s3Service->uploadRequisitionDocument($file, $requisitionType, 'aadhaar');

			if (!$upload['success']) {
				throw new \Exception('S3 Upload failed: ' . $upload['error']);
			}

			$fileUrl = $upload['url'];
			$filePath = $upload['key'];
			$filename = $upload['filename'];

			Log::info('Aadhaar file uploaded to S3', [
				'filePath' => $filePath,
				'fileUrl' => $fileUrl,
				'filename' => $filename
			]);

			// Extract Aadhaar details
			$client = new Client();
			$extractResponse = $client->post('https://api-gf4tdduqha-uc.a.run.app/api/v1/extract-aadhaar-number', [
				'headers' => ['Content-Type' => 'application/json'],
				'json' => ['fileUrl' => $fileUrl],
				'timeout' => 30,
			]);

			$extractData = json_decode($extractResponse->getBody(), true);
			Log::info('Aadhaar extraction response', ['data' => $extractData]);

			if (!$extractData['success'] || empty($extractData['data']['aadhaarNumber'])) {
				// Clean up uploaded file on failure
				$s3Service->deleteFile($filePath);
				throw new \Exception('Aadhaar number extraction failed: ' . ($extractData['message'] ?? 'No Aadhaar number found'));
			}

			$aadhaarNumber = $extractData['data']['aadhaarNumber'];

			// Optional: Verify Aadhaar with external API if available
			$isVerified = false;
			$verificationData = null;

			// Uncomment and implement when you have Aadhaar verification API
			/*
        try {
            $verifyResponse = $client->post('https://api.rpacpc.com/services/aadhaar-verification', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token' => 'your-token-here',
                    'secretkey' => 'your-secret-key-here',
                ],
                'json' => ['aadhaar_number' => $aadhaarNumber],
                'timeout' => 30,
            ]);

            $verifyData = json_decode($verifyResponse->getBody(), true);
            Log::info('Aadhaar verification response', ['data' => $verifyData]);

            $isVerified = ($verifyData['status'] === 'SUCCESS' && 
                          !empty($verifyData['data']['is_valid']) && 
                          $verifyData['data']['is_valid']);
            $verificationData = $verifyData['data'] ?? null;
            
        } catch (\Exception $e) {
            Log::warning('Aadhaar verification failed, but extraction succeeded', [
                'error' => $e->getMessage(),
                'aadhaar_number' => $aadhaarNumber
            ]);
        }
        */

			$message = $isVerified
				? 'Aadhaar extracted and verified successfully'
				: 'Aadhaar extracted successfully';

			return response()->json([
				'status' => 'SUCCESS',
				'data' => [
					'aadhaarNumber' => $aadhaarNumber,
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
				Log::info('Cleaned up Aadhaar file from S3 due to processing error', ['filePath' => $filePath]);
			}

			Log::error('Aadhaar processing error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to process Aadhaar card: ' . $e->getMessage(),
				'suggestion' => 'Please upload a clear image/PDF of your Aadhaar card or enter the Aadhaar number manually.',
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
			Log::error('Document deletion error', [
				'error' => $e->getMessage(),
				'file_path' => $request->file_path
			]);

			return response()->json([
				'status' => 'FAILURE',
				'message' => 'Unable to delete document: ' . $e->getMessage(),
			], 500);
		}
	}
}
