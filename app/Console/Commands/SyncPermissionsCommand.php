<?php

namespace App\Console\Commands;

use App\Models\Role\Permission;
use App\Models\Role\Role;
use App\Models\Role\RolePermission;
use App\Services\Auth\PermissionMatrix;
use App\Services\Auth\RolePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sync permissions from config/permission_matrix.php into DB.
 *
 *   php artisan permissions:sync
 *   php artisan permissions:sync --fresh
 *   php artisan permissions:sync --apply-templates
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync
                            {--fresh : Truncate permissions and role_permissions before sync}
                            {--apply-templates : Re-apply system role templates from matrix}
                            {--force : Skip confirmation on --fresh}';

    protected $description = 'Sync permission matrix → permissions table (web source of truth)';

    public function handle(RolePermissionService $permService): int
    {
        $rows = PermissionMatrix::flatten();

        if ($rows === []) {
            $this->error('permission_matrix is empty. Check config/permission_matrix.php');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            if (! $this->option('force') && ! $this->confirm('This will DELETE all permissions and role_permissions. Continue?', false)) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }

            $this->warn('Truncating permissions + role_permissions...');
            Schema::disableForeignKeyConstraints();
            DB::table('role_permissions')->truncate();
            DB::table('permissions')->truncate();
            Schema::enableForeignKeyConstraints();
        } else {
            $this->remapLegacyActions();
        }

        $this->info('Syncing '.count($rows).' permissions from matrix...');

        $seenActions = [];
        foreach ($rows as $row) {
            $seenActions[] = $row['action'];

            Permission::updateOrCreate(
                ['action' => $row['action']],
                [
                    'module' => $row['module'],
                    'label' => $row['label'],
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }

        if (! $this->option('fresh')) {
            $this->expandManageGrants($seenActions);
            $this->splitOtAppointmentEditGrants();

            $orphans = Permission::query()
                ->whereNotIn('action', $seenActions)
                ->pluck('action');

            if ($orphans->isNotEmpty()) {
                $this->warn('Orphan permissions (not in matrix): '.$orphans->implode(', '));
                $this->line('Removing orphans that were expanded from *_manage …');
                Permission::query()->whereNotIn('action', $seenActions)->get()->each(function (Permission $perm) {
                    RolePermission::where('permission_id', $perm->id)->delete();
                    $perm->delete();
                });
            }
        }

        if ($this->option('apply-templates') || $this->option('fresh')) {
            $this->applyRoleTemplates($permService);
        }

        $this->syncSuperRoles();
        $this->grantDefaultDashboardWidgets();

        // Remap changes action strings; clear role permission caches
        $tenantIds = Role::withoutTenantScope()->pluck('tenant_id')->unique()->filter();
        foreach ($tenantIds as $tenantId) {
            $permService->flushTenantCache((int) $tenantId);
        }

        $this->info('Done. Permissions in DB: '.Permission::count());

        return self::SUCCESS;
    }

    /**
     * Roles that never got Dashboard widget keys yet → grant ALL (default show UI).
     * Admins can uncheck later in Roles → Dashboard.
     */
    private function grantDefaultDashboardWidgets(): void
    {
        $actions = PermissionMatrix::dashboardWidgetKeys();
        $permIds = Permission::query()
            ->whereIn('action', $actions)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($permIds === []) {
            return;
        }

        $this->info('Ensuring default Dashboard widgets for roles missing them...');
        $granted = 0;

        $roles = Role::withoutTenantScope()
            ->where(function ($q) {
                $q->where('is_super', false)->orWhereNull('is_super');
            })
            ->get();

        foreach ($roles as $role) {
            if ($role->is_super) {
                continue;
            }

            $already = RolePermission::query()
                ->where('role_id', $role->id)
                ->whereIn('permission_id', $permIds)
                ->where('is_granted', true)
                ->exists();

            if ($already) {
                continue;
            }

            foreach ($permIds as $permissionId) {
                RolePermission::updateOrCreate(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'is_granted' => true,
                        'updated_by' => null,
                    ]
                );
            }
            $granted++;
        }

        $this->line("  default dashboard widgets applied to {$granted} role(s)");
    }

    /**
     * Rename old dotted actions → new keys in place (keeps permission IDs + role grants).
     * Skips expand_map sources (*_manage) — those are handled by expandManageGrants().
     */
    private function remapLegacyActions(): void
    {
        $map = PermissionMatrix::legacyMap();
        $expandSources = array_keys(PermissionMatrix::expandMap());
        if ($map === []) {
            return;
        }

        $this->info('Remapping legacy permission keys...');

        foreach ($map as $old => $new) {
            if ($old === $new || in_array($old, $expandSources, true)) {
                continue;
            }

            $oldPerm = Permission::where('action', $old)->first();
            $newPerm = Permission::where('action', $new)->first();

            if ($oldPerm && ! $newPerm) {
                $oldPerm->update(['action' => $new]);
                $this->line("  renamed {$old} → {$new}");

                continue;
            }

            if ($oldPerm && $newPerm) {
                $oldGrants = RolePermission::where('permission_id', $oldPerm->id)->get();
                foreach ($oldGrants as $grant) {
                    $existing = RolePermission::where('role_id', $grant->role_id)
                        ->where('permission_id', $newPerm->id)
                        ->first();

                    if ($existing) {
                        if ($grant->is_granted && ! $existing->is_granted) {
                            $existing->update(['is_granted' => true]);
                        }
                    } else {
                        RolePermission::create([
                            'role_id' => $grant->role_id,
                            'permission_id' => $newPerm->id,
                            'is_granted' => $grant->is_granted,
                            'updated_by' => $grant->updated_by,
                        ]);
                    }
                }

                RolePermission::where('permission_id', $oldPerm->id)->delete();
                $oldPerm->delete();
                $this->line("  merged {$old} → {$new}");
            }
        }
    }

    /**
     * Copy grants from old *_manage / dotted keys onto full CRUD action set, then drop old rows.
     *
     * @param  list<string>  $seenActions
     */
    private function expandManageGrants(array $seenActions): void
    {
        $expand = PermissionMatrix::expandMap();
        if ($expand === []) {
            return;
        }

        $this->info('Expanding manage → view/add/edit/delete grants...');
        $byAction = Permission::query()->get()->keyBy('action');

        foreach ($expand as $oldAction => $newActions) {
            $oldPerm = $byAction->get($oldAction);
            if (! $oldPerm) {
                continue;
            }

            $roleIds = RolePermission::where('permission_id', $oldPerm->id)
                ->where('is_granted', true)
                ->pluck('role_id');

            foreach ($newActions as $newAction) {
                $newPerm = $byAction->get($newAction);
                if (! $newPerm) {
                    continue;
                }

                foreach ($roleIds as $roleId) {
                    RolePermission::updateOrCreate(
                        ['role_id' => $roleId, 'permission_id' => $newPerm->id],
                        ['is_granted' => true]
                    );
                }
            }

            RolePermission::where('permission_id', $oldPerm->id)->delete();
            $oldPerm->delete();
            $byAction->forget($oldAction);
            $this->line("  expanded {$oldAction} → ".implode(', ', $newActions));
        }
    }

    /**
     * Roles that already had ot_appointment_edit also get confirm + cancel
     * (previously bundled under one key). Keeps edit itself.
     */
    private function splitOtAppointmentEditGrants(): void
    {
        $byAction = Permission::query()->get()->keyBy('action');
        $edit = $byAction->get('ot_appointment_edit');
        if (! $edit) {
            return;
        }

        $targets = array_filter([
            $byAction->get('ot_appointment_confirm'),
            $byAction->get('ot_appointment_cancel'),
        ]);

        if ($targets === []) {
            return;
        }

        $roleIds = RolePermission::where('permission_id', $edit->id)
            ->where('is_granted', true)
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $this->info('Splitting OT appointment edit → confirm + cancel grants...');

        foreach ($targets as $target) {
            foreach ($roleIds as $roleId) {
                RolePermission::updateOrCreate(
                    ['role_id' => $roleId, 'permission_id' => $target->id],
                    ['is_granted' => true]
                );
            }
            $this->line("  copied ot_appointment_edit → {$target->action}");
        }
    }

    private function applyRoleTemplates(RolePermissionService $permService): void
    {
        $templates = PermissionMatrix::roleTemplates();
        $all = Permission::all()->keyBy('action');

        if ($all->isEmpty()) {
            $this->error('No permissions in DB after sync.');

            return;
        }

        $tenantIds = Role::withoutTenantScope()
            ->whereIn('slug', array_keys($templates))
            ->pluck('tenant_id')
            ->unique()
            ->filter();

        $this->info('Applying role templates for '.$tenantIds->count().' tenant(s)...');

        foreach ($tenantIds as $tenantId) {
            foreach ($templates as $slug => $grantedKeys) {
                $role = Role::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $slug)
                    ->first();

                if (! $role || $role->is_super) {
                    continue;
                }

                $grantedIds = [];
                foreach ($grantedKeys as $key) {
                    $canonical = PermissionMatrix::resolve($key);
                    if ($all->has($canonical)) {
                        $grantedIds[] = $all[$canonical]->id;
                    } else {
                        $this->warn("Template [{$slug}] unknown key: {$key}");
                    }
                }

                $permService->saveRolePermissions($role->id, $grantedIds, 0);
            }

            $permService->flushTenantCache((int) $tenantId);
        }
    }

    private function syncSuperRoles(): void
    {
        $superRoles = Role::withoutTenantScope()->where('is_super', true)->get();
        $permissionIds = Permission::pluck('id');
        $now = now();

        foreach ($superRoles as $role) {
            foreach ($permissionIds as $permissionId) {
                RolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'permission_id' => $permissionId],
                    ['is_granted' => true, 'updated_at' => $now]
                );
            }
        }

        $this->info('Super roles synced with all permissions: '.$superRoles->count());
    }
}
