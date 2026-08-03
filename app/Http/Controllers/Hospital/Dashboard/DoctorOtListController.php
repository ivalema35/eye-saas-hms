<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Doctor dashboard OT drill-down — bookings by operating doctor + date range.
 */
class DoctorOtListController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $doctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;

        $query = OtBooking::query()
            ->with([
                'patient:id,first_name,middle_name,last_name,contact_no,age',
                'otDoctor:id,name',
                'otAssistant:id,name',
            ])
            ->whereDate('surgery_date', '>=', $startDate)
            ->whereDate('surgery_date', '<=', $endDate);

        if ($doctorId) {
            $query->where('ot_doctor_id', $doctorId);
        }

        $bookings = $query
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        $doctor = $doctorId
            ? HospitalUser::query()->where('id', $doctorId)->first(['id', 'name'])
            : null;

        $doctors = HospitalUser::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('hospital.dashboard.doctor_ot_list', [
            'slug' => $slug,
            'bookings' => $bookings,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'doctor' => $doctor,
            'doctorId' => $doctorId,
            'doctors' => $doctors,
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
