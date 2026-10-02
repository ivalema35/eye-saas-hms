<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\Patient;
use App\Models\Platform\HospitalShareRequest;
use App\Services\Hospital\PatientService;
use App\Support\PhoneRules;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientApiController extends Controller
{
    public function __construct(private PatientService $patientService) {}

    /**
     * Patient::location() points at the legacy `locations` table, but
     * `location_id` is actually validated/populated as a `tbl_master_cities`
     * id (see web's PatientController validation rule). Build the response
     * shape the apps expect ({id, city, district, state}) from the correct
     * masterCity relation instead of the broken `location` eager-load.
     * See PATIENT_DATA_EMPTY_FIELDS_AUDIT.md.
     */
    private function locationArray(Patient $patient): array
    {
        return [
            'id'       => $patient->location_id,
            'city'     => $patient->cityName,
            'district' => $patient->districtName,
            'state'    => $patient->stateName,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $showAll = $request->boolean('all');
        $search = trim((string) $request->input('search', ''));

        $authUser = auth('sanctum')->user();
        // Matches web's PatientController::index() exactly — scope by role
        // slug, not doctor_type (a separate, not-guaranteed-in-sync column).
        // See ROLES_PERMISSIONS_PARITY_AUDIT.md.
        $doctorUserId = ($authUser && $authUser->role?->slug === 'doctor') ? $authUser->id : null;

        $query = Patient::with([
            'doctor:id,name',
            'masterCity.district',
            'masterCity.state',
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
            $arr['location'] = $this->locationArray($p);

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
            'masterCity.district',
            'masterCity.state',
            'caseType:id,case_type',
            'referrer:id,name',
            // Web pull 2026-09-29 (WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding
            // #2) — exam screens' "Patient Details" modal needs the
            // Receptionist name, which nothing previously eager-loaded here.
            'reception:id,name',
            'primaryExamination',
            'secondaryExamination',
        ]);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;
        $arr['location'] = $this->locationArray($patient);

        return response()->json(['success' => true, 'data' => $arr]);
    }

    /**
     * Phone appointment history — same filters as the hospital page
     * (type=phone, optional date range, name or mobile search).
     */
    public function phoneHistory(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $search = trim((string) $request->input('search', ''));

        $query = Patient::with([
            'doctor:id,name',
            'reception:id,name',
            'caseType:id,case_type',
            'masterCity.district',
            'masterCity.state',
        ])->where('type', 'phone');

        if ($fromDate) {
            $query->whereDate('appointment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('appointment_date', '<=', $toDate);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) like ?", ["%{$search}%"])
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%")
                    ->orWhere('whatsapp_no', 'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%");
            });
        }

        $patients = $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('created_at')
            ->paginate(25);

        $items = $patients->getCollection()->map(function (Patient $p) {
            $arr = $p->toArray();
            $arr['full_name'] = $p->full_name;
            $arr['location'] = $this->locationArray($p);

            return $arr;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'meta' => [
                    'total' => $patients->total(),
                    'per_page' => $patients->perPage(),
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Exam screens' "Patient Details" modal — inline personal-info edit,
     * mirrors web's Hospital\Patient\PatientController::quickUpdatePersonal
     * exactly (same doctor-only gate, same validation, same response shape).
     * See WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #2.
     */
    public function quickUpdatePersonal(string $slug, Request $request, Patient $patient): JsonResponse
    {
        abort_unless($request->user()?->role?->slug === 'doctor', 403);

        $rawPhone = (string) $request->input('contact_no', '');
        $plus = str_starts_with(trim($rawPhone), '+');
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $request->merge(['contact_no' => $plus ? '+'.$digits : $digits]);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', 'in:male,female,other'],
            'contact_no' => PhoneRules::required(),
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
        ]);

        $parts = preg_split('/\s+/', trim($data['full_name']));
        $firstName = array_shift($parts);
        $lastName = count($parts) ? array_pop($parts) : '';
        $middleName = count($parts) ? implode(' ', $parts) : '';

        $patient->update([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'age' => $data['age'],
            'gender' => $data['gender'],
            'contact_no' => $data['contact_no'],
            'location_id' => $data['location_id'],
        ]);

        $patient = $patient->fresh(['masterCity.district', 'masterCity.state']);

        return response()->json([
            'success' => true,
            'data' => [
                'full_name' => $patient->full_name,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'contact_no' => $patient->contact_no,
                'location_id' => $patient->location_id,
                'city_name' => $patient->cityName ?: '—',
                'district_name' => $patient->districtName ?: '—',
                'state_name' => $patient->stateName ?: '—',
            ],
        ]);
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
            // Set when converting from an OT appointment lead (receptionist
            // "Today Added Patients" widget's Walk-In action) — not a Patient
            // column, handled separately below. Mirrors
            // Hospital\Patient\PatientController::store()'s OT-conversion step.
            // See WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §9 / FIX_PLAN TASK 5.3.
            'ot_appointment_id' => ['nullable', 'integer'],
        ]);

        $tenant = app('tenant');
        $otAppointmentId = $validated['ot_appointment_id'] ?? null;
        unset($validated['ot_appointment_id']);
        $validated['reception_id'] = auth('sanctum')->id();

        $patient = $this->patientService->registerWalkIn($validated, $tenant->id);

        if ($otAppointmentId) {
            OtAppointment::query()
                ->where('id', $otAppointmentId)
                ->where('status', '!=', OtAppointment::STATUS_CANCELLED)
                ->whereNull('converted_patient_id')
                ->update([
                    'status' => OtAppointment::STATUS_COMPLETED,
                    'converted_patient_id' => $patient->id,
                ]);
        }

        $patient->load(['doctor:id,name', 'masterCity.district', 'masterCity.state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;
        $arr['location'] = $this->locationArray($patient);

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
        $patient->load(['doctor:id,name', 'masterCity.district', 'masterCity.state']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;
        $arr['location'] = $this->locationArray($patient);

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
            'expected_updated_at' => ['sometimes', 'nullable', 'date'],
        ]);

        // Optimistic concurrency check — reject a save if the record changed
        // elsewhere (web/another platform) since the client last fetched it.
        // See ACCESS_CONTROL_AND_DATA_SYNC_PLAN.md Phase 1 Task 1.2.
        if (! empty($validated['expected_updated_at'])
            && ! $patient->updated_at->equalTo(Carbon::parse($validated['expected_updated_at']))) {
            return response()->json([
                'error' => 'This record was changed elsewhere. Please reload before saving.',
                'code'  => 'stale_record',
            ], 409);
        }
        unset($validated['expected_updated_at']);

        $patient->update($validated);
        $patient->load(['doctor:id,name', 'masterCity.district', 'masterCity.state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;
        $arr['location'] = $this->locationArray($patient);

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

        if (mb_strlen($contact) < 3) {
            return response()->json(['found' => false, 'patients' => []]);
        }

        $localPatients = Patient::query()
            ->where(function ($q) use ($contact) {
                $this->applyPatientLookup($q, $contact);
            })
            ->latest()
            ->limit(20)
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
            'contact_no'  => $p->contact_no,
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
                ->where(function ($q) use ($contact) {
                    $this->applyPatientLookup($q, $contact);
                })
                ->latest()
                ->limit(20)
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
                'contact_no'    => $p->contact_no,
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
        $patient->load(['doctor:id,name', 'masterCity.district', 'masterCity.state', 'caseType:id,case_type']);

        $arr = $patient->toArray();
        $arr['full_name'] = $patient->full_name;
        $arr['location'] = $this->locationArray($patient);

        return response()->json(['success' => true, 'data' => $arr, 'message' => 'Patient checked in successfully.']);
    }

    /**
     * Walk-in and phone registration lookup: a name matches first / middle /
     * last / full name, a number matches contact or WhatsApp.
     */
    private function applyPatientLookup($query, string $term): void
    {
        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $hasLetter = preg_match('/[a-z]/i', $term) === 1;

        $query->where(function ($q) use ($term, $digits, $hasLetter) {
            if ($hasLetter) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('middle_name', 'like', "%{$term}%")
                    ->orWhereRaw("TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) like ?", ["%{$term}%"]);
            }
            if ($digits !== '') {
                $phone = function ($inner) use ($digits, $term) {
                    $inner->where('contact_no', 'like', "%{$digits}%")
                        ->orWhere('whatsapp_no', 'like', "%{$digits}%")
                        ->orWhere('contact_no', $term)
                        ->orWhere('whatsapp_no', $term);
                };
                if ($hasLetter) {
                    $q->orWhere($phone);
                } else {
                    $q->where($phone);
                }
            }
            if (! $hasLetter && $digits === '') {
                $q->whereRaw('0 = 1');
            }
        });
    }
}
