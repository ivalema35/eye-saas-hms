<?php

/**
 * OtAppointmentController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 2 (Appointment Module).
 *          Pre-registration appointments — booked over phone/walk-in/online/referral
 *          BEFORE the patient physically arrives. Reception check-in (Patient module)
 *          searches these by appointment number/mobile to pre-fill OPD registration.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §2.
 *
 * PERMISSIONS: ot.appointment.view (index/search), ot.appointment.create (store),
 *              ot.appointment.edit (update/cancel/confirm)
 */

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtSlot;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Support\PhoneRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAppointmentController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $status = strtolower((string) $request->query('status', 'all'));
        if (! in_array($status, ['all', OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED, OtAppointment::STATUS_CANCELLED, OtAppointment::STATUS_COMPLETED], true)) {
            $status = 'all';
        }

        $query = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($date = $request->query('date')) {
            $query->whereDate('appointment_date', $date);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%");

                if (str_starts_with(strtoupper($search), 'APT-') && ctype_digit(ltrim(substr($search, 4), '0'))) {
                    $q->orWhere('id', (int) ltrim(substr($search, 4), '0'));
                }
            });
        }

        $appointments = $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->appends($request->query());

        return view('hospital.ot.appointments.index', [
            'slug' => $slug,
            'appointments' => $appointments,
            'activeStatus' => $status,
        ]);
    }

    public function create(string $slug): View
    {
        $tenantId = (int) app('tenant')->id;

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['doctor']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = $this->citiesForCurrentHospital();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = OtSlot::query()->orderBy('start_time')->get();

        $nextId = ((int) OtAppointment::withoutTenantScope()->max('id')) + 1;
        $nextAppointmentNumber = 'APT-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        return view('hospital.ot.appointments.create', [
            'slug' => $slug,
            'doctors' => $doctors,
            'locations' => $locations,
            'referrers' => $referrers,
            'slots' => $slots,
            'nextAppointmentNumber' => $nextAppointmentNumber,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'appointment_type' => ['required', Rule::in([
                OtAppointment::TYPE_PHONE, OtAppointment::TYPE_WALK_IN, OtAppointment::TYPE_ONLINE, OtAppointment::TYPE_REFERRAL,
            ])],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['required', 'integer', Rule::exists('hospital_users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'patient_name' => ['required', 'string', 'max:200'],
            'middle_name' => ['nullable', 'string', 'max:200'],
            'surname' => ['required', 'string', 'max:200'],
            'mobile_no' => PhoneRules::required(20),
            'whatsapp_no' => PhoneRules::nullable(20),
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'referrer_id' => ['nullable', 'integer', Rule::exists('tbl_referrers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], array_merge(PhoneRules::messages('mobile_no'), PhoneRules::messages('whatsapp_no')));

        OtAppointment::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'status' => OtAppointment::STATUS_BOOKED,
            'created_by' => (int) auth('hospital_user')->id(),
        ]);

        return redirect()
            ->route('hospital.ot.appointments.index', ['slug' => $slug])
            ->with('success', 'Appointment booked successfully.');
    }

    public function edit(string $slug, int $id): View
    {
        $appointment = OtAppointment::query()->findOrFail($id);

        $tenantId = (int) app('tenant')->id;

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['doctor']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = $this->citiesForCurrentHospital();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = OtSlot::query()->orderBy('start_time')->get();

        return view('hospital.ot.appointments.edit', [
            'slug' => $slug,
            'appointment' => $appointment,
            'doctors' => $doctors,
            'locations' => $locations,
            'referrers' => $referrers,
            'slots' => $slots,
        ]);
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $appointment = OtAppointment::query()->findOrFail($id);

        $validated = $request->validate([
            'appointment_type' => ['required', Rule::in([
                OtAppointment::TYPE_PHONE, OtAppointment::TYPE_WALK_IN, OtAppointment::TYPE_ONLINE, OtAppointment::TYPE_REFERRAL,
            ])],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['required', 'integer', Rule::exists('hospital_users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'patient_name' => ['required', 'string', 'max:200'],
            'middle_name' => ['nullable', 'string', 'max:200'],
            'surname' => ['required', 'string', 'max:200'],
            'mobile_no' => PhoneRules::required(20),
            'whatsapp_no' => PhoneRules::nullable(20),
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'referrer_id' => ['nullable', 'integer', Rule::exists('tbl_referrers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], array_merge(PhoneRules::messages('mobile_no'), PhoneRules::messages('whatsapp_no')));

        $appointment->update($validated);

        return redirect()
            ->route('hospital.ot.appointments.index', ['slug' => $slug])
            ->with('success', 'Appointment updated successfully.');
    }

    public function confirm(string $slug, int $id): RedirectResponse
    {
        $appointment = OtAppointment::query()->findOrFail($id);
        $appointment->update(['status' => OtAppointment::STATUS_CONFIRMED]);

        return redirect()->back()->with('success', 'Appointment confirmed.');
    }

    public function cancel(string $slug, int $id): RedirectResponse
    {
        $appointment = OtAppointment::query()->findOrFail($id);
        $appointment->update(['status' => OtAppointment::STATUS_CANCELLED]);

        return redirect()->back()->with('success', 'Appointment cancelled.');
    }

    /**
     * AJAX search used by Reception check-in (Patient registration) — matches by
     * appointment number (APT-000123), mobile number, or patient name; returns only
     * appointments not yet converted/cancelled.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['found' => false, 'appointments' => []]);
        }

        $query = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name'])
            ->whereIn('status', [OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED]);

        if (preg_match('/^APT-?0*(\d+)$/i', $term, $matches)) {
            $query->where('id', (int) $matches[1]);
        } else {
            $query->where(function ($q) use ($term): void {
                $q->where('mobile_no', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%");
            });
        }

        $appointments = $query->latest('appointment_date')->limit(10)->get();

        return response()->json([
            'found' => $appointments->isNotEmpty(),
            'appointments' => $appointments->map(fn (OtAppointment $appointment) => [
                'id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'patient_name' => $appointment->patient_name,
                'middle_name' => $appointment->middle_name,
                'surname' => $appointment->surname,
                'mobile_no' => $appointment->mobile_no,
                'whatsapp_no' => $appointment->whatsapp_no,
                'age' => $appointment->age,
                'gender' => $appointment->gender,
                'occupation' => $appointment->occupation,
                'referrer_id' => $appointment->referrer_id,
                'location_id' => $appointment->location_id,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor?->name,
                'appointment_date' => optional($appointment->appointment_date)->format('d M Y'),
            ])->values(),
        ]);
    }

    /**
     * AJAX lookup used on the appointment form — when the user picks a date + time
     * slot, shows which patients are already booked in that same slot so double
     * bookings are visible before saving.
     */
    public function slotAppointments(Request $request, string $slug): JsonResponse
    {
        $date = $request->query('date');
        $time = $request->query('time');

        if (! $date || ! $time) {
            return response()->json(['appointments' => []]);
        }

        $query = OtAppointment::query()
            ->whereDate('appointment_date', $date)
            ->whereTime('appointment_time', $time)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED);

        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', (int) $excludeId);
        }

        $appointments = $query->orderBy('id')->get(['id', 'patient_name', 'surname', 'status']);

        return response()->json([
            'appointments' => $appointments->map(fn (OtAppointment $appointment) => [
                'id' => $appointment->id,
                'name' => trim(collect([$appointment->patient_name, $appointment->surname])->filter()->implode(' ')),
                'status' => $appointment->status,
            ])->values(),
        ]);
    }

    /**
     * City dropdown scoped to the hospital's own country — same filter used by
     * the OPD walk-in patient form (PatientController@create). Without this,
     * every city in the platform-wide master list shows up regardless of which
     * country the hospital was registered under.
     */
    private function citiesForCurrentHospital()
    {
        $hospitalCountry = auth('hospital_user')->user()?->tenant?->country;

        return MasterCity::query()
            ->with(['district', 'state'])
            ->when($hospitalCountry, function ($query) use ($hospitalCountry): void {
                $query->whereHas('state.country', function ($q) use ($hospitalCountry): void {
                    $q->where('name', $hospitalCountry);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'district_id', 'state_id']);
    }
}
