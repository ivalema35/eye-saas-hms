<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Hospital admin dashboard "Report" / "Today Patients" drill-down.
 * Today's OPD patients with date-range + reception + doctor filters and Excel export.
 */
class AdminDashboardPatientsController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $patients = $this->baseQuery($request, $startDate, $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        return view('hospital.dashboard.admin_patients_report', [
            'slug' => $slug,
            'patients' => $patients,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'receptionId' => $request->filled('reception_id') ? (int) $request->input('reception_id') : null,
            'doctorId' => $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null,
            'receptions' => $this->receptionOptions(),
            'doctors' => $this->doctorOptions(),
        ]);
    }

    public function export(Request $request, string $slug): BinaryFileResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $patients = $this->baseQuery($request, $startDate, $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        $rows = $patients->map(function (Patient $patient): array {
            return [
                $patient->full_name,
                $patient->cityName ?: '-',
                $patient->contact_no ?: '-',
                $patient->age ?: '-',
                $this->formatTime($patient),
                $patient->slot?->slot_name ?: '-',
                $patient->doctor?->name ? 'Dr. '.$patient->doctor->name : '-',
                $patient->doctor_patient_no ?: '-',
                $patient->reception?->name ?: '-',
                $patient->appointment_date?->format('d M Y') ?? '-',
            ];
        })->all();

        $filename = 'Admin_Patient_Report_'.$startDate.'_to_'.$endDate.'.xlsx';

        return Excel::download(new GenericArrayExport($rows, [
            'Patient Name',
            'City',
            'Contact',
            'Age',
            'Time',
            'Time Slot',
            'Doctor',
            'Dr. Index',
            'Reception',
            'Date',
        ]), $filename);
    }

    private function baseQuery(Request $request, string $startDate, string $endDate): Builder
    {
        return Patient::query()
            ->with([
                'doctor:id,name',
                'reception:id,name',
                'masterCity:id,name',
                'location',
                'slot:id,slot_name',
            ])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->when($request->filled('reception_id'), fn (Builder $q) => $q->where('reception_id', (int) $request->input('reception_id')))
            ->when($request->filled('doctor_id'), fn (Builder $q) => $q->where('doctor_id', (int) $request->input('doctor_id')));
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

    private function receptionOptions()
    {
        return HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function doctorOptions()
    {
        return HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function formatTime(Patient $patient): string
    {
        $time = $patient->checked_in_at ?? $patient->created_at;

        return $time ? $time->format('h:i A') : '-';
    }
}
