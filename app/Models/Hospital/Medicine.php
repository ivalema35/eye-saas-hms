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

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicines';

    protected $fillable = [
        'tenant_id',
        'medicine_type_id',
        'name',
        'dosage_id',
        'duration',
        'qty',
        'composition',
        'company',
        'price',
    ];

    public function medicineType(): BelongsTo
    {
        return $this->belongsTo(MedicineType::class);
    }

    public function dosage(): BelongsTo
    {
        return $this->belongsTo(Dosage::class);
    }
}
