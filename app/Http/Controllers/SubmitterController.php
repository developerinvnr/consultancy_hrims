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
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			abort(403, 'Unauthorized access.');
		}

		if (!in_array($requisition->status, ['Unsigned Agreement Uploaded', 'Agreement Completed'])) {
			return redirect()->route('dashboard')->with('error', 'No agreement available.');
		}

		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->firstOrFail();

		// ✅ MULTIPLE unsigned
		$unsignedAgreements = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'unsigned')
			->orderBy('created_at')
			->get();

		// ✅ SINGLE signed (latest)
		$signedAgreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'signed')
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
		if ($requisition->status !== 'Unsigned Agreement Uploaded') {
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

			if (empty($fileUrl)) {
				throw new \Exception('Failed to generate S3 file URL');
			}

			/* ---------------- SAVE SIGNED AGREEMENT ---------------- */
			AgreementDocument::create([
				'candidate_id'        => $candidate->id,
				'candidate_code'      => $candidate->candidate_code,
				'document_type'       => 'signed',
				'agreement_number'    => $request->agreement_number,
				'agreement_path'      => $filePath,
				'file_url'            => $fileUrl,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role'    => 'submitter',
			]);

			/* ---------------- UPDATE CANDIDATE & REQUISITION ---------------- */
			$candidate->update([
				'candidate_status' => 'Active',
				'final_status'     => 'A',
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
				CURLOPT_URL            => 'http://192.168.34.174/agrisamvida/generated_signed_agreement.php',
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
}
