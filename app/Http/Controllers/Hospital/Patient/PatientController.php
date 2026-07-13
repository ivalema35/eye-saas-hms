<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Patient\PatientPhoneStoreRequest;
use App\Http\Requests\Hospital\Patient\PatientStoreRequest;
use App\Http\Requests\Hospital\Patient\PatientUpdateRequest;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use App\Models\Hospital\Referrer;
use App\Models\Platform\HospitalShareRequest;
use App\Models\Platform\MasterCity;
use App\Services\Hospital\PatientService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Patient Management Controller
 *
 * CRUD for patients within a hospital tenant.
 * BelongsToTenant global scope ensures cross-hospital isolation.
 *
 * URL: hmssaas.com/{slug}/patients
 */
class PatientController extends Controller
{
    public function __construct(private PatientService $patientService)
    {
    }

    public function index(Request $request): View
    {
        $slug = $request->route('slug');
        $user = Auth::guard('hospital_user')->user();
        $role = $user?->role?->slug;
        $today = now()->toDateString();

        $query = Patient::with(['doctor:id,name', 'reception:id,name', 'primaryExamination', 'secondaryExamination', 'location:id,city,district,state', 'masterCity:id,state_id,district_id,name', 'masterCity.district:id,name', 'masterCity.state:id,name', 'caseType:id,case_type', 'referrer:id,name']);

        // Doctors see only their assigned patients
        if ($role === 'doctor') {
            $query->where('doctor_id', $user->id);
        }

        // Toggle today/all
        $showAll = $request->boolean('all');
        if (!$showAll) {
            $query->whereDate('appointment_date', $today);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%");
            });
        }

        $stats = [
            'waiting' => (clone $query)->whereNull('primary_done_at')->whereNull('secondary_done_at')->count(),
            'primary_done' => (clone $query)->whereNotNull('primary_done_at')->whereNull('secondary_done_at')->count(),
            'completed' => (clone $query)->whereNotNull('secondary_done_at')->count(),
        ];

        $patients = $query->latest()->paginate((int) config('app.pagination_limit', 25))->withQueryString();

        // Live search: return just the table/pagination markup, no layout.
        if ($request->ajax()) {
            return view('hospital.patients.partials.table', compact('patients', 'slug', 'showAll'));
        }

        return view('hospital.patients.index', compact('patients', 'slug', 'showAll', 'stats'));
    }

    public function searchByContact(Request $request): JsonResponse
    {
        $contact = trim((string) $request->input('contact', ''));

        if ($contact === '') {
            return response()->json(['found' => false]);
        }

        // ── 1. Local search ───────────────────────────────────────────────────
        $localPatients = Patient::where('contact_no', $contact)
            ->latest()
            ->get()
            ->unique(fn($p) => strtolower(trim($p->first_name . '|' . $p->last_name)));

        $localMapped = $localPatients->values()->map(fn($p) => [
            'type' => 'local',
            'first_name' => $p->first_name,
            'middle_name' => $p->middle_name,
            'last_name' => $p->last_name,
            'age' => $p->age,
            'gender' => $p->gender,
            'whatsapp_no' => $p->whatsapp_no,
            'occupation' => $p->occupation,
            'location_id' => $p->location_id,
        ]);

        // ── 2. Partner hospital search (always run, not just on no local match) ─
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
                'type' => 'shared',
                'hospital_name' => $p->tenant?->name ?? 'Partner Hospital',
                'first_name' => $p->first_name,
                'middle_name' => $p->middle_name,
                'last_name' => $p->last_name,
                'age' => $p->age,
                'gender' => $p->gender,
                'whatsapp_no' => $p->whatsapp_no,
                'occupation' => $p->occupation,
                'location_id' => $p->location_id,
            ]);
        }

        $all = $localMapped->concat($sharedMapped)->values();

        return response()->json([
            'found' => $all->isNotEmpty(),
            'patients' => $all->toArray(),
        ]);
    }

    public function create(): View
    {
        $slug = request()->route('slug');
        $tenantId = app('tenant')->id;
        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })->get();
        $user = auth('hospital_user')->user();

        $hospitalCountry = $user->tenant->country;

        $locations = MasterCity::with([
            'district',
            'state',
            'state.country',
        ])
            ->whereHas('state.country', function ($q) use ($hospitalCountry) {
                $q->where('name', $hospitalCountry);
            })
            ->orderBy('name')
            ->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $slots = $this->loadPatientSlots($tenantId);
        $nextMrd = $this->patientService->peekNextMrd($tenantId);
        $currentHospitalName = app('tenant')->name;

        return view('hospital.patients.create', compact('slug', 'doctors', 'locations', 'cases', 'slots', 'referrers', 'nextMrd', 'currentHospitalName'));
    }

    public function store(PatientStoreRequest $request): RedirectResponse
    {
        $slug = $request->route('slug');
        $tenant = app('tenant');
        $data = $request->validated();

        $data['reception_id'] = Auth::guard('hospital_user')->id();

        $patient = $this->patientService->registerWalkIn($data, $tenant->id);

        return redirect()->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'dashboard'])
            ->with('success', 'Patient registered successfully.');
    }

    public function createPhone(): View
    {
        $slug = request()->route('slug');
        $tenantId = app('tenant')->id;
        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })->get();
        $user = auth('hospital_user')->user();

        $hospitalCountry = $user->tenant->country;

        $locations = MasterCity::with([
            'district',
            'state',
            'state.country',
        ])
            ->whereHas('state.country', function ($q) use ($hospitalCountry) {
                $q->where('name', $hospitalCountry);
            })
            ->orderBy('name')
            ->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = $this->loadPatientSlots($tenantId);

        return view('hospital.patients.create-phone', compact('slug', 'doctors', 'locations', 'cases', 'slots', 'referrers'));
    }

    public function storePhone(PatientPhoneStoreRequest $request): RedirectResponse
    {
        $slug = $request->route('slug');
        $tenant = app('tenant');
        $data = $request->validated();

        $data['reception_id'] = Auth::guard('hospital_user')->id();

        $patient = $this->patientService->registerPhone($data, $tenant->id);

        return redirect()->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'dashboard'])
            ->with('success', 'Phone appointment registered and ready for printing.');
    }

    public function phoneHistory(Request $request): View
    {
        $slug = $request->route('slug');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = Patient::with(['doctor:id,name', 'reception:id,name', 'caseType:id,case_type'])
            ->where('type', 'phone');

        if ($fromDate) {
            $query->whereDate('appointment_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('appointment_date', '<=', $toDate);
        }

        $patients = $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('created_at')
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        $groupedPatients = $patients->getCollection()->groupBy(
            fn(Patient $patient): string => $patient->appointment_date
            ? now()->parse((string) $patient->appointment_date)->format('Y-m-d')
            : $patient->created_at->format('Y-m-d')
        );

        return view('hospital.patients.phone-history', compact('slug', 'patients', 'groupedPatients', 'fromDate', 'toDate'));
    }

    public function show(string $slug, Patient $patient): View
    {
        $patient->load('doctor', 'reception', 'location', 'caseType');

        return view('hospital.patients.show', compact('patient', 'slug'));
    }

    public function edit(string $slug, Patient $patient): View
    {
        $tenantId = app('tenant')->id;
        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })->get();
        $user = auth('hospital_user')->user();

        $hospitalCountry = $user->tenant->country;

        $locations = MasterCity::with([
            'district',
            'state',
            'state.country',
        ])
            ->whereHas('state.country', function ($q) use ($hospitalCountry) {
                $q->where('name', $hospitalCountry);
            })
            ->orderBy('name')
            ->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $slots = $this->loadPatientSlots($tenantId);

        return view('hospital.patients.edit', compact('patient', 'slug', 'doctors', 'locations', 'cases', 'slots', 'referrers'));
    }

    public function update(PatientUpdateRequest $request, string $slug, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()->route('hospital.dashboard', ['slug' => $slug])
            ->with('success', 'Patient updated.');
    }

    public function quickUpdateName(Request $request, string $slug, Patient $patient): JsonResponse
    {
        abort_unless(Auth::guard('hospital_user')->user()?->role?->slug === 'doctor', 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
        ]);

        $parts = preg_split('/\s+/', trim($data['full_name']));
        $firstName = array_shift($parts);
        $lastName = count($parts) ? array_pop($parts) : '';
        $middleName = count($parts) ? implode(' ', $parts) : '';

        $patient->update([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ]);

        return response()->json([
            'full_name' => $patient->fresh()->full_name,
        ]);
    }

    public function destroy(string $slug, Patient $patient): RedirectResponse
    {
        $patient->delete();

        return redirect()->route('hospital.patients.index', ['slug' => $slug])
            ->with('success', 'Patient record deleted.');
    }

    public function checkinForm(string $slug, Patient $patient): View
    {
        abort_if($patient->type !== 'phone', 404);

        $patient->load('doctor', 'location', 'caseType');
        $tenantId = app('tenant')->id;

        $doctors = HospitalUser::active()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', fn($r) => $r->where(function ($i) {
                        $i->whereIn('slug', ['doctor', 'ot_doctor'])
                            ->orWhereIn('name', ['doctor', 'ot_doctor']);
                    }));
            })->get();

        $user = auth('hospital_user')->user();

        $hospitalCountry = $user->tenant->country;

        $locations = MasterCity::with([
            'district',
            'state',
            'state.country',
        ])
            ->whereHas('state.country', function ($q) use ($hospitalCountry) {
                $q->where('name', $hospitalCountry);
            })
            ->orderBy('name')
            ->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $slots = $this->loadPatientSlots($tenantId);

        return view(
            'hospital.patients.checkin',
            compact('patient', 'slug', 'doctors', 'locations', 'referrers', 'cases', 'slots')
        );
    }

    public function checkin(Request $request, string $slug, Patient $patient): RedirectResponse
    {
        abort_if($patient->type !== 'phone', 404);

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', 'in:male,female,other'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'contact_no' => ['required', 'string', 'max:15', 'regex:/^\d+$/'],
            'whatsapp_no' => ['nullable', 'string', 'max:15', 'regex:/^\d+$/'],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'appointment_date' => ['required', 'date'],
            'slot_id' => ['nullable', 'integer', 'exists:tbl_slots,id'],
            'doctor_id' => ['required', 'integer', 'exists:hospital_users,id'],
            'case_id' => ['required', 'integer', 'exists:tbl_cases,id'],
            'case_fee' => ['required', 'numeric', 'min:0'],
            'referrer_id' => ['nullable', 'integer', 'exists:tbl_referrers,id'],
        ]);

        DB::transaction(function () use ($request, $patient) {
            $patient->update(array_merge(
                $request->only([
                    'first_name',
                    'middle_name',
                    'last_name',
                    'age',
                    'gender',
                    'occupation',
                    'contact_no',
                    'whatsapp_no',
                    'location_id',
                    'appointment_date',
                    'slot_id',
                    'doctor_id',
                    'case_id',
                    'case_fee',
                    'referrer_id',
                ]),
                ['checked_in_at' => $patient->checked_in_at ?? now()]
            ));

            // $this->patientService->assignDoctorSerial(
            //     $patient->fresh(),
            //     (int) $request->doctor_id,
            //     $request->appointment_date,
            //     app('tenant')->id
            // );
        });

        return redirect()
            ->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'dashboard'])
            ->with('success', 'Patient checked in successfully.');
    }

    public function print(string $slug, Patient $patient): View
    {
        $patient->load('doctor', 'reception');
        $tenant = app('tenant');

        return view('hospital.patients.print', compact('patient', 'slug', 'tenant'));
    }

    public function downloadBill(string $slug, Patient $patient): Response
    {
        $patient->load('doctor', 'reception');
        $tenant = app('tenant');

        $pdf = Pdf::loadView('pdfs.opd-bill', compact('patient', 'tenant'))
            ->setPaper('a5', 'portrait');

        return $pdf->download("OPD-Bill-{$patient->patient_code}.pdf");
    }

    private function loadPatientSlots(int $tenantId): Collection
    {
        $otSlots = DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->orderBy('slot_name')
            ->get(['slot_name', 'start_time', 'end_time']);

        $timestamp = now();

        foreach ($otSlots as $otSlot) {
            $existingSlot = DB::table('tbl_slots')
                ->where('tenant_id', $tenantId)
                ->where('slot_name', $otSlot->slot_name)
                ->first(['id']);

            if ($existingSlot) {
                DB::table('tbl_slots')
                    ->where('id', $existingSlot->id)
                    ->update([
                        'start_time' => $otSlot->start_time,
                        'end_time' => $otSlot->end_time,
                        'deleted_at' => null,
                        'updated_at' => $timestamp,
                    ]);

                continue;
            }

            DB::table('tbl_slots')->insert([
                'tenant_id' => $tenantId,
                'slot_name' => $otSlot->slot_name,
                'start_time' => $otSlot->start_time,
                'end_time' => $otSlot->end_time,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return DB::table('tbl_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->orderBy('slot_name')
            ->get();
    }
}
