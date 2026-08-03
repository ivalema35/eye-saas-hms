<?php

namespace App\Models\Hospital;

use App\Models\Hospital\MasterDiagnosis;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineGroup extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'medicine_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'group_code',
        'diagnosis_id',
        'usage_scope',
    ];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(MasterDiagnosis::class, 'diagnosis_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicineGroupItem::class);
    }
}
