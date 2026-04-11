<?php

/**
 * OtSurgery.php
 *
 * PURPOSE: OT Surgery outcome record model.
 *          Surgery kisi ne ki, kaunsi aankhh, kya hua — sab yahan.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_surgeries
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtSurgery extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_surgeries';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'operated_by',
        'surgery_name',
        'eye_operated',
        'complication_status',
        'complication_notes',
        'ward_medicines',
        'surgery_status',
        'complication',
        'surgery_at',
    ];

    protected $casts = [
        'ward_medicines' => 'array',
        'surgery_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function operatedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'operated_by');
    }
}
