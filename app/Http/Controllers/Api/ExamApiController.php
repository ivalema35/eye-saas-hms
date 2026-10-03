<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Examination\StorePrimaryExamRequest;
use App\Http\Requests\Hospital\Examination\StoreSecondaryExamRequest;
use App\Models\Hospital\Patient;
use App\Services\Hospital\ExaminationService;
use Illuminate\Http\JsonResponse;

class ExamApiController extends Controller
{
    public function __construct(protected ExaminationService $examinationService) {}

    /**
     * GET /api/v1/{slug}/exams/primary/{patientId}
     */
    public function showPrimary(string $slug, int $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $exam = $patient->primaryExamination()->with('prescriptions.medicine', 'prescriptions.dosage')->first();

        if (! $exam) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No primary examination found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $exam,
            'message' => 'Primary examination retrieved successfully.',
        ]);
    }

    /**
     * POST /api/v1/{slug}/exams/primary/{patientId}
     */
    public function savePrimary(StorePrimaryExamRequest $request, string $slug, int $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $tenantId = app('tenant')->id;
        $validated = $request->validated();
        // App-only: per-section progress autosaves pass is_final=false so an
        // accidental back-navigation mid-exam doesn't stamp the patient as
        // "Primary Done" everywhere. Web's own form only ever submits once
        // and never sends this field, so it keeps stamping every time —
        // untouched, since the shared service call below is unchanged.
        $isFinal = $request->boolean('is_final', true);

        $examData = $validated['exam_data'] ?? [];
        $medicines = $validated['medicines'] ?? [];

        // Only a final submit stamps primary_done_at — savePrimary() skips
        // the column entirely when $stampDone is false, instead of writing
        // it and reverting it afterwards. The old revert approach wrote
        // then reset primary_done_at in two separate steps, so a slow
        // autosave request could finish after a concurrent final save's
        // commit and silently erase "Primary Done" — exactly the bug
        // reported where Dilate=No + Done still left the patient looking
        // like primary was never completed.
        $exam = $this->examinationService->savePrimary(
            $patient,
            (int) $validated['doctor_id'],
            $examData,
            $medicines,
            $tenantId,
            ! empty($validated['dilation_time']) ? (int) $validated['dilation_time'] : null,
            $isFinal
        );

        if ($isFinal && ($examData['dilate'] ?? 'No') !== 'Yes' && $this->secondarySectionsFilled($examData, $medicines)) {
            // Same rule as the web primary page: no dilation, and Diagnosis,
            // Medicine or Advice was filled, so secondary is finished too.
            $this->examinationService->saveSecondaryExam(
                $patient,
                (int) $validated['doctor_id'],
                $examData,
                $medicines,
                $tenantId,
                is_string($examData['advice'] ?? null) ? $examData['advice'] : null
            );
        }

        return response()->json([
            'success' => true,
            'data' => $exam->load('prescriptions.medicine', 'prescriptions.dosage'),
            'message' => 'Primary examination saved successfully.',
        ], 201);
    }

    /**
     * GET /api/v1/{slug}/exams/secondary/{patientId}
     */
    public function showSecondary(string $slug, int $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        // Secondary exams have no `prescriptions` relationship — unlike
        // Primary, their medicines are stored inline in exam_data['rx']
        // via PatientPrescription (exam_type: 'secondary'), not a hasMany.
        $exam = $patient->secondaryExamination()->first();

        if (! $exam) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No secondary examination found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $exam,
            'message' => 'Secondary examination retrieved successfully.',
        ]);
    }

    /**
     * POST /api/v1/{slug}/exams/secondary/{patientId}
     */
    public function saveSecondary(StoreSecondaryExamRequest $request, string $slug, int $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $tenantId = app('tenant')->id;
        $validated = $request->validated();

        $examData = $validated['exam_data'] ?? [];
        // Mobile sends advice inside exam_data; fall back to that if no top-level field
        $advice = $validated['advice'] ?? ($examData['advice'] ?? null);

        // App-only: per-section progress autosaves pass is_final=false so an
        // accidental back-navigation mid-exam doesn't stamp the patient as
        // "Secondary Done" everywhere. Web's own form only ever submits once
        // and never sends this field, so it keeps stamping every time —
        // untouched, since the shared service call below is unchanged.
        $isFinal = $request->boolean('is_final', true);

        // See savePrimary() above — stamp only on a final submit.
        $exam = $this->examinationService->saveSecondaryExam(
            $patient,
            (int) $validated['doctor_id'],
            $examData,
            $validated['medicines'] ?? [],
            $tenantId,
            $advice,
            $isFinal
        );

        return response()->json([
            'success' => true,
            'data' => $exam,
            'message' => 'Secondary examination saved successfully.',
        ], 201);
    }

    /**
     * Diagnosis, medicine, or advice on the primary save means secondary is finished too.
     * Matches PrimaryExamController::secondarySectionsFilled.
     *
     * @param  array<string, mixed>  $examData
     * @param  array<int, array<string, mixed>>  $medicines
     */
    private function secondarySectionsFilled(array $examData, array $medicines): bool
    {
        $diagnoses = array_filter(
            $examData['diagnoses'] ?? [],
            fn ($value): bool => trim((string) $value) !== ''
        );
        if ($diagnoses !== []) {
            return true;
        }

        foreach (['advice', 'special_advice'] as $key) {
            if (trim((string) ($examData[$key] ?? '')) !== '') {
                return true;
            }
        }

        foreach ($medicines as $line) {
            if (trim((string) ($line['name'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
