<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PatientHistoryApiController extends Controller
{
    public function history(string $slug, Patient $patient): JsonResponse
    {
        try {
            return $this->buildHistory($patient);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database unavailable. Please ensure the server is running.',
            ], 503);
        }
    }

    private function buildHistory(Patient $patient): JsonResponse
    {
        $tenantId = app('tenant')->id;

        // Pre-load dosage masters — used to resolve secondary exam rx dosage_id
        $dosageMasters = Dosage::all(['id', 'dosage'])->keyBy('id');

        // ── Primary exams ──────────────────────────────────────────────────────
        $primaryExams = PrimaryExamination::with([
            'doctor:id,name',
            'prescriptions.medicine',
            'prescriptions.dosage',
        ])
            ->where('patient_id', $patient->id)
            ->orderByDesc('examined_at')
            ->get()
            ->map(fn($exam) => [
                'id'          => $exam->id,
                'type'        => 'primary',
                'examined_at' => $exam->examined_at?->toISOString(),
                'doctor'      => $exam->doctor?->name,
                'exam_data'   => $exam->exam_data ?? [],
                'prescriptions' => $exam->prescriptions->map(fn($rx) => [
                    'medicine_name' => $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-'),
                    'dosage'        => $rx->dosage?->dosage ?? '-',
                    'duration'      => $rx->duration ? $rx->duration . ' D' : '-',
                    'eye'           => $rx->eye ?? '-',
                ])->values()->all(),
            ]);

        // ── Secondary exams ────────────────────────────────────────────────────
        // rx is stored inside exam_data['rx']; dosage_id resolved here so Flutter
        // always receives pre-resolved prescriptions for both exam types.
        $secondaryExams = SecondaryExamination::with('doctor:id,name')
            ->where('patient_id', $patient->id)
            ->orderByDesc('examined_at')
            ->get()
            ->map(function ($exam) use ($dosageMasters) {
                $prescriptions = collect($exam->exam_data['rx'] ?? [])
                    ->map(fn($rx) => [
                        'medicine_name' => $rx['name'] ?? '-',
                        'dosage'        => isset($rx['dosage_id'])
                            ? ($dosageMasters->get((int) $rx['dosage_id'])?->dosage ?? '-')
                            : '-',
                        'duration'      => !empty($rx['duration']) ? $rx['duration'] . ' D' : '-',
                        'eye'           => $rx['eye'] ?? '-',
                    ])
                    ->values()
                    ->all();

                return [
                    'id'            => $exam->id,
                    'type'          => 'secondary',
                    'examined_at'   => $exam->examined_at?->toISOString(),
                    'doctor'        => $exam->doctor?->name,
                    'exam_data'     => $exam->exam_data ?? [],
                    'prescriptions' => $prescriptions,
                ];
            });

        // Merge and sort newest-first
        $allExams = $primaryExams->concat($secondaryExams)
            ->sortByDesc('examined_at')
            ->values()
            ->all();

        // Visit-days = distinct calendar dates that have at least one exam
        $visitDays = collect($allExams)
            ->groupBy(fn($e) => $e['examined_at'] ? substr($e['examined_at'], 0, 10) : 'unknown')
            ->count();

        // Diagnosis masters for this tenant (id → value)
        $diagnosisMasters = DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get(['id', 'value'])
            ->pluck('value', 'id')
            ->all();

        // Patient profile
        $patient->load('location');

        return response()->json([
            'success' => true,
            'data'    => [
                'patient' => [
                    'id'           => $patient->id,
                    'name'         => trim(implode(' ', array_filter([
                        $patient->first_name,
                        $patient->middle_name,
                        $patient->last_name,
                    ]))),
                    'patient_code' => $patient->patient_code,
                    'gender'       => $patient->gender,
                    'age'          => $patient->age,
                    'contact_no'   => $patient->contact_no,
                    'location'     => $patient->location?->name
                        ?? $patient->location?->city
                        ?? 'N/A',
                    'created_at'   => $patient->created_at?->toISOString(),
                    'visit_days'   => $visitDays,
                ],
                'exams'             => $allExams,
                'diagnosis_masters' => $diagnosisMasters,
            ],
        ]);
    }
}
