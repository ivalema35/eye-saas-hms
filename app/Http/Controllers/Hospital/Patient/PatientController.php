<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Patient\PatientPhoneStoreRequest;
use App\Http\Requests\Hospital\Patient\PatientStoreRequest;
use App\Http\Requests\Hospital\Patient\PatientUpdateRequest;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\Patient;
use App\Models\Hospital\Referrer;
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
    public function __construct(private PatientService $patientService) {}

    public function index(Request $request): View
    {
        $slug = $request->route('slug');
        $user = Auth::guard('hospital_user')->user();
        $role = $user?->role?->slug;
        $today = now()->toDateString();

        $query = Patient::with(['doctor', 'reception', 'primaryExamination', 'secondaryExamination']);

        // Doctors see only their assigned patients
        if ($role === 'doctor') {
            $query->where('doctor_id', $user->id);
        }

        // Toggle today/all
        $showAll = $request->boolean('all');
        if (! $showAll) {
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

        $patients = $query->latest()->paginate((int) config('app.pagination_limit', 25))->withQueryString();

        return view('hospital.patients.index', compact('patients', 'slug', 'showAll'));
    }

    public function searchByContact(Request $request): JsonResponse
    {
        $contact = trim((string) $request->input('contact', ''));

        if ($contact === '') {
            return response()->json(['found' => false]);
        }

        $patients = Patient::where('contact_no', $contact)
            ->latest()
            ->get()
            ->unique(fn ($p) => strtolower(trim($p->first_name.'|'.$p->last_name)));

        return response()->json([
            'found' => $patients->isNotEmpty(),
            'patients' => $patients->values()->map(fn ($patient) => [
                'first_name' => $patient->first_name,
                'middle_name' => $patient->middle_name,
                'last_name' => $patient->last_name,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'whatsapp_no' => $patient->whatsapp_no,
                'occupation' => $patient->occupation,
                'location_id' => $patient->location_id,
            ])->toArray(),
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
        $locations = Location::orderBy('city')->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $slots   = $this->loadPatientSlots($tenantId);
        $nextMrd = $this->patientService->peekNextMrd($tenantId);

        return view('hospital.patients.create', compact('slug', 'doctors', 'locations', 'cases', 'slots', 'referrers', 'nextMrd'));
    }

    public function store(PatientStoreRequest $request): RedirectResponse
    {
        $slug = $request->route('slug');
        $tenant = app('tenant');
        $data = $request->validated();

        $data['reception_id'] = Auth::guard('hospital_user')->id();

        $patient = $this->patientService->registerWalkIn($data, $tenant->id);

        return redirect()->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'create'])
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
        $locations = Location::orderBy('city')->get();
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

        return redirect()->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'create-phone'])
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
            fn (Patient $patient): string => $patient->appointment_date
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
        $locations = Location::orderBy('city')->get();
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

        return redirect()->route('hospital.patients.index', ['slug' => $slug])
            ->with('success', 'Patient updated.');
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
                    ->orWhereHas('role', fn ($r) => $r->where(function ($i) {
                        $i->whereIn('slug', ['doctor', 'ot_doctor'])
                            ->orWhereIn('name', ['doctor', 'ot_doctor']);
                    }));
            })->get();

        $locations = Location::orderBy('city')->get();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->get();
        $slots = $this->loadPatientSlots($tenantId);

        return view('hospital.patients.checkin',
            compact('patient', 'slug', 'doctors', 'locations', 'referrers', 'cases', 'slots'));
    }

    public function checkin(Request $request, string $slug, Patient $patient): RedirectResponse
    {
        abort_if($patient->type !== 'phone', 404);

        $request->validate([
            'first_name'       => ['required', 'string', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'age'              => ['required', 'integer', 'min:0', 'max:150'],
            'gender'           => ['required', 'in:male,female,other'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'contact_no'       => ['required', 'string', 'max:15', 'regex:/^\d+$/'],
            'whatsapp_no'      => ['nullable', 'string', 'max:15', 'regex:/^\d+$/'],
            'location_id'      => ['required', 'integer', 'exists:tbl_locations,id'],
            'appointment_date' => ['required', 'date'],
            'slot_id'          => ['nullable', 'integer', 'exists:tbl_slots,id'],
            'doctor_id'        => ['required', 'integer', 'exists:hospital_users,id'],
            'case_id'          => ['required', 'integer', 'exists:tbl_cases,id'],
            'case_fee'         => ['required', 'numeric', 'min:0'],
            'referrer_id'      => ['nullable', 'integer', 'exists:tbl_referrers,id'],
        ]);

        DB::transaction(function () use ($request, $patient) {
            $patient->update(array_merge(
                $request->only([
                    'first_name', 'middle_name', 'last_name', 'age', 'gender',
                    'occupation', 'contact_no', 'whatsapp_no', 'location_id',
                    'appointment_date', 'slot_id', 'doctor_id', 'case_id',
                    'case_fee', 'referrer_id',
                ]),
                ['checked_in_at' => $patient->checked_in_at ?? now()]
            ));

            $this->patientService->assignDoctorSerial(
                $patient->fresh(),
                (int) $request->doctor_id,
                $request->appointment_date,
                app('tenant')->id
            );
        });

        return redirect()
            ->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'create'])
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
