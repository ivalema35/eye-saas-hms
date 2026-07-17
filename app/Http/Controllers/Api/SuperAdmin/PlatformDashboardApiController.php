<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\Payment;
use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformDashboardApiController extends Controller
{
    // GET /api/v1/super/dashboard
    public function index(Request $request): JsonResponse
    {
        $now = Carbon::now();

        $stats = [
            'total_hospitals'    => Tenant::count(),
            'active'             => Tenant::where('status', 'active')->count(),
            'trial'              => Tenant::where('status', 'trial')->count(),
            'grace'              => Tenant::where('status', 'grace')->count(),
            'suspended'          => Tenant::where('status', 'suspended')->count(),
            'inactive'           => Tenant::where('status', 'inactive')->count(),
            'monthly_revenue'    => (float) Payment::where('status', 'success')
                ->whereMonth('paid_at', $now->month)
                ->whereYear('paid_at', $now->year)
                ->sum('amount'),
            'expiring_this_week' => Tenant::whereIn('status', ['active', 'trial'])
                ->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(7)])
                ->count(),
        ];

        $revenueMonths  = [];
        $revenueAmounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $revenueMonths[]  = $month->format('M Y');
            $revenueAmounts[] = (int) Payment::where('status', 'success')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');
        }

        $regMonths  = [];
        $regCounts  = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $regMonths[]  = $month->format('M Y');
            $regCounts[]  = Tenant::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        }

        $recentHospitals = Tenant::latest()->take(8)->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'slug'        => $t->slug,
                'admin_name'  => $t->admin_name,
                'city'        => $t->city,
                'status'      => $t->status,
                'trial_ends_at' => $t->trial_ends_at,
                'created_at'  => $t->created_at,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'                 => $stats,
                'revenue_trend'         => ['months' => $revenueMonths,  'amounts' => $revenueAmounts],
                'registrations_trend'   => ['months' => $regMonths,      'counts'  => $regCounts],
                'subscription_cycles'   => [
                    'monthly'   => Subscription::where('status', 'active')->where('cycle', 'monthly')->count(),
                    'quarterly' => Subscription::where('status', 'active')->where('cycle', 'quarterly')->count(),
                    'yearly'    => Subscription::where('status', 'active')->where('cycle', 'yearly')->count(),
                ],
                'recent_hospitals' => $recentHospitals,
            ],
        ]);
    }
}
