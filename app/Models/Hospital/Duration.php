<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Duration extends Model
{
    use BelongsToTenant;

    protected $table = 'tbl_durations';

    protected $fillable = ['tenant_id', 'duration'];
}
