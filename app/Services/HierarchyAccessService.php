<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HierarchyAccessService
{
	/**
	 * Get the department of the logged-in user
	 */
	private function getUserDepartment($employeeId)
	{
		return DB::table('core_employee')
			->where('employee_id', $employeeId)
			->value('department');
	}

	/**
	 * Check if user is in Sales department
	 */
	public function isSalesDepartment($employeeId)
	{
		$departmentId = $this->getUserDepartment($employeeId);
		return $departmentId == 15; // Sales department ID
	}

	/**
	 * Check if user should see location filters (BU, Zone, Region, Territory)
	 * Only show for:
	 * 1. Admin, hr_admin, management roles (full access)
	 * 2. Users in Sales department (based on their access level)
	 */
	public function shouldShowLocationFilters($employeeId)
	{
		$user = Auth::user();

		// Admin, hr_admin, management roles → show all location filters
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return true;
		}

		// Sales department users → show location filters
		if ($this->isSalesDepartment($employeeId)) {
			return true;
		}

		// Other departments → do NOT show location filters
		return false;
	}

	public function getAssociatedBusinessUnitList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_business_unit')
				->where('is_active', '1')
				->pluck('business_unit_name', 'id')
				->prepend('All Business Units', 'All')
				->toArray();
		}

		// For Sales department users, return their associated BU based on access level
		if ($this->isSalesDepartment($employeeId)) {
			$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();

			// If user has specific BU access
			if ($employee && $employee->bu > 0) {
				return DB::table('core_business_unit')
					->where('id', $employee->bu)
					->pluck('business_unit_name', 'id')
					->prepend('All Business Units', 'All')
					->toArray();
			}

			// Return all BUs for Sales users with higher access
			return DB::table('core_business_unit')
				->where('is_active', '1')
				->pluck('business_unit_name', 'id')
				->prepend('All Business Units', 'All')
				->toArray();
		}

		// For other departments, return only their specific BU if assigned
		$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();
		if ($employee && $employee->bu > 0) {
			return DB::table('core_business_unit')
				->where('id', $employee->bu)
				->pluck('business_unit_name', 'id')
				->prepend('All Business Units', 'All')
				->toArray();
		}

		return [];
	}

	public function getAssociatedZoneList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_zone')
				->where('is_active', '1')
				->pluck('zone_name', 'id')
				->prepend('All Zones', 'All')
				->toArray();
		}

		// For Sales department users, return their associated zones based on access level
		if ($this->isSalesDepartment($employeeId)) {
			$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();

			// If user has specific Zone access
			if ($employee && $employee->zone > 0) {
				return DB::table('core_zone')
					->where('id', $employee->zone)
					->pluck('zone_name', 'id')
					->prepend('All Zones', 'All')
					->toArray();
			}

			// Return all zones for Sales users with higher access
			return DB::table('core_zone')
				->where('is_active', '1')
				->pluck('zone_name', 'id')
				->prepend('All Zones', 'All')
				->toArray();
		}

		// For other departments, return only their specific zone if assigned
		$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();
		if ($employee && $employee->zone > 0) {
			return DB::table('core_zone')
				->where('id', $employee->zone)
				->pluck('zone_name', 'id')
				->prepend('All Zones', 'All')
				->toArray();
		}

		return [];
	}

	public function getAssociatedRegionList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_region')
				->where('is_active', '1')
				->pluck('region_name', 'id')
				->prepend('All Regions', 'All')
				->toArray();
		}

		// For Sales department users, return their associated regions based on access level
		if ($this->isSalesDepartment($employeeId)) {
			$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();

			// If user has specific Region access
			if ($employee && $employee->region > 0) {
				return DB::table('core_region')
					->where('id', $employee->region)
					->pluck('region_name', 'id')
					->prepend('All Regions', 'All')
					->toArray();
			}

			// Return all regions for Sales users with higher access
			return DB::table('core_region')
				->where('is_active', '1')
				->pluck('region_name', 'id')
				->prepend('All Regions', 'All')
				->toArray();
		}

		// For other departments, return only their specific region if assigned
		$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();
		if ($employee && $employee->region > 0) {
			return DB::table('core_region')
				->where('id', $employee->region)
				->pluck('region_name', 'id')
				->prepend('All Regions', 'All')
				->toArray();
		}

		return [];
	}

	public function getAssociatedTerritoryList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_territory')
				->where('is_active', '1')
				->pluck('territory_name', 'id')
				->prepend('All Territories', 'All')
				->toArray();
		}

		// For Sales department users, return their associated territories based on access level
		if ($this->isSalesDepartment($employeeId)) {
			$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();

			// If user has specific Territory access
			if ($employee && $employee->territory > 0) {
				return DB::table('core_territory')
					->where('id', $employee->territory)
					->pluck('territory_name', 'id')
					->prepend('All Territories', 'All')
					->toArray();
			}

			// Return all territories for Sales users with higher access
			return DB::table('core_territory')
				->where('is_active', '1')
				->pluck('territory_name', 'id')
				->prepend('All Territories', 'All')
				->toArray();
		}

		// For other departments, return only their specific territory if assigned
		$employee = DB::table('core_employee')->where('employee_id', $employeeId)->first();
		if ($employee && $employee->territory > 0) {
			return DB::table('core_territory')
				->where('id', $employee->territory)
				->pluck('territory_name', 'id')
				->prepend('All Territories', 'All')
				->toArray();
		}

		return [];
	}

	public function getAccessLevel($employee)
	{
		$user = Auth::user();

		// Admin, hr_admin, management roles have full access
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return 'all';
		}

		// 🔒 If employee record not found
		if (!$employee) {
			return 'none';
		}

		// For Sales department users, return their access level
		if ($this->isSalesDepartment($employee->employee_id)) {
			if ($employee->territory > 0) return 'territory';
			if ($employee->region > 0) return 'region';
			if ($employee->zone > 0) return 'zone';
			if ($employee->bu > 0) return 'bu';
			return 'all'; // Sales users with no restrictions get all access
		}

		// For other departments, return their access level based on assigned hierarchy
		if ($employee->territory > 0) return 'territory';
		if ($employee->region > 0) return 'region';
		if ($employee->zone > 0) return 'zone';
		if ($employee->bu > 0) return 'bu';

		return 'none';
	}

	public function getAssociatedDepartmentList($employeeId)
	{
		$user = Auth::user();

		// Admin, hr_admin, management roles → show all departments
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
		$allIds = [(string)$empId];
		$this->collectSubordinatesFromEmployee($empId, $allIds);
		return $allIds;
	}

	private function collectSubordinatesFromEmployee($managerEmpId, &$allIds)
	{
		// Use core_employee table with emp_reporting field
		$subordinates = DB::table('core_employee')
			->where('emp_reporting', $managerEmpId)
			->where('emp_status', 'A')
			->pluck('employee_id')
			->toArray();

		foreach ($subordinates as $subEmpId) {
			$subEmpIdStr = (string)$subEmpId;
			if (!in_array($subEmpIdStr, $allIds)) {
				$allIds[] = $subEmpIdStr;
				$this->collectSubordinatesFromEmployee($subEmpIdStr, $allIds);
			}
		}
	}

	public function getTeamMemberIds($managerEmployeeId)
	{
		$teamIds = [$managerEmployeeId]; // Start with the manager themselves
		$this->getRecursiveTeamMembers($managerEmployeeId, $teamIds);
		return $teamIds;
	}

	private function getRecursiveTeamMembers($managerEmployeeId, &$teamIds)
	{
		// Get direct reports from core_employee table
		$directReports = DB::table('core_employee')
			->where('emp_reporting', $managerEmployeeId)
			->where('emp_status', 'A')
			->pluck('employee_id')
			->toArray();

		//\Log::info('Direct reports for manager ' . $managerEmployeeId . ': ' . json_encode($directReports));

		foreach ($directReports as $reportId) {
			if (!in_array($reportId, $teamIds)) {
				$teamIds[] = $reportId;
				$this->getRecursiveTeamMembers($reportId, $teamIds);
			}
		}
	}

	/**
	 * Check if user has full access (Admin, HR Admin, Management roles)
	 */
	public function hasFullAccess($employeeId = null)
	{
		$user = Auth::user();
		return $user->hasAnyRole(['Admin', 'hr_admin', 'management']);
	}

	/**
	 * Get associated verticals based on user access
	 */
	public function getAssociatedVerticalList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_vertical')
				->where('is_active', 1)
				->orderBy('vertical_name')
				->pluck('vertical_name', 'id')
				->prepend('All Verticals', 'All')
				->toArray();
		}

		// For Sales department users, show all verticals
		if ($this->isSalesDepartment($employeeId)) {
			return DB::table('core_vertical')
				->where('is_active', 1)
				->orderBy('vertical_name')
				->pluck('vertical_name', 'id')
				->prepend('All Verticals', 'All')
				->toArray();
		}

		// Get user's vertical from employee record for other departments
		$employee = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->first();

		if ($employee && $employee->emp_vertical > 0) {
			return DB::table('core_vertical')
				->where('id', $employee->emp_vertical)
				->where('is_active', 1)
				->pluck('vertical_name', 'id')
				->prepend('All Verticals', 'All')
				->toArray();
		}

		// Default: show all active verticals
		return DB::table('core_vertical')
			->where('is_active', 1)
			->orderBy('vertical_name')
			->pluck('vertical_name', 'id')
			->prepend('All Verticals', 'All')
			->toArray();
	}

	/**
	 * Get associated sub-departments based on user access
	 */
	public function getAssociatedSubDepartmentList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return DB::table('core_sub_department')
				->where('is_active', 1)
				->orderBy('sub_department_name')
				->pluck('sub_department_name', 'id')
				->prepend('All Sub Departments', 'All')
				->toArray();
		}

		// For Sales department users, show all sub-departments
		if ($this->isSalesDepartment($employeeId)) {
			return DB::table('core_sub_department')
				->where('is_active', 1)
				->orderBy('sub_department_name')
				->pluck('sub_department_name', 'id')
				->prepend('All Sub Departments', 'All')
				->toArray();
		}

		// Get user's sub-department from employee record for other departments
		$employee = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->first();

		if ($employee && $employee->emp_sub_department > 0) {
			return DB::table('core_sub_department')
				->where('id', $employee->emp_sub_department)
				->where('is_active', 1)
				->pluck('sub_department_name', 'id')
				->prepend('All Sub Departments', 'All')
				->toArray();
		}

		// Default: show all active sub-departments
		return DB::table('core_sub_department')
			->where('is_active', 1)
			->orderBy('sub_department_name')
			->pluck('sub_department_name', 'id')
			->prepend('All Sub Departments', 'All')
			->toArray();
	}
}
