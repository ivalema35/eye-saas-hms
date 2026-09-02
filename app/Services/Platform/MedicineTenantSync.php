<?php

namespace App\Services\Platform;

use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use App\Models\Platform\MasterMedicine;
use App\Models\Platform\Tenant;

/**
 * Pushes a Super Admin global Medicine down into hospital tenant-scoped
 * `medicines` tables, resolving each tenant's MedicineType/Dosage by name.
 * Deletes are intentionally never cascaded.
 */
class MedicineTenantSync
{
    public static function pushCreate(MasterMedicine $medicine): void
    {
        $tenantIds = Tenant::query()->pluck('id')->all();

        static::pushCreateForTenants($medicine, $tenantIds);
    }

    /**
     * @param  list<int>  $tenantIds
     */
    public static function pushCreateForTenants(MasterMedicine $medicine, array $tenantIds): void
    {
        foreach (array_unique(array_map('intval', $tenantIds)) as $tenantId) {
            if ($tenantId < 1) {
                continue;
            }

            if (Medicine::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($medicine->name)])
                ->exists()
            ) {
                continue;
            }

            $typeId = static::resolveTenantTypeId($tenantId, $medicine);
            $dosageId = static::resolveTenantDosageId($tenantId, $medicine);

            Medicine::withoutTenantScope()->create(static::tenantMedicinePayload(
                $tenantId,
                $medicine,
                $typeId,
                $dosageId,
            ));
        }
    }

    public static function pushUpdate(MasterMedicine $medicine, string $oldName): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = Medicine::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($oldName)])
                ->first();

            if (! $existing) {
                continue;
            }

            $conflict = Medicine::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($medicine->name)])
                ->where('id', '!=', $existing->id)
                ->exists();

            if ($conflict) {
                continue;
            }

            $typeId = static::resolveTenantTypeId($tenantId, $medicine);
            $dosageId = static::resolveTenantDosageId($tenantId, $medicine);

            $existing->update(static::tenantMedicinePayload(
                $tenantId,
                $medicine,
                $typeId,
                $dosageId,
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function tenantMedicinePayload(
        int $tenantId,
        MasterMedicine $medicine,
        ?int $typeId,
        ?int $dosageId,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'medicine_type_id' => $typeId,
            'dosage_id' => $dosageId,
            'name' => $medicine->name,
            'duration' => $medicine->duration,
            'qty' => $medicine->qty,
            'composition' => $medicine->composition,
            'company' => $medicine->company,
            'price' => $medicine->price,
        ];
    }

    private static function resolveTenantTypeId(int $tenantId, MasterMedicine $medicine): ?int
    {
        $typeName = $medicine->medicineType?->name;
        if (! $typeName) {
            return null;
        }

        return MedicineType::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) = ?', [strtolower($typeName)])
            ->value('id');
    }

    private static function resolveTenantDosageId(int $tenantId, MasterMedicine $medicine): ?int
    {
        $dosageValue = $medicine->dosage?->dosage;
        if (! $dosageValue) {
            return null;
        }

        return Dosage::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(dosage) = ?', [strtolower($dosageValue)])
            ->value('id');
    }
}
