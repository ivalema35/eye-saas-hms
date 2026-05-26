<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAccountantController extends Controller
{
    public function wardIndex(string $slug): View
    {
        $bookings = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state'])
            ->whereIn('ot_status', [OtBooking::STATUS_PAID, OtBooking::STATUS_IN_WARD, OtBooking::STATUS_READY])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        return view('hospital.ot.accountant.ward', [
            'slug' => $slug,
            'bookings' => $bookings,
        ]);
    }

    public function dashboard(Request $request, string $slug): View
    {
        $filter = strtolower((string) $request->query('filter', 'all'));
        if (! in_array($filter, ['all', 'today'], true)) {
            $filter = 'all';
        }

        $bookingsQuery = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state'])
            ->whereIn('ot_status', [OtBooking::STATUS_BOOKED, OtBooking::STATUS_PAID]);

        if ($filter === 'today') {
            $bookingsQuery->whereDate('surgery_date', today());
        }

        $bookings = $bookingsQuery
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25))
            ->appends($request->query());

        return view('hospital.ot.accountant.dashboard', [
            'slug' => $slug,
            'bookings' => $bookings,
            'activeFilter' => $filter,
        ]);
    }

    public function createPayment(string $slug, int $bookingId): View
    {
        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state'])
            ->findOrFail($bookingId);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', app('tenant')->id)
            ->where('ot_booking_id', $booking->id)
            ->first();

        return view('hospital.ot.accountant.payment', [
            'slug' => $slug,
            'booking' => $booking,
            'counselling' => $counselling,
            'defaultPackageAmount' => $counselling?->package_amount ?? $booking->package_amount,
        ]);
    }

    public function storePayment(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $booking = OtBooking::query()->findOrFail($bookingId);

        $validated = $request->validate([
            'package_amount' => ['required', 'numeric', 'min:0'],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', 'string', Rule::in(['cash', 'online', 'mediclaim'])],
        ]);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', app('tenant')->id)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $hasMediclaim = (bool) ($counselling?->mediclaim ?? $booking->has_mediclaim);
        $packageAmount = (float) $validated['package_amount'];
        $exportAmount = $hasMediclaim ? $packageAmount : ($packageAmount / 2);
        $accountantId = (int) auth('hospital_user')->id();

        DB::transaction(function () use ($booking, $validated, $hasMediclaim, $accountantId, $exportAmount): void {
            $payload = [
                'tenant_id' => app('tenant')->id,
                'ot_booking_id' => $booking->id,
                'package_amount' => $validated['package_amount'],
                'has_mediclaim' => $hasMediclaim,
                'receipt_number' => $validated['receipt_number'] ?? null,
                'payment_mode' => strtolower($validated['payment_mode']),
                'recorded_by' => $accountantId,
                'paid_at' => now(),
            ];

            // Backward/forward compatibility: persist extra report/accountant fields when available.
            if (Schema::hasColumn('ot_payments', 'export_amount')) {
                $payload['export_amount'] = $exportAmount;
            }
            if (Schema::hasColumn('ot_payments', 'accountant_id')) {
                $payload['accountant_id'] = $accountantId;
            }

            OtPayment::query()->create($payload);

            // Ensure counselling record (if exists) reflects the latest package amount
            DB::table('ot_counselling')
                ->where('tenant_id', app('tenant')->id)
                ->where('ot_booking_id', $booking->id)
                ->update([
                    'package_amount' => $validated['package_amount'],
                    'updated_at' => now(),
                ]);

            $booking->update([
                'ot_status' => OtBooking::STATUS_PAID,
                'payment_mode' => strtolower($validated['payment_mode']),
                'package_amount' => $validated['package_amount'],
                'has_mediclaim' => $hasMediclaim,
            ]);
        });

        return redirect()
            ->route('hospital.ot.accountant.dashboard', ['slug' => $slug])
            ->with('success', 'Payment recorded successfully. Export amount: '.number_format($exportAmount, 2));
    }

    public function markReadyForOt(string $slug, OtBooking $booking): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if (! in_array($booking->ot_status, [OtBooking::STATUS_PAID, OtBooking::STATUS_IN_WARD], true)) {
            return redirect()->back()->with('error', 'Only paid or in-ward patients can be handed off to OT Doctor.');
        }

        $booking->update([
            'ot_status' => OtBooking::STATUS_READY,
            'attended_at' => $booking->attended_at ?? now(),
        ]);

        return redirect()->back()->with('success', 'Patient is ready and handed off to OT Doctor');
    }
}
