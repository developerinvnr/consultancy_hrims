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
	 * Check if user has management department access
	 */
	private function hasManagementAccess($employeeId)
	{
		$user = Auth::user();

		// Admin, hr_admin, management roles have full access
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return true;
		}

		// Check if user belongs to Management department (ID 18)
		$departmentId = $this->getUserDepartment($employeeId);
		return $departmentId == 18;
	}

	public function getAssociatedBusinessUnitList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
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

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
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

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
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

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
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
		$user = Auth::user();

		// Admin, hr_admin, management roles have full access
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return 'all';
		}

		// 🔒 If employee record not found
		if (!$employee) {
			return 'none';
		}

		// If user belongs to Management department, give full access
		if ($employee->department == 18) {
			return 'all';
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

		// Admin, hr_admin, management roles OR Management department → show all departments
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
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

	/**
	 * Get all employee IDs under a manager (including indirect reports)
	 * This works with the core_employee table structure
	 */
	public function getTeamMemberIds($managerEmployeeId)
	{
		$teamIds = [];
		$this->getRecursiveTeamMembers($managerEmployeeId, $teamIds);

		// Log for debugging
		\Log::info('Team members for manager ' . $managerEmployeeId . ': ' . json_encode($teamIds));

		return $teamIds;
	}

	/**
	 * Recursively get all team members from core_employee table
	 */
	private function getRecursiveTeamMembers($managerEmployeeId, &$teamIds)
	{
		// Add current manager
		if (!in_array($managerEmployeeId, $teamIds)) {
			$teamIds[] = $managerEmployeeId;
		}

		// Get direct reports from core_employee table
		$directReports = DB::table('core_employee')
			->where('emp_reporting', $managerEmployeeId)
			->where('emp_status', 'A')
			->pluck('employee_id')
			->toArray();

		\Log::info('Direct reports for manager ' . $managerEmployeeId . ': ' . json_encode($directReports));

		foreach ($directReports as $reportId) {
			$this->getRecursiveTeamMembers($reportId, $teamIds);
		}
	}

	/**
	 * Check if user has full access (Admin, HR Admin, Management role, or Management department)
	 */
	public function hasFullAccess($employeeId = null)
	{
		$user = Auth::user();

		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management'])) {
			return true;
		}

		if ($employeeId) {
			$departmentId = $this->getUserDepartment($employeeId);
			return $departmentId == 18;
		}

		return false;
	}

	/**
	 * Get associated verticals based on user access
	 */
	public function getAssociatedVerticalList($employeeId)
	{
		$user = Auth::user();

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
			return DB::table('core_vertical')
				->where('is_active', 1)
				->orderBy('vertical_name')
				->pluck('vertical_name', 'id')
				->prepend('All Verticals', 'All')
				->toArray();
		}

		// Get user's vertical from employee record
		$employee = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->first();

		if ($employee && $employee->emp_vertical > 0) {
			// User has specific vertical access
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

		// Full access for Admin, hr_admin, management roles OR Management department
		if ($user->hasAnyRole(['Admin', 'hr_admin', 'management']) || $this->hasManagementAccess($employeeId)) {
			return DB::table('core_sub_department')
				->where('is_active', 1)
				->orderBy('sub_department_name')
				->pluck('sub_department_name', 'id')
				->prepend('All Sub Departments', 'All')
				->toArray();
		}

		// Get user's sub-department from employee record
		$employee = DB::table('core_employee')
			->where('employee_id', $employeeId)
			->first();

		if ($employee && $employee->emp_sub_department > 0) {
			// User has specific sub-department access
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