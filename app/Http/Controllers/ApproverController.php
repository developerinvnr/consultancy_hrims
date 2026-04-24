<?php

namespace App\Http\Controllers;

use App\Models\ManpowerRequisition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApproverController extends Controller
{
	/**
	 * Show pending requisitions for approval
	 */
	public function dashboard()
	{
		$user = Auth::user();

		// Get pending requisitions where approver_id matches user's emp_id
		$pendingRequisitions = ManpowerRequisition::where('status', 'Pending Approval')
			->where('approver_id', $user->emp_id)
			->with(['function', 'department', 'vertical'])
			->orderBy('approval_date', 'asc')
			->get();

		// Get requisitions history (approved/rejected by this approver)
		$requisitionsHistory = ManpowerRequisition::where(function ($query) use ($user) {
			$query->where('approver_id', $user->emp_id)
				->orWhere('previous_approver_id', $user->emp_id);
		})
			->whereIn('status', ['Approved', 'Rejected', 'Processing'])
			->with(['function', 'department', 'vertical'])
			->orderBy('updated_at', 'desc')
			->limit(20)
			->get();

		return view('approver.dashboard', compact('pendingRequisitions', 'requisitionsHistory', 'user'));
	}

	/**
	 * View requisition details for approval
	 */
	public function viewRequisition(ManpowerRequisition $requisition)
	{
		$user = Auth::user();

		// Check if user is the current approver
		if ($requisition->approver_id != $user->emp_id) {
			abort(403, 'You are not authorized to approve this requisition.');
		}

		// Check if requisition is in pending approval status
		if ($requisition->status != 'Pending Approval') {
			return redirect()->back()
				->with('error', 'This requisition is not pending approval.');
		}

		$requisition->load([
			'function',
			'department',
			'vertical',
			'submittedBy',
			'documents',
			'candidate'  // Load the candidate relationship
		]);

		// ========== FIX: Load agreements data ==========
		$agreements = $this->getAgreementsData($requisition);

		return view('approver.view', compact('requisition', 'agreements'));
	}

	/**
	 * Get agreements data for the requisition
	 */
	private function getAgreementsData($requisition)
	{
		$agreements = [
			'unsigned' => collect(),
			'signed' => null
		];

		// Check if candidate exists
		if (!$requisition->candidate) {
			return $agreements;
		}

		// Get all agreement documents for this candidate
		$agreementDocs = AgreementDocument::where('candidate_id', $requisition->candidate->id)
			->where('document_type', 'agreement')
			->orderBy('created_at', 'desc')
			->get();

		foreach ($agreementDocs as $doc) {
			// Add file URL
			$doc->file_url = $doc->file_path ? Storage::disk('s3')->url($doc->file_path) : null;

			if ($doc->sign_status === 'SIGNED') {
				// Get courier details for signed agreement
				$doc->courierDetails = $doc->courierDetails()->first();
				$agreements['signed'] = $doc;
			} else {
				$agreements['unsigned']->push($doc);
			}
		}

		return $agreements;
	}


	/**
	 * Approve requisition
	 */
	public function approveRequisition(Request $request, ManpowerRequisition $requisition)
	{

		$user = Auth::user();
		//dd($request->all());
		// Validate authorization
		if ($requisition->approver_id != $user->emp_id) {
			abort(403, 'You are not authorized to approve this requisition.');
		}
		// Validate status
		if ($requisition->status != 'Pending Approval') {
			return redirect()->back()
				->with('error', 'This requisition is not pending approval.');
		}

		// dd($final_days);
		// $request->validate([
		// 'contract_start_date' => 'required|date',
		// 'contract_end_date' => 'required|date|after:contract_start_date',
		// 'remuneration_per_month' => 'required|numeric|min:1',
		// 'approver_remarks' => 'nullable|string|max:1000'
		// ]);

		DB::beginTransaction();
		try {

			$requisition->contract_start_date = $request->contract_start_date;
			$requisition->contract_end_date = $request->contract_end_date;
			$requisition->remuneration_per_month = $request->remuneration_per_month;
			$start = \Carbon\Carbon::parse($request->contract_start_date);
			$end = \Carbon\Carbon::parse($request->contract_end_date);
			$requisition->contract_duration = $start->diffInDays($end);
			// Update requisition status to "Approved"
			$requisition->status = 'Approved';
			$requisition->approver_remarks = $request->approver_remarks;
			$requisition->approval_date = now();
			$requisition->save();



			// // Create approval log
			// \App\Models\ApprovalLog::create([
			// 	'requisition_id' => $requisition->id,
			// 	'action' => 'approved',
			// 	'performed_by_user_id' => $user->id,
			// 	'performed_by_role' => 'approver',
			// 	'action_date' => now(),
			// 	'previous_status' => 'Pending Approval',
			// 	'new_status' => 'Approved',
			// 	'remarks' => $request->approver_remarks
			// ]);

			DB::commit();

			// FIX: Redirect to COMMON dashboard, not approver.dashboard
			return redirect()->route('dashboard')
				->with('success', 'Requisition approved successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()
				->with('error', 'Failed to approve requisition: ' . $e->getMessage());
		}
	}

	public function rejectRequisition(Request $request, ManpowerRequisition $requisition)
	{
		$user = Auth::user();

		// Validate authorization
		if ($requisition->approver_id != $user->emp_id) {
			abort(403, 'You are not authorized to reject this requisition.');
		}

		// Validate status
		if ($requisition->status != 'Pending Approval') {
			return redirect()->back()
				->with('error', 'This requisition is not pending approval.');
		}

		$request->validate([
			'rejection_reason' => 'required|string|max:1000'
		]);

		DB::beginTransaction();
		try {
			// Update requisition status to "Rejected"
			$requisition->status = 'Rejected';
			$requisition->rejection_reason = $request->rejection_reason;
			$requisition->rejection_date = now();
			$requisition->rejected_by_user_id = auth()->id();
			$requisition->save();

			DB::commit();

			// FIX: Redirect to COMMON dashboard, not approver.dashboard
			return redirect()->route('dashboard')
				->with('success', 'Requisition rejected successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()
				->with('error', 'Failed to reject requisition: ' . $e->getMessage());
		}
	}

	/**
	 * Get approval statistics for dashboard
	 */
	public function getStatistics()
	{
		$user = Auth::user();

		$stats = [
			'pending' => ManpowerRequisition::where('status', 'Pending Approval')
				->where('approver_id', $user->emp_id)
				->count(),

			'approved' => ManpowerRequisition::where('status', 'Approved')
				->where('previous_approver_id', $user->emp_id)
				->count(),

			'rejected' => ManpowerRequisition::where('status', 'Rejected')
				->where('previous_approver_id', $user->emp_id)
				->count(),

			'total' => ManpowerRequisition::where(function ($query) use ($user) {
				$query->where('approver_id', $user->emp_id)
					->orWhere('previous_approver_id', $user->emp_id);
			})
				->count()
		];

		return response()->json($stats);
	}
	public function pendingApprovals()
	{
		$user = Auth::user();

		// Get pending requisitions where user is approver
		$pending_approvals = ManpowerRequisition::where('status', 'Pending Approval')
			->where('approver_id', $user->emp_id)
			->with(['department', 'submittedBy'])
			->orderBy('submission_date', 'asc')
			->get(); // Use get() instead of paginate() if partial expects collection

		return view('approver.pending-approvals', compact('pending_approvals'));
	}
}
