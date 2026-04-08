<?php

/**
 * Dosage.php
 *
 * PURPOSE: Dosage instructions master â€” per-tenant.
 *          e.g. '1 Drop OD', '1 Drop OU', '1 Tab BD'
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: dosages
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Dosage extends Model
{
    use BelongsToTenant;

    protected $table = 'dosages';

    protected $fillable = [
        'tenant_id',
        'dosage',
    ];
}
