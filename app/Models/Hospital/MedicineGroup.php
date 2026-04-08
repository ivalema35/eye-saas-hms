<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineGroup extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicine_groups';

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MedicineGroupItem::class);
    }
}
