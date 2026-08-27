<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorDashboardApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $today    = now()->toDateString();
        $isOtDoctor = $authUser?->role?->slug === 'ot_assistant';

        // ── Resolve target doctor (self or viewed) ───────────────────────────
        $targetDoctorId = $authUser->id;
        $viewingDoctor  = null;

        // OT doctors can only view their own dashboard — no cross-doctor switching
        if (! $isOtDoctor) {
            $viewDoctorId = (int) $request->query('view_doctor_id', 0);
            if ($viewDoctorId && $viewDoctorId !== $authUser->id) {
                $vd = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))
                    ->find($viewDoctorId);
                if ($vd) {
                    $viewingDoctor  = $vd;
                    $targetDoctorId = $vd->id;
                }
            }
        }

        // ── Stats ─────────────────────────────────────────────────────────────
        if ($isOtDoctor) {
            $assignedToday = OtBooking::where('ot_doctor_id', $targetDoctorId)
                ->whereDate('surgery_date', $today)->count();
            $primaryDone   = 0;
            $secondaryDone = 0;
        } else {
            $assignedToday = Patient::where('doctor_id', $targetDoctorId)
                ->whereDate('appointment_date', $today)->count();

            $primaryDone = Patient::where('doctor_id', $targetDoctorId)
                ->whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')->whereNull('secondary_done_at')->count();

            $secondaryDone = Patient::where('doctor_id', $targetDoctorId)
                ->whereDate('appointment_date', $today)
                ->whereNotNull('secondary_done_at')->count();
        }

        // ── Hospital-wide today/primary/secondary (shown alongside the
        // doctor's own stats — mirrors doctoredashboard.blade.php:756-760,
        // sourced from Hospital\DashboardController.php:89-98). Tenant-wide,
        // no doctor_id filter. See DASHBOARD_PARITY_FIX_PLAN.md Phase 3.
        $tenantTodayPatients  = Patient::whereDate('appointment_date', $today)->count();
        $tenantTodayPrimary   = Patient::whereDate('appointment_date', $today)
            ->whereNotNull('primary_done_at')->count();
        $tenantTodaySecondary = Patient::whereDate('appointment_date', $today)
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

        // ── OT doctor cards (same roster as OPD; counts from that doctor's OT
        // bookings — assigned ot_doctor_id, or recommender booked_by when a
        // doctor isn't assigned yet). Mirrors
        // Hospital\Dashboard\DashboardController's $otDoctorCards/$otSummary.
        // See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §6 / FIX_PLAN TASK 5.2.
        $completeOtStatuses = [OtBooking::STATUS_OPERATED, OtBooking::STATUS_DISCHARGED, OtBooking::STATUS_SURGERY_REFUSED];
        $doctorIdsForOt = $doctorCards->pluck('id')->all();

        $otStatsByDoctorId = collect();
        if ($doctorIdsForOt !== []) {
            $otStatsByDoctorId = OtBooking::query()
                ->selectRaw('COALESCE(ot_doctor_id, booked_by) as doctor_key')
                ->selectRaw('COUNT(*) as ot_total')
                ->selectRaw('SUM(CASE WHEN ot_status IN (?, ?, ?) THEN 1 ELSE 0 END) as ot_complete', $completeOtStatuses)
                ->selectRaw('SUM(CASE WHEN ot_status NOT IN (?, ?, ?) THEN 1 ELSE 0 END) as ot_pending', $completeOtStatuses)
                ->where(function ($q) use ($doctorIdsForOt) {
                    $q->whereIn('ot_doctor_id', $doctorIdsForOt)
                        ->orWhereIn('booked_by', $doctorIdsForOt);
                })
                ->groupBy(DB::raw('COALESCE(ot_doctor_id, booked_by)'))
                ->get()
                ->keyBy('doctor_key');
        }

        $otDoctorCards = $doctorCards->map(function ($doc) use ($otStatsByDoctorId) {
            $stats = $otStatsByDoctorId->get($doc['id']);

            return [
                'id' => $doc['id'],
                'name' => $doc['name'],
                'is_self' => $doc['is_self'],
                'ot_total' => (int) ($stats->ot_total ?? 0),
                'ot_pending' => (int) ($stats->ot_pending ?? 0),
                'ot_complete' => (int) ($stats->ot_complete ?? 0),
            ];
        })->values();

        $otSummary = [
            'total' => (int) $otDoctorCards->sum('ot_total'),
            'pending' => (int) $otDoctorCards->sum('ot_pending'),
            'complete' => (int) $otDoctorCards->sum('ot_complete'),
        ];

        // ── DR Index helper ────────────────────────────────────────────────────
        $buildIndex = function (Patient $p): string {
            if (! $p->doctor_patient_no) return '-';
            $prefix = $p->doctor?->doctor_prefix ?? '';
            $padded = str_pad($p->doctor_patient_no, 3, '0', STR_PAD_LEFT);
            return $prefix ? "{$prefix}-{$padded}" : "#{$padded}";
        };

        // OT Doctors do not have OPD primary/secondary queues
        $primaryQueue   = collect();
        $secondaryQueue = collect();

        if (! $isOtDoctor) {
            // ── Primary queue ──────────────────────────────────────────────────
            $primaryPatients = Patient::with(['doctor:id,name,doctor_prefix', 'masterCity', 'location'])
                ->where('doctor_id', $targetDoctorId)
                ->whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
                ->orderBy('doctor_patient_no')
                ->take(20)
                ->get();

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

            // ── Secondary queue ────────────────────────────────────────────────
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
                $examData = [];
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
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_ot_doctor' => $isOtDoctor,
                'stats' => [
                    'assigned_today' => $assignedToday,
                    'primary_done'   => $primaryDone,
                    'secondary_done' => $secondaryDone,
                ],
                'tenant_today_patients'  => $tenantTodayPatients,
                'tenant_today_primary'   => $tenantTodayPrimary,
                'tenant_today_secondary' => $tenantTodaySecondary,
                'doctor_cards'   => $doctorCards,
                'ot_doctor_cards'=> $otDoctorCards,
                'ot_summary'     => $otSummary,
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
