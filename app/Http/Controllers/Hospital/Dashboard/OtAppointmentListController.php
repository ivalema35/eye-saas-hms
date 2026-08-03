<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtAppointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard "OT Appointment" card drill-down — date-filtered appointment list.
 * Does not replace hospital.ot.appointments.index.
 */
class OtAppointmentListController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $appointments = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name', 'createdBy:id,name'])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        return view('hospital.dashboard.ot_appointment_list', [
            'slug' => $slug,
            'appointments' => $appointments,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvedDates(Request $request): array
    {
        $today = now()->toDateString();
        $start = $request->input('start_date') ?: $today;
        $end = $request->input('end_date') ?: $start;

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
