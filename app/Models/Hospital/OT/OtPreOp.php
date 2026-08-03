<?php

/**
 * OtPreOp.php
 *
 * PURPOSE: Ward pre-op vitals — BP, Pulse, Blood Sugar (RBS), Temperature, SpO2,
 *          HbA1c (diabetic patients), and the nurse's readiness verdict.
 *          PDF statuses: Preparing / Ready for OT / Hold / Complicated.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_pre_op
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtPreOp extends Model
{
    use BelongsToTenant;

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY_FOR_SURGERY = 'ready_for_surgery';

    public const STATUS_HOLD = 'hold';

    public const STATUS_COMPLICATED = 'complicated';

    /** @deprecated Prefer STATUS_COMPLICATED — kept for legacy rows */
    public const STATUS_NOT_FIT = 'not_fit';

    public const STATUSES = [
        self::STATUS_PREPARING,
        self::STATUS_READY_FOR_SURGERY,
        self::STATUS_HOLD,
        self::STATUS_COMPLICATED,
        self::STATUS_NOT_FIT,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PREPARING => 'Preparing',
        self::STATUS_READY_FOR_SURGERY => 'Ready for OT',
        self::STATUS_HOLD => 'Hold',
        self::STATUS_COMPLICATED => 'Complicated',
        self::STATUS_NOT_FIT => 'Not Fit / Complicated',
    ];

    protected $table = 'ot_pre_op';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'bp',
        'pulse',
        'rbs',
        'temperature',
        'spo2',
        'hba1c',
        'pre_op_status',
        'entered_by',
    ];

    protected $casts = [
        'rbs' => 'decimal:1',
        'temperature' => 'decimal:1',
        'spo2' => 'decimal:2',
        'hba1c' => 'decimal:1',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(HospitalUser::class, 'entered_by');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->pre_op_status] ?? (string) $this->pre_op_status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->pre_op_status) {
            self::STATUS_READY_FOR_SURGERY => 'text-bg-success',
            self::STATUS_PREPARING => 'text-bg-info',
            self::STATUS_HOLD => 'text-bg-warning',
            self::STATUS_COMPLICATED, self::STATUS_NOT_FIT => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }
}
