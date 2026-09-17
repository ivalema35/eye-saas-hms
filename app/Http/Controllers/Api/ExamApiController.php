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
        $previousDoneAt = $patient->primary_done_at;

        $exam = $this->examinationService->savePrimary(
            $patient,
            (int) $validated['doctor_id'],
            $validated['exam_data'] ?? [],
            $validated['medicines'] ?? [],
            $tenantId,
            ! empty($validated['dilation_time']) ? (int) $validated['dilation_time'] : null
        );

        if (! $isFinal) {
            $patient->update(['primary_done_at' => $previousDoneAt]);
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
        $previousDoneAt = $patient->secondary_done_at;

        $exam = $this->examinationService->saveSecondaryExam(
            $patient,
            (int) $validated['doctor_id'],
            $examData,
            $validated['medicines'] ?? [],
            $tenantId,
            $advice
        );

        if (! $isFinal) {
            $patient->update(['secondary_done_at' => $previousDoneAt]);
        }

        return response()->json([
            'success' => true,
            'data' => $exam,
            'message' => 'Secondary examination saved successfully.',
        ], 201);
    }
}
