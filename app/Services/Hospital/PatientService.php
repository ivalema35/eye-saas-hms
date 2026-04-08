<?php

namespace App\Services\Hospital;

use App\Models\Hospital\Patient;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * Generate a unique MRD code for a patient.
     *
     * Format: {SLUG_PREFIX}{4_DIGIT_SEQ} e.g. AEH0001
     * Prefix = first 3 uppercase alpha chars from tenant slug.
     * Sequence is per-tenant, auto-incremented.
     */
    public function generateMrd(int $tenantId): string
    {
        $tenant = Tenant::findOrFail($tenantId);
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $tenant->slug), 0, 3));

        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        return DB::transaction(function () use ($tenantId, $prefix) {
            $lastCode = Patient::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('patient_code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(patient_code, 4) AS UNSIGNED) DESC')
                ->value('patient_code');

            $nextSeq = 1;
            if ($lastCode) {
                $numericPart = (int) substr($lastCode, strlen($prefix));
                $nextSeq = $numericPart + 1;
            }

            return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Register a walk-in patient.
     *
     * @param  array<string, mixed>  $data  Validated patient fields
     */
    public function registerWalkIn(array $data, int $tenantId): Patient
    {
        $data['tenant_id'] = $tenantId;
        $data['patient_code'] = $this->generateMrd($tenantId);
        $data['type'] = 'walkin';

        return Patient::create($data);
    }

    /**
     * Register a phone appointment patient.
     *
     * @param  array<string, mixed>  $data  Validated patient fields
     */
    public function registerPhone(array $data, int $tenantId): Patient
    {
        $data['tenant_id'] = $tenantId;
        $data['patient_code'] = $this->generateMrd($tenantId);
        $data['type'] = 'phone';

        return Patient::create($data);
    }
}
