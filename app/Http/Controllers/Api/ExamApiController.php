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

        $exam = $this->examinationService->savePrimary(
            $patient,
            (int) $validated['doctor_id'],
            $validated['exam_data'] ?? [],
            $validated['medicines'] ?? [],
            $tenantId,
            ! empty($validated['dilation_time']) ? (int) $validated['dilation_time'] : null
        );

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

        $exam = $this->examinationService->saveSecondaryExam(
            $patient,
            (int) $validated['doctor_id'],
            $validated['exam_data'] ?? [],
            $validated['medicines'] ?? [],
            $tenantId,
            $validated['advice'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $exam,
            'message' => 'Secondary examination saved successfully.',
        ], 201);
    }
}
