<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamRequisitionController extends Controller
{
	public function index()
	{
		$managerId = auth()->user()->emp_id;

		$teamRequisitions = DB::table('manpower_requisitions as mr')
			->join('core_employee as ce', function ($join) {
				$join->on(
					DB::raw('CAST(mr.submitted_by_employee_id AS UNSIGNED)'),
					'=',
					'ce.employee_id'
				);
			})
			->where('ce.emp_reporting', $managerId)
			->select(
				'mr.id',
				'mr.request_code',
				'mr.requisition_type',
				'mr.submitted_by_name',
				'mr.submission_date',
				'mr.contract_start_date',
				'mr.contract_end_date',
				'mr.status'
			)
			->groupBy(
				'mr.id',
				'mr.request_code',
				'mr.requisition_type',
				'mr.submitted_by_name',
				'mr.submission_date',
				'mr.contract_start_date',
				'mr.contract_end_date',
				'mr.status'
			)
			->orderByDesc('mr.id')
			->paginate(10);

		return view(
			'team_requisitions.index',
			compact('teamRequisitions')
		);
	}
}
