<?php

/**
 * OtVerification.php
 *
 * PURPOSE: Pre-surgery verification checklist — identity, consent, payment,
 *          correct eye — recorded alongside the surgery entry so a surgery cannot
 *          be saved without all four being confirmed. See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_verifications
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtVerification extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_verifications';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'identity_verified',
        'consent_verified',
        'payment_verified',
        'correct_eye_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'identity_verified' => 'boolean',
        'consent_verified' => 'boolean',
        'payment_verified' => 'boolean',
        'correct_eye_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'verified_by');
    }

    public function allVerified(): bool
    {
        return $this->identity_verified && $this->consent_verified && $this->payment_verified && $this->correct_eye_verified;
    }
}
