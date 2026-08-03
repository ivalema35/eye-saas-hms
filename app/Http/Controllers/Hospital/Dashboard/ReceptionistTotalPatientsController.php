<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Receptionist "Total Patients" drill-down — collection + date-range list.
 * Does not replace patients.index or reports routes.
 */
class ReceptionistTotalPatientsController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $baseQuery = Patient::query()
            ->with([
                'doctor:id,name',
                'reception:id,name',
                'masterCity:id,name',
                'location',
                'caseType:id,case_type',
                'otAppointmentSource:id,converted_patient_id',
            ])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate);

        $collection = (float) (clone $baseQuery)->sum('case_fee');

        $patients = (clone $baseQuery)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        return view('hospital.dashboard.receptionist_total_patients', [
            'slug' => $slug,
            'patients' => $patients,
            'collection' => $collection,
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
