<?php

namespace App\Http\Controllers\Api;

use App\Exports\PatientReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportsApiController extends Controller
{
    // ── Report list (paginated) ───────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $patients = $this->buildQuery($request)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totalCollection = collect($patients->items())
            ->filter(fn (Patient $p) => $this->isWalkIn($p->type))
            ->sum(fn (Patient $p) => (float) ($p->case_fee ?? 0));

        return response()->json([
            'success'          => true,
            'total_collection' => $totalCollection,
            'data'             => collect($patients->items())->map(fn ($p) => $this->formatPatient($p))->values(),
            'meta'             => [
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
                'total'        => $patients->total(),
                'per_page'     => $patients->perPage(),
            ],
        ]);
    }

    // ── Filter dropdown data ──────────────────────────────────────────────────

    public function filterData(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function (Builder $r) {
                        $r->where(function (Builder $i) {
                            $i->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $receptionists = HospitalUser::active()
            ->whereHas('role', fn (Builder $q) => $q->where('slug', 'receptionist'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = Location::query()
            ->orderBy('city')
            ->get(['id', 'city', 'district', 'state'])
            ->map(fn ($l) => [
                'id'   => $l->id,
                'city' => $l->city,
                'name' => $l->name,
            ]);

        $caseTypes = CaseType::query()->orderBy('case_type')->get(['id', 'case_type']);

        return response()->json([
            'success' => true,
            'data'    => [
                'doctors'       => $doctors,
                'receptionists' => $receptionists,
                'locations'     => $locations,
                'case_types'    => $caseTypes,
            ],
        ]);
    }

    // ── Excel export ──────────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $patients = $this->buildQuery($request)->latest()->get();

        return Excel::download(
            new PatientReportExport($patients),
            'Patient_Report_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    // ── PDF export ────────────────────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $patients = $this->buildQuery($request)->latest()->get();

        $totalCollection = $patients
            ->filter(fn (Patient $p) => $this->isWalkIn($p->type))
            ->sum(fn (Patient $p) => (float) ($p->case_fee ?? 0));

        $pdf = Pdf::loadView('hospital.patients.reports.pdf', [
            'patients'        => $patients,
            'totalCollection' => $totalCollection,
            'filters'         => $request->all(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Patient_Report_'.now()->format('Y-m-d').'.pdf');
    }

    // ── Shared query builder (mirrors ReportController@buildQuery exactly) ────

    private function buildQuery(Request $request): Builder
    {
        $query = Patient::query()->with(['doctor', 'reception', 'location', 'caseType']);

        $query->when($request->filled('reception_id'), fn (Builder $b) =>
            $b->where('reception_id', (int) $request->input('reception_id'))
        );

        $query->when($request->filled('doctor_id'), fn (Builder $b) =>
            $b->where('doctor_id', (int) $request->input('doctor_id'))
        );

        $query->when(
            $request->filled('location_id') && ! $request->filled('reception_id'),
            fn (Builder $b) => $b->where('location_id', (int) $request->input('location_id'))
        );

        $query->when(
            $request->filled('case_id') && ! $request->filled('reception_id'),
            fn (Builder $b) => $b->where('case_id', (int) $request->input('case_id'))
        );

        $query->when($request->filled('type'), function (Builder $b) use ($request) {
            $type = (string) $request->input('type');
            if ($type === 'walkin' || $type === '0') {
                $b->whereIn('type', ['walkin', '0']);
            } elseif ($type === 'phone' || $type === '1') {
                $b->whereIn('type', ['phone', '1']);
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
            $query->whereBetween('appointment_date', [$dates[0], $dates[1]]);
        } else {
            $query->whereDate('appointment_date', $dates[0]);
        }
    }

    private function isWalkIn(?string $type): bool
    {
        return in_array((string) $type, ['walkin', '0'], true);
    }

    private function resolveCaseTypeLabel(?string $raw): string
    {
        $val = strtolower(trim((string) $raw));
        if (str_contains($val, 'general')) {
            return 'General';
        }
        if (str_contains($val, 'old')) {
            return 'Old';
        }
        if (str_contains($val, 'new')) {
            return 'New';
        }

        return ($raw !== null && $raw !== '') ? $raw : '-';
    }

    private function formatPatient(Patient $p): array
    {
        $apptDate = $p->appointment_date
            ? $p->appointment_date->format('d M, Y')
            : ($p->created_at?->format('d M, Y') ?? '-');

        return [
            'id'               => $p->id,
            'patient_code'     => $p->patient_code ?? 'N/A',
            'full_name'        => trim(($p->first_name ?? '').' '.($p->last_name ?? '')),
            'appointment_date' => $apptDate,
            'created_at'       => $p->created_at?->toISOString(),
            'contact_no'       => $p->contact_no ?? '-',
            'age'              => $p->age,
            'type'             => $p->type,
            'type_label'       => $this->isWalkIn($p->type) ? 'Walk-in' : 'Phone',
            'case_fee'         => (float) ($p->case_fee ?? 0),
            'case_type_label'  => $this->resolveCaseTypeLabel($p->caseType?->case_type),
            'doctor'           => $p->doctor ? ['name' => $p->doctor->name] : null,
            'receptionist'     => $p->reception ? ['name' => $p->reception->name] : null,
            'location'         => $p->location ? ['city' => $p->location->city ?? $p->location->name] : null,
        ];
    }
}
