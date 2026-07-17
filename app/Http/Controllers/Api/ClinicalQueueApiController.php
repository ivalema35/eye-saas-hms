<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicalQueueApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $filterDate = $request->input('date', Carbon::today()->toDateString());
        $selectedDoctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;

        // Auto-scope to own patients when the authenticated user is a doctor
        if ($selectedDoctorId === null) {
            $authUser = auth('sanctum')->user();
            if ($authUser && $authUser->doctor_type !== null) {
                $selectedDoctorId = $authUser->id;
            }
        }

        // ── Doctors ───────────────────────────────────────────────────────────
        $doctorModels = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($i) {
                            $i->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'doctor_type']);

        $doctors = $doctorModels->map(function ($doctor) use ($tenantId, $filterDate) {
            return [
                'id'               => $doctor->id,
                'name'             => $doctor->name,
                'doctor_type'      => $doctor->doctor_type,
                'total_assigned'   => Patient::where('tenant_id', $tenantId)
                    ->where('doctor_id', $doctor->id)
                    ->whereDate('appointment_date', $filterDate)
                    ->count(),
                'primary_pending'  => Patient::where('tenant_id', $tenantId)
                    ->where('doctor_id', $doctor->id)
                    ->whereDate('appointment_date', $filterDate)
                    ->whereNull('primary_done_at')
                    ->count(),
                'secondary_pending' => Patient::where('tenant_id', $tenantId)
                    ->where('doctor_id', $doctor->id)
                    ->whereDate('appointment_date', $filterDate)
                    ->whereNotNull('primary_done_at')
                    ->whereNull('secondary_done_at')
                    ->count(),
            ];
        })->values()->toArray();

        // ── Patient base query ────────────────────────────────────────────────
        $query = Patient::with(['doctor', 'caseType', 'primaryExamination'])
            ->where('tenant_id', $tenantId)
            ->whereDate('appointment_date', $filterDate);

        if ($selectedDoctorId) {
            $query->where('doctor_id', $selectedDoctorId);
        }

        $patients = $query->latest()->get();

        // ── Primary queue (no primary exam done) ──────────────────────────────
        $primaryQueue = $patients
            ->filter(fn ($p) => $p->primary_done_at === null)
            ->values()
            ->map(fn ($p) => $this->formatPatient($p))
            ->values();

        // ── Dilation queue (primary done, dilated, dilation NOT expired) ──────
        $dilationQueue = $patients
            ->filter(function ($p) {
                if ($p->primary_done_at === null || $p->secondary_done_at !== null) {
                    return false;
                }
                $exam = $p->primaryExamination;
                if (! $exam) {
                    return false;
                }
                if (($exam->exam_data['dilate'] ?? 'No') !== 'Yes' || ! $exam->dilation_time) {
                    return false;
                }

                return now()->lessThan(
                    $exam->updated_at->copy()->addMinutes($exam->dilation_time)
                );
            })
            ->values()
            ->map(fn ($p) => $this->formatPatient($p))
            ->values();

        // ── Secondary queue (primary done AND non-dilated OR dilation expired) ─
        $secondaryQueue = $patients
            ->filter(function ($p) {
                if ($p->primary_done_at === null || $p->secondary_done_at !== null) {
                    return false;
                }
                $exam = $p->primaryExamination;
                if (! $exam) {
                    return false;
                }
                if (($exam->exam_data['dilate'] ?? 'No') !== 'Yes' || ! $exam->dilation_time) {
                    return true;
                }

                return now()->greaterThanOrEqualTo(
                    $exam->updated_at->copy()->addMinutes($exam->dilation_time)
                );
            })
            ->values()
            ->map(fn ($p) => $this->formatPatient($p))
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'filter_date'        => $filterDate,
                'selected_doctor_id' => $selectedDoctorId,
                'wait_thresholds'    => [
                    'r'  => ['green' => 30, 'orange' => 60,  'red' => 120],
                    'd'  => ['green' => 40, 'orange' => 90,  'red' => 120],
                    'nd' => ['green' => 20, 'orange' => 60,  'red' => 120],
                ],
                'doctors'        => $doctors,
                'primary_queue'  => $primaryQueue,
                'dilation_queue' => $dilationQueue,
                'secondary_queue' => $secondaryQueue,
            ],
        ]);
    }

    private function formatPatient(Patient $p): array
    {
        $exam = $p->primaryExamination;

        $result = [
            'id'              => $p->id,
            'patient_code'    => $p->patient_code,
            'first_name'      => $p->first_name,
            'last_name'       => $p->last_name,
            'age'             => $p->age,
            'gender'          => $p->gender,
            'contact_no'      => $p->contact_no,
            'created_at'      => $p->created_at?->toISOString(),
            'primary_done_at' => $p->primary_done_at?->toISOString(),
            'doctor'          => $p->doctor
                ? ['id' => $p->doctor->id, 'name' => $p->doctor->name]
                : null,
            'case_type'       => $p->caseType
                ? ['case_type' => $p->caseType->case_type]
                : null,
        ];

        if ($exam) {
            $dilate = $exam->exam_data['dilate'] ?? 'No';
            $unlockTimeMs = null;

            if ($dilate === 'Yes' && $exam->dilation_time) {
                $unlockTimeMs = $exam->updated_at
                    ->copy()
                    ->addMinutes($exam->dilation_time)
                    ->timestamp * 1000;
            }

            $result['primary_examination'] = [
                'id'            => $exam->id,
                'examined_at'   => $exam->examined_at?->toISOString(),
                'dilation_time' => $exam->dilation_time,
                'exam_data'     => [
                    'dilate'    => $dilate,
                    'diagnosis' => $exam->exam_data['diagnosis'] ?? null,
                ],
            ];
            $result['unlock_time_ms'] = $unlockTimeMs;
        } else {
            $result['primary_examination'] = null;
            $result['unlock_time_ms']      = null;
        }

        return $result;
    }
}
