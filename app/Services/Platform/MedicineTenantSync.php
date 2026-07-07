<?php

namespace App\Services\Platform;

use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use App\Models\Platform\MasterMedicine;
use App\Models\Platform\Tenant;

/**
 * Pushes a Super Admin global Medicine down into every hospital's own
 * tenant-scoped `medicines` table, resolving the tenant's own MedicineType/
 * Dosage rows by name (these already exist per tenant via the Dosage/Type/
 * Category/Route cascade). Deletes are intentionally never cascaded — a
 * hospital may already have prescriptions referencing the medicine.
 */
class MedicineTenantSync
{
    public static function pushCreate(MasterMedicine $medicine): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            if (Medicine::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($medicine->name)])
                ->exists()
            ) {
                continue;
            }

            $typeId = static::resolveTenantTypeId($tenantId, $medicine);
            if (! $typeId) {
                continue; // medicine_type_id is NOT NULL on tenant medicines table
            }

            Medicine::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'medicine_type_id' => $typeId,
                'dosage_id' => static::resolveTenantDosageId($tenantId, $medicine),
                'name' => $medicine->name,
                'duration' => $medicine->duration,
                'qty' => $medicine->qty,
                'composition' => $medicine->composition,
                'company' => $medicine->company,
                'price' => $medicine->price,
            ]);
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
            if (! $typeId) {
                continue;
            }

            $existing->update([
                'medicine_type_id' => $typeId,
                'dosage_id' => static::resolveTenantDosageId($tenantId, $medicine),
                'name' => $medicine->name,
                'duration' => $medicine->duration,
                'qty' => $medicine->qty,
                'composition' => $medicine->composition,
                'company' => $medicine->company,
                'price' => $medicine->price,
            ]);
        }
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
