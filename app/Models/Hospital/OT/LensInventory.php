<?php

/**
 * LensInventory.php
 *
 * PURPOSE: Real stock-tracked Lens Master — PDF §10 Inventory Module. Source of
 *          truth for physical lens units (batch/serial/expiry/stock), selected
 *          by the OT Assistant at implant time (Phase 4 lens form).
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §7.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: lens_inventory
 */

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LensInventory extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'lens_inventory';

    protected $fillable = [
        'tenant_id',
        'lens_code',
        'manufacturer',
        'lens_name',
        'type',
        'power',
        'batch_number',
        'serial_number',
        'mrp',
        'purchase_cost',
        'supplier',
        'expiry_date',
        'available_stock',
        'is_active',
    ];

    protected $casts = [
        'power' => 'decimal:2',
        'mrp' => 'decimal:2',
        'purchase_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'available_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function lensDetails()
    {
        return $this->hasMany(OtLensDetail::class, 'lens_inventory_id');
    }
}
