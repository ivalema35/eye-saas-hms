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
use App\Models\Platform\HospitalShareRequest;
use App\Services\Auth\RolePermissionService;
use App\Services\Hospital\HospitalCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private readonly RolePermissionService $perm) {}

    /**
     * Admin Dashboard Stats
     *
     * GET /api/v1/{slug}/admin/dashboard
     * Auth: sanctum + subscription.active
     */
    public function adminDashboard(Request $request, HospitalCollectionService $collectionService): JsonResponse
    {
        $tenant   = app('tenant');
        $authUser = auth('sanctum')->user();
        $today    = now()->toDateString();

        // RolePermissionService resolves the current user off the
        // hospital_user session guard, not sanctum — bind it explicitly the
        // same way CheckPermission middleware does for routes that use it.
        // This route has no permission middleware of its own (it must stay
        // reachable by every role), so nothing else would do this binding.
        if ($authUser) {
            Auth::guard('hospital_user')->setUser($authUser);
        }

        $roleSlug    = $authUser?->role?->slug ?? '';
        $isDoctor    = in_array($roleSlug, ['doctor', 'ot_assistant'], true);
        $isOtDoctor  = $roleSlug === 'ot_assistant';
        $isReceptionist = in_array($roleSlug, ['receptionist', 'receptionist_opd'], true);
        $isAdmin     = (bool) ($authUser?->role?->is_super ?? false);

        // Web's role-based bypass for clinical data (Hospital\DashboardController.php:55,88)
        // includes the legacy 'ot_doctor' slug alongside 'doctor' — mirrored
        // exactly here, distinct from this file's own $isDoctor (which means
        // something different: 'doctor' or 'ot_assistant', used elsewhere
        // below for queue-scoping and doctor-card visibility).
        $isDoctorRoleForClinicalGate = in_array($roleSlug, ['doctor', 'ot_doctor'], true);

        // ── OPD Stats — gated: web only computes this section for a user
        // who can perform exams, or who is a doctor by role regardless of
        // permission (Hospital\DashboardController.php:88). Previously this
        // whole section, including the real per-patient primary queue further
        // below, was computed unconditionally for any authenticated user
        // regardless of permission. See ROLES_PERMISSIONS_DEEP_AUDIT_ROUND3.md.
        $canSeeClinical = $this->perm->can('dashboard_clinical')
            || $isDoctorRoleForClinicalGate;

        $todayPatients = null;
        $pendingExams = null;
        $primaryQueueCount = null;
        $secondaryQueueCount = null;

        if ($canSeeClinical) {
            $todayPatients = Patient::whereDate('appointment_date', $today)->count();

            // Web's separate "Pending Exams" card (Hospital\DashboardController.php:90-92)
            // — deliberately no type/checked_in_at filter, unlike primaryQueueCount
            // below. See DASHBOARD_PARITY_FIX_PLAN.md Phase 5.
            $pendingExams = Patient::whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->count();

            $primaryQueueCount = Patient::whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
                ->count();

            $secondaryQueueCount = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->whereNull('secondary_done_at')
                ->count();
        }

        // ── Reception Data — gated: web dashboard_reception
        $canSeeReception = $this->perm->can('dashboard_reception');

        $todayWalkin = null;
        $todayPhone = null;

        if ($canSeeReception) {
            $todayWalkin = Patient::whereDate('appointment_date', $today)
                ->where('type', 'walkin')
                ->count();

            $todayPhone = Patient::whereDate('appointment_date', $today)
                ->where('type', 'phone')
                ->count();
        }

        // ── Revenue — gated: web dashboard_revenue
        $canSeeRevenue = $this->perm->can('dashboard_revenue');
        $revenueToday = null;
        $revenueMonth = null;
        $revenueYear = null;

        if ($canSeeRevenue) {
            // Unified OPD + OT net (payments − refunds), same formula web uses
            // via HospitalCollectionService — see DASHBOARD_PARITY_FIX_PLAN.md
            // Phase 2. Plain `Patient.case_fee` sums used to miss OT revenue
            // entirely.
            $revenueToday = $collectionService->summaryForDay($today)['total'];
            $revenueMonth = $collectionService->summaryForCalendarMonth()['total'];
            $revenueYear  = $collectionService->summaryForCalendarYear()['total'];
        }

        // ── OT Stats (dashboard_ot) ─────────────────────────────────────
        $otToday = $otOperated = $otPending = null;
        if ($this->perm->can('dashboard_ot')) {
            try {
                $otToday    = OtAppointment::whereDate('appointment_date', $today)->count();
                $otOperated = OtAppointment::whereDate('appointment_date', $today)
                    ->where('status', OtAppointment::STATUS_CONFIRMED)
                    ->count();
                $otPending  = OtAppointment::whereDate('appointment_date', $today)
                    ->where('status', OtAppointment::STATUS_BOOKED)
                    ->count();
            } catch (\Throwable) {
                $otToday = $otOperated = $otPending = 0;
            }
        }
        // ── Role-specific pending cards (same gates as web) ────────────
        $accountantPendingCount = $accountantRefundsCount = $accountantCompletedCount = null;
        $wardPendingCount = null;
        $otAssistantPendingCount = null;
        $dischargePendingCount = null;
        $canSeeOtWidget = $this->perm->can('dashboard_ot');

        try {
            if ($roleSlug === 'accountant' && ($canSeeOtWidget || $this->perm->can('ot_payment_record'))) {
                $accountantPendingCount = OtBooking::whereIn('ot_status', [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID])->count();

                $accountantRefundsCount = OtBooking::where('ot_status', OtBooking::STATUS_SURGERY_REFUSED)
                    ->with(['payments', 'refunds'])
                    ->get()
                    ->filter(fn (OtBooking $b) => ! $b->isFullyRefunded() && $b->refundable_balance > 0)
                    ->count();

                $accountantCompletedCount = OtBooking::whereIn('ot_status', [
                    OtBooking::STATUS_PAYMENT_VERIFIED,
                    OtBooking::STATUS_IN_WARD,
                    OtBooking::STATUS_DILATED,
                    OtBooking::STATUS_READY,
                    OtBooking::STATUS_OPERATED,
                    OtBooking::STATUS_DISCHARGED,
                    OtBooking::STATUS_SURGERY_REFUSED,
                ])->count();
            } elseif ($roleSlug === 'ward_management' && ($canSeeOtWidget || $this->perm->can('ot_ward_entry'))) {
                $wardPendingCount = OtBooking::whereIn('ot_status', [
                    OtBooking::STATUS_PAYMENT_VERIFIED,
                    OtBooking::STATUS_IN_WARD,
                    OtBooking::STATUS_DILATED,
                ])->count();
            } elseif ($isOtDoctor && ($canSeeOtWidget || $this->perm->can('ot_surgery_ready'))) {
                $otAssistantReadyQuery = OtBooking::where('ot_status', OtBooking::STATUS_READY);
                $seeAll = $isAdmin || $roleSlug === 'hospital_admin';
                if (! $seeAll) {
                    $otAssistantReadyQuery->where('ot_assistant_id', (int) $authUser->id);
                }
                $otAssistantPendingCount = $otAssistantReadyQuery->count();
            } elseif ($roleSlug === 'discharge_counter' && ($canSeeOtWidget || $this->perm->can('ot_billing_manage'))) {
                $dischargePendingCount = OtBooking::whereIn('ot_status', ['operated', 'discharged', 'OPERATED', 'DISCHARGED'])->count();
            }
        } catch (\Throwable) {}

        // ── Staff (dashboard_staff) ────────────────────────────────────
        $canSeeStaff = $this->perm->can('dashboard_staff');
        $totalDoctors = null;
        $totalReceptions = null;
        if ($canSeeStaff || $isAdmin) {
            $totalDoctors    = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))->count();
            $totalReceptions = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))->count();
        }

        // ── Hospital Admin's own 8-card set — is_super only (web parity)
        $todayPrimary = null;
        $todaySecondary = null;
        $otTotalToday = null;
        if ($isAdmin) {
            $todayPatients = Patient::whereDate('appointment_date', $today)->count();
            $todayPrimary = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->count();
            $todaySecondary = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('secondary_done_at')
                ->count();
            try {
                $otTotalToday = OtBooking::whereDate('surgery_date', $today)->count();
            } catch (\Throwable) {
                $otTotalToday = 0;
            }
            if (! $canSeeRevenue) {
                $revenueToday = $collectionService->summaryForDay($today)['total'];
                $revenueMonth = $collectionService->summaryForCalendarMonth()['total'];
                $revenueYear  = $collectionService->summaryForCalendarYear()['total'];
            }
        }
        // ── Subscription days remaining ────────────────────────────────
        $subscriptionDaysLeft = null;
        try {
            $sub = $tenant->activeSubscription ?? null;
            if ($sub && $sub->ends_at) {
                $subscriptionDaysLeft = (int) now()->diffInDays($sub->ends_at, false);
            } elseif ($tenant->trial_ends_at) {
                $subscriptionDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
            }
        } catch (\Throwable) {}

        // ── Primary Queue (top 20; scoped to own patients for doctors) ────
        // Gated by the same $canSeeClinical check as the OPD stats above —
        // this is real per-patient data (name, age, gender), previously
        // returned to any authenticated user regardless of permission. See
        // ROLES_PERMISSIONS_DEEP_AUDIT_ROUND3.md.
        $primaryQueue = collect();

        if ($canSeeClinical) {
            $primaryQueueQuery = Patient::with(['doctor:id,name,doctor_prefix'])
                ->whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
                ->orderBy('doctor_patient_no')
                ->take(20);

            if ($isDoctor) {
                $primaryQueueQuery->where('doctor_id', $authUser->id);
            }

            $primaryPatients = $primaryQueueQuery->get();

            // Batch has_history — match by name+contact_no (not contact alone)
            $primaryQueue = $this->attachHasHistory($primaryPatients)->map(fn ($p) => [
                'id'                => $p->id,
                'patient_code'      => $p->patient_code,
                'full_name'         => $p->full_name,
                'age'               => $p->age,
                'gender'            => $p->gender,
                'doctor_patient_no' => $p->doctor_patient_no,
                'checked_in_at'     => $p->checked_in_at?->toISOString(),
                'registered_at'     => $p->created_at?->toISOString(),
                'doctor_name'       => $p->doctor?->name,
                'doctor_prefix'     => $p->doctor?->doctor_prefix,
                'has_history'       => (bool) ($p->has_history ?? false),
            ]);
        }

        // ── Receptionists Performance (dashboard_staff) ────────────────
        $receptionists = null;
        if ($canSeeStaff) {
            $receptionists = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))
                ->get()
                ->map(function ($rec) use ($today) {
                    $count = Patient::whereDate('appointment_date', $today)
                        ->where('reception_id', $rec->id)
                        ->count();
                    $gross = (float) Patient::whereDate('appointment_date', $today)
                        ->where('reception_id', $rec->id)
                        ->sum('case_fee');

                    return [
                        'id'          => $rec->id,
                        'name'        => $rec->name,
                        'today_count' => $count,
                        'today_gross' => $gross,
                        'today_net'   => $gross,
                    ];
                });
        }
        // ── Wait thresholds ────────────────────────────────────────────
        $thresholds = [
            'r_green'  => (int) HospitalSetting::get('wait_green_max', 30),
            'r_orange' => (int) HospitalSetting::get('wait_orange_max', 60),
            'r_red'    => (int) HospitalSetting::get('wait_red_max', 120),
        ];

        // ── Doctor-specific stats ───────────────────────────────────────
        $myTodayPatients  = 0;
        $myPrimaryPending = 0;
        $mySecondaryPending = 0;

        if ($isDoctor && $authUser) {
            if ($isOtDoctor) {
                $myTodayPatients = OtBooking::where('ot_doctor_id', $authUser->id)
                    ->whereDate('surgery_date', $today)->count();
            } else {
                $myTodayPatients = Patient::whereDate('appointment_date', $today)
                    ->where('doctor_id', $authUser->id)->count();
                $myPrimaryPending = Patient::whereDate('appointment_date', $today)
                    ->where('doctor_id', $authUser->id)
                    ->whereNull('primary_done_at')->count();
                $mySecondaryPending = Patient::whereDate('appointment_date', $today)
                    ->where('doctor_id', $authUser->id)
                    ->whereNotNull('primary_done_at')
                    ->whereNull('secondary_done_at')->count();
            }
        }

        // ── Receptionist stats (dashboard_reception) ───────────────────
        $receptionistStats = null;
        if ($isReceptionist && $authUser && $canSeeReception) {
            $myPatientsQuery = Patient::where('reception_id', $authUser->id);

            $receptionistStats = [
                'my_patients_today'    => (clone $myPatientsQuery)->whereDate('appointment_date', $today)->count(),
                'my_total_patients'    => (clone $myPatientsQuery)->count(),
                'my_walkin'            => (clone $myPatientsQuery)->where('type', 'walkin')->count(),
                'my_phone'             => (clone $myPatientsQuery)->where('type', 'phone')->count(),
                'today_collection'     => (float) Patient::whereDate('appointment_date', $today)->sum('case_fee'),
                'pending_phone_checkin' => Patient::where('type', 'phone')
                    ->whereNull('case_id')
                    ->whereDate('appointment_date', '>=', $today)
                    ->count(),
            ];
        }

        // ── Doctor cards (dashboard_clinical | dashboard_reception) ────
        $doctorCards = null;
        if (! $isDoctor && ($canSeeClinical || $canSeeReception)) {
            $allDoctors = HospitalUser::with('role:id,slug')
                ->whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_assistant']))
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'role_id', 'name', 'doctor_type']);

            $doctorStatsById = Patient::whereDate('appointment_date', $today)
                ->whereIn('doctor_id', $allDoctors->pluck('id'))
                ->select('doctor_id')
                ->selectRaw('COUNT(*) as assigned_today')
                ->selectRaw('SUM(CASE WHEN primary_done_at IS NOT NULL AND secondary_done_at IS NULL THEN 1 ELSE 0 END) as primary_count')
                ->selectRaw('SUM(CASE WHEN secondary_done_at IS NOT NULL THEN 1 ELSE 0 END) as secondary_count')
                ->groupBy('doctor_id')
                ->get()
                ->keyBy('doctor_id');

            $doctorCards = $allDoctors->map(function ($doc) use ($doctorStatsById) {
                $stats = $doctorStatsById->get($doc->id);
                return [
                    'id'              => $doc->id,
                    'name'            => $doc->name,
                    'doctor_type'     => $doc->doctor_type,
                    'role_slug'       => $doc->role?->slug,
                    'assigned_today'  => (int) ($stats->assigned_today ?? 0),
                    'primary_count'   => (int) ($stats->primary_count ?? 0),
                    'secondary_count' => (int) ($stats->secondary_count ?? 0),
                ];
            })->values();
        }

        // ── Pending share requests (admin only) ────────────────────────
        $pendingShareRequestsCount = null;
        if ($isAdmin && $tenant) {
            try {
                $pendingShareRequestsCount = HospitalShareRequest::where('to_tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->count();
            } catch (\Throwable) {}
        }

        return response()->json([
            'success' => true,
            'data'    => [
                // Flutter: show card when true (even if count is 0); hide when false/null fields
                'widgets' => [
                    'clinical'  => $canSeeClinical || $isAdmin,
                    'reception' => $canSeeReception || $isAdmin,
                    'revenue'   => $canSeeRevenue || $isAdmin,
                    'ot'        => $canSeeOtWidget || $isAdmin
                        || $accountantPendingCount !== null
                        || $wardPendingCount !== null
                        || $otAssistantPendingCount !== null
                        || $dischargePendingCount !== null,
                    'staff'     => $canSeeStaff || $isAdmin,
                ],
                'subscription_days_left'      => $subscriptionDaysLeft,
                'is_doctor'                   => $isDoctor,
                'is_ot_doctor'                => $isOtDoctor,
                'is_receptionist'             => $isReceptionist,
                'my_today_patients'           => $myTodayPatients,
                'my_primary_pending'          => $myPrimaryPending,
                'my_secondary_pending'        => $mySecondaryPending,
                'today_patients'              => $todayPatients,
                'pending_exams'               => $pendingExams,
                'primary_queue_count'         => $primaryQueueCount,
                'secondary_queue_count'       => $secondaryQueueCount,
                'today_walkin'                => $todayWalkin,
                'today_phone'                 => $todayPhone,
                'today_registrations'         => $canSeeReception ? ((int) $todayWalkin + (int) $todayPhone) : null,
                'revenue_today'               => $revenueToday,
                'revenue_month'               => $revenueMonth,
                'revenue_year'                => $revenueYear,
                'ot_today'                    => $otToday,
                'ot_operated'                 => $otOperated,
                'ot_pending'                  => $otPending,
                'total_staff'                 => ($totalDoctors !== null && $totalReceptions !== null)
                    ? ($totalDoctors + $totalReceptions)
                    : null,
                'total_doctors'               => $totalDoctors,
                'total_receptions'            => $totalReceptions,
                'today_primary'               => $todayPrimary,
                'today_secondary'             => $todaySecondary,
                'ot_total_today'              => $otTotalToday,
                'primary_queue'               => $primaryQueue,
                'receptionists'               => $receptionists,
                'wait_thresholds'             => $thresholds,
                'receptionist_stats'          => $receptionistStats,
                'doctor_cards'                => $doctorCards,
                'pending_share_requests_count'=> $pendingShareRequestsCount,
                'accountant_pending_count'    => $accountantPendingCount,
                'accountant_refunds_count'    => $accountantRefundsCount,
                'accountant_completed_count'  => $accountantCompletedCount,
                'ward_pending_count'          => $wardPendingCount,
                'ot_assistant_pending_count'  => $otAssistantPendingCount,
                'discharge_pending_count'     => $dischargePendingCount,
            ],
        ]);
    }

    /**
     * Receptionist "Today Added Patients" widget — patients this receptionist
     * registered today, merged with today's still-open OT appointments
     * (pre-registration leads not yet converted to a patient). Mirrors
     * Hospital\Dashboard\DashboardController::mergeTodayOtAppointments().
     * See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §9 / FIX_PLAN TASK 5.1.
     *
     * GET /api/v1/{slug}/dashboard/today-patients
     * Auth: sanctum + subscription.active. Receptionist role only.
     */
    public function todayPatients(Request $request): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $roleSlug = $authUser?->role?->slug ?? '';
        if (! in_array($roleSlug, ['receptionist', 'receptionist_opd'], true)) {
            return response()->json(['success' => false, 'message' => 'Only available to receptionist users.'], 403);
        }

        $today = now()->toDateString();
        $searchContact = trim((string) $request->query('search_contact', ''));

        $patients = Patient::with([
            'doctor:id,name,doctor_prefix',
            'location:id,city,district,state',
            'caseType:id,case_type',
            'referrer:id,name',
            'primaryExamination:id,patient_id,exam_data,dilation_time,updated_at',
            'secondaryExamination:id,patient_id',
        ])
            ->where('reception_id', $authUser->id)
            ->whereDate('appointment_date', $today)
            ->when($searchContact !== '', fn ($q) => $q->where('contact_no', 'like', "%{$searchContact}%"))
            ->latest('created_at')
            ->get();

        $alreadyLinkedIds = $patients->pluck('id')->map(fn ($id) => (int) $id)->all();

        $patientRows = $patients->map(function (Patient $p) {
            $arr = $p->toArray();
            $arr['full_name'] = $p->full_name;
            $arr['source'] = 'patient';

            // Same dilation-unlock computation as PatientApiController::index,
            // duplicated here since this query's filters (reception_id-scoped,
            // today-only) differ from that endpoint's.
            $arr['unlock_time_ms'] = null;
            $pe = $p->primaryExamination;
            if ($pe && ! $p->secondary_done_at && ! $p->secondaryExamination) {
                $examData = $pe->exam_data;
                if (is_string($examData)) {
                    $examData = json_decode($examData, true);
                }
                $dilate = is_array($examData) ? ($examData['dilate'] ?? null) : null;
                $dilationTime = $pe->dilation_time;
                if ($dilate === 'Yes' && $dilationTime && $pe->updated_at) {
                    $unlockAt = $pe->updated_at->addMinutes((int) $dilationTime);
                    if ($unlockAt->isFuture()) {
                        $arr['unlock_time_ms'] = $unlockAt->timestamp * 1000;
                    }
                }
            }

            return $arr;
        });

        $otRows = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name'])
            ->whereDate('appointment_date', $today)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED)
            ->when($searchContact !== '', function ($q) use ($searchContact) {
                $q->where(function ($qq) use ($searchContact) {
                    $qq->where('mobile_no', 'like', "%{$searchContact}%")
                        ->orWhere('whatsapp_no', 'like', "%{$searchContact}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->reject(function (OtAppointment $appt) use ($alreadyLinkedIds) {
                $convertedId = (int) ($appt->converted_patient_id ?? 0);

                return $convertedId > 0 && in_array($convertedId, $alreadyLinkedIds, true);
            })
            ->map(function (OtAppointment $appt) {
                $arr = $appt->toArray();
                $arr['source'] = 'ot_appointment';

                return $arr;
            });

        $rows = $patientRows->concat($otRows)
            ->sortByDesc(fn ($row) => strtotime((string) ($row['created_at'] ?? '')) ?: 0)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'count' => $rows->count(),
                'wait_thresholds' => [
                    'r_green' => (int) HospitalSetting::get('wait_green_max', 30),
                    'r_orange' => (int) HospitalSetting::get('wait_orange_max', 60),
                    'r_red' => (int) HospitalSetting::get('wait_red_max', 120),
                    'd_green' => (int) HospitalSetting::get('wait_d_green_max', 40),
                    'd_orange' => (int) HospitalSetting::get('wait_d_orange_max', 90),
                    'd_red' => (int) HospitalSetting::get('wait_d_red_max', 120),
                    'nd_green' => (int) HospitalSetting::get('wait_nd_green_max', 20),
                    'nd_orange' => (int) HospitalSetting::get('wait_nd_orange_max', 60),
                    'nd_red' => (int) HospitalSetting::get('wait_nd_red_max', 120),
                ],
            ],
        ]);
    }

    /**
     * Attaches has_history to each patient in the collection.
     * Matched by contact_no — if any patient with that contact has an exam, flag it.
     */
    private function attachHasHistory(\Illuminate\Support\Collection $patients): \Illuminate\Support\Collection
    {
        $contacts = $patients->pluck('contact_no')->filter()->unique()->values()->all();
        if (empty($contacts)) {
            return $patients->map(function ($p) { $p->has_history = false; return $p; });
        }

        $patientIds = Patient::whereIn('contact_no', $contacts)->pluck('id');
        $examinedContacts = collect();

        if ($patientIds->isNotEmpty()) {
            $examinedIds = PrimaryExamination::whereIn('patient_id', $patientIds)->pluck('patient_id')
                ->merge(SecondaryExamination::whereIn('patient_id', $patientIds)->pluck('patient_id'))
                ->unique();

            $examinedContacts = Patient::whereIn('id', $examinedIds)
                ->whereIn('contact_no', $contacts)
                ->pluck('contact_no')
                ->unique()
                ->flip(); // contact_no => index (truthy)
        }

        return $patients->map(function ($p) use ($examinedContacts) {
            $p->has_history = $p->contact_no && $examinedContacts->has($p->contact_no);
            return $p;
        });
    }
}
