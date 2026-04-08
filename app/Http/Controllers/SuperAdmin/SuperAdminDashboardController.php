<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\Payment;
use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Carbon\Carbon;
use Illuminate\View\View;

/**
 * SuperAdmin Dashboard Controller
 *
 * Platform-wide overview: total tenants, revenue, active subscriptions, etc.
 * Accessible at: /superadmin/dashboard
 */
class SuperAdminDashboardController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();

        $totalHospitals = Tenant::count();
        $activeCount    = Tenant::where('status', 'active')->count();
        $trialCount     = Tenant::where('status', 'trial')->count();
        $graceCount     = Tenant::where('status', 'grace')->count();
        $suspendedCount = Tenant::where('status', 'suspended')->count();
        $inactiveCount  = Tenant::where('status', 'inactive')->count();

        $monthlyRevenue = Payment::where('status', 'success')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('amount');

        $expiringThisWeek = Tenant::whereIn('status', ['active', 'trial'])
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(7)])
            ->count();

        /** @var array<string> $revenueMonths */
        $revenueMonths = [];

        /** @var array<int> $revenueAmounts */
        $revenueAmounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $revenueMonths[]  = $month->format('M Y');
            $revenueAmounts[] = (int) Payment::where('status', 'success')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');
        }

        $regMonths = [];
        $regCounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $regMonths[] = $month->format('M Y');
            $regCounts[] = Tenant::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        }

        $cycleMonthly = Subscription::where('status', 'active')
            ->where('cycle', 'monthly')
            ->count();

        $cycleQuarterly = Subscription::where('status', 'active')
            ->where('cycle', 'quarterly')
            ->count();

        $cycleYearly = Subscription::where('status', 'active')
            ->where('cycle', 'yearly')
            ->count();

        $recentHospitals = Tenant::latest()->take(8)->get();

        return view('superadmin.dashboard', compact(
            'totalHospitals',
            'activeCount',
            'trialCount',
            'graceCount',
            'suspendedCount',
            'inactiveCount',
            'monthlyRevenue',
            'expiringThisWeek',
            'revenueMonths',
            'revenueAmounts',
            'regMonths',
            'regCounts',
            'cycleMonthly',
            'cycleQuarterly',
            'cycleYearly',
            'recentHospitals'
        ));
    }
}
