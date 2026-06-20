<?php

namespace App\Http\Controllers\Hospital\Report;

use App\Exports\PatientReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\Patient;
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

        $patients = $this->buildQuery($request)
            ->latest()
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        $totalCollection = collect($patients->items())
            ->filter(fn (Patient $patient): bool => $this->isWalkInType($patient->type))
            ->sum('case_fee');

        $doctors = HospitalUser::active()
            ->where('tenant_id', app('tenant')->id)
            ->where(function (Builder $query): void {
                $query->whereNotNull('doctor_type')
                    ->orWhereHas('role', function (Builder $roleQuery): void {
                        $roleQuery->where(function (Builder $innerQuery): void {
                            $innerQuery->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $receptions = HospitalUser::query()
            ->active()
            ->whereHas('role', fn (Builder $query) => $query->where('slug', 'receptionist'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = Location::query()->orderBy('city')->get(['id', 'city', 'district', 'state']);
        $cases = CaseType::query()->orderBy('case_type')->get(['id', 'case_type']);

        return view('hospital.patients.reports.index', compact(
            'patients',
            'totalCollection',
            'doctors',
            'receptions',
            'locations',
            'cases',
            'slug'
        ));
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

    private function buildQuery(Request $request): Builder
    {
        $query = Patient::query()->with(['doctor', 'reception', 'location', 'masterCity', 'caseType']);

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

        $query->when($request->filled('type'), function (Builder $builder) use ($request): void {
            $type = (string) $request->input('type');

            if ($type === 'walkin' || $type === '0') {
                $builder->whereIn('type', ['walkin', '0']);
            }

            if ($type === 'phone' || $type === '1') {
                $builder->whereIn('type', ['phone', '1']);
            }
        });

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
