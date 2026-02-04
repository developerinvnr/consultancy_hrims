<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CandidateMaster;
use App\Models\ManpowerRequisition;
use App\Models\SalaryProcessing;

class DashboardController extends Controller
{
	public function managementDashboard()
	{
		// Get filter options
		$years = range(date('Y'), 2020);

		$departments = \App\Models\CoreDepartment::orderBy('department_name')->get();
		$businessUnits = \App\Models\CoreBusinessUnit::orderBy('business_unit_name')->get();
		$zones = \App\Models\CoreZone::orderBy('zone_name')->get();
		$regions = \App\Models\CoreRegion::orderBy('region_name')->get();
		$territories = \App\Models\CoreTerritory::orderBy('territory_name')->get();
		$verticals = \App\Models\CoreVertical::orderBy('vertical_name')->get();

		return view('dashboard.management', compact(
			'years',
			'departments',
			'businessUnits',
			'zones',
			'regions',
			'territories',
			'verticals'
		));
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

		// Helper function to apply filters to query
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

		// 1. Total Parties Statistics
		$partiesData = $this->getPartiesStatistics($year, $month, $applyFilters);

		// 2. Monthly Trend Data
		$monthlyTrend = $this->getMonthlyTrendData($year, $applyFilters);

		// 3. Department-wise Distribution
		$departmentDistribution = $this->getDepartmentDistribution($year, $month, $filters);

		// 4. Type-wise Distribution
		$typeDistribution = $this->getTypeDistribution($year, $month, $filters);

		// 5. Status-wise Distribution
		$statusDistribution = $this->getStatusDistribution($year, $month, $filters);

		// 6. Zone/Region-wise Distribution
		$geographicDistribution = $this->getGeographicDistribution($year, $month, $filters);

		// 7. Monthly Salary Expenditure
		$salaryExpenditure = $this->getSalaryExpenditure($year, $month, $filters);

		// FIX: Handle month formatting properly
		$monthName = $month ? Carbon::createFromDate($year, $month, 1)->format('F') : 'Whole Year';

		        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $partiesData,
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

	private function getPartiesStatistics($year, $month, $applyFilters)
	{
		// Parties created in the period
		$query = ManpowerRequisition::query();

		if ($month) {
			$query->whereYear('created_at', $year)
				->whereMonth('created_at', $month);
		} else {
			$query->whereYear('created_at', $year);
		}

		$query = $applyFilters($query);

		$totalCreated = $query->count();

		// Active parties
		$activeQuery = CandidateMaster::where('candidate_status', 'Active');
		if ($month) {
			$activeQuery->where(function ($q) use ($year, $month) {
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
			$activeQuery->whereYear('contract_start_date', '<=', $year)
				->where(function ($q) use ($year) {
					$q->whereNull('contract_end_date')
						->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		$activeQuery = $applyFilters($activeQuery);
		$totalActive = $activeQuery->count();

		// Deactivated parties
		$deactivatedQuery = CandidateMaster::where('candidate_status', 'Deactivated');
		if ($month) {
			$deactivatedQuery->whereYear('contract_end_date', $year)
				->whereMonth('contract_end_date', $month);
		} else {
			$deactivatedQuery->whereYear('contract_end_date', $year);
		}

		$deactivatedQuery = $applyFilters($deactivatedQuery);
		$totalDeactivated = $deactivatedQuery->count();

		// Remaining (Created - (Active + Deactivated))
		$totalRemaining = $totalCreated - ($totalActive + $totalDeactivated);

		return [
			'total_created' => $totalCreated,
			'total_active' => $totalActive,
			'total_deactivated' => $totalDeactivated,
			'total_remaining' => max(0, $totalRemaining),
		];
	}

	private function getMonthlyTrendData($year, $applyFilters)
{
    $data = [];
    
    for ($i = 1; $i <= 12; $i++) {
        // FIX: Use Carbon::createFromDate instead of Carbon::create()->month()
        $monthName = Carbon::createFromDate($year, $i, 1)->format('M');
        
        // Created this month
        $createdQuery = ManpowerRequisition::whereYear('created_at', $year)
                                          ->whereMonth('created_at', $i);
        $createdQuery = $applyFilters($createdQuery);
        
        // Activated this month
        $activatedQuery = CandidateMaster::whereYear('contract_start_date', $year)
                                        ->whereMonth('contract_start_date', $i)
                                        ->where('candidate_status', 'Active');
        $activatedQuery = $applyFilters($activatedQuery);
        
        // Deactivated this month
        $deactivatedQuery = CandidateMaster::whereYear('contract_end_date', $year)
                                          ->whereMonth('contract_end_date', $i)
                                          ->where('candidate_status', 'Deactivated');
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
					$q->whereNull('contract_end_date')
						->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		// Apply other filters except department itself
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

		$query->groupBy('core_department.department_name')
			->orderBy('count', 'desc')
			->limit(10);

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
					$q->whereNull('contract_end_date')
						->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		// Apply filters
		$this->applyFiltersToQuery($query, $filters);

		$query->groupBy('requisition_type');

		return $query->get();
	}

	private function getStatusDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::query();

		if ($month) {
			$query->where(function ($q) use ($year, $month) {
				$q->whereYear('contract_start_date', $year)
					->whereMonth('contract_start_date', $month);
			});
		} else {
			$query->whereYear('contract_start_date', $year);
		}

		// Apply filters
		$this->applyFiltersToQuery($query, $filters);

		$query->select('candidate_status', DB::raw('COUNT(*) as count'))
			->groupBy('candidate_status');

		return $query->get();
	}

	private function getGeographicDistribution($year, $month, $filters)
	{
		$query = CandidateMaster::select(
			DB::raw("CONCAT_WS(' - ', zone, region) as location"),
			DB::raw('COUNT(*) as count')
		)
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
					$q->whereNull('contract_end_date')
						->orWhereYear('contract_end_date', '>=', $year);
				});
		}

		// Apply filters
		$tempFilters = array_diff_key($filters, ['zone' => '', 'region' => '', 'territory' => '']);
		$this->applyFiltersToQuery($query, $tempFilters);

		$query->whereNotNull('zone')
			->whereNotNull('region')
			->groupBy('zone', 'region')
			->orderBy('count', 'desc')
			->limit(15);

		return $query->get();
	}

	private function getSalaryExpenditure($year, $month, $filters)
{
    $query = SalaryProcessing::join('candidate_master', 'salary_processings.candidate_id', '=', 'candidate_master.id')
                            ->where('salary_processings.year', $year)
                            ->where('candidate_master.candidate_status', 'Active');
    
    if ($month) {
        $query->where('salary_processings.month', $month);
    }
    
    // Apply filters
    $this->applyFiltersToQuery($query, $filters, 'candidate_master');
    
    if ($month) {
        $data = $query->select(
                    DB::raw("'Month {$month}' as period"),
                    DB::raw('SUM(net_pay) as total_salary')
                )->first();
        
        // FIX: Use Carbon::createFromDate
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
                                   ->select(DB::raw('SUM(net_pay) as total_salary'))
                                   ->first();
            
            // FIX: Use Carbon::createFromDate
            $monthlyData[] = [
                'month' => Carbon::createFromDate($year, $i, 1)->format('M'),
                'total_salary' => $monthData->total_salary ?? 0,
                'employee_count' => $monthQuery->count()
            ];
        }
        
        $totalSalary = $query->sum('net_pay');
        $avgMonthly = $totalSalary / 12;
        
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
