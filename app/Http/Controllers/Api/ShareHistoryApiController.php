<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Models\Platform\HospitalShareRequest;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShareHistoryApiController extends Controller
{
    // ── Tab 1: Patient History (own + accepted partners) ──────────────

    public function patients(Request $request): JsonResponse
    {
        $currentTenant = app('tenant');
        $partnerIds    = $this->partnerTenantIds($currentTenant);
        $tenantIds     = array_merge([$currentTenant->id], $partnerIds);

        $query = Patient::withoutTenantScope()
            ->with([
                'doctor'  => fn($q) => $q->withoutGlobalScope('tenant')->select('id', 'name', 'tenant_id'),
                'tenant:id,name',
            ])
            ->whereIn('tenant_id', $tenantIds)
            ->where(fn($q) => $q->whereNotNull('primary_done_at')->orWhereNotNull('secondary_done_at'))
            ->whereHas('doctor.role', fn($q) => $q->withoutGlobalScope('tenant')->where('slug', 'doctor'));

        if ($v = $request->input('patient_name')) {
            $query->where(fn($q) => $q->where('first_name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%"));
        }
        if ($v = $request->input('doctor_name')) {
            $query->whereHas('doctor', fn($q) => $q->withoutGlobalScope('tenant')->where('name', 'like', "%{$v}%"));
        }
        if ($v = $request->input('contact_no')) {
            $query->where('contact_no', 'like', "%{$v}%");
        }
        if ($v = $request->input('date')) {
            $query->whereDate('appointment_date', $v);
        }

        $all = $query->latest('appointment_date')->get();

        // Deduplicate: same name + contact + tenant → one representative row
        $deduped = $all
            ->groupBy(fn($p) => mb_strtolower(trim("{$p->first_name} {$p->last_name}")) . '|' . $p->contact_no . '|' . $p->tenant_id)
            ->map(function ($group) use ($currentTenant) {
                $rep = $group->sortByDesc('id')->first();
                $rep->all_patient_ids = $group->pluck('id')->implode(',');
                $rep->is_own          = $rep->tenant_id === $currentTenant->id;
                return $rep;
            })
            ->values();

        [$perPage, $page] = [20, max(1, (int) $request->input('page', 1))];
        $paginated = new LengthAwarePaginator(
            $deduped->slice(($page - 1) * $perPage, $perPage)->values(),
            $deduped->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'data' => $paginated->items(),
                'meta' => [
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    // ── Tab 2: Hospital Directory ──────────────────────────────────────

    public function hospitals(Request $request, string $slug): JsonResponse
    {
        $currentTenant = app('tenant');

        $query = Tenant::whereIn('status', ['trial', 'active', 'grace'])
            ->where('slug', '!=', $slug);

        if ($v = $request->input('hosp_name')) {
            $query->where('name', 'like', "%{$v}%");
        }
        if ($v = $request->input('city')) {
            $query->where('city', 'like', "%{$v}%");
        }
        if ($v = $request->input('district')) {
            $query->where('district', 'like', "%{$v}%");
        }
        if ($v = $request->input('state')) {
            $query->where('state', 'like', "%{$v}%");
        }

        $paginated = $query->orderBy('name')
            ->paginate(20, ['id', 'name', 'slug', 'city', 'district', 'state', 'logo_path', 'status']);

        // Build request-status map for this tenant
        $reqMap = [];
        HospitalShareRequest::where('from_tenant_id', $currentTenant->id)
            ->orWhere('to_tenant_id', $currentTenant->id)
            ->get()
            ->each(function ($req) use ($currentTenant, &$reqMap) {
                $otherId         = $req->from_tenant_id === $currentTenant->id ? $req->to_tenant_id : $req->from_tenant_id;
                $reqMap[$otherId] = [
                    'id'        => $req->id,
                    'status'    => $req->status,
                    'direction' => $req->from_tenant_id === $currentTenant->id ? 'sent' : 'received',
                ];
            });

        $rows = collect($paginated->items())->map(fn($h) => [
            'id'           => $h->id,
            'name'         => $h->name,
            'city'         => $h->city,
            'district'     => $h->district,
            'state'        => $h->state,
            'logo_path'    => $h->logo_path,
            'status'       => $h->status,
            'request_info' => $reqMap[$h->id] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'data' => $rows,
                'meta' => [
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    // ── Hospital Detail (bottom sheet) ────────────────────────────────

    public function hospitalDetail(string $slug, int $hospitalId): JsonResponse
    {
        $hospital = Tenant::findOrFail($hospitalId);

        $doctorsCount = HospitalUser::withoutTenantScope()
            ->where('tenant_id', $hospitalId)
            ->whereHas('role', fn($q) => $q->withoutGlobalScope('tenant')->where('slug', 'doctor'))
            ->count();

        $staffCount = HospitalUser::withoutTenantScope()
            ->where('tenant_id', $hospitalId)
            ->count();

        $patientsCount = Patient::withoutTenantScope()
            ->where('tenant_id', $hospitalId)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $hospital->id,
                'name'             => $hospital->name,
                'city'             => $hospital->city   ?? 'N/A',
                'state'            => $hospital->state  ?? 'N/A',
                'admin_email'      => $hospital->admin_email ?? 'N/A',
                'doctors_count'    => $doctorsCount,
                'staff_count'      => $staffCount,
                'patients_count'   => $patientsCount,
            ],
        ]);
    }

    // ── Tab 3: Connections (all 3 sections in one call) ────────────────

    public function connections(): JsonResponse
    {
        $currentTenant = app('tenant');

        $accepted = HospitalShareRequest::where(fn($q) => $q
            ->where('from_tenant_id', $currentTenant->id)
            ->orWhere('to_tenant_id', $currentTenant->id))
            ->where('status', 'accepted')
            ->with(['fromTenant', 'toTenant'])
            ->latest()
            ->get()
            ->map(function ($req) use ($currentTenant) {
                $partner = $req->from_tenant_id === $currentTenant->id ? $req->toTenant : $req->fromTenant;
                return [
                    'id'      => $req->id,
                    'partner' => [
                        'id'    => $partner->id,
                        'name'  => $partner->name,
                        'city'  => $partner->city,
                        'state' => $partner->state,
                    ],
                ];
            });

        $incoming = HospitalShareRequest::with('fromTenant')
            ->where('to_tenant_id', $currentTenant->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn($req) => [
                'id'          => $req->id,
                'from_tenant' => [
                    'id'    => $req->fromTenant->id,
                    'name'  => $req->fromTenant->name,
                    'city'  => $req->fromTenant->city,
                    'state' => $req->fromTenant->state,
                ],
                'created_at' => $req->created_at->toDateString(),
            ]);

        $sent = HospitalShareRequest::with('toTenant')
            ->where('from_tenant_id', $currentTenant->id)
            ->latest()
            ->get()
            ->map(fn($req) => [
                'id'        => $req->id,
                'to_tenant' => [
                    'id'    => $req->toTenant->id,
                    'name'  => $req->toTenant->name,
                    'city'  => $req->toTenant->city,
                    'state' => $req->toTenant->state,
                ],
                'status'     => $req->status,
                'created_at' => $req->created_at->toDateString(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'accepted_connections' => $accepted,
                'incoming_requests'    => $incoming,
                'sent_requests'        => $sent,
            ],
        ]);
    }

    // ── Send share request ─────────────────────────────────────────────

    public function sendRequest(Request $request): JsonResponse
    {
        $currentTenant = app('tenant');
        $toTenantId    = (int) $request->input('to_tenant_id');

        if (!Tenant::find($toTenantId)) {
            return response()->json(['success' => false, 'message' => 'Hospital not found.'], 404);
        }
        if ($currentTenant->id === $toTenantId) {
            return response()->json(['success' => false, 'message' => 'Cannot send request to own hospital.'], 422);
        }

        $existing = HospitalShareRequest::where(function ($q) use ($currentTenant, $toTenantId) {
            $q->where('from_tenant_id', $currentTenant->id)->where('to_tenant_id', $toTenantId);
        })->orWhere(function ($q) use ($currentTenant, $toTenantId) {
            $q->where('from_tenant_id', $toTenantId)->where('to_tenant_id', $currentTenant->id);
        })->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Request already exists.'], 422);
        }

        HospitalShareRequest::create([
            'from_tenant_id' => $currentTenant->id,
            'to_tenant_id'   => $toTenantId,
            'status'         => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Request sent successfully.']);
    }

    // ── Accept incoming request ───────────────────────────────────────

    public function acceptRequest(string $slug, int $requestId): JsonResponse
    {
        $currentTenant = app('tenant');

        $req = HospitalShareRequest::where('id', $requestId)
            ->where('to_tenant_id', $currentTenant->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $req->update(['status' => 'accepted']);

        return response()->json(['success' => true, 'message' => 'Connection accepted.']);
    }

    // ── Remove / cancel request ───────────────────────────────────────

    public function removeRequest(string $slug, int $requestId): JsonResponse
    {
        $currentTenant = app('tenant');

        $req = HospitalShareRequest::where('id', $requestId)
            ->where(fn($q) => $q
                ->where('from_tenant_id', $currentTenant->id)
                ->orWhere('to_tenant_id', $currentTenant->id))
            ->firstOrFail();

        $req->delete();

        return response()->json(['success' => true, 'message' => 'Removed successfully.']);
    }

    // ── Partner hospital's patient list ───────────────────────────────

    public function partnerPatients(Request $request, string $slug, int $partnerTenantId): JsonResponse
    {
        $currentTenant = app('tenant');

        $shareExists = HospitalShareRequest::where(function ($q) use ($currentTenant, $partnerTenantId) {
            $q->where('from_tenant_id', $currentTenant->id)->where('to_tenant_id', $partnerTenantId);
        })->orWhere(function ($q) use ($currentTenant, $partnerTenantId) {
            $q->where('from_tenant_id', $partnerTenantId)->where('to_tenant_id', $currentTenant->id);
        })->where('status', 'accepted')->exists();

        if (!$shareExists) {
            return response()->json(['success' => false, 'message' => 'No accepted connection with this hospital.'], 403);
        }

        $partnerTenant = Tenant::findOrFail($partnerTenantId);

        $query = Patient::withoutTenantScope()
            ->with([
                'doctor'   => fn($q) => $q->withoutGlobalScope('tenant')->select('id', 'name', 'tenant_id'),
                'caseType' => fn($q) => $q->withoutGlobalScope('tenant'),
            ])
            ->where('tenant_id', $partnerTenantId)
            ->where(fn($q) => $q->whereNotNull('primary_done_at')->orWhereNotNull('secondary_done_at'));

        if ($v = $request->input('patient_name')) {
            $query->where(fn($q) => $q->where('first_name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%"));
        }
        if ($v = $request->input('doctor_name')) {
            $query->whereHas('doctor', fn($q) => $q->withoutGlobalScope('tenant')->where('name', 'like', "%{$v}%"));
        }
        if ($v = $request->input('contact_no')) {
            $query->where('contact_no', 'like', "%{$v}%");
        }
        if ($v = $request->input('date')) {
            $query->whereDate('appointment_date', $v);
        }

        $all = $query->latest('appointment_date')->get();

        $grouped = $all
            ->groupBy(fn($p) => mb_strtolower(trim("{$p->first_name} {$p->last_name}")) . '|' . $p->contact_no)
            ->map(function ($group) {
                $rep = $group->sortByDesc('id')->first();
                $rep->all_patient_ids = $group->pluck('id')->implode(',');
                $rep->is_own          = false;
                return $rep;
            })
            ->values();

        [$perPage, $page] = [20, max(1, (int) $request->input('page', 1))];
        $paginated = new LengthAwarePaginator(
            $grouped->slice(($page - 1) * $perPage, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'partner_hospital' => [
                    'id'   => $partnerTenant->id,
                    'name' => $partnerTenant->name,
                    'city' => $partnerTenant->city,
                ],
                'data' => $paginated->items(),
                'meta' => [
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    // ── Partner hospital patient's own exam history (the View action web's
    // partner_history.blade.php links to `hospital.shared.patient.history`
    // for — this API never had an equivalent, so the Flutter apps' partner
    // patient lists have always omitted their View button entirely).
    // Mirrors PatientHistoryApiController::buildHistory()'s exam-fetching
    // shape exactly (same JSON keys) so the apps can reuse the same
    // ExamHistoryData model/parsing — deliberately duplicated rather than
    // refactored out of that controller, to avoid touching the already-
    // working normal-patient history endpoint. ────────────────────────────

    public function patientHistory(Request $request, string $slug): JsonResponse
    {
        $currentTenant    = app('tenant');
        $partnerTenantIds = $this->partnerTenantIds($currentTenant);

        $rawIds     = (string) $request->query('patient_ids', '');
        $patientIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));

        if (empty($patientIds)) {
            return response()->json(['success' => false, 'message' => 'patient_ids is required.'], 422);
        }

        $patients = Patient::withoutTenantScope()
            ->whereIn('id', $patientIds)
            ->whereIn('tenant_id', $partnerTenantIds)
            ->with(['masterCity.district', 'masterCity.state'])
            ->get();

        if ($patients->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Patient not found or not accessible.'], 404);
        }

        $patient       = $patients->sortByDesc('id')->first();
        $dosageMasters = Dosage::all(['id', 'dosage'])->keyBy('id');

        $primaryExams = PrimaryExamination::withoutGlobalScope('tenant')
            ->with([
                'doctor'                 => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name'),
                'prescriptions.medicine' => fn ($q) => $q->withoutGlobalScopes(),
                'prescriptions.dosage',
            ])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(fn ($exam) => [
                'patient_id'    => $exam->patient_id,
                'id'            => $exam->id,
                'type'          => 'primary',
                'examined_at'   => $exam->examined_at?->toISOString(),
                'doctor'        => $exam->doctor?->name,
                'exam_data'     => $this->normalizeSharedExamData($exam->exam_data),
                'prescriptions' => $exam->prescriptions->map(fn ($rx) => [
                    'medicine_name' => $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '-'),
                    'dosage'        => $rx->dosage?->dosage ?? '-',
                    'duration'      => $rx->duration ? $rx->duration.' D' : '-',
                    'eye'           => $rx->eye ?? '-',
                ])->values()->all(),
            ]);

        $secondaryExams = SecondaryExamination::withoutGlobalScope('tenant')
            ->with(['doctor' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name')])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function ($exam) use ($dosageMasters) {
                $prescriptions = collect($exam->exam_data['rx'] ?? [])
                    ->map(fn ($rx) => [
                        'medicine_name' => $rx['name'] ?? '-',
                        'dosage'        => isset($rx['dosage_id'])
                            ? ($dosageMasters->get((int) $rx['dosage_id'])?->dosage ?? '-')
                            : '-',
                        'duration' => ! empty($rx['duration']) ? $rx['duration'].' D' : '-',
                        'eye'      => $rx['eye'] ?? '-',
                    ])
                    ->values()
                    ->all();

                return [
                    'patient_id'    => $exam->patient_id,
                    'id'            => $exam->id,
                    'type'          => 'secondary',
                    'examined_at'   => $exam->examined_at?->toISOString(),
                    'doctor'        => $exam->doctor?->name,
                    'exam_data'     => $this->normalizeSharedExamData($exam->exam_data),
                    'prescriptions' => $prescriptions,
                ];
            });

        // One exam per visit (patient_id): secondary once done, primary until then.
        $allExams = $secondaryExams->keyBy('patient_id')
            ->union($primaryExams->keyBy('patient_id'))
            ->sortByDesc('examined_at')
            ->values()
            ->all();

        $visitDays = collect($allExams)
            ->groupBy(fn ($e) => $e['examined_at'] ? substr($e['examined_at'], 0, 10) : 'unknown')
            ->count();

        $diagnosisMasters = DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $patient->tenant_id)
            ->orderBy('id')
            ->get(['id', 'value'])
            ->pluck('value', 'id')
            ->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'patient' => [
                    'id'           => $patient->id,
                    'name'         => trim(implode(' ', array_filter([
                        $patient->first_name,
                        $patient->middle_name,
                        $patient->last_name,
                    ]))),
                    'patient_code' => $patient->patient_code,
                    'gender'       => $patient->gender,
                    'age'          => $patient->age,
                    'contact_no'   => $patient->contact_no,
                    'location'     => $patient->locationLabel,
                    'created_at'   => $patient->created_at?->toISOString(),
                    'visit_days'   => $visitDays,
                ],
                'exams'             => $allExams,
                'diagnosis_masters' => empty($diagnosisMasters) ? new \stdClass() : $diagnosisMasters,
                // No further partner-of-partner nesting, matching web's
                // loadSharedExamHistoryForIds() — this response IS already
                // the partner's own data.
                'partner_hospitals' => [],
            ],
        ]);
    }

    // Same re-indexing fix as PatientHistoryApiController::normalizeExamData()
    // — kept as a private duplicate rather than a shared dependency (see the
    // class-level note above this section).
    private function normalizeSharedExamData(mixed $raw): array|\stdClass
    {
        if (! is_array($raw) || count($raw) === 0) {
            return new \stdClass();
        }
        foreach (['co_rows', 'kco_rows', 'diagnoses', 'rx'] as $field) {
            if (isset($raw[$field]) && is_array($raw[$field])) {
                $raw[$field] = array_values($raw[$field]);
            }
        }

        return $raw;
    }

    // ── Private helper ─────────────────────────────────────────────────

    private function partnerTenantIds($currentTenant): array
    {
        return HospitalShareRequest::where(fn($q) => $q
            ->where('from_tenant_id', $currentTenant->id)
            ->orWhere('to_tenant_id', $currentTenant->id))
            ->where('status', 'accepted')
            ->get()
            ->map(fn($r) => $r->from_tenant_id === $currentTenant->id ? $r->to_tenant_id : $r->from_tenant_id)
            ->toArray();
    }
}
