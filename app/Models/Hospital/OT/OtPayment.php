<?php

/**
 * OtPayment.php
 *
 * PURPOSE: OT Payment record model.
 *          Accountant ke dwara record ki gayi OT payment.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_payments
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtPayment extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_payments';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'package_amount',
        'has_mediclaim',
        'receipt_number',
        'payment_mode',
        'recorded_by',
        'paid_at',
    ];

    protected $casts = [
        'package_amount' => 'decimal:2',
        'has_mediclaim' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'recorded_by');
    }
}
