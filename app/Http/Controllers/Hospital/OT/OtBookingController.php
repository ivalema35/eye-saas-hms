<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtLensOption;
use App\Models\Hospital\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtBookingController extends Controller
{
    public function index(string $slug): View
    {
        $bookings = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name', 'otDoctor:id,name'])
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        return view('hospital.ot.bookings.index', [
            'slug' => $slug,
            'bookings' => $bookings,
        ]);
    }

    public function create(string $slug): View
    {
        $tenantId = (int) app('tenant')->id;

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->whereIn('slug', ['doctor', 'ot_doctor']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $slots = DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->orderBy('slot_name')
            ->get(['id', 'slot_name', 'start_time', 'end_time']);

        $otTypes = DB::table('ot_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $surgeryTypes = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('surgery_name')
            ->get(['id', 'ot_type_id', 'surgery_name']);

        $lensOptions = OtLensOption::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $patients = Patient::query()
            ->whereNotNull('secondary_done_at')
            ->orderByDesc('secondary_done_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'patient_code', 'first_name', 'middle_name', 'last_name', 'contact_no']);

        return view('hospital.ot.bookings.create', [
            'slug' => $slug,
            'doctors' => $doctors,
            'slots' => $slots,
            'otTypes' => $otTypes,
            'surgeryTypes' => $surgeryTypes,
            'lensOptions' => $lensOptions,
            'patients' => $patients,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')],
            'surgery_date' => ['required', 'date', 'after_or_equal:today'],
            'slot_id' => ['required', 'integer', Rule::exists('tbl_ot_slots', 'id')],
            'ot_doctor_id' => ['required', 'integer', Rule::exists('hospital_users', 'id')],
            'eye' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            'ot_type_id' => ['required', 'integer', Rule::exists('ot_types', 'id')],
            'ot_surgery_type_id' => ['required', 'integer', Rule::exists('ot_surgery_types', 'id')],
            'mediclaim' => ['required', 'boolean'],
            'lens_option' => [
                'nullable',
                Rule::exists('ot_lens_options', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('is_active', true)),
            ],
            'package_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', Rule::in(['Cash', 'Online'])],
            'report_ok' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $surgeryType = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->where('id', (int) $validated['ot_surgery_type_id'])
            ->first(['id', 'surgery_name']);

        abort_if(! $surgeryType, 422, 'Invalid surgery type for this tenant.');

        DB::transaction(function () use ($validated, $tenantId, $surgeryType): void {
            $booking = OtBooking::create([
                'tenant_id' => $tenantId,
                'patient_id' => (int) $validated['patient_id'],
                'ot_doctor_id' => (int) $validated['ot_doctor_id'],
                'booked_by' => (int) auth('hospital_user')->id(),
                'surgery_date' => $validated['surgery_date'],
                'slot_id' => (int) $validated['slot_id'],
                'eye' => $validated['eye'],
                'ot_type' => $surgeryType->surgery_name,
                'reports_ok' => (bool) ($validated['report_ok'] ?? false),
                'has_mediclaim' => (bool) $validated['mediclaim'],
                'lens_option' => $validated['lens_option'] ?? null,
                'package_amount' => $validated['package_amount'] ?? null,
                'payment_mode' => isset($validated['payment_mode'])
                    ? strtolower((string) $validated['payment_mode'])
                    : null,
                'ot_status' => 'booked',
            ]);

            DB::table('ot_counselling')->insert([
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
                'mediclaim' => (bool) $validated['mediclaim'],
                'lens_option' => $validated['lens_option'] ?? null,
                'package_amount' => $validated['package_amount'] ?? null,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'report_ok' => (bool) ($validated['report_ok'] ?? false),
                'notes' => $validated['notes'] ?? null,
                'created_by' => (int) auth('hospital_user')->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('hospital.ot.bookings.index', ['slug' => $slug])
            ->with('success', 'OT booking created successfully.');
    }
}
