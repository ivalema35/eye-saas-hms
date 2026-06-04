<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterDisc extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_disc';

    protected $fillable = ['value', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];
}
