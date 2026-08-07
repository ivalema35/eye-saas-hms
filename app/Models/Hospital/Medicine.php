<?php

/**
 * Medicine.php
 *
 * PURPOSE: Hospital medicine master â€” per-tenant medication catalog.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: medicines
 */

namespace App\Models\Hospital;

use App\Models\Hospital\MedicineGroupItem;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicines';

    protected $fillable = [
        'tenant_id',
        'medicine_type_id',
        'name',
        'usage_scope',
        'dosage_id',
        'duration',
        'qty',
        'composition',
        'company',
        'price',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Medicine $medicine) {
            $medicine->groupItems()->delete();
        });
    }

    public function groupItems(): HasMany
    {
        return $this->hasMany(MedicineGroupItem::class);
    }

    public function medicineType(): BelongsTo
    {
        return $this->belongsTo(MedicineType::class);
    }

    public function dosage(): BelongsTo
    {
        return $this->belongsTo(Dosage::class);
    }
}
