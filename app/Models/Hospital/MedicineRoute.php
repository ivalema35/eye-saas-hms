<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineRoute extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicine_routes';

    protected $fillable = [
        'tenant_id',
        'name',
    ];
}
