<?php

namespace App\Services\Hospital;

use App\Models\Hospital\ExamPrescription;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PatientPrescription;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Support\Facades\DB;

class ExaminationService
{
    /**
     * Save (create or update) a primary examination.
     *
     * Stores exam JSON data, syncs prescription lines, and stamps
     * patients.primary_done_at so the patient list shows the green tick.
     *
     * @param  array<string, mixed>  $examData  All exam_data sections (A–J)
     * @param  array<int, array>  $medicines  Prescription line items
     */
    public function savePrimary(
        Patient $patient,
        int $doctorId,
        array $examData,
        array $medicines,
        int $tenantId,
        ?int $dilationTime = null
    ): PrimaryExamination {
        return DB::transaction(function () use ($patient, $doctorId, $examData, $medicines, $tenantId, $dilationTime) {
            // Merge with existing exam_data so a per-step save never wipes other sections
            $existing = PrimaryExamination::where([
                'patient_id' => $patient->id,
                'tenant_id'  => $tenantId,
            ])->first();
            $mergedData = array_merge(
                is_array($existing?->exam_data) ? $existing->exam_data : [],
                $examData
            );

            $exam = PrimaryExamination::updateOrCreate(
                ['patient_id' => $patient->id, 'tenant_id' => $tenantId],
                [
                    'doctor_id'     => $doctorId,
                    'exam_data'     => $mergedData,
                    'dilation_time' => $dilationTime,
                    'examined_at'   => now(),
                ]
            );

            // Sync prescription lines — delete old, insert new
            ExamPrescription::where('primary_examination_id', $exam->id)->delete();

            foreach ($medicines as $i => $line) {
                if (empty($line['medicine_id'])) {
                    continue;
                }

                ExamPrescription::create([
                    'tenant_id' => $tenantId,
                    'primary_examination_id' => $exam->id,
                    'medicine_id' => (int) $line['medicine_id'],
                    'dosage_id' => ! empty($line['dosage_id']) ? (int) $line['dosage_id'] : null,
                    'frequency' => $line['frequency'] ?? null,
                    'duration' => $line['duration'] ?? null,
                    'quantity' => ! empty($line['quantity']) ? (int) $line['quantity'] : null,
                    'route_id' => ! empty($line['route_id']) ? (int) $line['route_id'] : null,
                    'eye' => $line['eye'] ?? 'Both',
                    'instructions' => $line['instructions'] ?? null,
                    'sort_order' => $i,
                ]);
            }

            // Stamp the patient so patient list shows primary done
            $patient->update(['primary_done_at' => now()]);

            return $exam;
        });
    }

    /**
     * Save (create or update) a secondary examination.
     *
     * Stores exam JSON and stamps patients.secondary_done_at.
     *
     * @param  array<string, mixed>  $examData  Secondary exam findings
     */
    public function saveSecondaryExam(
        Patient $patient,
        int $doctorId,
        array $examData,
        array $medicines,
        int $tenantId,
        ?string $advice = null
    ): SecondaryExamination {
        return DB::transaction(function () use ($patient, $doctorId, $examData, $medicines, $tenantId, $advice) {
            // Merge with existing exam_data so a per-step save never wipes other sections
            $existingSec = SecondaryExamination::where([
                'patient_id' => $patient->id,
                'tenant_id'  => $tenantId,
            ])->first();
            $mergedSecData = array_merge(
                is_array($existingSec?->exam_data) ? $existingSec->exam_data : [],
                $examData
            );

            $exam = SecondaryExamination::updateOrCreate(
                ['patient_id' => $patient->id, 'tenant_id' => $tenantId],
                [
                    'doctor_id'  => $doctorId,
                    'exam_data'  => $mergedSecData,
                    'advice'     => $advice,
                    'examined_at' => now(),
                ]
            );

            $rxData = [];
            foreach ($medicines as $line) {
                if (empty($line['name'])) {
                    continue;
                }

                $rxData[] = [
                    'medicine_id' => ! empty($line['medicine_id']) ? (int) $line['medicine_id'] : null,
                    'name' => $line['name'],
                    'dosage_id' => ! empty($line['dosage_id']) ? (int) $line['dosage_id'] : null,
                    'duration' => $line['duration'] ?? null,
                    'quantity' => ! empty($line['quantity']) ? (int) $line['quantity'] : null,
                    'route_id' => ! empty($line['route_id']) ? (int) $line['route_id'] : null,
                ];
            }

            if ($rxData !== []) {
                PatientPrescription::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'patient_id' => $patient->id,
                        'exam_type' => 'secondary',
                    ],
                    [
                        'doctor_id' => $doctorId,
                        'medicines' => $rxData,
                        'prescribed_at' => now(),
                    ]
                );

                $examData['rx'] = $rxData;
                $exam->update(['exam_data' => $examData]);
            }

            $patient->update(['secondary_done_at' => now()]);

            return $exam;
        });
    }

    /**
     * Backward-compatible alias for old call sites.
     *
     * @param  array<string, mixed>  $examData
     */
    public function saveSecondary(
        Patient $patient,
        int $doctorId,
        array $examData,
        array $medicines,
        int $tenantId
    ): SecondaryExamination {
        return $this->saveSecondaryExam($patient, $doctorId, $examData, $medicines, $tenantId);
    }
}
