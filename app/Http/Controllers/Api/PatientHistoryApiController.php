<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Models\Platform\HospitalShareRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PatientHistoryApiController extends Controller
{
    /**
     * Re-index list fields inside exam_data so they always JSON-encode as arrays [],
     * not objects {}.  When a doctor deletes a row in the exam form, PHP may store
     * co_rows as [1 => {...}, 2 => {...}] (non-sequential).  json_encode sees a
     * non-sequential array and emits {"1":{...},"2":{...}}, which Dart parses as a
     * Map — breaking Flutter's `as List?` cast and silently showing empty data.
     * array_values() re-indexes to 0,1,2… so json_encode emits a proper JSON array.
     * Also guards against null exam_data being sent as [] (a JSON array), which would
     * break Flutter's `j['exam_data'] as Map<String,dynamic>?` cast.
     */
    private function normalizeExamData(mixed $raw): array|\stdClass
    {
        if (! is_array($raw) || count($raw) === 0) {
            return new \stdClass(); // {} not [] — Flutter expects a Map, not a List
        }
        foreach (['co_rows', 'kco_rows', 'diagnoses', 'rx'] as $field) {
            if (isset($raw[$field]) && is_array($raw[$field])) {
                $raw[$field] = array_values($raw[$field]);
            }
        }
        return $raw;
    }

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

        // ── Collect all patient_ids for this person ────────────────────────────
        // A person can be registered multiple times (common when staff creates a
        // new record on each visit). We merge all registrations so the timeline
        // shows the complete clinical history, matching the web behaviour.
        $patientNameKey = mb_strtolower(trim($patient->first_name . ' ' . $patient->last_name));
        $allPatientIds  = [$patient->id];

        if ($patient->contact_no) {
            // Mirror the web list's filter: only consider other registrations that were
            // actually examined (primary_done_at or secondary_done_at set on the patients
            // row).  Without this, orphaned patients — those with exam records in the
            // secondary/primary_examinations table but whose patients.secondary_done_at is
            // still NULL — would be picked up and their exams incorrectly shown, while the
            // web search list never sees those patients and therefore never merges them.
            $related = Patient::where('contact_no', $patient->contact_no)
                ->where('id', '!=', $patient->id)
                ->where(function ($q) {
                    $q->whereNotNull('primary_done_at')->orWhereNotNull('secondary_done_at');
                })
                ->get(['id', 'first_name', 'last_name'])
                ->filter(fn($p) => mb_strtolower(trim($p->first_name . ' ' . $p->last_name)) === $patientNameKey)
                ->pluck('id')
                ->all();
            $allPatientIds = array_merge($allPatientIds, $related);
        }

        // ── Primary exams (all registrations) ─────────────────────────────────
        $primaryExams = PrimaryExamination::with([
            'doctor:id,name',
            'prescriptions.medicine',
            'prescriptions.dosage',
        ])
            ->whereIn('patient_id', $allPatientIds)
            ->get()
            ->map(fn($exam) => [
                'patient_id'  => $exam->patient_id,
                'id'          => $exam->id,
                'type'        => 'primary',
                'examined_at' => $exam->examined_at?->toISOString(),
                'doctor'      => $exam->doctor?->name,
                'exam_data'   => $this->normalizeExamData($exam->exam_data),
                'prescriptions' => $exam->prescriptions->map(fn($rx) => [
                    'medicine_name' => $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-'),
                    'dosage'        => $rx->dosage?->dosage ?? '-',
                    'duration'      => $rx->duration ? $rx->duration . ' D' : '-',
                    'eye'           => $rx->eye ?? '-',
                ])->values()->all(),
            ]);

        // ── Secondary exams (all registrations) ───────────────────────────────
        // rx is stored inside exam_data['rx']; dosage_id resolved here so Flutter
        // always receives pre-resolved prescriptions for both exam types.
        $secondaryExams = SecondaryExamination::with('doctor:id,name')
            ->whereIn('patient_id', $allPatientIds)
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
                    'patient_id'    => $exam->patient_id,
                    'id'            => $exam->id,
                    'type'          => 'secondary',
                    'examined_at'   => $exam->examined_at?->toISOString(),
                    'doctor'        => $exam->doctor?->name,
                    'exam_data'     => $this->normalizeExamData($exam->exam_data),
                    'prescriptions' => $prescriptions,
                ];
            });

        // One exam per visit (patient_id): secondary once done, primary until then.
        // Mirrors web PatientHistoryController::loadExamHistoryForIds().
        // ->values() must come AFTER ->sortByDesc() to guarantee sequential 0,1,2…
        // keys; otherwise json_encode() serialises it as an object {}, not an array [].
        $allExams = $secondaryExams->keyBy('patient_id')
            ->union($primaryExams->keyBy('patient_id'))
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

        // ── Partner hospital history ───────────────────────────────────────────
        $partnerHospitals = [];

        if ($patient->contact_no) {
            $currentTenant    = app('tenant');
            $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($currentTenant) {
                $q->where('from_tenant_id', $currentTenant->id)
                  ->orWhere('to_tenant_id',   $currentTenant->id);
            })->where('status', 'accepted')->get()
              ->map(fn($r) => $r->from_tenant_id === $currentTenant->id
                  ? $r->to_tenant_id
                  : $r->from_tenant_id)
              ->toArray();

            if (! empty($partnerTenantIds)) {
                $partnerPatients = Patient::withoutTenantScope()
                    ->whereIn('tenant_id', $partnerTenantIds)
                    ->where('contact_no', $patient->contact_no)
                    ->with(['tenant:id,name,city,state'])
                    ->get()
                    ->filter(fn($p) => mb_strtolower(trim($p->first_name . ' ' . $p->last_name)) === $patientNameKey);

                foreach ($partnerPatients->groupBy('tenant_id') as $partnerTenantId => $group) {
                    $pIds = $group->pluck('id')->all();

                    $pPrimary = PrimaryExamination::withoutGlobalScope('tenant')
                        ->with([
                            'doctor'              => fn($q) => $q->withoutGlobalScopes()->select(['id', 'name']),
                            'prescriptions.medicine' => fn($q) => $q->withoutGlobalScopes(),
                            'prescriptions.dosage',
                        ])
                        ->whereIn('patient_id', $pIds)
                        ->get()
                        ->map(fn($exam) => [
                            'patient_id'    => $exam->patient_id,
                            'id'            => $exam->id,
                            'type'          => 'primary',
                            'examined_at'   => $exam->examined_at?->toISOString(),
                            'doctor'        => $exam->doctor?->name,
                            'exam_data'     => $this->normalizeExamData($exam->exam_data),
                            'prescriptions' => $exam->prescriptions->map(fn($rx) => [
                                'medicine_name' => $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-'),
                                'dosage'        => $rx->dosage?->dosage ?? '-',
                                'duration'      => $rx->duration ? $rx->duration . ' D' : '-',
                                'eye'           => $rx->eye ?? '-',
                            ])->values()->all(),
                        ]);

                    $pSecondary = SecondaryExamination::withoutGlobalScope('tenant')
                        ->with(['doctor' => fn($q) => $q->withoutGlobalScopes()->select(['id', 'name'])])
                        ->whereIn('patient_id', $pIds)
                        ->get()
                        ->map(function ($exam) use ($dosageMasters) {
                            $prescriptions = collect($exam->exam_data['rx'] ?? [])
                                ->map(fn($rx) => [
                                    'medicine_name' => $rx['name'] ?? '-',
                                    'dosage'        => isset($rx['dosage_id'])
                                        ? ($dosageMasters->get((int) $rx['dosage_id'])?->dosage ?? '-')
                                        : '-',
                                    'duration'      => ! empty($rx['duration']) ? $rx['duration'] . ' D' : '-',
                                    'eye'           => $rx['eye'] ?? '-',
                                ])->values()->all();

                            return [
                                'patient_id'    => $exam->patient_id,
                                'id'            => $exam->id,
                                'type'          => 'secondary',
                                'examined_at'   => $exam->examined_at?->toISOString(),
                                'doctor'        => $exam->doctor?->name,
                                'exam_data'     => $this->normalizeExamData($exam->exam_data),
                                'prescriptions' => $prescriptions,
                            ];
                        });

                    // One exam per visit: secondary once done, primary until then.
                    $partnerExams = $pSecondary->keyBy('patient_id')
                        ->union($pPrimary->keyBy('patient_id'))
                        ->sortByDesc('examined_at')
                        ->values()
                        ->all();

                    if (empty($partnerExams)) continue;

                    $partnerDx = DB::table('tbl_master_diagnosis')
                        ->where('tenant_id', $partnerTenantId)
                        ->orderBy('id')
                        ->get(['id', 'value'])
                        ->pluck('value', 'id')
                        ->all();

                    $tenant = $group->first()->tenant;
                    $partnerHospitals[] = [
                        'hospital_name'     => $tenant?->name  ?? 'Partner Hospital',
                        'hospital_city'     => $tenant?->city  ?? null,
                        'hospital_state'    => $tenant?->state ?? null,
                        'exams'             => $partnerExams,
                        'diagnosis_masters' => $partnerDx,
                    ];
                }
            }
        }

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
                'partner_hospitals' => $partnerHospitals,
            ],
        ]);
    }
}
