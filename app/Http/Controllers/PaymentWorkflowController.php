<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryPaymentExport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class PaymentWorkflowController extends Controller
{

	public function index()
	{
		return view('payment_workflow.index');
	}


	public function list(Request $request)
	{

		$tab = $request->tab;
		$month = $request->month ?? now()->format('m');
		$year  = $request->year ?? now()->format('Y');
		$exportStatus = $request->export_status;
		$requisitionType = $request->requisition_type;

		$query = DB::table('salary_processings as sp')

			->join('candidate_master as c', 'c.id', '=', 'sp.candidate_id')

			->where('sp.payment_instruction', 'release')

			->where('sp.month', $month)

			->where('sp.year', $year)

			->select(
				'sp.*',
				'c.candidate_code',
				'c.candidate_name',
				'c.requisition_type',
				DB::raw('
					COALESCE(sp.total_payable,
						(COALESCE(sp.net_pay,0) + COALESCE(sp.arrear_amount,0))
					) as final_payable
				')
			);


		if ($requisitionType) {

			$query->where('c.requisition_type', $requisitionType);
		}


		if ($tab == 'pending') {

			$query->where('payment_status', 'pending');
		}

		if ($tab == 'instruction') {

			$query->whereIn('payment_status', ['approved', 'exported', 'failed']);
		}

		if ($tab == 'confirmed') {
			$query->where('payment_status', 'paid');
		}

		if ($tab == 'unsettled') {
			$query->where('payment_status', 'failed');
		}

		/** Month filter */

		if ($month) {

			$query->where('month', $month);
		}


		/** Export filter (instruction tab only) */

		if ($tab == 'instruction') {

			if ($exportStatus == 'exported') {

				$query->whereExists(function ($q) {

					$q->select(DB::raw(1))
						->from('report_exports as re')
						->whereColumn('re.reference_id', 'sp.id')
						->where('re.report_type', 'payment_instruction');
				});
			}


			if ($exportStatus == 'not_exported') {

				$query->whereNotExists(function ($q) {

					$q->select(DB::raw(1))
						->from('report_exports as re')
						->whereColumn('re.reference_id', 'sp.id')
						->where('re.report_type', 'payment_instruction');
				});
			}
		}


		return response()->json(

			$query->orderBy('id', 'desc')->get()

		);
	}


	public function approve(Request $request)
	{

		DB::table('salary_processings')
			->whereIn('id', $request->ids)
			->update([
				'payment_status' => 'approved',
				'payment_date' => now()
			]);
		return response()->json([
			'success' => true
		]);
	}



	public function export(Request $request)
	{
		if (empty($request->ids)) {
			return back()->with('error', 'Please select records first');
		}

		$query = DB::table('salary_processings as sp')
			->join('candidate_master as c', 'c.id', '=', 'sp.candidate_id')
			->select('sp.*', 'c.candidate_code', 'c.requisition_type')
			->whereIn('sp.id', $request->ids)
			->where('sp.payment_status', 'approved');

		if ($request->requisition_type) {
			$query->where('c.requisition_type', $request->requisition_type);
		}

		$records = $query->get();

		$batchNo = 'PAYINST' . time();

		foreach ($records as $row) {

			DB::table('report_exports')->updateOrInsert(
				[
					'reference_id' => $row->id,
					'report_type' => 'payment_instruction'
				],
				[
					'reference_table' => 'salary_processings',
					'batch_no' => $batchNo,
					'exported_by' => auth()->id(),
					'exported_at' => now(),
					'updated_at' => now(),
					'created_at' => now()
				]
			);
		}

		DB::table('salary_processings')
			->whereIn('id', $request->ids)   // ✅ FIXED HERE
			->update([
				'payment_status' => 'exported'
			]);

		return Excel::download(
			new SalaryPaymentExport($records),
			'payment_instruction.xlsx'
		);
	}



	public function confirm(Request $request)
	{

		DB::table('salary_processings')

			->whereIn('id', $request->ids)

			->update([

				'payment_status' => 'confirmed'

			]);


		return response()->json([

			'success' => true

		]);
	}

	public function syncPayments(Request $request)
	{
		// Get month and year from request (sent from UI)
		$month = $request->month ?? date('m');
		$year = $request->year ?? date('Y');

		$records = DB::table('salary_processings as sp')
			->join('candidate_master as cm', 'cm.id', '=', 'sp.candidate_id')
			->join('report_exports as re', 're.reference_id', '=', 'sp.id')
			->where('re.report_type', 'payment_instruction')
			->whereIn('sp.payment_status', ['exported', 'failed'])
			->whereNull('sp.utr_number')
			->where('sp.month', $month)
			->where('sp.year', $year)
			->select(
				'sp.id',
				DB::raw('
					COALESCE(sp.total_payable,
						(COALESCE(sp.net_pay,0) + COALESCE(sp.arrear_amount,0))
					) as final_payable
				'),
				're.exported_at',
				'cm.bank_account_no',
				'cm.candidate_code'
			)
			->limit(1000)
			->get();

		if ($records->isEmpty()) {
			return response()->json([
				'success' => false,
				'message' => 'No exported records pending sync for ' . $month . '/' . $year
			]);
		}

		$payload = ['payments' => []];

		foreach ($records as $row) {
			if ($row->final_payable <= 0) {
				continue;
			}

			$payload['payments'][] = [
				'beneficiary_account_number' => $row->bank_account_no,
				'amount' => (float)$row->final_payable,
				'date' => date('Y-m-d', strtotime($row->exported_at)),
				'source' => 'Peepal Bonsai',
				'source_reference' => $row->candidate_code
			];
		}


		$response = Http::withHeaders([
			'x-api-key' => 'YizwA2jvt69eXKbLAhyF0gAeJCHOhmQFTx1efMcN',
			'Content-Type' => 'application/json'
		])->post(
			'https://matrix.vnrin.in/api/v1/payment/verify-bulk',
			$payload
		);

		if (!$response->successful()) {
		

			return response()->json([
				'success' => false,
				'message' => 'Matrix API connection failed'
			]);
		}

		$data = $response->json();

		foreach ($data['results'] as $result) {
			$matchStatus = $result['match_status'] ?? null;

			// CASE 1: MATCHED - Must verify account and amount match
			if ($matchStatus === 'matched' && !empty($result['transaction'])) {
				$transaction = $result['transaction'];
				$account = $transaction['beneficiary_account_number'] ?? null;
				$amount = $transaction['amount'] ?? null;

				// Find matching record by account and amount
				$matchedRow = $records->first(function ($r) use ($account, $amount) {
					return $r->bank_account_no == $account && $r->final_payable == $amount;
				});

				if ($matchedRow) {
					DB::table('salary_processings')
						->where('id', $matchedRow->id)
						->update([
							'payment_status' => 'paid',
							'utr_number' => $transaction['utr_number'],
							'payment_date' => $transaction['value_date'],
							'verification_remark' => null
						]);

				} else {
					Log::warning('Matched transaction but account/amount mismatch', [
						'account' => $account,
						'amount' => $amount,
						'utr' => $transaction['utr_number'] ?? null
					]);
				}
			}
			// CASE 2: NOT FOUND - Use index to update remark
			else if ($matchStatus === 'not_found' && isset($result['index']) && isset($records[$result['index']])) {
				$matchedRow = $records[$result['index']];
				$mismatchReasons = $result['mismatch_reasons'] ?? [];
				$reason = implode(', ', $mismatchReasons);

				// Add nearest match info if available
				if (!empty($result['nearest_match'])) {
					$nearest = $result['nearest_match'];
					$reason .= " | Nearest Match - UTR: {$nearest['utr_number']}, Amount: {$nearest['amount']}, Date: {$nearest['value_date']}";
				}

				// Check if already verified
				$isAlreadyVerified = in_array('already_verified', $mismatchReasons);

				if ($isAlreadyVerified && !empty($result['nearest_match']['utr_number'])) {
					// Already verified - update with UTR
					DB::table('salary_processings')
						->where('id', $matchedRow->id)
						->update([
							'payment_status' => 'paid',
							'utr_number' => $result['nearest_match']['utr_number'],
							'payment_date' => $result['nearest_match']['value_date'],
							'verification_remark' => 'already_verified'
						]);

				
				} else {
					// Just update the remark
					DB::table('salary_processings')
						->where('id', $matchedRow->id)
						->update([
							'verification_remark' => $reason
						]);

					
				}
			}
		}

		return response()->json([
			'success' => true,
			'message' => 'Payment sync completed successfully for ' . $month . '/' . $year
		]);
	}
}
