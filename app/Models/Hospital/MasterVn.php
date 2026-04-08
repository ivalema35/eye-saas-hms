<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterVn extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_vn';

    protected $fillable = ['value'];
}
