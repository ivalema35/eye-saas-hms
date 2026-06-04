<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hospital\MedicineRoute;

class MedicineGroupItem extends Model
{
    use BelongsToTenant;

    protected $table = 'medicine_group_items';

    protected $fillable = [
        'tenant_id',
        'medicine_group_id',
        'medicine_id',
        'dosage_id',
        'route_id',
        'frequency',
        'duration',
        'instructions',
        'quantity',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function dosage(): BelongsTo
    {
        return $this->belongsTo(Dosage::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(MedicineRoute::class, 'route_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MedicineGroup::class, 'medicine_group_id');
    }
}
