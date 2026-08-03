<?php

/**
 * OtSurgeryMedicine.php
 *
 * PURPOSE: Medicines administered / prescribed during OT surgery (pivot rows).
 *          Replaces free-form JSON on ot_surgeries.ward_medicines for new writes.
 *
 * TENANT-SCOPED: YES (BelongsToTenant)
 * TABLE: ot_surgery_medicines
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\Medicine;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OtSurgeryMedicine extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_surgery_medicines';

    protected $fillable = [
        'tenant_id',
        'ot_surgery_id',
        'medicine_id',
        'medicine_name',
        'quantity',
        'dose',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function surgery()
    {
        return $this->belongsTo(OtSurgery::class, 'ot_surgery_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    /** Shape expected by discharge / Rx print blades. */
    public function toPrintArray(): array
    {
        return [
            'medicine' => $this->medicine_name
                ?: ($this->medicine?->brand_name ?: $this->medicine?->name)
                ?: 'Medicine',
            'dose' => $this->dose,
            'quantity' => $this->quantity,
        ];
    }
}
