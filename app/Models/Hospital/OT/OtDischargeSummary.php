<?php

/**
 * OtDischargeSummary.php
 *
 * PURPOSE: OT Discharge summary model.
 *          Patient discharge ke waqt generate hone wala document.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_invoices (shared with invoice data)
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtDischargeSummary extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_invoices';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'line_items',
        'total_amount',
        'is_finalized',
        'generated_by',
    ];

    protected $casts = [
        'line_items' => 'array',
        'total_amount' => 'decimal:2',
        'is_finalized' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'generated_by');
    }
}
