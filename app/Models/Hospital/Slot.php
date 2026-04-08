<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slot extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_slots';

    protected $fillable = ['tenant_id', 'slot_name', 'start_time', 'end_time'];
}
