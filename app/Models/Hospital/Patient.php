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

use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Platform\MasterCity;
use App\Models\Platform\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    protected static function booted(): void
    {
        // Moving a record to a new visit date (phone check-in, edit, API) must not carry
        // the previous visit's exam stamps — otherwise the patient silently skips the queue.
        static::saving(function (Patient $patient): void {
            if (! $patient->isDirty('appointment_date') || ! $patient->appointment_date) {
                return;
            }

            $visitStart = $patient->appointment_date->copy()->startOfDay();

            if ($patient->primary_done_at && $patient->primary_done_at->lt($visitStart)) {
                $patient->primary_done_at = null;
                $patient->secondary_done_at = null;
            } elseif ($patient->secondary_done_at && $patient->secondary_done_at->lt($visitStart)) {
                $patient->secondary_done_at = null;
            }
        });
    }

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

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class, 'slot_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class, 'referrer_id');
    }

    /**
     * The OT Appointment (pre-registration) this patient was converted from, if any.
     * Only walk-in registrations (`type = 'walkin'`) can originate this way — see
     * PatientController::store(), which sets `ot_appointments.converted_patient_id`.
     */
    public function otAppointmentSource(): HasOne
    {
        return $this->hasOne(OtAppointment::class, 'converted_patient_id');
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

    /** City name — prefers MasterCity over old Location */
    public function getCityNameAttribute(): string
    {
        return $this->masterCity?->name ?? $this->location?->city ?? '';
    }

    /** District name — prefers MasterCity over old Location */
    public function getDistrictNameAttribute(): string
    {
        return $this->masterCity?->district?->name ?? $this->location?->district ?? '';
    }

    /** State name — prefers MasterCity over old Location */
    public function getStateNameAttribute(): string
    {
        return $this->masterCity?->state?->name ?? $this->location?->state ?? '';
    }

    /** Full location label: "City, District, State" */
    public function getLocationLabelAttribute(): string
    {
        $parts = array_filter([
            $this->getCityNameAttribute(),
            $this->getDistrictNameAttribute(),
            $this->getStateNameAttribute(),
        ]);

        return $parts ? implode(', ', $parts) : 'N/A';
    }

    public function otBookings()
    {
        return $this->hasMany(OtBooking::class, 'patient_id');
    }

    /**
     * Most recent OT booking for this patient — used to show the patient's current
     * OT workflow stage (in accountant/billing, in ward, operated, etc.) instead of
     * a flat "completed" status. Uses "latest of many" so it stays eager-load safe.
     */
    public function latestOtBooking(): HasOne
    {
        return $this->hasOne(OtBooking::class, 'patient_id')->ofMany('id', 'max');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Where this visit currently is in the OPD → OT workflow.
     * Eager-load `latestOtBooking` to stay N+1 safe.
     *
     * @return array{label:string, sub:?string, tone:string, icon:string}
     */
    public function workflowStage(): array
    {
        $booking = $this->latestOtBooking;

        // A closed booking from an earlier visit must not label this visit.
        if ($booking) {
            $closed = in_array($booking->ot_status, [OtBooking::STATUS_DISCHARGED, OtBooking::STATUS_SURGERY_REFUSED], true);
            $visitStart = $this->appointment_date?->copy()->startOfDay();
            if ($booking->ot_status === OtBooking::STATUS_CANCELLED
                || ($closed && $visitStart && $booking->created_at && $booking->created_at->lt($visitStart))) {
                $booking = null;
            }
        }

        if ($booking) {
            return match ($booking->ot_status) {
                OtBooking::STATUS_BOOKED,
                OtBooking::STATUS_SURGERY_RECOMMENDED => ['label' => 'Counselling', 'sub' => 'Surgery recommended', 'tone' => 'warning', 'icon' => 'bi-chat-left-heart'],
                OtBooking::STATUS_COUNSELLED => ['label' => 'Account', 'sub' => 'Payment pending', 'tone' => 'info', 'icon' => 'bi-cash-coin'],
                OtBooking::STATUS_PAID => ['label' => 'Account', 'sub' => 'Partially paid', 'tone' => 'info', 'icon' => 'bi-cash-coin'],
                OtBooking::STATUS_PAYMENT_VERIFIED => ['label' => 'Ward Management', 'sub' => 'Awaiting ward', 'tone' => 'purple', 'icon' => 'bi-heart-pulse'],
                OtBooking::STATUS_IN_WARD => ['label' => 'Ward Management', 'sub' => 'In ward', 'tone' => 'purple', 'icon' => 'bi-heart-pulse'],
                OtBooking::STATUS_DILATED => ['label' => 'Ward Management', 'sub' => 'Dilated', 'tone' => 'purple', 'icon' => 'bi-heart-pulse'],
                OtBooking::STATUS_READY => ['label' => 'OT Assistant', 'sub' => 'Ready for OT', 'tone' => 'teal', 'icon' => 'bi-hospital'],
                OtBooking::STATUS_OPERATED => ['label' => 'OT Done', 'sub' => 'Awaiting discharge', 'tone' => 'success', 'icon' => 'bi-check2-circle'],
                OtBooking::STATUS_DISCHARGED => ['label' => 'OT Done', 'sub' => 'Discharged', 'tone' => 'success', 'icon' => 'bi-check2-all'],
                OtBooking::STATUS_SURGERY_REFUSED => ['label' => 'Surgery Refused', 'sub' => null, 'tone' => 'danger', 'icon' => 'bi-x-circle'],
                default => ['label' => 'OT', 'sub' => str_replace('_', ' ', (string) $booking->ot_status), 'tone' => 'muted', 'icon' => 'bi-hospital'],
            };
        }

        if ($this->secondary_done_at) {
            return ['label' => 'Secondary Completed', 'sub' => null, 'tone' => 'success', 'icon' => 'bi-check2-circle'];
        }

        if ($this->primary_done_at) {
            return ['label' => 'Secondary Exam', 'sub' => 'Primary done · with doctor', 'tone' => 'primary', 'icon' => 'bi-eye'];
        }

        if (strtolower((string) $this->type) === 'phone' && ! $this->checked_in_at) {
            return ['label' => 'Not Checked-In', 'sub' => 'Phone booking', 'tone' => 'muted', 'icon' => 'bi-telephone'];
        }

        return ['label' => 'Primary Exam', 'sub' => 'Waiting', 'tone' => 'warning', 'icon' => 'bi-hourglass-split'];
    }
}
