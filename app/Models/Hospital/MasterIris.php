<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterIris extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_master_iris';

    protected $fillable = ['value', 'is_favourite'];

    protected $casts = ['is_favourite' => 'boolean'];
}
