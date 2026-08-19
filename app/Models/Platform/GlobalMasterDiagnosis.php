<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalMasterDiagnosis extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_global_master_diagnosis';

    protected $fillable = [
        'value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}