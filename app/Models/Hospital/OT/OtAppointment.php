<?php

/**
 * OtAppointment.php
 *
 * PURPOSE: Pre-registration appointment record — captured before the patient
 *          physically arrives at the hospital (phone/walk-in/online/referral).
 *          Distinct from `Patient` (OPD registration, created at Reception check-in)
 *          and `OtBooking` (confirmed surgery slot, created by Counsellor/Receptionist).
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §2.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_appointments
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtAppointment extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const TYPE_PHONE = 'phone';

    public const TYPE_WALK_IN = 'walk_in';

    public const TYPE_ONLINE = 'online';

    public const TYPE_REFERRAL = 'referral';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'ot_appointments';

    protected $fillable = [
        'tenant_id',
        'appointment_type',
        'referrer_id',
        'appointment_date',
        'appointment_time',
        'doctor_id',
        'patient_name',
        'middle_name',
        'surname',
        'mobile_no',
        'whatsapp_no',
        'age',
        'gender',
        'occupation',
        'location_id',
        'status',
        'notes',
        'converted_patient_id',
        'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    /** Human-friendly appointment number shown to reception/patient, e.g. APT-000123. */
    public function getAppointmentNumberAttribute(): string
    {
        return 'APT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function doctor()
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }

    public function location()
    {
        return $this->belongsTo(MasterCity::class, 'location_id');
    }

    public function referrer()
    {
        return $this->belongsTo(Referrer::class, 'referrer_id');
    }

    public function convertedPatient()
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(HospitalUser::class, 'created_by');
    }

    /**
     * Where the patient actually stands in the OT process right now — Accountant,
     * Ward, Operated, etc. — instead of a blunt "Completed" once OPD check-in happens.
     * Controller eager-loads `convertedPatient.latestOtBooking` so this stays N+1 safe.
     */
    public function getStageLabelAttribute(): string
    {
        return $this->resolveStage()['label'];
    }

    public function getStageBadgeClassAttribute(): string
    {
        return $this->resolveStage()['class'];
    }

    private function resolveStage(): array
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return ['label' => 'Cancelled', 'class' => 'ot-stage-cancelled'];
        }

        if (! $this->converted_patient_id) {
            return $this->status === self::STATUS_CONFIRMED
                ? ['label' => 'Confirmed', 'class' => 'ot-stage-confirmed']
                : ['label' => 'Booked', 'class' => 'ot-stage-booked'];
        }

        $booking = $this->convertedPatient?->latestOtBooking;

        if (! $booking) {
            return ['label' => 'Checked-In (OPD)', 'class' => 'ot-stage-checkedin'];
        }

        return match ($booking->ot_status) {
            OtBooking::STATUS_SURGERY_RECOMMENDED => ['label' => 'Surgery Recommended', 'class' => 'ot-stage-recommended'],
            OtBooking::STATUS_COUNSELLED => ['label' => 'In Counselling', 'class' => 'ot-stage-counselled'],
            OtBooking::STATUS_PAID => ['label' => 'In Accountant / Billing', 'class' => 'ot-stage-billing'],
            OtBooking::STATUS_PAYMENT_VERIFIED => ['label' => 'Payment Verified', 'class' => 'ot-stage-billing'],
            OtBooking::STATUS_IN_WARD => ['label' => 'In Ward', 'class' => 'ot-stage-ward'],
            OtBooking::STATUS_DILATED => ['label' => 'In Ward (Dilated)', 'class' => 'ot-stage-ward'],
            OtBooking::STATUS_READY => ['label' => 'Ready for OT', 'class' => 'ot-stage-ready'],
            OtBooking::STATUS_OPERATED => ['label' => 'In Surgery / Operated', 'class' => 'ot-stage-operated'],
            OtBooking::STATUS_DISCHARGED => ['label' => 'Discharged', 'class' => 'ot-stage-discharged'],
            default => ['label' => 'Booking Created', 'class' => 'ot-stage-booked'],
        };
    }
}
