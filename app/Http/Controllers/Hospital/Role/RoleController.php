<?php

namespace App\Http\Controllers\Hospital\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Role\RoleStoreRequest;
use App\Http\Requests\Hospital\Role\RoleUpdateRequest;
use App\Models\Role\Role;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Role & Permission Management — Hospital Admin
 *
 * ROUTES: /{slug}/roles
 * ACCESS: permission:master.roles
 */
class RoleController extends Controller
{
    public function __construct(private RolePermissionService $permService) {}

    public function index(): View
    {
        $slug  = request()->route('slug');
        $roles = Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get();

        return view('hospital.roles.index', compact('roles', 'slug'));
    }

    public function create(): View
    {
        $slug       = request()->route('slug');
        $modules    = $this->permService->getPermissionsForRoleUI(0);

        return view('hospital.roles.create', compact('slug', 'modules'));
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $slug = $request->route('slug');

        $role = Role::create([
            'tenant_id'  => app('tenant')->id,
            'name'       => $request->validated('name'),
            'slug'       => Str::slug($request->validated('name'), '_'),
            'description'=> $request->validated('description'),
            'color'      => $request->validated('color', '#1B4F72'),
            'is_system'  => false,
            'is_super'   => false,
            'created_by' => auth('hospital_user')->id(),
        ]);

        $this->permService->saveRolePermissions(
            $role->id,
            $request->validated('permissions', []),
            auth('hospital_user')->id()
        );

        return redirect()->route('hospital.roles.index', ['slug' => $slug])
                         ->with('success', 'Role "' . $role->name . '" created successfully.');
    }

    public function edit(string $slug, string $id): View
    {
        $role    = Role::withoutGlobalScopes()
            ->where('id', $id)
            ->withCount('users')
            ->firstOrFail();

        abort_if((int) $role->tenant_id !== (int) app('tenant')->id, 403);
        $modules = $this->permService->getPermissionsForRoleUI($role->id);

        return view('hospital.roles.edit', compact('role', 'slug', 'modules'));
    }

    public function update(RoleUpdateRequest $request, string $slug, string $id): RedirectResponse
    {
        // withoutTenantScope() use karo — tenant scope conflict se bachne ke liye
        $role = Role::withoutTenantScope()->findOrFail((int) $id);
        abort_if((int) $role->tenant_id !== (int) app('tenant')->id, 403);

        $updateData = [
            'description' => $request->validated('description'),
            'color'       => $request->validated('color', $role->color),
        ];

        // System roles (Hospital Admin) — name cannot be changed
        if (! $role->is_system) {
            $updateData['name'] = $request->validated('name');
            $updateData['slug'] = Str::slug($request->validated('name'), '_');
        }

        $role->update($updateData);

        // is_super roles bypass all permission checks — don't overwrite role_permissions
        if (! $role->is_super) {
            $this->permService->saveRolePermissions(
                $role->id,
                $request->validated('permissions', []),
                auth('hospital_user')->id()
            );
        }

        return redirect()->route('hospital.roles.edit', ['slug' => $slug, 'id' => $role->id])
                         ->with('success', 'Role "' . $role->name . '" updated.');
    }

    public function destroy(string $slug, string $id): RedirectResponse
    {
        $role = Role::withoutTenantScope()->withCount('users')->findOrFail((int) $id);
        abort_if((int) $role->tenant_id !== (int) app('tenant')->id, 403);

        if ($role->is_system) {
            return redirect()->back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users_count > 0) {
            return redirect()->back()->with('error', 'Reassign ' . $role->users_count . ' user(s) to another role before deleting.');
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('hospital.roles.index', ['slug' => $slug])
                         ->with('success', 'Role "' . $roleName . '" deleted.');
    }
}
