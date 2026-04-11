<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtSurgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtDoctorController extends Controller
{
    public function dashboard(string $slug): View
    {
        $doctorId = (int) auth('hospital_user')->id();

        $bookings = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->where('ot_doctor_id', $doctorId)
            ->where('ot_status', OtBooking::STATUS_READY)
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        return view('hospital.ot.doctor.dashboard', [
            'slug' => $slug,
            'bookings' => $bookings,
        ]);
    }

    public function createSurgery(string $slug, int $bookingId): View
    {
        $doctorId = (int) auth('hospital_user')->id();

        $booking = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->where('ot_doctor_id', $doctorId)
            ->findOrFail($bookingId);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', app('tenant')->id)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $surgeryTypes = DB::table('ot_surgery_types')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->orderBy('surgery_name')
            ->get(['id', 'surgery_name']);

        $medicines = Medicine::query()
            ->orderBy('name')
            ->get(['name']);

        return view('hospital.ot.doctor.surgery', [
            'slug' => $slug,
            'booking' => $booking,
            'counselling' => $counselling,
            'surgeryTypes' => $surgeryTypes,
            'medicines' => $medicines,
        ]);
    }

    public function storeSurgery(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $doctorId = (int) auth('hospital_user')->id();

        $booking = OtBooking::query()
            ->where('ot_doctor_id', $doctorId)
            ->findOrFail($bookingId);

        $validated = $request->validate([
            'surgery_name' => ['required', 'string', 'max:255'],
            'eye_operated' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            'complication_status' => ['required', Rule::in(['none', 'minor', 'major'])],
            'complication_notes' => ['nullable', 'string', 'max:4000'],
            'ward_medicines' => ['nullable', 'array'],
            'ward_medicines.*.medicine' => [
                'nullable',
                Rule::exists('medicines', 'name')->where(fn ($query) => $query
                    ->where('tenant_id', app('tenant')->id)
                    ->whereNull('deleted_at')),
            ],
            'ward_medicines.*.dose' => ['nullable', 'string', 'max:255'],
        ]);

        $wardMedicines = collect($validated['ward_medicines'] ?? [])
            ->filter(fn (array $item): bool => ! empty($item['medicine']) || ! empty($item['dose']))
            ->values()
            ->all();

        DB::transaction(function () use ($validated, $wardMedicines, $doctorId, $booking): void {
            OtSurgery::query()->create([
                'tenant_id' => app('tenant')->id,
                'ot_booking_id' => $booking->id,
                'operated_by' => $doctorId,
                'surgery_name' => $validated['surgery_name'],
                'eye_operated' => $validated['eye_operated'],
                'complication_status' => $validated['complication_status'],
                'complication_notes' => $validated['complication_notes'] ?? null,
                'ward_medicines' => $wardMedicines !== [] ? $wardMedicines : null,
                'surgery_status' => 'operated',
                'complication' => $validated['complication_notes'] ?? null,
                'surgery_at' => now(),
            ]);

            $booking->update([
                'ot_status' => OtBooking::STATUS_OPERATED,
                'operated_at' => now(),
            ]);
        });

        return redirect()
            ->route('hospital.ot.doctor.dashboard', ['slug' => $slug])
            ->with('success', 'Surgery recorded successfully.');
    }
}
