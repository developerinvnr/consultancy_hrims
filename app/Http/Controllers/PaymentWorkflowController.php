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
				'c.requisition_type'
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

	public function syncPayments()
	{
		$records = DB::table('salary_processings as sp')
			->join('candidate_master as cm', 'cm.id', '=', 'sp.candidate_id')
			->join('report_exports as re', 're.reference_id', '=', 'sp.id')
			->where('re.report_type', 'payment_instruction')
			->whereIn('sp.payment_status', ['exported', 'failed'])
			->whereNull('sp.utr_number')
			->select(
				'sp.id',
				'sp.net_pay',
				're.exported_at',
				'cm.bank_account_no',
				'cm.candidate_code'
			)
			->limit(1000)
			->get();

		if ($records->isEmpty()) {
			return response()->json([
				'success' => false,
				'message' => 'No exported records pending sync'
			]);
		}

		$payload = ['payments' => []];

		foreach ($records as $row) {
			if ($row->net_pay <= 0) {
				continue;
			}

			$payload['payments'][] = [
				'beneficiary_account_number' => $row->bank_account_no,
				'amount' => (float)$row->net_pay,
				'date' => date('Y-m-d', strtotime($row->exported_at)),
				'source' => 'Peepal Bonsai',
				'source_reference' => $row->candidate_code
			];
		}

		Log::info('Matrix Payment Sync Request', $payload);

		$response = Http::withHeaders([
			'x-api-key' => 'YizwA2jvt69eXKbLAhyF0gAeJCHOhmQFTx1efMcN',
			'Content-Type' => 'application/json'
		])->post(
			'https://matrix.vnrin.in/api/v1/payment/verify-bulk',
			$payload
		);

		if (!$response->successful()) {
			Log::error('Matrix API connection failed', [
				'status' => $response->status(),
				'body' => $response->body()
			]);

			return response()->json([
				'success' => false,
				'message' => 'Matrix API connection failed'
			]);
		}

		$data = $response->json();
		Log::info('Payment Sync Response', $data);

		foreach ($data['results'] as $result) {
			/*
        CASE 1: MATCHED TRANSACTION
        */
			if (($result['match_status'] ?? null) === 'matched'
				&& !empty($result['transaction'])
			) {
				$transaction = $result['transaction'];

				// FIXED: Use $transaction instead of $nearest
				$account = $transaction['beneficiary_account_number'] ?? null;
				$amount  = $transaction['amount'] ?? null;

				if ($account && $amount) {
					$matchedRow = $records->first(function ($r) use ($account, $amount) {
						return $r->bank_account_no == $account
							&& $r->net_pay == $amount;
					});
				}

				if ($matchedRow) {
					DB::table('salary_processings')
						->where('id', $matchedRow->id)
						->update([
							'payment_status' => 'paid',
							'utr_number' => $transaction['utr_number'],
							'payment_date' => $transaction['value_date'],
							'verification_remark' => null
						]);
				}

				continue;
			}

			/*
        CASE 2: ALREADY VERIFIED (optional handling)
        */
			if (
				in_array('already_verified', $result['mismatch_reasons'] ?? [])
				&& !empty($result['nearest_match'])
				&& ($result['nearest_match']['is_verified'] ?? false)
			) {
				$nearest = $result['nearest_match'];

				$matchedRow = $records->first(function ($r) use ($nearest) {
					return $r->bank_account_no == ($nearest['beneficiary_account_number'] ?? null)
						&& $r->net_pay == ($nearest['amount'] ?? null);
				});

				if ($matchedRow) {
					DB::table('salary_processings')
						->where('id', $matchedRow->id)
						->update([
							'payment_status' => 'paid',
							'utr_number' => $nearest['utr_number'],
							'payment_date' => $nearest['value_date'],
							'verification_remark' => 'already_verified'
						]);
				}

				continue;
			}

			/*
        CASE 3: NOT FOUND / MISMATCH
        */
			if (!empty($result['mismatch_reasons'])) {
				$reason = implode(', ', $result['mismatch_reasons']);

				if (!empty($result['nearest_match'])) {
					$nearest = $result['nearest_match'];
					$reason .= " | Nearest Match - UTR: {$nearest['utr_number']}, Amount: {$nearest['amount']}, Date: {$nearest['value_date']}";

					// Match using account + amount if nearest_match exists
					$matchedRow = $records->first(function ($r) use ($nearest) {
						return $r->bank_account_no == ($nearest['beneficiary_account_number'] ?? null)
							&& $r->net_pay == ($nearest['amount'] ?? null);
					});

					if ($matchedRow) {
						DB::table('salary_processings')
							->where('id', $matchedRow->id)
							->update([
								'payment_status' => 'failed',
								'verification_remark' => $reason
							]);
					} else {
						// Try fallback by index if exact match not found
						if (isset($result['index']) && isset($records[$result['index']])) {
							DB::table('salary_processings')
								->where('id', $records[$result['index']]->id)
								->update([
									'payment_status' => 'failed',
									'verification_remark' => $reason
								]);
						} else {
							Log::warning('Could not match failed payment', [
								'index' => $result['index'] ?? null,
								'mismatch_reasons' => $result['mismatch_reasons'],
								'has_nearest_match' => !empty($result['nearest_match'])
							]);
						}
					}
				} else {
					// No nearest_match, try to use index
					if (isset($result['index']) && isset($records[$result['index']])) {
						DB::table('salary_processings')
							->where('id', $records[$result['index']]->id)
							->update([
								'payment_status' => 'failed',
								'verification_remark' => $reason
							]);
					} else {
						Log::warning('Mismatch without nearest match and invalid index', [
							'reason' => $reason,
							'index' => $result['index'] ?? null
						]);
					}
				}
			}
		}

		return response()->json([
			'success' => true,
			'message' => 'Payment sync completed successfully'
		]);
	}
}
