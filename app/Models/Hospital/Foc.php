<?php

/**
 * Foc.php
 *
 * PURPOSE: Free of Charge (FOC) record model.
 *          Doctor kisi patient ko case fee maaf kar sakta hai.
 *          Reception accept/reject karti hai.
 *          status: pending | accepted | rejected
 *          BelongsToTenant: hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: focs
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Foc extends Model
{
    use BelongsToTenant;

    protected $table = 'focs';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'reception_id',
        'foc_fee',
        'accepted',
        'accepted_by',
        'accepted_at',
        'status',
        'reason',
        'rejected_reason',
    ];

    protected $casts = [
        'foc_fee' => 'decimal:2',
        'accepted' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return ($this->status ?? 'pending') === 'pending';
    }

    public function isAccepted(): bool
    {
        return ($this->status ?? ($this->accepted ? 'accepted' : 'pending')) === 'accepted';
    }

    public function isRejected(): bool
    {
        return ($this->status ?? 'pending') === 'rejected';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'reception_id');
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'accepted_by');
    }
}
