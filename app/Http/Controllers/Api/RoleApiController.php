<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role\Permission;
use App\Models\Role\Role;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
    public function show(string $slug, int $id): JsonResponse
    {
        $role = Role::withCount('users')->find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        $modules = $this->permService->getPermissionsForRoleUI($role->id);

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
        $authUser = auth('sanctum')->user();

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'color'          => ['nullable', 'string', 'max:20'],
            'description'    => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        $role = Role::create([
            'tenant_id'   => app('tenant')->id,
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name'], '_'),
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#1B4F72',
            'is_system'   => false,
            'is_super'    => false,
            'created_by'  => $authUser?->id,
        ]);

        $this->permService->saveRolePermissions(
            $role->id,
            $validated['permission_ids'] ?? [],
            $authUser?->id ?? 0
        );

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
        $authUser = auth('sanctum')->user();

        $role = Role::withoutTenantScope()->find($id);

        if (!$role || (int) $role->tenant_id !== (int) app('tenant')->id) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        $validated = $request->validate([
            'name'           => ['sometimes', 'required', 'string', 'max:100'],
            'color'          => ['nullable', 'string', 'max:20'],
            'description'    => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        $updateData = [
            'color'       => $validated['color']       ?? $role->color,
            'description' => $validated['description'] ?? $role->description,
        ];

        // System roles (Hospital Admin) name cannot be changed
        if (!$role->is_system && isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
            $updateData['slug'] = Str::slug($validated['name'], '_');
        }

        $role->update($updateData);

        // Super roles bypass all permissions — don't overwrite their role_permissions
        if (!$role->is_super && array_key_exists('permission_ids', $validated)) {
            $this->permService->saveRolePermissions(
                $role->id,
                $validated['permission_ids'] ?? [],
                $authUser?->id ?? 0
            );
        }

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
     * All platform permissions grouped by module — used to build the permissions UI.
     */
    public function permissions(): JsonResponse
    {
        $allPermissions = Permission::orderBy('module')->orderBy('sort_order')->get();

        $grouped = [];
        foreach ($allPermissions as $perm) {
            $grouped[$perm->module][] = [
                'id'          => $perm->id,
                'action'      => $perm->action,
                'label'       => $perm->label,
                'description' => $perm->description,
            ];
        }

        return response()->json(['success' => true, 'data' => $grouped]);
    }
}
