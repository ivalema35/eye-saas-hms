<?php

namespace App\Http\Controllers\Api;

use App\Exports\PatientReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Platform\MasterCity;
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

        // Full-dataset SQL sum, not a per-page PHP sum — mirrors
        // Hospital\Report\ReportController::index() exactly (same rule
        // channelCounts() below already gets right; index() previously
        // summed only the current page). See
        // REPORTS_MODULE_WEB_PARITY_FIX_PLAN.md TASK 1.1.
        $totalCollection = (float) $this->buildQuery($request, true)
            ->where('type', 'walkin')
            ->whereDoesntHave('otAppointmentSource')
            ->sum('case_fee');

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

        // Doctors and OT assistants are kept as separate lists — mirrors
        // web's channel-conditional dropdown (doctors for every channel
        // except ot_appointment, which shows assistants instead). This
        // used to be one merged list; splitting it here matches
        // showChannel()'s already-correct behaviour below. See
        // REPORTS_MODULE_WEB_PARITY_FIX_PLAN.md TASK 1.8.
        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->whereHas('role', fn (Builder $q) => $q->where('slug', 'doctor'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $assistants = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->whereHas('role', fn (Builder $q) => $q->where('slug', 'ot_assistant'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $receptionists = HospitalUser::active()
            ->whereHas('role', fn (Builder $q) => $q->where('slug', 'receptionist'))
            ->orderBy('name')
            ->get(['id', 'name']);

        // MasterCity, country-scoped to the current tenant — mirrors
        // Hospital\Report\ReportController::showChannel() exactly. The old
        // Location-table lookup returned IDs from the wrong table:
        // patients.location_id is validated against tbl_master_cities, not
        // tbl_locations. See REPORTS_MODULE_WEB_PARITY_FIX_PLAN.md TASK 1.2.
        $hospitalCountry = app('tenant')->country;
        $locations = MasterCity::whereHas('state.country', fn ($q) => $q->where('name', $hospitalCountry))
            ->orderBy('name')
            ->get(['id', 'name']);

        $caseTypes = CaseType::query()->orderBy('case_type')->get(['id', 'case_type']);

        return response()->json([
            'success' => true,
            'data'    => [
                'doctors'       => $doctors,
                'assistants'    => $assistants,
                'receptionists' => $receptionists,
                'locations'     => $locations,
                'case_types'    => $caseTypes,
            ],
        ]);
    }

    /**
     * Channel cards (Round 3.5 gap-fill) — mirrors Hospital\Report\ReportController::index()'s
     * $channelCounts, which itself was added with the OT Workflow Upgrade. Counts respect
     * every filter except the channel itself, so switching cards doesn't reset other filters.
     */
    public function channelCounts(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_collection' => (float) $this->buildQuery($request, true)
                    ->where('type', 'walkin')
                    ->whereDoesntHave('otAppointmentSource')
                    ->sum('case_fee'),
                'ot_appointment' => $this->buildQuery($request, true)->whereHas('otAppointmentSource')->count(),
                'walkin' => $this->buildQuery($request, true)->where('type', 'walkin')->whereDoesntHave('otAppointmentSource')->count(),
                'phone' => $this->buildQuery($request, true)->where('type', 'phone')->count(),
            ],
        ]);
    }

    /**
     * Dedicated per-channel patient list (Round 3.5 gap-fill) — mirrors
     * Hospital\Report\ReportController::showChannel() exactly.
     */
    public function showChannel(Request $request, string $slug, string $channel): JsonResponse
    {
        $labels = [
            'ot_appointment' => 'OT Appointment Patients',
            'walkin' => 'Walk-in Patients',
            'phone' => 'Phone Appointment Patients',
        ];

        if (! array_key_exists($channel, $labels)) {
            return response()->json(['success' => false, 'message' => 'Unknown channel.'], 404);
        }

        $request->merge(['type' => $channel]);

        $patients = $this->buildQuery($request)
            ->orderByDesc('appointment_date')
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        $doctors = collect();
        $assistants = collect();

        if ($channel === 'ot_appointment') {
            $assistants = HospitalUser::active()->whereHas('role', fn (Builder $q) => $q->where('slug', 'ot_assistant'))->orderBy('name')->get(['id', 'name']);
        } else {
            $doctors = HospitalUser::active()->whereHas('role', fn (Builder $q) => $q->where('slug', 'doctor'))->orderBy('name')->get(['id', 'name']);
        }

        // receptionists / locations / case_types were missing from this
        // response even though filterData() already returned them —
        // channel drill-down needs the same filter set. See
        // REPORTS_MODULE_WEB_PARITY_FIX_PLAN.md TASK 1.9.
        $receptionists = HospitalUser::active()
            ->whereHas('role', fn (Builder $q) => $q->where('slug', 'receptionist'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $hospitalCountry = app('tenant')->country;
        $locations = MasterCity::whereHas('state.country', fn ($q) => $q->where('name', $hospitalCountry))
            ->orderBy('name')
            ->get(['id', 'name']);

        $caseTypes = CaseType::query()->orderBy('case_type')->get(['id', 'case_type']);

        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel,
                'label' => $labels[$channel],
                'patients' => collect($patients->items())->map(fn ($p) => $this->formatPatient($p))->values(),
                'meta' => ['current_page' => $patients->currentPage(), 'last_page' => $patients->lastPage(), 'total' => $patients->total(), 'per_page' => $patients->perPage()],
                'doctors' => $doctors,
                'assistants' => $assistants,
                'receptionists' => $receptionists,
                'locations' => $locations,
                'case_types' => $caseTypes,
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

    private function buildQuery(Request $request, bool $excludeChannelFilter = false): Builder
    {
        $query = Patient::query()->with(['doctor', 'reception', 'location', 'masterCity.district', 'masterCity.state', 'caseType', 'otAppointmentSource']);

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

        if (! $excludeChannelFilter) {
            $query->when($request->filled('type'), function (Builder $b) use ($request) {
                $type = (string) $request->input('type');
                if ($type === 'ot_appointment') {
                    $b->whereHas('otAppointmentSource');
                } elseif ($type === 'walkin' || $type === '0') {
                    $b->whereIn('type', ['walkin', '0'])->whereDoesntHave('otAppointmentSource');
                } elseif ($type === 'phone' || $type === '1') {
                    $b->whereIn('type', ['phone', '1']);
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
        // 'd M, Y h:i A' (date + time), not date-only — mirrors web's
        // display format exactly. See
        // REPORTS_MODULE_WEB_PARITY_FIX_PLAN.md TASK 1.5.
        $apptDate = $p->appointment_date
            ? $p->appointment_date->format('d M, Y h:i A')
            : ($p->created_at?->format('d M, Y h:i A') ?? '-');

        return [
            'id'               => $p->id,
            'patient_code'     => $p->patient_code ?? 'N/A',
            // full_name accessor (handles middle name); manual
            // first+last concatenation was dropping it. See TASK 1.6.
            'full_name'        => $p->full_name,
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
            // masterCity is the correct relation; location (tbl_locations)
            // is legacy and often unset. See TASK 1.3.
            'location'         => ['city' => $p->masterCity?->name ?? $p->location?->city ?? ''],
        ];
    }
}
