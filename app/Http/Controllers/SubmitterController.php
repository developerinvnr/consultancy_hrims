<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\CandidateMaster;
use App\Models\AgreementDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\SignedAgreementSubmitted;

class SubmitterController extends Controller
{
	/**
	 * View unsigned agreement details
	 */
	public function viewAgreement(ManpowerRequisition $requisition)
	{
		$user = Auth::user();
		 if ($requisition->submitted_by_user_id !== $user->id && !$user->hasAnyRole(['hr_admin']))  
		{
			abort(403, 'Unauthorized access.');
		}
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->firstOrFail();

		$hasAgreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'agreement')
			->exists();

		if (!$hasAgreement) {
			return redirect()->route('dashboard')->with('error', 'No agreement available.');
		}

		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->firstOrFail();

		// ✅ MULTIPLE unsigned
		$unsignedAgreements = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'agreement')
			->where('sign_status', 'UNSIGNED')
			->where('stamp_type', 'E_STAMP')  // Filter to show only E-Stamp
			->orderBy('created_at')
			->get();


		// ✅ SINGLE signed (latest)
		$signedAgreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'agreement')
			->where('sign_status', 'SIGNED')
			->latest()
			->first();

		$isCompleted = $requisition->status === 'Agreement Completed';

		return view('submitter.agreement.view', compact(
			'requisition',
			'candidate',
			'unsignedAgreements',
			'signedAgreement',
			'isCompleted'
		));
	}

	/**
	 * Download unsigned agreement
	 */
	public function downloadAgreement(ManpowerRequisition $requisition)
	{
		// Auth check
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			abort(403, 'Unauthorized access.');
		}

		// Document id from query
		$docId = request()->query('doc');

		if (!$docId) {
			abort(400, 'Document ID missing.');
		}

		// Fetch agreement document
		$agreement = AgreementDocument::find($docId);

		if (!$agreement) {
			abort(404, 'Agreement document not found.');
		}

		// Fetch candidate for requisition
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();

		if (!$candidate) {
			abort(404, 'Candidate not found.');
		}

		// Security check: doc must belong to this candidate
		if ($agreement->candidate_id !== $candidate->id) {
			abort(403, 'Invalid agreement document.');
		}

		// File existence
		if ($agreement->file_url) {
			return redirect()->away($agreement->file_url);
		}

		// 🔹 Otherwise fallback to internal S3 (signed agreements)
		if (!Storage::disk('s3')->exists($agreement->agreement_path)) {
			abort(404, 'Agreement file not found in storage.');
		}

		return Storage::disk('s3')->download(
			$agreement->agreement_path,
			"{$candidate->candidate_code}_{$agreement->document_type}_agreement.pdf",
			['Content-Type' => 'application/pdf']
		);
	}



	/**
	 * Upload signed agreement
	 */
	public function uploadSignedAgreement(Request $request, ManpowerRequisition $requisition)
	{
		/* ---------------- AUTH CHECK ---------------- */
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access.'
			], 403);
		}

		/* ---------------- STATUS CHECK ---------------- */
		if ($requisition->status !== 'Unsigned Agreement Created') {
			return response()->json([
				'success' => false,
				'message' => 'Cannot upload signed agreement at this stage.'
			], 400);
		}

		/* ---------------- VALIDATION ---------------- */
		$request->validate([
			'agreement_file'   => 'required|file|mimes:pdf|max:10240',
			'agreement_number' => 'required|string|max:100',
		]);

		/* ---------------- FETCH CANDIDATE ---------------- */
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();

		if (!$candidate) {
			return response()->json([
				'success' => false,
				'message' => 'Candidate not found.'
			], 404);
		}

		DB::beginTransaction();

		try {
			/* ---------------- REMOVE OLD SIGNED AGREEMENT ---------------- */
			$oldSigned = AgreementDocument::where('candidate_id', $candidate->id)
				->where('document_type', 'agreement')
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

			if (empty($fileUrl)) {
				throw new \Exception('Failed to generate S3 file URL');
			}

			/* ---------------- SAVE SIGNED AGREEMENT ---------------- */
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
				'uploaded_by_role'    => 'submitter',
			]);

			/* ---------------- UPDATE CANDIDATE & REQUISITION ---------------- */
			$candidate->update([
				 'candidate_status' => 'Active',
                 'final_status' => 'A'
			]);

			$requisition->update([
				'status' => 'Agreement Completed',
			]);

			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();

			Log::error('Signed agreement upload failed', [
				'requisition_id' => $requisition->id,
				'candidate_id'   => $candidate->id,
				'error'          => $e->getMessage(),
			]);

			return response()->json([
				'success' => false,
				'message' => 'Failed to upload signed agreement.'
			], 500);
		}

		/* ---------------- SYNC TO AGRISAMVIDA (POST-COMMIT) ---------------- */
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
					'Accept: application/json',
				],
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
			]);

			$response  = curl_exec($ch);
			$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlError = curl_error($ch);
			curl_close($ch);

			Log::info('Agrisamvida signed agreement sync', [
				'http_code' => $httpCode,
				'response'  => $response,
				'error'     => $curlError,
				'payload'   => $apiPayload,
			]);
		} catch (\Exception $apiEx) {
			Log::warning('Agrisamvida API sync exception', [
				'message' => $apiEx->getMessage(),
			]);
		}

		/* ---------------- FINAL RESPONSE ---------------- */
		return response()->json([
			'success' => true,
			'message' => 'Signed agreement uploaded successfully. Agreement process completed.',
			'status'  => $candidate->candidate_status,
		]);
	}

	/**
	 * Show courier details form for an unsigned agreement
	 */
	public function showCourierForm(ManpowerRequisition $requisition, AgreementDocument $agreement)
	{
		// Auth check
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			abort(403, 'Unauthorized access.');
		}

		// Verify agreement belongs to this requisition
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();
		if (!$candidate || $agreement->candidate_id !== $candidate->id) {
			abort(404, 'Agreement not found.');
		}

		// Only allow for unsigned agreements
		if ($agreement->sign_status !== 'UNSIGNED') {
			return redirect()->back()->with('error', 'Courier details can only be added for unsigned agreements.');
		}

		// Get existing courier details if any
		$courierDetails = $agreement->courierDetails;

		return view('submitter.agreement.courier-details', compact('requisition', 'agreement', 'candidate', 'courierDetails'));
	}


	/**
	 * Save courier details for an agreement
	 */
	public function saveCourierDetails(Request $request, ManpowerRequisition $requisition, AgreementDocument $agreement)
	{
		// Auth check
		$user = Auth::user();

		if ($requisition->submitted_by_user_id !== $user->id && !$user->hasAnyRole(['hr_admin'])) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access.'
			], 403);
		}

		// Validate agreement
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();
		if (!$candidate || $agreement->candidate_id !== $candidate->id) {
			return response()->json([
				'success' => false,
				'message' => 'Agreement not found.'
			], 404);
		}

		// Validate request
		$request->validate([
			'courier_name' => 'required|string|max:150',
			'docket_number' => 'required|string|max:150',
			'dispatch_date' => 'required|date|before_or_equal:today',
		]);

		DB::beginTransaction();

		try {
			// Update or create courier details
			$courierDetails = $agreement->courierDetails()->updateOrCreate(
				['agreement_document_id' => $agreement->id],
				[
					'courier_name' => $request->courier_name,
					'docket_number' => $request->docket_number,
					'dispatch_date' => $request->dispatch_date,
					'sent_by_user_id' => Auth::id(),
				]
			);

			// Log to Laravel log instead of activity()
			Log::info('Courier details ' . ($courierDetails->wasRecentlyCreated ? 'added' : 'updated') . ' for agreement', [
				'agreement_id' => $agreement->id,
				'candidate_id' => $candidate->id,
				'user_id' => Auth::id(),
				'courier_name' => $request->courier_name,
				'docket_number' => $request->docket_number,
				'dispatch_date' => $request->dispatch_date
			]);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Courier details saved successfully.',
				'data' => $courierDetails
			]);
		} catch (\Exception $e) {
			DB::rollBack();

			Log::error('Failed to save courier details', [
				'agreement_id' => $agreement->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);

			return response()->json([
				'success' => false,
				'message' => 'Failed to save courier details. Please try again.'
			], 500);
		}
	}

	/**
	 * Mark courier as received (for admin/recruiter use)
	 */
	public function markCourierReceived(Request $request, ManpowerRequisition $requisition, AgreementDocument $agreement)
	{
		//dd($request->all());
		$user = Auth::user();

		// FIXED: Check if user does NOT have required roles
		if (!$user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access.'
			], 403);
		}

		// Remove the after_or_equal validation since date is readonly
		$request->validate([
			'received_date' => 'required|date',
		]);

		try {
			$courierDetails = $agreement->courierDetails;

			if (!$courierDetails) {
				return response()->json([
					'success' => false,
					'message' => 'Courier details not found.'
				], 404);
			}

			$courierDetails->update([
				'received_date' => $request->received_date,
				'received_by_user_id' => Auth::id(),
			]);

			return response()->json([
				'success' => true,
				'message' => 'Courier marked as received successfully.'
			]);
		} catch (\Exception $e) {
			Log::error('Failed to mark courier as received', [
				'agreement_id' => $agreement->id,
				'error' => $e->getMessage()
			]);

			return response()->json([
				'success' => false,
				'message' => 'Failed to mark courier as received.'
			], 500);
		}
	}
}
