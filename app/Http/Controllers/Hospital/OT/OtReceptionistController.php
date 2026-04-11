<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OtReceptionistController extends Controller
{
    public function dashboard(string $slug): View
    {
        $tenantId = (int) app('tenant')->id;

        $totalOtToday = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->whereDate('surgery_date', today())
            ->count();

        $pendingCounselling = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->whereDate('surgery_date', '>=', today())
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ot_counselling')
                    ->whereColumn('ot_counselling.ot_booking_id', 'ot_bookings.id');
            })
            ->count();

        $readyForSurgery = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->where('ot_status', 'ready')
            ->count();

        return view('hospital.ot.dashboard', [
            'slug' => $slug,
            'stats' => [
                'total_ot_today' => $totalOtToday,
                'pending_counselling' => $pendingCounselling,
                'ready_for_surgery' => $readyForSurgery,
            ],
        ]);
    }
}
