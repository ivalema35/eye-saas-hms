<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Foc;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Models\Platform\HospitalShareRequest;
use App\Models\Platform\Tenant;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Hospital Dashboard Controller
 *
 * Permission-aware dashboard: each data section is only queried when the
 * current user is actually authorised for that feature.
 *
 * NOTE: This app uses a custom hospital_user guard + RolePermissionService,
 *       not Laravel's Gate/can(). Always use $this->perm->can() here.
 *
 * URL: hmssaas.com/{slug}/
 */
class DashboardController extends Controller
{
    public function __construct(private readonly RolePermissionService $perm) {}

    public function index(): View|RedirectResponse
    {
        $slug = request()->route('slug');
        $tenant = app('tenant');
        $user = Auth::guard('hospital_user')->user();
        $today = now()->toDateString();

        $isReceptionistUser = in_array($user?->role?->slug, ['receptionist', 'receptionist_opd'], true);
        $isDoctorUser = in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true);

        // Setup wizard redirect — admin, first login only
        if ($user?->role?->is_super && $tenant && ! $tenant->is_setup_done) {
            return redirect()->route('hospital.setup.show', ['slug' => $slug, 'step' => 1]);
        }

        // ── Subscription banner ──────────────────────────────────────────────
        $subscriptionDaysLeft = null;
        if ($tenant) {
            $sub = $tenant->activeSubscription;
            if ($sub && $sub->ends_at) {
                $subscriptionDaysLeft = (int) now()->diffInDays($sub->ends_at, false);
            } elseif ($tenant->trial_ends_at) {
                $subscriptionDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
            }
        }

        // ── Clinical Data (exam.primary.view) ────────────────────────────────
        // Today's patients and pending exam queue.
        $todayPatients = null;
        $pendingExams = null;
        $todayPrimary = null;
        $todaySecondary = null;
        $primaryQueue = null;
        $doctorName = null;
        $doctorAssignedPatients = null;
        $doctorPrimaryDone = null;
        $doctorSecondaryDone = null;
        $secondaryQueue = null;
        $primaryQueueCount = null;
        $secondaryQueueCount = null;

        if ($this->perm->can('opd.exam.primary') || $this->perm->can('opd.exam.secondary') || $isDoctorUser) {
            $todayPatients = Patient::whereDate('appointment_date', $today)->count();
            $pendingExams = Patient::whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->count();
            $todayPrimary = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->count();
            $todaySecondary = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('secondary_done_at')
                ->count();

            $selectedDoctorId = null;
            $viewingDoctor = null;
            if (in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)) {
                $selectedDoctorId = $user->id;

                // Allow switching to another doctor's view via ?view_doctor=ID
                $viewDoctorId = (int) request('view_doctor', 0);
                if ($viewDoctorId && $viewDoctorId !== $user->id) {
                    $viewingDoctor = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
                        ->find($viewDoctorId);
                    if ($viewingDoctor) {
                        $selectedDoctorId = $viewingDoctor->id;
                    }
                }
            }

            $buildIndex = function (Patient $patient): Patient {
                $patient->display_doctor_index = $patient->doctor_patient_no
                    ? (($patient->doctor?->doctor_prefix ?? '')
                        ? $patient->doctor->doctor_prefix.'-'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT)
                        : '#'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT))
                    : '-';

                return $patient;
            };

            $primaryQueueQuery = Patient::with(['doctor', 'caseType', 'primaryExamination', 'location', 'masterCity.district', 'masterCity.state', 'reception', 'referrer'])
                ->whereDate('appointment_date', $today);

            if ($selectedDoctorId) {
                $primaryQueueQuery->where('doctor_id', $selectedDoctorId);
            }

            $primaryQueue = $primaryQueueQuery
                ->orderBy('doctor_patient_no')
                ->get()
                ->filter(
                    fn (Patient $patient): bool => $patient->primary_done_at === null &&
                    ($patient->type !== 'phone' || $patient->checked_in_at !== null)
                )
                ->map($buildIndex)
                ->take(20)
                ->values();

            // Attach all_patient_ids so the View button shows complete history (all old visits)
            if ($primaryQueue->isNotEmpty()) {
                $contactNos = $primaryQueue->pluck('contact_no')->filter()->unique()->values()->all();
                $allIdsByContact = Patient::whereIn('contact_no', $contactNos)
                    ->get(['id', 'contact_no'])
                    ->groupBy('contact_no')
                    ->map(fn ($g) => $g->pluck('id')->implode(','));

                $primaryQueue = $primaryQueue->map(function (Patient $p) use ($allIdsByContact): Patient {
                    $p->all_patient_ids = $allIdsByContact->get($p->contact_no) ?? (string) $p->id;

                    return $p;
                });

                // Determine which patients have any exam history (used to show/hide the View button)
                $allQueueIds = $primaryQueue
                    ->flatMap(fn ($p) => array_filter(array_map('intval', explode(',', $p->all_patient_ids))))
                    ->unique()
                    ->values()
                    ->all();

                $idsWithExams = PrimaryExamination::whereIn('patient_id', $allQueueIds)->pluck('patient_id')
                    ->merge(SecondaryExamination::whereIn('patient_id', $allQueueIds)->pluck('patient_id'))
                    ->unique();

                // Also check partner hospitals for same contact_no
                $contactNosWithPartnerHistory = collect();
                $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($tenant) {
                    $q->where('from_tenant_id', $tenant->id)->orWhere('to_tenant_id', $tenant->id);
                })->where('status', 'accepted')->get()
                    ->map(fn ($r) => $r->from_tenant_id === $tenant->id ? $r->to_tenant_id : $r->from_tenant_id)
                    ->toArray();

                if (! empty($partnerTenantIds) && ! empty($contactNos)) {
                    $contactNosWithPartnerHistory = Patient::withoutTenantScope()
                        ->whereIn('tenant_id', $partnerTenantIds)
                        ->whereIn('contact_no', $contactNos)
                        ->where(fn ($q) => $q
                            ->whereExists(fn ($sq) => $sq->selectRaw('1')->from('primary_examinations')->whereColumn('primary_examinations.patient_id', 'patients.id'))
                            ->orWhereExists(fn ($sq) => $sq->selectRaw('1')->from('secondary_examinations')->whereColumn('secondary_examinations.patient_id', 'patients.id'))
                        )
                        ->pluck('contact_no')
                        ->unique();
                }

                $primaryQueue = $primaryQueue->map(function (Patient $p) use ($idsWithExams, $contactNosWithPartnerHistory): Patient {
                    $ids = array_filter(array_map('intval', explode(',', $p->all_patient_ids)));
                    $hasOwn = collect($ids)->contains(fn ($id) => $idsWithExams->contains($id));
                    $hasPartner = $p->contact_no && $contactNosWithPartnerHistory->contains($p->contact_no);
                    $p->has_history = $hasOwn || $hasPartner;

                    return $p;
                });
            }

            // Queue counts for the admin dashboard card (all doctors, no take() limit)
            $primaryQueueCountQuery = Patient::whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'));
            if ($selectedDoctorId) {
                $primaryQueueCountQuery->where('doctor_id', $selectedDoctorId);
            }
            $primaryQueueCount = $primaryQueueCountQuery->count();

            $secondaryQueueCountQuery = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->whereNull('secondary_done_at');
            if ($selectedDoctorId) {
                $secondaryQueueCountQuery->where('doctor_id', $selectedDoctorId);
            }
            $secondaryQueueCount = $secondaryQueueCountQuery->count();

            // if ($isDoctorUser && $user) {
            //     $statsDoctorId = $viewingDoctor ? $viewingDoctor->id : $user->id;
            //     $doctorName = $viewingDoctor ? $viewingDoctor->name : $user->name;

            //     $doctorAssignedPatients = Patient::where('doctor_id', $statsDoctorId)
            //         ->whereDate('appointment_date', $today)
            //         ->count();
            //     $doctorPrimaryDone = Patient::where('doctor_id', $statsDoctorId)
            //         ->whereDate('appointment_date', $today)
            //         ->whereNotNull('primary_done_at')
            //         ->whereNull('secondary_done_at')
            //         ->count();
            //     $doctorSecondaryDone = Patient::where('doctor_id', $statsDoctorId)
            //         ->whereDate('appointment_date', $today)
            //         ->whereNotNull('secondary_done_at')
            //         ->count();

            //     $secondaryQueue = Patient::with(['doctor', 'caseType', 'primaryExamination'])
            //         ->where('doctor_id', $statsDoctorId)
            //         ->whereDate('appointment_date', $today)
            //         ->whereNotNull('primary_done_at')
            //         ->whereNull('secondary_done_at')
            //         ->orderBy('doctor_patient_no')
            //         ->take(20)
            //         ->get()
            //         ->map($buildIndex);
            // }

            if ($isDoctorUser && $user) {
                $statsDoctorId = $viewingDoctor ? $viewingDoctor->id : $user->id;
                $doctorName = $viewingDoctor ? $viewingDoctor->name : $user->name;

                if ($user->role?->slug === 'ot_doctor') {
                    $doctorAssignedPatients = OtBooking::where('ot_doctor_id', $statsDoctorId)
                        ->whereDate('surgery_date', $today)
                        ->count();

                    $doctorPrimaryDone = 0;
                    $doctorSecondaryDone = 0;
                    $secondaryQueue = collect();
                } else {

                    $doctorAssignedPatients = Patient::where('doctor_id', $statsDoctorId)
                        ->whereDate('appointment_date', $today)
                        ->count();
                    $doctorPrimaryDone = Patient::where('doctor_id', $statsDoctorId)
                        ->whereDate('appointment_date', $today)
                        ->whereNotNull('primary_done_at')
                        ->whereNull('secondary_done_at')
                        ->count();
                    $doctorSecondaryDone = Patient::where('doctor_id', $statsDoctorId)
                        ->whereDate('appointment_date', $today)
                        ->whereNotNull('secondary_done_at')
                        ->count();

                    $secondaryQueue = Patient::with(['doctor', 'caseType', 'primaryExamination', 'location', 'masterCity.district', 'masterCity.state'])
                        ->where('doctor_id', $statsDoctorId)
                        ->whereDate('appointment_date', $today)
                        ->whereNotNull('primary_done_at')
                        ->whereNull('secondary_done_at')
                        ->orderBy('doctor_patient_no')
                        ->take(20)
                        ->get()
                        ->map($buildIndex);
                }
            }
        }

        // ── Reception Data (opd.patient.register / opd.patient.register_phone) ────────────
        // Today's registrations and outstanding FOC requests.
        $todayRegistrations = null;
        $focPending = null;
        $todayWalkin = null;
        $todayPhone = null;

        if ($this->perm->can('opd.patient.register')) {
            $todayRegistrations = Patient::whereDate('appointment_date', $today)->count();
            $focPending = Foc::where('accepted', false)->count();
            $todayWalkin = Patient::whereDate('appointment_date', $today)
                ->where('type', 'walkin')
                ->count();
            $todayPhone = Patient::whereDate('appointment_date', $today)
                ->where('type', 'phone')
                ->count();
        }

        $receptionistTotalPatients = null;
        $receptionistPhoneAppointments = null;
        $receptionistMyPatients = null;
        $receptionistMyWalkin = null;
        $receptionistMyPhone = null;
        $receptionistTodayCollection = null;
        $receptionistMyPatientsToday = null;
        $receptionistTodayPhone = null;

        if ($isReceptionistUser && ($this->perm->can('opd.patient.register') || $this->perm->can('opd.patient.register_phone'))) {
            $receptionistBasePatients = Patient::whereHas('reception.role', fn ($q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd', 'hospital_admin']));
            $myPatientsQuery = Patient::where('reception_id', $user?->id);

            $receptionistTotalPatients = (clone $receptionistBasePatients)->count();
            $receptionistPhoneAppointments = (clone $receptionistBasePatients)
                ->where('type', 'phone')
                ->count();
            $receptionistMyPatients = (clone $myPatientsQuery)->count();
            $receptionistMyWalkin = (clone $myPatientsQuery)
                ->where('type', 'walkin')
                ->count();
            $receptionistMyPhone = (clone $myPatientsQuery)
                ->where('type', 'phone')
                ->count();

            // Today-specific stats for receptionist dashboard cards
            $receptionistTodayCollection = (float) Patient::whereDate('appointment_date', $today)->sum('case_fee');
            $receptionistMyPatientsToday = Patient::where('reception_id', $user?->id)
                ->whereDate('appointment_date', $today)
                ->count();
            $receptionistTodayPhone = Patient::where('type', 'phone')
                ->whereNull('case_id')
                ->whereDate('appointment_date', '>=', $today)
                ->count();
        }

        // ── Financial Data (report.revenue) ──────────────────────────────────
        // Revenue aggregates across today / month / year.
        $revenueToday = null;
        $revenueMonth = null;
        $revenueYear = null;

        if ($this->perm->can('opd.reports.view')) {
            $revenueToday = (float) Patient::whereDate('appointment_date', $today)
                ->sum('case_fee');
            $revenueMonth = (float) Patient::whereMonth('appointment_date', now()->month)
                ->whereYear('appointment_date', now()->year)
                ->sum('case_fee');
            $revenueYear = (float) Patient::whereYear('appointment_date', now()->year)
                ->sum('case_fee');
        }

        // ── OT Data (ot.booking.view) ───────────────────────────────────────────
        // Today's OT surgery schedule overview.
        $otToday = null;
        $otOperated = null;
        $otPending = null;

        if ($this->perm->can('ot.patient.list')) {
            $otToday = OtBooking::whereDate('surgery_date', $today)->count();
            $otOperated = OtBooking::whereDate('surgery_date', $today)
                ->where('ot_status', 'operated')
                ->count();
            $otPending = OtBooking::whereDate('surgery_date', $today)
                ->whereNotIn('ot_status', ['operated', 'discharged'])
                ->count();
        }

        // ── Incoming Share Requests (hospital admin only) ─────────────────────
        $pendingShareRequestsCount = null;
        if ($user?->role?->is_super && $tenant) {
            $pendingShareRequestsCount = HospitalShareRequest::where('to_tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->count();
        }

        // ── FOC Approval Alerts (foc.approve) ────────────────────────────────
        // Pending FOC requests awaiting this approver's decision.
        $focAlerts = null;
        $pendingFocRequests = null;
        $focReceptionists = null;

        if ($this->perm->can('opd.foc.accept')) {
            $focAlerts = Foc::where('accepted', false)->count();

            $pendingFocRequests = Foc::with([
                'patient' => fn ($q) => $q->withTrashed(),
                'doctor',
            ])
                ->where('status', 'pending')
                ->latest()
                ->limit(20)
                ->get();
        }

        if ($this->perm->can('opd.foc.create')) {
            $focReceptionists = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // ── Staff Overview (master.doctors / master.receptions) ───────────────────────────
        // Reception performance table — only for users who can see master data.
        $receptionists = null;
        $totalDoctors = null;
        $totalReceptions = null;

        if ($this->perm->can('master.doctors') || $this->perm->can('master.case_types')) {
            $totalDoctors = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))->count();
            $totalReceptions = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))->count();

            $receptionists = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))
                ->get()
                ->map(function (HospitalUser $rec) use ($today): HospitalUser {
                    $base = Patient::where('reception_id', $rec->id)->whereDate('appointment_date', $today);
                    $rec->today_count = $base->count();
                    $rec->today_gross = (float) $base->sum('case_fee');
                    $rec->today_foc = (float) Foc::where('reception_id', $rec->id)
                        ->whereHas('patient', fn ($q) => $q->whereDate('appointment_date', $today))
                        ->where('accepted', true)
                        ->sum('foc_fee');
                    $rec->today_net = $rec->today_gross - $rec->today_foc;

                    return $rec;
                });
        }

        // Receptionist-only doctor strip (all doctors + per-doctor assigned today)
        $doctorCards = collect();
        $doctorCardSummary = [
            'all' => 0,
            'primary' => 0,
            'secondary' => 0,
        ];
        $receptionistTodayPatients = collect();

        $isPureDoctorUser = $user?->role?->slug === 'doctor';

        if (
            ($isReceptionistUser || $isDoctorUser) && ! $isPureDoctorUser && (
                $this->perm->can('opd.patient.register')
                || $this->perm->can('opd.exam.primary')
                || $this->perm->can('opd.exam.secondary')
            )
        ) {
            $doctorCards = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
                ->active()
                ->with('role:id,slug')
                ->orderBy('name')
                ->get(['id', 'role_id', 'name', 'doctor_type']);

            $doctorStatsById = Patient::whereDate('appointment_date', $today)
                ->whereIn('doctor_id', $doctorCards->pluck('id'))
                ->select('doctor_id')
                ->selectRaw('COUNT(*) as assigned_today')
                ->selectRaw('SUM(CASE WHEN primary_done_at IS NOT NULL AND secondary_done_at IS NULL THEN 1 ELSE 0 END) as primary_count')
                ->selectRaw('SUM(CASE WHEN secondary_done_at IS NOT NULL THEN 1 ELSE 0 END) as secondary_count')
                ->groupBy('doctor_id')
                ->get()
                ->keyBy('doctor_id');

            $doctorCards = $doctorCards->map(function (HospitalUser $doctor) use ($doctorStatsById): HospitalUser {
                $doctorStats = $doctorStatsById->get($doctor->id);

                $doctor->assigned_today = (int) ($doctorStats->assigned_today ?? 0);
                $doctor->primary_count = (int) ($doctorStats->primary_count ?? 0);
                $doctor->secondary_count = (int) ($doctorStats->secondary_count ?? 0);

                return $doctor;
            });

            $doctorCardSummary = [
                'all' => $doctorCards->count(),
                'primary' => $doctorCards->where('doctor_type', 'primary')->count(),
                'secondary' => $doctorCards->where('doctor_type', 'secondary')->count(),
            ];

            $receptionistTodayPatients = Patient::with([
                'doctor:id,name,doctor_prefix',
                'location:id,city',
                'masterCity:id,name',
                'otBookings' => fn ($query) => $query->latest('id')->select('id', 'patient_id', 'ot_status'),
                'primaryExamination' => fn ($query) => $query->select('id', 'patient_id', 'examined_at', 'exam_data', 'dilation_time', 'updated_at'),
            ])
                ->leftJoin('tbl_slots', 'patients.slot_id', '=', 'tbl_slots.id')
                ->where('reception_id', $user->id)
                ->whereDate('appointment_date', $today)
                ->when(request('search_contact'), function ($query, $contact) {
                    $query->where('patients.contact_no', 'like', "%{$contact}%");
                })
                ->latest('patients.created_at')
                ->get([
                    'patients.id',
                    'patients.patient_code',
                    'patients.first_name',
                    'patients.middle_name',
                    'patients.last_name',
                    'patients.age',
                    'patients.gender',
                    'patients.contact_no',
                    'patients.location_id',
                    'patients.slot_id',
                    'patients.doctor_id',
                    'patients.doctor_patient_no',
                    'patients.case_fee',
                    'patients.type',
                    'patients.checked_in_at',
                    'patients.primary_done_at',
                    'patients.secondary_done_at',
                    'patients.created_at',
                    'tbl_slots.slot_name as slot_name',
                ]);
        }

        if ($user && $user->role?->slug === 'doctor') {

            $doctorCards = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
                ->with('role:id,slug')
                ->orderBy('name')
                ->get(['id', 'role_id', 'name', 'doctor_type']);

            $doctorStatsById = Patient::whereDate('appointment_date', $today)
                ->whereIn('doctor_id', $doctorCards->pluck('id'))
                ->select('doctor_id')
                ->selectRaw('COUNT(*) as assigned_today')
                ->selectRaw('SUM(CASE WHEN primary_done_at IS NOT NULL AND secondary_done_at IS NULL THEN 1 ELSE 0 END) as primary_count')
                ->selectRaw('SUM(CASE WHEN secondary_done_at IS NOT NULL THEN 1 ELSE 0 END) as secondary_count')
                ->groupBy('doctor_id')
                ->get()
                ->keyBy('doctor_id');

            $doctorCards = $doctorCards->map(function ($doc) use ($doctorStatsById) {
                $stats = $doctorStatsById->get($doc->id);
                $doc->assigned_today = (int) ($stats->assigned_today ?? 0);
                $doc->primary_count = (int) ($stats->primary_count ?? 0);
                $doc->secondary_count = (int) ($stats->secondary_count ?? 0);

                return $doc;
            });

            $doctorCards = $doctorCards
                ->sortBy(fn ($doc) => $doc->id === $user->id ? 0 : 1)
                ->values();

            return view('hospital.dashboard.doctoredashboard', compact(
                'slug',
                'tenant',
                'subscriptionDaysLeft',
                'primaryQueue',
                'secondaryQueue',
                'doctorName',
                'doctorAssignedPatients',
                'doctorPrimaryDone',
                'doctorSecondaryDone',
                'doctorCards',
                'viewingDoctor'
            ));
        }

        return view('hospital.dashboard.index', compact(
            'slug',
            'tenant',
            'isReceptionistUser',
            'isDoctorUser',
            'subscriptionDaysLeft',
            // Clinical
            'todayPatients',
            'pendingExams',
            'todayPrimary',
            'todaySecondary',
            'primaryQueue',
            'primaryQueueCount',
            'secondaryQueueCount',
            'doctorName',
            'doctorAssignedPatients',
            'doctorPrimaryDone',
            'doctorSecondaryDone',
            // Reception
            'todayRegistrations',
            'focPending',
            'todayWalkin',
            'todayPhone',
            'receptionistTotalPatients',
            'receptionistPhoneAppointments',
            'receptionistMyPatients',
            'receptionistMyWalkin',
            'receptionistMyPhone',
            'receptionistTodayCollection',
            'receptionistMyPatientsToday',
            'receptionistTodayPhone',
            // Financial
            'revenueToday',
            'revenueMonth',
            'revenueYear',
            // OT
            'otToday',
            'otOperated',
            'otPending',
            // FOC Approval
            'focAlerts',
            'pendingFocRequests',
            'focReceptionists',
            // Staff
            'totalDoctors',
            'totalReceptions',
            'receptionists',
            // Receptionist doctor cards
            'doctorCards',
            'doctorCardSummary',
            'receptionistTodayPatients',
            // Share requests (admin only)
            'pendingShareRequestsCount',
        ));
    }

    public function hospitalHistory()
    {
        $slug = request()->route('slug');

        $hospitals = Tenant::whereIn('status', ['trial', 'active', 'grace'])
            ->where('slug', '!=', $slug)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'city', 'district', 'state', 'logo_path', 'status']);

        return view('hospital.dashboard.hospital_history', compact('slug', 'hospitals'));
    }

    public function history()
    {
        $slug = request()->route('slug');
        $currentTenant = app('tenant');
        $patientName = request('patient_name');
        $doctorName = request('doctor_name');
        $contactNo = request('contact_no');
        $date = request('date');

        // Get accepted partner tenant IDs
        $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($currentTenant) {
            $q->where('from_tenant_id', $currentTenant->id)
                ->orWhere('to_tenant_id', $currentTenant->id);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(fn ($r) => $r->from_tenant_id === $currentTenant->id ? $r->to_tenant_id : $r->from_tenant_id)
            ->toArray();

        $tenantIds = array_merge([$currentTenant->id], $partnerTenantIds);

        $allHistoryPatients = Patient::withoutTenantScope()
            ->with(['caseType', 'doctor', 'tenant:id,name'])
            ->whereIn('tenant_id', $tenantIds)
            ->where(function ($query) {
                $query->whereNotNull('primary_done_at')
                    ->orWhereNotNull('secondary_done_at');
            })
            ->whereHas('doctor.role', function ($q) {
                $q->withoutGlobalScope('tenant')->where('slug', 'doctor');
            })
            ->when($patientName, function ($q) use ($patientName) {
                $q->where(function ($q2) use ($patientName) {
                    $q2->where('first_name', 'like', "%{$patientName}%")
                        ->orWhere('last_name', 'like', "%{$patientName}%");
                });
            })
            ->when($doctorName, function ($q) use ($doctorName) {
                $q->whereHas('doctor', function ($q2) use ($doctorName) {
                    $q2->withoutGlobalScope('tenant')->where('name', 'like', "%{$doctorName}%");
                });
            })
            ->when($contactNo, fn ($q) => $q->where('contact_no', 'like', "%{$contactNo}%"))
            ->when($date, function ($q) use ($date) {
                $q->whereDate('appointment_date', $date);
            })
            ->latest('appointment_date')
            ->get();

        // Group by name + contact + tenant so duplicates within the same hospital
        // are merged into one row with all their IDs; cross-hospital rows stay separate.
        $deduped = $allHistoryPatients
            ->groupBy(fn ($p) => mb_strtolower(trim($p->first_name.' '.$p->last_name)).'|'.$p->contact_no.'|'.$p->tenant_id)
            ->map(function ($group) {
                $rep = $group->sortByDesc('id')->first();
                $rep->all_patient_ids = $group->pluck('id')->implode(',');

                return $rep;
            })
            ->values();

        $perPage = 20;
        $page = max(1, (int) request('patient_page', 1));
        $historyPatients = new LengthAwarePaginator(
            $deduped->slice(($page - 1) * $perPage, $perPage)->values(),
            $deduped->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'patient_page',
                'query' => request()->query(),
            ]
        );

        $hospName = request('hosp_name');
        $hospCity = request('hosp_city');
        $hospDistrict = request('hosp_district');
        $hospState = request('hosp_state');

        $hospitals = Tenant::whereIn('status', ['trial', 'active', 'grace'])
            ->where('slug', '!=', $slug)
            ->when($hospName, fn ($q) => $q->where('name', 'like', "%{$hospName}%"))
            ->when($hospCity, fn ($q) => $q->where('city', 'like', "%{$hospCity}%"))
            ->when($hospDistrict, fn ($q) => $q->where('district', 'like', "%{$hospDistrict}%"))
            ->when($hospState, fn ($q) => $q->where('state', 'like', "%{$hospState}%"))
            ->orderBy('name')
            ->paginate(
                20,
                ['id', 'name', 'slug', 'city', 'district', 'state', 'logo_path', 'status'],
                'hospital_page'
            )
            ->withQueryString();

        // Build a map of request statuses for the hospital list
        $allRequests = HospitalShareRequest::where('from_tenant_id', $currentTenant->id)
            ->orWhere('to_tenant_id', $currentTenant->id)
            ->get();

        $requestMap = [];
        foreach ($allRequests as $req) {
            $otherId = $req->from_tenant_id === $currentTenant->id ? $req->to_tenant_id : $req->from_tenant_id;
            $requestMap[$otherId] = [
                'id' => $req->id,
                'status' => $req->status,
                'direction' => $req->from_tenant_id === $currentTenant->id ? 'sent' : 'received',
            ];
        }

        // Incoming pending requests for the Request tab
        $incomingRequests = HospitalShareRequest::with('fromTenant')
            ->where('to_tenant_id', $currentTenant->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Sent requests (all statuses)
        $sentRequests = HospitalShareRequest::with('toTenant')
            ->where('from_tenant_id', $currentTenant->id)
            ->latest()
            ->get();

        // All accepted connections (both directions) — shown in Connected section
        $acceptedConnections = HospitalShareRequest::where(function ($q) use ($currentTenant) {
            $q->where('from_tenant_id', $currentTenant->id)
                ->orWhere('to_tenant_id', $currentTenant->id);
        })
            ->where('status', 'accepted')
            ->with(['fromTenant', 'toTenant'])
            ->latest()
            ->get()
            ->map(function ($req) use ($currentTenant) {
                $req->partner = $req->from_tenant_id === $currentTenant->id
                    ? $req->toTenant
                    : $req->fromTenant;

                return $req;
            });

        return view('hospital.dashboard.doctor_history', compact(
            'slug',
            'historyPatients',
            'hospitals',
            'requestMap',
            'incomingRequests',
            'sentRequests',
            'acceptedConnections',
            'currentTenant'
        ));
    }

    public function sendShareRequest(string $slug, int $toTenantId): RedirectResponse
    {
        $currentTenant = app('tenant');

        if ($currentTenant->id === $toTenantId) {
            return back()->with('error', 'You cannot send a request to your own hospital.');
        }

        $existing = HospitalShareRequest::where(function ($q) use ($currentTenant, $toTenantId) {
            $q->where('from_tenant_id', $currentTenant->id)->where('to_tenant_id', $toTenantId);
        })->orWhere(function ($q) use ($currentTenant, $toTenantId) {
            $q->where('from_tenant_id', $toTenantId)->where('to_tenant_id', $currentTenant->id);
        })->first();

        if ($existing) {
            return back()->with('info', 'The request has already been sent.');
        }

        HospitalShareRequest::create([
            'from_tenant_id' => $currentTenant->id,
            'to_tenant_id' => $toTenantId,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Request sent successfully!');
    }

    public function acceptShareRequest(string $slug, int $requestId): RedirectResponse
    {
        $currentTenant = app('tenant');

        $req = HospitalShareRequest::where('id', $requestId)
            ->where('to_tenant_id', $currentTenant->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $req->update(['status' => 'accepted']);

        if ($fromTab = request('_from_tab')) {
            return redirect()->route('hospital.doctor.history', ['slug' => $slug, '_tab' => $fromTab])
                ->with('success', 'Request accepted successfully!');
        }

        return back()->with('success', 'Request accepted successfully!');
    }

    public function removeShareRequest(string $slug, int $requestId): RedirectResponse
    {
        $currentTenant = app('tenant');

        $req = HospitalShareRequest::where('id', $requestId)
            ->where(function ($q) use ($currentTenant) {
                $q->where('from_tenant_id', $currentTenant->id)
                    ->orWhere('to_tenant_id', $currentTenant->id);
            })
            ->firstOrFail();

        $req->delete();

        if ($fromTab = request('_from_tab')) {
            return redirect()->route('hospital.doctor.history', ['slug' => $slug, '_tab' => $fromTab])
                ->with('success', 'Request removed successfully!');
        }

        return back()->with('success', 'Request removed successfully!');
    }

    public function sharedPatientHistory(Request $request, string $slug): View|RedirectResponse
    {
        $currentTenant = app('tenant');
        $search = $request->input('search');
        $selectedId = (int) $request->input('patient_id');

        // Accepted partner tenant IDs
        $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($currentTenant) {
            $q->where('from_tenant_id', $currentTenant->id)
                ->orWhere('to_tenant_id', $currentTenant->id);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(fn ($r) => $r->from_tenant_id === $currentTenant->id ? $r->to_tenant_id : $r->from_tenant_id)
            ->toArray();

        $patient = null;
        $history = collect();
        $nameGroups = collect();

        if (count($partnerTenantIds) > 0) {
            $rawIds = $request->input('patient_ids', '');
            $patientIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));

            if ($patientIds) {
                // A name-group card was clicked — load combined history for all IDs
                $patient = Patient::withoutTenantScope()
                    ->with(['location', 'tenant:id,name'])
                    ->find($patientIds[0]);
                $history = $this->loadSharedExamHistoryForIds($patientIds);
            } elseif ($selectedId) {
                $patient = Patient::withoutTenantScope()
                    ->with(['location', 'tenant:id,name'])
                    ->whereIn('tenant_id', $partnerTenantIds)
                    ->find($selectedId);
                if ($patient) {
                    $history = $this->loadSharedExamHistoryForIds([$patient->id]);
                }
            } elseif ($search) {
                $candidates = Patient::withoutTenantScope()
                    ->with(['location', 'tenant:id,name'])
                    ->whereIn('tenant_id', $partnerTenantIds)
                    ->where(function ($q) use ($search) {
                        $q->where('patient_code', $search)
                            ->orWhere('contact_no', $search)
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    })
                    ->get();

                if ($candidates->count() === 1) {
                    $patient = $candidates->first();
                    $history = $this->loadSharedExamHistoryForIds([$patient->id]);
                } elseif ($candidates->count() > 1) {
                    $groups = $candidates->groupBy(
                        fn ($p) => mb_strtolower(trim($p->first_name.' '.$p->last_name))
                    );

                    if ($groups->count() === 1) {
                        // All same name → auto-merge
                        $patient = $candidates->sortByDesc('id')->first();
                        $history = $this->loadSharedExamHistoryForIds($candidates->pluck('id')->all());
                    } else {
                        $nameGroups = $groups->map(fn ($group) => (object) [
                            'display_name' => trim($group->first()->first_name.' '.$group->first()->last_name),
                            'patient_code' => $group->first()->patient_code,
                            'age' => $group->first()->age,
                            'gender' => $group->first()->gender,
                            'patient_ids' => $group->pluck('id')->implode(','),
                            'count' => $group->count(),
                        ])->values();
                    }
                }
            }
        }

        // Use the patient's own tenant for diagnosis master so IDs resolve correctly
        $masterTenantId = $patient?->tenant_id ?? $currentTenant->id;
        $diagnosisMasters = DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $masterTenantId)
            ->orderBy('id')
            ->get(['id', DB::raw('value as diagnosis')]);
        $dosageMasters = Dosage::all(['id', 'dosage'])->keyBy('id');
        $historyRoute = route('hospital.shared.patient.history', ['slug' => $slug]);

        return view(
            'hospital.patient.history',
            compact('patient', 'history', 'search', 'slug', 'diagnosisMasters', 'dosageMasters', 'nameGroups', 'historyRoute')
        );
    }

    private function loadSharedExamHistoryForIds(array $patientIds): Collection
    {
        $primaryExams = PrimaryExamination::withoutGlobalScope('tenant')
            ->with(['doctor' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function ($exam) {
                $exam->type = 'Primary Exam';
                $exam->color = 'primary';
                $exam->icon = 'bi-clipboard2-pulse';

                return $exam;
            });

        $secondaryExams = SecondaryExamination::withoutGlobalScope('tenant')
            ->with(['doctor' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function ($exam) {
                $exam->type = 'Secondary Exam';
                $exam->color = 'secondary';
                $exam->icon = 'bi-clipboard2-check';

                return $exam;
            });

        return $primaryExams->concat($secondaryExams)->sortByDesc('examined_at');
    }

    public function partnerHistory(Request $request, string $slug, int $partnerTenantId): View|RedirectResponse
    {
        $currentTenant = app('tenant');

        // Validate accepted share relationship
        $shareExists = HospitalShareRequest::where(function ($q) use ($currentTenant, $partnerTenantId) {
            $q->where('from_tenant_id', $currentTenant->id)->where('to_tenant_id', $partnerTenantId);
        })->orWhere(function ($q) use ($currentTenant, $partnerTenantId) {
            $q->where('from_tenant_id', $partnerTenantId)->where('to_tenant_id', $currentTenant->id);
        })->where('status', 'accepted')->exists();

        if (! $shareExists) {
            return redirect()->route('hospital.doctor.history', ['slug' => $slug])
                ->with('error', 'Is hospital ke saath koi accepted connection nahi hai.');
        }

        $partnerTenant = Tenant::findOrFail($partnerTenantId);

        $patientName = $request->input('patient_name');
        $doctorName = $request->input('doctor_name');
        $contactNo = $request->input('contact_no');
        $date = $request->input('date');

        $allPatients = Patient::withoutTenantScope()
            ->with([
                'doctor' => fn ($q) => $q->withoutGlobalScope('tenant'),
                'caseType' => fn ($q) => $q->withoutGlobalScope('tenant'),
            ])
            ->where('tenant_id', $partnerTenantId)
            ->where(function ($q) {
                $q->whereNotNull('primary_done_at')->orWhereNotNull('secondary_done_at');
            })
            ->when($patientName, fn ($q) => $q->where(function ($q2) use ($patientName) {
                $q2->where('first_name', 'like', "%{$patientName}%")
                    ->orWhere('last_name', 'like', "%{$patientName}%");
            }))
            ->when($doctorName, fn ($q) => $q->whereHas(
                'doctor',
                fn ($q2) => $q2->withoutGlobalScope('tenant')->where('name', 'like', "%{$doctorName}%")
            ))
            ->when($contactNo, fn ($q) => $q->where('contact_no', 'like', "%{$contactNo}%"))
            ->when($date, fn ($q) => $q->whereDate('appointment_date', $date))
            ->latest('appointment_date')
            ->get();

        // Group by (name + contact_no) so the same person registered multiple times
        // appears as a single row; all their patient_ids are stored for the View link.
        $grouped = $allPatients
            ->groupBy(fn ($p) => mb_strtolower(trim($p->first_name.' '.$p->last_name)).'|'.$p->contact_no)
            ->map(function ($group) {
                $rep = $group->sortByDesc('id')->first();
                $rep->all_patient_ids = $group->pluck('id')->implode(',');

                return $rep;
            })
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $partnerPatients = new LengthAwarePaginator(
            $grouped->slice(($page - 1) * $perPage, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('hospital.dashboard.partner_history', compact(
            'slug',
            'partnerTenant',
            'partnerPatients',
            'patientName',
            'doctorName',
            'contactNo',
            'date'
        ));
    }

    public function getHospitalDetails(string $slug, int $id): JsonResponse
    {
        $hospital = Tenant::findOrFail($id);

        // BelongsToTenant scope Role model par bhi hai, isliye whereHas ke andar bhi
        // withoutGlobalScope('tenant') lagana zaroori hai warna dusre hospitals ka count 0 aata hai.
        $doctorsCount = HospitalUser::withoutTenantScope()
            ->where('tenant_id', $id)
            ->whereHas('role', fn ($q) => $q->withoutGlobalScope('tenant')->where('slug', 'doctor'))
            ->count();

        // Staff = hospital ke saare users (all roles)
        $staffCount = HospitalUser::withoutTenantScope()
            ->where('tenant_id', $id)
            ->count();

        $patientsCount = Patient::withoutTenantScope()
            ->where('tenant_id', $id)
            ->count();

        return response()->json([
            'name' => $hospital->name,
            'city' => $hospital->city ?? 'N/A',
            'state' => $hospital->state ?? 'N/A',
            'admin_email' => $hospital->admin_email ?? 'N/A',
            'doctors_count' => $doctorsCount,
            'receptionists_count' => $staffCount,
            'patients_count' => $patientsCount,
        ]);
    }
}
