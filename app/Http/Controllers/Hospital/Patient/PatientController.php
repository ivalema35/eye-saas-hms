<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
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

        $patients = Patient::where('contact_no', $contact)->latest()->get();

        return response()->json([
            'found' => $patients->isNotEmpty(),
            'patients' => $patients->map(fn ($patient) => [
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
        $slots = $this->loadPatientSlots($tenantId);

        return view('hospital.patients.create', compact('slug', 'doctors', 'locations', 'cases', 'slots', 'referrers'));
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

    public function storePhone(PatientStoreRequest $request): RedirectResponse
    {
        $slug = $request->route('slug');
        $tenant = app('tenant');
        $data = $request->validated();

        $data['reception_id'] = Auth::guard('hospital_user')->id();

        $patient = $this->patientService->registerPhone($data, $tenant->id);

        return redirect()->route('hospital.patients.print', ['slug' => $slug, 'patient' => $patient->id, 'auto_print' => 1, 'return_to' => 'create'])
            ->with('success', 'Phone appointment registered and ready for printing.');
    }

    public function phoneHistory(Request $request): View
    {
        $slug = $request->route('slug');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = Patient::with(['doctor:id,name', 'reception:id,name'])
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
        $patient->load('primaryExamination', 'secondaryExamination', 'doctor', 'reception');

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
