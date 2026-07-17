<?php

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtType extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'ot_types';

    protected $fillable = ['name'];
}
