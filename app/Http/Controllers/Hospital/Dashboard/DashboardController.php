<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Foc;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\Patient;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
            if (in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)) {
                $selectedDoctorId = $user->id;
            }

            $primaryQueueQuery = Patient::with(['doctor', 'caseType', 'primaryExamination'])
                ->whereDate('appointment_date', $today);

            if ($selectedDoctorId) {
                $primaryQueueQuery->where('doctor_id', $selectedDoctorId);
            }

            $primaryQueue = $primaryQueueQuery
                ->latest()
                ->get()
                ->filter(fn (Patient $patient): bool => $patient->primary_done_at === null)
                ->map(function (Patient $patient): Patient {
                    $patient->display_doctor_index = $patient->doctor_patient_no
                        ? (($patient->doctor?->doctor_prefix ?? '')
                            ? $patient->doctor->doctor_prefix.'-'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT)
                            : '#'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT))
                        : '-';
                    $patient->display_wait_status = $patient->primary_done_at ? 'Done' : 'Waiting';

                    return $patient;
                })
                ->take(20)
                ->values();

            if ($isDoctorUser && $user) {
                $doctorName = $user->name;
                $doctorAssignedPatients = Patient::where('doctor_id', $user->id)
                    ->whereDate('appointment_date', $today)
                    ->count();
                $doctorPrimaryDone = Patient::where('doctor_id', $user->id)
                    ->whereDate('appointment_date', $today)
                    ->whereNull('primary_done_at')
                    ->count();
                $doctorSecondaryDone = Patient::where('doctor_id', $user->id)
                    ->whereDate('appointment_date', $today)
                    ->whereNotNull('primary_done_at')
                    ->whereNull('secondary_done_at')
                    ->count();

                $secondaryQueue = Patient::with(['doctor', 'caseType'])
                    ->where('doctor_id', $user->id)
                    ->whereDate('appointment_date', $today)
                    ->whereNotNull('primary_done_at')
                    ->whereNull('secondary_done_at')
                    ->latest()
                    ->take(20)
                    ->get()
                    ->map(function (Patient $patient): Patient {
                        $patient->display_doctor_index = $patient->doctor_patient_no
                            ? (($patient->doctor?->doctor_prefix ?? '')
                                ? $patient->doctor->doctor_prefix.'-'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT)
                                : '#'.str_pad($patient->doctor_patient_no, 3, '0', STR_PAD_LEFT))
                            : '-';
                        $patient->display_wait_status = $patient->secondary_done_at ? 'Done' : 'Waiting';

                        return $patient;
                    });
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
            $receptionistTodayPhone = Patient::whereDate('appointment_date', $today)
                ->where('type', 'phone')
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

        if (($isReceptionistUser || $isDoctorUser) && (
            $this->perm->can('opd.patient.register')
            || $this->perm->can('opd.exam.primary')
            || $this->perm->can('opd.exam.secondary')
        )) {
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
                'otBookings' => fn ($query) => $query->latest('id')->select('id', 'patient_id', 'ot_status'),
                'primaryExamination' => fn ($query) => $query->select('id', 'patient_id', 'examined_at', 'exam_data', 'dilation_time', 'updated_at'),
            ])
                ->leftJoin('tbl_slots', 'patients.slot_id', '=', 'tbl_slots.id')
                ->where('reception_id', $user->id)
                ->whereDate('appointment_date', $today)
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
                    'patients.primary_done_at',
                    'patients.secondary_done_at',
                    'patients.created_at',
                    'tbl_slots.slot_name as slot_name',
                ]);
        }

        if ($user && $user->role?->slug === 'doctor') {

            // ૧. બધા ડૉક્ટર્સ લાવો (પોતાના સિવાય)
            $doctorCards = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
                ->with('role:id,slug')
                ->orderBy('name')
                ->get(['id', 'role_id', 'name', 'doctor_type'])
                ->where('id', '!=', $user->id);

            // ૨. માત્ર ડૉક્ટરના ડેશબોર્ડ માટે જ આ નવું કાઉન્ટ લોજીક
            $doctorStatsById = Patient::whereDate('appointment_date', $today)
                ->whereIn('doctor_id', $doctorCards->pluck('id'))
                ->select('doctor_id')
                ->selectRaw('COUNT(*) as assigned_today')
                ->selectRaw('SUM(CASE WHEN primary_done_at IS NULL THEN 1 ELSE 0 END) as primary_count')
                ->selectRaw('SUM(CASE WHEN primary_done_at IS NOT NULL AND secondary_done_at IS NULL THEN 1 ELSE 0 END) as secondary_count')
                ->groupBy('doctor_id')
                ->get()
                ->keyBy('doctor_id');

            // ૩. કાર્ડ્સમાં આ નવો ડેટા સેટ કરો
            $doctorCards = $doctorCards->map(function ($doc) use ($doctorStatsById) {
                $stats = $doctorStatsById->get($doc->id);
                $doc->assigned_today = (int) ($stats->assigned_today ?? 0);
                $doc->primary_count = (int) ($stats->primary_count ?? 0);
                $doc->secondary_count = (int) ($stats->secondary_count ?? 0);

                return $doc;
            });

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
                'doctorCards'
            ));
        }

        // ── એડમિન, રીસેપ્શનિસ્ટ, એકાઉન્ટન્ટ, આસિસ્ટન્ટ અને OT_DOCTOR માટે જૂનો ઓરિજિનલ વ્યુ લોડ થશે ──
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
        ));

        // ... (તમારો ઉપરનો જૂનો બધો કોડ) ...

        // ── એડમિન, રીસેપ્શનિસ્ટ, એકાઉન્ટન્ટ, આસિસ્ટન્ટ અને OT_DOCTOR માટે જૂનો ઓરિજિનલ વ્યુ લોડ થશે ──
        return view('hospital.dashboard.index', compact(
            'slug',
            // ... બાકીના બધા વેરીએબલ્સ ...
            'receptionistTodayPatients',
        ));

    } // અહી તમારું index() ફંક્શન પૂરું થાય છે

    // ===============================================================
    // અહીંથી તમારો નવો History વાળો કોડ ચાલુ થાય છે
    // ===============================================================
    public function history()
    {
        $slug = request()->route('slug');
        $user = Auth::guard('hospital_user')->user();

        // સુરક્ષા: જો યુઝર ડૉક્ટર અથવા OT ડૉક્ટર ન હોય, તો તેમને પાછા ડેશબોર્ડ પર મોકલી દો
        if (! $user || ! in_array($user->role?->slug, ['doctor', 'ot_doctor'])) {
            return redirect()->route('hospital.dashboard', ['slug' => $slug]);
        }

        // ડેટાબેઝ ક્વેરી: આ ડૉક્ટરે અત્યાર સુધીમાં જેટલા પેશન્ટ એટેન્ડ કર્યા છે તે લાવો
        $historyPatients = Patient::with(['caseType'])
            ->where('doctor_id', $user->id)
            ->where(function ($query) {
                // શરત: જેનું પ્રાયમરી અથવા સેકન્ડરી થઈ ગયું હોય તેવા જ પેશન્ટ
                $query->whereNotNull('primary_done_at')
                    ->orWhereNotNull('secondary_done_at');
            })
            ->latest('appointment_date') // નવા પેશન્ટ ઉપર દેખાય તે માટે
            ->paginate(20); // એક પેજ પર ૨૦ પેશન્ટ બતાવશે

        // ડેટાને વ્યુ (View) ફાઈલમાં મોકલો
        return view('hospital.dashboard.doctor_history', compact('slug', 'historyPatients'));
    }
}
