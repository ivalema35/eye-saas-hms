<?php

/**
 * OtSurgery.php
 *
 * PURPOSE: OT Surgery outcome record model.
 *          Surgery kisi ne ki, kaunsi aankhh, kya hua — sab yahan.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: ot_surgeries
 */

namespace App\Models\Hospital\OT;

use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\MedicineGroup;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtSurgery extends Model
{
    use BelongsToTenant;

    protected $table = 'ot_surgeries';

    protected $fillable = [
        'tenant_id',
        'ot_booking_id',
        'operated_by',
        'assistant_id',
        'surgery_name',
        'ot_room',
        'eye_operated',
        'complication_status',
        'complication_notes',
        'medicine_group_id',
        'surgery_status',
        'complication',
        'blood_loss',
        'surgery_at',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'surgery_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(OtBooking::class, 'ot_booking_id');
    }

    public function operatedBy()
    {
        return $this->belongsTo(HospitalUser::class, 'operated_by');
    }

    public function assistant()
    {
        return $this->belongsTo(HospitalUser::class, 'assistant_id');
    }

    public function medicineGroup()
    {
        return $this->belongsTo(MedicineGroup::class, 'medicine_group_id');
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(OtSurgeryMedicine::class, 'ot_surgery_id');
    }

    /**
     * Print-ready medicine rows from ot_surgery_medicines pivot.
     *
     * @return list<array{medicine: string, dose: mixed, quantity?: mixed}>
     */
    public function medicinesForPrint(): array
    {
        $pivot = $this->relationLoaded('medicines')
            ? $this->medicines
            : $this->medicines()->with('medicine')->get();

        if ($pivot->isEmpty()) {
            return [];
        }

        return $pivot->map(fn (OtSurgeryMedicine $row) => $row->toPrintArray())->values()->all();
    }

    /** Duration in minutes — computed from start/end, not stored (PDF: "Duration — Auto"). */
    public function getDurationMinutesAttribute(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        return (int) $this->start_time->diffInMinutes($this->end_time);
    }
}
