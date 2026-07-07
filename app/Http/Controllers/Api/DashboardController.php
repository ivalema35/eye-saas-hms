<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Foc;
use App\Models\Hospital\HospitalSetting;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard Stats
     *
     * GET /api/v1/{slug}/admin/dashboard
     * Auth: sanctum + subscription.active
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        // ── OPD Stats ──────────────────────────────────────────────────
        $todayPatients = Patient::whereDate('appointment_date', today())->count();

        $primaryQueueCount = Patient::whereDate('appointment_date', today())
            ->whereNull('primary_done_at')
            ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
            ->count();

        $secondaryQueueCount = Patient::whereDate('appointment_date', today())
            ->whereNotNull('primary_done_at')
            ->whereNull('secondary_done_at')
            ->count();

        $todayWalkin = Patient::whereDate('appointment_date', today())
            ->where('type', 'walkin')
            ->count();

        $todayPhone = Patient::whereDate('appointment_date', today())
            ->where('type', 'phone')
            ->count();

        // ── Revenue ────────────────────────────────────────────────────
        $revenueToday = (float) Patient::whereDate('appointment_date', today())->sum('case_fee');
        $revenueMonth = (float) Patient::whereMonth('appointment_date', now()->month)
            ->whereYear('appointment_date', now()->year)
            ->sum('case_fee');
        $revenueYear = (float) Patient::whereYear('appointment_date', now()->year)->sum('case_fee');

        // ── OT Stats ───────────────────────────────────────────────────
        $otToday = $otOperated = $otPending = 0;
        try {
            $otToday    = OtBooking::whereDate('surgery_date', today())->count();
            $otOperated = OtBooking::whereDate('surgery_date', today())
                ->where('ot_status', OtBooking::STATUS_OPERATED)
                ->count();
            $otPending  = OtBooking::whereDate('surgery_date', today())
                ->whereNotIn('ot_status', [OtBooking::STATUS_OPERATED, OtBooking::STATUS_DISCHARGED])
                ->count();
        } catch (\Throwable) {}

        // ── Staff ──────────────────────────────────────────────────────
        $totalDoctors    = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'doctor'))->count();
        $totalReceptions = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))->count();

        // ── Subscription days remaining ────────────────────────────────
        $subscriptionDaysLeft = null;
        try {
            if ($tenant->trial_ends_at) {
                $subscriptionDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
            }
        } catch (\Throwable) {}

        // ── Primary Queue (top 20) ─────────────────────────────────────
        $primaryQueue = Patient::with(['doctor:id,name,doctor_prefix'])
            ->whereDate('appointment_date', today())
            ->whereNull('primary_done_at')
            ->where(fn ($q) => $q->where('type', '!=', 'phone')->orWhereNotNull('checked_in_at'))
            ->orderBy('doctor_patient_no')
            ->take(20)
            ->get()
            ->map(fn ($p) => [
                'id'               => $p->id,
                'patient_code'     => $p->patient_code,
                'full_name'        => $p->full_name,
                'age'              => $p->age,
                'gender'           => $p->gender,
                'doctor_patient_no' => $p->doctor_patient_no,
                'checked_in_at'    => $p->checked_in_at?->toISOString(),
                'registered_at'    => $p->created_at?->toISOString(),
                'doctor_name'      => $p->doctor?->name,
                'doctor_prefix'    => $p->doctor?->doctor_prefix,
            ]);

        // ── Receptionists Performance ──────────────────────────────────
        $receptionists = HospitalUser::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))
            ->get()
            ->map(function ($rec) {
                $count = Patient::whereDate('appointment_date', today())
                    ->where('reception_id', $rec->id)
                    ->count();
                $gross = (float) Patient::whereDate('appointment_date', today())
                    ->where('reception_id', $rec->id)
                    ->sum('case_fee');
                $foc = 0.0;
                try {
                    $foc = (float) Foc::where('reception_id', $rec->id)
                        ->whereHas('patient', fn ($q) => $q->whereDate('appointment_date', today()))
                        ->where('status', 'accepted')
                        ->sum('foc_fee');
                } catch (\Throwable) {}

                return [
                    'id'          => $rec->id,
                    'name'        => $rec->name,
                    'today_count' => $count,
                    'today_gross' => $gross,
                    'today_foc'   => $foc,
                    'today_net'   => $gross - $foc,
                ];
            });

        // ── Wait thresholds (from hospital settings KV store) ──────────
        $thresholds = [
            'r_green'  => (int) HospitalSetting::get('wait_green_max', 30),
            'r_orange' => (int) HospitalSetting::get('wait_orange_max', 60),
            'r_red'    => (int) HospitalSetting::get('wait_red_max', 120),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'subscription_days_left' => $subscriptionDaysLeft,
                'today_patients'         => $todayPatients,
                'primary_queue_count'    => $primaryQueueCount,
                'secondary_queue_count'  => $secondaryQueueCount,
                'today_walkin'           => $todayWalkin,
                'today_phone'            => $todayPhone,
                'today_registrations'    => $todayWalkin + $todayPhone,
                'revenue_today'          => $revenueToday,
                'revenue_month'          => $revenueMonth,
                'revenue_year'           => $revenueYear,
                'ot_today'               => $otToday,
                'ot_operated'            => $otOperated,
                'ot_pending'             => $otPending,
                'total_staff'            => $totalDoctors + $totalReceptions,
                'primary_queue'          => $primaryQueue,
                'receptionists'          => $receptionists,
                'wait_thresholds'        => $thresholds,
            ],
        ]);
    }
}
