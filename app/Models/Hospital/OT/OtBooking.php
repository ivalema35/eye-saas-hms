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

    /** Eager loads needed by the ward list + ward view modal (hospital/ot/ward/_view-modal). */
    public const WARD_VIEW_RELATIONS = [
        'patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no,whatsapp_no,age,gender,type,doctor_id,reception_id',
        'patient.location:id,city,district,state',
        'patient.masterCity:id,name',
        'patient.doctor:id,name',
        'patient.reception:id,name',
        'counselling:id,ot_booking_id,diagnosis,mediclaim,lens_category,lens_company,lens_model,package_name,room_category',
        'preOp:id,ot_booking_id,bp,pulse,rbs,temperature,spo2,hba1c,pre_op_status',
        'dilationEntries:id,ot_booking_id,administered_at,medicine_name,eye,dose_number,drops_count,administered_by',
        'dilationEntries.administeredBy:id,name',
        'payments',
        'otDoctor:id,name',
        'otAssistant:id,name',
    ];

    /** Eager loads needed by the OT assistant list + view modal (hospital/ot/assistant/_view-modal). */
    public const OT_ASSISTANT_VIEW_RELATIONS = [
        'patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no,whatsapp_no,age,gender,type,doctor_id,reception_id',
        'patient.location:id,city,district,state',
        'patient.masterCity:id,name',
        'patient.doctor:id,name',
        'patient.reception:id,name',
        'counselling:id,ot_booking_id,diagnosis,mediclaim,lens_category,lens_company,lens_model,package_name,room_category',
        'preOp:id,ot_booking_id,bp,pulse,rbs,temperature,spo2,hba1c,pre_op_status',
        'surgery',
        'surgery.operatedBy:id,name',
        'lensDetail',
        'payments',
        'otDoctor:id,name',
        'otAssistant:id,name',
    ];

    /** Roles that own counselling for the patients they register. */
    public const COUNSELLOR_ROLE_SLUGS = ['receptionist', 'receptionist_opd'];

    /**
     * Documents handed to the patient at discharge. The booking moves to Discharged
     * only once every one of them has been printed (after the invoice exists).
     */
    public const DISCHARGE_PRINTS = [
        'summary_bill' => ['column' => 'summary_bill_printed_at', 'label' => 'Bill Summary', 'short' => 'Bill Summary', 'icon' => 'bi-receipt-cutoff', 'route' => 'hospital.ot.summary-bill.print', 'permission' => 'ot_bill_print'],
        'discharge' => ['column' => 'discharge_printed_at', 'label' => 'Discharge Summary', 'short' => 'Discharge', 'icon' => 'bi-file-medical', 'route' => 'hospital.ot.discharge.print', 'permission' => 'ot_discharge_generate'],
        'certificate' => ['column' => 'certificate_printed_at', 'label' => 'Surgery Certificate', 'short' => 'Certificate', 'icon' => 'bi-patch-check', 'route' => 'hospital.ot.certificate.print', 'permission' => 'ot_certificate_print'],
    ];

    /** Eager loads needed by the discharge desk list + view modal (hospital/ot/discharge/_view-modal). */
    public const DISCHARGE_VIEW_RELATIONS = [
        'patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no,whatsapp_no,age,gender,type,doctor_id,reception_id',
        'patient.location:id,city,district,state',
        'patient.masterCity:id,name',
        'patient.doctor:id,name',
        'patient.reception:id,name',
        'counselling:id,ot_booking_id,diagnosis,mediclaim,lens_category,lens_company,lens_model,package_name,room_category',
        'surgery',
        'surgery.operatedBy:id,name',
        'lensDetail',
        'invoice',
        'invoice.generatedBy:id,name',
        'payments',
        'otDoctor:id,name',
        'otAssistant:id,name',
    ];

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
        'summary_bill_printed_at',
        'discharge_printed_at',
        'certificate_printed_at',
    ];

    protected $casts = [
        'surgery_date' => 'date',
        'reports_ok' => 'boolean',
        'has_mediclaim' => 'boolean',
        'package_amount' => 'decimal:2',
        'attended_at' => 'datetime',
        'operated_at' => 'datetime',
        'discharged_at' => 'datetime',
        'summary_bill_printed_at' => 'datetime',
        'discharge_printed_at' => 'datetime',
        'certificate_printed_at' => 'datetime',
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

    public function dilationEntries()
    {
        return $this->hasMany(OtDilationEntry::class, 'ot_booking_id');
    }

    public function surgery()
    {
        return $this->hasOne(OtSurgery::class, 'ot_booking_id')->latestOfMany();
    }

    public function lensDetail()
    {
        return $this->hasOne(OtLensDetail::class, 'ot_booking_id')->latestOfMany();
    }

    public function invoice()
    {
        return $this->hasOne(OtDischargeSummary::class, 'ot_booking_id');
    }

    public function dischargePrintsDoneCount(): int
    {
        return collect(self::DISCHARGE_PRINTS)->filter(fn (array $doc) => $this->{$doc['column']} !== null)->count();
    }

    public function allDischargePrintsDone(): bool
    {
        return $this->dischargePrintsDoneCount() === count(self::DISCHARGE_PRINTS);
    }

    /**
     * Record that a discharge document was printed (first print time is kept) and
     * discharge the patient once all documents are printed and the invoice exists.
     * Returns true when this call moved the booking to Discharged.
     */
    public function markDischargePrinted(string $document): bool
    {
        $column = self::DISCHARGE_PRINTS[$document]['column'] ?? null;
        if (! $column) {
            return false;
        }

        if ($this->{$column} === null) {
            $this->{$column} = now();
        }

        $discharged = false;
        if (strtolower((string) $this->ot_status) === self::STATUS_OPERATED
            && $this->allDischargePrintsDone()
            && $this->invoice()->exists()) {
            $this->ot_status = self::STATUS_DISCHARGED;
            $this->discharged_at = now();
            $discharged = true;
        }

        $this->save();

        return $discharged;
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
     * Counselling is owned by the receptionist who registered the patient (patients.reception_id).
     * Hospital admins see everything. Patients whose registering user is missing, inactive or
     * not a receptionist stay visible to every counsellor so no case is left unowned.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssignedToCounsellor($query, ?HospitalUser $user)
    {
        if (! $user || $user->role?->is_super || $user->role?->slug === 'hospital_admin') {
            return $query;
        }

        return $query->whereHas('patient', function ($q) use ($user) {
            $q->where(function ($w) use ($user) {
                $w->where('patients.reception_id', $user->id)
                    ->orWhereNull('patients.reception_id')
                    ->orWhereDoesntHave('reception', function ($r) {
                        $r->active()->whereHas('role', fn ($role) => $role->whereIn('slug', self::COUNSELLOR_ROLE_SLUGS));
                    });
            });
        });
    }

    public function isAssignedToCounsellor(?HospitalUser $user): bool
    {
        return static::query()->whereKey($this->getKey())->assignedToCounsellor($user)->exists();
    }

    /**
     * Query scope: ward-assigned doctor consult queue (OP card count / list).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDoctorConsultationPending($query)
    {
        return $query
            ->whereNotNull('ot_doctor_id')
            ->whereIn('ot_status', [
                self::STATUS_PAYMENT_VERIFIED,
                self::STATUS_IN_WARD,
                self::STATUS_DILATED,
            ])
            ->whereHas('preOp', function ($q) {
                $q->whereIn('pre_op_status', [
                    OtPreOp::STATUS_PREPARING,
                    OtPreOp::STATUS_HOLD,
                    OtPreOp::STATUS_COMPLICATED,
                    OtPreOp::STATUS_NOT_FIT,
                ]);
            });
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
