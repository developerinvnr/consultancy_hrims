<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
	public function index(Request $request)
	{
		$search = $request->get('search', '');

		$query = Role::withCount('permissions');

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('id', 'like', "%{$search}%");
			});
		}

		$roles = $query->orderBy('id', 'desc')->paginate(10);

		return view('roles.index', compact('roles'));
	}

	public function create()
	{
		$permissions = Permission::orderBy('group_name')->get();
		$groupedPermissions = $permissions->groupBy('group_name');

		return view('roles.create', compact('groupedPermissions'));
	}

	public function store(Request $request)
	{
		try {
			$request->validate([
				'role_name' => 'required|string|max:255|unique:roles,name',
				'guard_name' => 'required|string|max:255',
				'permission' => 'required|array',
				'permission.*' => 'exists:permissions,id',
			], [
				'role_name.required' => 'Role name is required.',
				'role_name.unique' => 'This role name already exists.',
				'guard_name.required' => 'Guard name is required.',
				'permission.required' => 'At least one permission is required.',
				'permission.*' => 'Invalid permission selected.',
			]);

			$role = Role::create([
				'name' => $request->role_name,
				'guard_name' => $request->guard_name,
			]);

			$permissions = Permission::whereIn('id', $request->permission)->get();
			$role->syncPermissions($permissions);

			return response()->json([
				'success' => true,
				'message' => 'Role created successfully.',
				'redirect' => route('roles.index')
			]);
		} catch (ValidationException $e) {
			return response()->json([
				'success' => false,
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			Log::error('Error creating role: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Error creating role.',
			], 500);
		}
	}

	public function edit(Role $role)
	{
		$permissions = Permission::orderBy('group_name')->get();
		$groupedPermissions = $permissions->groupBy('group_name');

		$rolePermissions = $role->permissions()->pluck('id')->toArray();

		return view('roles.edit', compact('role', 'groupedPermissions', 'rolePermissions'));
	}

	public function update(Request $request, Role $role)
	{
		try {
			$request->validate([
				'role_name' => 'required|string|max:255|unique:roles,name,' . $role->id,
				'guard_name' => 'required|string|max:255',
				'permission' => 'required|array',
				'permission.*' => 'exists:permissions,id',
			], [
				'role_name.required' => 'Role name is required.',
				'role_name.unique' => 'This role name already exists.',
				'guard_name.required' => 'Guard name is required.',
				'permission.required' => 'At least one permission is required.',
				'permission.*' => 'Invalid permission selected.',
			]);

			$role->update([
				'name' => $request->role_name,
				'guard_name' => $request->guard_name,
			]);

			$permissions = Permission::whereIn('id', $request->permission)->get();
			$role->syncPermissions($permissions);

			return response()->json([
				'success' => true,
				'message' => 'Role updated successfully.',
				'redirect' => route('roles.index')
			]);
		} catch (ValidationException $e) {
			return response()->json([
				'success' => false,
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			Log::error('Error updating role: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Error updating role.',
			], 500);
		}
	}

	public function destroy(Role $role)
	{
		try {
			// Check if role is assigned to any user
			$userCount = DB::table('model_has_roles')
				->where('role_id', $role->id)
				->count();

			if ($userCount > 0) {
				return response()->json([
					'success' => false,
					'message' => 'Cannot delete role. It is assigned to ' . $userCount . ' user(s).',
				], 400);
			}

			$role->delete();

			return response()->json([
				'success' => true,
				'message' => 'Role deleted successfully.',
			]);
		} catch (\Exception $e) {
			Log::error('Error deleting role: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Error deleting role.',
			], 500);
		}
	}

	public function permissions(Role $role)
	{
		// Get all permissions grouped by module/group
		$permissions = Permission::orderBy('group_name')->get();
		$groupedPermissions = $permissions->groupBy('group_name');

		// Get role's current permissions
		$rolePermissions = $role->permissions()->pluck('id')->toArray();

		return view('roles.permissions', compact('role', 'groupedPermissions', 'rolePermissions'));
	}
}
