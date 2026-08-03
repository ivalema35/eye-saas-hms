<?php

/**
 * OtConsent.php
 *
 * PURPOSE: Informed-consent record for an OT booking — patient/guardian digital
 *          signature, witness name, consent timestamp. Captured by the Counsellor
 *          before the booking can be sent to Billing.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_consents
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtConsent extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_consents';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'consent_given',
        'patient_signature_path',
        'guardian_signature_path',
        'witness_name',
        'consent_date',
        'created_by',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_date' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(HospitalUser::class, 'created_by');
    }
}
