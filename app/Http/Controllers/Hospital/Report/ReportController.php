<?php

namespace App\Http\Controllers\Hospital\Report;

use App\Exports\PatientReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Platform\MasterCity;
use App\Services\Auth\RolePermissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Hospital Reports Controller
 *
 * Advanced patient report filters with Excel/PDF export.
 * Access: Hospital Admin only.
 */
class ReportController extends Controller
{
    public function __construct(private readonly RolePermissionService $permissionService) {}

    public function index(Request $request, string $slug): View
    {
        $this->authorizePermission('reports.view');

        $totalCollection = $this->buildQuery($request, true)
            ->where('type', 'walkin')
            ->whereDoesntHave('otAppointmentSource')
            ->sum('case_fee');

        // Channel cards — counts respect every filter except the channel itself,
        // so switching cards doesn't reset date range / doctor / etc.
        $channelCounts = [
            'ot_appointment' => $this->buildQuery($request, true)->whereHas('otAppointmentSource')->count(),
            'walkin' => $this->buildQuery($request, true)->where('type', 'walkin')->whereDoesntHave('otAppointmentSource')->count(),
            'phone' => $this->buildQuery($request, true)->where('type', 'phone')->count(),
        ];

        return view('hospital.patients.reports.index', compact('totalCollection', 'channelCounts', 'slug'));
    }

    /**
     * Dedicated per-channel patient list (OT Appointment / Walk-in / Phone),
     * navigated to from the channel cards on the main report page — mirrors
     * the OT Reports pattern (hospital.reports.ot.show).
     */
    public function showChannel(Request $request, string $slug, string $channel): View
    {
        $this->authorizePermission('reports.view');

        $labels = [
            'ot_appointment' => 'OT Appointment Patients',
            'walkin' => 'Walk-in Patients',
            'phone' => 'Phone Appointment Patients',
        ];

        abort_unless(array_key_exists($channel, $labels), 404);

        $request->merge(['type' => $channel]);

        $patients = $this->buildQuery($request)
            ->orderByDesc('appointment_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $receptions = HospitalUser::query()
            ->active()
            ->whereHas('role', fn (Builder $query) => $query->where('slug', 'receptionist'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $doctors = collect();
        $assistants = collect();

        if ($channel === 'ot_appointment') {
            $assistants = HospitalUser::query()
                ->active()
                ->whereHas('role', fn (Builder $query) => $query->where('slug', 'ot_assistant'))
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $doctors = HospitalUser::query()
                ->active()
                ->whereHas('role', fn (Builder $query) => $query->where('slug', 'doctor'))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $hospitalCountry = auth('hospital_user')->user()->tenant->country;
        $locations = MasterCity::whereHas('state.country', fn ($q) => $q->where('name', $hospitalCountry))
            ->orderBy('name')
            ->get(['id', 'name']);
        $cases = CaseType::query()->orderBy('case_type')->get(['id', 'case_type']);

        return view('hospital.patients.reports.channel', [
            'slug' => $slug,
            'channel' => $channel,
            'label' => $labels[$channel],
            'patients' => $patients,
            'receptions' => $receptions,
            'doctors' => $doctors,
            'assistants' => $assistants,
            'locations' => $locations,
            'cases' => $cases,
        ]);
    }

    public function exportExcel(Request $request, string $slug)
    {
        $this->authorizePermission('reports.export');

        $patients = $this->buildQuery($request)
            ->latest()
            ->get();

        return Excel::download(
            new PatientReportExport($patients),
            'Patient_Report_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportPdf(Request $request, string $slug)
    {
        $this->authorizePermission('reports.export');

        $patients = $this->buildQuery($request)
            ->latest()
            ->get();

        $totalCollection = $patients
            ->filter(fn (Patient $patient): bool => $this->isWalkInType($patient->type))
            ->sum('case_fee');

        $pdf = Pdf::loadView('hospital.patients.reports.pdf', [
            'patients' => $patients,
            'totalCollection' => $totalCollection,
            'filters' => $request->all(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Patient_Report_'.now()->format('Y-m-d').'.pdf');
    }

    private function buildQuery(Request $request, bool $excludeChannelFilter = false): Builder
    {
        $query = Patient::query()->with(['doctor', 'reception', 'location', 'masterCity.district', 'masterCity.state', 'caseType', 'otAppointmentSource']);

        $query->when($request->filled('reception_id'), function (Builder $builder) use ($request): void {
            $builder->where('reception_id', (int) $request->input('reception_id'));
        });

        $query->when($request->filled('doctor_id'), function (Builder $builder) use ($request): void {
            $builder->where('doctor_id', (int) $request->input('doctor_id'));
        });

        $query->when(
            $request->filled('location_id') && ! $request->filled('reception_id'),
            function (Builder $builder) use ($request): void {
                $builder->where('location_id', (int) $request->input('location_id'));
            }
        );

        $query->when(
            $request->filled('case_id') && ! $request->filled('reception_id'),
            function (Builder $builder) use ($request): void {
                $builder->where('case_id', (int) $request->input('case_id'));
            }
        );

        if (! $excludeChannelFilter) {
            $query->when($request->filled('type'), function (Builder $builder) use ($request): void {
                $type = (string) $request->input('type');

                if ($type === 'ot_appointment') {
                    $builder->whereHas('otAppointmentSource');
                } elseif ($type === 'walkin' || $type === '0') {
                    $builder->whereIn('type', ['walkin', '0'])->whereDoesntHave('otAppointmentSource');
                } elseif ($type === 'phone' || $type === '1') {
                    $builder->whereIn('type', ['phone', '1']);
                }
            });
        }

        $this->applyDateRange($query, (string) $request->input('date_range', ''));

        return $query;
    }

    private function applyDateRange(Builder $query, string $dateRange): void
    {
        if ($dateRange === '') {
            return;
        }

        $dates = explode(' to ', $dateRange);

        if (count($dates) === 2) {
            $query->whereBetween('appointment_date', [
                $dates[0],
                $dates[1],
            ]);

            return;
        }

        $query->whereDate('appointment_date', $dates[0]);
    }

    private function authorizePermission(string $permissionKey): void
    {
        abort_unless($this->permissionService->can($permissionKey), 403, 'Access denied.');
    }

    private function isWalkInType(?string $type): bool
    {
        return in_array((string) $type, ['walkin', '0'], true);
    }
}
