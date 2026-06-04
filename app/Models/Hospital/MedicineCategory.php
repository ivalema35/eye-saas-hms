<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineCategory extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicine_categories';

    protected $fillable = [
        'tenant_id',
        'name',
    ];
}
