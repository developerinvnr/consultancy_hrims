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
		$request->validate([
			'role_name' => 'required|string|max:255|unique:roles,name',
			'guard_name' => 'required|string|max:255',
			'permission' => 'required|array',
			'permission.*' => 'exists:permissions,id',
		]);

		$role = Role::create([
			'name' => $request->role_name,
			'guard_name' => $request->guard_name,
		]);

		$permissions = Permission::whereIn('id', $request->permission)->get();
        $role->syncPermissions($permissions);

		return redirect()
			->route('roles.index')
			->with('success', 'Role created successfully.');
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
		$request->validate([
			'role_name' => 'required|string|max:255|unique:roles,name,' . $role->id,
			'guard_name' => 'required|string|max:255',
			'permission' => 'required|array',
			'permission.*' => 'exists:permissions,id',
		]);

		$role->update([
			'name' => $request->role_name,
			'guard_name' => $request->guard_name,
		]);

		$permissions = Permission::whereIn('id', $request->permission)->get();
        $role->syncPermissions($permissions);

		return redirect()
			->route('roles.index')
			->with('success', 'Role updated successfully.');
	}

	public function destroy(Role $role)
	{
		$userCount = DB::table('model_has_roles')
			->where('role_id', $role->id)
			->count();

		if ($userCount > 0) {
			return redirect()
				->route('roles.index')
				->with('error', 'Cannot delete role. It is assigned to ' . $userCount . ' user(s).');
		}

		$role->delete();

		return redirect()
			->route('roles.index')
			->with('success', 'Role deleted successfully.');
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
