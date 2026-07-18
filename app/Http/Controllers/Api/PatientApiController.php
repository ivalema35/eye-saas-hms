<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\Patient;
use App\Models\Platform\HospitalShareRequest;
use App\Services\Hospital\PatientService;
use App\Support\PhoneRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientApiController extends Controller
{
    public function __construct(private PatientService $patientService) {}

    public function index(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $showAll = $request->boolean('all');
        $search = trim((string) $request->input('search', ''));

        $authUser = auth('sanctum')->user();
        $doctorUserId = ($authUser && $authUser->doctor_type !== null) ? $authUser->id : null;

        $query = Patient::with([
            'doctor:id,name',
            'location:id,city,district,state',
            'caseType:id,case_type',
            'referrer:id,name',
            'primaryExamination:id,patient_id,exam_data,dilation_time,updated_at',
            'secondaryExamination:id,patient_id',
        ])->latest();

        if (!$showAll) {
            $query->whereDate('appointment_date', $today);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%");
            });
        }

        if ($doctorUserId !== null) {
            $query->where('doctor_id', $doctorUserId);
        }

        $patients = $query->paginate(25);

        // Stats for current filter (today or all)
        $statsQuery = Patient::query();
        if (!$showAll) {
            $statsQuery->whereDate('appointment_date', $today);
        }
        if ($search !== '') {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%");
            });
        }

        if ($doctorUserId !== null) {
            $statsQuery->where('doctor_id', $doctorUserId);
        }

        $allInFilter = $statsQuery->get(['primary_done_at', 'secondary_done_at']);
        $stats = [
            'total'        => $allInFilter->count(),
            'waiting'      => $allInFilter->filter(fn($p) => !$p->primary_done_at && !$p->secondary_done_at)->count(),
            'primary_done' => $allInFilter->filter(fn($p) => $p->primary_done_at && !$p->secondary_done_at)->count(),
            'completed'    => $allInFilter->filter(fn($p) => $p->secondary_done_at !== null)->count(),
        ];

        // Compute dilation unlock timestamp per patient
        $items = $patients->getCollection()->map(function (Patient $p) {
            $arr = $p->toArray();
            $arr['full_name'] = $p->full_name;

            // Dilation logic
            $arr['unlock_time_ms'] = null;
            $pe = $p->primaryExamination;
            if (
                $pe &&
                !$p->secondary_done_at &&
                !$p->secondaryExamination
            ) {
                $examData = $pe->exam_data;
                if (is_string($examData)) {
                    $examData = json_decode($examData, true);
                }
                $dilate = is_array($examData) ? ($examData['dilate'] ?? null) : null;
                $dilationTime = $pe->dilation_time; // minutes
                if ($dilate === 'Yes' && $dilationTime && $pe->updated_at) {
                    $unlockAt = $pe->updated_at->addMinutes((int) $dilationTime);
                    if ($unlockAt->isFuture()) {
                        $arr['unlock_time_ms'] = $unlockAt->timestamp * 1000;
                    }
                }
            }

            return $arr;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data'         => $items,
                'meta'         => [
                    'total'        => $patients->total(),
                    'per_page'     => $patients->perPage(),
                    'current_page' => $patients->currentPage(),
                    'last_page'    => $patients->lastPage(),
                ],
                'stats' => $stats,
            ],
        ]);
    }

    public function show(string $slug, Patient $patient): JsonResponse
    {
        $patient->load([
            'doctor:id,name',
            'location:id,city,district,state',
            'caseType:id,case_type',
            'referrer:id,name',
            'primaryExamination',
            'secondaryExamination',
        ]);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;

        return response()->json(['success' => true, 'data' => $arr]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'contact_no'       => PhoneRules::required(),
            'whatsapp_no'      => PhoneRules::nullable(),
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'case_id'          => ['required', 'integer'],
            'case_fee'         => ['required', 'numeric', 'min:0'],
            'doctor_id'        => ['required', 'integer'],
            'location_id'      => ['required', 'integer'],
            'age'              => ['required', 'integer', 'min:0', 'max:150'],
            'gender'           => ['required', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'referrer_id'      => ['nullable', 'integer'],
            'is_old_patient'   => ['nullable', 'boolean'],
        ]);

        $tenant = app('tenant');
        $validated['reception_id'] = auth('sanctum')->id();

        $patient = $this->patientService->registerWalkIn($validated, $tenant->id);
        $patient->load(['doctor:id,name', 'location:id,city,district,state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;

        return response()->json(['success' => true, 'data' => $arr, 'message' => 'Patient registered successfully.'], 201);
    }

    public function storePhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'contact_no'       => PhoneRules::required(),
            'whatsapp_no'      => PhoneRules::nullable(),
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'doctor_id'        => ['required', 'integer'],
            'slot_id'          => ['nullable', 'integer'],
            'location_id'      => ['required', 'integer'],
            'age'              => ['required', 'integer', 'min:0', 'max:150'],
            'gender'           => ['required', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'referrer_id'      => ['nullable', 'integer'],
            'is_old_patient'   => ['nullable', 'boolean'],
        ]);

        $tenant = app('tenant');
        $validated['reception_id'] = auth('sanctum')->id();

        $patient = $this->patientService->registerPhone($validated, $tenant->id);
        $patient->load(['doctor:id,name', 'location:id,city,district,state']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;

        return response()->json(['success' => true, 'data' => $arr, 'message' => 'Phone appointment registered.'], 201);
    }

    public function update(string $slug, Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['sometimes', 'date'],
            'contact_no'       => ['sometimes', 'string', 'max:'.PhoneRules::MAX, PhoneRules::RULE],
            'whatsapp_no'      => PhoneRules::nullable(),
            'first_name'       => ['sometimes', 'string', 'max:100'],
            'last_name'        => ['sometimes', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'case_id'          => ['sometimes', 'nullable', 'integer'],
            'case_fee'         => ['sometimes', 'numeric', 'min:0'],
            'doctor_id'        => ['sometimes', 'integer'],
            'location_id'      => ['sometimes', 'integer'],
            'age'              => ['sometimes', 'integer', 'min:0', 'max:150'],
            'gender'           => ['sometimes', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'referrer_id'      => ['nullable', 'integer'],
            'is_old_patient'   => ['nullable', 'boolean'],
            'slot_id'          => ['nullable', 'integer'],
        ]);

        $patient->update($validated);
        $patient->load(['doctor:id,name', 'location:id,city,district,state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;

        return response()->json(['success' => true, 'data' => $arr, 'message' => 'Patient updated successfully.']);
    }

    public function destroy(string $slug, Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json(['success' => true, 'message' => 'Patient deleted.']);
    }

    public function nextMrd(): JsonResponse
    {
        $tenant = app('tenant');
        $mrd = $this->patientService->peekNextMrd($tenant->id);

        return response()->json(['success' => true, 'mrd' => $mrd]);
    }

    public function searchByContact(Request $request): JsonResponse
    {
        $contact = trim((string) $request->input('contact', ''));

        if ($contact === '') {
            return response()->json(['found' => false, 'patients' => []]);
        }

        $localPatients = Patient::where('contact_no', $contact)
            ->latest()
            ->get()
            ->unique(fn($p) => strtolower(trim($p->first_name . '|' . $p->last_name)));

        $localMapped = $localPatients->values()->map(fn($p) => [
            'type'        => 'local',
            'first_name'  => $p->first_name,
            'middle_name' => $p->middle_name,
            'last_name'   => $p->last_name,
            'age'         => $p->age,
            'gender'      => $p->gender,
            'whatsapp_no' => $p->whatsapp_no,
            'occupation'  => $p->occupation,
            'location_id' => $p->location_id,
        ]);

        $currentTenant = app('tenant');
        $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($currentTenant) {
            $q->where('from_tenant_id', $currentTenant->id)
                ->orWhere('to_tenant_id', $currentTenant->id);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(fn($r) => $r->from_tenant_id === $currentTenant->id
                ? $r->to_tenant_id
                : $r->from_tenant_id)
            ->toArray();

        $sharedMapped = collect();
        if (!empty($partnerTenantIds)) {
            $sharedPatients = Patient::withoutTenantScope()
                ->with('tenant:id,name')
                ->whereIn('tenant_id', $partnerTenantIds)
                ->where('contact_no', $contact)
                ->latest()
                ->get()
                ->unique(fn($p) => strtolower(trim($p->first_name . '|' . $p->last_name)));

            $sharedMapped = $sharedPatients->values()->map(fn($p) => [
                'type'          => 'shared',
                'hospital_name' => $p->tenant?->name ?? 'Partner Hospital',
                'first_name'    => $p->first_name,
                'middle_name'   => $p->middle_name,
                'last_name'     => $p->last_name,
                'age'           => $p->age,
                'gender'        => $p->gender,
                'whatsapp_no'   => $p->whatsapp_no,
                'occupation'    => $p->occupation,
                'location_id'   => null,
            ]);
        }

        $all = $localMapped->concat($sharedMapped)->values();

        return response()->json([
            'found'    => $all->isNotEmpty(),
            'patients' => $all->toArray(),
        ]);
    }

    public function checkin(string $slug, Request $request, Patient $patient): JsonResponse
    {
        if ($patient->type !== 'phone') {
            return response()->json(['success' => false, 'message' => 'Only phone patients can be checked in.'], 422);
        }

        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'contact_no'       => PhoneRules::required(),
            'whatsapp_no'      => PhoneRules::nullable(),
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'age'              => ['required', 'integer', 'min:0', 'max:150'],
            'gender'           => ['required', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'location_id'      => ['required', 'integer'],
            'slot_id'          => ['nullable', 'integer'],
            'doctor_id'        => ['required', 'integer'],
            'case_id'          => ['required', 'integer'],
            'case_fee'         => ['required', 'numeric', 'min:0'],
            'referrer_id'      => ['nullable', 'integer'],
        ]);

        $tenant = app('tenant');

        DB::transaction(function () use ($patient, $validated, $tenant) {
            $patient->update(array_merge($validated, ['checked_in_at' => $patient->checked_in_at ?? now()]));

            $this->patientService->assignDoctorSerial(
                $patient->fresh(),
                (int) $validated['doctor_id'],
                $validated['appointment_date'],
                $tenant->id
            );
        });

        $patient->refresh();
        $patient->load(['doctor:id,name', 'location:id,city,district,state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;

        return response()->json(['success' => true, 'data' => $arr, 'message' => 'Patient checked in successfully.']);
    }
}
