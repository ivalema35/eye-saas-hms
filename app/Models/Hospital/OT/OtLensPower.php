<?php

/**
 * OtLensPower.php
 *
 * PURPOSE: Lens Power master (quick-pick list with favourite flag) — PDF §10.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_lens_powers
 */

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtLensPower extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'ot_lens_powers';

    protected $fillable = [
        'tenant_id',
        'power',
        'is_favourite',
        'is_active',
    ];

    protected $casts = [
        'power' => 'decimal:2',
        'is_favourite' => 'boolean',
        'is_active' => 'boolean',
    ];
}
