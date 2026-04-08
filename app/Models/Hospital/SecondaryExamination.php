<?php

/**
 * SecondaryExamination.php
 *
 * PURPOSE: OPD Secondary (Specialist) Eye Examination model.
 *          Primary exam ke baad secondary doctor ka exam.
 *          BelongsToTenant: cross-hospital data isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: secondary_examinations
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecondaryExamination extends Model
{
    use BelongsToTenant;

    protected $table = 'secondary_examinations';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'exam_data',
        'advice',
        'examined_at',
    ];

    protected $casts = [
        'exam_data' => 'array',
        'examined_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }
}
