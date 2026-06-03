<?php

namespace App\Services\Hospital;

use App\Models\Hospital\Patient;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * Register a walk-in patient.
     * MRD generation and insert happen in ONE transaction so the
     * lockForUpdate() is held until the row is committed — preventing
     * two concurrent receptionists from getting the same MRD.
     */
    public function registerWalkIn(array $data, int $tenantId): Patient
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id']    = $tenantId;
            $data['patient_code'] = $this->nextMrdLocked($tenantId);
            $data['type']         = 'walkin';

            return Patient::create($data);
        });
    }

    /**
     * Register a phone appointment patient.
     * Same single-transaction pattern as registerWalkIn.
     */
    public function registerPhone(array $data, int $tenantId): Patient
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id']    = $tenantId;
            $data['patient_code'] = $this->nextMrdLocked($tenantId);
            $data['type']         = 'phone';

            return Patient::create($data);
        });
    }

    /**
     * Preview the likely next MRD — NO lock, for display only.
     * The actual MRD is assigned at save time; this is an estimate.
     */
    public function peekNextMrd(int $tenantId): string
    {
        $prefix = $this->prefix($tenantId);

        $lastCode = Patient::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('patient_code', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(patient_code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('patient_code');

        $nextSeq = $lastCode ? ((int) substr($lastCode, strlen($prefix)) + 1) : 1;

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next MRD with a SELECT ... FOR UPDATE lock.
     * MUST be called inside an active DB::transaction() — the lock is
     * only released when that outer transaction commits/rolls back,
     * ensuring no other request can read the same "last code" until
     * the new patient row is persisted.
     */
    private function nextMrdLocked(int $tenantId): string
    {
        $prefix = $this->prefix($tenantId);

        $lastCode = Patient::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('patient_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(patient_code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->value('patient_code');

        $nextSeq = $lastCode ? ((int) substr($lastCode, strlen($prefix)) + 1) : 1;

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    private function prefix(int $tenantId): string
    {
        $tenant = Tenant::findOrFail($tenantId);
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $tenant->slug), 0, 3));

        return strlen($prefix) < 3 ? str_pad($prefix, 3, 'X') : $prefix;
    }
}
