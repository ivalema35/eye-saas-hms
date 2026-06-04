<?php

namespace App\Models\Hospital;

use App\Models\Hospital\MasterDiagnosis;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAdvice extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_advice';

    protected $fillable = ['value', 'diagnosis_id', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(MasterDiagnosis::class, 'diagnosis_id');
    }
}
