<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorDashboardApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $today    = now()->toDateString();

        // ── Resolve target doctor (self or viewed) ───────────────────────────
        $targetDoctorId = $authUser->id;
        $viewingDoctor  = null;

        $viewDoctorId = (int) $request->query('view_doctor_id', 0);
        if ($viewDoctorId && $viewDoctorId !== $authUser->id) {
            $vd = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))
                ->find($viewDoctorId);
            if ($vd) {
                $viewingDoctor  = $vd;
                $targetDoctorId = $vd->id;
            }
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        $assignedToday = Patient::where('doctor_id', $targetDoctorId)
            ->whereDate('appointment_date', $today)->count();

        $primaryDone = Patient::where('doctor_id', $targetDoctorId)
            ->whereDate('appointment_date', $today)
            ->whereNotNull('primary_done_at')->whereNull('secondary_done_at')->count();

        $secondaryDone = Patient::where('doctor_id', $targetDoctorId)
            ->whereDate('appointment_date', $today)
            ->whereNotNull('secondary_done_at')->count();

        // ── Doctor cards (all OPD doctors, skip OT Doctor) ────────────────────
        $doctorCards = HospitalUser::with('role')
            ->whereHas('role', fn ($q) => $q->where('slug', 'doctor'))
            ->where('status', 'active')
            ->get()
            ->map(function ($doc) use ($today, $authUser) {
                $assigned  = Patient::where('doctor_id', $doc->id)->whereDate('appointment_date', $today)->count();
                $primary   = Patient::where('doctor_id', $doc->id)->whereDate('appointment_date', $today)
                    ->whereNotNull('primary_done_at')->whereNull('secondary_done_at')->count();
                $secondary = Patient::where('doctor_id', $doc->id)->whereDate('appointment_date', $today)
                    ->whereNotNull('secondary_done_at')->count();

                return [
                    'id'             => $doc->id,
                    'name'           => $doc->name,
                    'is_self'        => $doc->id === $authUser->id,
                    'assigned_today' => $assigned,
                    'primary_count'  => $primary,
                    'secondary_count'=> $secondary,
                ];
            })->values();

        // ── DR Index helper ────────────────────────────────────────────────────
        $buildIndex = function (Patient $p): string {
            if (! $p->doctor_patient_no) return '-';
            $prefix = $p->doctor?->doctor_prefix ?? '';
            $padded = str_pad($p->doctor_patient_no, 3, '0', STR_PAD_LEFT);
            return $prefix ? "{$prefix}-{$padded}" : "#{$padded}";
        };

        // ── Primary queue ──────────────────────────────────────────────────────
        $primaryPatients = Patient::with(['doctor:id,name,doctor_prefix', 'masterCity', 'location'])
            ->where('doctor_id', $targetDoctorId)
            ->whereDate('appointment_date', $today)
            ->whereNull('primary_done_at')
            ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
            ->orderBy('doctor_patient_no')
            ->take(20)
            ->get();

        // Batch has_history for primary queue
        $primaryContactHistory = $this->resolveContactHistory($primaryPatients->pluck('contact_no'));

        $primaryQueue = $primaryPatients->map(fn ($p) => [
            'id'           => $p->id,
            'patient_code' => $p->patient_code,
            'doctor_id'    => $p->doctor_id,
            'patient_name' => $p->full_name,
            'dr_index_no'  => $buildIndex($p),
            'age'          => $p->age,
            'city'         => $p->city_name,
            'registered_at'=> $p->created_at?->toISOString(),
            'has_history'  => isset($primaryContactHistory[$p->contact_no]),
        ])->values();

        // ── Secondary queue ────────────────────────────────────────────────────
        $dilationLockMinutes = (int) HospitalSetting::get('wait_d_green_max', 40);

        $secondaryPatients = Patient::with(['doctor:id,name,doctor_prefix', 'masterCity', 'location', 'primaryExamination'])
            ->where('doctor_id', $targetDoctorId)
            ->whereDate('appointment_date', $today)
            ->whereNotNull('primary_done_at')
            ->whereNull('secondary_done_at')
            ->orderBy('doctor_patient_no')
            ->take(20)
            ->get();

        $secondaryContactHistory = $this->resolveContactHistory($secondaryPatients->pluck('contact_no'));

        $secondaryQueue = $secondaryPatients->map(function ($p) use ($buildIndex, $dilationLockMinutes, $secondaryContactHistory) {
            $examData  = [];
            if ($p->primaryExamination) {
                $examData = is_array($p->primaryExamination->exam_data)
                    ? $p->primaryExamination->exam_data
                    : (json_decode($p->primaryExamination->exam_data ?? '{}', true) ?? []);
            }
            $isDilated = ($examData['dilate'] ?? '') === 'Yes';

            return [
                'id'                    => $p->id,
                'patient_code'          => $p->patient_code,
                'doctor_id'             => $p->doctor_id,
                'patient_name'          => $p->full_name,
                'dr_index_no'           => $buildIndex($p),
                'age'                   => $p->age,
                'city'                  => $p->city_name,
                'registered_at'         => $p->created_at?->toISOString(),
                'primary_done_at'       => $p->primary_done_at?->toISOString(),
                'is_dilated'            => $isDilated,
                'dilation_lock_minutes' => $dilationLockMinutes,
                'has_history'           => isset($secondaryContactHistory[$p->contact_no]),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'assigned_today' => $assignedToday,
                    'primary_done'   => $primaryDone,
                    'secondary_done' => $secondaryDone,
                ],
                'doctor_cards'   => $doctorCards,
                'viewing_doctor' => $viewingDoctor
                    ? ['id' => $viewingDoctor->id, 'name' => $viewingDoctor->name, 'is_self' => false]
                    : null,
                'primary_queue'  => $primaryQueue,
                'secondary_queue'=> $secondaryQueue,
            ],
        ]);
    }

    /**
     * Given a collection of contact numbers, returns an associative array
     * (contact_no => true) for contacts that have any exam history.
     */
    private function resolveContactHistory(\Illuminate\Support\Collection $contactNos): array
    {
        $contacts = $contactNos->filter()->unique()->values()->all();
        if (empty($contacts)) return [];

        $patientIds = Patient::whereIn('contact_no', $contacts)->pluck('id');
        if ($patientIds->isEmpty()) return [];

        $examinedIds = PrimaryExamination::whereIn('patient_id', $patientIds)->pluck('patient_id')
            ->merge(SecondaryExamination::whereIn('patient_id', $patientIds)->pluck('patient_id'))
            ->unique();

        if ($examinedIds->isEmpty()) return [];

        return Patient::whereIn('id', $examinedIds)
            ->whereIn('contact_no', $contacts)
            ->pluck('contact_no')
            ->unique()
            ->flip()
            ->toArray();
    }
}
