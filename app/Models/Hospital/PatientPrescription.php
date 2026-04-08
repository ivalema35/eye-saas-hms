<?php

namespace App\Models\Hospital;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPrescription extends Model
{
    use BelongsToTenant;

    protected $table = 'patient_prescriptions';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'exam_type',
        'medicines',
        'prescribed_at',
    ];

    protected $casts = [
        'medicines' => 'array',
        'prescribed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalUser::class, 'doctor_id');
    }
}
