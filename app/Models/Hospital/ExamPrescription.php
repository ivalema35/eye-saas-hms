<?php

/**
 * ExamPrescription.php
 *
 * PURPOSE: One medicine line in an OPD prescription.
 *          Linked to a primary_examination.
 *          Eye: RE / LE / Both / OU
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: exam_prescriptions
 */

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPrescription extends Model
{
    use BelongsToTenant;

    protected $table = 'exam_prescriptions';

    protected $fillable = [
        'tenant_id',
        'primary_examination_id',
        'medicine_id',
        'dosage_id',
        'frequency',
        'duration',
        'eye',
        'instructions',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function primaryExamination(): BelongsTo
    {
        return $this->belongsTo(PrimaryExamination::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function dosage(): BelongsTo
    {
        return $this->belongsTo(Dosage::class);
    }
}
