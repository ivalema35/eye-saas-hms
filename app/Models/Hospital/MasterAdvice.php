<?php

namespace App\Models\Hospital;

use App\Models\Hospital\MasterDiagnosis;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAdvice extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_advice';

    protected $fillable = ['value', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];

    public function diagnoses(): BelongsToMany
    {
        return $this->belongsToMany(MasterDiagnosis::class, 'tbl_advice_diagnoses', 'advice_id', 'diagnosis_id');
    }
}
