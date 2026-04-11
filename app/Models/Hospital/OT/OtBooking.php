<?php

/**
 * OtBooking.php
 *
 * PURPOSE: OT (Operation Theatre) Booking model.
 *          Ek patient ki surgery booking ki puri detail yahan.
 *          Status: booked → paid → in_ward → dilated → ready → operated → discharged
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_bookings
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtBooking extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_BOOKED = 'booked';

    public const STATUS_PAID = 'paid';

    public const STATUS_IN_WARD = 'in_ward';

    public const STATUS_DILATED = 'dilated';

    public const STATUS_READY = 'ready';

    public const STATUS_OPERATED = 'operated';

    public const STATUS_DISCHARGED = 'discharged';

    protected $table = 'ot_bookings';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'ot_doctor_id',
        'booked_by',
        'surgery_date',
        'slot_id',
        'eye',
        'ot_type',
        'reports_ok',
        'has_mediclaim',
        'lens_option',
        'package_amount',
        'payment_mode',
        'ot_status',
        'attended_at',
        'operated_at',
        'discharged_at',
    ];

    protected $casts = [
        'surgery_date' => 'date',
        'reports_ok' => 'boolean',
        'has_mediclaim' => 'boolean',
        'package_amount' => 'decimal:2',
        'attended_at' => 'datetime',
        'operated_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function otDoctor()
    {
        return $this->belongsTo(HospitalUser::class, 'ot_doctor_id');
    }

    public function bookedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'booked_by');
    }
}
