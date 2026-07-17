<?php

namespace App\Models\Hospital\OT;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtSlot extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'tbl_ot_slots';

    protected $fillable = ['slot_name', 'start_time', 'end_time'];
}
