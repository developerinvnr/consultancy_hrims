<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CandidateMaster;
use App\Models\ManpowerRequisition;
use App\Models\SalaryProcessing;
use App\Models\AgreementDocument;
use App\Models\AgreementCourier;

class DashboardController extends Controller
{
	public function managementDashboard(Request $request)
	{
		// Get filter options for the filters card
		$years = range(date('Y'), 2020);
		$departments = \App\Models\CoreDepartment::orderBy('department_name')->get();
		$businessUnits = \App\Models\CoreBusinessUnit::orderBy('business_unit_name')->get();
		$zones = \App\Models\CoreZone::orderBy('zone_name')->get();
		$regions = \App\Models\CoreRegion::orderBy('region_name')->get();
		$territories = \App\Models\CoreTerritory::orderBy('territory_name')->get();
		$verticals = \App\Models\CoreVertical::orderBy('vertical_name')->get();

		// ========== SIMPLIFIED MANAGEMENT METRICS ==========
		$reqTab = $request->get('req_tab', 'submission');
		$expTab = $request->get('exp_tab', 'exp30');

		// Get requisitions data for the table
		$query = ManpowerRequisition::with(['submittedBy', 'department', 'candidate', 'rejectedBy', 'currentApprover']);

		$exemptTabs = ['approval', 'submission', 'correction_required', 'hr_verified', 'approved', 'rejected'];
		if (!in_array($reqTab, $exemptTabs)) {
			$query->whereHas('candidate', function ($q) {
				$q->where('candidate_status', '!=', 'Inactive');
			});
		}

		switch ($reqTab) {
			case 'submission':
				$query->where('status', 'Pending HR Verification');
				break;
			case 'correction_required':
				$query->where('status', 'Correction Required');
				break;
			case 'hr_verified':
				$query->where('status', 'Hr Verified');
				break;
			case 'approval':
				$query->where('status', 'Pending Approval');
				break;
			case 'approved':
				$query->where('status', 'Approved');
				break;
			case 'unsigned':
				$query->whereHas('candidate', function ($q) {
					$q->where('candidate_status', 'Unsigned Agreement Created');
				});
				break;
			case 'dispatch_pending':
				$query->whereHas('candidate', function ($q) {
					$q->where('candidate_status', 'Signed Agreement Uploaded')
						->whereHas('signedAgreements', function ($q) {
							$q->whereDoesntHave('courierDetails');
						});
				});
				break;
			case 'courier_pending':
				$query->whereHas('candidate', function ($candidateQuery) {
					$candidateQuery->where('candidate_status', '!=', 'Cancelled')
						->whereHas('signedAgreements.courierDetails', function ($courierQuery) {
							$courierQuery->whereNotNull('dispatch_date')
								->whereNull('received_date');
						});
				});
				break;
			case 'file_pending':
				$query->whereHas('candidate', function ($q) {
					$q->where('candidate_status', 'Signed Agreement Uploaded')
						->whereNull('file_created_date')
						->whereHas('signedAgreements.courierDetails', function ($q) {
							$q->whereNotNull('received_date');
						});
				});
				break;
			case 'active':
				$query->whereHas('candidate', fn($q) => $q->where('candidate_status', 'Active'));
				break;
			case 'inactive':
				$query->whereHas('candidate', fn($q) => $q->where('candidate_status', 'Inactive'));
				break;
			case 'rejected':
				$query->where(function ($q) {
					$q->where('status', 'Rejected')
						->orWhereHas('candidate', fn($sub) => $sub->where('candidate_status', 'Rejected'));
				});
				break;
		}

		$recent_requisitions = $query
			->latest()
			->paginate(10)
			->appends(['req_tab' => $reqTab, 'exp_tab' => $expTab]);

		// Load agreement and courier data for each requisition
		foreach ($recent_requisitions as $requisition) {
			if ($requisition->candidate) {
				$unsignedAgreement = AgreementDocument::where('candidate_id', $requisition->candidate->id)
					->where('document_type', 'agreement')
					->where('sign_status', 'UNSIGNED')
					->latest()
					->first();

				$signedAgreement = AgreementDocument::where('candidate_id', $requisition->candidate->id)
					->where('document_type', 'agreement')
					->where('sign_status', 'SIGNED')
					->latest()
					->first();

				$courierDetails = null;
				if ($signedAgreement) {
					$courierDetails = AgreementCourier::where('agreement_document_id', $signedAgreement->id)
						->latest()
						->first();
				}

				$requisition->unsigned_agreement = $unsignedAgreement;
				$requisition->signed_agreement = $signedAgreement;
				$requisition->courier_details = $courierDetails;
			}

			// Calculate ageing days
			$ageDays = 0;
			$baseDate = null;

			if ($requisition->candidate) {
				$candidateStatus = $requisition->candidate->candidate_status;
				switch ($candidateStatus) {
					case 'Agreement Pending':
						$baseDate = $requisition->approval_date;
						break;
					case 'Unsigned Agreement Created':
						if ($requisition->unsigned_agreement) {
							$baseDate = $requisition->unsigned_agreement->created_at;
						} else {
							$baseDate = $requisition->candidate->created_at;
						}
						break;
					case 'Signed Agreement Uploaded':
						if ($requisition->courier_details && !$requisition->courier_details->received_date) {
							$baseDate = $requisition->courier_details->dispatch_date ?? $requisition->candidate->updated_at;
						} else {
							$baseDate = $requisition->signed_agreement ? $requisition->signed_agreement->created_at : $requisition->approval_date;
						}
						break;
					default:
						$baseDate = $this->getBaseDateFromStatus($requisition);
						break;
				}
			} else {
				$baseDate = $this->getBaseDateFromStatus($requisition);
			}

			if ($requisition->courier_details && !$requisition->courier_details->received_date) {
				if ($requisition->courier_details->dispatch_date) {
					$baseDate = $requisition->courier_details->dispatch_date;
				}
			}

			if ($baseDate) {
				$baseDateCarbon = Carbon::parse($baseDate);
				$now = Carbon::now();
				$ageDays = floor($baseDateCarbon->diffInDays($now));
			}

			$requisition->ageing_days = (int) $ageDays;

			if ($ageDays < 1) {
				$requisition->priority_label = '🟢 Low';
				$requisition->priority_color = 'success';
			} elseif ($ageDays <= 2) {
				$requisition->priority_label = '🟡 Medium';
				$requisition->priority_color = 'warning';
			} else {
				$requisition->priority_label = '🔴 High';
				$requisition->priority_color = 'danger';
			}
		}

		// Sort by ageing days
		$sortedCollection = $recent_requisitions->getCollection()
			->sortByDesc('ageing_days')
			->values();
		$recent_requisitions->setCollection($sortedCollection);

		// ========== SIMPLIFIED STATS FOR MANAGEMENT ==========
		$activeCount = CandidateMaster::where('candidate_status', 'Active')->count();
		$inProcessCount = CandidateMaster::whereIn('candidate_status', [
			'Agreement Pending',
			'Unsigned Agreement Created',
			'Signed Agreement Uploaded'
		])->count();

		// Party type breakdown for active candidates
		$contractualCount = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'Contractual')->count();
		$tfaCount = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'TFA')->count();
		$cbCount = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'CB')->count();

		// Attention Panel Stats
		$attention = [];
		$attention['delayed_cases'] = $recent_requisitions->getCollection()
			->filter(function ($requisition) {
				return $requisition->candidate
					&& $requisition->ageing_days > 2
					&& !in_array($requisition->candidate->candidate_status, ['Inactive', 'Rejected', 'Cancelled', 'Active']);
			})->count();
		$attention['agreement_not_signed'] = CandidateMaster::where('candidate_status', 'Unsigned Agreement Created')->count();
		$attention['courier_pending'] = ManpowerRequisition::whereHas('candidate', function ($candidateQuery) {
			$candidateQuery->where('candidate_status', '!=', 'Cancelled')
				->whereHas('signedAgreements.courierDetails', function ($courierQuery) {
					$courierQuery->whereNotNull('dispatch_date')->whereNull('received_date');
				});
		})->count();
		$attention['expiring_3_days'] = CandidateMaster::whereBetween('contract_end_date', [now(), now()->addDays(3)])->count();
		$attention['expiring_5_days'] = CandidateMaster::whereBetween('contract_end_date', [now(), now()->addDays(5)])->count();
		$attention['expiring_7_days'] = CandidateMaster::whereBetween('contract_end_date', [now(), now()->addDays(7)])->count();
		$attention['in_process'] = $inProcessCount;
		$attention['active'] = $activeCount;

		// Tab counts for requisition tabs
		$tabCounts = [
			'submission' => ManpowerRequisition::where('status', 'Pending HR Verification')->count(),
			'correction_required' => ManpowerRequisition::where('status', 'Correction Required')->count(),
			'hr_verified' => ManpowerRequisition::where('status', 'Hr Verified')->count(),
			'approval' => ManpowerRequisition::where('status', 'Pending Approval')->count(),
			'approved' => ManpowerRequisition::where('status', 'Approved')->count(),
			'unsigned' => ManpowerRequisition::whereHas('candidate', function ($q) {
				$q->where('candidate_status', 'Unsigned Agreement Created');
			})->count(),
			'dispatch_pending' => ManpowerRequisition::whereHas('candidate', function ($q) {
				$q->where('candidate_status', 'Signed Agreement Uploaded')
					->whereHas('signedAgreements', function ($q2) {
						$q2->whereDoesntHave('courierDetails');
					});
			})->count(),
			'courier_pending' => ManpowerRequisition::whereHas('candidate', function ($candidateQuery) {
				$candidateQuery->where('candidate_status', '!=', 'Cancelled')
					->whereHas('signedAgreements.courierDetails', function ($courierQuery) {
						$courierQuery->whereNotNull('dispatch_date')->whereNull('received_date');
					});
			})->count(),
			'file_pending' => ManpowerRequisition::whereHas('candidate', function ($q) {
				$q->where('candidate_status', 'Signed Agreement Uploaded')
					->whereNull('file_created_date')
					->whereHas('signedAgreements.courierDetails', function ($q2) {
						$q2->whereNotNull('received_date');
					});
			})->count(),
			'active' => $activeCount,
			'inactive' => CandidateMaster::where('candidate_status', 'Inactive')->count(),
			'rejected' => ManpowerRequisition::where('status', 'Rejected')->count()
		];

		// Joinings chart data
		$fyStart = now()->month >= 4 ? now()->year . '-04-01' : (now()->year - 1) . '-04-01';
		$fyEnd = now()->month >= 4 ? (now()->year + 1) . '-03-31' : now()->year . '-03-31';

		$joiningsChart = DB::table('candidate_master')
			->selectRaw('MONTH(contract_start_date) as month, COUNT(*) as total')
			->whereNotNull('contract_start_date')
			->whereBetween('contract_start_date', [$fyStart, $fyEnd])
			->groupBy('month')
			->pluck('total', 'month');

		// Expiry data
		$today = Carbon::today();
		$expiry = [
			'lt_30_days' => CandidateMaster::whereNotNull('contract_end_date')
				->whereBetween('contract_end_date', [$today, $today->copy()->addDays(30)])
				->orderBy('contract_end_date')
				->paginate(10, ['*'], 'lt30_page'),
			'days_30_60' => CandidateMaster::whereNotNull('contract_end_date')
				->whereBetween('contract_end_date', [$today->copy()->addDays(31), $today->copy()->addDays(60)])
				->orderBy('contract_end_date')
				->paginate(10, ['*'], 'd30_page'),
			'days_60_90' => CandidateMaster::whereNotNull('contract_end_date')
				->whereBetween('contract_end_date', [$today->copy()->addDays(61), $today->copy()->addDays(90)])
				->orderBy('contract_end_date')
				->paginate(10, ['*'], 'd60_page'),
		];

		// Top Submitters & Departments
		$topSubmitters = ManpowerRequisition::select('submitted_by_name', DB::raw('count(*) as count'))
			->where('submission_date', '>=', now()->subDays(30))
			->groupBy('submitted_by_name')
			->orderBy('count', 'desc')
			->limit(6)
			->get();

		$topDepartments = CandidateMaster::select('department_id', DB::raw('count(*) as count'))
			->where('candidate_status', 'Active')
			->with('department:id,department_name')
			->groupBy('department_id')
			->orderBy('count', 'desc')
			->limit(6)
			->get();

		return view('dashboard.management', compact(
			'years',
			'departments',
			'businessUnits',
			'zones',
			'regions',
			'territories',
			'verticals',
			'activeCount',
			'inProcessCount',
			'contractualCount',
			'tfaCount',
			'cbCount',
			'recent_requisitions',
			'expiry',
			'tabCounts',
			'attention',
			'joiningsChart',
			'topSubmitters',
			'topDepartments',
			'reqTab',
			'expTab'
		));
	}

	private function getBaseDateFromStatus($requisition)
	{
		switch ($requisition->status) {
			case 'Pending HR Verification':
				return $requisition->submission_date;
			case 'Correction Required':
				return $requisition->correction_requested_date ?? $requisition->updated_at;
			case 'Hr Verified':
				return $requisition->hr_verification_date;
			case 'Pending Approval':
				return $requisition->hr_verification_date;
			case 'Approved':
				return $requisition->approval_date;
			default:
				return null;
		}
	}

	public function getDashboardData(Request $request)
	{
		try {
			$request->validate([
				'year' => 'required|integer',
				'month' => 'nullable|integer|between:1,12',
				'department' => 'nullable|string',
				'bu' => 'nullable|string',
				'zone' => 'nullable|string',
				'region' => 'nullable|string',
				'territory' => 'nullable|string',
				'vertical' => 'nullable|string',
				'requisition_type' => 'nullable|string|in:Contractual,TFA,CB,All',
			]);

			$year = $request->year;
			$month = $request->month;
			$filters = $request->only([
				'department',
				'bu',
				'zone',
				'region',
				'territory',
				'vertical',
				'requisition_type'
			]);

			$applyFilters = function ($query) use ($filters) {
				foreach ($filters as $key => $value) {
					if ($value && $value !== 'All') {
						switch ($key) {
							case 'department':
								$query->where('department_id', $value);
								break;
							case 'bu':
								$query->where('business_unit', $value);
								break;
							case 'zone':
								$query->where('zone', $value);
								break;
							case 'region':
								$query->where('region', $value);
								break;
							case 'territory':
								$query->where('territory', $value);
								break;
							case 'vertical':
								$query->where('vertical_id', $value);
								break;
							case 'requisition_type':
								$query->where('requisition_type', $value);
								break;
						}
					}
				}
				return $query;
			};

			// ========== CURRENT HEADCOUNT (NOT FILTERED BY FY) ==========
			// Active count - ALL currently active candidates
			$activeQuery = CandidateMaster::where('candidate_status', 'Active');
			$activeQuery = $applyFilters(clone $activeQuery);
			$activeCount = $activeQuery->count();

			// In Process count - ALL candidates in agreement workflow
			$inProcessQuery = CandidateMaster::whereIn('candidate_status', [
				'Agreement Pending',
				'Unsigned Agreement Created',
				'Signed Agreement Uploaded'
			]);
			$inProcessQuery = $applyFilters(clone $inProcessQuery);
			$inProcessCount = $inProcessQuery->count();

			// Party type breakdown - ALL active candidates
			$contractualQuery = CandidateMaster::where('candidate_status', 'Active')->where('requisition_type', 'Contractual');
			$tfaQuery = CandidateMaster::where('candidate_status', 'Active')->where('requisition_type', 'TFA');
			$cbQuery = CandidateMaster::where('candidate_status', 'Active')->where('requisition_type', 'CB');

			$contractualQuery = $applyFilters(clone $contractualQuery);
			$tfaQuery = $applyFilters(clone $tfaQuery);
			$cbQuery = $applyFilters(clone $cbQuery);

			$contractualCount = $contractualQuery->count();
			$tfaCount = $tfaQuery->count();
			$cbCount = $cbQuery->count();

			// ========== FINANCIAL YEAR FILTER FOR CHARTS ==========
			$financialYearStart = $year . '-04-01';
			$financialYearEnd = ($year + 1) . '-03-31';

			if ($month && $month !== '') {
				$actualYear = ($month >= 4) ? $year : $year + 1;
				$dateStart = $actualYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
				$dateEnd = Carbon::parse($dateStart)->endOfMonth()->toDateString();
			} else {
				$dateStart = $financialYearStart;
				$dateEnd = $financialYearEnd;
			}

			// Limit to today for future dates
			$today = Carbon::today()->toDateString();
			if ($dateEnd > $today) {
				$dateEnd = $today;
			}

			// Department Distribution - ALL active (not filtered by FY)
			$departmentQuery = CandidateMaster::join('core_department', 'candidate_master.department_id', '=', 'core_department.id')
				->select('core_department.department_name', DB::raw('COUNT(*) as count'))
				->where('candidate_status', 'Active');
			$departmentQuery = $applyFilters($departmentQuery);
			$departmentDistribution = $departmentQuery->groupBy('core_department.department_name')
				->orderBy('count', 'desc')
				->limit(10)
				->get();

			// Type Distribution - ALL active
			$typeQuery = CandidateMaster::select('requisition_type', DB::raw('COUNT(*) as count'))
				->where('candidate_status', 'Active');
			$typeQuery = $applyFilters($typeQuery);
			$typeDistribution = $typeQuery->groupBy('requisition_type')->get();

			// Status Distribution - ALL
			$statusQuery = CandidateMaster::select('candidate_status', DB::raw('COUNT(*) as count'))
				->whereIn('candidate_status', [
					'Active',
					'Agreement Pending',
					'Unsigned Agreement Created',
					'Signed Agreement Uploaded'
				]);
			$statusQuery = $applyFilters($statusQuery);
			$statusDistribution = $statusQuery->groupBy('candidate_status')->get();

			// Geographic Distribution - ALL active
		$geoQuery = CandidateMaster::join('core_zone', 'candidate_master.zone', '=', 'core_zone.id')
    ->join('core_region', 'candidate_master.region', '=', 'core_region.id')
    ->select(
        DB::raw("CONCAT(core_zone.zone_name, ' - ', core_region.region_name) as location"),
        DB::raw('CAST(COUNT(*) AS UNSIGNED) as count')
    )
    ->where('candidate_master.candidate_status', 'Active');

$geoQuery = $applyFilters($geoQuery);

$geographicDistribution = $geoQuery
    ->groupBy('core_zone.zone_name', 'core_region.region_name')
    ->orderBy('count', 'desc')
    ->limit(10)
    ->get();
				// Debug query - check what location data exists
			$debugLocations = CandidateMaster::select('zone', 'region', DB::raw('COUNT(*) as count'))
				->where('candidate_status', 'Active')
				->groupBy('zone', 'region')
				->get();

			\Log::info('Location data:', $debugLocations->toArray());

			// Monthly Trend (new joinings by month in FY)
			$monthlyTrend = $this->getMonthlyTrendData($year, $applyFilters);

			// Salary Expenditure
			$salaryExpenditure = $this->getSalaryExpenditure($year, $month, $filters);

			$monthName = $month ? Carbon::createFromDate($year, $month, 1)->format('F') : 'Whole Year';

			return response()->json([
				'success' => true,
				'data' => [
					'overview' => [
						'total_active' => $activeCount,      // ALL active candidates
						'in_process' => $inProcessCount,     // ALL in-process candidates
						'contractual' => $contractualCount,  // ALL active contractual
						'tfa' => $tfaCount,                  // ALL active TFA
						'cb' => $cbCount,                    // ALL active CB
					],
					'monthly_trend' => $monthlyTrend ?? [],
					'department_distribution' => $departmentDistribution ?? [],
					'type_distribution' => $typeDistribution ?? [],
					'status_distribution' => $statusDistribution ?? [],
					'geographic_distribution' => $geographicDistribution ?? [],
					'salary_expenditure' => $salaryExpenditure ?? [],
					'filters' => $filters,
					'period' => [
						'year' => $year,
						'month' => $monthName
					]
				]
			]);
		} catch (\Exception $e) {
			\Log::error('Dashboard data error: ' . $e->getMessage());

			return response()->json([
				'success' => false,
				'message' => 'Error loading dashboard data',
				'error' => config('app.debug') ? $e->getMessage() : null
			], 500);
		}
	}
	private function getActiveCountByFinancialYear($year, $month, $applyFilters)
	{
		// Financial Year: April to March
		$financialYearStart = $year . '-04-01';
		$financialYearEnd = ($year + 1) . '-03-31';

		$activeQuery = CandidateMaster::where('candidate_status', 'Active');

		if ($month && $month !== '') {
			// Calculate the actual year for the month
			$actualYear = ($month >= 4) ? $year : $year + 1;
			$dateStart = $actualYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
			$dateEnd = Carbon::parse($dateStart)->endOfMonth()->toDateString();
			$activeQuery->whereBetween('contract_start_date', [$dateStart, $dateEnd]);
		} else {
			// Full financial year - only include dates up to today
			$today = Carbon::today()->toDateString();
			$activeQuery->whereBetween('contract_start_date', [$financialYearStart, min($financialYearEnd, $today)]);
		}

		$activeQuery = $applyFilters($activeQuery);
		return $activeQuery->count();
	}

	private function getPartiesStatistics($year, $month, $applyFilters)
	{
		// Financial Year: April to March
		// $year is the starting year of FY (e.g., 2025 for FY 2025-26)
		$financialYearStart = $year . '-04-01';
		$financialYearEnd = ($year + 1) . '-03-31';

		$activeQuery = CandidateMaster::where('candidate_status', 'Active');

		if ($month && $month !== '') {
			// Calculate the actual year for the month (months Jan-Mar belong to next calendar year)
			$actualYear = ($month >= 4) ? $year : $year + 1;
			$dateStart = $actualYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
			$dateEnd = Carbon::parse($dateStart)->endOfMonth()->toDateString();

			$activeQuery->whereBetween('contract_start_date', [$dateStart, $dateEnd]);
		} else {
			// Full financial year - but only include dates up to today (don't show future)
			$today = Carbon::today()->toDateString();
			$activeQuery->whereBetween('contract_start_date', [$financialYearStart, min($financialYearEnd, $today)]);
		}

		$activeQuery = $applyFilters($activeQuery);
		$totalActive = $activeQuery->count();

		// Get In Process count (not filtered by financial year - these are current operations)
		$inProcessQuery = CandidateMaster::whereIn('candidate_status', [
			'Agreement Pending',
			'Unsigned Agreement Created',
			'Signed Agreement Uploaded'
		]);
		$inProcessQuery = $applyFilters(clone $inProcessQuery);
		$inProcessCount = $inProcessQuery->count();

		// Get party type breakdown (active only, filtered by financial year)
		$contractualQuery = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'Contractual');
		$tfaQuery = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'TFA');
		$cbQuery = CandidateMaster::where('candidate_status', 'Active')
			->where('requisition_type', 'CB');

		if ($month && $month !== '') {
			$actualYear = ($month >= 4) ? $year : $year + 1;
			$dateStart = $actualYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
			$dateEnd = Carbon::parse($dateStart)->endOfMonth()->toDateString();
			$contractualQuery->whereBetween('contract_start_date', [$dateStart, $dateEnd]);
			$tfaQuery->whereBetween('contract_start_date', [$dateStart, $dateEnd]);
			$cbQuery->whereBetween('contract_start_date', [$dateStart, $dateEnd]);
		} else {
			$today = Carbon::today()->toDateString();
			$contractualQuery->whereBetween('contract_start_date', [$financialYearStart, min($financialYearEnd, $today)]);
			$tfaQuery->whereBetween('contract_start_date', [$financialYearStart, min($financialYearEnd, $today)]);
			$cbQuery->whereBetween('contract_start_date', [$financialYearStart, min($financialYearEnd, $today)]);
		}

		$contractualQuery = $applyFilters($contractualQuery);
		$tfaQuery = $applyFilters($tfaQuery);
		$cbQuery = $applyFilters($cbQuery);

		return [
			'total_active' => $totalActive,
			'in_process' => $inProcessCount,
			'contractual' => $contractualQuery->count(),
			'tfa' => $tfaQuery->count(),
			'cb' => $cbQuery->count(),
		];
	}

	private function getMonthlyTrendData($year, $applyFilters)
	{
		$data = [];

		// $year is the starting year of Financial Year (e.g., 2025 for FY 2025-26)
		// FY 2025-26: April 2025 to March 2026

		for ($i = 1; $i <= 12; $i++) {
			// Map month numbers to Financial Year months (1=April, 2=May, ..., 12=March)
			$monthNames = [
				1 => 'Apr',
				2 => 'May',
				3 => 'Jun',
				4 => 'Jul',
				5 => 'Aug',
				6 => 'Sep',
				7 => 'Oct',
				8 => 'Nov',
				9 => 'Dec',
				10 => 'Jan',
				11 => 'Feb',
				12 => 'Mar'
			];

			$monthName = $monthNames[$i];

			// Calculate actual calendar year and month for this FY month
			// Months 1-9 (Apr-Dec) use the starting year
			// Months 10-12 (Jan-Mar) use the next calendar year
			if ($i <= 9) {
				// Apr to Dec - same as financial year start year
				$calendarYear = $year;
				$calendarMonth = $i + 3; // Apr=4, May=5, ..., Dec=12
			} else {
				// Jan to Mar - next calendar year
				$calendarYear = $year + 1;
				$calendarMonth = $i - 9; // Jan=1, Feb=2, Mar=3
			}

			// Created this month (manpower requisitions created)
			$createdQuery = ManpowerRequisition::whereYear('created_at', $calendarYear)
				->whereMonth('created_at', $calendarMonth);
			$createdQuery = $applyFilters($createdQuery);

			// Activated this month (contracts started)
			$activatedQuery = CandidateMaster::whereYear('contract_start_date', $calendarYear)
				->whereMonth('contract_start_date', $calendarMonth)
				->where('candidate_status', 'Active');
			$activatedQuery = $applyFilters($activatedQuery);

			// Deactivated this month (contracts ended)
			$deactivatedQuery = CandidateMaster::whereYear('contract_end_date', $calendarYear)
				->whereMonth('contract_end_date', $calendarMonth)
				->where('candidate_status', 'Inactive');
			$deactivatedQuery = $applyFilters($deactivatedQuery);

			$data[] = [
				'month' => $monthName,
				'created' => $createdQuery->count(),
				'activated' => $activatedQuery->count(),
				'deactivated' => $deactivatedQuery->count(),
			];
		}

		return $data;
	}

	private function getDepartmentDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::join('core_department', 'candidate_master.department_id', '=', 'core_department.id')
			->select('core_department.department_name', DB::raw('COUNT(*) as count'))
			->where('candidate_status', 'Active');

		if ($month) {
			$query->where(function ($q) use ($year, $month) {
				$q->whereYear('contract_start_date', '<=', $year)
					->whereMonth('contract_start_date', '<=', $month)
					->where(function ($q2) use ($year, $month) {
						$q2->whereNull('contract_end_date')
							->orWhere(function ($q3) use ($year, $month) {
								$q3->whereYear('contract_end_date', '>=', $year)
									->whereMonth('contract_end_date', '>=', $month);
							});
					});
			});
		} else {
			$query->whereYear('contract_start_date', '<=', $year)
				->where(function ($q) use ($year) {
					$q->whereNull('contract_end_date')->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		$tempFilters = array_diff_key($filters, ['department' => '']);
		foreach ($tempFilters as $key => $value) {
			if ($value && $value !== 'All') {
				switch ($key) {
					case 'bu':
						$query->where('business_unit', $value);
						break;
					case 'zone':
						$query->where('zone', $value);
						break;
					case 'region':
						$query->where('region', $value);
						break;
					case 'territory':
						$query->where('territory', $value);
						break;
					case 'vertical':
						$query->where('vertical_id', $value);
						break;
					case 'requisition_type':
						$query->where('requisition_type', $value);
						break;
				}
			}
		}

		$query->groupBy('core_department.department_name')->orderBy('count', 'desc')->limit(10);
		return $query->get();
	}

	private function getTypeDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::select('requisition_type', DB::raw('COUNT(*) as count'))
			->where('candidate_status', 'Active');

		if ($month) {
			$query->where(function ($q) use ($year, $month) {
				$q->whereYear('contract_start_date', '<=', $year)
					->whereMonth('contract_start_date', '<=', $month)
					->where(function ($q2) use ($year, $month) {
						$q2->whereNull('contract_end_date')
							->orWhere(function ($q3) use ($year, $month) {
								$q3->whereYear('contract_end_date', '>=', $year)
									->whereMonth('contract_end_date', '>=', $month);
							});
					});
			});
		} else {
			$query->whereYear('contract_start_date', '<=', $year)
				->where(function ($q) use ($year) {
					$q->whereNull('contract_end_date')->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		$this->applyFiltersToQuery($query, $filters);
		$query->groupBy('requisition_type');
		return $query->get();
	}

	private function getStatusDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::query();

		if ($month) {
			$query->where(function ($q) use ($year, $month) {
				$q->whereYear('contract_start_date', $year)->whereMonth('contract_start_date', $month);
			});
		} else {
			$query->whereYear('contract_start_date', $year);
		}

		$this->applyFiltersToQuery($query, $filters);
		$query->select('candidate_status', DB::raw('COUNT(*) as count'))->groupBy('candidate_status');
		return $query->get();
	}

	private function getGeographicDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::select(DB::raw("CONCAT_WS(' - ', zone, region) as location"), DB::raw('COUNT(*) as count'))
			->where('candidate_status', 'Active');

		if ($month) {
			$query->where(function ($q) use ($year, $month) {
				$q->whereYear('contract_start_date', '<=', $year)
					->whereMonth('contract_start_date', '<=', $month)
					->where(function ($q2) use ($year, $month) {
						$q2->whereNull('contract_end_date')
							->orWhere(function ($q3) use ($year, $month) {
								$q3->whereYear('contract_end_date', '>=', $year)
									->whereMonth('contract_end_date', '>=', $month);
							});
					});
			});
		} else {
			$query->whereYear('contract_start_date', '<=', $year)
				->where(function ($q) use ($year) {
					$q->whereNull('contract_end_date')->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		$tempFilters = array_diff_key($filters, ['zone' => '', 'region' => '', 'territory' => '']);
		$this->applyFiltersToQuery($query, $tempFilters);
		$query->whereNotNull('zone')->whereNotNull('region')
			->groupBy('zone', 'region')->orderBy('count', 'desc')->limit(15);
		return $query->get();
	}

	private function getSalaryExpenditure($year, $month, $filters)
	{
		$query = SalaryProcessing::join('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
			->where('salary_processings.year', $year)->where('candidate_master.candidate_status', 'Active');

		if ($month) {
			$query->where('salary_processings.month', $month);
		}

		$this->applyFiltersToQuery($query, $filters, 'candidate_master');

		if ($month) {
			$data = $query->select(DB::raw('SUM(net_pay) as total_salary'))->first();
			return [
				'period' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
				'total_salary' => $data->total_salary ?? 0,
				'avg_salary' => $query->avg('net_pay') ?? 0,
				'employee_count' => $query->count()
			];
		} else {
			$monthlyData = [];
			for ($i = 1; $i <= 12; $i++) {
				$monthQuery = clone $query;
				$monthData = $monthQuery->where('salary_processings.month', $i)
					->select(DB::raw('SUM(net_pay) as total_salary'))->first();
				$monthlyData[] = [
					'month' => Carbon::createFromDate($year, $i, 1)->format('M'),
					'total_salary' => $monthData->total_salary ?? 0,
					'employee_count' => $monthQuery->count()
				];
			}
			$totalSalary = $query->sum('net_pay');
			$avgMonthly = $totalSalary ? $totalSalary / 12 : 0;
			return [
				'period' => "Year {$year}",
				'total_salary' => $totalSalary,
				'avg_monthly_salary' => $avgMonthly,
				'monthly_data' => $monthlyData,
				'employee_count' => $query->distinct('candidate_id')->count('candidate_id')
			];
		}
	}

	private function applyFiltersToQuery($query, $filters, $tableAlias = null)
	{
		$prefix = $tableAlias ? $tableAlias . '.' : '';

		foreach ($filters as $key => $value) {
			if ($value && $value !== 'All') {
				switch ($key) {
					case 'department':
						$query->where($prefix . 'department_id', $value);
						break;
					case 'bu':
						$query->where($prefix . 'business_unit', $value);
						break;
					case 'zone':
						$query->where($prefix . 'zone', $value);
						break;
					case 'region':
						$query->where($prefix . 'region', $value);
						break;
					case 'territory':
						$query->where($prefix . 'territory', $value);
						break;
					case 'vertical':
						$query->where($prefix . 'vertical_id', $value);
						break;
					case 'requisition_type':
						$query->where($prefix . 'requisition_type', $value);
						break;
				}
			}
		}
		return $query;
	}
}
