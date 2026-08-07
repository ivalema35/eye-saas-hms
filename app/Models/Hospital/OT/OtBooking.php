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

    public const STATUS_SURGERY_RECOMMENDED = 'surgery_recommended';

    public const STATUS_COUNSELLED = 'counselled';

    public const STATUS_PAID = 'paid';

    public const STATUS_PAYMENT_VERIFIED = 'payment_verified';

    public const STATUS_IN_WARD = 'in_ward';

    public const STATUS_DILATED = 'dilated';

    public const STATUS_READY = 'ready';

    public const STATUS_OPERATED = 'operated';

    public const STATUS_DISCHARGED = 'discharged';

    /** Patient refused OT after ward consult — goes to Accounts for refund. */
    public const STATUS_SURGERY_REFUSED = 'surgery_refused';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'ot_bookings';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'ot_doctor_id',
        'ot_assistant_id',
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

    public function otAssistant()
    {
        return $this->belongsTo(HospitalUser::class, 'ot_assistant_id');
    }

    public function bookedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'booked_by');
    }

    public function counselling()
    {
        return $this->hasOne(OtCounselling::class, 'ot_booking_id');
    }

    public function consent()
    {
        return $this->hasOne(OtConsent::class, 'ot_booking_id');
    }

    public function payments()
    {
        return $this->hasMany(OtPayment::class, 'ot_booking_id');
    }

    public function refunds()
    {
        return $this->hasMany(OtRefund::class, 'ot_booking_id');
    }

    public function preOp()
    {
        return $this->hasOne(OtPreOp::class, 'ot_booking_id');
    }

    /**
     * Ward sent patient to OPD/OT doctor for consult (not Ready for OT yet).
     */
    public function isDoctorConsultationPending(): bool
    {
        if (! in_array($this->ot_status, [
            self::STATUS_PAYMENT_VERIFIED,
            self::STATUS_IN_WARD,
            self::STATUS_DILATED,
        ], true)) {
            return false;
        }

        if (empty($this->ot_doctor_id)) {
            return false;
        }

        $preOpStatus = $this->preOp?->pre_op_status;

        return in_array($preOpStatus, [
            OtPreOp::STATUS_PREPARING,
            OtPreOp::STATUS_HOLD,
            OtPreOp::STATUS_COMPLICATED,
            OtPreOp::STATUS_NOT_FIT,
        ], true);
    }

    /**
     * Computed payment status — Paid / Partially Paid / Pending (PDF §5, Billing Module).
     * Deliberately NOT a stored column: derived from `payments` sum vs. `package_amount`
     * so it can never drift out of sync. See docs/OT_WORKFLOW_UPGRADE_PRD.md §5.
     */
    public function getPaymentStatusAttribute(): string
    {
        $required = (float) ($this->package_amount ?? 0);

        // Zero / unset package is not billable yet (counsellor must set amount).
        if ($required <= 0) {
            return 'unpriced';
        }

        $paid = (float) $this->payments->sum('package_amount');

        if ($paid <= 0) {
            return 'pending';
        }

        return $paid >= $required ? 'paid' : 'partially_paid';
    }

    /** Amount still owed against the contracted package_amount. */
    public function getRemainingBalanceAttribute(): float
    {
        $required = (float) ($this->package_amount ?? 0);
        $paid = (float) $this->payments->sum('package_amount');

        return max(0, round($required - $paid, 2));
    }

    /** Total collected on this booking (payments). */
    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->payments->sum('package_amount'), 2);
    }

    /** Total refunded on this booking. */
    public function getTotalRefundedAttribute(): float
    {
        return round((float) $this->refunds->sum('amount'), 2);
    }

    /** Amount still returnable to patient (paid − refunded). Full-refund mode uses this whole amount. */
    public function getRefundableBalanceAttribute(): float
    {
        return max(0, round($this->total_paid - $this->total_refunded, 2));
    }

    public function isFullyRefunded(): bool
    {
        $paid = $this->total_paid;

        return $paid > 0 && $this->total_refunded + 0.001 >= $paid;
    }
}
