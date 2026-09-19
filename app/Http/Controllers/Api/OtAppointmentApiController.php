<?php

/**
 * OtAppointmentApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtAppointmentController (web).
 *          OT Workflow Upgrade — Phase 2 (Appointment Module). See docs/OT_WORKFLOW_UPGRADE_PRD.md §2
 *          and docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §6 (FR-OT-22..25).
 *
 * PERMISSIONS: ot.appointment.view (index/search/slot-appointments/form-data),
 *              ot.appointment.create (store), ot.appointment.edit (update/confirm/cancel).
 *              search is additionally reachable via opd.patient.register (reception check-in),
 *              matching the web route's OR-gate exactly.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtSlot;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Support\PhoneRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtAppointmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = strtolower((string) $request->query('status', 'all'));
        if (! in_array($status, ['all', OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED, OtAppointment::STATUS_CANCELLED, OtAppointment::STATUS_COMPLETED], true)) {
            $status = 'all';
        }

        // convertedPatient.latestOtBooking is eager-loaded so the stage_label/
        // stage_badge_class accessors below (same ones web's index.blade.php
        // reads) stay N+1-safe across the whole page.
        $query = OtAppointment::query()->with(['doctor:id,name', 'location:id,name', 'convertedPatient.latestOtBooking']);

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
                    $q->orWhere('appointment_seq', (int) ltrim(substr($search, 4), '0'));
                }
            });
        }

        $appointments = $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25))
            // Same granular stage web's index shows (Surgery Recommended, In
            // Ward, Ready for OT, etc.) instead of the raw 4-value status —
            // apps were previously blind to this and showed only the enum.
            ->through(fn (OtAppointment $appt) => [
                ...$appt->toArray(),
                // appointment_number is a computed accessor (APT-000123),
                // not a real column — never in toArray() on its own, so the
                // apps' list/edit screens saw it as blank without this.
                'appointment_number' => $appt->appointment_number,
                'stage_label' => $appt->stage_label,
                'stage_badge_class' => $appt->stage_badge_class,
            ]);

        return response()->json(['success' => true, 'data' => $appointments]);
    }

    /**
     * Dropdown/form data for the create + edit screens — not in the original PRD skeleton,
     * added because the mobile app can't build the appointment form without it (mirrors
     * what web's create()/edit() pass to the blade view).
     */
    public function formData(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('slug', ['doctor']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = $this->citiesForCurrentHospital();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = OtSlot::query()->orderBy('start_time')->get();

        $nextSeq = OtAppointment::peekNextSequenceForTenant($tenantId);
        $nextAppointmentNumber = OtAppointment::formatAppointmentNumber($nextSeq);

        return response()->json([
            'success' => true,
            'data' => [
                'doctors' => $doctors,
                'locations' => $locations,
                'referrers' => $referrers,
                'slots' => $slots,
                'next_appointment_number' => $nextAppointmentNumber,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            // TYPE_OT (not TYPE_REFERRAL, which never existed on the model —
            // caused a fatal error on every save/update). Matches web's real
            // 4 types exactly (_form.blade.php). See OT_BUGS_ROUND4_AUDIT.md.
            'appointment_type' => ['required', Rule::in([
                OtAppointment::TYPE_PHONE, OtAppointment::TYPE_WALK_IN, OtAppointment::TYPE_ONLINE, OtAppointment::TYPE_OT,
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

        $appointment = DB::transaction(function () use ($validated, $tenantId) {
            return OtAppointment::create([
                ...$validated,
                'tenant_id' => $tenantId,
                'appointment_seq' => OtAppointment::allocateNextSequenceForTenant($tenantId),
                'status' => OtAppointment::STATUS_BOOKED,
                'created_by' => (int) auth('sanctum')->id(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Appointment booked successfully.',
            'data' => $appointment,
        ], 201);
    }

    public function update(string $slug, Request $request, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $appointment = OtAppointment::query()->find($id);

        if (! $appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found.'], 404);
        }

        $validated = $request->validate([
            // TYPE_OT (not TYPE_REFERRAL, which never existed on the model —
            // caused a fatal error on every save/update). Matches web's real
            // 4 types exactly (_form.blade.php). See OT_BUGS_ROUND4_AUDIT.md.
            'appointment_type' => ['required', Rule::in([
                OtAppointment::TYPE_PHONE, OtAppointment::TYPE_WALK_IN, OtAppointment::TYPE_ONLINE, OtAppointment::TYPE_OT,
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

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully.',
            'data' => $appointment->fresh(['doctor:id,name', 'location:id,name']),
        ]);
    }

    public function confirm(string $slug, int $id): JsonResponse
    {
        $appointment = OtAppointment::query()->find($id);

        if (! $appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found.'], 404);
        }

        $appointment->update(['status' => OtAppointment::STATUS_CONFIRMED]);

        return response()->json(['success' => true, 'message' => 'Appointment confirmed.', 'data' => ['status' => $appointment->status]]);
    }

    public function cancel(string $slug, int $id): JsonResponse
    {
        $appointment = OtAppointment::query()->find($id);

        if (! $appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found.'], 404);
        }

        $appointment->update(['status' => OtAppointment::STATUS_CANCELLED]);

        return response()->json(['success' => true, 'message' => 'Appointment cancelled.', 'data' => ['status' => $appointment->status]]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['success' => true, 'data' => ['found' => false, 'appointments' => []]]);
        }

        // Matches web's `Hospital\OT\OtAppointmentController::search()` exactly
        // — this endpoint feeds the Add Patient form's "OT check-in search"
        // (mirrored in the apps' walk-in patient form), which only makes
        // sense for appointments that are (a) today's, since check-in is a
        // same-day action, and (b) not already converted to a patient —
        // without these two conditions, the picker previously surfaced
        // stale/other-day appointments and ones already checked in.
        $query = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name'])
            ->whereIn('status', [OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED])
            ->whereDate('appointment_date', now()->toDateString())
            ->whereNull('converted_patient_id');

        if (preg_match('/^APT-?0*(\d+)$/i', $term, $matches)) {
            $query->where('appointment_seq', (int) $matches[1]);
        } else {
            $query->where(function ($q) use ($term): void {
                $q->where('mobile_no', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%");
            });
        }

        $appointments = $query->latest('appointment_date')->limit(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'found' => $appointments->isNotEmpty(),
                // `doctor`/`location` are nested {id, name} objects (not the
                // flat `doctor_id`/`location_id` this endpoint sent before) so
                // they parse the same way `OtAppointmentItem.fromJson` already
                // reads them off the `/ot/appointments` list endpoint — the
                // flat id/name fields were silently unusable by the apps'
                // shared model (it only ever reads the nested shape), which
                // meant a picked appointment's doctor/city could never
                // auto-fill even though the data was in the payload.
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
                    'location' => $appointment->location ? ['id' => $appointment->location->id, 'name' => $appointment->location->name] : null,
                    'doctor_id' => $appointment->doctor_id,
                    'doctor_name' => $appointment->doctor?->name,
                    'doctor' => $appointment->doctor ? ['id' => $appointment->doctor->id, 'name' => $appointment->doctor->name] : null,
                    'appointment_date' => optional($appointment->appointment_date)->format('d M Y'),
                ])->values(),
            ],
        ]);
    }

    public function slotAppointments(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $time = $request->query('time');

        if (! $date || ! $time) {
            return response()->json(['success' => true, 'data' => ['appointments' => []]]);
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
            'success' => true,
            'data' => [
                'appointments' => $appointments->map(fn (OtAppointment $appointment) => [
                    'id' => $appointment->id,
                    'name' => trim(collect([$appointment->patient_name, $appointment->surname])->filter()->implode(' ')),
                    'status' => $appointment->status,
                ])->values(),
            ],
        ]);
    }

    private function citiesForCurrentHospital()
    {
        $hospitalCountry = auth('sanctum')->user()?->tenant?->country;

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
