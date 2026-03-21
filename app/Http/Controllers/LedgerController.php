<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CandidateMaster;
use DB;
use Maatwebsite\Excel\Facades\Excel;

class LedgerController extends Controller
{
	public function index(Request $request)
	{
		$tab = $request->get('tab', 'operative');

		$query = CandidateMaster::query()->where('final_status', 'A');

		// ✅ APPLY SAME FILTERS AS MASTER
		if ($request->financial_year) {
			[$startYear, $endYear] = explode('-', $request->financial_year);

			$startDate = $startYear . '-04-01';
			$endDate   = $endYear . '-03-31';

			$query->whereBetween('contract_start_date', [$startDate, $endDate]);
		}

		if ($request->month) {
			$month = $request->month;
			$year = date('Y');

			$query->whereMonth('contract_start_date', $month);
		}

		if ($request->department_id) {
			$query->where('department_id', $request->department_id);
		}

		if ($request->work_location) {
			$query->where('work_location_hq', $request->work_location);
		}


		if ($request->requisition_type) {

			$query->where('requisition_type', $request->requisition_type);

		}

		if ($request->search) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('candidate_name', 'like', "%$search%")
					->orWhere('pan_no', 'like', "%$search%")
					->orWhere('candidate_code', 'like', "%$search%");
			});
		}

		// ✅ TAB LOGIC
		if ($tab == 'inoperative') {
			$query->where(function ($q) {
				$q->whereNull('pan_status_2')
					->orWhere('pan_status_2', '!=', 'Operative');
			});
		}

		if ($tab == 'operative') {
			$query->where('pan_status_2', 'Operative')
				->where(function ($q) {
					$q->whereNull('ledger_created')
						->orWhere('ledger_created', 0);
				});
		}

		if ($tab == 'created') {
			$query->where('ledger_created', 1);
		}

		if ($request->requisition_type) {
			$query->where('requisition_type', $request->requisition_type);
		}

		// ✅ PAN GROUPING
		$subQuery = $query->select(DB::raw('MAX(id) as id'))
			->whereNotNull('pan_no')
			->groupBy('pan_no');

		$candidates = CandidateMaster::whereIn('id', $subQuery)
			->with([
				'department',
				'zoneRef',
				'regionRef',
				'businessUnit',
				'vertical',
				'subDepartmentRef',
				'function',
				'cityMaster',
				'workState',
				'reportingManager',
				'unsignedAgreements.courierDetails',
				'signedAgreements.courierDetails'
			])
			->paginate(20)
			->appends($request->all());
		$departments = \App\Models\CoreDepartment::orderBy('department_name')->get();

		return view('ledger.index', compact('candidates', 'tab', 'departments'));
	}

	// 🔴 PAN INOPERATIVE
	private function getInoperative()
	{
		$panIds = CandidateMaster::where(function ($q) {
			$q->whereNull('pan_status_2')
				->orWhere('pan_status_2', '!=', 'Operative');
		})
			->select('pan_no', DB::raw('MAX(id) as id'))
			->whereNotNull('pan_no')
			->groupBy('pan_no')
			->pluck('id');

		return CandidateMaster::whereIn('id', $panIds)->paginate(20);
	}

	// 🟡 PAN OPERATIVE (ACTION TAB)
	private function getOperative()
	{
		$panIds = CandidateMaster::where('pan_status_2', 'Operative')
			->where(function ($q) {
				$q->whereNull('ledger_created')
					->orWhere('ledger_created', 0);
			})
			->whereNotNull('pan_no')
			->select('pan_no', DB::raw('MAX(id) as id'))
			->groupBy('pan_no')
			->pluck('id');

		return CandidateMaster::whereIn('id', $panIds)->paginate(20);
	}

	// 🟢 LEDGER CREATED
	private function getCreated()
	{
		$panIds = CandidateMaster::where('ledger_created', 1)
			->whereNotNull('pan_no')
			->select('pan_no', DB::raw('MAX(id) as id'))
			->groupBy('pan_no')
			->pluck('id');

		return CandidateMaster::whereIn('id', $panIds)->paginate(20);
	}

	// ✅ ACTION
	public function markCreated(Request $request)
	{
		$ids = $request->ids;

		$pans = CandidateMaster::whereIn('id', $ids)->pluck('pan_no');

		CandidateMaster::whereIn('pan_no', $pans)
			->update([
				'ledger_created' => 1,
				'ledger_created_at' => now()
			]);

		return response()->json(['success' => true]);
	}

	public function exportOperative(Request $request)
	{
		$ids = explode(',', $request->ids);

		$records = CandidateMaster::whereIn('id', $ids)
			->with([
				'department',
				'zoneRef',
				'regionRef',
				'businessUnit',
				'vertical',
				'subDepartmentRef',
				'function',
				'cityMaster',
				'workState',
				'reportingManager'
			])
			->get();

		// ✅ Update ledger_created
		$pans = $records->pluck('pan_no');

		CandidateMaster::whereIn('pan_no', $pans)
			->update([
				'ledger_created' => 1,
				'ledger_created_at' => now()
			]);

		return Excel::download(
			new \App\Exports\LedgerOperativeExport($records),
			'ledger_export.xlsx'
		);
	}

	public function export(Request $request)
	{
		$tab = $request->get('tab', 'created');

		$query = CandidateMaster::query()->where('final_status', 'A');

		// 🔹 SAME FILTERS
		if ($request->financial_year) {
			[$startYear, $endYear] = explode('-', $request->financial_year);
			$query->whereBetween('contract_start_date', [
				$startYear . '-04-01',
				$endYear . '-03-31'
			]);
		}

		if ($request->department_id) {
			$query->where('department_id', $request->department_id);
		}

		if ($request->work_location) {
			$query->where('work_location_hq', $request->work_location);
		}

		if ($request->requisition_type) {
			$query->where('requisition_type', $request->requisition_type);
		}

		if ($request->search) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('candidate_name', 'like', "%$search%")
					->orWhere('pan_no', 'like', "%$search%");
			});
		}

		// 🔹 TAB FILTER
		if ($tab == 'created') {
			$query->where('ledger_created', 1);
		}

		// 🔹 PAN GROUPING
		$panIds = $query->select('pan_no', DB::raw('MAX(id) as id'))
			->groupBy('pan_no')
			->pluck('id');

		$records = CandidateMaster::whereIn('id', $panIds)
			->with([
				'department',
				'zoneRef',
				'regionRef',
				'businessUnit',
				'vertical',
				'subDepartmentRef',
				'function',
				'cityMaster',
				'workState',
				'reportingManager',
				'unsignedAgreements.courierDetails',
				'signedAgreements.courierDetails'
			])
			->get();

		return \Maatwebsite\Excel\Facades\Excel::download(
			new \App\Exports\LedgerExport($records),
			'ledger_created.xlsx'
		);
	}
}
