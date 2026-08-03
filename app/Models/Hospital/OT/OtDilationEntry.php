<?php

/**
 * OtDilationEntry.php
 *
 * PURPOSE: Ward "Eye Drop Register" — one row per drop administered, with dose
 *          number, eye, and who administered it. See docs/OT_WORKFLOW_UPGRADE_PRD.md §3.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_dilation_entries
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtDilationEntry extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_dilation_entries';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'administered_at',
        'medicine_name',
        'eye',
        'dose_number',
        'drops_count',
        'is_instilled',
        'administered_by',
        'remarks',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'is_instilled' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function administeredBy()
    {
        return $this->belongsTo(HospitalUser::class, 'administered_by');
    }
}
