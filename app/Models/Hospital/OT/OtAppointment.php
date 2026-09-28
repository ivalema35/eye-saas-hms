<?php

/**
 * OtAppointment.php
 *
 * PURPOSE: Pre-registration appointment record — captured before the patient
 *          physically arrives at the hospital (phone/walk-in/online/OT).
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

    public const TYPE_OT = 'ot';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    /** Every stage `resolveStage()` can return, in workflow order (key => label). */
    public const STAGES = [
        'booked' => 'Booked',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked-In (OPD)',
        'booking_created' => 'Booking Created',
        'surgery_recommended' => 'Surgery Recommended',
        'counselled' => 'In Counselling',
        'billing' => 'In Accountant / Billing',
        'payment_verified' => 'Payment Verified',
        'in_ward' => 'In Ward',
        'dilated' => 'In Ward (Dilated)',
        'ready' => 'Ready for OT',
        'operated' => 'In Surgery / Operated',
        'discharged' => 'Discharged',
        'surgery_refused' => 'Surgery Refused',
        'ot_cancelled' => 'OT Booking Cancelled',
        'cancelled' => 'Cancelled',
    ];

    protected $table = 'ot_appointments';

    protected $fillable = [
        'tenant_id',
        'appointment_seq',
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
        'appointment_seq' => 'integer',
    ];

    /** Human-friendly appointment number shown to reception/patient, e.g. APT-000123. */
    public function getAppointmentNumberAttribute(): string
    {
        $seq = (int) ($this->appointment_seq ?: $this->id);

        return 'APT-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Next APT sequence for a hospital (starts at 1). Includes soft-deleted rows
     * so cancelled/deleted appointments do not recycle numbers.
     */
    public static function peekNextSequenceForTenant(int $tenantId): int
    {
        return (int) static::withoutTenantScope()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->max('appointment_seq') + 1;
    }

    /**
     * Allocate and return the next sequence under a row lock (call inside a transaction).
     */
    public static function allocateNextSequenceForTenant(int $tenantId): int
    {
        static::withoutTenantScope()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('appointment_seq')
            ->lockForUpdate()
            ->first();

        return static::peekNextSequenceForTenant($tenantId);
    }

    public static function formatAppointmentNumber(int $seq): string
    {
        return 'APT-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
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

    public function getStageKeyAttribute(): string
    {
        return $this->resolveStage()['key'];
    }

    public function canWalkIn(): bool
    {
        return in_array($this->status, [self::STATUS_BOOKED, self::STATUS_CONFIRMED], true)
            && !$this->converted_patient_id;
    }

    /** @return array{key:string,label:string,class:string} */
    private function resolveStage(): array
    {
        $stage = fn(string $key, string $class): array => ['key' => $key, 'label' => self::STAGES[$key], 'class' => $class];

        if ($this->status === self::STATUS_CANCELLED) {
            return $stage('cancelled', 'ot-stage-cancelled');
        }

        if (!$this->converted_patient_id) {
            return $this->status === self::STATUS_CONFIRMED
                ? $stage('confirmed', 'ot-stage-confirmed')
                : $stage('booked', 'ot-stage-booked');
        }

        $booking = $this->convertedPatient?->latestOtBooking;

        if (!$booking) {
            return $stage('checked_in', 'ot-stage-checkedin');
        }

        return match ($booking->ot_status) {
            OtBooking::STATUS_SURGERY_RECOMMENDED => $stage('surgery_recommended', 'ot-stage-recommended'),
            OtBooking::STATUS_COUNSELLED => $stage('counselled', 'ot-stage-counselled'),
            OtBooking::STATUS_PAID => $stage('billing', 'ot-stage-billing'),
            OtBooking::STATUS_PAYMENT_VERIFIED => $stage('payment_verified', 'ot-stage-billing'),
            OtBooking::STATUS_IN_WARD => $stage('in_ward', 'ot-stage-ward'),
            OtBooking::STATUS_DILATED => $stage('dilated', 'ot-stage-ward'),
            OtBooking::STATUS_READY => $stage('ready', 'ot-stage-ready'),
            OtBooking::STATUS_OPERATED => $stage('operated', 'ot-stage-operated'),
            OtBooking::STATUS_DISCHARGED => $stage('discharged', 'ot-stage-discharged'),
            OtBooking::STATUS_SURGERY_REFUSED => $stage('surgery_refused', 'ot-stage-cancelled'),
            OtBooking::STATUS_CANCELLED => $stage('ot_cancelled', 'ot-stage-cancelled'),
            default => $stage('booking_created', 'ot-stage-booked'),
        };
    }
}
