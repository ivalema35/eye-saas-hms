<?php

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtChargeHead extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'ot_charge_heads';

    protected $fillable = ['charge_name', 'percentage', 'is_active'];

    protected $casts = [
        'percentage' => 'float',
        'is_active'  => 'boolean',
    ];
}
