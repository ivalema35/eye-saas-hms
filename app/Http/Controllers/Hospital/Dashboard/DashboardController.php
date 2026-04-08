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
        $slug   = request()->route('slug');
        $tenant = app('tenant');
        $user   = Auth::guard('hospital_user')->user();
        $today  = now()->toDateString();

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
        $todayPatients  = null;
        $pendingExams   = null;
        $todayPrimary   = null;
        $todaySecondary = null;
        $primaryQueue   = null;

        if ($this->perm->can('opd.exam.primary')) {
            $todayPatients  = Patient::whereDate('appointment_date', $today)->count();
            $pendingExams   = Patient::whereDate('appointment_date', $today)
                ->whereNull('primary_done_at')
                ->count();
            $todayPrimary   = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('primary_done_at')
                ->count();
            $todaySecondary = Patient::whereDate('appointment_date', $today)
                ->whereNotNull('secondary_done_at')
                ->count();

            // Show the personal exam queue when the user is assigned as a doctor
            if ($user?->doctor_id || $user?->id) {
                $primaryQueue = Patient::where('doctor_id', $user->id)
                    ->whereDate('appointment_date', $today)
                    ->whereNull('primary_done_at')
                    ->orderBy('created_at')
                    ->limit(20)
                    ->get();
            }
        }

        // ── Reception Data (opd.patient.register / opd.patient.register_phone) ────────────
        // Today's registrations and outstanding FOC requests.
        $todayRegistrations = null;
        $focPending         = null;
        $todayWalkin        = null;
        $todayPhone         = null;

        if ($this->perm->can('opd.patient.register')) {
            $todayRegistrations = Patient::whereDate('appointment_date', $today)->count();
            $focPending         = Foc::where('accepted', false)->count();
            $todayWalkin        = Patient::whereDate('appointment_date', $today)
                ->where('type', 'walkin')
                ->count();
            $todayPhone         = Patient::whereDate('appointment_date', $today)
                ->where('type', 'phone')
                ->count();
        }

        // ── Financial Data (report.revenue) ──────────────────────────────────
        // Revenue aggregates across today / month / year.
        $revenueToday = null;
        $revenueMonth = null;
        $revenueYear  = null;

        if ($this->perm->can('opd.reports.view')) {
            $revenueToday = (float) Patient::whereDate('appointment_date', $today)
                ->sum('case_fee');
            $revenueMonth = (float) Patient::whereMonth('appointment_date', now()->month)
                ->whereYear('appointment_date', now()->year)
                ->sum('case_fee');
            $revenueYear  = (float) Patient::whereYear('appointment_date', now()->year)
                ->sum('case_fee');
        }

        // ── OT Data (ot.booking.view) ───────────────────────────────────────────
        // Today's OT surgery schedule overview.
        $otToday    = null;
        $otOperated = null;
        $otPending  = null;

        if ($this->perm->can('ot.patient.list')) {
            $otToday    = OtBooking::whereDate('surgery_date', $today)->count();
            $otOperated = OtBooking::whereDate('surgery_date', $today)
                ->where('ot_status', 'operated')
                ->count();
            $otPending  = OtBooking::whereDate('surgery_date', $today)
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

            $pendingFocRequests = Foc::with(['patient', 'doctor'])
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
        $receptionists    = null;
        $totalDoctors     = null;
        $totalReceptions  = null;

        if ($this->perm->can('master.doctors') || $this->perm->can('master.case_types')) {
            $totalDoctors    = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))->count();
            $totalReceptions = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))->count();

            $receptionists = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))
                ->get()
                ->map(function (HospitalUser $rec) use ($today): HospitalUser {
                    $base              = Patient::where('reception_id', $rec->id)->whereDate('appointment_date', $today);
                    $rec->today_count  = $base->count();
                    $rec->today_gross  = (float) $base->sum('case_fee');
                    $rec->today_foc    = (float) Foc::where('reception_id', $rec->id)
                        ->whereHas('patient', fn ($q) => $q->whereDate('appointment_date', $today))
                        ->where('accepted', true)
                        ->sum('foc_fee');
                    $rec->today_net    = $rec->today_gross - $rec->today_foc;

                    return $rec;
                });
        }

        return view('hospital.dashboard.index', compact(
            'slug',
            'tenant',
            'subscriptionDaysLeft',
            // Clinical
            'todayPatients',
            'pendingExams',
            'todayPrimary',
            'todaySecondary',
            'primaryQueue',
            // Reception
            'todayRegistrations',
            'focPending',
            'todayWalkin',
            'todayPhone',
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
        ));
    }
}
