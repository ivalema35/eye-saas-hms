<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterMedicine extends Model
{
    protected $table = 'tbl_master_medicines';

    protected $fillable = [
        'master_medicine_type_id',
        'master_dosage_id',
        'name',
        'duration',
        'qty',
        'composition',
        'company',
        'price',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function medicineType(): BelongsTo
    {
        return $this->belongsTo(MasterMedicineType::class, 'master_medicine_type_id');
    }

    public function dosage(): BelongsTo
    {
        return $this->belongsTo(MasterDosage::class, 'master_dosage_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
