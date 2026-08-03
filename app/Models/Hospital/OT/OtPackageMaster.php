<?php

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtPackageMaster extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const ROOM_GENERAL = 'general';

    public const ROOM_PRIVATE = 'private';

    protected $table = 'ot_package_masters';

    protected $fillable = [
        'tenant_id',
        'package_name',
        'lens_cost',
        'room_category',
        'ot_charges',
        'surgeon_charges',
        'nursing_charges',
        'consumables_charges',
        'is_active',
    ];

    protected $casts = [
        'lens_cost' => 'decimal:2',
        'ot_charges' => 'decimal:2',
        'surgeon_charges' => 'decimal:2',
        'nursing_charges' => 'decimal:2',
        'consumables_charges' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
