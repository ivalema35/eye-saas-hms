<?php

/**
 * OtRefund.php — OT money returned when patient refuses surgery.
 *
 * TENANT-SCOPED: YES (BelongsToTenant)
 * TABLE: ot_refunds
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtRefund extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_refunds';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'amount',
        'payment_mode',
        'receipt_number',
        'reason',
        'refunded_by',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function refundedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'refunded_by');
    }
}
