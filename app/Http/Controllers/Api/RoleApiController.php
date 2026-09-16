<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role\Role;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleApiController extends Controller
{
    public function __construct(private RolePermissionService $permService)
    {
    }

    /**
     * List all roles for this tenant with user count.
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'slug'        => $role->slug,
                'color'       => $role->color,
                'description' => $role->description,
                'is_system'   => $role->is_system,
                'is_super'    => $role->is_super,
                'users_count' => $role->users_count,
                'is_deletable' => $role->isDeletable(),
            ]);

        return response()->json(['success' => true, 'data' => $roles]);
    }

    /**
     * Single role with its permission assignments grouped by module.
     */
    public function show(Request $request, string $slug, int $id): JsonResponse
    {
        $role = Role::withCount('users')->find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        $modules = $this->permService->getPermissionsForRoleUI($role->id);
        if (! $request->user()->role?->is_super) {
            $modules = $this->filterModulesByPermissionIds($modules, $this->assignablePermissionIds($request->user()));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'slug'        => $role->slug,
                'color'       => $role->color,
                'description' => $role->description,
                'is_system'   => $role->is_system,
                'is_super'    => $role->is_super,
                'users_count' => $role->users_count,
                'is_deletable' => $role->isDeletable(),
                'permissions'  => $modules,
            ],
        ]);
    }

    /**
     * Create a new custom role with permissions.
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'color'          => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/', 'max:7'],
            'description'    => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $name = trim($validated['name']);
        $roleSlug = Str::slug($name, '_');
        $this->assertUniqueRole($tenantId, $name, $roleSlug);
        $permissionIds = $this->validatedAssignablePermissionIds($authUser, $validated['permission_ids'] ?? []);

        $role = DB::transaction(function () use ($authUser, $tenantId, $validated, $name, $roleSlug, $permissionIds): Role {
            $role = Role::create([
                'tenant_id'   => $tenantId,
                'name'        => $name,
                'slug'        => $roleSlug,
                'description' => $validated['description'] ?? null,
                'color'       => $validated['color'] ?? '#1B4F72',
                'is_system'   => false,
                'is_super'    => false,
                'created_by'  => $authUser?->id,
            ]);

            $this->permService->saveRolePermissions($role->id, $permissionIds, $authUser?->id ?? 0);

            return $role;
        });

        return response()->json([
            'success' => true,
            'message' => "Role \"{$role->name}\" created successfully.",
            'data'    => ['id' => $role->id, 'name' => $role->name, 'slug' => $role->slug],
        ], 201);
    }

    /**
     * Update role name/color/description and its permissions.
     */
    public function update(Request $request, string $slug, int $id): JsonResponse
    {
        $authUser = $request->user();
        $tenantId = (int) app('tenant')->id;

        $role = Role::withoutTenantScope()->find($id);

        if (!$role || (int) $role->tenant_id !== (int) app('tenant')->id) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        if (! $authUser->role?->is_super && ($role->is_system || $role->is_super)) {
            return response()->json(['success' => false, 'message' => 'Only a hospital administrator may edit system roles.'], 403);
        }

        $validated = $request->validate([
            'name'           => ['sometimes', 'required', 'string', 'max:255'],
            'color'          => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/', 'max:7'],
            'description'    => ['nullable', 'string', 'max:255'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $requestedPermissionIds = array_key_exists('permission_ids', $validated)
            ? $this->validatedAssignablePermissionIds($authUser, $validated['permission_ids'] ?? [])
            : null;

        if ((int) $authUser->role_id === $role->id && $requestedPermissionIds !== null) {
            $currentIds = $role->grantedPermissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
            if (array_diff($requestedPermissionIds, $currentIds) !== []) {
                return response()->json(['success' => false, 'message' => 'You cannot elevate your own role.'], 403);
            }
        }

        $permissionIds = $requestedPermissionIds;
        if ($permissionIds !== null && ! $authUser->role?->is_super) {
            $currentIds = $role->grantedPermissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
            $hiddenExistingIds = array_diff($currentIds, $this->assignablePermissionIds($authUser));
            $permissionIds = array_values(array_unique([...$permissionIds, ...$hiddenExistingIds]));
        }

        $updateData = [
            'color'       => $validated['color']       ?? $role->color,
            'description' => $validated['description'] ?? $role->description,
        ];

        // System roles (Hospital Admin) name cannot be changed
        if (!$role->is_system && isset($validated['name'])) {
            $name = trim($validated['name']);
            $roleSlug = Str::slug($name, '_');
            $this->assertUniqueRole($tenantId, $name, $roleSlug, $role->id);
            $updateData['name'] = $name;
            $updateData['slug'] = $roleSlug;
        }

        DB::transaction(function () use ($role, $updateData, $permissionIds, $authUser): void {
            $role->update($updateData);

            // Super roles bypass all permissions — don't overwrite their role_permissions.
            if (! $role->is_super && $permissionIds !== null) {
                $this->permService->saveRolePermissions($role->id, $permissionIds, $authUser?->id ?? 0);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Role \"{$role->name}\" updated.",
            'data'    => ['id' => $role->id, 'name' => $role->name],
        ]);
    }

    /**
     * Delete a custom non-system role.
     */
    public function destroy(string $slug, int $id): JsonResponse
    {
        $role = Role::withoutTenantScope()->withCount('users')->find($id);

        if (!$role || (int) $role->tenant_id !== (int) app('tenant')->id) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        if ($role->is_system) {
            return response()->json(['success' => false, 'message' => 'System roles cannot be deleted.'], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Reassign {$role->users_count} user(s) to another role before deleting.",
            ], 422);
        }

        $roleName = $role->name;
        $role->delete();

        return response()->json(['success' => true, 'message' => "Role \"{$roleName}\" deleted."]);
    }

    /**
     * All platform permissions grouped by module → feature → actions —
     * used to build the "new role" permissions UI. Same nested shape as
     * show()'s "permissions" key (roleId 0 = nothing granted yet), so
     * clients only need one parser for both endpoints.
     */
    public function permissions(Request $request): JsonResponse
    {
        $modules = $this->permService->getPermissionsForRoleUI(0);

        if (! $request->user()->role?->is_super) {
            $modules = $this->filterModulesByPermissionIds(
                $modules,
                $this->assignablePermissionIds($request->user())
            );
        }

        return response()->json(['success' => true, 'data' => $modules]);
    }

    private function assertUniqueRole(int $tenantId, string $name, string $slug, ?int $ignoreId = null): void
    {
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'The role name must contain letters or numbers.']);
        }

        $query = Role::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($name, $slug) {
                $q->where('name', $name)->orWhere('slug', $slug);
            });

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'A role with this name already exists for this hospital.']);
        }
    }

    /** @return list<int> */
    private function validatedAssignablePermissionIds($authUser, array $permissionIds): array
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        if ($authUser->role?->is_super) {
            return $permissionIds;
        }

        $assignableIds = $this->assignablePermissionIds($authUser);

        if (array_diff($permissionIds, $assignableIds) !== []) {
            throw ValidationException::withMessages([
                'permission_ids' => 'You may only assign permissions granted to your own role.',
            ]);
        }

        return $permissionIds;
    }

    /** @return list<int> */
    private function assignablePermissionIds($authUser): array
    {
        return $authUser->role?->grantedPermissions()
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];
    }

    private function filterModulesByPermissionIds(array $modules, array $allowedIds): array
    {
        foreach ($modules as $moduleKey => &$module) {
            foreach ($module['features'] ?? [] as $featureKey => &$feature) {
                $feature['permissions'] = array_values(array_filter(
                    $feature['permissions'] ?? [],
                    fn (array $permission) => in_array((int) $permission['id'], $allowedIds, true)
                ));

                if ($feature['permissions'] === []) {
                    unset($module['features'][$featureKey]);
                }
            }
            unset($feature);

            if (($module['features'] ?? []) === []) {
                unset($modules[$moduleKey]);
            }
        }
        unset($module);

        return $modules;
    }
}