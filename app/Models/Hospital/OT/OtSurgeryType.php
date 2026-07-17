<?php

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtSurgeryType extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'ot_surgery_types';

    protected $fillable = ['ot_type_id', 'surgery_name'];

    public function otType(): BelongsTo
    {
        return $this->belongsTo(OtType::class, 'ot_type_id');
    }
}
