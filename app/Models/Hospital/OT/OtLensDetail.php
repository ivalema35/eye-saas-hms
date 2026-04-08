<?php

/**
 * OtLensDetail.php
 *
 * PURPOSE: IOL (Intraocular Lens) detail model for OT.
 *          Lens ka naam, power, MRP — invoice me use hoga.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_lens_details
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtLensDetail extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_lens_details';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'lens_name',
        'lens_type',
        'lens_power',
        'lens_mrp',
        'is_implanted',
        'entered_by',
    ];

    protected $casts = [
        'lens_power' => 'decimal:2',
        'lens_mrp' => 'decimal:2',
        'is_implanted' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(HospitalUser::class, 'entered_by');
    }
}
