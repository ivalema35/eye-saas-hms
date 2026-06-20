<?php

/**
 * Patient.php
 *
 * PURPOSE: OPD Patient model.
 *          Har patient record ek specific hospital ka hoga.
 *          MRD number auto-generate hota hai (e.g., AEH0001).
 *          BelongsToTenant: Hospital A ka patient Hospital B ko dikh nahi sakta.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: patients
 */

namespace App\Models\Hospital;

use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Models\Platform\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'patients';

    protected $fillable = [
        'tenant_id',
        'patient_code',
        'doctor_patient_no',
        'first_name',
        'middle_name',
        'last_name',
        'age',
        'gender',
        'occupation',
        'contact_no',
        'whatsapp_no',
        'location_id',
        'appointment_date',
        'slot_id',
        'doctor_id',
        'case_id',
        'case_fee',
        'reception_id',
        'referrer_id',
        'type',
        'checked_in_at',
        'is_old_patient',
        'primary_done_at',
        'secondary_done_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'checked_in_at' => 'datetime',
        'primary_done_at' => 'datetime',
        'secondary_done_at' => 'datetime',
        'is_old_patient' => 'boolean',
        'case_fee' => 'decimal:2',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'reception_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function masterCity(): BelongsTo
    {
        return $this->belongsTo(MasterCity::class, 'location_id');
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class, 'case_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class, 'referrer_id');
    }

    public function primaryExamination()
    {
        return $this->hasOne(PrimaryExamination::class);
    }

    public function secondaryExamination()
    {
        return $this->hasOne(SecondaryExamination::class);
    }

    /** Full name helper */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function otBookings()
    {
        return $this->hasMany(OtBooking::class, 'patient_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
