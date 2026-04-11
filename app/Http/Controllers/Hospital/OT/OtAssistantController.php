<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtLensDetail;
use App\Models\Hospital\OT\OtLensOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAssistantController extends Controller
{
    public function dashboard(string $slug): View
    {
        $bookings = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->where('ot_status', OtBooking::STATUS_OPERATED)
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        return view('hospital.ot.assistant.dashboard', [
            'slug' => $slug,
            'bookings' => $bookings,
        ]);
    }

    public function editLens(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no'])
            ->findOrFail($bookingId);

        $lensDetail = OtLensDetail::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        $lensTypes = OtLensOption::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['name']);

        return view('hospital.ot.assistant.lens_form', [
            'slug' => $slug,
            'booking' => $booking,
            'lensDetail' => $lensDetail,
            'lensTypes' => $lensTypes,
        ]);
    }

    public function storeLens(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->findOrFail($bookingId);

        $validated = $request->validate([
            'lens_name' => ['required', 'string', 'max:255'],
            'lens_type' => [
                'required',
                Rule::exists('ot_lens_options', 'name')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'lens_power' => ['required', 'numeric', 'between:-99.99,99.99'],
            'lens_mrp' => ['required', 'numeric', 'min:0'],
            'implanted' => ['nullable', 'boolean'],
        ]);

        $isImplanted = (bool) ($validated['implanted'] ?? false);

        OtLensDetail::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
            ],
            [
                'lens_name' => $validated['lens_name'],
                'lens_type' => $validated['lens_type'],
                'lens_power' => $validated['lens_power'],
                'lens_mrp' => $validated['lens_mrp'],
                'is_implanted' => $isImplanted,
                'entered_by' => (int) auth('hospital_user')->id(),
            ]
        );

        if ($isImplanted) {
            $booking->update([
                'ot_status' => OtBooking::STATUS_OPERATED,
                'operated_at' => $booking->operated_at ?? now(),
            ]);
        }

        return redirect()
            ->route('hospital.ot.assistant.dashboard', ['slug' => $slug])
            ->with('success', 'Lens details saved successfully.');
    }
}
