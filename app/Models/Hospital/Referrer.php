<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referrer extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_referrers';

    protected $fillable = ['tenant_id', 'name', 'contact'];
}
