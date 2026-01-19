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
		// Check if user is the submitter
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			abort(403, 'Unauthorized access.');
		}

		// Check if status allows viewing agreement
		if (!in_array($requisition->status, ['Unsigned Agreement Uploaded', 'Agreement Completed'])) {
			return redirect()->route('dashboard')->with('error', 'No agreement available for viewing.');
		}

		// Get candidate
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();

		if (!$candidate) {
			return redirect()->route('dashboard')->with('error', 'Candidate not found.');
		}

		// Get agreement documents
		$unsignedAgreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'unsigned')
			->first();

		$signedAgreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', 'signed')
			->first();

		// Determine view type
		$isCompleted = $requisition->status === 'Agreement Completed';

		return view('submitter.agreement.view', compact(
			'requisition',
			'candidate',
			'unsignedAgreement',
			'signedAgreement',
			'isCompleted'
		));
	}

	/**
	 * Download unsigned agreement
	 */
	public function downloadAgreement(ManpowerRequisition $requisition)
	{
		// Get type from query parameter first, then default to unsigned
		$type = request()->input('type', 'unsigned');
		// Check if user is the submitter
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			abort(403, 'Unauthorized access.');
		}

		// Validate type
		if (!in_array($type, ['unsigned', 'signed'])) {
			abort(400, 'Invalid agreement type.');
		}

		// Check if status allows downloading this type
		if ($type === 'signed') {
			if ($requisition->status !== 'Agreement Completed') {
				abort(403, 'Signed agreement not available yet. Current status: ' . $requisition->status);
			}
		} else {
			// For unsigned
			if (!in_array($requisition->status, ['Unsigned Agreement Uploaded', 'Agreement Completed'])) {
				abort(403, 'Unsigned agreement not available. Current status: ' . $requisition->status);
			}
		}

		// Get candidate
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();

		if (!$candidate) {
			abort(404, 'Candidate not found.');
		}

		// Get agreement document
		$agreement = AgreementDocument::where('candidate_id', $candidate->id)
			->where('document_type', $type)
			->first();

		if (!$agreement) {
			abort(404, ucfirst($type) . ' agreement not found in database.');
		}
		

		if (!$agreement->agreement_path) {
			abort(404, ucfirst($type) . ' agreement file path not set.');
		}
		// Check S3 storage
		if (Storage::disk('s3')->exists($agreement->agreement_path)) {
			$filePath = $agreement->agreement_path;
			$filename = "{$candidate->candidate_code}_{$type}_agreement.pdf";

			return Storage::disk('s3')->download($filePath, $filename, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'inline; filename="' . $filename . '"'
			]);
		}
        //dd($agreement->agreement_path);
		// Fallback to local storage
		$filePath = storage_path('app/' . $agreement->agreement_path);
		if (!file_exists($filePath)) {
			abort(404, 'Agreement file not found in storage.');
		}

		$filename = "{$candidate->candidate_code}_{$type}_agreement.pdf";

		return response()->download($filePath, $filename, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'inline; filename="' . $filename . '"'
		]);
	}

	/**
	 * Upload signed agreement
	 */
	public function uploadSignedAgreement(Request $request, ManpowerRequisition $requisition)
	{
		//dd($request->all());
		// Check if user is the submitter
		if ($requisition->submitted_by_user_id !== Auth::id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized access.'
			], 403);
		}

		// Check if status allows upload
		if ($requisition->status !== 'Unsigned Agreement Uploaded') {
			return response()->json([
				'success' => false,
				'message' => 'Cannot upload signed agreement at this stage.'
			], 400);
		}

		$request->validate([
			'agreement_file' => 'required|file|mimes:pdf|max:10240',
			'agreement_number' => 'required|string|max:100',
		]);

		// Get candidate
		$candidate = CandidateMaster::where('requisition_id', $requisition->id)->first();

		if (!$candidate) {
			return response()->json([
				'success' => false,
				'message' => 'Candidate not found.'
			], 404);
		}

		DB::beginTransaction();
		try {
			// Check if already has signed agreement
			$existingSigned = AgreementDocument::where('candidate_id', $candidate->id)
				->where('document_type', 'signed')
				->first();

			// Delete old signed agreement if exists
			if ($existingSigned) {
				Storage::disk('s3')->delete($existingSigned->agreement_path);
				$existingSigned->delete();
			}

			// Upload file to S3
			$file = $request->file('agreement_file');
			$fileName = 'signed_' . $candidate->candidate_code . '_' . time() . '.pdf';
			$filePath = 'agreements/signed/' . $fileName;

			Storage::disk('s3')->put($filePath, file_get_contents($file));

			// Create signed agreement record
			AgreementDocument::create([
				'candidate_id' => $candidate->id,
				'candidate_code' => $candidate->candidate_code,
				'document_type' => 'signed',
				'agreement_number' => $request->agreement_number,
				'agreement_path' => $filePath,
				'uploaded_by_user_id' => Auth::id(),
				'uploaded_by_role' => 'submitter',
			]);

			// Update candidate status
			$candidate->candidate_status = 'Signed Agreement Uploaded';
			$candidate->save();

			// Update requisition status
			$requisition->status = 'Agreement Completed';
			$requisition->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Signed agreement uploaded successfully. Agreement process completed.',
				'status' => $candidate->candidate_status
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error uploading signed agreement: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Failed to upload agreement: ' . $e->getMessage()
			], 500);
		}
	}
}
