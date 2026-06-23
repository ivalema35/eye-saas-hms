<?php

namespace App\Services\Hospital;

use App\Models\Hospital\Patient;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * Register a walk-in patient.
     * MRD + doctor serial assigned in one locked transaction.
     */
    public function registerWalkIn(array $data, int $tenantId): Patient
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id']        = $tenantId;
            $data['patient_code']     = $this->nextMrdLocked($tenantId);
            $data['doctor_patient_no'] = $this->nextDoctorSerialLocked(
                (int) $data['doctor_id'],
                $data['appointment_date'],
                $tenantId
            );
            $data['type'] = 'walkin';

            return Patient::create($data);
        });
    }

    /**
     * Register a phone appointment patient (no case assigned yet).
     * MRD + doctor serial assigned at registration (doctor & date known at this point).
     */
    public function registerPhone(array $data, int $tenantId): Patient
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id']        = $tenantId;
            $data['patient_code']     = $this->nextMrdLocked($tenantId);
            $data['doctor_patient_no'] = $this->nextDoctorSerialLocked(
                (int) $data['doctor_id'],
                $data['appointment_date'],
                $tenantId
            );
            $data['type'] = 'phone';

            return Patient::create($data);
        });
    }

    /**
     * Assign doctor serial to a phone patient at checkin time.
     * Called from PatientController::checkin() inside its own transaction.
     */
    public function assignDoctorSerial(Patient $patient, int $doctorId, string $appointmentDate, int $tenantId): void
    {
        if ($patient->doctor_patient_no !== null) {
            return; // already assigned — idempotent
        }

        $patient->doctor_patient_no = $this->nextDoctorSerialLocked($doctorId, $appointmentDate, $tenantId);
        $patient->save();
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

    /**
     * Next doctor-wise daily serial with FOR UPDATE lock.
     * Scoped per doctor + date + tenant.
     */
    private function nextDoctorSerialLocked(int $doctorId, string $appointmentDate, int $tenantId): int
    {
        $max = Patient::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $appointmentDate)
            ->lockForUpdate()
            ->max('doctor_patient_no');

        return ($max ?? 0) + 1;
    }

    private function prefix(int $tenantId): string
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (!empty($tenant->hospital_code)) {
            return strtoupper($tenant->hospital_code);
        }

        // Fallback for tenants created before hospital_code was introduced
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $tenant->slug), 0, 3));
        return strlen($prefix) < 3 ? str_pad($prefix, 3, 'X') : $prefix;
    }
}
