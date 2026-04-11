<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Models\Role\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('tenant:sync-ot-data {--tenant_id= : Sync only one tenant id}')]
#[Description('Backfill OT roles and default OT charge heads for existing tenants')]
class SyncTenantOTData extends Command
{
    public function handle(): int
    {
        $tenantId = $this->option('tenant_id');
        $tenants = Tenant::query()
            ->when($tenantId, fn ($query) => $query->whereKey((int) $tenantId))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found for sync.');

            return self::SUCCESS;
        }

        $defaultRoles = [
            ['name' => 'Hospital Admin', 'slug' => 'hospital_admin', 'color' => '#1B4F72', 'is_super' => true, 'is_system' => true],
            ['name' => 'Doctor', 'slug' => 'doctor', 'color' => '#2980B9', 'is_super' => false, 'is_system' => false],
            ['name' => 'Receptionist', 'slug' => 'receptionist', 'color' => '#27AE60', 'is_super' => false, 'is_system' => false],
            ['name' => 'OT Receptionist', 'slug' => 'ot_receptionist', 'color' => '#7D3C98', 'is_super' => false],
            ['name' => 'Accountant', 'slug' => 'accountant', 'color' => '#117A65', 'is_super' => false],
            ['name' => 'OT Doctor', 'slug' => 'ot_doctor', 'color' => '#2E86C1', 'is_super' => false],
            ['name' => 'OT Assistant', 'slug' => 'ot_assistant', 'color' => '#CA6F1E', 'is_super' => false],
        ];

        $defaultRoles = array_map(static function (array $role): array {
            if (! array_key_exists('is_system', $role)) {
                $role['is_system'] = false;
            }

            return $role;
        }, $defaultRoles);

        $defaultSlots = [
            ['slot_name' => 'Slot 9to12', 'start_time' => '09:00:00', 'end_time' => '12:00:00'],
            ['slot_name' => 'Slot 12to1', 'start_time' => '12:00:00', 'end_time' => '13:00:00'],
            ['slot_name' => 'Slot 1to3', 'start_time' => '13:00:00', 'end_time' => '15:00:00'],
            ['slot_name' => 'Slot 3to4', 'start_time' => '15:00:00', 'end_time' => '16:00:00'],
        ];

        $defaultOtTypes = ['Cataract', 'LASIK', 'Other'];

        $defaultLensOptions = ['Monofocal', 'Multifocal', 'Toric'];

        $defaultChargeHeads = [
            ['charge_name' => 'Surgeon Fee', 'percentage' => 50.00],
            ['charge_name' => 'Anesthesia', 'percentage' => 15.00],
            ['charge_name' => 'OT Charges', 'percentage' => 20.00],
            ['charge_name' => 'Nursing and Staff', 'percentage' => 10.00],
            ['charge_name' => 'Consumables', 'percentage' => 5.00],
        ];

        foreach ($tenants as $tenant) {
            $roleIdsBySlug = [];

            foreach ($defaultRoles as $roleData) {
                $role = Role::withoutTenantScope()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $roleData['slug']],
                    [
                        'name' => $roleData['name'],
                        'color' => $roleData['color'],
                        'is_system' => $roleData['is_system'],
                        'is_super' => $roleData['is_super'],
                    ]
                );

                $roleIdsBySlug[$roleData['slug']] = (int) $role->id;
            }

            $this->syncSection24PermissionMatrix($roleIdsBySlug);

            foreach ($defaultSlots as $slot) {
                $this->upsertTenantMasterRow(
                    'tbl_ot_slots',
                    ['tenant_id' => $tenant->id, 'slot_name' => $slot['slot_name']],
                    ['start_time' => $slot['start_time'], 'end_time' => $slot['end_time']]
                );
            }

            foreach ($defaultOtTypes as $name) {
                $this->upsertTenantMasterRow('ot_types', ['tenant_id' => $tenant->id, 'name' => $name], []);
            }

            $this->seedTenantSurgeryTypes($tenant->id);

            foreach ($defaultLensOptions as $name) {
                $this->upsertTenantMasterRow('ot_lens_options', ['tenant_id' => $tenant->id, 'name' => $name], ['is_active' => true]);
            }

            foreach ($defaultChargeHeads as $row) {
                $this->upsertTenantMasterRow(
                    'ot_charge_heads',
                    ['tenant_id' => $tenant->id, 'charge_name' => $row['charge_name']],
                    ['percentage' => $row['percentage'], 'is_active' => true]
                );
            }

            $this->info("Tenant #{$tenant->id} synced.");
        }

        $this->info('Tenant OT sync completed.');

        return self::SUCCESS;
    }

    private function upsertTenantMasterRow(string $table, array $where, array $values): void
    {
        $now = now()->toDateTimeString();

        $existing = DB::table($table)
            ->where($where)
            ->first();

        if ($existing) {
            DB::table($table)
                ->where('id', $existing->id)
                ->update([
                    ...$values,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table($table)->insert([
            ...$where,
            ...$values,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedTenantSurgeryTypes(int $tenantId): void
    {
        $parentMap = [
            'Cataract' => [
                'Phacoemulsification',
                'SICS (Small Incision Cataract Surgery)',
            ],
            'LASIK' => [
                'Standard LASIK',
                'Custom LASIK',
            ],
            'Other' => [
                'Minor Procedure',
            ],
        ];

        foreach ($parentMap as $parentName => $surgeryNames) {
            $parentId = DB::table('ot_types')
                ->where('tenant_id', $tenantId)
                ->where('name', $parentName)
                ->whereNull('deleted_at')
                ->value('id');

            if (! $parentId) {
                continue;
            }

            foreach ($surgeryNames as $surgeryName) {
                $this->upsertTenantMasterRow(
                    'ot_surgery_types',
                    ['tenant_id' => $tenantId, 'ot_type_id' => $parentId, 'surgery_name' => $surgeryName],
                    []
                );
            }
        }
    }

    private function syncSection24PermissionMatrix(array $roleIdsBySlug): void
    {
        $matrix = [
            'ot_receptionist' => [
                'ot.booking.*',
                'ot.counselling.fill',
                'ot.patient.list',
                'opd.patient.view',
                'opd.exam.history',
            ],
            'accountant' => [
                'ot.package.set',
                'ot.payment.*',
                'ot.invoice.*',
                'ot.billing.manage',
                'ot.discharge.*',
            ],
            'ot_doctor' => [
                'ot.surgery.*',
                'ot.meds.takehome',
                'ot.lens.record',
            ],
            'ot_assistant' => [
                'ot.lens.*',
                'ot.meds.takehome',
            ],
        ];

        $permissions = DB::table('permissions')->select('id', 'action')->get();
        $timestamp = now();

        foreach ($matrix as $roleSlug => $permissionPatterns) {
            $roleId = $roleIdsBySlug[$roleSlug] ?? null;

            if (! $roleId) {
                continue;
            }

            $permissionIds = $permissions
                ->filter(function ($permission) use ($permissionPatterns) {
                    foreach ($permissionPatterns as $pattern) {
                        if (Str::is($pattern, $permission->action)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->pluck('id')
                ->unique()
                ->values();

            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->update([
                    'is_granted' => false,
                    'updated_at' => $timestamp,
                ]);

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => (int) $permissionId],
                    [
                        'is_granted' => true,
                        'updated_by' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]
                );
            }
        }
    }
}
