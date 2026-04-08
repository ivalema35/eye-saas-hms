<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseType extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_cases';

    protected $fillable = ['tenant_id', 'case_type', 'case_fee'];
}
