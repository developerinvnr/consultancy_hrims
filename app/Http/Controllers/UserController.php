<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
	public function index(Request $request)
	{
		$roles = Role::all();

		// Get employee list for the dropdown
		$employee_list = Employee::where('emp_status', 'A')
			->select('id', 'employee_id','emp_name', 'emp_code', 'emp_email', 'emp_department','emp_reporting')
			->orderBy('emp_code')
			->get();

		// Get search parameters
		$search = $request->get('search', '');
		$status = $request->get('status', '');

		// Query users
		$query = User::with('roles')
			->select([
				'users.id',
				'users.name',
				'users.email',
				'users.status',
				'users.created_at',
				'users.emp_id'
			]);

		// Apply filters
		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('users.id', 'like', "%{$search}%")
					->orWhere('users.name', 'like', "%{$search}%")
					->orWhere('users.email', 'like', "%{$search}%");
			});
		}

		if ($status) {
			$query->where('users.status', $status);
		}

		// Get paginated results
		$users = $query->orderBy('users.id', 'desc')->paginate(10);

		return view('users.index', compact('users', 'roles', 'employee_list'));
	}

	/**
	 * Get business units filtered by user permissions
	 */
	protected function getFilteredBusinessUnits($user, $employeeId)
	{
		if ($user->hasAnyRole(['Super Admin', 'Admin', 'SP Admin', 'Management'])) {
			return DB::table('core_business_unit')
				->where('is_active', '1')
				->where('business_type', '1')
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
				->where('is_active', '1')
				->where('business_type', '1')
				->pluck('business_unit_name', 'id')
				->prepend('Select BU', '')
				->toArray();
		}

		return [];
	}

	/**
	 * Get zones filtered by user permissions
	 */
	protected function getFilteredZones($user, $employeeId)
	{
		if ($user->hasAnyRole(['Super Admin', 'Admin', 'SP Admin', 'Management'])) {
			return DB::table('core_zone')
				->where('is_active', '1')
				->where('business_type', '1')
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
				->where('is_active', '1')
				->where('business_type', '1')
				->pluck('zone_name', 'id')
				->prepend('Select Zone', '')
				->toArray();
		}

		return [];
	}

	/**
	 * Get regions filtered by user permissions
	 */
	protected function getFilteredRegions($user, $employeeId)
	{
		if ($user->hasAnyRole(['Super Admin', 'Admin', 'SP Admin', 'Management'])) {
			return DB::table('core_region')
				->where('is_active', '1')
				->where('business_type', '1')
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

	/**
	 * Get territories filtered by user permissions
	 */
	protected function getFilteredTerritories($user, $employeeId)
	{
		if ($user->hasAnyRole(['Super Admin', 'Admin', 'SP Admin', 'Management'])) {
			return DB::table('core_territory')
				->where('is_active', '1')
				->where('business_type', '1')
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

	public function store(Request $request)
	{
		try {
			// dd($request->all());
			// Log::info('User create request received', [
			// 	'payload' => $request->except(['password', 'password_confirmation'])
			// ]);

			$request->validate([
				'name' => 'required|string|max:255',
				'email' => 'required|email|max:255|unique:users,email',
				'user_status' => 'required|in:A,P,D',
				'emp_id' => 'required|integer|exists:core_employee,employee_id',
				'reporting_id'  => 'nullable|integer',
				'password' => 'required|string|min:8|confirmed',
				'roles' => 'required|array',
				'roles.*' => 'exists:roles,id',
			]);

			$user = User::create([
				'name'       => $request->name,
				'email'      => $request->email,
				'emp_id'     => $request->emp_id,           // ← now saved!
				'emp_code'   => $request->emp_code ?? null, // optional
				'reporting_id' => $request->reporting_id ?? 0,
				'status'     => $request->user_status,
				'password'   => Hash::make($request->password),
			]);

			// Log::info('User created successfully', [
			// 	'user_id' => $user->id,
			// 	'email' => $user->email,
			// ]);

			$roles = Role::findMany($request->roles);
			$user->syncRoles($roles);

			// Log::info('Roles assigned to user', [
			// 	'user_id' => $user->id,
			// 	'roles' => $roles->pluck('name')
			// ]);

			return response()->json([
				'success' => true,
				'message' => 'User created successfully.',
			]);
		} catch (ValidationException $e) {

			// Log::warning('User validation failed', [
			// 	'errors' => $e->errors()
			// ]);

			return response()->json([
				'success' => false,
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {

			// Log::error('Error creating user', [
			// 	'message' => $e->getMessage(),
			// 	'file'    => $e->getFile(),
			// 	'line'    => $e->getLine(),
			// 	'trace'   => $e->getTraceAsString(),
			// ]);

			return response()->json([
				'success' => false,
				'message' => $e->getMessage(), // temporarily expose real error
			], 500);
		}
	}

	public function edit(Request $request, User $user)
	{
		$userRoles = $user->roles()->whereIn('id', Role::pluck('id'))->pluck('id')->toArray();
		return response()->json([
			'success' => true,
			'user' => $user,
			'userRoles' => $userRoles,
		]);
	}

	public function update(Request $request, User $user)
	{
		try {
			$request->validate([
				'name' => 'required|string|max:255',
				'email' => 'required|email|max:255|unique:users,email,' . $user->id,
				'user_status' => 'required|in:A,P,D',
				'password' => 'nullable|string|min:8|confirmed',
				'roles' => 'required|array',
				'roles.*' => 'exists:roles,id',
			], [
				'email.required' => 'Email is required.',
				'user_status.in' => 'Status must be either Active, Pending or Disabled.',
				'roles.*' => 'One or more selected roles are invalid.',
			]);

			$data = [
				'name' => $request->name,
				'email' => $request->email,
				'status' => $request->user_status,
			];

			if ($request->filled('password')) {
				$data['password'] = Hash::make($request->password);
			}

			$user->update($data);
			// Sync roles using IDs
			$roles = Role::findMany($request->roles);
			$user->syncRoles($roles);

			return response()->json([
				'success' => true,
				'message' => 'User updated successfully.',
			]);
		} catch (ValidationException $e) {
			return response()->json([
				'success' => false,
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Error updating user.',
			], 500);
		}
	}

	public function changePassword(Request $request, User $user)
	{
		try {
			$request->validate([
				'password' => 'required|string|min:8|confirmed',
			]);

			$user->update([
				'password' => Hash::make($request->password),
			]);

			return response()->json([
				'success' => true,
				'message' => 'Password updated successfully.',
			]);
		} catch (ValidationException $e) {
			return response()->json([
				'success' => false,
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Error updating password.',
			], 500);
		}
	}

	public function destroy(User $user)
	{
		try {
			$user->delete();
			return response()->json([
				'success' => true,
				'message' => 'User deleted successfully.',
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Error deleting user.',
			], 500);
		}
	}

	public function export(Request $request)
	{
		try {
			return Excel::download(new UsersExport($request), 'UserList.xlsx');
		} catch (\Exception $e) {
			\Log::error('Export error: ' . $e->getMessage());
			return response()->json(['error' => 'Failed to export users'], 500);
		}
	}

	public function give_permission($id)
	{
		$permission_list = Permission::orderBy('group_name')->get();
		$grouped_results = $permission_list->mapToGroups(function ($item, $key) {
			return [$item->group_name => ['name' => $item->name, 'id' => $item->id]];
		});
		$permissions = $grouped_results->toArray();

		$userPermissions = DB::table('model_has_permissions')->where('model_id', $id)->pluck('permission_id', 'permission_id')->all();
		$user = User::find($id);
		return view('users.give_permission', compact('permissions', 'userPermissions', 'user'));
	}

	public function set_user_permission(Request $request, $id)
	{
		$user = User::find($id);
		$user->syncPermissions($request->input('permission'));
		return redirect()->back()->with('success', 'Permission Added Successfully.');
	}
}
