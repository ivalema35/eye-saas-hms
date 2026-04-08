<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAdvice extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_advice';

    protected $fillable = ['value'];
}
