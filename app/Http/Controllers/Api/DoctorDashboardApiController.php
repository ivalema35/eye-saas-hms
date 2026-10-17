<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
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
        // 4th pill on web's top bar (doctoredashboard.blade.php:1017,
        // tooltip "Today — counselling through OT Assistant") — was missing
        // here entirely, so neither app could show it no matter what the UI
        // did. Same scope/params as Hospital\Dashboard\DashboardController.php:101-103.
        $tenantTodayOtPipeline = OtBooking::query()
            ->betweenCounsellingAndAssistant($today, $today)
            ->count();

        // ── Doctor cards (all OPD doctors, skip OT Doctor) ────────────────────
        $doctorCards = HospitalUser::with('role')
            ->whereHas('role', fn ($q) => $q->where('slug', 'doctor'))
            ->where('status', 'active')
            ->get()
            ->map(function ($doc) use ($today, $authUser) {
                $assigned  = Patient::where('doctor_id', $doc->id)->whereDate('appointment_date', $today)->count();
                $primary   = Patient::where('doctor_id', $doc->id)->whereDate('appointment_date', $today)
                    ->whereNotNull('primary_done_at')->count();
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

        // OT doctor cards — mirrors web's Hospital\Dashboard\DashboardController
        // ::index() (OPD/OT dashboard tab). Today-scoped:
        // OT = pending at the OT appointment counter (booked/confirmed, not yet
        //      converted to an OPD patient), by appointment_date.
        // PD = payment complete (payment_verified or any later stage), by last
        //      status change.
        // OC = operated and discharged (handed off from OT assistant), by last
        //      status change.
        $paymentCompleteStatuses = [
            OtBooking::STATUS_PAYMENT_VERIFIED,
            OtBooking::STATUS_IN_WARD,
            OtBooking::STATUS_DILATED,
            OtBooking::STATUS_READY,
            OtBooking::STATUS_OPERATED,
            OtBooking::STATUS_DISCHARGED,
        ];
        $doctorIdsForOt = $doctorCards->pluck('id')->all();

        // Effective doctor of a booking: ot_doctor_id once Ward Management set
        // it, else the OPD walk-in doctor (patients.doctor_id) — same fallback
        // web uses, since ot_doctor_id stays null on bookings that go straight
        // to "Ready for OT".
        $effectiveDoctorSql = 'COALESCE(ot_bookings.ot_doctor_id, patients.doctor_id)';

        $otBookingStatsByDoctorId = collect();
        $otCounterStatsByDoctorId = collect();
        if ($doctorIdsForOt !== []) {
            $otBookingStatsByDoctorId = OtBooking::query()
                ->join('patients', 'patients.id', '=', 'ot_bookings.patient_id')
                ->selectRaw("$effectiveDoctorSql as doctor_key")
                ->selectRaw(
                    'SUM(CASE WHEN ot_bookings.ot_status IN (?, ?, ?, ?, ?, ?) THEN 1 ELSE 0 END) as ot_payment_complete',
                    $paymentCompleteStatuses
                )
                ->selectRaw(
                    'SUM(CASE WHEN ot_bookings.ot_status = ? THEN 1 ELSE 0 END) as ot_discharged',
                    [OtBooking::STATUS_DISCHARGED]
                )
                ->whereIn(DB::raw($effectiveDoctorSql), $doctorIdsForOt)
                ->whereDate('ot_bookings.updated_at', $today)
                ->groupBy(DB::raw($effectiveDoctorSql))
                ->get()
                ->keyBy('doctor_key');

            $otCounterStatsByDoctorId = OtAppointment::query()
                ->selectRaw('doctor_id as doctor_key')
                ->selectRaw('COUNT(*) as ot_counter_pending')
                ->whereIn('doctor_id', $doctorIdsForOt)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', [OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED])
                ->whereNull('converted_patient_id')
                ->groupBy('doctor_id')
                ->get()
                ->keyBy('doctor_key');
        }

        $otDoctorCards = $doctorCards->map(function ($doc) use ($otBookingStatsByDoctorId, $otCounterStatsByDoctorId) {
            $bookingStats = $otBookingStatsByDoctorId->get($doc['id']);
            $counterStats = $otCounterStatsByDoctorId->get($doc['id']);

            return [
                'id' => $doc['id'],
                'name' => $doc['name'],
                'is_self' => $doc['is_self'],
                'ot_counter_pending' => (int) ($counterStats->ot_counter_pending ?? 0),
                'ot_payment_complete' => (int) ($bookingStats->ot_payment_complete ?? 0),
                'ot_discharged' => (int) ($bookingStats->ot_discharged ?? 0),
            ];
        })->values();

        // ── OT tab lists for the doctor whose dashboard is open (self or
        // viewed), today by last status change. "This doctor's booking" =
        // ot_doctor_id when set, else the OPD walk-in doctor.
        //  - Payment Pending: counselled / paid, awaiting payment.
        //  - Ward Management: payment verified / in ward / dilated.
        //  - Ready for Surgery: this doctor's Ready-for-OT patients — the doctor can
        //    Operate them (same Surgery Recording Form the OT Assistant uses).
        $otPaymentPendingQueue = collect();
        $otWardQueue = collect();
        $otReadyQueue = collect();
        if ($targetDoctorId) {
            $otBookingsQuery = OtBooking::with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,age,gender,location_id,doctor_id',
                'patient.location:id,city',
                'patient.masterCity:id,name',
                'preOp:id,ot_booking_id,pre_op_status',
                'otAssistant:id,name',
            ])->whereDate('updated_at', $today);

            $matchesDoctor = function ($q) use ($targetDoctorId) {
                $q->where('ot_doctor_id', $targetDoctorId)
                    ->orWhere(function ($q2) use ($targetDoctorId) {
                        $q2->whereNull('ot_doctor_id')
                            ->whereHas('patient', fn ($p) => $p->where('doctor_id', $targetDoctorId));
                    });
            };

            $otPaymentPendingQueue = (clone $otBookingsQuery)
                ->whereIn('ot_status', [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID])
                ->where($matchesDoctor)
                ->orderByDesc('surgery_date')
                ->orderByDesc('id')
                ->take(50)
                ->get();

            $otWardQueue = (clone $otBookingsQuery)
                ->whereIn('ot_status', [
                    OtBooking::STATUS_PAYMENT_VERIFIED,
                    OtBooking::STATUS_IN_WARD,
                    OtBooking::STATUS_DILATED,
                ])
                ->where($matchesDoctor)
                ->orderByDesc('surgery_date')
                ->orderByDesc('id')
                ->take(50)
                ->get();

            $otReadyQueue = (clone $otBookingsQuery)
                ->where('ot_status', OtBooking::STATUS_READY)
                ->where($matchesDoctor)
                ->orderByDesc('surgery_date')
                ->orderByDesc('id')
                ->take(50)
                ->get();
        }

        $mapOtQueueItem = function (OtBooking $b): array {
            $status = OtBooking::statusLabel($b->ot_status);
            $p = $b->patient;

            return [
                'id' => $b->id,
                'patient_code' => $p?->patient_code,
                'patient_name' => $p?->full_name ?? '',
                'gender' => $p?->gender,
                'age' => $p?->age,
                'city' => $p?->city_name,
                'ot_status' => $b->ot_status,
                'status_label' => $status['label'],
                'status_tone' => $status['tone'],
                'is_consult_pending' => $b->isDoctorConsultationPending(),
                'ot_assistant_name' => $b->otAssistant?->name,
            ];
        };

        // ── Top bar (OT tab) — hospital-wide "today" counters, same
        // definitions as the per-doctor OT/PD/OC above without the doctor
        // filter, plus the TP badge's OT figure (OT appointments added today).
        $todayOtCounterPending = OtAppointment::query()
            ->whereDate('appointment_date', $today)
            ->whereIn('status', [OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED])
            ->whereNull('converted_patient_id')
            ->count();

        $todayPaymentComplete = OtBooking::query()
            ->whereIn('ot_status', $paymentCompleteStatuses)
            ->whereDate('updated_at', $today)
            ->count();

        $todayDischarged = OtBooking::query()
            ->where('ot_status', OtBooking::STATUS_DISCHARGED)
            ->whereDate('updated_at', $today)
            ->count();

        $todayOtAppointmentsAdded = OtAppointment::query()
            ->whereDate('appointment_date', $today)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED)
            ->count();

        // ── DR Index helper ────────────────────────────────────────────────────
        // Matches web's `$formatQueueNo` exactly (doctoredashboard.blade.php)
        // — a plain '#' + the unpadded doctor_patient_no, no doctor prefix.
        // Previously padded to 3 digits and prefixed with the doctor's own
        // code (e.g. "DR-001"), which read as a patient-ID-like code instead
        // of web's simple queue index (e.g. "#1"). See user report 2026-09-17.
        $buildIndex = function (Patient $p): string {
            if (! $p->doctor_patient_no) return '—';
            return '#' . (int) $p->doctor_patient_no;
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
                'gender'       => $p->gender,
                'city'         => $p->city_name,
                'registered_at'=> $p->created_at?->toISOString(),
                'has_history'  => isset($primaryContactHistory[$p->contact_no]),
            ])->values();

            // ── Secondary queue ────────────────────────────────────────────────
            // Fallback only for a patient whose primary exam has no recorded
            // dilation_time at all — the real value below always wins when
            // present. See DOCTOR_DASHBOARD_WEB_PARITY_STUDY.md.
            $dilationLockMinutesFallback = (int) HospitalSetting::get('wait_d_green_max', 40);

            $secondaryPatients = Patient::with(['doctor:id,name,doctor_prefix', 'masterCity', 'location', 'primaryExamination'])
                ->where('doctor_id', $targetDoctorId)
                ->whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->whereNull('secondary_done_at')
                ->orderBy('doctor_patient_no')
                ->take(20)
                ->get();

            $secondaryContactHistory = $this->resolveContactHistory($secondaryPatients->pluck('contact_no'));

            $secondaryQueue = $secondaryPatients->map(function ($p) use ($buildIndex, $dilationLockMinutesFallback, $secondaryContactHistory) {
                $examData = [];
                if ($p->primaryExamination) {
                    $examData = is_array($p->primaryExamination->exam_data)
                        ? $p->primaryExamination->exam_data
                        : (json_decode($p->primaryExamination->exam_data ?? '{}', true) ?? []);
                }
                $isDilated = ($examData['dilate'] ?? '') === 'Yes';

                // Per-exam dilation duration — the doctor's own recorded
                // decision for THIS patient, not a hospital-wide display
                // setting. Mirrors the already-correct pattern in
                // PatientApiController.php:125-128. Previously this sent the
                // same flat setting-derived number for every patient in the
                // queue, which could show the wrong unlock time. See
                // DOCTOR_DASHBOARD_WEB_PARITY_STUDY.md.
                $dilationLockMinutes = $p->primaryExamination?->dilation_time
                    ? (int) $p->primaryExamination->dilation_time
                    : $dilationLockMinutesFallback;

                return [
                    'id'                    => $p->id,
                    'patient_code'          => $p->patient_code,
                    'doctor_id'             => $p->doctor_id,
                    'patient_name'          => $p->full_name,
                    'dr_index_no'           => $buildIndex($p),
                    'age'                   => $p->age,
                    'gender'                => $p->gender,
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
                'tenant_today_ot_pipeline' => $tenantTodayOtPipeline,
                'doctor_cards'   => $doctorCards,
                'ot_doctor_cards'=> $otDoctorCards,
                'viewing_doctor' => $viewingDoctor
                    ? ['id' => $viewingDoctor->id, 'name' => $viewingDoctor->name, 'is_self' => false]
                    : null,
                'primary_queue'  => $primaryQueue,
                'secondary_queue'=> $secondaryQueue,
                // OT tab: today's Payment Pending / Ward Management / OT
                // Assistant lists for the open doctor, plus the hospital-wide
                // OT/PD/OC counters on the top bar and the TP badge figure.
                'ot_payment_pending_queue' => $otPaymentPendingQueue->map($mapOtQueueItem)->values(),
                'ot_ward_queue'            => $otWardQueue->map($mapOtQueueItem)->values(),
                'ot_ready_queue'           => $otReadyQueue->map($mapOtQueueItem)->values(),
                'today_ot_counter_pending' => $todayOtCounterPending,
                'today_payment_complete'   => $todayPaymentComplete,
                'today_discharged'         => $todayDischarged,
                'today_ot_appointments_added' => $todayOtAppointmentsAdded,
                // Hospital-configurable wait-pill color thresholds — same
                // shape/defaults as DashboardController::todayPatients(),
                // so a hospital that customizes these sees the same colors
                // here as on web/the regular dashboard. See
                // DOCTOR_DASHBOARD_FIX_PLAN.md Phase 4.
                'wait_thresholds' => [
                    'r_green'  => (int) HospitalSetting::get('wait_green_max', 30),
                    'r_orange' => (int) HospitalSetting::get('wait_orange_max', 60),
                    'r_red'    => (int) HospitalSetting::get('wait_red_max', 120),
                    'd_green'  => (int) HospitalSetting::get('wait_d_green_max', 40),
                    'd_orange' => (int) HospitalSetting::get('wait_d_orange_max', 90),
                    'd_red'    => (int) HospitalSetting::get('wait_d_red_max', 120),
                    'nd_green' => (int) HospitalSetting::get('wait_nd_green_max', 20),
                    'nd_orange'=> (int) HospitalSetting::get('wait_nd_orange_max', 60),
                    'nd_red'   => (int) HospitalSetting::get('wait_nd_red_max', 120),
                ],
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
