<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HierarchyAccessService
{
	public function getAssociatedBusinessUnitList($employeeId)
	{
		$user = Auth::user();

		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_business_unit')
				->where('is_active', '1')
				->pluck('business_unit_name', 'id')
				->prepend('All BU', 'All')
				->toArray();
		}

		$buId = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->where('zone', 0)
			->value('bu');

		if ($buId > 0) {
			return DB::table('core_business_unit')
				->where('id', $buId)
				->pluck('business_unit_name', 'id')
				->prepend('Select BU', '')
				->toArray();
		}

		return [];
	}

	public function getAssociatedZoneList($employeeId)
	{
		$user = Auth::user();

		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_zone')
				->where('is_active', '1')
				->pluck('zone_name', 'id')
				->prepend('All Zone', 'All')
				->toArray();
		}

		$zoneId = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->where('region', 0)
			->value('zone');

		if ($zoneId > 0) {
			return DB::table('core_zone')
				->where('id', $zoneId)
				->pluck('zone_name', 'id')
				->prepend('Select Zone', '')
				->toArray();
		}

		return [];
	}

	public function getAssociatedRegionList($employeeId)
	{
		$user = Auth::user();

		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_region')
				->where('is_active', '1')
				->pluck('region_name', 'id')
				->prepend('All Region', 'All')
				->toArray();
		}

		$regionId = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->where('territory', 0)
			->value('region');

		if ($regionId > 0) {
			return DB::table('core_region')
				->where('id', $regionId)
				->pluck('region_name', 'id')
				->prepend('Select Region', '')
				->toArray();
		}

		return [];
	}

	public function getAssociatedTerritoryList($employeeId)
	{
		$user = Auth::user();

		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_territory')
				->where('is_active', '1')
				->pluck('territory_name', 'id')
				->prepend('All Territory', 'All')
				->toArray();
		}

		$territoryId = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->value('territory');

		if ($territoryId > 0) {
			return DB::table('core_territory')
				->where('id', $territoryId)
				->pluck('territory_name', 'id')
				->toArray();
		}

		return [];
	}

	public function getAccessLevel($employee)
	{
		if (Auth::user()->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return 'all';
		}

		// 🔒 If employee record not found
		if (!$employee) {
			return 'none';
		}

		if ($employee->territory > 0) return 'territory';
		if ($employee->region > 0) return 'region';
		if ($employee->zone > 0) return 'zone';
		if ($employee->bu > 0) return 'bu';

		return 'none';
	}

	public function getAssociatedDepartmentList($employeeId)
	{
		$user = Auth::user();

		// Admin → show all departments
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_department')
				->where('is_active', 1)
				->orderBy('department_name')
				->pluck('department_name', 'id')
				->prepend('All Departments', 'All')
				->toArray();
		}

		// Get employee department
		$departmentId = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->value('department');

		if ($departmentId > 0) {
			return DB::table('core_department')
				->where('id', $departmentId)
				->pluck('department_name', 'id')
				->toArray();
		}

		return [];
	}

	public function getReportingEmployeeIds($empId)
	{
		$allIds = [$empId];

		$this->collectSubordinates($empId, $allIds);

		return $allIds;
	}

	private function collectSubordinates($managerEmpId, &$allIds)
	{
		$subordinates = DB::table('users')
			->where('reporting_id', $managerEmpId)
			->pluck('emp_id')
			->toArray();

		foreach ($subordinates as $subEmpId) {
			if (!in_array($subEmpId, $allIds)) {
				$allIds[] = $subEmpId;
				$this->collectSubordinates($subEmpId, $allIds);
			}
		}
	}
}
