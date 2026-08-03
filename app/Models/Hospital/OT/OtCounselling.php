<?php

/**
 * OtCounselling.php
 *
 * PURPOSE: OT Counsellor screen data — diagnosis confirmation, lens selection,
 *          package cost breakdown, mediclaim/blood-report checks, cost estimate.
 *          Filled by the Counsellor after the doctor recommends surgery, before Billing.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_counselling
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtCounselling extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_counselling';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'diagnosis',
        'surgery_type_confirmed',
        'mediclaim',
        'lens_option',
        'lens_category',
        'lens_company',
        'lens_model',
        'lens_type',
        'estimated_power',
        'lens_cost',
        'package_amount',
        'package_name',
        'room_category',
        'ot_charges',
        'surgeon_charges',
        'nursing_charges',
        'consumables_charges',
        'total_estimate',
        'payment_mode',
        'report_ok',
        'blood_reports_verified',
        'blood_reports_normal',
        'notes',
        'created_by',
        'counselled_by',
        'counselled_at',
    ];

    protected $casts = [
        'surgery_type_confirmed' => 'boolean',
        'mediclaim' => 'boolean',
        'report_ok' => 'boolean',
        'blood_reports_verified' => 'boolean',
        'blood_reports_normal' => 'boolean',
        'estimated_power' => 'decimal:2',
        'lens_cost' => 'decimal:2',
        'package_amount' => 'decimal:2',
        'ot_charges' => 'decimal:2',
        'surgeon_charges' => 'decimal:2',
        'nursing_charges' => 'decimal:2',
        'consumables_charges' => 'decimal:2',
        'total_estimate' => 'decimal:2',
        'counselled_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function counsellor()
    {
        return $this->belongsTo(HospitalUser::class, 'counselled_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(HospitalUser::class, 'created_by');
    }
}
