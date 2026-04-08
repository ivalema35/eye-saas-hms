<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterNrvn extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_nrvn';

    protected $fillable = ['value'];
}
