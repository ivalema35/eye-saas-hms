<?php

/**
 * PrimaryExamination.php
 *
 * PURPOSE: OPD Primary Eye Examination model.
 *          Exam data JSON format me store hota hai (flexible schema).
 *          Ek patient ka sirf ek primary exam ho sakta hai.
 *          BelongsToTenant: cross-hospital isolation.
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: primary_examinations
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrimaryExamination extends Model
{
    use BelongsToTenant;

    protected $table = 'primary_examinations';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'exam_data',
        'dilation_time',
        'examined_at',
    ];

    protected $casts = [
        'exam_data' => 'array',
        'examined_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(ExamPrescription::class)->orderBy('sort_order');
    }
}
